<?php

namespace Tests\Feature;

use App\Models\ModuleConfiguration;
use App\Models\User;
use App\Support\RolePermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        ModuleConfiguration::flushCache();
    }

    protected function tearDown(): void
    {
        ModuleConfiguration::flushCache();
        parent::tearDown();
    }

    public function test_superadmin_can_disable_public_registration(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);
        $enabledAspects = collect(RolePermissionRegistry::modules())
            ->flatMap(fn (array $module) => collect($module['items'] ?? [])->pluck('aspect'))
            ->filter()
            ->values()
            ->all();

        $this->actingAs($superAdmin)
            ->get('/utilities/modules-configuration')
            ->assertOk()
            ->assertSee('Allow User Registration');

        $this->actingAs($superAdmin)
            ->post('/utilities/modules-configuration', [
                'modules' => $enabledAspects,
                'registration_enabled' => '0',
            ]);

        $this->assertDatabaseHas('module_configurations', [
            'module_key' => 'user_registration',
            'is_enabled' => false,
        ]);

        ModuleConfiguration::flushCache();
        $this->app['auth']->guard()->logout();
        $this->flushSession();

        $this->get('/register')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('registration');

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('No account? Create one!');

        $this->postJson('/register', [
            'username' => 'blocked-registration',
        ])->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Registration is currently disabled.',
            ]);

        $this->assertDatabaseMissing('tbusers', [
            'username' => 'blocked-registration',
        ]);
    }

    public function test_registration_rejects_disposable_email_addresses(): void
    {
        $this->postJson('/register', [
            'fname' => 'Test',
            'lname' => 'User',
            'agency' => 'DILG',
            'position' => 'Staff',
            'region' => 'Cordillera Administrative Region',
            'province' => 'Benguet',
            'emailaddress' => 'user@sub.mailinator.com',
            'mobileno' => '09123456789',
            'username' => 'disposable-email-user',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['emailaddress']);

        $this->assertDatabaseMissing('tbusers', [
            'username' => 'disposable-email-user',
        ]);
    }

    public function test_disabled_modules_are_hidden_and_direct_routes_are_blocked(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);

        foreach (['dashboard', 'communications', 'announcement_management', 'user_profile', 'road_maintenance_status_reports'] as $module) {
            ModuleConfiguration::updateOrCreate(
                ['module_key' => $module],
                ['is_enabled' => false],
            );
        }
        ModuleConfiguration::flushCache();

        $this->actingAs($superAdmin)
            ->get('/users')
            ->assertOk()
            ->assertDontSee('<span>Dashboard</span>', false)
            ->assertDontSee('<span>Messages</span>', false)
            ->assertDontSee('<span>Announcements</span>', false)
            ->assertDontSee('<span>My Profile</span>', false);

        $this->get('/dashboard')->assertForbidden();
        $this->get('/messages')->assertForbidden();
        $this->get('/announcements')->assertForbidden();
        $this->get('/profile')->assertForbidden();
        $this->get('/road-maintenance-status')->assertForbidden();

        $this->get('/utilities/modules-configuration')
            ->assertOk()
            ->assertSee('Dashboard');
    }
}
