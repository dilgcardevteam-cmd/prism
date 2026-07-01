<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FURBatchDocument extends Model
{
    protected $table = 'tbfur_batch_documents';

    protected $fillable = [
        'project_code',
        'quarter',
        'batch_document_file_path',
        'batch_document_files_json',
        'status',
        'approval_remarks',
        'approved_by',
        'approved_at',
        'approved_at_dilg_po',
        'approved_at_dilg_ro',
        'approved_by_dilg_po',
        'approved_by_dilg_ro',
        'user_remarks',
        'encoder_id',
        'batch_document_uploaded_at',
        'batch_document_encoder_id',
    ];

    protected $casts = [
        'batch_document_files_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_at_dilg_po' => 'datetime',
        'approved_at_dilg_ro' => 'datetime',
        'batch_document_uploaded_at' => 'datetime',
    ];

    public function encoder()
    {
        return $this->belongsTo(\App\Models\User::class, 'encoder_id', 'idno');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by', 'idno');
    }
}
