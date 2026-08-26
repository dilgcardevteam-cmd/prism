<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringEvaluationMonthlyDocument extends Model
{
    protected $table = 'tblmonitoring_evaluation_monthly_documents';

    protected $fillable = [
        'office',
        'province',
        'doc_type',
        'year',
        'month',
        'file_path',
        'uploaded_by',
        'uploaded_at',
        'status',
        'approved_at',
        'approved_at_dilg_po',
        'approved_at_dilg_ro',
        'approved_by_dilg_po',
        'approved_by_dilg_ro',
        'approval_remarks',
        'user_remarks',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_at_dilg_po' => 'datetime',
        'approved_at_dilg_ro' => 'datetime',
        'year' => 'integer',
    ];
}

