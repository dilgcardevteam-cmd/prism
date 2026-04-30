<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogService
{
    /**
     * Explicitly handled routes get custom audit messages and should not be
     * logged again by the generic request middleware.
     */
    private const EXPLICIT_ROUTE_NAMES = [
        'login',
        'logout',
        'register',
        'forgot-password.send-otp',
        'forgot-password.verify-otp',
        'forgot-password.reset-submit',
        'password.update',
        'users.store',
        'users.update',
        'users.destroy',
        'users.block',
        'utilities.role-configuration.role-definitions.store',
        'utilities.role-configuration.role-definitions.update',
        'utilities.role-configuration.role-definitions.destroy',
        'utilities.role-configuration.roles.update',
        'utilities.role-configuration.roles.reset',
        'utilities.activity-logs.export',
        'utilities.system-maintenance.toggle',
        'reports.quarterly.dilg-mc-2018-19.approve',
        'reports.quarterly.dilg-mc-2018-19.delete-document',
    ];

    private ?bool $activityLogTableExists = null;

    /**
     * Persist a new immutable activity log entry.
     */
    public function log(?User $user, string $action, string $description, array $context = []): void
    {
        if (!$this->tableExists()) {
            return;
        }

        try {
            $request = $context['request'] ?? request();
            $userAgent = $context['user_agent'] ?? ($request instanceof Request ? $request->userAgent() : null);
            $createdAt = $context['created_at'] ?? now();
            $createdAt = $createdAt instanceof Carbon ? $createdAt : Carbon::parse((string) $createdAt);
            $timezone = trim((string) ($context['timezone'] ?? config('app.timezone', 'UTC')));

            ActivityLog::query()->create([
                'user_id' => $context['user_id'] ?? $user?->getKey(),
                'username' => $context['username'] ?? $user?->username,
                'action' => Str::upper(trim($action)),
                'description' => Str::limit(trim($description), 65535, ''),
                'timezone' => $timezone !== '' ? $timezone : 'UTC',
                'ip_address' => $context['ip_address'] ?? $this->resolveIpAddress($request),
                'user_agent' => $userAgent,
                'device' => $context['device'] ?? $this->summarizeDevice($userAgent),
                'properties' => $this->sanitizeContextProperties($context['properties'] ?? []),
                'created_at' => $createdAt->copy()->setTimezone(config('app.timezone')),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to persist activity log entry.', [
                'action' => $action,
                'description' => Str::limit($description, 255),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Generic request logger for authenticated read/create/update/delete access.
     */
    public function logRequest(Request $request, Response $response, ?User $actingUser = null): void
    {
        if ($this->shouldSkipRequestLogging($request, $response, $actingUser)) {
            return;
        }

        $user = $request->user() ?? $actingUser;
        if (!$user instanceof User) {
            return;
        }

        $uploadedFiles = $this->extractUploadedFiles($request->allFiles());
        $action = $this->inferRequestAction($request, $uploadedFiles);
        $route = $request->route();
        $routeName = $route?->getName();
        $routeLabel = $this->humanizeRouteName($routeName ?: $request->path());
        $parameterSummary = $this->formatRouteParameters($route?->parametersWithoutNulls() ?? []);
        $uploadSummary = $this->formatUploadedFileSummary($uploadedFiles);

        $description = match ($action) {
            ActivityLog::ACTION_EXPORT => 'Exported data from ' . $routeLabel . '.',
            ActivityLog::ACTION_CREATE => 'Created a resource via ' . $routeLabel . ($parameterSummary !== '' ? ' (' . $parameterSummary . ')' : '') . '.',
            ActivityLog::ACTION_UPDATE => 'Updated a resource via ' . $routeLabel . ($parameterSummary !== '' ? ' (' . $parameterSummary . ')' : '') . '.',
            ActivityLog::ACTION_DELETE => 'Deleted a resource via ' . $routeLabel . ($parameterSummary !== '' ? ' (' . $parameterSummary . ')' : '') . '.',
            ActivityLog::ACTION_UPLOAD => 'Uploaded file'
                . (count($uploadedFiles) === 1 ? '' : 's')
                . ' via ' . $routeLabel
                . ($uploadSummary !== '' ? ' (' . $uploadSummary . ')' : '')
                . '.',
            default => 'Viewed ' . $routeLabel . ($parameterSummary !== '' ? ' (' . $parameterSummary . ')' : '') . '.',
        };

        $this->log($user, $action, $description, [
            'request' => $request,
            'properties' => [
                'route_name' => $routeName,
                'method' => $request->method(),
                'path' => '/' . ltrim($request->path(), '/'),
                'response_status' => $response->getStatusCode(),
                'route_parameters' => $this->sanitizeContextProperties($route?->parametersWithoutNulls() ?? []),
                'query' => $this->sanitizeContextProperties($request->query()),
                'uploaded_files' => $uploadedFiles,
            ],
        ]);
    }

    public function shouldSkipRequestLogging(Request $request, Response $response, ?User $actingUser = null): bool
    {
        if (!$this->tableExists()) {
            return true;
        }

        if ($response->getStatusCode() >= 400) {
            return true;
        }

        $route = $request->route();
        if ($route === null) {
            return true;
        }

        $routeName = (string) $route->getName();
        if ($routeName !== '' && in_array($routeName, self::EXPLICIT_ROUTE_NAMES, true)) {
            return true;
        }

        $method = Str::upper($request->method());
        if (in_array($method, ['GET', 'HEAD'], true) && ($request->expectsJson() || $request->ajax() || $request->is('api/*'))) {
            return true;
        }

        return !$request->user() && !$actingUser;
    }

    public function summarizeDevice(?string $userAgent): ?string
    {
        $userAgent = trim((string) $userAgent);
        if ($userAgent === '') {
            return null;
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') && !str_contains($userAgent, 'Chrome/') => 'Safari',
            str_contains($userAgent, 'MSIE') || str_contains($userAgent, 'Trident/') => 'Internet Explorer',
            default => 'Unknown Browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') || str_contains($userAgent, 'iPod') => 'iOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => 'Unknown Platform',
        };

        $deviceType = match (true) {
            str_contains($userAgent, 'iPad') || str_contains($userAgent, 'Tablet') => 'Tablet',
            str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') || str_contains($userAgent, 'iPhone') => 'Mobile',
            default => 'Desktop',
        };

        return $deviceType . ' · ' . $browser . ' · ' . $platform;
    }

    private function inferRequestAction(Request $request, array $uploadedFiles = []): string
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        $path = '/' . ltrim($request->path(), '/');

        if (Str::contains(Str::lower($routeName . ' ' . $path), 'export')) {
            return ActivityLog::ACTION_EXPORT;
        }

        if ($uploadedFiles !== []) {
            return ActivityLog::ACTION_UPLOAD;
        }

        return match (Str::upper($request->method())) {
            'POST' => ActivityLog::ACTION_CREATE,
            'PUT', 'PATCH' => ActivityLog::ACTION_UPDATE,
            'DELETE' => ActivityLog::ACTION_DELETE,
            default => ActivityLog::ACTION_READ,
        };
    }

    private function humanizeRouteName(string $routeName): string
    {
        $normalized = trim($routeName);
        if ($normalized === '') {
            return 'System Page';
        }

        return Str::headline(str_replace(['.', '-', '_', '/'], ' ', $normalized));
    }

    private function formatRouteParameters(array $parameters): string
    {
        $parts = [];

        foreach ($parameters as $key => $value) {
            if (is_object($value) && method_exists($value, 'getKey')) {
                $value = $value->getKey();
            }

            if (is_array($value) || $value === null) {
                continue;
            }

            $parts[] = Str::headline((string) $key) . ': ' . $value;
        }

        return implode(', ', $parts);
    }

    private function extractUploadedFiles(array $files): array
    {
        $summaries = [];

        foreach ($files as $field => $file) {
            if (is_array($file)) {
                foreach ($this->extractUploadedFiles($file) as $nestedFile) {
                    $summaries[] = $nestedFile;
                }

                continue;
            }

            if (!$file instanceof UploadedFile) {
                continue;
            }

            $summaries[] = [
                'field' => (string) $field,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getClientMimeType(),
            ];
        }

        return $summaries;
    }

    private function formatUploadedFileSummary(array $uploadedFiles): string
    {
        if ($uploadedFiles === []) {
            return '';
        }

        $parts = [];

        foreach (array_slice($uploadedFiles, 0, 3) as $uploadedFile) {
            $parts[] = $uploadedFile['name'] ?? 'file';
        }

        if (count($uploadedFiles) > 3) {
            $parts[] = '+' . (count($uploadedFiles) - 3) . ' more';
        }

        return implode(', ', $parts);
    }

    private function resolveIpAddress(?Request $request): ?string
    {
        if (!$request instanceof Request) {
            return null;
        }

        $ipAddress = trim((string) $request->ip());
        if ($ipAddress === '') {
            return null;
        }

        return filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : null;
    }

    private function sanitizeContextProperties(array $properties): array
    {
        $sanitized = [];

        foreach ($properties as $key => $value) {
            $normalizedKey = Str::lower((string) $key);

            if ($this->isSensitiveKey($normalizedKey)) {
                $sanitized[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContextProperties($value);
                continue;
            }

            if (is_object($value) && method_exists($value, 'toArray')) {
                $sanitized[$key] = $this->sanitizeContextProperties($value->toArray());
                continue;
            }

            if (is_object($value) && method_exists($value, 'getKey')) {
                $sanitized[$key] = $value->getKey();
                continue;
            }

            $sanitized[$key] = is_scalar($value) || $value === null
                ? $value
                : (string) json_encode($value);
        }

        return $sanitized;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (['password', 'token', 'secret', 'otp', 'remember'] as $needle) {
            if (str_contains($key, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function tableExists(): bool
    {
        if ($this->activityLogTableExists !== null) {
            return $this->activityLogTableExists;
        }

        try {
            $this->activityLogTableExists = Schema::hasTable('activity_logs');
        } catch (\Throwable) {
            $this->activityLogTableExists = false;
        }

        return $this->activityLogTableExists;
    }
}
