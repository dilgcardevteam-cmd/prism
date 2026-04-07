<?php

use App\Models\RolePermissionSetting;
use App\Models\User;
use App\Support\RolePermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tbusers')) {
            return;
        }

        $baselinePermissions = $this->sortedPermissions(
            RolePermissionRegistry::permissionsForRole(
                User::ROLE_LGU,
                RolePermissionSetting::permissionsForRole(User::ROLE_LGU),
            )
        );
        $allPermissions = $this->sortedPermissions(RolePermissionRegistry::validPermissionKeys());

        User::query()
            ->where('role', User::ROLE_LGU)
            ->whereNotNull('access')
            ->whereRaw('TRIM(COALESCE(access, "")) <> ""')
            ->get(['idno', 'access'])
            ->each(function (User $user) use ($baselinePermissions, $allPermissions): void {
                $access = strtolower(trim((string) $user->access));

                if ($access === User::ACCESS_SCOPE_ALL || !str_starts_with($access, User::ACCESS_PERMISSION_PREFIX)) {
                    return;
                }

                $explicitPermissions = $this->sortedPermissions($this->parsePermissions($access));
                $mergedPermissions = $this->sortedPermissions(array_merge($baselinePermissions, $explicitPermissions));

                if ($mergedPermissions === $baselinePermissions) {
                    $user->forceFill(['access' => null])->save();

                    return;
                }

                if ($mergedPermissions === $allPermissions) {
                    $user->forceFill(['access' => User::ACCESS_SCOPE_ALL])->save();

                    return;
                }

                $user->forceFill([
                    'access' => User::ACCESS_PERMISSION_PREFIX . implode(',', $mergedPermissions),
                ])->save();
            });
    }

    public function down(): void
    {
        // Irreversible on purpose: previous restrictive user overrides cannot
        // be reconstructed safely once the role baseline is merged in.
    }

    private function parsePermissions(string $access): array
    {
        if ($access === User::ACCESS_SCOPE_NONE) {
            return [];
        }

        return RolePermissionRegistry::normalizePermissions(
            array_values(array_filter(array_map(
                'trim',
                explode(',', substr($access, strlen(User::ACCESS_PERMISSION_PREFIX)))
            )))
        );
    }

    private function sortedPermissions(array $permissions): array
    {
        $normalized = RolePermissionRegistry::normalizePermissions($permissions);
        sort($normalized);

        return array_values($normalized);
    }
};
