<?php

namespace App\Modules\SystemSettings\Models;

use App\Support\RolePermissionRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class RolePermissionSetting extends Model
{
    protected $table = 'role_permission_settings';

    protected $fillable = [
        'role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    protected static array $permissionsCache = [];
    protected static ?bool $tableAvailabilityCache = null;

    public static function tableAvailable(): bool
    {
        if (static::$tableAvailabilityCache !== null) {
            return static::$tableAvailabilityCache;
        }

        try {
            return static::$tableAvailabilityCache = Schema::hasTable((new static())->getTable());
        } catch (\Throwable) {
            return false;
        }
    }

    public static function permissionsForRole(string $role): ?array
    {
        $normalizedRole = strtolower(trim($role));

        if (array_key_exists($normalizedRole, static::$permissionsCache)) {
            return static::$permissionsCache[$normalizedRole];
        }

        if (!static::tableAvailable()) {
            return static::$permissionsCache[$normalizedRole] = null;
        }

        $setting = static::query()
            ->where('role', $normalizedRole)
            ->first();

        return static::$permissionsCache[$normalizedRole] = $setting
            ? RolePermissionRegistry::normalizePermissions($setting->permissions ?? [])
            : null;
    }

    public static function flushPermissionsCache(): void
    {
        static::$permissionsCache = [];
        static::$tableAvailabilityCache = null;
    }
}
