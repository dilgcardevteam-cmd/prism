<?php

namespace App\Modules\FundUtilization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundUtilizationBatchApprovalLog extends Model
{
    protected $table = 'fund_utilization_batch_approval_logs';
    public $timestamps = false;

    protected $fillable = [
        'batch_id',
        'batch_code',
        'actor_id',
        'action',
        'approval_level',
        'previous_status',
        'new_status',
        'remarks',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'batch_id' => 'integer',
        'approval_level' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FundUtilizationBatchApproval::class, 'batch_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id', 'idno');
    }
}

