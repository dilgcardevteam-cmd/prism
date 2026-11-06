<?php

namespace Tests\Feature;

use App\Mail\LoginOtpMail;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileApiSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('mobile_access_tokens');
        Schema::dropIfExists('mobile_login_challenges');
        Schema::dropIfExists('tbnotifications');
        Schema::dropIfExists('locally_funded_projects');
        Schema::dropIfExists('tbusers');

        Schema::create('tbusers', function (Blueprint $table): void {
            $table->id('idno');
            $table->string('fname');
            $table->string('lname');
            $table->string('agency');
            $table->string('position');
            $table->string('region');
            $table->string('province');
            $table->string('office')->nullable();
            $table->string('emailaddress')->unique();
            $table->string('mobileno');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->string('status')->default('active');
            $table->text('access')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('mobile_login_challenges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('challenge_hash', 64)->unique();
            $table->text('encrypted_token')->nullable();
            $table->string('otp_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('tbnotifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('message')->nullable();
            $table->text('url')->nullable();
            $table->string('document_type')->nullable();
            $table->string('quarter')->nullable();
            $table->string('sender_name')->nullable();
            $table->unsignedBigInteger('sender_user_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('locally_funded_projects', function (Blueprint $table): void {
            $table->id();
        });
        Mail::fake();
        Queue::fake();
    }

    public function test_mobile_profile_and_notifications_require_a_bearer_token(): void
    {
        $this->getJson('/api/mobile/notifications')->assertUnauthorized();
        $this->getJson('/api/mobile/user/profile')->assertUnauthorized();
        $this->getJson('/api/mobile/messages')->assertUnauthorized();
        $this->postJson('/api/mobile/messages', ['message' => 'unauthenticated'])->assertUnauthorized();
        $this->postJson('/api/mobile/messages/typing', ['typing' => true])->assertUnauthorized();
        $this->postJson('/api/mobile/user/profile/update', [
            'first_name' => 'Changed',
            'last_name' => 'Name',
            'position' => 'Position',
            'phone' => '09123456789',
        ])->assertUnauthorized();
    }

    public function test_mobile_token_scopes_profile_and_notifications_to_authenticated_user(): void
    {
        $owner = $this->createUser('owner', 'Owner');
        $other = $this->createUser('other', 'Other');
        DB::table('tbnotifications')->insert([
            [
                'user_id' => $owner->idno,
                'message' => 'Owner notification',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $other->idno,
                'message' => 'Other notification',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $login = $this->postJson('/api/mobile/login', [
            'username' => 'owner',
            'password' => 'Password1!',
        ])->assertAccepted()
            ->assertJsonPath('requires_otp', true);

        $challengeToken = $login->json('challenge_token');
        $this->assertIsString($challengeToken);
        $this->assertDatabaseHas('mobile_login_challenges', [
            'user_id' => $owner->idno,
            'challenge_hash' => hash('sha256', $challengeToken),
        ]);
        $this->assertDatabaseMissing('mobile_access_tokens', ['user_id' => $owner->idno]);
        Queue::assertNothingPushed();
        $this->assertCount(1, Mail::sent(LoginOtpMail::class));

        $repeatedLogin = $this->postJson('/api/mobile/login', [
            'username' => 'owner',
            'password' => 'Password1!',
        ])->assertAccepted();
        $this->assertSame($challengeToken, $repeatedLogin->json('challenge_token'));
        Queue::assertNothingPushed();
        $this->assertCount(1, Mail::sent(LoginOtpMail::class));

        $mail = Mail::sent(LoginOtpMail::class)->first();
        $tokenResponse = $this->postJson('/api/mobile/login/otp', [
            'challenge_token' => $challengeToken,
            'otp' => $mail->otp,
        ])->assertOk()->assertJsonPath('user.id', $owner->idno);

        $token = $tokenResponse->json('access_token');
        $this->assertIsString($token);
        $this->assertDatabaseHas('mobile_access_tokens', [
            'user_id' => $owner->idno,
            'token_hash' => hash('sha256', $token),
        ]);
        $this->assertDatabaseMissing('mobile_access_tokens', ['token_hash' => $token]);

        $this->withToken($token)->getJson('/api/mobile/user/profile')
            ->assertOk()
            ->assertJsonPath('user.id', $owner->idno);
        $this->withToken($token)->getJson('/api/mobile/messages')->assertOk();

        $this->withToken($token)->getJson('/api/mobile/notifications?user_id='.$other->idno)
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.message', 'Owner notification');

        $this->withToken($token)->postJson('/api/mobile/user/profile/update', [
            'user_id' => $other->idno,
            'first_name' => 'Updated',
            'last_name' => 'Owner',
            'position' => 'Position',
            'phone' => '09123456789',
        ])->assertOk();

        $this->assertDatabaseHas('tbusers', [
            'idno' => $owner->idno,
            'fname' => 'Updated',
        ]);
        $this->assertDatabaseHas('tbusers', [
            'idno' => $other->idno,
            'fname' => 'Other',
        ]);

        $this->withToken($token)->postJson('/api/mobile/logout')->assertOk();
        $this->withToken($token)->getJson('/api/mobile/user/profile')->assertUnauthorized();
    }

    public function test_mobile_gallery_mutations_require_a_bearer_token(): void
    {
        DB::table('locally_funded_projects')->insert(['id' => 1]);

        $this->postJson('/api/mobile/locally-funded/1/gallery', [
            'gallery_category' => 'During',
        ])->assertUnauthorized();

        $user = $this->createUser('gallery-user', 'Gallery');
        $user->forceFill(['access' => 'crud:none'])->save();
        $token = str_repeat('t', 80);
        DB::table('mobile_access_tokens')->insert([
            'user_id' => $user->idno,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)->postJson('/api/mobile/locally-funded/1/gallery', [
            'gallery_category' => 'During',
        ])->assertForbidden();
    }

    public function test_expired_tokens_and_inactive_accounts_are_rejected(): void
    {
        $user = $this->createUser('expired', 'Expired');
        $expiredToken = str_repeat('e', 80);
        DB::table('mobile_access_tokens')->insert([
            'user_id' => $user->idno,
            'token_hash' => hash('sha256', $expiredToken),
            'expires_at' => now()->subMinute(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->withToken($expiredToken)->getJson('/api/mobile/user/profile')->assertUnauthorized();

        $activeToken = str_repeat('a', 80);
        DB::table('mobile_access_tokens')->insert([
            'user_id' => $user->idno,
            'token_hash' => hash('sha256', $activeToken),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user->forceFill(['status' => 'inactive'])->save();

        $this->withToken($activeToken)->getJson('/api/mobile/user/profile')->assertUnauthorized();
    }

    public function test_mobile_access_token_expires_after_two_hours_of_inactivity(): void
    {
        $user = $this->createUser('idle-user', 'Idle');
        $token = str_repeat('i', 80);
        DB::table('mobile_access_tokens')->insert([
            'user_id' => $user->idno,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subHours(2)->subMinute(),
            'updated_at' => now()->subHours(2)->subMinute(),
        ]);

        $this->withToken($token)->getJson('/api/mobile/user/profile')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Your session expired due to inactivity. Please sign in again.');

        $this->assertDatabaseMissing('mobile_access_tokens', [
            'token_hash' => hash('sha256', $token),
        ]);
    }

    public function test_mobile_activity_refreshes_the_idle_timeout(): void
    {
        $user = $this->createUser('active-user', 'Active');
        $token = str_repeat('a', 80);
        DB::table('mobile_access_tokens')->insert([
            'user_id' => $user->idno,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subMinutes(119),
            'updated_at' => now()->subMinutes(119),
        ]);

        $this->withToken($token)->getJson('/api/mobile/user/profile')->assertOk();

        $this->assertSame(
            now()->toDateTimeString(),
            DB::table('mobile_access_tokens')
                ->where('token_hash', hash('sha256', $token))
                ->value('updated_at')
        );
    }

    public function test_password_change_uses_authenticated_user_and_revokes_all_mobile_tokens(): void
    {
        $owner = $this->createUser('password-owner', 'Owner');
        $other = $this->createUser('password-other', 'Other');
        $token = str_repeat('p', 80);
        DB::table('mobile_access_tokens')->insert([
            'user_id' => $owner->idno,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)->postJson('/api/mobile/user/password/update', [
            'user_id' => $other->idno,
            'current_password' => 'Password1!',
            'new_password' => 'NewPassword2!',
            'new_password_confirmation' => 'NewPassword2!',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword2!', $owner->fresh()->password));
        $this->assertTrue(Hash::check('Password1!', $other->fresh()->password));
        $this->assertDatabaseMissing('mobile_access_tokens', ['user_id' => $owner->idno]);
    }

    public function test_mobile_login_does_not_issue_a_token_for_an_incorrect_otp(): void
    {
        $user = $this->createUser('otp-user', 'OTP');
        $login = $this->postJson('/api/mobile/login', [
            'username' => $user->username,
            'password' => 'Password1!',
        ])->assertAccepted();

        $this->postJson('/api/mobile/login/otp', [
            'challenge_token' => $login->json('challenge_token'),
            'otp' => '000000',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'The verification code is incorrect.');

        $this->assertDatabaseMissing('mobile_access_tokens', ['user_id' => $user->idno]);
    }

    public function test_mobile_otp_resend_is_limited_to_once_every_five_minutes(): void
    {
        $user = $this->createUser('otp-resend-user', 'OTP');
        $login = $this->postJson('/api/mobile/login', [
            'username' => $user->username,
            'password' => 'Password1!',
        ])->assertAccepted();
        $challengeToken = $login->json('challenge_token');

        $this->postJson('/api/mobile/login/otp/resend', [
            'challenge_token' => $challengeToken,
        ])->assertStatus(429);
        Queue::assertNothingPushed();
        $this->assertCount(1, Mail::sent(LoginOtpMail::class));

        $this->travel(5)->minutes();
        $this->postJson('/api/mobile/login/otp/resend', [
            'challenge_token' => $challengeToken,
        ])->assertOk();
        Queue::assertNothingPushed();
        $this->assertCount(2, Mail::sent(LoginOtpMail::class));
    }

    public function test_token_migration_creates_token_expiry_index_and_user_foreign_key(): void
    {
        Schema::dropIfExists('mobile_access_tokens');
        $migration = require database_path('migrations/2026_10_28_163000_create_mobile_access_tokens_table.php');

        $migration->up();

        $this->assertTrue(Schema::hasColumns('mobile_access_tokens', [
            'user_id',
            'token_hash',
            'expires_at',
        ]));
        $this->assertTrue(Schema::hasIndex('mobile_access_tokens', 'mobile_access_tokens_user_expiry_idx'));
        $this->assertSame(
            'tbusers',
            Schema::getForeignKeys('mobile_access_tokens')[0]['foreign_table']
        );
    }

    public function test_mobile_login_challenge_migration_creates_expiry_index_and_user_foreign_key(): void
    {
        Schema::dropIfExists('mobile_login_challenges');
        $migration = require database_path('migrations/2026_11_06_000000_create_mobile_login_challenges_table.php');

        $migration->up();

        $this->assertTrue(Schema::hasColumns('mobile_login_challenges', [
            'user_id',
            'challenge_hash',
            'encrypted_token',
            'otp_hash',
            'attempts',
            'expires_at',
        ]));
        $this->assertTrue(Schema::hasIndex('mobile_login_challenges', 'mobile_login_challenges_user_expiry_idx'));
        $this->assertSame(
            'tbusers',
            Schema::getForeignKeys('mobile_login_challenges')[0]['foreign_table']
        );
    }

    private function createUser(string $username, string $firstName): User
    {
        return User::query()->create([
            'fname' => $firstName,
            'lname' => 'Test',
            'agency' => 'DILG',
            'position' => 'Position',
            'region' => 'Region',
            'province' => 'Province',
            'emailaddress' => $username.'@example.test',
            'mobileno' => '09123456789',
            'username' => $username,
            'password' => Hash::make('Password1!'),
            'role' => User::ROLE_LGU,
            'status' => 'active',
            'access' => 'none',
        ]);
    }
}
