<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MandatoryPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_open_system_setup_with_reset_count(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);

        $this->actingAs($admin)
            ->get(route('utilities.system-setup.index'))
            ->assertOk()
            ->assertSee('Mandatory Password Change')
            ->assertSee('0 non-superadmin account(s) currently require a password change.');
    }

    public function test_superadmin_trigger_flags_non_superadmins_and_revokes_mobile_tokens(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $user = User::factory()->create();
        $inactiveUser = User::factory()->create(['status' => 'inactive']);
        $adminTokenHash = hash('sha256', str_repeat('a', 80));
        $userTokenHash = hash('sha256', str_repeat('u', 80));

        DB::table('mobile_access_tokens')->insert([
            [
                'user_id' => $admin->idno,
                'token_hash' => $adminTokenHash,
                'expires_at' => now()->addDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->idno,
                'token_hash' => $userTokenHash,
                'expires_at' => now()->addDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('utilities.system-setup.mandatory-password-change'), ['confirm' => '1'])
            ->assertRedirect(route('utilities.system-setup.index'));

        $this->assertFalse($admin->fresh()->must_change_password);
        $this->assertTrue($user->fresh()->must_change_password);
        $this->assertSame(1, $user->fresh()->password_security_version);
        $this->assertTrue($inactiveUser->fresh()->must_change_password);
        $this->assertDatabaseHas('mobile_access_tokens', ['token_hash' => $adminTokenHash]);
        $this->assertDatabaseMissing('mobile_access_tokens', ['token_hash' => $userTokenHash]);
    }

    public function test_non_superadmin_cannot_trigger_mandatory_password_change(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('utilities.system-setup.mandatory-password-change'), ['confirm' => '1'])
            ->assertForbidden();

        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_trigger_requires_explicit_confirmation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_SUPERADMIN]);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('utilities.system-setup.index'))
            ->post(route('utilities.system-setup.mandatory-password-change'))
            ->assertRedirect(route('utilities.system-setup.index'))
            ->assertSessionHasErrors('confirm');

        $this->assertFalse($user->fresh()->must_change_password);
    }
}
