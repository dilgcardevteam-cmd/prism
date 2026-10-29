<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\SystemSettings\Models\RolePermissionSetting;
use App\Modules\SystemSettings\Models\UserRole;
use App\Support\RolePermissionRegistry;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RoleConfigurationPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('user_roles');
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
            $table->string('role_key')->virtualAs("LOWER(TRIM(COALESCE(role, '')))");
        });

        Schema::create('role_permission_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('role')->unique();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->id();
            $table->string('role_key')->unique();
            $table->string('label')->unique();
            $table->string('base_role');
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tbnotifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        UserRole::flushRoleCache();
        RolePermissionSetting::flushPermissionsCache();
    }

    public function test_role_configuration_groups_user_counts_by_the_role_expression(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);
        User::factory()->create([
            'role' => User::ROLE_LGU,
            'status' => 'active',
        ]);

        $roleCountQuery = null;
        DB::listen(function (QueryExecuted $query) use (&$roleCountQuery): void {
            if (str_contains($query->sql, 'total_users')) {
                $roleCountQuery = $query->sql;
            }
        });

        $response = $this->actingAs($admin)->get(route('utilities.role-configuration.index'));

        $response->assertOk()
            ->assertSee('Role Configuration')
            ->assertSee('Manage User Roles')
            ->assertSee('Approval Management')
            ->assertSee('DILG MC No. 2018-19')
            ->assertSee('DILG MC No. 2018-30');

        $this->assertNotNull($roleCountQuery);
        $this->assertStringContainsString(
            "group by lower(trim(coalesce(role, '')))",
            strtolower($roleCountQuery),
        );
    }

    public function test_approval_management_is_configurable_and_included_in_default_role_access(): void
    {
        $modules = collect(RolePermissionRegistry::modules())
            ->flatMap(fn (array $module): array => $module['items'] ?? [])
            ->keyBy('aspect');

        $this->assertSame(['view'], $modules['approval_management']['actions']);
        $this->assertContains('approval_management.view', RolePermissionRegistry::validPermissionKeys());

        foreach ([
            User::ROLE_REGIONAL,
            User::ROLE_PROVINCIAL,
            User::ROLE_MLGOO,
            User::ROLE_LGU,
        ] as $role) {
            $this->assertContains(
                'approval_management.view',
                RolePermissionRegistry::permissionsForRole($role),
            );
        }

        $this->assertArrayHasKey('quarterly_dilg_mc_2018_19', $modules);
        $this->assertArrayHasKey('quarterly_dilg_mc_2018_30', $modules);
        $this->assertContains('quarterly_dilg_mc_2018_19.update', RolePermissionRegistry::validPermissionKeys());
        $this->assertContains('quarterly_dilg_mc_2018_30.update', RolePermissionRegistry::validPermissionKeys());
    }
}
