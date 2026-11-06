<?php

use App\Http\Middleware\AuthenticateMobileAccessToken;
use App\Http\Middleware\EnforceMandatoryPasswordChange;
use App\Http\Middleware\EnsureCrudPermission;
use App\Http\Middleware\EnsureEnabledModule;
use App\Http\Middleware\EnsureSystemMaintenanceAccess;
use App\Http\Middleware\GzipEncodeResponse;
use App\Http\Middleware\RecordActivityLog;
use App\Http\Middleware\RegionalOfficeDilgMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SuperAdminMiddleware;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo('/dashboard');
        $middleware->append(SecurityHeaders::class);
        $middleware->append(GzipEncodeResponse::class);
        $middleware->web(append: [
            EnsureSystemMaintenanceAccess::class,
            EnsureEnabledModule::class,
            EnforceMandatoryPasswordChange::class,
            RecordActivityLog::class,
        ]);

        $middleware->alias([
            'superadmin' => SuperAdminMiddleware::class,
            'regional_dilg' => RegionalOfficeDilgMiddleware::class,
            'crud_permission' => EnsureCrudPermission::class,
            'mobile.auth' => AuthenticateMobileAccessToken::class,
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (ValidationException $exception): void {
            $request = request();

            if (! $request instanceof Request) {
                return;
            }

            $routeName = (string) ($request->route()?->getName() ?? $request->path());
            $routeLabel = Str::headline(str_replace(['.', '-', '_', '/'], ' ', $routeName));
            $username = trim((string) ($request->input('username') ?? ''));

            app(ActivityLogService::class)->log(
                $request->user(),
                ActivityLog::ACTION_VALIDATION_FAILED,
                'Validation failed on '.($routeLabel !== '' ? $routeLabel : 'Form Submission').'.',
                [
                    'request' => $request,
                    'username' => $username !== '' ? $username : null,
                    'properties' => [
                        'route_name' => $request->route()?->getName(),
                        'method' => $request->method(),
                        'path' => '/'.ltrim($request->path(), '/'),
                        'fields' => array_keys($exception->errors()),
                    ],
                ],
            );
        });
    })->create();
