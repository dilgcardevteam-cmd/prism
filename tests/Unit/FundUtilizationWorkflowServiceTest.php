<?php

namespace Tests\Unit;

use App\Models\ApprovalLog;
use App\Models\FURMovUpload;
use App\Models\FundUtilizationApprovalWorkflow;
use App\Models\FundUtilizationReport;
use App\Models\User;
use App\Services\FundUtilizationWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class FundUtilizationWorkflowServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*' => Http::response('<html>Wednesday, 01 July 2026 10:15:30 AM</html>', 200),
        ]);

        $this->createWorkflowTestTables();
    }

    public function test_lgu_submission_routes_from_provincial_to_regional_and_updates_record(): void
    {
        $service = app(FundUtilizationWorkflowService::class);

        $uploader = $this->createUser([
            'agency' => 'LGU',
            'role' => User::ROLE_LGU,
            'region' => 'CAR',
            'province' => 'Abra',
            'office' => 'Municipality of Bangued',
        ]);
        $provincial = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'CAR',
            'province' => 'Abra',
            'office' => 'Provincial Office',
        ]);
        $regional = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_REGIONAL,
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
        ]);

        $report = $this->createReport('LGU-001', 'Abra', 'Municipality of Bangued');
        $record = $this->createMovRecord($report->project_code, 'Q1', $uploader);

        $workflow = $service->submitOrResubmit($report, 'Q1', 'mov', $record, $uploader);
        $this->assertSame('Pending Level 1 Approval', $workflow->status);
        $this->assertSame(1, $workflow->current_approval_level);
        $this->assertSame($provincial->idno, $workflow->current_approver_id);
        $this->assertSame('pending', $record->fresh()->status);

        $workflow = $service->approve($report, 'Q1', 'mov', $record->fresh(), $provincial);
        $record->refresh();

        $this->assertSame('Pending Level 2 Approval', $workflow->status);
        $this->assertSame(2, $workflow->current_approval_level);
        $this->assertSame($regional->idno, $workflow->current_approver_id);
        $this->assertSame('pending', $record->status);
        $this->assertSame($provincial->idno, $record->approved_by_dilg_po);
        $this->assertNotNull($record->approved_at_dilg_po);
        $this->assertNull($record->approved_by_dilg_ro);

        $workflow = $service->approve($report, 'Q1', 'mov', $record->fresh(), $regional);
        $record->refresh();

        $this->assertSame('Approved', $workflow->status);
        $this->assertNull($workflow->current_approver_id);
        $this->assertSame('approved', $record->status);
        $this->assertSame($regional->idno, $record->approved_by_dilg_ro);
        $this->assertSame($regional->idno, $record->approved_by);
        $this->assertNotNull($record->approved_at_dilg_ro);
        $this->assertNotNull($record->approved_at);

        $this->assertSame(
            ['Submitted', 'Forwarded', 'Approved'],
            ApprovalLog::query()->orderBy('id')->pluck('action')->all()
        );
    }

    public function test_provincial_uploader_skips_level_one_and_goes_directly_to_regional(): void
    {
        $service = app(FundUtilizationWorkflowService::class);

        $uploader = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'CAR',
            'province' => 'Abra',
            'office' => 'Provincial Office',
        ]);
        $regional = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_REGIONAL,
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
        ]);

        $report = $this->createReport('DILG-001', 'Abra', 'DILG Abra');
        $record = $this->createMovRecord($report->project_code, 'Q2', $uploader);

        $workflow = $service->submitOrResubmit($report, 'Q2', 'mov', $record, $uploader);

        $this->assertSame('Pending Level 2 Approval', $workflow->status);
        $this->assertSame(2, $workflow->current_approval_level);
        $this->assertSame($regional->idno, $workflow->current_approver_id);

        $workflow = $service->approve($report, 'Q2', 'mov', $record->fresh(), $regional);
        $record->refresh();

        $this->assertSame('Approved', $workflow->status);
        $this->assertSame('approved', $record->status);
        $this->assertSame($regional->idno, $record->approved_by_dilg_ro);
    }

    public function test_any_provincial_officer_role_can_validate_or_return_level_one(): void
    {
        $service = app(FundUtilizationWorkflowService::class);

        $uploader = $this->createUser([
            'agency' => 'LGU',
            'role' => User::ROLE_LGU,
            'region' => 'CAR',
            'province' => 'Abra',
            'office' => 'Municipality of Bangued',
        ]);
        $assignedProvincial = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'CAR',
            'province' => 'Abra',
            'office' => 'Provincial Office',
        ]);
        $otherProvincial = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'CAR',
            'province' => 'Abra',
            'office' => 'Provincial Office - Alternate',
        ]);
        $outsideProvincial = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'CAR',
            'province' => 'Benguet',
            'office' => 'Provincial Office - Outside',
        ]);

        $report = $this->createReport('LGU-PO-001', 'Abra', 'Municipality of Bangued');
        $record = $this->createMovRecord($report->project_code, 'Q1', $uploader);

        $workflow = $service->submitOrResubmit($report, 'Q1', 'mov', $record, $uploader);

        $this->assertSame($assignedProvincial->idno, $workflow->current_approver_id);
        $this->assertTrue(Gate::forUser($assignedProvincial)->allows('fund-utilization.validateWorkflow', $workflow));
        $this->assertTrue(Gate::forUser($otherProvincial)->allows('fund-utilization.validateWorkflow', $workflow));
        $this->assertTrue(Gate::forUser($outsideProvincial)->allows('fund-utilization.validateWorkflow', $workflow));

        $returnedWorkflow = $service->returnForRevision(
            $report,
            'Q1',
            'mov',
            $record->fresh(),
            $otherProvincial,
            'Please complete the attachment set.'
        );

        $this->assertSame('Returned by Provincial Officer', $returnedWorkflow->status);
        $this->assertNull($returnedWorkflow->current_approver_id);

        $record->refresh();
        $this->assertSame('returned', $record->status);
        $this->assertNull($record->approved_at_dilg_po);
        $this->assertNull($record->approved_at_dilg_ro);
    }

    public function test_any_regional_officer_role_can_validate_level_two(): void
    {
        $service = app(FundUtilizationWorkflowService::class);

        $uploader = $this->createUser([
            'agency' => 'LGU',
            'role' => User::ROLE_LGU,
            'region' => 'CAR',
            'province' => 'Abra',
        ]);
        $provincial = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'CAR',
            'province' => 'Abra',
        ]);
        $assignedRegional = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_REGIONAL,
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
        ]);
        $otherRegional = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_REGIONAL,
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office - Alternate',
        ]);
        $outsideRegional = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_REGIONAL,
            'region' => 'Region I',
            'province' => 'Regional Office',
            'office' => 'Regional Office - Outside',
        ]);

        $report = $this->createReport('LGU-002', 'Abra', 'Municipality of Bangued');
        $record = $this->createMovRecord($report->project_code, 'Q1', $uploader);

        $service->submitOrResubmit($report, 'Q1', 'mov', $record, $uploader);
        $workflow = $service->approve($report, 'Q1', 'mov', $record->fresh(), $provincial)->fresh('uploader');

        $this->assertSame($assignedRegional->idno, $workflow->current_approver_id);
        $this->assertTrue(Gate::forUser($assignedRegional)->allows('fund-utilization.validateWorkflow', $workflow));
        $this->assertTrue(Gate::forUser($otherRegional)->allows('fund-utilization.validateWorkflow', $workflow));
        $this->assertTrue(Gate::forUser($outsideRegional)->allows('fund-utilization.validateWorkflow', $workflow));

        $updatedWorkflow = $service->approve($report, 'Q1', 'mov', $record->fresh(), $otherRegional);
        $this->assertSame('Approved', $updatedWorkflow->status);
    }

    public function test_regional_return_for_lgu_submission_requires_provincial_return_to_lgu(): void
    {
        $service = app(FundUtilizationWorkflowService::class);

        $uploader = $this->createUser([
            'agency' => 'LGU',
            'role' => User::ROLE_LGU,
            'region' => 'CAR',
            'province' => 'Abra',
            'office' => 'Municipality of Bangued',
        ]);
        $provincial = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'CAR',
            'province' => 'Abra',
            'office' => 'Provincial Office',
        ]);
        $regional = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_REGIONAL,
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
        ]);

        $report = $this->createReport('LGU-RO-001', 'Abra', 'Municipality of Bangued');
        $record = $this->createMovRecord($report->project_code, 'Q1', $uploader);

        $service->submitOrResubmit($report, 'Q1', 'mov', $record, $uploader);
        $service->approve($report, 'Q1', 'mov', $record->fresh(), $provincial);
        $workflow = $service->returnForRevision($report, 'Q1', 'mov', $record->fresh(), $regional, 'Please revise.');

        $this->assertSame('Returned by Regional Officer', $workflow->status);
        $this->assertSame(1, $workflow->current_approval_level);
        $this->assertTrue($service->canActorReturn($workflow->fresh(), $provincial));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This returned submission must be sent back to the LGU user for revision.');

        $service->approve($report, 'Q1', 'mov', $record->fresh(), $provincial);
    }

    public function test_uploader_cannot_approve_their_own_submission(): void
    {
        $user = $this->createUser([
            'agency' => 'LGU',
            'role' => User::ROLE_LGU,
            'region' => 'CAR',
            'province' => 'Abra',
        ]);

        $workflow = FundUtilizationApprovalWorkflow::query()->create([
            'project_code' => 'SELF-001',
            'quarter' => 'Q1',
            'document_type' => 'mov',
            'uploader_id' => $user->idno,
            'uploader_role' => User::ROLE_LGU,
            'current_approval_level' => 1,
            'current_approver_id' => $user->idno,
            'current_approver_role' => User::ROLE_PROVINCIAL,
            'status' => 'Pending Level 1 Approval',
            'revision_number' => 1,
        ]);

        $service = app(FundUtilizationWorkflowService::class);

        $this->assertFalse($service->canActorValidate($workflow, $user));
    }

    public function test_approved_submission_cannot_be_approved_again(): void
    {
        $service = app(FundUtilizationWorkflowService::class);

        $uploader = $this->createUser([
            'agency' => 'LGU',
            'role' => User::ROLE_LGU,
            'region' => 'CAR',
            'province' => 'Abra',
        ]);
        $provincial = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'CAR',
            'province' => 'Abra',
        ]);
        $regional = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_REGIONAL,
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
        ]);

        $report = $this->createReport('LGU-003', 'Abra', 'Municipality of Bangued');
        $record = $this->createMovRecord($report->project_code, 'Q1', $uploader);

        $service->submitOrResubmit($report, 'Q1', 'mov', $record, $uploader);
        $service->approve($report, 'Q1', 'mov', $record->fresh(), $provincial);
        $service->approve($report, 'Q1', 'mov', $record->fresh(), $regional);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This submission is already fully approved and cannot be modified.');

        $service->approve($report, 'Q1', 'mov', $record->fresh(), $regional);
    }

    private function createWorkflowTestTables(): void
    {
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('fund_utilization_approval_workflows');
        Schema::dropIfExists('tbfur_mov_uploads');
        Schema::dropIfExists('tbfur');
        Schema::dropIfExists('tbusers');

        Schema::create('tbusers', function (Blueprint $table) {
            $table->unsignedBigInteger('idno')->primary();
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('agency')->nullable();
            $table->string('position')->nullable();
            $table->string('region')->nullable();
            $table->string('province')->nullable();
            $table->string('office')->nullable();
            $table->string('emailaddress')->nullable();
            $table->string('mobileno')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->string('status')->nullable();
            $table->text('access')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tbfur', function (Blueprint $table) {
            $table->string('project_code')->primary();
            $table->string('province')->nullable();
            $table->string('implementing_unit')->nullable();
            $table->string('barangay')->nullable();
            $table->string('project_title')->nullable();
            $table->string('fund_source')->nullable();
            $table->integer('funding_year')->nullable();
            $table->timestamps();
        });

        Schema::create('tbfur_mov_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('project_code');
            $table->string('quarter', 2);
            $table->string('mov_file_path')->nullable();
            $table->string('status')->nullable();
            $table->text('approval_remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('approved_at_dilg_po')->nullable();
            $table->timestamp('approved_at_dilg_ro')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_po')->nullable();
            $table->unsignedBigInteger('approved_by_dilg_ro')->nullable();
            $table->text('user_remarks')->nullable();
            $table->unsignedBigInteger('encoder_id')->nullable();
            $table->timestamp('mov_uploaded_at')->nullable();
            $table->unsignedBigInteger('mov_encoder_id')->nullable();
            $table->timestamps();
        });

        Schema::create('fund_utilization_approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('project_code');
            $table->string('quarter', 2);
            $table->string('document_type', 80);
            $table->unsignedBigInteger('uploader_id')->nullable();
            $table->string('uploader_role', 80)->nullable();
            $table->unsignedSmallInteger('current_approval_level')->nullable();
            $table->unsignedSmallInteger('last_approved_level')->default(0);
            $table->unsignedSmallInteger('returned_from_level')->nullable();
            $table->unsignedInteger('revision_number')->default(1);
            $table->unsignedBigInteger('current_approver_id')->nullable();
            $table->string('current_approver_role', 80)->nullable();
            $table->string('status', 120)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['project_code', 'quarter', 'document_type'], 'fur_workflows_unique_submission');
        });

        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->string('project_code');
            $table->string('quarter', 2);
            $table->string('document_type', 80);
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedBigInteger('uploader_id')->nullable();
            $table->unsignedSmallInteger('approval_level')->nullable();
            $table->string('action', 40);
            $table->text('remarks')->nullable();
            $table->string('previous_status', 120)->nullable();
            $table->string('new_status', 120)->nullable();
            $table->unsignedBigInteger('returned_to_id')->nullable();
            $table->unsignedBigInteger('forwarded_to_id')->nullable();
            $table->unsignedInteger('revision_number')->default(1);
            $table->timestamp('created_at')->nullable();
        });
    }

    private function createUser(array $attributes): User
    {
        static $id = 1000;
        $id++;

        return User::query()->create(array_merge([
            'idno' => $id,
            'fname' => 'Test',
            'lname' => 'User',
            'username' => 'user' . $id,
            'status' => 'active',
            'password' => 'secret',
        ], $attributes));
    }

    private function createReport(string $projectCode, string $province, string $implementingUnit): FundUtilizationReport
    {
        return FundUtilizationReport::create([
            'project_code' => $projectCode,
            'province' => $province,
            'implementing_unit' => $implementingUnit,
            'project_title' => 'Test Project',
            'fund_source' => 'SBDP',
            'funding_year' => 2026,
        ]);
    }

    private function createMovRecord(string $projectCode, string $quarter, User $uploader): FURMovUpload
    {
        return FURMovUpload::create([
            'project_code' => $projectCode,
            'quarter' => $quarter,
            'mov_file_path' => 'fur/mov/' . $projectCode . '/document.pdf',
            'status' => 'pending',
            'encoder_id' => $uploader->idno,
            'mov_encoder_id' => $uploader->idno,
        ]);
    }
}
