<?php

namespace Tests\Feature;

use App\Http\Controllers\LocallyFundedProjectController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
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

    public function test_missing_locally_funded_project_redirects_to_the_project_list(): void
    {
        $route = Route::getRoutes()->getByName('locally-funded-project.show');
        $missingHandler = $route?->getMissing();

        $this->assertNotNull($missingHandler);

        $response = $missingHandler();

        $this->assertTrue($response->isRedirect(route('projects.locally-funded')));
    }
}
