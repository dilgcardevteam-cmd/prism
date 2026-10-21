<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GzipCompressionTest extends TestCase
{
    public function test_response_is_gzipped_when_client_accepts_gzip(): void
    {
        $response = $this->withHeaders([
            'Accept-Encoding' => 'gzip, deflate',
        ])->get('/up');

        $response->assertStatus(200);
        $this->assertEquals('gzip', $response->headers->get('Content-Encoding'));
        $this->assertStringContainsString('Accept-Encoding', (string) $response->headers->get('Vary'));
    }

    public function test_response_is_not_gzipped_when_client_does_not_send_accept_encoding(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
        $this->assertNull($response->headers->get('Content-Encoding'));
    }

    public function test_static_asset_responses_include_a_far_future_cache_policy(): void
    {
        Route::get('/assets/app.js', function () {
            return response('console.log("hello");', 200)->header('Content-Type', 'application/javascript');
        });

        $response = $this->get('/assets/app.js');

        $response->assertOk();
        $this->assertSame('immutable, max-age=31536000, public', $response->headers->get('Cache-Control'));
    }
}

