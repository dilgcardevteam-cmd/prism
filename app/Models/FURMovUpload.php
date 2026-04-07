<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FURMovUpload extends Model
{
    protected $table = 'tbfur_mov_uploads';
    
    protected $fillable = [
        'project_code',
        'quarter',
        'mov_file_path',
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
        'mov_uploaded_at',
        'mov_encoder_id',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_at_dilg_po' => 'datetime',
        'approved_at_dilg_ro' => 'datetime',
        'mov_uploaded_at' => 'datetime',
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
