<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActivityLogModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('tbnotifications');
        Schema::dropIfExists('role_permission_settings');
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
            $table->string('verification_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->string('registration_ip_address')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username', 255)->nullable();
            $table->string('action', 50);
            $table->text('description');
            $table->string('timezone', 64)->default('UTC');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device', 255)->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('role_permission_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('role')->unique();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('tbnotifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_superadmin_can_open_activity_logs_page_and_the_visit_is_logged(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('utilities.activity-logs.index'));

        $response->assertOk();
        $response->assertSee('Activity Logs');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->idno,
            'username' => $user->username,
            'action' => ActivityLog::ACTION_READ,
        ]);
    }

    public function test_non_superadmin_cannot_access_activity_logs_page(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_LGU,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('utilities.activity-logs.index'));

        $response->assertForbidden();

        $this->assertDatabaseMissing('activity_logs', [
            'user_id' => $user->idno,
            'action' => ActivityLog::ACTION_READ,
        ]);
    }

    public function test_export_route_streams_csv_and_records_export_event(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);

        ActivityLog::query()->create([
            'user_id' => $user->idno,
            'username' => $user->username,
            'action' => ActivityLog::ACTION_LOGIN,
            'description' => 'User signed in successfully.',
            'timezone' => config('app.timezone'),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'device' => 'Desktop · PHPUnit · Test Runner',
            'created_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->get(route('utilities.activity-logs.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->idno,
            'username' => $user->username,
            'action' => ActivityLog::ACTION_EXPORT,
            'description' => 'Exported activity logs to CSV.',
        ]);
    }
}
