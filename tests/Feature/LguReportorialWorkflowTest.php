<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\LpmcDocument;
use App\Models\LpmcDocumentFile;
use App\Models\RoadMaintenanceStatusDocument;
use App\Models\User;
use App\Services\LguReportorialWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LguReportorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createLguUser(string $province = 'Benguet', string $office = 'La Trinidad'): User
    {
        return User::factory()->create([
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => $province,
            'office' => $office,
            'status' => 'active',
        ]);
    }

    private function createMlgooUser(string $province = 'Benguet', string $office = 'La Trinidad'): User
    {
        return User::factory()->create([
            'role' => User::ROLE_MLGOO,
            'agency' => 'DILG',
            'province' => $province,
            'office' => $office,
            'status' => 'active',
        ]);
    }

    private function createProvincialUser(string $province = 'Benguet'): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => $province,
            'office' => "{$province} Provincial Office",
            'status' => 'active',
        ]);
    }

    private function createRegionalUser(): User
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

    /**
     * Test 1: LGU User submission enters the C/MLGOO validation stage (Workflow A).
     */
    public function test_criterion_1_lgu_user_submission_enters_c_mlgoo_validation_stage(): void
    {
        $lguUser = $this->createLguUser();
        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->actingAs($lguUser)->post('/local-project-monitoring-committee/La Trinidad/upload', [
            'document' => [$file],
            'doc_type' => 'mep',
            'year' => 2025,
        ]);

        $response->assertRedirect();
        $doc = LpmcDocument::where('office', 'La Trinidad')->where('doc_type', 'mep')->first();
        $this->assertNotNull($doc);
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_MLGOO, $doc->status);
        $this->assertEquals($lguUser->idno, $doc->uploaded_by);
        $this->assertNull($doc->approved_at_c_mlgoo);
        $this->assertNull($doc->approved_at_dilg_po);
        $this->assertNull($doc->approved_at_dilg_ro);
    }

    /**
     * Test 2: C/MLGOO approval advances the report to Provincial validation.
     */
    public function test_criterion_2_c_mlgoo_approval_advances_to_provincial_validation(): void
    {
        $lguUser = $this->createLguUser();
        $mlgoo = $this->createMlgooUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'uploaded_by' => $lguUser->idno,
            'uploaded_at' => now(),
            'file_path' => 'lpmc/test.pdf',
        ]);

        $response = $this->actingAs($mlgoo)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_PO, $doc->status);
        $this->assertNotNull($doc->approved_at_c_mlgoo);
        $this->assertEquals($mlgoo->idno, $doc->approved_by_c_mlgoo);
        $this->assertNull($doc->approved_at_dilg_po);
    }

    /**
     * Test 3: Provincial approval advances the report to Regional validation.
     */
    public function test_criterion_3_provincial_approval_advances_to_regional_validation(): void
    {
        $lguUser = $this->createLguUser();
        $mlgoo = $this->createMlgooUser();
        $poUser = $this->createProvincialUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'uploaded_by' => $lguUser->idno,
            'uploaded_at' => now(),
            'approved_at_c_mlgoo' => now(),
            'approved_by_c_mlgoo' => $mlgoo->idno,
            'file_path' => 'lpmc/test.pdf',
        ]);

        $response = $this->actingAs($poUser)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_RO, $doc->status);
        $this->assertNotNull($doc->approved_at_dilg_po);
        $this->assertEquals($poUser->idno, $doc->approved_by_dilg_po);
        $this->assertNull($doc->approved_at_dilg_ro);
    }

    /**
     * Test 4: Regional approval marks the report Fully Approved.
     */
    public function test_criterion_4_regional_approval_marks_report_fully_approved(): void
    {
        $lguUser = $this->createLguUser();
        $roUser = $this->createRegionalUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_RO,
            'uploaded_by' => $lguUser->idno,
            'uploaded_at' => now(),
            'approved_at_c_mlgoo' => now(),
            'approved_at_dilg_po' => now(),
            'file_path' => 'lpmc/test.pdf',
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

    /**
     * Test 5: C/MLGOO User submission starts at Provincial validation (Workflow B).
     */
    public function test_criterion_5_c_mlgoo_user_submission_starts_at_provincial_validation(): void
    {
        $mlgoo = $this->createMlgooUser();
        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->actingAs($mlgoo)->post('/local-project-monitoring-committee/La Trinidad/upload', [
            'document' => [$file],
            'doc_type' => 'mep',
            'year' => 2025,
        ]);

        $response->assertRedirect();
        $doc = LpmcDocument::where('office', 'La Trinidad')->where('doc_type', 'mep')->first();
        $this->assertNotNull($doc);
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_PO, $doc->status);
        $this->assertEquals($mlgoo->idno, $doc->uploaded_by);
        $this->assertNotNull($doc->approved_at_c_mlgoo);
        $this->assertEquals($mlgoo->idno, $doc->approved_by_c_mlgoo);
    }

    /**
     * Test 6: Provincial approval of a C/MLGOO-originated submission advances to Regional validation.
     */
    public function test_criterion_6_provincial_approval_of_mlgoo_submission_advances_to_regional(): void
    {
        $mlgoo = $this->createMlgooUser();
        $poUser = $this->createProvincialUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'uploaded_by' => $mlgoo->idno,
            'uploaded_at' => now(),
            'approved_at_c_mlgoo' => now(),
            'approved_by_c_mlgoo' => $mlgoo->idno,
            'file_path' => 'lpmc/test.pdf',
        ]);

        $response = $this->actingAs($poUser)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);

        $response->assertRedirect();
        $doc->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_RO, $doc->status);
        $this->assertEquals($poUser->idno, $doc->approved_by_dilg_po);
    }

    /**
     * Test 7: Provincial User submission starts at Regional validation (Workflow C).
     */
    public function test_criterion_7_provincial_user_submission_starts_at_regional_validation(): void
    {
        $poUser = $this->createProvincialUser();
        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->actingAs($poUser)->post('/local-project-monitoring-committee/La Trinidad/upload', [
            'document' => [$file],
            'doc_type' => 'mep',
            'year' => 2025,
        ]);

        $response->assertRedirect();
        $doc = LpmcDocument::where('office', 'La Trinidad')->where('doc_type', 'mep')->first();
        $this->assertNotNull($doc);
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_RO, $doc->status);
        $this->assertEquals($poUser->idno, $doc->uploaded_by);
        $this->assertNotNull($doc->approved_at_dilg_po);
        $this->assertEquals($poUser->idno, $doc->approved_by_dilg_po);
    }

    /**
     * Test 8: Regional approval of a Provincial-originated submission marks the report Fully Approved.
     */
    public function test_criterion_8_regional_approval_of_provincial_submission_marks_fully_approved(): void
    {
        $poUser = $this->createProvincialUser();
        $roUser = $this->createRegionalUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_RO,
            'uploaded_by' => $poUser->idno,
            'uploaded_at' => now(),
            'approved_at_dilg_po' => now(),
            'approved_by_dilg_po' => $poUser->idno,
            'file_path' => 'lpmc/test.pdf',
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

    /**
     * Test 9: Each authorized validator can return a report with a required reason.
     */
    public function test_criterion_9_each_validator_can_return_with_required_reason(): void
    {
        $lguUser = $this->createLguUser();
        $mlgoo = $this->createMlgooUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'uploaded_by' => $lguUser->idno,
            'uploaded_at' => now(),
            'file_path' => 'lpmc/test.pdf',
        ]);

        // Attempt return without remarks should fail validation
        $responseNoRemarks = $this->actingAs($mlgoo)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'return',
            'remarks' => '',
        ]);
        $responseNoRemarks->assertSessionHasErrors('remarks');

        // Return with valid remarks succeeds
        $responseValid = $this->actingAs($mlgoo)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'return',
            'remarks' => 'Please attach the signed signature page.',
        ]);
        $responseValid->assertRedirect();

        $doc->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_RETURNED, $doc->status);
        $this->assertSame('Please attach the signed signature page.', $doc->approval_remarks);
    }

    /**
     * Test 10: Returned reports are editable only by the authorized originating submitter.
     */
    public function test_criterion_10_returned_reports_editable_only_by_originating_submitter(): void
    {
        $originalLguUser = $this->createLguUser();
        $otherLguUser = User::factory()->create([
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'uploaded_by' => $originalLguUser->idno,
            'uploaded_at' => now(),
            'file_path' => 'lpmc/test.pdf',
            'approval_remarks' => 'Signature missing',
        ]);

        // Another user attempting to resubmit is forbidden (403)
        $file = UploadedFile::fake()->create('fixed.pdf', 100, 'application/pdf');
        $forbiddenResponse = $this->actingAs($otherLguUser)->post('/local-project-monitoring-committee/La Trinidad/upload', [
            'document' => [$file],
            'doc_type' => 'mep',
            'year' => 2025,
        ]);
        $forbiddenResponse->assertStatus(403);

        // Original submitter can edit and resubmit
        $allowedResponse = $this->actingAs($originalLguUser)->post('/local-project-monitoring-committee/La Trinidad/upload', [
            'document' => [$file],
            'doc_type' => 'mep',
            'year' => 2025,
        ]);
        $allowedResponse->assertRedirect();
    }

    public function test_originating_submitter_can_delete_returned_document_files_before_replacement(): void
    {
        $lguUser = $this->createLguUser();
        $oldFilePath = 'lpmc/la_trinidad/returned.pdf';
        Storage::disk('public')->put($oldFilePath, 'returned document');

        $document = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'uploaded_by' => $lguUser->idno,
            'uploaded_at' => now(),
            'file_path' => $oldFilePath,
            'original_filename' => 'returned.pdf',
            'approval_remarks' => 'Please correct the report.',
        ]);
        LpmcDocumentFile::create([
            'lpmc_document_id' => $document->id,
            'file_path' => $oldFilePath,
            'original_filename' => 'returned.pdf',
            'uploaded_by' => $lguUser->idno,
            'uploaded_at' => now(),
        ]);

        $deleteResponse = $this->actingAs($lguUser)
            ->delete("/local-project-monitoring-committee/La Trinidad/document/{$document->id}");

        $deleteResponse->assertRedirect();
        $document->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_RETURNED, $document->status);
        $this->assertNull($document->file_path);
        $this->assertNull($document->original_filename);
        $this->assertDatabaseMissing('tblpmc_document_files', ['lpmc_document_id' => $document->id]);
        Storage::disk('public')->assertMissing($oldFilePath);

        $replacementResponse = $this->actingAs($lguUser)->post('/local-project-monitoring-committee/La Trinidad/upload', [
            'document' => [UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf')],
            'doc_type' => 'mep',
            'year' => 2025,
        ]);

        $replacementResponse->assertRedirect();
        $document->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_MLGOO, $document->status);
        $this->assertNotNull($document->file_path);
        $this->assertDatabaseHas('tblpmc_document_files', [
            'lpmc_document_id' => $document->id,
            'original_filename' => 'replacement.pdf',
        ]);
    }

    public function test_only_owner_can_delete_returned_document_files(): void
    {
        $owner = $this->createLguUser();
        $otherUser = User::factory()->create([
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);
        $filePath = 'lpmc/la_trinidad/returned.pdf';
        Storage::disk('public')->put($filePath, 'returned document');

        $document = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'uploaded_by' => $owner->idno,
            'uploaded_at' => now(),
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete("/local-project-monitoring-committee/La Trinidad/document/{$document->id}");

        $response->assertForbidden();
        $this->assertSame($filePath, $document->fresh()->file_path);
        Storage::disk('public')->assertExists($filePath);
    }

    public function test_owner_cannot_delete_a_document_that_has_not_been_returned(): void
    {
        $lguUser = $this->createLguUser();
        $filePath = 'lpmc/la_trinidad/pending.pdf';
        Storage::disk('public')->put($filePath, 'pending document');

        $document = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'uploaded_by' => $lguUser->idno,
            'uploaded_at' => now(),
            'file_path' => $filePath,
        ]);

        $response = $this->actingAs($lguUser)
            ->delete("/local-project-monitoring-committee/La Trinidad/document/{$document->id}");

        $response->assertForbidden();
        $this->assertSame($filePath, $document->fresh()->file_path);
        Storage::disk('public')->assertExists($filePath);
    }

    /**
     * Test 11: Resubmission restarts at the correct first validation stage for the original route.
     */
    public function test_criterion_11_resubmission_restarts_at_correct_stage_for_each_route(): void
    {
        $workflowService = app(LguReportorialWorkflowService::class);
        $lguUser = $this->createLguUser();
        $mlgoo = $this->createMlgooUser();
        $poUser = $this->createProvincialUser();

        // Route A (LGU origin): Resubmission returns to pending_mlgoo
        $docA = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'uploaded_by' => $lguUser->idno,
        ]);
        $restartStageA = $workflowService->restartStatusForResubmission($docA, $lguUser, 'La Trinidad', 'Benguet');
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_MLGOO, $restartStageA);

        // Route B (C/MLGOO origin): Resubmission returns to pending_po
        $docB = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'eo',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'uploaded_by' => $mlgoo->idno,
        ]);
        $restartStageB = $workflowService->restartStatusForResubmission($docB, $mlgoo, 'La Trinidad', 'Benguet');
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_PO, $restartStageB);

        // Route C (Provincial origin): Resubmission returns to pending_ro
        $docC = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'awfp',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'uploaded_by' => $poUser->idno,
        ]);
        $restartStageC = $workflowService->restartStatusForResubmission($docC, $poUser, 'La Trinidad', 'Benguet');
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_RO, $restartStageC);
    }

    /**
     * Test 12: Previous validation history remains intact after return and resubmission.
     */
    public function test_criterion_12_validation_history_remains_intact_across_cycles(): void
    {
        $workflowService = app(LguReportorialWorkflowService::class);
        $lguUser = $this->createLguUser();
        $mlgoo = $this->createMlgooUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'uploaded_by' => $lguUser->idno,
            'file_path' => 'lpmc/test.pdf',
        ]);

        // MLGOO returns the document
        $workflowService->processValidation($mlgoo, $doc, 'return', 'Initial return reason', 'La Trinidad', 'Benguet');
        $doc->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_RETURNED, $doc->status);

        // Submitter resubmits
        $file = UploadedFile::fake()->create('fixed.pdf', 100, 'application/pdf');
        $this->actingAs($lguUser)->post('/local-project-monitoring-committee/La Trinidad/upload', [
            'document' => [$file],
            'doc_type' => 'mep',
            'year' => 2025,
        ]);

        // Audit logs must preserve the previous return entry and the new resubmitted entry
        $history = $workflowService->getValidationHistory($doc);
        $this->assertNotEmpty($history);
        $actions = collect($history)->pluck('action')->all();
        $this->assertContains('REPORT_RETURNED', $actions);
        $this->assertContains('REPORT_RESUBMITTED', $actions);
    }

    /**
     * Test 13: Users cannot approve or return their own submissions.
     */
    public function test_criterion_13_users_cannot_approve_or_return_own_submissions(): void
    {
        $mlgoo = $this->createMlgooUser();

        // MLGOO submitted on behalf of LGU (Workflow B)
        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'uploaded_by' => $mlgoo->idno,
            'file_path' => 'lpmc/test.pdf',
        ]);

        // MLGOO attempts to validate their own submission -> 403 Forbidden
        $response = $this->actingAs($mlgoo)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test 14: Users cannot validate reports belonging to another unauthorized municipality or province.
     */
    public function test_criterion_14_cannot_validate_outside_authorized_geographic_scope(): void
    {
        $mlgooBaguio = $this->createMlgooUser('City of Baguio', 'City of Baguio');
        $lguUser = $this->createLguUser('Benguet', 'La Trinidad');

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'uploaded_by' => $lguUser->idno,
            'file_path' => 'lpmc/test.pdf',
        ]);

        // MLGOO of Baguio cannot validate La Trinidad document
        $response = $this->actingAs($mlgooBaguio)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test 15: Users cannot skip a validation stage by modifying request parameters.
     */
    public function test_criterion_15_users_cannot_skip_validation_stages(): void
    {
        $lguUser = $this->createLguUser();
        $poUser = $this->createProvincialUser();

        // Document is currently at pending_mlgoo
        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'uploaded_by' => $lguUser->idno,
            'file_path' => 'lpmc/test.pdf',
        ]);

        // Provincial user attempts to approve directly, bypassing C/MLGOO
        $response = $this->actingAs($poUser)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_RO,
        ]);
        $response->assertStatus(403);

        $doc->refresh();
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_MLGOO, $doc->status);
    }

    /**
     * Test 16: Users cannot directly set a report to Fully Approved.
     */
    public function test_criterion_16_users_cannot_directly_set_status_to_approved(): void
    {
        $lguUser = $this->createLguUser();
        $mlgoo = $this->createMlgooUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'uploaded_by' => $lguUser->idno,
            'file_path' => 'lpmc/test.pdf',
        ]);

        // Parameter injection attempting to force 'approved' status
        $this->actingAs($mlgoo)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
            'status' => 'approved',
            'target_status' => 'approved',
        ]);

        $doc->refresh();
        // C/MLGOO approval only advances to pending_po, never directly to approved
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_PO, $doc->status);
    }

    /**
     * Test 17: Duplicate or concurrent validation requests cannot create inconsistent workflow states.
     */
    public function test_criterion_17_concurrent_or_duplicate_validation_requests_prevented(): void
    {
        $workflowService = app(LguReportorialWorkflowService::class);
        $lguUser = $this->createLguUser();
        $mlgoo = $this->createMlgooUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'uploaded_by' => $lguUser->idno,
            'file_path' => 'lpmc/test.pdf',
        ]);

        // First validation succeeds
        $result = $workflowService->processValidation($mlgoo, $doc, 'approve', null, 'La Trinidad', 'Benguet');
        $this->assertTrue($result['success']);
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_PO, $doc->refresh()->status);

        // Immediate duplicate approval attempt by same MLGOO throws exception because stage has progressed
        $this->expectException(\RuntimeException::class);
        $workflowService->processValidation($mlgoo, $doc, 'approve', null, 'La Trinidad', 'Benguet');
    }

    /**
     * Test 18: Unauthorized users cannot invoke approval or return endpoints directly.
     */
    public function test_criterion_18_unauthorized_users_cannot_invoke_endpoints(): void
    {
        $lguUser = $this->createLguUser();

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'uploaded_by' => 9999,
            'file_path' => 'lpmc/test.pdf',
        ]);

        // LGU user cannot invoke approval
        $lguResponse = $this->actingAs($lguUser)->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);
        $lguResponse->assertStatus(403);

        // Guest is redirected to login
        auth()->logout();
        $guestResponse = $this->post("/local-project-monitoring-committee/La Trinidad/approve/{$doc->id}", [
            'action' => 'approve',
        ]);
        $guestResponse->assertRedirect();
    }

    /**
     * Test 19: Existing report submissions and unrelated features continue to work.
     */
    public function test_criterion_19_existing_submissions_and_views_work_properly(): void
    {
        $lguUser = $this->createLguUser();

        $response = $this->actingAs($lguUser)->get('/local-project-monitoring-committee/La Trinidad/edit');
        $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));

        // Verify road maintenance status edit view loads cleanly for provincial user without 500 error
        $abraProvincialUser = $this->createProvincialUser('Abra');
        $provincialResponse = $this->actingAs($abraProvincialUser)->get('/road-maintenance-status/Bangued/edit');
        $this->assertSame(200, $provincialResponse->getStatusCode());

        // Create a road maintenance status document with file_path and status returned
        $returnedDoc = RoadMaintenanceStatusDocument::create([
            'office' => 'Bangued',
            'province' => 'Abra',
            'doc_type' => 'road_maintenance_status',
            'year' => (int) now()->year,
            'quarter' => 'Q1',
            'file_path' => 'road_maintenance/test_q1.pdf',
            'uploaded_by' => $abraProvincialUser->idno,
            'uploaded_at' => now(),
            'status' => 'returned',
            'approved_at' => now(),
            'approved_by_dilg_ro' => 9999,
            'approval_remarks' => 'Please provide complete signatures.',
        ]);

        $returnedViewResponse = $this->actingAs($abraProvincialUser)->get('/road-maintenance-status/Bangued/edit');
        $this->assertSame(200, $returnedViewResponse->getStatusCode());
        $returnedViewResponse->assertSee('Please provide complete signatures.');
    }

    /**
     * Test 20: Reports with legacy statuses are handled safely and are not silently corrupted.
     */
    public function test_criterion_20_reports_with_legacy_statuses_handled_safely(): void
    {
        $workflowService = app(LguReportorialWorkflowService::class);

        // Legacy blank status with uploaded file maps to pending_mlgoo for municipal office
        $docMunicipalLegacy = new LpmcDocument(['status' => '', 'office' => 'La Trinidad', 'file_path' => 'lpmc/legacy_test.pdf']);
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_MLGOO, $workflowService->currentStage($docMunicipalLegacy));

        // Legacy blank status with uploaded file maps to pending_po for PLGU office
        $docPlguLegacy = new LpmcDocument(['status' => '', 'office' => 'PLGU Benguet', 'file_path' => 'lpmc/legacy_test.pdf']);
        $this->assertSame(LguReportorialWorkflowService::STATUS_PENDING_PO, $workflowService->currentStage($docPlguLegacy));

        // Legacy approved_at_dilg_ro maps to approved regardless of status column
        $docApprovedLegacy = new LpmcDocument(['status' => 'pending', 'approved_at_dilg_ro' => now(), 'office' => 'La Trinidad', 'file_path' => 'lpmc/legacy_test.pdf']);
        $this->assertSame(LguReportorialWorkflowService::STATUS_APPROVED, $workflowService->currentStage($docApprovedLegacy));
    }

    /**
     * Submission fields with no uploaded documents or file must NOT be put for C/MLGOO validation.
     */
    public function test_submission_field_with_no_uploaded_document_or_file_is_not_put_for_c_mlgoo_validation(): void
    {
        $workflowService = app(LguReportorialWorkflowService::class);
        $mlgooUser = $this->createMlgooUser();

        // 1. Null document returns pending_upload, not pending_mlgoo
        $this->assertSame('pending_upload', $workflowService->currentStage(null, 'La Trinidad'));
        $badgeNull = $workflowService->resolveStatusBadge(null, 'La Trinidad');
        $this->assertSame('Pending Upload', $badgeNull['label']);
        $this->assertNotSame('For C/MLGOO Validation', $badgeNull['label']);

        // 2. Document record with empty file_path returns pending_upload, not pending_mlgoo
        $docEmptyFile = new LpmcDocument(['office' => 'La Trinidad', 'file_path' => '']);
        $this->assertSame('pending_upload', $workflowService->currentStage($docEmptyFile, 'La Trinidad'));
        $badgeEmpty = $workflowService->resolveStatusBadge($docEmptyFile, 'La Trinidad');
        $this->assertSame('Pending Upload', $badgeEmpty['label']);
        $this->assertNotSame('For C/MLGOO Validation', $badgeEmpty['label']);

        // 3. Validation is disallowed when there is no uploaded file
        $checkNull = $workflowService->canUserValidate($mlgooUser, null, 'La Trinidad');
        $this->assertFalse($checkNull['allowed']);

        $checkEmpty = $workflowService->canUserValidate($mlgooUser, $docEmptyFile, 'La Trinidad');
        $this->assertFalse($checkEmpty['allowed']);

        // 4. Edit view with empty submission fields shows "Pending Upload" and does NOT show "For C/MLGOO Validation"
        $lguUser = $this->createLguUser();
        $response = $this->actingAs($lguUser)->get('/road-maintenance-status/La Trinidad/edit');
        $response->assertStatus(200);
        $response->assertSee('Pending Upload');
        $response->assertDontSee('For C/MLGOO Validation');
    }
}
