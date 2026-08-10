<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleConfiguration extends Model
{
    protected $table = 'module_configurations';

    protected $fillable = [
        'module_key',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    private static ?array $cachedStatuses = null;

    /**
     * Determine if a specific module/aspect is globally enabled.
     */
    public static function isModuleEnabled(string $aspect): bool
    {
        if (self::$cachedStatuses === null) {
            self::$cachedStatuses = self::pluck('is_enabled', 'module_key')->toArray();
        }

        return self::$cachedStatuses[$aspect] ?? true;
    }

    /**
     * Flush the local memory cache of module statuses.
     */
    public static function flushCache(): void
    {
        self::$cachedStatuses = null;
    }
}
