<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AnnualRpmesForm4Upload;
use App\Models\QuarterlyRpmesForm2Upload;
use App\Models\QuarterlyRpmesForm5Upload;
use App\Models\QuarterlyRpmesForm6Upload;
use App\Models\User;
use App\Modules\DilgDeliverables\Models\MonitoringEvaluationMonthlyDocument;
use App\Services\LguReportorialWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Psr\Log\NullLogger;
use Tests\TestCase;

class RegionalApprovedReportReturnsTest extends TestCase
{
    use RefreshDatabase;

    private function regionalUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);
    }

    public function test_regional_return_of_rpmes_document_is_audited_and_quarter_scoped(): void
    {
        $regionalUser = $this->regionalUser();
        $target = null;
        $reports = [
            [QuarterlyRpmesForm2Upload::class, 'Q1'],
            [QuarterlyRpmesForm5Upload::class, 'Q1'],
            [QuarterlyRpmesForm6Upload::class, 'Q1'],
            [AnnualRpmesForm4Upload::class, 'Annual'],
        ];

        foreach ($reports as $index => [$modelClass, $quarter]) {
            $document = $modelClass::create([
                'project_code' => 'RPMES-RETURN-' . $index,
                'quarter' => $quarter,
                'file_path' => 'rpmes/target-' . $index . '.pdf',
                'status' => LguReportorialWorkflowService::STATUS_APPROVED,
                'uploaded_by' => 999001 + $index,
                'approved_at' => now()->subHour(),
                'approved_at_dilg_ro' => now()->subHour(),
            ]);

            app(LguReportorialWorkflowService::class)->processValidation(
                $regionalUser,
                $document,
                'return',
                'Correct the project results figures.',
                'La Trinidad',
                'Benguet'
            );

            $document->refresh();
            $this->assertSame(LguReportorialWorkflowService::STATUS_RETURNED, $document->status);
            $target ??= $document;
        }

        $otherQuarter = QuarterlyRpmesForm2Upload::create([
            'project_code' => 'RPMES-RETURN-0',
            'quarter' => 'Q2',
            'file_path' => 'rpmes/other.pdf',
            'status' => LguReportorialWorkflowService::STATUS_APPROVED,
            'uploaded_by' => 999010,
            'approved_at' => now()->subHour(),
            'approved_at_dilg_ro' => now()->subHour(),
        ]);

        $target->refresh();
        $otherQuarter->refresh();
        $audit = ActivityLog::where('action', 'REPORT_RETURNED')
            ->where('properties->report_table', $target->getTable())
            ->where('properties->report_id', $target->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(LguReportorialWorkflowService::STATUS_RETURNED, $target->status);
        $this->assertSame(LguReportorialWorkflowService::STATUS_APPROVED, $otherQuarter->status);
        $this->assertSame($target->getTable(), $audit->properties['report_table']);
        $this->assertSame($target->id, $audit->properties['report_id']);
        $this->assertSame(LguReportorialWorkflowService::STATUS_APPROVED, $audit->properties['previous_status']);
        $this->assertSame(LguReportorialWorkflowService::STATUS_RETURNED, $audit->properties['resulting_status']);
        $this->assertSame('Correct the project results figures.', $audit->properties['remarks']);
        $this->assertSame($regionalUser->idno, $audit->user_id);
        $this->assertNotNull($audit->created_at);
    }

    public function test_regional_return_of_monthly_me_document_does_not_change_another_report(): void
    {
        $regionalUser = $this->regionalUser();
        $target = MonitoringEvaluationMonthlyDocument::create([
            'office' => 'Abra',
            'province' => 'Abra',
            'doc_type' => 'monitoring_evaluation_monthly_lfp',
            'year' => 2026,
            'month' => 'JAN',
            'file_path' => 'monitoring-evaluation/target.pdf',
            'uploaded_by' => 999001,
            'status' => LguReportorialWorkflowService::STATUS_APPROVED,
            'approved_at' => now()->subHour(),
            'approved_at_dilg_ro' => now()->subHour(),
        ]);
        $otherReport = MonitoringEvaluationMonthlyDocument::create([
            'office' => 'Abra',
            'province' => 'Abra',
            'doc_type' => 'monitoring_evaluation_monthly_rlip_lime',
            'year' => 2026,
            'month' => 'JAN',
            'file_path' => 'monitoring-evaluation/other.pdf',
            'uploaded_by' => 999002,
            'status' => LguReportorialWorkflowService::STATUS_APPROVED,
            'approved_at' => now()->subHour(),
            'approved_at_dilg_ro' => now()->subHour(),
        ]);

        Auth::login($regionalUser);
        Log::shouldReceive('channel')
            ->once()
            ->with('upload_timestamps')
            ->andReturn(new NullLogger());
        $response = app(\App\Modules\DilgDeliverables\Controllers\MonitoringEvaluationMonthlyReportController::class)
            ->approveDocumentLfp(
                Request::create('/', 'POST', [
                    'action' => 'return',
                    'remarks' => 'Please attach the signed M&E report.',
                ]),
                'Abra',
                $target->id
            );

        $target->refresh();
        $otherReport->refresh();
        $audit = ActivityLog::where('action', 'REPORT_RETURNED')->latest('id')->firstOrFail();

        $this->assertNotNull($response->getSession()->get('success') ?? null);
        $this->assertSame(LguReportorialWorkflowService::STATUS_RETURNED, $target->status);
        $this->assertSame(LguReportorialWorkflowService::STATUS_APPROVED, $otherReport->status);
        $this->assertSame($target->getTable(), $audit->properties['report_table']);
        $this->assertSame($target->id, $audit->properties['report_id']);
        $this->assertSame('approved', $audit->properties['previous_status']);
        $this->assertSame('returned', $audit->properties['resulting_status']);
        $this->assertSame('Please attach the signed M&E report.', $audit->properties['remarks']);
    }
}
