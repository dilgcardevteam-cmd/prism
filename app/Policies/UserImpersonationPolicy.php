<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\SystemSettings\Models\UserRole;

class UserImpersonationPolicy
{
    public function start(User $administrator, User $target): bool
    {
        return $administrator->isActive()
            && $administrator->isSuperAdmin()
            && !(bool) $administrator->must_change_password
            && $administrator->isNot($target)
            && $this->canBeImpersonated($target);
    }

    public function canBeImpersonated(User $target): bool
    {
        if (
            !$target->isActive()
            || $target->isSuperAdmin()
            || (bool) $target->must_change_password
            || in_array('*', $target->grantedCrudPermissions(), true)
        ) {
            return false;
        }

        $role = $target->normalizedRole();
        $definition = UserRole::findDefinition($role);
        $baseRole = $definition['base_role'] ?? $role;

        if (
            $baseRole !== User::ROLE_LGU
            || ($definition !== null && !($definition['is_active'] ?? false))
        ) {
            return false;
        }

        $builtInOverride = UserRole::builtInOverrides()[$role] ?? null;

        return $builtInOverride === null || (bool) ($builtInOverride['is_active'] ?? true);
    }
}
