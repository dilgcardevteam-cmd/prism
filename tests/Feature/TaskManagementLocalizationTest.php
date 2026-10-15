<?php

namespace Tests\Feature;

use App\Models\LpmcDocument;
use App\Models\User;
use App\Services\LguReportorialWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_c_mlgoo_sees_localized_approvals_pool_for_their_municipality_only(): void
    {
        $mlgooLaTrinidad = User::factory()->create([
            'role' => User::ROLE_MLGOO,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        // 1. Document in assigned municipality awaiting C/MLGOO
        $docLaTrinidad = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'la_trinidad_mep.pdf',
            'file_path' => 'reports/la_trinidad_mep.pdf',
            'uploaded_at' => now(),
        ]);

        // 2. Document in different municipality in same province awaiting C/MLGOO
        $docItogon = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'Itogon',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'itogon_mep.pdf',
            'file_path' => 'reports/itogon_mep.pdf',
            'uploaded_at' => now(),
        ]);

        // 3. Document in another province awaiting C/MLGOO
        $docBangued = LpmcDocument::create([
            'province' => 'Abra',
            'office' => 'Bangued',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'bangued_mep.pdf',
            'file_path' => 'reports/bangued_mep.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($mlgooLaTrinidad)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Header & localization badges
        $response->assertSee('Level: C/MLGOO Validator');
        $response->assertSee('La Trinidad, Benguet');

        // Pool title localized to C/MLGOO jurisdiction
        $response->assertSee('C/MLGOO Approvals Pool — La Trinidad, Benguet');

        // Can see assigned municipality document
        $response->assertSee('La Trinidad');
        $response->assertSee('/local-project-monitoring-committee/La%20Trinidad/edit');

        // Cannot see other municipalities
        $response->assertDontSee('itogon_mep.pdf');
        $response->assertDontSee('/local-project-monitoring-committee/Itogon/edit');
        $response->assertDontSee('bangued_mep.pdf');
        $response->assertDontSee('/local-project-monitoring-committee/Bangued/edit');
    }

    public function test_provincial_user_sees_localized_approvals_pool_for_their_province_only(): void
    {
        $provincialBenguet = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'Provincial Office',
            'status' => 'active',
        ]);

        // 1. Benguet document awaiting Provincial validation
        $docBenguet = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'approved_at_c_mlgoo' => now(),
            'file_name' => 'benguet_doc.pdf',
            'file_path' => 'reports/benguet_doc.pdf',
            'uploaded_at' => now(),
        ]);

        // 2. Abra document awaiting Provincial validation
        $docAbra = LpmcDocument::create([
            'province' => 'Abra',
            'office' => 'Bangued',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'approved_at_c_mlgoo' => now(),
            'file_name' => 'abra_doc.pdf',
            'file_path' => 'reports/abra_doc.pdf',
            'uploaded_at' => now(),
        ]);

        // 3. Benguet document awaiting Regional validation (already approved by PO, NOT for provincial level)
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'Itogon',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_RO,
            'approved_at_c_mlgoo' => now(),
            'approved_at_dilg_po' => now(),
            'file_name' => 'itogon_ro.pdf',
            'file_path' => 'reports/itogon_ro.pdf',
            'uploaded_at' => now(),
        ]);

        // 4. Benguet document still awaiting C/MLGOO validation (NOT yet for provincial level)
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'Tuba',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'tuba_mlgoo.pdf',
            'file_path' => 'reports/tuba_mlgoo.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($provincialBenguet)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Header & localization badges
        $response->assertSee('Level: Provincial Validator');
        $response->assertSee('Province of Benguet');

        // Pool title localized to Province
        $response->assertSee('Provincial Approvals Pool — Benguet');
        $response->assertSee('Unassigned Tickets Pool — Benguet');
        $response->assertSee('Returned Documents Awaiting Action — Benguet');

        // Can see Benguet document awaiting Provincial validation
        $response->assertSee('/local-project-monitoring-committee/La%20Trinidad/edit');

        // Cannot see Abra document
        $response->assertDontSee('/local-project-monitoring-committee/Bangued/edit');

        // Cannot see documents whose validation is not for provincial level (pending_ro or pending_mlgoo)
        $response->assertDontSee('/local-project-monitoring-committee/Itogon/edit');
        $response->assertDontSee('/local-project-monitoring-committee/Tuba/edit');
    }

    public function test_regional_user_sees_regional_pool_and_only_pending_ro_documents(): void
    {
        $regionalUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        // 1. Document awaiting Regional validation
        $docPendingRo = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_RO,
            'approved_at_c_mlgoo' => now(),
            'approved_at_dilg_po' => now(),
            'file_name' => 'ro_ready.pdf',
            'file_path' => 'reports/ro_ready.pdf',
            'uploaded_at' => now(),
        ]);

        // 2. Document still awaiting C/MLGOO validation
        $docPendingMlgoo = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'Itogon',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'mlgoo_stage.pdf',
            'file_path' => 'reports/mlgoo_stage.pdf',
            'uploaded_at' => now(),
        ]);

        // 3. Document still awaiting Provincial validation
        $docPendingPo = LpmcDocument::create([
            'province' => 'Abra',
            'office' => 'Bangued',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'approved_at_c_mlgoo' => now(),
            'file_name' => 'po_stage.pdf',
            'file_path' => 'reports/po_stage.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($regionalUser)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Header & localization badges
        $response->assertSee('Level: Regional Validator');
        $response->assertSee('Centralized Regional Approvals Pool — CAR');
        $response->assertSee('Unassigned Tickets Pool — Regional (CAR)');

        // Only pending_ro is visible in Regional pool
        $response->assertSee('/local-project-monitoring-committee/La%20Trinidad/edit');
        $response->assertDontSee('/local-project-monitoring-committee/Itogon/edit');
        $response->assertDontSee('/local-project-monitoring-committee/Bangued/edit');
    }

    public function test_lgu_user_does_not_see_approvals_pool_and_sees_only_own_returned_documents(): void
    {
        $lguUser = User::factory()->create([
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        // 1. Returned document for own LGU
        $docReturnedOwn = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'file_name' => 'returned_la_trinidad.pdf',
            'file_path' => 'reports/returned_la_trinidad.pdf',
            'approval_remarks' => 'Please provide correct signature',
            'uploaded_at' => now(),
        ]);

        // 2. Returned document for another LGU
        $docReturnedOther = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'Itogon',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'file_name' => 'returned_itogon.pdf',
            'file_path' => 'reports/returned_itogon.pdf',
            'approval_remarks' => 'Missing annexes',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($lguUser)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Header & localization badges
        $response->assertSee('Level: LGU Submitter');
        $response->assertSee('La Trinidad, Benguet');

        // Cannot see Approvals Pool or Tickets Pool
        $response->assertDontSee('Approvals Pool');
        $response->assertDontSee('Unassigned Tickets Pool');

        // Sees localized Returned Documents header
        $response->assertSee('Returned Documents Awaiting Action — La Trinidad');

        // Sees own returned document
        $response->assertSee('Please provide correct signature');

        // Does NOT see other LGU returned document
        $response->assertDontSee('Missing annexes');
    }

    public function test_regional_user_does_not_see_returned_documents_awaiting_action_section(): void
    {
        $regionalUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        // A returned document exists in the system
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'file_name' => 'returned_test.pdf',
            'file_path' => 'reports/returned_test.pdf',
            'approval_remarks' => 'Revision needed',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($regionalUser)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Regional users are validators only and must NEVER see Returned Documents Awaiting Action
        $response->assertDontSee('Returned Documents Awaiting Action');
        $response->assertDontSee('Revision needed');
    }

    public function test_provincial_validator_does_not_see_municipal_lgu_returned_documents(): void
    {
        $provincialUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'Provincial Office',
            'status' => 'active',
        ]);

        $lguUser = User::factory()->create([
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        // Municipal LGU document returned for revision
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'file_name' => 'municipal_returned.pdf',
            'file_path' => 'reports/municipal_returned.pdf',
            'approval_remarks' => 'LGU must fix municipal annexes',
            'uploaded_by' => $lguUser->idno,
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($provincialUser)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Provincial validator does not have this document in their returned tasks (it belongs to the LGU to fix)
        $response->assertDontSee('LGU must fix municipal annexes');
        $response->assertSee('No Returned Documents');
    }

    public function test_returned_documents_displays_validator_who_returned_document_not_the_uploader(): void
    {
        $lguUser = User::factory()->create([
            'fname' => 'Maria',
            'lname' => 'Uploader',
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        $provincialValidator = User::factory()->create([
            'fname' => 'Juan',
            'lname' => 'ProvincialValidator',
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'Provincial Office',
            'status' => 'active',
        ]);

        $doc = LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'file_name' => 'lgu_mep.pdf',
            'file_path' => 'reports/lgu_mep.pdf',
            'approval_remarks' => 'Please attach signed certification',
            'uploaded_by' => $lguUser->idno,
            'approved_by_dilg_po' => $provincialValidator->idno,
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($lguUser)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Under "Returned By", should show the validator who returned it (Juan ProvincialValidator), NOT Maria Uploader
        $response->assertSee('Juan ProvincialValidator');
        $response->assertSee('Provincial Validator');
    }

    public function test_superadmin_does_not_see_returned_documents_awaiting_action_section(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => null,
            'status' => 'active',
        ]);

        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_RETURNED,
            'file_name' => 'returned_test.pdf',
            'file_path' => 'reports/returned_test.pdf',
            'approval_remarks' => 'Revision needed',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Superadmin is central oversight and must NEVER see Returned Documents Awaiting Action
        $response->assertDontSee('Returned Documents Awaiting Action');
        $response->assertDontSee('Revision needed');
    }

    public function test_superadmin_with_regional_office_sees_regional_approval_pool_and_not_lower_levels(): void
    {
        $superadmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => null,
            'status' => 'active',
        ]);

        // 1. Municipal document awaiting C/MLGOO
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'Itogon',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'mlgoo_stage_doc.pdf',
            'file_path' => 'reports/mlgoo_stage_doc.pdf',
            'uploaded_at' => now(),
        ]);

        // 2. Provincial document awaiting PO
        LpmcDocument::create([
            'province' => 'Abra',
            'office' => 'Bangued',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'approved_at_c_mlgoo' => now(),
            'file_name' => 'po_stage_doc.pdf',
            'file_path' => 'reports/po_stage_doc.pdf',
            'uploaded_at' => now(),
        ]);

        // 3. Regional document awaiting RO
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_RO,
            'approved_at_c_mlgoo' => now(),
            'approved_at_dilg_po' => now(),
            'file_name' => 'ro_stage_doc.pdf',
            'file_path' => 'reports/ro_stage_doc.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Sees Regional pool based on regional office / level
        $response->assertSee('Centralized Regional Approvals Pool — CAR');
        $response->assertDontSee('All Pending System Approvals');
        $response->assertDontSee('Provincial Approvals Pool');
        $response->assertDontSee('C/MLGOO Approvals Pool');

        // Only sees pending_ro document in pool
        $response->assertSee('/local-project-monitoring-committee/La%20Trinidad/edit');
        $response->assertDontSee('/local-project-monitoring-committee/Itogon/edit');
        $response->assertDontSee('/local-project-monitoring-committee/Bangued/edit');
    }

    public function test_superadmin_with_provincial_office_sees_provincial_approval_pool_only(): void
    {
        $provincialAdmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Benguet',
            'office' => 'Provincial Office',
            'status' => 'active',
        ]);

        // 1. Benguet PO document
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'approved_at_c_mlgoo' => now(),
            'file_name' => 'benguet_po_doc.pdf',
            'file_path' => 'reports/benguet_po_doc.pdf',
            'uploaded_at' => now(),
        ]);

        // 2. Abra PO document
        LpmcDocument::create([
            'province' => 'Abra',
            'office' => 'Bangued',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_PO,
            'approved_at_c_mlgoo' => now(),
            'file_name' => 'abra_po_doc.pdf',
            'file_path' => 'reports/abra_po_doc.pdf',
            'uploaded_at' => now(),
        ]);

        // 3. Benguet RO document
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'Itogon',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_RO,
            'approved_at_c_mlgoo' => now(),
            'approved_at_dilg_po' => now(),
            'file_name' => 'benguet_ro_doc.pdf',
            'file_path' => 'reports/benguet_ro_doc.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($provincialAdmin)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Shows Provincial Approvals Pool — Benguet
        $response->assertSee('Provincial Approvals Pool — Benguet');
        $response->assertDontSee('Centralized Regional Approvals Pool');
        $response->assertDontSee('C/MLGOO Approvals Pool');
        $response->assertDontSee('All Pending System Approvals');

        // Can see Benguet provincial document, not Abra or RO
        $response->assertSee('/local-project-monitoring-committee/La%20Trinidad/edit');
        $response->assertDontSee('/local-project-monitoring-committee/Bangued/edit');
        $response->assertDontSee('/local-project-monitoring-committee/Itogon/edit');
    }

    public function test_superadmin_with_municipal_office_sees_cmlgoo_approval_pool_only(): void
    {
        $cmlgooAdmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'status' => 'active',
        ]);

        // 1. La Trinidad MLGOO document
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'La Trinidad',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'la_trinidad_cmlgoo.pdf',
            'file_path' => 'reports/la_trinidad_cmlgoo.pdf',
            'uploaded_at' => now(),
        ]);

        // 2. Itogon MLGOO document
        LpmcDocument::create([
            'province' => 'Benguet',
            'office' => 'Itogon',
            'year' => 2026,
            'doc_type' => 'mep',
            'status' => LguReportorialWorkflowService::STATUS_PENDING_MLGOO,
            'file_name' => 'itogon_cmlgoo.pdf',
            'file_path' => 'reports/itogon_cmlgoo.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($cmlgooAdmin)->get(route('task-management.index'));
        $response->assertStatus(200);

        // Shows C/MLGOO Approvals Pool — La Trinidad, Benguet
        $response->assertSee('C/MLGOO Approvals Pool — La Trinidad, Benguet');
        $response->assertDontSee('Provincial Approvals Pool');
        $response->assertDontSee('Centralized Regional Approvals Pool');
        $response->assertDontSee('All Pending System Approvals');

        // Can see assigned municipality, not others
        $response->assertSee('/local-project-monitoring-committee/La%20Trinidad/edit');
        $response->assertDontSee('/local-project-monitoring-committee/Itogon/edit');
    }
}

