<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseBackupRun extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'backup_automation_setting_id',
        'backup_type',
        'status',
        'filename',
        'stored_path',
        'mailed_to',
        'was_encrypted',
        'retention_deleted_count',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'mailed_to' => 'array',
            'was_encrypted' => 'boolean',
            'retention_deleted_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
