<?php

namespace Tests\Feature;

use App\Http\Controllers\LocallyFundedProjectController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LocallyFundedProjectAccessTest extends TestCase
{
    public function test_provincial_user_can_access_a_project_when_province_labels_normalize_to_the_same_value(): void
    {
        $user = new User([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Baguio City',
            'office' => '',
            'status' => 'active',
        ]);

        $controller = new LocallyFundedProjectController();
        $method = new \ReflectionMethod(LocallyFundedProjectController::class, 'userCanAccessLocation');
        $method->setAccessible(true);

        $canAccess = $method->invoke(
            $controller,
            $user,
            'City of Baguio',
            'Baguio City',
            'CAR',
            null
        );

        $this->assertTrue($canAccess);
    }

    public function test_regional_user_can_access_a_locally_funded_project_detail(): void
    {
        $user = new User([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $controller = new LocallyFundedProjectController();
        $method = new \ReflectionMethod(LocallyFundedProjectController::class, 'userCanAccessLocation');
        $method->setAccessible(true);

        $canAccess = $method->invoke(
            $controller,
            $user,
            'Abra',
            'Bangued',
            'CAR',
            null
        );

        $this->assertTrue($canAccess);
    }

    public function test_provincial_user_cannot_access_a_project_from_another_province(): void
    {
        $user = new User([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Benguet',
            'office' => '',
            'status' => 'active',
        ]);

        $controller = new LocallyFundedProjectController();
        $method = new \ReflectionMethod(LocallyFundedProjectController::class, 'userCanAccessLocation');
        $method->setAccessible(true);

        $canAccess = $method->invoke(
            $controller,
            $user,
            'Abra',
            'Bangued',
            'CAR',
            null
        );

        $this->assertFalse($canAccess);
    }

    public function test_provincial_user_cannot_submit_regional_only_physical_updates(): void
    {
        $user = new User([
            'idno' => 17,
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Abra',
            'office' => '',
            'status' => 'active',
        ]);

        Auth::shouldReceive('user')->andReturn($user);

        $controller = new LocallyFundedProjectController();
        $request = Request::create('/projects/locally-funded/4700', 'PUT', [
            'section' => 'physical',
            'status_project_ro' => [now()->month => 'Ongoing'],
        ]);

        try {
            $controller->update($request, new \App\Models\LocallyFundedProject([
                'province' => 'Abra',
                'city_municipality' => 'Bangued',
                'region' => 'CAR',
            ]));

            $this->fail('Expected provincial RO-only physical update submission to be forbidden.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_missing_locally_funded_project_redirects_to_the_project_list(): void
    {
        $route = Route::getRoutes()->getByName('locally-funded-project.show');
        $missingHandler = $route?->getMissing();

        $this->assertNotNull($missingHandler);

        $response = $missingHandler();

        $this->assertTrue($response->isRedirect(route('projects.locally-funded')));
    }

    public function test_locally_funded_project_controller_registers_view_middleware(): void
    {
        $controller = new LocallyFundedProjectController();
        $middlewareList = $controller->getMiddleware();

        $foundViewMiddleware = false;
        foreach ($middlewareList as $item) {
            if ($item['middleware'] === 'crud_permission:locally_funded_projects,view') {
                $foundViewMiddleware = true;
                $options = $item['options'] ?? [];
                $this->assertArrayHasKey('only', $options);
                $this->assertContains('index', $options['only']);
                $this->assertContains('show', $options['only']);
                $this->assertContains('showSubaybayan', $options['only']);
                $this->assertContains('viewPcrMov', $options['only']);
                $this->assertContains('ensureFromSubay', $options['only']);
            }
        }

        $this->assertTrue($foundViewMiddleware, 'LocallyFundedProjectController should register the view permission middleware.');
    }
}
