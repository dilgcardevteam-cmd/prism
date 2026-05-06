<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LgsfProjectCompletionReport extends Model
{
    use HasFactory;

    protected $table = 'lgsf_project_completion_reports';

    protected $fillable = [
        'project_code',
        'project_title',
        'province',
        'city_municipality',
        'funding_year',
        'project_completion_report_path',
        'statement_of_work_accomplished_path',
        'as_built_plans_path',
        'certificate_of_completion_path',
        'statement_of_receipts_and_disbursements_path',
        'photos_path',
        'proof_of_reversion_of_unexpended_funds_path',
        'copy_of_or_cr_for_vehicles_path',
        'updated_by',
    ];
}
