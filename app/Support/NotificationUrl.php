<?php

namespace App\Support;

use Illuminate\Support\Str;

class NotificationUrl
{
    public static function normalizeForStorage(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        return static::extractInternalPath($url) ?? $url;
    }

    public static function normalizeForRedirect(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        return static::extractInternalPath($url) ?? $url;
    }

    private static function extractInternalPath(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $host = Str::lower(trim((string) ($parts['host'] ?? '')));
        $scheme = trim((string) ($parts['scheme'] ?? ''));

        if ($host !== '') {
            if (!static::isKnownApplicationHost($host)) {
                return null;
            }

            return static::rebuildPath($parts);
        }

        if ($scheme !== '') {
            return null;
        }

        return static::rebuildPath($parts);
    }

    private static function isKnownApplicationHost(string $host): bool
    {
        $requestHost = app()->bound('request')
            ? Str::lower(trim((string) request()->getHost()))
            : '';
        $configuredHost = Str::lower(trim((string) parse_url((string) config('app.url'), PHP_URL_HOST)));

        return in_array($host, array_filter([
            $requestHost,
            $configuredHost,
            'localhost',
            '127.0.0.1',
        ]), true);
    }

    private static function rebuildPath(array $parts): string
    {
        $path = trim((string) ($parts['path'] ?? ''));
        $path = $path === '' ? '/' : '/' . ltrim($path, '/');

        if (($parts['query'] ?? '') !== '') {
            $path .= '?' . $parts['query'];
        }

        if (($parts['fragment'] ?? '') !== '') {
            $path .= '#' . $parts['fragment'];
        }

        return $path;
    }
}
