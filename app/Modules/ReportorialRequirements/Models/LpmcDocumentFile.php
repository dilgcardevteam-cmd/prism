<?php

namespace App\Modules\ReportorialRequirements\Models;

use App\Models\User;
use App\Models\FundUtilizationApprovalWorkflow;

use Illuminate\Database\Eloquent\Model;

class LpmcDocumentFile extends Model
{
    protected $table = 'tblpmc_document_files';

    protected $fillable = [
        'lpmc_document_id',
        'file_path',
        'original_filename',
        'uploaded_by',
        'uploaded_at',
        'regional_replacement_used_at',
        'workflow_status',
        'workflow_actor_id',
        'workflow_updated_at',
        'workflow_previous_status',
        'workflow_remarks',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'regional_replacement_used_at' => 'datetime',
        'workflow_updated_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(LpmcDocument::class, 'lpmc_document_id');
    }

    public function workflowLogs()
    {
        return $this->hasMany(LpmcDocumentFileWorkflowLog::class, 'lpmc_document_file_id')
            ->orderBy('created_at');
    }

    public function getEffectiveWorkflowStatusAttribute(): string
    {
        return (string) ($this->workflow_status ?? $this->document?->status ?? 'pending');
    }
}
