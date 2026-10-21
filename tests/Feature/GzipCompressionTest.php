<?php

namespace Tests\Feature;

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
}

