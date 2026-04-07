<?php

if (!function_exists('pagasa_time')) {
    /**
     * Get current time from PAGASA server
     *
     * @return \Carbon\Carbon
     */
    function pagasa_time()
    {
        return \App\Services\PagasaTimeService::getCurrentTime();
    }
}

if (!function_exists('pagasa_adjusted_time')) {
    /**
     * Get system time adjusted by PAGASA offset
     *
     * @return \Carbon\Carbon
     */
    function pagasa_adjusted_time()
    {
        return \App\Services\PagasaTimeService::getAdjustedTime();
    }
}

if (!function_exists('pagasa_time_offset')) {
    /**
     * Get time offset in seconds between PAGASA and system
     *
     * @return int
     */
    function pagasa_time_offset()
    {
        return \App\Services\PagasaTimeService::getTimeOffset();
    }
}
