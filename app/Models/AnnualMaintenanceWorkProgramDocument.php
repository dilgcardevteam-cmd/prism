<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnnualMaintenanceWorkProgramDocument extends Model
{
    protected $table = 'tblannual_maintenance_work_program_documents';

    protected $fillable = [
        'office',
        'province',
        'document_name',
        'document_year',
        'remarks',
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
        'document_year' => 'integer',
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_at_dilg_po' => 'datetime',
        'approved_at_dilg_ro' => 'datetime',
    ];
}
