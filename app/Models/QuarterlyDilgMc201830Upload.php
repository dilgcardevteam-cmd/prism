<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuarterlyDilgMc201830Upload extends Model
{
    protected $table = 'quarterly_dilg_mc_2018_30_uploads';

    protected $fillable = [
        'office',
        'province',
        'year',
        'quarter',
        'file_path',
        'original_name',
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
        'year' => 'integer',
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_at_dilg_po' => 'datetime',
        'approved_at_dilg_ro' => 'datetime',
    ];
}
