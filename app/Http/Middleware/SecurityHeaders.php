<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add baseline security headers to every web response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'self'; object-src 'none'; base-uri 'self';");

        if ($this->shouldCacheStaticAsset($request, $response)) {
            $response->headers->set('Cache-Control', 'immutable, max-age=31536000, public');
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    protected function shouldCacheStaticAsset(Request $request, Response $response): bool
    {
        if (!$response->isSuccessful()) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        $path = strtolower((string) $request->path());

        if ($path !== '' && preg_match('/\.(?:css|js|mjs|map|svg|png|jpe?g|gif|webp|avif|ico|woff2?|ttf|eot|xml)$/', $path) === 1) {
            return true;
        }

        foreach (['text/css', 'application/javascript', 'application/x-javascript', 'text/javascript', 'image/', 'font/', 'application/font', 'application/vnd.ms-fontobject', 'image/svg+xml'] as $type) {
            if ($type === 'image/' && str_starts_with($contentType, 'image/')) {
                return true;
            }

            if (str_starts_with($contentType, $type) || str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }
}

