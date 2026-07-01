<?php

namespace Tests\Feature;

use App\Models\FURMovUpload;
use App\Models\FundUtilizationReport;
use App\Models\User;
use App\Services\FundUtilizationWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FundUtilizationWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createWorkflowTestTables();
    }

    public function test_regional_user_becomes_current_approver_after_provincial_approval(): void
    {
        $service = app(FundUtilizationWorkflowService::class);

        $lgu = $this->createUser([
            'agency' => 'LGU',
            'role' => User::ROLE_LGU,
            'region' => 'NCR',
            'province' => 'Metro Manila',
            'office' => 'City Hall',
        ]);
        $provincial = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'NCR',
            'province' => 'Metro Manila',
            'office' => 'Provincial Office',
        ]);
        $regional = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_REGIONAL,
            'region' => 'NCR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
        ]);

        $report = FundUtilizationReport::create([
            'project_code' => 'FEATURE-001',
            'province' => 'Metro Manila',
            'implementing_unit' => 'City Hall',
            'project_title' => 'Feature Test Project',
            'fund_source' => 'SBDP',
            'funding_year' => 2026,
        ]);

        $record = FURMovUpload::create([
            'project_code' => $report->project_code,
            'quarter' => 'Q1',
            'mov_file_path' => 'fur/mov/FEATURE-001/document.pdf',
            'status' => 'pending',
            'encoder_id' => $lgu->idno,
            'mov_encoder_id' => $lgu->idno,
        ]);

        $service->submitOrResubmit($report, 'Q1', 'mov', $record, $lgu);
        $workflow = $service->approve($report, 'Q1', 'mov', $record->fresh(), $provincial);

        $this->assertSame('Pending Level 2 Approval', $workflow->status);
        $this->assertSame($regional->idno, $workflow->current_approver_id);
        $this->assertTrue($service->canActorValidate($workflow->fresh(), $regional));
    }

    public function test_provincial_uploader_starts_directly_at_regional_approval(): void
    {
        $service = app(FundUtilizationWorkflowService::class);

        $provincial = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'NCR',
            'province' => 'Metro Manila',
            'office' => 'Provincial Office',
        ]);
        $regional = $this->createUser([
            'agency' => 'DILG',
            'role' => User::ROLE_REGIONAL,
            'region' => 'NCR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
        ]);

        $report = FundUtilizationReport::create([
            'project_code' => 'FEATURE-002',
            'province' => 'Metro Manila',
            'implementing_unit' => 'DILG Metro Manila',
            'project_title' => 'Feature Test Project 2',
            'fund_source' => 'CMGP',
            'funding_year' => 2026,
        ]);

        $record = FURMovUpload::create([
            'project_code' => $report->project_code,
            'quarter' => 'Q2',
            'mov_file_path' => 'fur/mov/FEATURE-002/document.pdf',
            'status' => 'pending',
            'encoder_id' => $provincial->idno,
            'mov_encoder_id' => $provincial->idno,
        ]);

        $workflow = $service->submitOrResubmit($report, 'Q2', 'mov', $record, $provincial);

        $this->assertSame('Pending Level 2 Approval', $workflow->status);
        $this->assertSame(2, $workflow->current_approval_level);
        $this->assertSame($regional->idno, $workflow->current_approver_id);
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
        static $id = 2000;
        $id++;

        return User::query()->create(array_merge([
            'idno' => $id,
            'fname' => 'Feature',
            'lname' => 'User',
            'username' => 'feature' . $id,
            'status' => 'active',
            'password' => 'secret',
        ], $attributes));
    }
}
