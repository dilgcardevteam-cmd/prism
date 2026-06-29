<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('role_permission_settings')) {
            return;
        }

        $setting = DB::table('role_permission_settings')
            ->where('role', User::ROLE_REGIONAL)
            ->first();

        if (!$setting) {
            return;
        }

        $existingPermissions = json_decode((string) ($setting->permissions ?? '[]'), true);
        $existingPermissions = is_array($existingPermissions) ? $existingPermissions : [];

        $normalizedPermissions = collect(array_merge($existingPermissions, [
            'ticketing_system.view',
            'ticketing_system.add',
            'ticketing_system.update',
        ]))
            ->map(fn ($permission) => strtolower(trim((string) $permission)))
            ->filter(fn ($permission) => $permission !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        DB::table('role_permission_settings')
            ->where('id', $setting->id)
            ->update([
                'permissions' => json_encode($normalizedPermissions),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('role_permission_settings')) {
            return;
        }

        $setting = DB::table('role_permission_settings')
            ->where('role', User::ROLE_REGIONAL)
            ->first();

        if (!$setting) {
            return;
        }

        $existingPermissions = json_decode((string) ($setting->permissions ?? '[]'), true);
        $existingPermissions = is_array($existingPermissions) ? $existingPermissions : [];

        $normalizedPermissions = collect($existingPermissions)
            ->map(fn ($permission) => strtolower(trim((string) $permission)))
            ->reject(fn ($permission) => $permission === 'ticketing_system.add')
            ->filter(fn ($permission) => $permission !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        DB::table('role_permission_settings')
            ->where('id', $setting->id)
            ->update([
                'permissions' => json_encode($normalizedPermissions),
                'updated_at' => now(),
            ]);
    }
};
