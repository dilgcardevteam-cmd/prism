<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GzipEncodeResponse
{
    /**
     * Handle an incoming request and compress the response if supported.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return $response;
        }

        // Compress using gzencode (compression level 6 offers optimal speed/ratio)
        $compressed = gzencode($content, 6);
        if ($compressed === false || strlen($compressed) >= strlen($content)) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding');
        $response->headers->set('Content-Length', (string) strlen($compressed));

        return $response;
    }

    /**
     * Determine whether the request/response should be compressed.
     */
    protected function shouldCompress(Request $request, Response $response): bool
    {
        if (!function_exists('gzencode')) {
            return false;
        }

        // Skip binary file downloads and streamed responses
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        // Skip if output compression is already active at PHP/web server level
        if ((bool) ini_get('zlib.output_compression')) {
            return false;
        }

        // Skip if response is already encoded
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // Skip non-success or empty-content status codes
        $status = $response->getStatusCode();
        if ($status < 200 || $status === 204 || $status === 304) {
            return false;
        }

        // Ensure client explicitly supports gzip
        $acceptEncoding = $request->header('Accept-Encoding', '');
        if (!str_contains($acceptEncoding, 'gzip')) {
            return false;
        }

        // Target compressible MIME types
        $contentType = (string) $response->headers->get('Content-Type', '');
        $compressibleTypes = [
            'text/',
            'application/json',
            'application/javascript',
            'application/xml',
            'application/rss+xml',
            'application/atom+xml',
            'image/svg+xml',
        ];

        foreach ($compressibleTypes as $type) {
            if (str_starts_with($contentType, $type) || str_contains($contentType, $type)) {
                return true;
            }
        }

        return false;
    }
}

