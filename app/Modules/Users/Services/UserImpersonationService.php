<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Policies\UserImpersonationPolicy;
use App\Modules\Users\Models\UserImpersonationAuditLog;
use Illuminate\Auth\SessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserImpersonationService
{
    public const AUDIT_SESSION_KEY = 'impersonation_audit_id';
    public const REFERENCE_SESSION_KEY = 'impersonation_reference';
    public const ADMIN_ID_SESSION_KEY = 'impersonator_user_id';
    public const ADMIN_NAME_SESSION_KEY = 'impersonator_name';
    public const ADMIN_PASSWORD_VERSION_SESSION_KEY = 'impersonator_password_version';
    public const ADMIN_PASSWORD_CONFIRMED_SESSION_KEY = 'impersonator_password_confirmed_at';
    public const TARGET_ID_SESSION_KEY = 'impersonated_user_id';

    public function __construct(
        private readonly UserImpersonationPolicy $policy,
    ) {
    }

    public function start(Request $request, User $administrator, User $target, ?string $reason = null): void
    {
        abort_if($request->session()->has(self::AUDIT_SESSION_KEY), 403, 'Nested impersonation is not allowed.');
        abort_if(
            (int) $request->session()->get('auth_password_version', 0) < (int) $administrator->password_security_version,
            403,
            'Sign in again before starting impersonation.',
        );

        $reference = Str::random(64);
        $startedAt = now();
        $lifetimeMinutes = max(1, (int) config('session.lifetime', 120));

        $audit = UserImpersonationAuditLog::query()->create([
            'admin_user_id' => $administrator->getKey(),
            'admin_name' => $administrator->fullName(),
            'target_user_id' => $target->getKey(),
            'target_username' => $target->username,
            'started_at' => $startedAt,
            'expires_at' => $startedAt->copy()->addMinutes($lifetimeMinutes),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_reference_hash' => hash('sha256', $reference),
            'reason' => $reason,
            'status' => UserImpersonationAuditLog::STATUS_ACTIVE,
        ]);

        $request->session()->put([
            self::AUDIT_SESSION_KEY => $audit->getKey(),
            self::REFERENCE_SESSION_KEY => $reference,
            self::ADMIN_ID_SESSION_KEY => $administrator->getKey(),
            self::ADMIN_NAME_SESSION_KEY => $administrator->fullName(),
            self::ADMIN_PASSWORD_VERSION_SESSION_KEY => (int) $administrator->password_security_version,
            self::TARGET_ID_SESSION_KEY => $target->getKey(),
            'auth_password_version' => (int) $target->password_security_version,
        ]);
        $request->session()->put(
            self::ADMIN_PASSWORD_CONFIRMED_SESSION_KEY,
            $request->session()->pull('auth.password_confirmed_at'),
        );
        $request->session()->forget([
            'auth.password_confirmed_at',
            'login_otp_challenge',
            'mandatory_password_user_id',
            'mandatory_password_remember',
            'mandatory_password_otp',
            'otp_notice',
            'login_otp_sent',
            'url.intended',
            '_old_input',
            'errors',
        ]);

        $guard = $this->webGuard();
        $guard->logoutCurrentDevice();
        $guard->login($target, false);
        $request->session()->regenerate(true);
    }

    public function stop(Request $request): ?User
    {
        $audit = $this->validatedActiveAudit($request);
        if (!$audit) {
            return null;
        }

        $administrator = User::query()->find($audit->admin_user_id);
        if (
            !$administrator
            || !$administrator->isActive()
            || !$administrator->isSuperAdmin()
            || (bool) $administrator->must_change_password
            || (int) $request->session()->get(self::ADMIN_PASSWORD_VERSION_SESSION_KEY, -1) !== (int) $administrator->password_security_version
        ) {
            $this->finalize($audit, UserImpersonationAuditLog::STATUS_EXPIRED);
            $this->clearSession($request);
            $this->webGuard()->logoutCurrentDevice();

            return null;
        }

        $administratorPasswordConfirmedAt = $request->session()->get(self::ADMIN_PASSWORD_CONFIRMED_SESSION_KEY);
        $this->finalize($audit, UserImpersonationAuditLog::STATUS_ENDED);
        $this->webGuard()->logoutCurrentDevice();
        $this->clearSession($request);
        if ($administratorPasswordConfirmedAt !== null) {
            $request->session()->put('auth.password_confirmed_at', $administratorPasswordConfirmedAt);
        }
        $this->webGuard()->login($administrator, false);
        $request->session()->put('auth_password_version', (int) $administrator->password_security_version);
        $request->session()->regenerate(true);

        return $administrator;
    }

    public function validateActiveSession(Request $request, User $currentUser): bool
    {
        $audit = $this->auditFromSession($request);
        if (!$audit || !$this->matchesSessionReference($request, $audit)) {
            return false;
        }

        if (
            $audit->status !== UserImpersonationAuditLog::STATUS_ACTIVE
            || $audit->expires_at === null
            || $audit->expires_at->isPast()
            || (string) $currentUser->getKey() !== (string) $audit->target_user_id
            || (string) $request->session()->get(self::ADMIN_ID_SESSION_KEY) !== (string) $audit->admin_user_id
            || (string) $request->session()->get(self::TARGET_ID_SESSION_KEY) !== (string) $audit->target_user_id
            || (int) $request->session()->get('auth_password_version', 0) < (int) $currentUser->password_security_version
            || !$currentUser->isActive()
            || (bool) $currentUser->must_change_password
            || !$this->policy->canBeImpersonated($currentUser)
        ) {
            return false;
        }

        $administrator = User::query()->find($audit->admin_user_id);

        return $administrator !== null
            && $administrator->isActive()
            && $administrator->isSuperAdmin()
            && !(bool) $administrator->must_change_password
            && (int) $request->session()->get(self::ADMIN_PASSWORD_VERSION_SESSION_KEY, -1) === (int) $administrator->password_security_version;
    }

    public function auditFromSession(Request $request): ?UserImpersonationAuditLog
    {
        $auditId = $request->session()->get(self::AUDIT_SESSION_KEY);

        return $auditId
            ? UserImpersonationAuditLog::query()->find($auditId)
            : null;
    }

    public function endIfSessionUserChanged(
        int|string|null $targetId,
        UserImpersonationAuditLog $audit,
    ): void
    {
        if ($targetId === null || (string) $this->webGuard()->id() === (string) $targetId) {
            return;
        }

        $this->finalize($audit, UserImpersonationAuditLog::STATUS_ENDED);
    }

    public function expire(Request $request): void
    {
        $audit = $this->auditFromSession($request);
        if ($audit && $this->matchesSessionReference($request, $audit)) {
            $this->finalize($audit, UserImpersonationAuditLog::STATUS_EXPIRED);
        }

        $this->webGuard()->logoutCurrentDevice();
        $this->clearSession($request);
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function validatedActiveAudit(Request $request): ?UserImpersonationAuditLog
    {
        $audit = $this->auditFromSession($request);
        $currentUser = $request->user();

        if (
            !$audit
            || !$currentUser
            || !$this->matchesSessionReference($request, $audit)
            || $audit->status !== UserImpersonationAuditLog::STATUS_ACTIVE
            || $audit->expires_at === null
            || $audit->expires_at->isPast()
            || (string) $currentUser->getKey() !== (string) $audit->target_user_id
            || (string) $request->session()->get(self::ADMIN_ID_SESSION_KEY) !== (string) $audit->admin_user_id
        ) {
            return null;
        }

        return $audit;
    }

    private function matchesSessionReference(Request $request, UserImpersonationAuditLog $audit): bool
    {
        $reference = (string) $request->session()->get(self::REFERENCE_SESSION_KEY, '');

        return $reference !== ''
            && hash_equals($audit->session_reference_hash, hash('sha256', $reference));
    }

    private function finalize(UserImpersonationAuditLog $audit, string $status): bool
    {
        if (!in_array($status, [
            UserImpersonationAuditLog::STATUS_ENDED,
            UserImpersonationAuditLog::STATUS_EXPIRED,
        ], true)) {
            return false;
        }

        $updated = UserImpersonationAuditLog::query()
            ->whereKey($audit->getKey())
            ->where('status', UserImpersonationAuditLog::STATUS_ACTIVE)
            ->update([
                'status' => $status,
                'ended_at' => now(),
            ]);

        return $updated === 1;
    }

    private function clearSession(Request $request): void
    {
        $request->session()->forget([
            self::AUDIT_SESSION_KEY,
            self::REFERENCE_SESSION_KEY,
            self::ADMIN_ID_SESSION_KEY,
            self::ADMIN_NAME_SESSION_KEY,
            self::ADMIN_PASSWORD_VERSION_SESSION_KEY,
            self::ADMIN_PASSWORD_CONFIRMED_SESSION_KEY,
            self::TARGET_ID_SESSION_KEY,
        ]);
    }

    private function webGuard(): SessionGuard
    {
        /** @var SessionGuard $guard */
        $guard = Auth::guard('web');

        return $guard;
    }
}
