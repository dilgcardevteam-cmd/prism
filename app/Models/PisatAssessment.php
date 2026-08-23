<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PisatAssessment extends Model
{
    use HasFactory;

    protected $table = 'pisat_assessments';

    protected $fillable = [
        'user_id',
        'status',
        'office',
        'project_code',

        // I. Project Profile
        'project_title',
        'location',
        'implementing_lgu',
        'date_of_turnover',
        'date_of_assessment',
        'respondent_group',

        // II. The "Most Significant Change" (MSC) Story
        'msc_title',
        'msc_situation_before',
        'msc_situation_now',
        'msc_why_significant',
        'msc_category',

        // III. Project Impact Assessment
        'travel_time_savings_current',
        'travel_time_savings_previous',
        'travel_time_savings_rating',
        'asset_value_findings',
        'asset_value_rating',
        'new_economic_activities_findings',
        'new_economic_activities_rating',
        'agricultural_output_findings',
        'agricultural_output_rating',

        'access_to_services_findings',
        'access_to_services_rating',
        'health_outcomes_findings',
        'health_outcomes_rating',
        'safety_security_findings',
        'safety_security_rating',
        'community_pride_findings',
        'community_pride_rating',

        'unexpected_positive',
        'unexpected_negative',

        // IV. Sustainability and Operational Assessment
        'manager_maintainer',
        'organized_user_group',
        'organization_recognized',
        'has_om_fund',
        'source_of_funds_user_fees',
        'source_of_funds_user_fees_rate',
        'source_of_funds_barangay',
        'source_of_funds_municipal',
        'source_of_funds_other',
        'source_of_funds_other_desc',
        'available_funds',
        'functional_status',
        'defect_structural_cracks',
        'defect_drainage_leakage',
        'defect_electrical_plumbing',
        'defect_vandalism_wear',
        'defect_other',
        'defect_other_desc',

        // V. Evaluation Findings and Proposed Actions
        'impact_classification',
        'key_findings',
        'proposed_actions',
        'prepared_by',
        'position',
        'date_prepared',
        'remarks',
    ];

    protected $casts = [
        'date_of_turnover' => 'date',
        'date_of_assessment' => 'date',
        'date_prepared' => 'date',
        'source_of_funds_user_fees' => 'boolean',
        'source_of_funds_barangay' => 'boolean',
        'source_of_funds_municipal' => 'boolean',
        'source_of_funds_other' => 'boolean',
        'defect_structural_cracks' => 'boolean',
        'defect_drainage_leakage' => 'boolean',
        'defect_electrical_plumbing' => 'boolean',
        'defect_vandalism_wear' => 'boolean',
        'defect_other' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'idno');
    }
}

