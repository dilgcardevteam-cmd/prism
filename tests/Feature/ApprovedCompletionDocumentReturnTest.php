<?php

namespace Tests\Feature;

use App\Models\LgsfProjectCompletionReportFile;
use App\Models\PreImplementationDocumentFile;
use App\Models\SglgifProjectCompletionReportFile;
use App\Models\User;
use App\Services\LguReportorialWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApprovedCompletionDocumentReturnTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(string $projectCode, string $program): void
    {
        DB::table('subay_project_profiles')->insert([
            'project_code' => $projectCode,
            'project_title' => 'Approved Return Test Project',
            'province' => 'Mountain Province',
            'city_municipality' => 'Tadian',
            'region' => 'CAR',
            'funding_year' => '2024',
            'program' => $program,
            'status' => 'Completed',
        ]);
    }

    private function regionalUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
            'access' => 'crud:*',
        ]);
    }

    private function approvedFile(string $projectCode, string $documentType, int $uploaderId, string $filePath, string $table): object
    {
        return $table::create([
            'project_code' => $projectCode,
            'document_type' => $documentType,
            'file_path' => $filePath,
            'status' => 'approved',
            'uploaded_by' => $uploaderId,
            'uploaded_at' => now()->subDay(),
            'approved_by_dilg_ro' => $uploaderId,
            'approved_at_dilg_ro' => now()->subHour(),
            'approved_at' => now()->subHour(),
        ]);
    }

    public function test_regional_return_uses_selected_file_and_preserves_other_approved_files_across_all_three_workflows(): void
    {
        $regionalUser = $this->regionalUser();
        $uploader = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Mountain Province',
            'office' => 'Bontoc',
            'status' => 'active',
        ]);

        $cases = [
            [
                'project_code' => 'FALGU-2024-PRE-RETURN',
                'program' => 'FALGU',
                'document_type' => 'project_completion_report_path',
                'model' => PreImplementationDocumentFile::class,
                'route' => 'initial-project-documents.validate-file',
                'table' => 'pre_implementation_document_files',
            ],
            [
                'project_code' => 'FALGU-2024-LGSF-RETURN',
                'program' => 'FALGU',
                'document_type' => 'project_completion_report_path',
                'model' => LgsfProjectCompletionReportFile::class,
                'route' => 'reports.one-time.project-completion-reports.falgu-gef-sbdp.validate-file',
                'table' => 'lgsf_project_completion_report_files',
            ],
            [
                'project_code' => 'SGLGIF-2024-RETURN',
                'program' => 'SGLGIF',
                'document_type' => 'project_completion_report_path',
                'model' => SglgifProjectCompletionReportFile::class,
                'route' => 'reports.one-time.project-completion-reports.sglgif.validate-file',
                'table' => 'sglgif_project_completion_report_files',
            ],
        ];

        foreach ($cases as $case) {
            $this->createProject($case['project_code'], $case['program']);
            $target = $this->approvedFile(
                $case['project_code'],
                $case['document_type'],
                $uploader->idno,
                'reports/target-' . strtolower($case['program']) . '.pdf',
                $case['model']
            );
            $untouched = $this->approvedFile(
                $case['project_code'],
                $case['document_type'],
                $uploader->idno,
                'reports/untouched-' . strtolower($case['program']) . '.pdf',
                $case['model']
            );

            $response = $this->actingAs($regionalUser)->post(
                route($case['route'], ['projectCode' => $case['project_code'], 'fileId' => $target->id]),
                ['action' => 'return', 'remarks' => 'Please correct this approved file.']
            );

            $response->assertRedirect();
            $this->assertDatabaseHas($case['table'], [
                'id' => $target->id,
                'status' => 'returned',
                'approval_remarks' => 'Please correct this approved file.',
            ]);
            $this->assertDatabaseHas($case['table'], [
                'id' => $untouched->id,
                'status' => 'approved',
            ]);

            $history = app(LguReportorialWorkflowService::class)->getValidationHistory($target);
            $returnHistory = $history[array_key_last($history)];
            $this->assertSame('REPORT_RETURNED', $returnHistory['action']);
            $this->assertSame('Please correct this approved file.', $returnHistory['remarks']);
            $this->assertTrue(DB::table('activity_logs')
                ->where('action', 'REPORT_RETURNED')
                ->where('properties->report_table', $case['table'])
                ->where('properties->report_id', $target->id)
                ->exists());
        }
    }

    public function test_only_regional_users_may_return_approved_files_and_return_requires_remarks(): void
    {
        $projectCode = 'FALGU-2024-RETURN-AUTH';
        $this->createProject($projectCode, 'FALGU');
        $uploader = User::factory()->create([
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Mountain Province',
            'office' => 'Tadian',
            'status' => 'active',
        ]);
        $file = $this->approvedFile(
            $projectCode,
            'project_completion_report_path',
            $uploader->idno,
            'reports/approved-auth.pdf',
            PreImplementationDocumentFile::class
        );
        $route = route('initial-project-documents.validate-file', [
            'projectCode' => $projectCode,
            'fileId' => $file->id,
        ]);

        $provincialUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Mountain Province',
            'office' => 'Bontoc',
            'status' => 'active',
        ]);

        $this->actingAs($provincialUser)
            ->post($route, ['action' => 'return', 'remarks' => 'Unauthorized return'])
            ->assertForbidden();

        $this->actingAs($this->regionalUser())
            ->post($route, ['action' => 'return'])
            ->assertSessionHasErrors('remarks');

        $this->assertDatabaseHas('pre_implementation_document_files', [
            'id' => $file->id,
            'status' => 'approved',
        ]);
    }

    public function test_regional_approved_multi_upload_controls_target_each_file_id(): void
    {
        $projectCode = 'FALGU-2024-RETURN-MULTI';
        $this->createProject($projectCode, 'FALGU');
        $regionalUser = $this->regionalUser();
        $uploader = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Mountain Province',
            'office' => 'Bontoc',
            'status' => 'active',
        ]);
        $firstFile = $this->approvedFile($projectCode, 'photos_path', $uploader->idno, 'reports/photo-1.pdf', LgsfProjectCompletionReportFile::class);
        $secondFile = $this->approvedFile($projectCode, 'photos_path', $uploader->idno, 'reports/photo-2.pdf', LgsfProjectCompletionReportFile::class);

        $response = $this->actingAs($regionalUser)
            ->get(route('initial-project-documents.show', ['projectCode' => $projectCode]));

        $response->assertOk()
            ->assertSee(route('reports.one-time.project-completion-reports.falgu-gef-sbdp.validate-file', [
                'projectCode' => $projectCode,
                'fileId' => $firstFile->id,
                'from_project_documents' => '1',
            ]), false)
            ->assertSee(route('reports.one-time.project-completion-reports.falgu-gef-sbdp.validate-file', [
                'projectCode' => $projectCode,
                'fileId' => $secondFile->id,
                'from_project_documents' => '1',
            ]), false)
            ->assertDontSee(route('reports.one-time.project-completion-reports.falgu-gef-sbdp.validate', [
                'projectCode' => $projectCode,
                'documentType' => 'photos_path',
                'from_project_documents' => '1',
            ]), false);
    }
}
