<?php

namespace App\Http\Controllers;

use App\Models\PisatAssessment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PisatReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function getOffices(): array
    {
        return [
            'Abra' => [
                'PLGU Abra', 'Bangued', 'Boliney', 'Bucay', 'Bucloc', 'Daguioman', 'Danglas', 'Dolores',
                'La Paz', 'Lacub', 'Lagangilang', 'Lagayan', 'Langiden', 'Licuan-Baay', 'Luba', 'Malibcong',
                'Manabo', 'Peñarrubia', 'Pidigan', 'Pilar', 'Sallapadan', 'San Isidro', 'San Juan',
                'San Quintin', 'Tayum', 'Tineg', 'Tubo', 'Villaviciosa',
            ],
            'Apayao' => [
                'PLGU Apayao', 'Calanasan', 'Conner', 'Flora', 'Kabugao', 'Luna', 'Pudtol', 'Santa Marcela',
            ],
            'Benguet' => [
                'PLGU Benguet', 'Atok', 'Bakun', 'Bokod', 'Buguias', 'Itogon', 'Kabayan', 'Kapangan',
                'Kibungan', 'La Trinidad', 'Mankayan', 'Sablan', 'Tuba', 'Tublay',
            ],
            'City of Baguio' => [
                'City of Baguio',
            ],
            'Ifugao' => [
                'PLGU Ifugao', 'Aguinaldo', 'Alfonso Lista', 'Asipulo', 'Banaue', 'Hingyon', 'Hungduan',
                'Kiangan', 'Lagawe', 'Lamut', 'Mayoyao', 'Tinoc',
            ],
            'Kalinga' => [
                'PLGU Kalinga', 'Balbalan', 'Lubuagan', 'Pasil', 'Pinukpuk', 'Rizal', 'Tabuk', 'Tanudan',
            ],
            'Mountain Province' => [
                'PLGU Mountain Province', 'Barlig', 'Bauko', 'Besao', 'Bontoc', 'Natonin', 'Paracelis',
                'Sabangan', 'Sadanga', 'Sagada', 'Tadian',
            ],
        ];
    }

    private function buildAccessibleLfpQuery($user)
    {
        $province = trim((string) ($user->province ?? ''));
        $office = trim((string) ($user->office ?? ''));
        $provinceLower = strtolower($province);
        $officeLower = strtolower($office);

        $query = DB::table('locally_funded_projects as lfp');

        if ($user->isLguScopedUser()) {
            if ($office !== '') {
                if ($province !== '') {
                    $query->whereRaw('LOWER(lfp.province) = ?', [$provinceLower])
                          ->whereRaw('LOWER(lfp.city_municipality) = ?', [$officeLower]);
                } else {
                    $query->whereRaw('LOWER(lfp.city_municipality) = ?', [$officeLower]);
                }
            } elseif ($province !== '') {
                $query->whereRaw('LOWER(lfp.province) = ?', [$provinceLower]);
            }
        } elseif ($user->isDilgUser()) {
            if ($provinceLower === 'regional office') {
                // Regional Office has access to all
            } elseif ($province !== '') {
                $query->whereRaw('LOWER(lfp.province) = ?', [$provinceLower]);
            }
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!Schema::hasTable('locally_funded_projects')) {
            $projects = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
            $officesByProvince = $this->getOffices();
            $provinces = array_keys($officesByProvince);
            return view('reports.one-time.pisat.index', compact('projects', 'provinces', 'officesByProvince'));
        }

        $query = $this->buildAccessibleLfpQuery($user)
            ->join('subay_project_profiles as spp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
            ->leftJoin('pisat_assessments as pa', 'lfp.subaybayan_project_code', '=', 'pa.project_code')
            ->whereRaw("UPPER(spp.status) = 'COMPLETED'");

        // Apply filters
        if ($request->filled('search')) {
            $search = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('lfp.project_name', 'like', $search)
                  ->orWhere('lfp.subaybayan_project_code', 'like', $search);
            });
        }

        if ($request->filled('province')) {
            $query->where('lfp.province', $request->input('province'));
        }

        if ($request->filled('city_municipality')) {
            $query->where('lfp.city_municipality', $request->input('city_municipality'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'none') {
                $query->whereNull('pa.status');
            } else {
                $query->where('pa.status', $status);
            }
        }

        $query->select([
            'lfp.subaybayan_project_code as project_code',
            'lfp.project_name as project_title',
            'lfp.province',
            'lfp.city_municipality',
            'lfp.barangay',
            'lfp.funding_year',
            'pa.id as assessment_id',
            'pa.status as assessment_status',
            'pa.impact_classification as assessment_impact'
        ])
        ->orderByRaw("CASE WHEN lfp.funding_year IS NULL OR TRIM(lfp.funding_year) = '' THEN 1 ELSE 0 END")
        ->orderByRaw('CAST(lfp.funding_year AS UNSIGNED) DESC')
        ->orderBy('lfp.subaybayan_project_code');

        $projects = $query->paginate(15)->withQueryString();

        $officesByProvince = $this->getOffices();
        $provinces = array_keys($officesByProvince);

        return view('reports.one-time.pisat.index', compact('projects', 'provinces', 'officesByProvince'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $preselectedProject = null;

        if ($request->filled('project_code')) {
            $projectCode = $request->input('project_code');

            $existing = PisatAssessment::where('project_code', $projectCode)->first();
            if ($existing) {
                return redirect()->route('reports.one-time.pisat.show', $existing)
                    ->with('info', 'An assessment already exists for this project.');
            }

            if (Schema::hasTable('locally_funded_projects')) {
                $preselectedProject = $this->buildAccessibleLfpQuery($user)
                    ->join('subay_project_profiles as spp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
                    ->where('lfp.subaybayan_project_code', $projectCode)
                    ->select([
                        'lfp.subaybayan_project_code as project_code',
                        'lfp.project_name as project_title',
                        'lfp.province',
                        'lfp.city_municipality',
                        'lfp.barangay',
                        'spp.date_of_nadai'
                    ])
                    ->first();
            }
        }

        $completedProjects = [];
        if (Schema::hasTable('locally_funded_projects')) {
            $completedProjects = $this->buildAccessibleLfpQuery($user)
                ->join('subay_project_profiles as spp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
                ->whereRaw("UPPER(spp.status) = 'COMPLETED'")
                ->select([
                    'lfp.subaybayan_project_code as project_code',
                    'lfp.project_name as project_title',
                    'lfp.province',
                    'lfp.city_municipality',
                    'lfp.barangay',
                    'spp.date_of_nadai'
                ])
                ->get();
        }

        return view('reports.one-time.pisat.create', compact('completedProjects', 'preselectedProject'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'project_code' => 'nullable|string|unique:pisat_assessments,project_code',
            'project_title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'implementing_lgu' => 'nullable|string|max:255',
            'date_of_turnover' => 'nullable|date',
            'date_of_assessment' => 'nullable|date',
            'respondent_group' => 'nullable|string|max:255',

            // MSC
            'msc_title' => 'nullable|string|max:255',
            'msc_situation_before' => 'nullable|string',
            'msc_situation_now' => 'nullable|string',
            'msc_why_significant' => 'nullable|string',
            'msc_category' => 'nullable|string',

            // Impact Ratings & Findings
            'travel_time_savings_current' => 'nullable|string|max:255',
            'travel_time_savings_previous' => 'nullable|string|max:255',
            'travel_time_savings_rating' => 'nullable|integer|between:1,5',
            'asset_value_findings' => 'nullable|string',
            'asset_value_rating' => 'nullable|integer|between:1,5',
            'new_economic_activities_findings' => 'nullable|string',
            'new_economic_activities_rating' => 'nullable|integer|between:1,5',
            'agricultural_output_findings' => 'nullable|string',
            'agricultural_output_rating' => 'nullable|integer|between:1,5',

            'access_to_services_findings' => 'nullable|string',
            'access_to_services_rating' => 'nullable|integer|between:1,5',
            'health_outcomes_findings' => 'nullable|string',
            'health_outcomes_rating' => 'nullable|integer|between:1,5',
            'safety_security_findings' => 'nullable|string',
            'safety_security_rating' => 'nullable|integer|between:1,5',
            'community_pride_findings' => 'nullable|string',
            'community_pride_rating' => 'nullable|integer|between:1,5',

            'unexpected_positive' => 'nullable|string',
            'unexpected_negative' => 'nullable|string',

            // Sustainability
            'manager_maintainer' => 'nullable|string|max:255',
            'organized_user_group' => 'nullable|string',
            'organization_recognized' => 'nullable|string',
            'has_om_fund' => 'nullable|string',
            'source_of_funds_user_fees' => 'nullable|boolean',
            'source_of_funds_user_fees_rate' => 'nullable|string|max:50',
            'source_of_funds_barangay' => 'nullable|boolean',
            'source_of_funds_municipal' => 'nullable|boolean',
            'source_of_funds_other' => 'nullable|boolean',
            'source_of_funds_other_desc' => 'nullable|string|max:255',
            'available_funds' => 'nullable|numeric|min:0',
            'functional_status' => 'nullable|string',
            'defect_structural_cracks' => 'nullable|boolean',
            'defect_drainage_leakage' => 'nullable|boolean',
            'defect_electrical_plumbing' => 'nullable|boolean',
            'defect_vandalism_wear' => 'nullable|boolean',
            'defect_other' => 'nullable|boolean',
            'defect_other_desc' => 'nullable|string|max:255',
        ]);

        $checkboxes = [
            'source_of_funds_user_fees', 'source_of_funds_barangay', 'source_of_funds_municipal', 'source_of_funds_other',
            'defect_structural_cracks', 'defect_drainage_leakage', 'defect_electrical_plumbing', 'defect_vandalism_wear', 'defect_other'
        ];
        foreach ($checkboxes as $cb) {
            $validated[$cb] = $request->has($cb);
        }

        $validated['user_id'] = $user->idno;
        $validated['office'] = $user->office;
        $validated['status'] = $request->input('submit_btn') === 'submit' ? 'submitted' : 'draft';

        PisatAssessment::create($validated);

        return redirect()->route('reports.one-time.pisat')
            ->with('success', 'PISAT Assessment successfully ' . ($validated['status'] === 'submitted' ? 'submitted.' : 'saved as draft.'));
    }

    public function show(PisatAssessment $assessment)
    {
        $user = Auth::user();
        if ($user->isLguScopedUser() && $assessment->office !== $user->office) {
            abort(403, 'Unauthorized access to this assessment.');
        }

        return view('reports.one-time.pisat.show', compact('assessment'));
    }

    public function edit(PisatAssessment $assessment)
    {
        $user = Auth::user();
        if ($user->isLguScopedUser() && $assessment->office !== $user->office) {
            abort(403, 'Unauthorized access to this assessment.');
        }

        if ($user->isLguScopedUser() && !in_array($assessment->status, ['draft', 'returned'])) {
            return redirect()->route('reports.one-time.pisat.show', $assessment)
                ->with('error', 'Only drafts or returned assessments can be modified by LGUs.');
        }

        $completedProjects = [];
        if (Schema::hasTable('locally_funded_projects')) {
            $completedProjects = $this->buildAccessibleLfpQuery($user)
                ->join('subay_project_profiles as spp', 'lfp.subaybayan_project_code', '=', 'spp.project_code')
                ->whereRaw("UPPER(spp.status) = 'COMPLETED'")
                ->select([
                    'lfp.subaybayan_project_code as project_code',
                    'lfp.project_name as project_title',
                    'lfp.province',
                    'lfp.city_municipality',
                    'lfp.barangay',
                    'spp.date_of_nadai'
                ])
                ->get();
        }

        return view('reports.one-time.pisat.edit', compact('assessment', 'completedProjects'));
    }

    public function update(Request $request, PisatAssessment $assessment)
    {
        $user = Auth::user();
        if ($user->isLguScopedUser() && $assessment->office !== $user->office) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'project_code' => 'nullable|string|unique:pisat_assessments,project_code,' . $assessment->id,
            'project_title' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'implementing_lgu' => 'nullable|string|max:255',
            'date_of_turnover' => 'nullable|date',
            'date_of_assessment' => 'nullable|date',
            'respondent_group' => 'nullable|string|max:255',

            // MSC
            'msc_title' => 'nullable|string|max:255',
            'msc_situation_before' => 'nullable|string',
            'msc_situation_now' => 'nullable|string',
            'msc_why_significant' => 'nullable|string',
            'msc_category' => 'nullable|string',

            // Impact Ratings & Findings
            'travel_time_savings_current' => 'nullable|string|max:255',
            'travel_time_savings_previous' => 'nullable|string|max:255',
            'travel_time_savings_rating' => 'nullable|integer|between:1,5',
            'asset_value_findings' => 'nullable|string',
            'asset_value_rating' => 'nullable|integer|between:1,5',
            'new_economic_activities_findings' => 'nullable|string',
            'new_economic_activities_rating' => 'nullable|integer|between:1,5',
            'agricultural_output_findings' => 'nullable|string',
            'agricultural_output_rating' => 'nullable|integer|between:1,5',

            'access_to_services_findings' => 'nullable|string',
            'access_to_services_rating' => 'nullable|integer|between:1,5',
            'health_outcomes_findings' => 'nullable|string',
            'health_outcomes_rating' => 'nullable|integer|between:1,5',
            'safety_security_findings' => 'nullable|string',
            'safety_security_rating' => 'nullable|integer|between:1,5',
            'community_pride_findings' => 'nullable|string',
            'community_pride_rating' => 'nullable|integer|between:1,5',

            'unexpected_positive' => 'nullable|string',
            'unexpected_negative' => 'nullable|string',

            // Sustainability
            'manager_maintainer' => 'nullable|string|max:255',
            'organized_user_group' => 'nullable|string',
            'organization_recognized' => 'nullable|string',
            'has_om_fund' => 'nullable|string',
            'source_of_funds_user_fees' => 'nullable|boolean',
            'source_of_funds_user_fees_rate' => 'nullable|string|max:50',
            'source_of_funds_barangay' => 'nullable|boolean',
            'source_of_funds_municipal' => 'nullable|boolean',
            'source_of_funds_other' => 'nullable|boolean',
            'source_of_funds_other_desc' => 'nullable|string|max:255',
            'available_funds' => 'nullable|numeric|min:0',
            'functional_status' => 'nullable|string',
            'defect_structural_cracks' => 'nullable|boolean',
            'defect_drainage_leakage' => 'nullable|boolean',
            'defect_electrical_plumbing' => 'nullable|boolean',
            'defect_vandalism_wear' => 'nullable|boolean',
            'defect_other' => 'nullable|boolean',
            'defect_other_desc' => 'nullable|string|max:255',
        ]);

        $checkboxes = [
            'source_of_funds_user_fees', 'source_of_funds_barangay', 'source_of_funds_municipal', 'source_of_funds_other',
            'defect_structural_cracks', 'defect_drainage_leakage', 'defect_electrical_plumbing', 'defect_vandalism_wear', 'defect_other'
        ];
        foreach ($checkboxes as $cb) {
            $validated[$cb] = $request->has($cb);
        }

        if ($request->input('submit_btn') === 'submit') {
            $validated['status'] = 'submitted';
        }

        $assessment->update($validated);

        return redirect()->route('reports.one-time.pisat')
            ->with('success', 'PISAT Assessment successfully updated.');
    }

    public function validateAssessment(Request $request, PisatAssessment $assessment)
    {
        $user = Auth::user();
        if (!$user->isDilgUser()) {
            abort(403, 'Unauthorized. Only DILG users can validate reports.');
        }

        $validated = $request->validate([
            'validation_action' => 'required|in:approve,return',
            'remarks' => 'nullable|string',

            // Section V Fields
            'impact_classification' => 'required_if:validation_action,approve|string|nullable',
            'key_findings' => 'required_if:validation_action,approve|string|nullable',
            'proposed_actions' => 'required_if:validation_action,approve|string|nullable',
            'prepared_by' => 'required_if:validation_action,approve|string|nullable',
            'position' => 'required_if:validation_action,approve|string|nullable',
            'date_prepared' => 'required_if:validation_action,approve|date|nullable',
        ]);

        if ($validated['validation_action'] === 'approve') {
            $assessment->update([
                'status' => 'approved',
                'impact_classification' => $validated['impact_classification'],
                'key_findings' => $validated['key_findings'],
                'proposed_actions' => $validated['proposed_actions'],
                'prepared_by' => $validated['prepared_by'],
                'position' => $validated['position'],
                'date_prepared' => $validated['date_prepared'],
                'remarks' => $validated['remarks'] ?? null,
            ]);
            $msg = 'PISAT Assessment approved successfully.';
        } else {
            $assessment->update([
                'status' => 'returned',
                'remarks' => $validated['remarks'],
            ]);
            $msg = 'PISAT Assessment has been returned to LGU for corrections.';
        }

        return redirect()->route('reports.one-time.pisat')
            ->with('success', $msg);
    }
}

