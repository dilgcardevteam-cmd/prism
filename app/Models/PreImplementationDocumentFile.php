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
        'approved_at_c_mlgoo',
        'approved_by_c_mlgoo',
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
        'approved_at_c_mlgoo' => 'datetime',
        'approved_at_dilg_po' => 'datetime',
        'approved_at_dilg_ro' => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'idno');
    }

    public function cMlgooApprover()
    {
        return $this->belongsTo(User::class, 'approved_by_c_mlgoo', 'idno');
    }

    public function dilgPoApprover()
    {
        return $this->belongsTo(User::class, 'approved_by_dilg_po', 'idno');
    }

    public function dilgRoApprover()
    {
        return $this->belongsTo(User::class, 'approved_by_dilg_ro', 'idno');
    }
}

