<?php

namespace App\Modules\SystemSettings\Models;

use Illuminate\Database\Eloquent\Model;

class LguReportorialDeadline extends Model
{
    protected $table = 'lgu_reportorial_deadlines';

    protected $fillable = [
        'aspect',
        'timeline',
        'reporting_year',
        'reporting_period',
        'deadline_date',
        'deadline_time',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'reporting_year' => 'integer',
            'deadline_date' => 'date',
            'updated_by' => 'integer',
        ];
    }
}

