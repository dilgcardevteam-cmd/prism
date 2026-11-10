<?php

namespace Tests\Feature;

use App\Models\LpmcDocument;
use App\Models\LpmcDocumentFile;
use App\Models\User;
use App\Modules\ReportorialRequirements\Models\LpmcDocumentFileWorkflowLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LpmcPerFileApprovedReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_regional_return_changes_only_selected_file_and_records_its_audit_history(): void
    {
        $regionalUser = $this->createRegionalUser();
        $document = $this->createApprovedDocument($this->createLguUser());
        $selectedFile = $this->createFile($document, 'lpmc/first.pdf');
        $siblingFile = $this->createFile($document, 'lpmc/sibling.pdf');

        $this->actingAs($regionalUser)
            ->post(route('local-project-monitoring-committee.validate-file', [
                'lpmc' => $document->office,
                'fileId' => $selectedFile->id,
            ]), [
                'action' => 'return',
                'remarks' => 'Please correct the date on this file.',
            ])
            ->assertRedirect();

        $document->refresh();
        $selectedFile->refresh();
        $siblingFile->refresh();

        $this->assertSame('approved', $document->status);
        $this->assertSame('returned', $selectedFile->workflow_status);
        $this->assertSame('approved', $siblingFile->effective_workflow_status);
        $this->assertNull($siblingFile->workflow_status);
        $this->assertSame('approved', $selectedFile->workflow_previous_status);
        $this->assertSame('Please correct the date on this file.', $selectedFile->workflow_remarks);
        $this->assertSame($regionalUser->idno, $selectedFile->workflow_actor_id);
        $this->assertNotNull($selectedFile->workflow_updated_at);

        $log = LpmcDocumentFileWorkflowLog::query()
            ->where('lpmc_document_file_id', $selectedFile->id)
            ->firstOrFail();
        $this->assertSame('returned', $log->action);
        $this->assertSame('approved', $log->previous_status);
        $this->assertSame('returned', $log->resulting_status);
        $this->assertSame('Please correct the date on this file.', $log->remarks);
        $this->assertSame($regionalUser->idno, $log->actor_id);

        $this->actingAs($regionalUser)
            ->get(route('local-project-monitoring-committee.edit', ['lpmc' => $document->office]))
            ->assertOk()
            ->assertSee('Please correct the date on this file.')
            ->assertSee('Mixed File Statuses')
            ->assertSee('File status: Returned')
            ->assertSee('File status: Approved')
            ->assertSee('background-color: #d97706; color: #ffffff;', false)
            ->assertSee('openLpmcFileHistoryModal(')
            ->assertSee('id="lpmcFileHistoryModal"', false)
            ->assertSeeInOrder(['View', 'History']);
    }

    public function test_non_regional_user_cannot_return_a_file_and_return_requires_remarks(): void
    {
        $document = $this->createApprovedDocument($this->createLguUser());
        $file = $this->createFile($document, 'lpmc/approved.pdf');
        $url = route('local-project-monitoring-committee.validate-file', [
            'lpmc' => $document->office,
            'fileId' => $file->id,
        ]);

        $this->actingAs($this->createProvincialUser())
            ->post($url, ['action' => 'return', 'remarks' => 'Must be rejected.'])
            ->assertForbidden();

        $this->actingAs($this->createRegionalUser())
            ->from(route('local-project-monitoring-committee.edit', ['lpmc' => $document->office]))
            ->post($url, ['action' => 'return', 'remarks' => ''])
            ->assertSessionHasErrors('remarks');

        $this->assertSame('approved', $file->fresh()->effective_workflow_status);
        $this->assertDatabaseCount('tblpmc_document_file_workflow_logs', 0);
    }

    public function test_report_badge_is_red_when_all_lpmc_files_are_returned(): void
    {
        $regionalUser = $this->createRegionalUser();
        $document = $this->createApprovedDocument($this->createLguUser());
        $file = $this->createFile($document, 'lpmc/only-file.pdf');

        $this->actingAs($regionalUser)
            ->post(route('local-project-monitoring-committee.validate-file', [
                'lpmc' => $document->office,
                'fileId' => $file->id,
            ]), [
                'action' => 'return',
                'remarks' => 'Please update this file.',
            ])
            ->assertRedirect();

        $this->get(route('local-project-monitoring-committee.edit', ['lpmc' => $document->office]))
            ->assertOk()
            ->assertSee('background-color: #dc2626; color: #ffffff;', false);
    }

    public function test_originating_submitter_can_replace_and_resubmit_only_the_returned_file(): void
    {
        $submitter = $this->createLguUser();
        $regionalUser = $this->createRegionalUser();
        $document = $this->createApprovedDocument($submitter);
        $selectedFile = $this->createFile($document, 'lpmc/old.pdf');
        $siblingFile = $this->createFile($document, 'lpmc/keep.pdf');

        $this->actingAs($regionalUser)
            ->post(route('local-project-monitoring-committee.validate-file', [
                'lpmc' => $document->office,
                'fileId' => $selectedFile->id,
            ]), [
                'action' => 'return',
                'remarks' => 'Replace this file.',
            ])
            ->assertRedirect();

        $oldPath = $selectedFile->fresh()->file_path;
        $this->actingAs($submitter)
            ->post(route('local-project-monitoring-committee.replace-file', [
                'lpmc' => $document->office,
                'fileId' => $selectedFile->id,
            ]), [
                'replacement_document' => UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $document->refresh();
        $selectedFile->refresh();
        $siblingFile->refresh();

        $this->assertSame('approved', $document->status);
        $this->assertSame('pending_ro', $selectedFile->workflow_status);
        $this->assertNotSame($oldPath, $selectedFile->file_path);
        $this->assertSame('approved', $siblingFile->effective_workflow_status);
        $this->assertNull($document->file_path);

        $resubmissionLog = LpmcDocumentFileWorkflowLog::query()
            ->where('lpmc_document_file_id', $selectedFile->id)
            ->where('action', 'resubmitted')
            ->firstOrFail();
        $this->assertSame('returned', $resubmissionLog->previous_status);
        $this->assertSame('pending_ro', $resubmissionLog->resulting_status);
        $this->assertSame($oldPath, $resubmissionLog->previous_file_path);

        $this->actingAs($regionalUser)
            ->post(route('local-project-monitoring-committee.validate-file', [
                'lpmc' => $document->office,
                'fileId' => $selectedFile->id,
            ]), ['action' => 'approve'])
            ->assertRedirect();

        $this->assertSame('approved', $selectedFile->fresh()->workflow_status);
        $this->assertSame('approved', $document->fresh()->status);
        $this->assertSame('approved', $siblingFile->fresh()->effective_workflow_status);
    }

    public function test_legacy_file_with_null_workflow_status_inherits_parent_approval(): void
    {
        $document = $this->createApprovedDocument($this->createLguUser());
        $file = $this->createFile($document, 'lpmc/legacy.pdf');

        $this->assertNull($file->workflow_status);
        $this->assertSame('approved', $file->effective_workflow_status);
    }

    private function createApprovedDocument(User $uploader): LpmcDocument
    {
        return LpmcDocument::query()->create([
            'office' => 'La Trinidad',
            'province' => 'Benguet',
            'doc_type' => 'mep',
            'year' => 2025,
            'status' => 'approved',
            'file_path' => null,
            'uploaded_by' => $uploader->idno,
            'uploaded_at' => now()->subDay(),
            'approved_at_dilg_ro' => now(),
            'approved_by_dilg_ro' => $this->createRegionalUser()->idno,
        ]);
    }

    private function createFile(LpmcDocument $document, string $path): LpmcDocumentFile
    {
        return LpmcDocumentFile::query()->create([
            'lpmc_document_id' => $document->id,
            'file_path' => $path,
            'original_filename' => basename($path),
            'uploaded_by' => $document->uploaded_by,
            'uploaded_at' => now()->subDay(),
        ]);
    }

    private function createLguUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
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

    private function createProvincialUser(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'Benguet Provincial Office',
            'status' => 'active',
        ]);
    }
}
