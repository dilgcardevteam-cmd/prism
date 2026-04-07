<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use App\Support\RolePermissionRegistry;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_REGIONAL = 'user_regional';
    public const ROLE_PROVINCIAL = 'user_provincial';
    public const ROLE_MLGOO = 'user_mlgoo';
    public const ROLE_LGU = 'user_lgu';

    public const ACCESS_SCOPE_ALL = 'crud:*';
    public const ACCESS_SCOPE_NONE = 'crud:none';
    public const ACCESS_PERMISSION_PREFIX = 'crud:';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbusers';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'idno';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fname',
        'lname',
        'agency',
        'position',
        'region',
        'province',
        'office',
        'emailaddress',
        'mobileno',
        'username',
        'password',
        'role',
        'status',
        'access',
        'registration_ip_address',
        'verification_token',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the email address that should be used for verification.
     *
     * @return string
     */
    public function getEmailForVerification()
    {
        return $this->emailaddress;
    }

    /**
     * Determine if the user has verified their email address.
     *
     * @return bool
     */
    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Mark the given user's email as verified.
     *
     * @return bool
     */
    public function markEmailAsVerified()
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Generate a verification token and save it.
     *
     * @return string
     */
    public function generateVerificationToken()
    {
        $token = Str::random(64);
        $this->verification_token = $token;
        $this->save();

        return $token;
    }

    /**
     * Verify the user's email using the token.
     *
     * @param string $token
     * @return bool
     */
    public function verifyEmailWithToken($token)
    {
        if ($this->verification_token === $token && !$this->hasVerifiedEmail()) {
            $this->email_verified_at = now();
            $this->verification_token = null;

            return $this->save();
        }

        return false;
    }

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        try {
            if (!$this->verification_token) {
                $this->generateVerificationToken();
            }

            \Illuminate\Support\Facades\Mail::send(new \App\Mail\VerifyEmailMailable($this));

            \Illuminate\Support\Facades\Log::info('Verification email sent', [
                'user_id' => $this->id,
                'email' => $this->emailaddress,
                'timestamp' => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send verification email', [
                'user_id' => $this->id,
                'email' => $this->emailaddress,
                'error' => $e->getMessage(),
                'driver' => config('mail.default'),
            ]);

            $this->notify(new VerifyEmailNotification);
        }
    }

    /**
     * Get the email address to be used for password reset.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->emailaddress;
    }

    /**
     * Find the user by username for authentication.
     *
     * @param string $username
     * @return mixed
     */
    public static function findByUsername($username)
    {
        return static::where('username', $username)->first();
    }

    public static function roleOptions(): array
    {
        return array_merge(self::activeBuiltInRoleOptions(), UserRole::roleOptions());
    }

    public static function allRoleOptions(): array
    {
        $roleOptions = self::builtInRoleOptions();

        foreach (UserRole::builtInOverrides() as $roleKey => $definition) {
            if (($definition['label'] ?? '') !== '') {
                $roleOptions[$roleKey] = $definition['label'];
            }
        }

        foreach (UserRole::definitions() as $definition) {
            if (!UserRole::isBuiltInRoleKey($definition['role_key'])) {
                $roleOptions[$definition['role_key']] = $definition['label'];
            }
        }

        return $roleOptions;
    }

    public static function activeBuiltInRoleOptions(): array
    {
        $roleOptions = self::builtInRoleOptions();

        foreach (UserRole::builtInOverrides() as $roleKey => $definition) {
            if (($definition['is_active'] ?? true) === false) {
                unset($roleOptions[$roleKey]);
                continue;
            }

            if (($definition['label'] ?? '') !== '') {
                $roleOptions[$roleKey] = $definition['label'];
            }
        }

        return $roleOptions;
    }

    public static function builtInRoleOptions(): array
    {
        return [
            self::ROLE_SUPERADMIN => 'Superadmin',
            self::ROLE_REGIONAL => 'Regional User',
            self::ROLE_PROVINCIAL => 'Provincial User',
            self::ROLE_MLGOO => 'MLGOO User',
            self::ROLE_LGU => 'LGU User',
        ];
    }

    public static function roleAliases(): array
    {
        return [
            'admin' => [self::ROLE_SUPERADMIN],
            'superadmin' => [self::ROLE_SUPERADMIN],
            'central' => [self::ROLE_SUPERADMIN],
            'central_office' => [self::ROLE_SUPERADMIN],
            'province' => [self::ROLE_PROVINCIAL],
            'provincial' => [self::ROLE_PROVINCIAL],
            'region' => [self::ROLE_REGIONAL],
            'regional' => [self::ROLE_REGIONAL],
            'mlgoo' => [self::ROLE_MLGOO],
            'lgu' => [self::ROLE_LGU, self::ROLE_MLGOO],
        ];
    }

    public function normalizedRole(): string
    {
        return strtolower(trim((string) $this->role));
    }

    public function hasAssignedRole(): bool
    {
        return $this->normalizedRole() !== '';
    }

    public function roleLabel(): string
    {
        $normalizedRole = $this->normalizedRole();

        if ($normalizedRole === '') {
            return 'Unassigned';
        }

        return self::allRoleOptions()[$normalizedRole] ?? ucwords(str_replace('_', ' ', $normalizedRole));
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([$this->fname, $this->lname])));
    }

    public function isSuperAdmin(): bool
    {
        return $this->normalizedRole() === self::ROLE_SUPERADMIN;
    }

    public function isRegionalUser(): bool
    {
        return $this->normalizedRole() === self::ROLE_REGIONAL;
    }

    public function isProvincialUser(): bool
    {
        return $this->normalizedRole() === self::ROLE_PROVINCIAL;
    }

    public function isMlgooUser(): bool
    {
        return $this->normalizedRole() === self::ROLE_MLGOO;
    }

    public function isLguUser(): bool
    {
        return in_array($this->normalizedRole(), [self::ROLE_LGU, self::ROLE_MLGOO], true);
    }

    public function isActive(): bool
    {
        return strtolower(trim((string) $this->status)) === 'active';
    }

    public function isCentralOfficeAdmin(): bool
    {
        return $this->isSuperAdmin();
    }

    public function matchesRoleAlias(string $role): bool
    {
        $normalizedAlias = strtolower(trim($role));
        $allowedRoles = self::roleAliases()[$normalizedAlias] ?? [$normalizedAlias];

        return in_array($this->normalizedRole(), $allowedRoles, true);
    }

    public function normalizedAgency(): string
    {
        return Str::lower(trim((string) $this->agency));
    }

    public function normalizedRegion(): string
    {
        return Str::lower(trim((string) $this->region));
    }

    public function normalizedRegionComparable(?string $value = null): string
    {
        $normalizedValue = Str::lower(trim((string) ($value ?? $this->region)));
        $normalizedValue = preg_replace('/\([^)]*\)/', ' ', $normalizedValue) ?? $normalizedValue;
        $normalizedValue = preg_replace('/[^a-z0-9\s-]/i', ' ', $normalizedValue) ?? $normalizedValue;
        $normalizedValue = preg_replace('/\s+/', ' ', $normalizedValue) ?? $normalizedValue;

        return trim($normalizedValue);
    }

    public function normalizedProvince(): string
    {
        return Str::lower(trim((string) $this->province));
    }

    public function normalizedOffice(): string
    {
        return Str::lower(trim((string) $this->office));
    }

    public function normalizedOfficeComparable(?string $value = null): string
    {
        $normalizedValue = Str::lower(trim((string) ($value ?? $this->office)));
        $baseValue = trim((string) preg_replace('/,.*$/', '', $normalizedValue));
        $baseValue = preg_replace('/\([^)]*\)/', ' ', $baseValue) ?? $baseValue;
        $baseValue = preg_replace('/^(municipality|city)\s+of\s+/i', '', $baseValue) ?? $baseValue;
        $baseValue = preg_replace('/\s+(municipality|city)$/i', '', $baseValue) ?? $baseValue;
        $baseValue = preg_replace('/[^a-z0-9\s-]/i', ' ', $baseValue) ?? $baseValue;
        $baseValue = preg_replace('/\s+/', ' ', $baseValue) ?? $baseValue;

        return trim($baseValue);
    }

    public function isDilgUser(): bool
    {
        return $this->normalizedAgency() === 'dilg';
    }

    public function isLguScopedUser(): bool
    {
        return $this->isLguUser() || $this->normalizedAgency() === 'lgu';
    }

    public function isRegionalOfficeAssignment(): bool
    {
        if (!$this->isDilgUser() || $this->isLguScopedUser()) {
            return false;
        }

        return str_contains($this->normalizedProvince(), 'regional office')
            || str_contains($this->normalizedOffice(), 'regional office');
    }

    public function matchesAssignedOffice(?string $value): bool
    {
        $assignedOffice = $this->normalizedOffice();
        $candidate = Str::lower(trim((string) $value));

        if ($assignedOffice === '' || $candidate === '') {
            return false;
        }

        if ($candidate === $assignedOffice) {
            return true;
        }

        $assignedComparable = $this->normalizedOfficeComparable();

        return $assignedComparable !== ''
            && $this->normalizedOfficeComparable($candidate) === $assignedComparable;
    }

    public function defaultCrudPermissions(): array
    {
        return RolePermissionRegistry::permissionsForRole(
            $this->normalizedRole(),
            RolePermissionSetting::permissionsForRole($this->normalizedRole()),
        );
    }

    public function usesScopedCrudAccess(): bool
    {
        $access = strtolower(trim((string) $this->access));

        return $access === self::ACCESS_SCOPE_ALL
            || $access === self::ACCESS_SCOPE_NONE
            || str_starts_with($access, self::ACCESS_PERMISSION_PREFIX);
    }

    public function grantedCrudPermissions(): array
    {
        $access = strtolower(trim((string) $this->access));

        if ($access === self::ACCESS_SCOPE_ALL) {
            return ['*'];
        }

        if ($access === self::ACCESS_SCOPE_NONE || $access === '') {
            return [];
        }

        if (!str_starts_with($access, self::ACCESS_PERMISSION_PREFIX)) {
            return [];
        }

        $permissions = substr($access, strlen(self::ACCESS_PERMISSION_PREFIX));

        return RolePermissionRegistry::normalizePermissions(
            array_values(array_filter(array_map('trim', explode(',', $permissions))))
        );
    }

    public function effectiveCrudPermissions(): array
    {
        return $this->usesScopedCrudAccess()
            ? $this->grantedCrudPermissions()
            : $this->defaultCrudPermissions();
    }

    public function effectiveConcreteCrudPermissions(): array
    {
        $permissions = $this->effectiveCrudPermissions();

        if (in_array('*', $permissions, true)) {
            return RolePermissionRegistry::validPermissionKeys();
        }

        return RolePermissionRegistry::normalizePermissions($permissions);
    }

    public function hasCustomCrudPermissions(): bool
    {
        return $this->usesScopedCrudAccess();
    }

    public function permissionCandidateKeys(string $aspect, string $action): array
    {
        $normalizedAspect = strtolower(trim($aspect));
        $normalizedAction = strtolower(trim($action));
        $candidateActions = array_values(array_unique(array_filter([
            $normalizedAction,
            $normalizedAction === 'upload' ? 'add' : null,
            $normalizedAction === 'add' ? 'upload' : null,
            $normalizedAction === 'view' ? 'add' : null,
            $normalizedAction === 'view' ? 'upload' : null,
            $normalizedAction === 'view' ? 'update' : null,
            $normalizedAction === 'view' ? 'delete' : null,
        ])));

        return array_map(function ($candidateAction) use ($normalizedAspect) {
            return $normalizedAspect . '.' . $candidateAction;
        }, $candidateActions);
    }

    public function hasDefaultCrudPermission(string $aspect, string $action): bool
    {
        $defaultPermissions = $this->defaultCrudPermissions();

        if (in_array('*', $defaultPermissions, true)) {
            return true;
        }

        return count(array_intersect($this->permissionCandidateKeys($aspect, $action), $defaultPermissions)) > 0;
    }

    public function hasExplicitCrudPermission(string $aspect, string $action): bool
    {
        if (!$this->usesScopedCrudAccess()) {
            return false;
        }

        $explicitPermissions = $this->grantedCrudPermissions();

        if (in_array('*', $explicitPermissions, true)) {
            return true;
        }

        return count(array_intersect($this->permissionCandidateKeys($aspect, $action), $explicitPermissions)) > 0;
    }

    public function hasCrudPermission(string $aspect, string $action): bool
    {
        $permissions = $this->effectiveCrudPermissions();

        if (in_array('*', $permissions, true)) {
            return true;
        }

        $permissionKeys = $this->permissionCandidateKeys($aspect, $action);

        return count(array_intersect($permissionKeys, $permissions)) > 0;
    }

    public function submittedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'submitted_by', 'idno');
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to', 'idno');
    }
}
