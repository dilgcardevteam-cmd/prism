<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserRole extends Model
{
    protected $fillable = [
        'role_key',
        'label',
        'base_role',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static ?array $definitionsCache = null;

    protected static function booted(): void
    {
        static::saved(function (): void {
            static::flushRoleCache();
        });

        static::deleted(function (): void {
            static::flushRoleCache();
        });
    }

    public static function tableAvailable(): bool
    {
        try {
            return Schema::hasTable((new static())->getTable());
        } catch (\Throwable) {
            return false;
        }
    }

    public static function flushRoleCache(): void
    {
        static::$definitionsCache = null;
    }

    public static function roleOptions(): array
    {
        return collect(static::definitions())
            ->filter(fn (array $definition): bool => !static::isBuiltInRoleKey($definition['role_key']) && ($definition['is_active'] ?? true) === true)
            ->mapWithKeys(fn (array $definition): array => [
                $definition['role_key'] => $definition['label'],
            ])
            ->all();
    }

    public static function builtInOverrides(): array
    {
        return collect(static::definitions())
            ->filter(fn (array $definition): bool => static::isBuiltInRoleKey($definition['role_key']))
            ->mapWithKeys(fn (array $definition): array => [
                $definition['role_key'] => $definition,
            ])
            ->all();
    }

    public static function isBuiltInRoleKey(string $role): bool
    {
        return array_key_exists(strtolower(trim($role)), User::builtInRoleOptions());
    }

    public static function descriptions(): array
    {
        return collect(static::definitions())
            ->mapWithKeys(fn (array $definition): array => [
                $definition['role_key'] => $definition['description'],
            ])
            ->all();
    }

    public static function baseRoleFor(string $role): ?string
    {
        return static::findDefinition($role)['base_role'] ?? null;
    }

    public static function isCustomRole(string $role): bool
    {
        return static::findDefinition($role) !== null;
    }

    public static function findDefinition(string $role): ?array
    {
        $normalizedRole = strtolower(trim($role));

        if ($normalizedRole === '') {
            return null;
        }

        return collect(static::definitions())
            ->first(fn (array $definition): bool => $definition['role_key'] === $normalizedRole);
    }

    public static function generateUniqueRoleKey(string $label, ?string $ignoreRoleKey = null): string
    {
        $baseKey = 'role_' . Str::snake(Str::lower(trim($label)));
        $baseKey = trim(preg_replace('/_+/', '_', $baseKey) ?? $baseKey, '_');
        $baseKey = $baseKey === '' ? 'role_custom' : $baseKey;

        $normalizedIgnoreRoleKey = strtolower(trim((string) $ignoreRoleKey));
        $existingRoleKeys = array_merge(
            array_keys(User::builtInRoleOptions()),
            array_keys(static::roleOptions()),
        );

        if ($normalizedIgnoreRoleKey !== '') {
            $existingRoleKeys = array_values(array_filter(
                $existingRoleKeys,
                fn (string $roleKey): bool => $roleKey !== $normalizedIgnoreRoleKey,
            ));
        }

        $existingLookup = array_fill_keys($existingRoleKeys, true);
        $candidate = $baseKey;
        $suffix = 2;

        while (isset($existingLookup[$candidate])) {
            $candidate = $baseKey . '_' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    public static function defaultDescriptionFor(?string $baseRole = null): string
    {
        return 'Official custom role. Configure its access from Role Configuration after saving.';
    }

    public static function normalizeDescription(?string $description): string
    {
        $normalizedDescription = trim((string) $description);

        if (
            $normalizedDescription === ''
            || (str_contains($normalizedDescription, 'Inherits the ')
                && str_contains($normalizedDescription, 'hierarchy scope'))
        ) {
            return static::defaultDescriptionFor();
        }

        return $normalizedDescription;
    }

    /**
     * @return array<int, array{role_key: string, label: string, base_role: string, description: string, is_active: bool}>
     */
    public static function definitions(): array
    {
        if (static::$definitionsCache !== null) {
            return static::$definitionsCache;
        }

        if (!static::tableAvailable()) {
            return static::$definitionsCache = [];
        }

        return static::$definitionsCache = static::query()
            ->orderBy('label')
            ->get(['role_key', 'label', 'base_role', 'description', 'is_active'])
            ->map(fn (self $role): array => [
                'role_key' => strtolower(trim((string) $role->role_key)),
                'label' => trim((string) $role->label),
                'base_role' => strtolower(trim((string) $role->base_role)),
                'description' => static::normalizeDescription($role->description),
                'is_active' => (bool) $role->is_active,
            ])
            ->all();
    }
}
