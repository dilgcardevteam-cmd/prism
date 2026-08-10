<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureCrudPermission;
use App\Models\ModuleConfiguration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class EnsureCrudPermissionTest extends \Tests\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('module_configurations', function ($table) {
            $table->id();
            $table->string('module_key')->unique();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('role_permission_settings', function ($table) {
            $table->id();
            $table->string('role')->unique();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function it_denies_access_when_module_is_disabled(): void
    {
        // 1. Create a disabled module configuration
        ModuleConfiguration::create([
            'module_key' => 'locally_funded_projects',
            'is_enabled' => false,
        ]);
        ModuleConfiguration::flushCache();

        // 2. Instantiate middleware
        $middleware = new EnsureCrudPermission();
        $request = Request::create('/locally-funded-projects', 'GET');

        // 3. Invoke handle and expect 403 response
        $response = $middleware->handle($request, function () {
            return response('Access granted');
        }, 'locally_funded_projects', 'view');

        $this->assertEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_access_when_module_is_enabled(): void
    {
        // 1. Create an enabled module configuration
        ModuleConfiguration::create([
            'module_key' => 'locally_funded_projects',
            'is_enabled' => true,
        ]);
        ModuleConfiguration::flushCache();

        // 2. Mock a user with CRUD permission
        Schema::create('tbusers', function ($table) {
            $table->id('idno');
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->string('agency')->nullable();
            $table->string('province')->nullable();
            $table->string('office')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        $user = User::query()->create([
            'idno' => 1,
            'fname' => 'Super',
            'lname' => 'Admin',
            'username' => 'superadmin',
            'password' => 'secret',
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);

        // 3. Instantiate middleware
        $middleware = new EnsureCrudPermission();
        $request = Request::create('/locally-funded-projects', 'GET');
        $request->setUserResolver(fn() => $user);

        // 4. Invoke handle and expect next closure execution
        $response = $middleware->handle($request, function () {
            return response('Access granted');
        }, 'locally_funded_projects', 'view');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Access granted', $response->getContent());
    }
}
