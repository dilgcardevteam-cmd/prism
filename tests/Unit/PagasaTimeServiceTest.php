<?php

namespace Tests\Unit;

use App\Services\PagasaTimeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagasaTimeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function it_caches_a_signed_pagasa_time_offset_for_subsequent_requests(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-03 15:23:00', 'Asia/Manila'));
        Http::fake([
            'https://oras.pagasa.dost.gov.ph/*' => Http::response(
                '<p>Tuesday, 03 November 2026</p><p>03:25:00 PM</p>'
            ),
        ]);

        $firstTimestamp = PagasaTimeService::getCurrentTime();
        Carbon::setTestNow(Carbon::parse('2026-11-03 15:23:20', 'Asia/Manila'));
        $secondTimestamp = PagasaTimeService::getCurrentTime();

        $this->assertSame('2026-11-03 15:25:00', $firstTimestamp->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-03 15:25:20', $secondTimestamp->format('Y-m-d H:i:s'));
        $this->assertSame(1, Http::recorded()->count());
    }

    #[Test]
    public function it_preserves_negative_pagasa_time_offsets(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-03 15:23:00', 'Asia/Manila'));
        Http::fake([
            'https://oras.pagasa.dost.gov.ph/*' => Http::response(
                '<p>Tuesday, 03 November 2026</p><p>03:21:00 PM</p>'
            ),
        ]);

        $timestamp = PagasaTimeService::getCurrentTime();

        $this->assertSame('2026-11-03 15:21:00', $timestamp->format('Y-m-d H:i:s'));
        $this->assertSame(-120, PagasaTimeService::getTimeOffset());
    }
}
