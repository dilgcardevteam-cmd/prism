<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreImplementationDocumentFile extends Model
{
    use HasFactory;

    protected $table = 'pre_implementation_document_files';

    protected $fillable = [
        'project_code',
        'document_type',
        'file_path',
        'uploaded_at',
        'uploaded_by',
        'status',
        'approved_at',
        'approved_by',
        'approved_at_dilg_po',
        'approved_by_dilg_po',
        'approved_at_dilg_ro',
        'approved_by_dilg_ro',
        'approval_remarks',
        'user_remarks',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_at_dilg_po' => 'datetime',
        'approved_at_dilg_ro' => 'datetime',
    ];
}

