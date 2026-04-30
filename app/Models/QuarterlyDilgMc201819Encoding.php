<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuarterlyDilgMc201819Encoding extends Model
{
    protected $table = 'quarterly_dilg_mc_2018_19_encodings';

    protected $fillable = [
        'office',
        'province',
        'year',
        'quarter',
        'rows',
        'last_saved_by',
        'last_saved_at',
    ];

    protected $casts = [
        'year' => 'integer',
        'rows' => 'array',
        'last_saved_at' => 'datetime',
    ];
}
