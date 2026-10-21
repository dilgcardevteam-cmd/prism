<?php

namespace App\Http\Middleware;

/**
 * Backward-compatibility proxy for the modular RecordActivityLog middleware.
 */
class RecordActivityLog extends \App\Modules\ActivityLogs\Middleware\RecordActivityLog
{
}
