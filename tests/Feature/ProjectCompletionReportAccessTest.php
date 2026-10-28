<?php

namespace Tests\Feature;

use App\Models\LgsfProjectCompletionReport;
use App\Models\LgsfProjectCompletionReportFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
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

    public function test_standalone_lgsf_project_completion_page_redirects_to_project_documents(): void
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

        $response->assertRedirect(route('initial-project-documents.show', ['projectCode' => $projectCode]));
    }

    public function test_project_documents_screen_includes_lgsf_completion_uploads_for_eligible_projects(): void
    {
        $projectCode = 'FALGU-2024-TEST-1';
        $this->createSampleProject($projectCode, 'Mountain Province', 'Tadian');

        $user = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Mountain Province',
            'office' => 'Bontoc',
            'status' => 'active',
            'access' => 'crud:*',
        ]);

        $response = $this->actingAs($user)
            ->get(route('initial-project-documents.show', ['projectCode' => $projectCode]));

        $response->assertOk()
            ->assertSee('LGSF Project Completion Documents')
            ->assertSee('Project Completion Report (using prescribed format)')
            ->assertDontSee('Project Completion Reports')
            ->assertSee('from_project_documents=1');
    }

    public function test_lgsf_upload_from_project_documents_returns_to_the_project_documents_screen(): void
    {
        $projectCode = 'FALGU-2024-TEST-1';
        $this->createSampleProject($projectCode, 'Mountain Province', 'Tadian');

        $user = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Mountain Province',
            'office' => 'Bontoc',
            'status' => 'active',
            'access' => 'crud:*',
        ]);
        Storage::fake('public');

        $response = $this->actingAs($user)->post(
            route('reports.one-time.project-completion-reports.falgu-gef-sbdp.save', [
                'projectCode' => $projectCode,
                'from_project_documents' => '1',
            ]),
            [
                'project_completion_report_path' => UploadedFile::fake()->create('completion-report.pdf', 100, 'application/pdf'),
            ]
        );

        $response->assertRedirect(route('initial-project-documents.show', ['projectCode' => $projectCode]));
        $this->assertDatabaseHas('lgsf_project_completion_report_files', [
            'project_code' => $projectCode,
            'document_type' => 'project_completion_report_path',
            'uploaded_by' => $user->idno,
        ]);
    }

    public function test_old_standalone_lgsf_url_redirects_for_users_outside_project_province(): void
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

        $response->assertRedirect(route('initial-project-documents.show', ['projectCode' => $projectCode]));
    }

    public function test_standalone_lgsf_project_completion_list_redirects_to_project_documents(): void
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

        $response = $this->actingAs($roUser)
            ->get('/reports/one-time/project-completion-reports/falgu-gef-sbdp');

        $response->assertRedirect(route('initial-project-documents.index'));
    }

    public function test_initial_project_documents_list_only_includes_projects_from_funding_year_2021(): void
    {
        $this->createSampleProject('FALGU-2020-TEST-1');
        $this->createSampleProject('FALGU-2021-TEST-1');

        DB::table('subay_project_profiles')
            ->where('project_code', 'FALGU-2020-TEST-1')
            ->update(['funding_year' => '2020', 'project_title' => 'FY2020 project']);
        DB::table('subay_project_profiles')
            ->where('project_code', 'FALGU-2021-TEST-1')
            ->update(['funding_year' => '2021', 'project_title' => 'FY2021 project']);

        $user = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Mountain Province',
            'office' => 'Bontoc',
            'status' => 'active',
            'access' => 'crud:*',
        ]);

        $this->actingAs($user)
            ->get(route('initial-project-documents.index'))
            ->assertOk()
            ->assertSee('FY2021 project')
            ->assertDontSee('FY2020 project');
    }

    public function test_standalone_sglgif_project_completion_pages_redirect_to_project_documents(): void
    {
        $projectCode = 'SGLGIF-2024-TEST-1';
        $this->createSampleProject($projectCode, 'Mountain Province', 'Tadian');

        $user = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Mountain Province',
            'office' => 'Bontoc',
            'status' => 'active',
            'access' => 'crud:*',
        ]);

        $this->actingAs($user)
            ->get('/reports/one-time/project-completion-reports/sglgif')
            ->assertRedirect(route('initial-project-documents.index'));

        $this->get("/reports/one-time/project-completion-reports/sglgif/{$projectCode}")
            ->assertRedirect(route('initial-project-documents.show', ['projectCode' => $projectCode]));
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
