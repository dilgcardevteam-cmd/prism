<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeadlineConfiguration extends Model
{
    protected $fillable = [
        'funding_year',
        'pcr_submission_deadline',
        'rssa_report_deadline',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'funding_year' => 'integer',
            'pcr_submission_deadline' => 'date',
            'rssa_report_deadline' => 'date',
            'updated_by' => 'integer',
        ];
    }
}
