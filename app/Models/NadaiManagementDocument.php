<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NadaiManagementDocument extends Model
{
    protected $table = 'tblnadai_management_documents';

    protected $fillable = [
        'office',
        'province',
        'municipality',
        'barangay',
        'funding_year',
        'program',
        'project_title',
        'nadai_date',
        'original_filename',
        'file_path',
        'uploaded_by',
        'uploaded_at',
        'confirmation_accepted_by',
        'confirmation_accepted_at',
        'confirmation_acceptance_remarks',
    ];

    protected $casts = [
        'nadai_date' => 'date',
        'uploaded_at' => 'datetime',
        'confirmation_accepted_at' => 'datetime',
    ];
}
