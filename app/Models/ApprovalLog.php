<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'submission_id',
        'project_code',
        'quarter',
        'document_type',
        'approver_id',
        'uploader_id',
        'approval_level',
        'action',
        'remarks',
        'previous_status',
        'new_status',
        'returned_to_id',
        'forwarded_to_id',
        'revision_number',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(FundUtilizationApprovalWorkflow::class, 'submission_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id', 'idno');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id', 'idno');
    }
}
