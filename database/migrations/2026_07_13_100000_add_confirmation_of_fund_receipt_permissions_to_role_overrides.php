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

        $permissionsByRole = [
            User::ROLE_REGIONAL => [
                'confirmation_of_fund_receipt.view',
            ],
            User::ROLE_PROVINCIAL => [
                'confirmation_of_fund_receipt.view',
                'confirmation_of_fund_receipt.add',
                'confirmation_of_fund_receipt.update',
            ],
        ];

        foreach ($permissionsByRole as $role => $permissionsToAdd) {
            $setting = DB::table('role_permission_settings')
                ->where('role', $role)
                ->first();

            if (!$setting) {
                continue;
            }

            $existingPermissions = json_decode((string) ($setting->permissions ?? '[]'), true);
            $existingPermissions = is_array($existingPermissions) ? $existingPermissions : [];

            $normalizedPermissions = collect(array_merge($existingPermissions, $permissionsToAdd))
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
    }

    public function down(): void
    {
        if (!Schema::hasTable('role_permission_settings')) {
            return;
        }

        $permissionsByRole = [
            User::ROLE_REGIONAL => [
                'confirmation_of_fund_receipt.view',
            ],
            User::ROLE_PROVINCIAL => [
                'confirmation_of_fund_receipt.view',
                'confirmation_of_fund_receipt.add',
                'confirmation_of_fund_receipt.update',
            ],
        ];

        foreach ($permissionsByRole as $role => $permissionsToRemove) {
            $setting = DB::table('role_permission_settings')
                ->where('role', $role)
                ->first();

            if (!$setting) {
                continue;
            }

            $existingPermissions = json_decode((string) ($setting->permissions ?? '[]'), true);
            $existingPermissions = is_array($existingPermissions) ? $existingPermissions : [];

            $normalizedPermissions = collect($existingPermissions)
                ->map(fn ($permission) => strtolower(trim((string) $permission)))
                ->reject(fn ($permission) => in_array($permission, $permissionsToRemove, true))
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
    }
};
