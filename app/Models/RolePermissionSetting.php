<?php

namespace App\Models;

use App\Support\RolePermissionRegistry;
use Illuminate\Database\Eloquent\Model;

class RolePermissionSetting extends Model
{
    protected $fillable = [
        'role',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    protected static array $permissionsCache = [];

    public static function permissionsForRole(string $role): ?array
    {
        $normalizedRole = strtolower(trim($role));

        if (array_key_exists($normalizedRole, static::$permissionsCache)) {
            return static::$permissionsCache[$normalizedRole];
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
    }
}
