<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectDashboardController extends Controller
{
	public function __invoke(Request $request)
	{
		Log::warning('Fallback ProjectDashboardController::__invoke() route hit. Clear and recache routes on deployment to use routes/web.php dashboard closure.');

		return view('dashboard.index', [
			'totalProjects' => 0,
			'statusActualCounts' => collect(),
			'statusSubaybayanCounts' => collect(),
			'statusSubaybayanProjectsMap' => [],
			'statusSubaybayanLocationReport' => collect(),
			'provinceFundingYearProgramStatusReport' => collect(),
			'provinceFundingYearProgramStatusSourceRows' => collect(),
			'statusDisplayOrder' => collect(),
			'subayUploadDateLabel' => 'No SubayBAYAN upload yet',
			'fundSourceCounts' => collect(),
			'filters' => [
				'province' => [],
				'city_municipality' => [],
				'barangay' => [],
				'programs' => [],
				'funding_year' => [],
				'project_type' => [],
				'project_status' => [],
			],
			'filterOptions' => [
				'provinces' => collect(),
				'cities' => collect(),
				'barangays' => collect(),
				'programs' => collect(),
				'funding_years' => collect(),
				'project_types' => collect(),
				'project_statuses' => collect(),
			],
			'totalLgsfAllocationAmount' => 0.0,
			'totalObligationAmount' => 0.0,
			'totalDisbursementAmount' => 0.0,
			'totalBalanceAmount' => 0.0,
			'utilizationPercentage' => 0.0,
			'projectsExpectedCompletionThisMonth' => collect(),
			'expectedCompletionMonthLabel' => now()->format('F Y'),
			'projectAtRiskCounts' => [
				'Ahead' => 0,
				'No Risk' => 0,
				'On Schedule' => 0,
				'High Risk' => 0,
				'Moderate Risk' => 0,
				'Low Risk' => 0,
			],
			'projectAtRiskAgingCounts' => [
				'High Risk' => 0,
				'Low Risk' => 0,
				'No Risk' => 0,
			],
			'projectAtRiskAgingProjects' => [
				'High Risk' => collect(),
				'Low Risk' => collect(),
				'No Risk' => collect(),
			],
			'projectUpdateStatusCounts' => [
				'High Risk' => 0,
				'Low Risk' => 0,
				'No Risk' => 0,
			],
			'projectUpdateRiskProjects' => [
				'High Risk' => collect(),
				'Low Risk' => collect(),
				'No Risk' => collect(),
			],
			'projectsWithBalance' => collect(),
			'financialStatusProjects' => collect(),
			'fundSourceProjectsMap' => [],
			'carProvinceProjectCounts' => [
				'ABRA' => 0,
				'APAYAO' => 0,
				'BENGUET' => 0,
				'IFUGAO' => 0,
				'KALINGA' => 0,
				'MOUNTAIN PROVINCE' => 0,
			],
			'carProvinceProjectMaxCount' => 0,
			'activeProjectTab' => (string) $request->query('tab', 'locally-funded'),
		]);
	}
}
