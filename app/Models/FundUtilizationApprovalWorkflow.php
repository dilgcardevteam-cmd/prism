<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FundUtilizationApprovalWorkflow extends Model
{
    protected $fillable = [
        'project_code',
        'quarter',
        'document_type',
        'uploader_id',
        'uploader_role',
        'current_approval_level',
        'last_approved_level',
        'returned_from_level',
        'revision_number',
        'current_approver_id',
        'current_approver_role',
        'status',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id', 'idno');
    }

    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id', 'idno');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ApprovalLog::class, 'submission_id');
    }

    /**
     * Determine if workflow is awaiting Provincial Officer action after Regional Officer return.
     * This occurs when RO returns submission and PO must formally return it to LGU.
     */
    public function isPendingProvinceReturnToLgu(): bool
    {
        return $this->status === 'Returned by Regional Officer'
            && (int) ($this->current_approval_level ?? 0) === 1
            && (int) ($this->uploader_role === User::ROLE_LGU ? 1 : 0) === 1;
    }

    /**
     * Determine if submission is at provincial level and came from regional return.
     */
    public function isPendingProvinceReviewAfterRegionalReturn(): bool
    {
        return str_starts_with((string) $this->status, 'Returned by Regional')
            && (int) ($this->current_approval_level ?? 0) === 1;
    }

    /**
     * Determine if submission is fully approved.
     */
    public function isFullyApproved(): bool
    {
        return $this->status === 'Approved' && $this->completed_at !== null;
    }

    /**
     * Determine if submission is awaiting approval.
     */
    public function isPendingApproval(): bool
    {
        return str_starts_with((string) $this->status, 'Pending')
            && $this->current_approver_id !== null;
    }

    /**
     * Determine if submission has been returned for revision.
     */
    public function isReturned(): bool
    {
        return str_starts_with((string) $this->status, 'Returned by');
    }
}
