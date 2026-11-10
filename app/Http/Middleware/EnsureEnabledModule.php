<?php

namespace App\Http\Middleware;

use App\Models\ModuleConfiguration;
use App\Support\RolePermissionRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnabledModule
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $routeName = $route?->getName();
        if ($routeName === 'users.impersonate.stop') {
            return $next($request);
        }

        $path = $route?->uri() ?? $request->path();
        $aspect = RolePermissionRegistry::aspectForRoute($routeName, $path);

        if ($aspect === 'data_management_hub') {
            $hasEnabledUploadModule = collect([
                'subaybayan_data_uploads',
                'rlip_lime_data_uploads',
                'project_at_risk_data_uploads',
                'sglgif_data_uploads',
            ])->contains(fn (string $uploadAspect): bool => ModuleConfiguration::isModuleEnabled($uploadAspect));

            if ($hasEnabledUploadModule) {
                return $next($request);
            }
        } elseif ($aspect === null || ModuleConfiguration::isModuleEnabled($aspect)) {
            return $next($request);
        }

        return response()->view('errors.restricted', [
            'message' => 'This module has been temporarily disabled by the system administrator.',
        ], 403);
    }
}
