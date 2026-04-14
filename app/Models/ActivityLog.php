<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const ACTION_LOGIN = 'LOGIN';
    public const ACTION_LOGOUT = 'LOGOUT';
    public const ACTION_REGISTER = 'REGISTER';
    public const ACTION_FAILED_LOGIN = 'FAILED_LOGIN';
    public const ACTION_PASSWORD_CHANGE = 'PASSWORD_CHANGE';
    public const ACTION_PASSWORD_RESET_REQUEST = 'PASSWORD_RESET_REQUEST';
    public const ACTION_PASSWORD_RESET = 'PASSWORD_RESET';
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_READ = 'READ';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_UPLOAD = 'UPLOAD';
    public const ACTION_EXPORT = 'EXPORT';
    public const ACTION_ROLE_CHANGE = 'ROLE_CHANGE';
    public const ACTION_PERMISSION_CHANGE = 'PERMISSION_CHANGE';
    public const ACTION_STATUS_CHANGE = 'STATUS_CHANGE';
    public const ACTION_VALIDATION_FAILED = 'VALIDATION_FAILED';
    public const ACTION_MAINTENANCE_MODE_CHANGE = 'MAINTENANCE_MODE_CHANGE';

    protected $fillable = [
        'user_id',
        'username',
        'action',
        'description',
        'timezone',
        'ip_address',
        'user_agent',
        'device',
        'properties',
        'created_at',
    ];

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        // Audit entries are append-only records and must remain immutable.
        static::updating(function (): void {
            throw new \LogicException('Activity logs are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new \LogicException('Activity logs are immutable and cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'idno');
    }
}
