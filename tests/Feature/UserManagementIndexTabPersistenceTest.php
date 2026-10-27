<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\SystemSettings\Models\RolePermissionSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserManagementIndexTabPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('tbnotifications');
        Schema::dropIfExists('role_permission_settings');
        Schema::dropIfExists('tbusers');
        Cache::forget('users.management.location_options');
        RolePermissionSetting::flushPermissionsCache();

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

        Schema::create('tbnotifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permission_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('role')->unique();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
    }

    public function test_default_users_tab_does_not_render_role_access_panel(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);

        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Users (', false);
        $response->assertDontSee('Role-Based Access', false);
    }

    public function test_access_grants_pagination_links_keep_the_active_tab(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);

        User::factory()->count(20)->create();

        $response = $this->actingAs($admin)->get(route('users.index', [
            'tab' => 'access-grants',
        ]));

        $response->assertOk();

        $this->assertMatchesRegularExpression(
            '/href="[^"]*(?:page=2&amp;tab=access-grants|tab=access-grants&amp;page=2)[^"]*"/',
            $response->getContent()
        );
    }

    public function test_access_grants_tab_renders_role_access_panel_without_users_filter_form(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);

        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('users.index', [
            'tab' => 'access-grants',
        ]));

        $response->assertOk();
        $response->assertSee('Role-Based Access', false);
        $response->assertDontSee('class="user-filters-form"', false);
    }

    public function test_user_with_noncanonical_inactive_status_is_shown_as_activatable_and_can_be_activated(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'status' => ' INACTIVE ',
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('fa-user-check', false)
            ->assertSee('Activate this user?', false);

        $this->actingAs($admin)
            ->put(route('users.block', $user->idno))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User activated successfully.');

        $this->assertSame('active', $user->fresh()->status);
    }

    public function test_status_toggle_returns_json_without_rendering_user_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($admin)
            ->putJson(route('users.block', $user->idno));

        $response->assertOk()
            ->assertExactJson([
                'success' => true,
                'message' => 'User activated successfully.',
                'status' => 'active',
            ]);
        $this->assertSame('active', $user->fresh()->status);

        $this->putJson(route('users.block', $user->idno))
            ->assertOk()
            ->assertJsonPath('message', 'User deactivated successfully.')
            ->assertJsonPath('status', 'inactive');
        $this->assertSame('inactive', $user->fresh()->status);
    }

    public function test_location_filter_options_are_invalidated_when_user_locations_change(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
            'province' => 'Existing Province',
            'agency' => 'DILG',
        ]);

        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->assertTrue(Cache::has('users.management.location_options'));

        User::factory()->create([
            'province' => 'New Province',
            'agency' => 'LGU',
            'office' => 'New Municipality',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('New Province', false);
        $response->assertSee('New Municipality', false);
    }

    public function test_role_permission_table_availability_is_checked_once_until_cache_is_flushed(): void
    {
        RolePermissionSetting::flushPermissionsCache();
        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->assertTrue(RolePermissionSetting::tableAvailable());
        }

        $this->assertSame(1, $queryCount);
    }
}
