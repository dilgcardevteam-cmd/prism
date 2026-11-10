<?php

namespace App\Modules\Users\Models;

use Illuminate\Database\Eloquent\Model;

class UserImpersonationAuditLog extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';
    public const STATUS_EXPIRED = 'expired';

    protected $table = 'user_impersonation_audit_logs';

    protected $fillable = [
        'admin_user_id',
        'admin_name',
        'target_user_id',
        'target_username',
        'started_at',
        'ended_at',
        'expires_at',
        'ip_address',
        'user_agent',
        'session_reference_hash',
        'reason',
        'status',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $log): void {
            $changedFields = array_keys($log->getDirty());
            sort($changedFields);

            if (
                $log->getOriginal('status') !== self::STATUS_ACTIVE
                || $log->status !== self::STATUS_ENDED && $log->status !== self::STATUS_EXPIRED
                || $changedFields !== ['ended_at', 'status']
                || $log->ended_at === null
            ) {
                throw new \LogicException('Impersonation audit logs can only be finalized once.');
            }
        });

        static::deleting(function (): void {
            throw new \LogicException('Impersonation audit logs cannot be deleted.');
        });
    }
}
