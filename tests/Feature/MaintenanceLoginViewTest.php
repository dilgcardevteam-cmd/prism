<?php

namespace Tests\Feature;

use App\Support\SystemMaintenanceState;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Tests\TestCase;

class MaintenanceLoginViewTest extends TestCase
{
    public function test_public_login_redirects_to_maintenance_notice_when_maintenance_is_enabled(): void
    {
        $state = $this->createMock(SystemMaintenanceState::class);
        $state->method('isEnabled')->willReturn(true);
        $state->method('state')->willReturn([
            'enabled' => true,
            'updated_at' => now()->toIso8601String(),
            'updated_at_display' => 'Apr 11, 2026 08:00 AM',
            'updated_by_id' => 1,
            'updated_by_name' => 'Superadmin',
        ]);

        $this->instance(SystemMaintenanceState::class, $state);

        $response = $this->get(route('login'));

        $response->assertRedirect(route('maintenance.notice'));
    }

    public function test_admin_login_shows_superadmin_maintenance_view_when_maintenance_is_enabled(): void
    {
        $state = $this->createMock(SystemMaintenanceState::class);
        $state->method('isEnabled')->willReturn(true);
        $state->method('state')->willReturn([
            'enabled' => true,
            'updated_at' => now()->toIso8601String(),
            'updated_at_display' => 'Apr 11, 2026 08:00 AM',
            'updated_by_id' => 1,
            'updated_by_name' => 'Superadmin',
        ]);

        $this->instance(SystemMaintenanceState::class, $state);

        $response = $this->get(route('maintenance.superadmin-login'));

        $response->assertOk();
        $response->assertSee('Temporary Superadmin Access');
        $response->assertSee('Superadmin Login');
        $response->assertDontSee('No account? Create one!');
    }

    public function test_admin_login_redirects_to_regular_login_when_maintenance_is_disabled(): void
    {
        $state = $this->createMock(SystemMaintenanceState::class);
        $state->method('isEnabled')->willReturn(false);

        $this->instance(SystemMaintenanceState::class, $state);

        $response = $this->get(route('maintenance.superadmin-login'));

        $response->assertRedirect(route('login'));
    }

    public function test_login_page_uses_the_regular_view_when_maintenance_is_disabled(): void
    {
        $state = $this->createMock(SystemMaintenanceState::class);
        $state->method('isEnabled')->willReturn(false);

        $this->instance(SystemMaintenanceState::class, $state);

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Reporting, Inspection and Monitoring System (PRISM)');
        $response->assertSee('No account? Create one!');
        $response->assertDontSee('Superadmin Login');
    }

    public function test_maintenance_notice_route_excludes_session_dependent_middleware(): void
    {
        $route = app('router')->getRoutes()->getByName('maintenance.notice');

        $this->assertNotNull($route);
        $this->assertContains(StartSession::class, $route->excludedMiddleware());
        $this->assertContains(ShareErrorsFromSession::class, $route->excludedMiddleware());
        $this->assertContains(VerifyCsrfToken::class, $route->excludedMiddleware());
    }
}
