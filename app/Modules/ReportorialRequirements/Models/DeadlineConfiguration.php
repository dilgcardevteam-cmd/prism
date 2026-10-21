<?php

namespace App\Modules\ReportorialRequirements\Models;

use App\Models\User;
use App\Models\FundUtilizationApprovalWorkflow;

use Illuminate\Database\Eloquent\Model;

class DeadlineConfiguration extends Model
{
    protected $table = 'deadline_configurations';
    protected $fillable = [
        'funding_year',
        'pcr_submission_deadline',
        'rssa_report_deadline',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'funding_year' => 'integer',
            'pcr_submission_deadline' => 'date',
            'rssa_report_deadline' => 'date',
            'updated_by' => 'integer',
        ];
    }
}
