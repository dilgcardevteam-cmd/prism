<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SglgifProjectCompletionReport extends Model
{
    use HasFactory;

    protected $table = 'sglgif_project_completion_reports';

    protected $fillable = [
        'project_code',
        'project_title',
        'province',
        'city_municipality',
        'funding_year',
        'corrective_measures_conducted_path',
        'final_sord_with_refund_path',
        'final_swa_path',
        'project_completion_report_path',
        'certificate_of_completion_path',
        'certificate_of_occupancy_path',
        'certificate_of_turnover_and_acceptance_path',
        'warranty_certificate_path',
        'geotagged_photos_path',
        'copy_of_or_cr_under_lgu_name_path',
        'copy_of_official_receipt_heavy_equipment_path',
        'updated_by',
    ];
}
