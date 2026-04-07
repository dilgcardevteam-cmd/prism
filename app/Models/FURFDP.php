<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FURFDP extends Model
{
    protected $table = 'tbfur_fdp';
    
    protected $fillable = [
        'project_code',
        'quarter',
        'fdp_file_path',
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
        // Individual FDP approval fields
        'fdp_status', 'fdp_approved_by', 'fdp_approved_at', 'fdp_remarks', 'fdp_uploaded_at', 'fdp_encoder_id',
        // LGU posting link fields
        'posting_link', 'posting_status', 'posting_approved_by', 'posting_approved_at', 'posting_remarks',
        'posting_uploaded_at', 'posting_encoder_id',
        // Posting link validation timestamps
        'posting_approved_at_dilg_po', 'posting_approved_at_dilg_ro',
        'posting_approved_by_dilg_po', 'posting_approved_by_dilg_ro',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_at_dilg_po' => 'datetime',
        'approved_at_dilg_ro' => 'datetime',
        'fdp_approved_at' => 'datetime',
        'fdp_uploaded_at' => 'datetime',
        'posting_approved_at' => 'datetime',
        'posting_uploaded_at' => 'datetime',
        'posting_approved_at_dilg_po' => 'datetime',
        'posting_approved_at_dilg_ro' => 'datetime',
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
