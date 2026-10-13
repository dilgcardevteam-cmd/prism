<?php

namespace Tests\Feature;

use App\Models\LpmcDocument;
use App\Models\User;
use App\Services\LguReportorialWorkflowService;
use App\Services\RegionalApprovalPoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpmcTaskManagementValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lgu_user_can_access_own_lgu_but_forbidden_from_other_lgus(): void
    {
        $lguUser = User::factory()->create([
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        // Own LGU access allowed
        $response = $this->actingAs($lguUser)->get('/local-project-monitoring-committee/La Trinidad/edit');
        $this->assertNotEquals(403, $response->getStatusCode());
        $this->assertNotEquals(404, $response->getStatusCode());

        // Other LGU in same province forbidden
        $responseOther = $this->actingAs($lguUser)->get('/local-project-monitoring-committee/Itogon/edit');
        $responseOther->assertStatus(403);

        // LGU in another province forbidden
        $responseDiffProv = $this->actingAs($lguUser)->get('/local-project-monitoring-committee/Bontoc/edit');
        $responseDiffProv->assertStatus(403);
    }

    public function test_lgu_user_cannot_validate_or_approve_submissions(): void
    {
        $lguUser = User::factory()->create([
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2025,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'test.pdf',
            'file_path' => 'reports/test.pdf',
            'uploaded_by' => $lguUser->idno,
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($lguUser)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);

        $response->assertStatus(403);
    }

    public function test_c_mlgoo_user_can_validate_assigned_municipality_but_not_others(): void
    {
        $mlgoo = User::factory()->create([
            'role' => User::ROLE_MLGOO,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2025,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'test.pdf',
            'file_path' => 'reports/test.pdf',
            'uploaded_at' => now(),
        ]);

        // Can validate assigned municipality
        $response = $this->actingAs($mlgoo)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);
        $response->assertRedirect();

        $doc->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_PO, $doc->status);
        $this->assertNotNull($doc->approved_at_c_mlgoo);
        $this->assertEquals($mlgoo->idno, $doc->approved_by_c_mlgoo);

        // Cannot validate other municipality
        $otherDoc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'Itogon',
            'year' => 2025,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'other.pdf',
            'file_path' => 'reports/other.pdf',
            'uploaded_at' => now(),
        ]);

        $forbiddenResponse = $this->actingAs($mlgoo)->post("/local-project-monitoring-committee/Itogon/approve/{$otherDoc->id}", [
            'action' => 'approve',
        ]);
        $forbiddenResponse->assertStatus(403);
    }

    public function test_c_mlgoo_cannot_access_dilg_deliverables(): void
    {
        $mlgoo = User::factory()->create([
            'role' => User::ROLE_MLGOO,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        $response = $this->actingAs($mlgoo)->get('/reports/dilg-deliverables/monitoring-and-evaluation-reports/lfp');
        $response->assertStatus(403);
    }

    public function test_provincial_user_cannot_bypass_mlgoo_validation(): void
    {
        $poUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'Benguet Provincial Office',
            'status' => 'active',
        ]);

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2025,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'test.pdf',
            'file_path' => 'reports/test.pdf',
            'uploaded_at' => now(),
        ]);

        // Attempting to approve while still in pending_mlgoo should be rejected
        $response = $this->actingAs($poUser)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);
        $response->assertStatus(403);
    }

    public function test_provincial_user_can_validate_once_mlgoo_approved(): void
    {
        $poUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'Benguet Provincial Office',
            'status' => 'active',
        ]);

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2025,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'approved_at_c_mlgoo' => now(),
            'approved_by_c_mlgoo' => 999,
            'file_name' => 'test.pdf',
            'file_path' => 'reports/test.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($poUser)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);
        $response->assertRedirect();

        $doc->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_RO, $doc->status);
        $this->assertNotNull($doc->approved_at_dilg_po);
        $this->assertEquals($poUser->idno, $doc->approved_by_dilg_po);
    }

    public function test_regional_user_cannot_bypass_provincial_validation(): void
    {
        $roUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2025,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'approved_at_c_mlgoo' => now(),
            'file_name' => 'test.pdf',
            'file_path' => 'reports/test.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($roUser)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);
        $response->assertStatus(403);
    }

    public function test_regional_user_can_grant_final_approval_on_pending_ro(): void
    {
        $roUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2025,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_RO,
            'approved_at_c_mlgoo' => now(),
            'approved_at_dilg_po' => now(),
            'file_name' => 'test.pdf',
            'file_path' => 'reports/test.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($roUser)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);
        $response->assertRedirect();

        $doc->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_APPROVED, $doc->status);
        $this->assertNotNull($doc->approved_at_dilg_ro);
        $this->assertEquals($roUser->idno, $doc->approved_by_dilg_ro);
    }

    public function test_regional_and_superadmin_users_review_and_validate_url_resolves_without_404(): void
    {
        $roUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2025,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_RO,
            'approved_at_c_mlgoo' => now(),
            'approved_at_dilg_po' => now(),
            'file_name' => 'test.pdf',
            'file_path' => 'reports/test.pdf',
            'uploaded_at' => now(),
        ]);

        // Check task pool item URL
        $poolService = app(RegionalApprovalPoolService::class);
        $tasks = $poolService->pendingTasksForUser($roUser);
        $lpmcTask = $tasks->firstWhere('module_key', 'local-project-monitoring-committee');

        $this->assertNotNull($lpmcTask, 'LPMC pending task should be in regional pool');
        $this->assertNotEmpty($lpmcTask['url']);

        // Test Regional User access to the generated URL
        $roResponse = $this->actingAs($roUser)->get($lpmcTask['url']);
        $this->assertNotEquals(404, $roResponse->getStatusCode(), 'Review & Validate URL must not 404 for Regional User');
        $this->assertNotEquals(403, $roResponse->getStatusCode(), 'Review & Validate URL must not 403 for Regional User');

        // Test Superadmin access to the generated URL
        $adminResponse = $this->actingAs($admin)->get($lpmcTask['url']);
        $this->assertNotEquals(404, $adminResponse->getStatusCode(), 'Review & Validate URL must not 404 for Superadmin');
        $this->assertNotEquals(403, $adminResponse->getStatusCode(), 'Review & Validate URL must not 403 for Superadmin');
    }
}

