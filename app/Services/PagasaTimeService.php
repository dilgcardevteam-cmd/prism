<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PagasaTimeService
{
    private const PAGASA_URL = 'https://oras.pagasa.dost.gov.ph/';
    private const CACHE_KEY = 'pagasa_time_offset';
    private const CACHE_DURATION = 3600; // 1 hour

    /**
     * Get current time from PAGASA server
     * Falls back to system time if PAGASA is unavailable
     *
     * @return \Carbon\Carbon
     */
    public static function getCurrentTime(): Carbon
    {
        try {
            // Try to fetch from PAGASA
            $response = Http::timeout(5)
                ->retry(2, 100)
                ->get(self::PAGASA_URL);

            if ($response->successful()) {
                $time = self::parsePageTime($response->body());
                if ($time) {
                    return $time;
                }
            }
        } catch (\Exception $e) {
            Log::warning('PAGASA time fetch failed: ' . $e->getMessage());
        }

        // Fallback to system time
        return Carbon::now('Asia/Manila');
    }

    /**
     * Parse time from PAGASA HTML response
     * PAGASA now uses JavaScript to dynamically calculate time, so we extract the time string directly
     *
     * @param string $html
     * @return \Carbon\Carbon|null
     */
    private static function parsePageTime(string $html): ?Carbon
    {
        try {
            // Look for time pattern: "HH:MM:SS AM/PM" anywhere in the HTML
            // Look for date pattern: "DayName, DD MonthName YYYY"
            
            if (preg_match('/(\d{1,2}):(\d{2}):(\d{2})\s*([AP]M)/i', $html, $timeMatches)) {
                $timeStr = $timeMatches[0]; // e.g., "02:20:02 PM"
                
                // Look for the full date with day name
                if (preg_match('/(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|Sunday),\s+(\d{1,2})\s+(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})/i', $html, $dateMatches)) {
                    $dateStr = $dateMatches[0]; // e.g., "Monday, 26 January 2026"
                    
                    try {
                        // Combine and parse
                        $fullDateTime = $dateStr . ' ' . $timeStr;
                        return Carbon::createFromFormat('l, d F Y h:i:s A', $fullDateTime, 'Asia/Manila');
                    } catch (\Exception $e) {
                        Log::warning('Failed to parse PAGASA datetime: ' . $fullDateTime . ' - ' . $e->getMessage());
                        return null;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error parsing PAGASA page: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get time offset between PAGASA and system time (in seconds)
     *
     * @return int
     */
    public static function getTimeOffset(): int
    {
        return \Illuminate\Support\Facades\Cache::remember(
            self::CACHE_KEY,
            self::CACHE_DURATION,
            function () {
                $pagasaTime = self::getCurrentTime();
                $systemTime = Carbon::now('Asia/Manila');
                return $pagasaTime->diffInSeconds($systemTime);
            }
        );
    }

    /**
     * Adjust system time based on PAGASA
     *
     * @return \Carbon\Carbon
     */
    public static function getAdjustedTime(): Carbon
    {
        $offset = self::getTimeOffset();
        return Carbon::now('Asia/Manila')->addSeconds($offset);
    }

    /**
     * Clear cached time offset
     *
     * @return void
     */
    public static function clearCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget(self::CACHE_KEY);
    }
}
