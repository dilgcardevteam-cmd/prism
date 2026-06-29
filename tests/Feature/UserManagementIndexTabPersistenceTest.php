<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
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
}
