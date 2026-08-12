<?php

namespace App\Models;

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
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(LpmcDocument::class, 'lpmc_document_id');
    }
}
