<?php

namespace Tests\Feature;

use App\Models\LgsfProjectCompletionReport;
use App\Models\LgsfProjectCompletionReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectCompletionReportAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createSampleProject(string $code = 'FALGU-2024-TEST-1', string $province = 'Mountain Province', string $city = 'Tadian'): void
    {
        DB::table('subay_project_profiles')->insert([
            'project_code' => $code,
            'project_title' => 'Sample Completion Project',
            'province' => $province,
            'city_municipality' => $city,
            'region' => 'CAR',
            'funding_year' => '2024',
            'program' => 'FALGU',
            'status' => 'Completed',
        ]);
    }

    public function test_provincial_user_can_access_project_completion_report_with_returned_documents(): void
    {
        $projectCode = 'FALGU-2024-TEST-1';
        $this->createSampleProject($projectCode, 'Mountain Province', 'Tadian');

        $mpUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Mountain Province',
            'office' => 'Bontoc',
            'status' => 'active',
            'access' => 'reports.one-time.project-completion-reports.falgu-gef-sbdp',
        ]);

        // Create a returned document
        LgsfProjectCompletionReportFile::create([
            'project_code' => $projectCode,
            'document_type' => 'project_completion_report_path',
            'file_path' => 'reports/test_returned.pdf',
            'status' => 'returned',
            'approval_remarks' => 'Please revise section 2',
            'uploaded_by' => $mpUser->idno,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($mpUser)
            ->get("/reports/one-time/project-completion-reports/falgu-gef-sbdp/{$projectCode}");

        $response->assertStatus(200);
        $response->assertSee('pre-impl-delete-form-project_completion_report_path');
    }

    public function test_provincial_user_cannot_access_project_completion_report_from_other_province(): void
    {
        $projectCode = 'FALGU-2024-TEST-1';
        $this->createSampleProject($projectCode, 'Mountain Province', 'Tadian');

        $abraUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Abra',
            'office' => 'Bangued',
            'status' => 'active',
            'access' => 'reports.one-time.project-completion-reports.falgu-gef-sbdp',
        ]);

        $response = $this->actingAs($abraUser)
            ->get("/reports/one-time/project-completion-reports/falgu-gef-sbdp/{$projectCode}");

        $response->assertStatus(404);
    }

    public function test_regional_user_can_access_project_completion_report_without_errors(): void
    {
        $projectCode = 'FALGU-2024-TEST-1';
        $this->createSampleProject($projectCode, 'Mountain Province', 'Tadian');

        $roUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
            'access' => 'reports.one-time.project-completion-reports.falgu-gef-sbdp',
        ]);

        // Create a returned document
        LgsfProjectCompletionReportFile::create([
            'project_code' => $projectCode,
            'document_type' => 'project_completion_report_path',
            'file_path' => 'reports/test_returned.pdf',
            'status' => 'returned',
            'approval_remarks' => 'Please revise section 2',
            'uploaded_by' => 83,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($roUser)
            ->get("/reports/one-time/project-completion-reports/falgu-gef-sbdp/{$projectCode}");

        $response->assertStatus(200);
    }

    public function test_provincial_user_can_delete_returned_completion_document(): void
    {
        $projectCode = 'FALGU-2024-TEST-1';
        $this->createSampleProject($projectCode, 'Mountain Province', 'Tadian');

        $mpUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Mountain Province',
            'office' => 'Bontoc',
            'status' => 'active',
            'access' => 'reports.one-time.project-completion-reports.falgu-gef-sbdp',
        ]);

        $fileRecord = LgsfProjectCompletionReportFile::create([
            'project_code' => $projectCode,
            'document_type' => 'project_completion_report_path',
            'file_path' => 'reports/test_to_delete.pdf',
            'status' => 'returned',
            'approval_remarks' => 'Returned for testing',
            'uploaded_by' => $mpUser->idno,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($mpUser)
            ->delete("/reports/one-time/project-completion-reports/falgu-gef-sbdp/{$projectCode}/document/project_completion_report_path");

        $response->assertStatus(302);
        $this->assertDatabaseMissing('lgsf_project_completion_report_files', [
            'id' => $fileRecord->id,
        ]);
    }
}

