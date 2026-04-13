<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuarterlyRpmesForm6Upload extends Model
{
    protected $table = 'quarterly_rpmes_form6_uploads';

    protected $fillable = [
        'project_code',
        'quarter',
        'file_path',
        'original_name',
        'uploaded_by',
        'uploaded_at',
        'status',
        'approved_by',
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'idno');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'idno');
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
