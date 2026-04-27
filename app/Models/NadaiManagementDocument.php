<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NadaiManagementDocument extends Model
{
    protected $table = 'tblnadai_management_documents';

    protected $fillable = [
        'office',
        'province',
        'project_title',
        'nadai_date',
        'original_filename',
        'file_path',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'nadai_date' => 'date',
        'uploaded_at' => 'datetime',
    ];
}
