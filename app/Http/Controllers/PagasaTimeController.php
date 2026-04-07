<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PagasaTimeController extends Controller
{
    // Time source APIs with built-in time headers
    private const TIME_SOURCES = [
        'https://www.google.com',  // Google (most reliable, always returns Date header)
        'https://www.microsoft.com',  // Microsoft fallback
        'https://www.amazon.com',  // Amazon fallback
    ];
    
    // Alternative APIs
    private const TIME_APIS = [
        'https://timeapi.io/api/Time/current/zone?timeZone=Asia/Manila',
        'https://worldtimeapi.org/api/timezone/Asia/Manila',
    ];

    /**
     * Get current time from Google servers (tamper-proof)
     * Google's servers are NTP-synchronized and extremely reliable
     * Uses HTTP Date header which is always present and accurate
     *
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        try {
            // First try: Get time from Google's HTTP Date header (most reliable)
            $ntpTime = $this->fetchFromGoogleHeaders();
            
            if ($ntpTime) {
                \Log::info('Time from Google headers: ' . $ntpTime->toIso8601String());
                
                return response()->json([
                    'success' => true,
                    'ntp_time' => $ntpTime->toIso8601String(),
                    'timezone' => 'Asia/Manila',
                    'timestamp' => $ntpTime->timestamp,
                    'source' => 'google'
                ]);
            }
            
            // Second try: Get time from Time APIs
            $apiTime = $this->fetchFromTimeAPIs();
            
            if ($apiTime) {
                \Log::info('Time from API: ' . $apiTime->toIso8601String());
                
                return response()->json([
                    'success' => true,
                    'ntp_time' => $apiTime->toIso8601String(),
                    'timezone' => 'Asia/Manila',
                    'timestamp' => $apiTime->timestamp,
                    'source' => 'time_api'
                ]);
            }
            
            // Last resort: use system time (still better than nothing)
            $systemTime = Carbon::now('Asia/Manila');
            \Log::warning('Using system time as fallback (all sources unavailable): ' . $systemTime->toIso8601String());
            
            return response()->json([
                'success' => true,
                'ntp_time' => $systemTime->toIso8601String(),
                'timezone' => 'Asia/Manila',
                'timestamp' => $systemTime->timestamp,
                'source' => 'system_fallback',
                'warning' => 'Using local system time'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Time controller error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve time',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch time from Google's HTTP headers
     * Every HTTP response from Google includes a Date header with accurate server time
     * This is extremely reliable and requires no parsing
     *
     * @return Carbon|null
     */
    private function fetchFromGoogleHeaders(): ?Carbon
    {
        foreach (self::TIME_SOURCES as $url) {
            try {
                $response = Http::timeout(5)
                    ->connectTimeout(3)
                    ->head($url);

                if ($response->successful() && $response->hasHeader('Date')) {
                    $dateHeader = $response->header('Date');
                    
                    try {
                        // Parse RFC 7231 date format from HTTP header
                        // Example: "Mon, 26 Jan 2026 14:45:13 GMT"
                        $time = Carbon::createFromFormat('D, d M Y H:i:s T', $dateHeader)
                            ->setTimezone('Asia/Manila');
                        
                        \Log::info('Time fetched from ' . $url . ' header: ' . $time->toIso8601String());
                        return $time;
                    } catch (\Exception $e) {
                        \Log::debug('Failed to parse Date header from ' . $url . ': ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                \Log::debug('HEAD request failed to ' . $url . ': ' . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    /**
     * Fetch time from Time APIs as fallback
     *
     * @return Carbon|null
     */
    private function fetchFromTimeAPIs(): ?Carbon
    {
        foreach (self::TIME_APIS as $api) {
            try {
                $response = Http::timeout(5)
                    ->connectTimeout(3)
                    ->get($api);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // TimeAPI.io format
                    if (isset($data['dateTime'])) {
                        try {
                            $time = Carbon::parse($data['dateTime'])->setTimezone('Asia/Manila');
                            \Log::info('Time fetched from ' . $api . ': ' . $time->toIso8601String());
                            return $time;
                        } catch (\Exception $e) {
                            \Log::debug('Failed to parse TimeAPI response: ' . $e->getMessage());
                        }
                    }
                    
                    // World Time API format
                    if (isset($data['unixtime'])) {
                        try {
                            $time = Carbon::createFromTimestamp($data['unixtime'], 'Asia/Manila');
                            \Log::info('Time fetched from ' . $api . ': ' . $time->toIso8601String());
                            return $time;
                        } catch (\Exception $e) {
                            \Log::debug('Failed to parse WorldTime response: ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::debug('API call failed (' . $api . '): ' . $e->getMessage());
                continue;
            }
        }

        return null;
    }
}




