<?php

namespace Tests\Unit;

use App\Http\Controllers\FundUtilizationReportController;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;

class FundUtilizationReportControllerTest extends \Tests\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('tbusers', function ($table) {
            $table->id('idno');
            $table->string('fname')->nullable();
            $table->string('lname')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->string('agency')->nullable();
            $table->string('province')->nullable();
            $table->string('office')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        User::query()->create([
            'idno' => 1,
            'fname' => 'Test',
            'lname' => 'User',
            'username' => 'testuser',
            'password' => 'secret',
            'role' => User::ROLE_LGU,
            'agency' => 'LGU',
            'province' => 'Metro Manila',
            'office' => 'City Hall',
            'status' => 'active',
        ]);
    }

    #[Test]
    public function it_marks_returned_documents_as_lgu_level_for_display(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'resolveFundUtilizationValidatorLevelForDisplay');
        $method->setAccessible(true);

        $level = $method->invoke($controller, [
            'status' => 'returned',
            'uploader_level' => 'lgu',
            'approved_at_dilg_po' => '2026-06-01 09:00:00',
            'approved_at_dilg_ro' => null,
        ]);

        $this->assertSame('lgu', $level);
    }

    #[Test]
    public function it_resolves_current_validator_level_correctly(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'resolveFundUtilizationCurrentValidatorLevel');
        $method->setAccessible(true);

        // Scenario 1: LGU uploader without PO approval -> provincial validator
        $val1 = $method->invoke($controller, 'lgu', 'pending', null, null);
        $this->assertSame('provincial', $val1);

        // Scenario 2: LGU uploader with PO approval -> regional validator
        $val2 = $method->invoke($controller, 'lgu', 'pending', '2026-06-01 09:00:00', null);
        $this->assertSame('regional', $val2);

        // Scenario 3: Provincial uploader -> regional validator directly
        $val3 = $method->invoke($controller, 'provincial', 'pending', null, null);
        $this->assertSame('regional', $val3);
    }

    #[Test]
    public function it_resolves_returned_status_messages_in_listing(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'summarizeFundUtilizationListing');
        $method->setAccessible(true);

        $movUploadMock = (object)[
            'mov_file_path' => 'uploads/test.pdf',
            'status' => 'returned',
            'mov_uploaded_at' => '2026-06-01 09:00:00',
            'approved_at' => null,
            'mov_encoder_id' => 1,
            'approved_at_dilg_po' => null,
            'approved_at_dilg_ro' => null,
        ];

        $quarterDocuments = [
            'Q1' => [
                'mov' => $movUploadMock,
                'batch_document' => null,
                'written_notice' => null,
                'fdp' => null,
            ]
        ];

        // Scenario 1: Workflow status: Returned by Regional Officer
        $workflowMock1 = (object)[
            'status' => 'Returned by Regional Officer',
        ];
        $workflowMap1 = [
            'mov::Q1' => $workflowMock1,
        ];

        $summary1 = $method->invoke($controller, $quarterDocuments, $workflowMap1);
        $this->assertSame('Returned by DILG Regional Office', $summary1['approval_status_label']);
        $this->assertSame('Returned by DILG Regional Office', $summary1['validation_level_label']);

        // Scenario 2: Workflow status: Returned by Provincial Officer
        $workflowMock2 = (object)[
            'status' => 'Returned by Provincial Officer',
        ];
        $workflowMap2 = [
            'mov::Q1' => $workflowMock2,
        ];

        $summary2 = $method->invoke($controller, $quarterDocuments, $workflowMap2);
        $this->assertSame('Returned by DILG Provincial Office', $summary2['approval_status_label']);
        $this->assertSame('Returned by DILG Provincial Office', $summary2['validation_level_label']);
    }

    #[Test]
    public function it_prioritizes_returned_status_over_pending_validation_in_validation_summary(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'summarizeFundUtilizationValidation');
        $method->setAccessible(true);

        $movUploadMock = (object)[
            'mov_file_path' => 'uploads/test1.pdf',
            'status' => 'returned',
            'mov_uploaded_at' => '2026-06-01 09:00:00',
            'approved_at' => null,
            'mov_encoder_id' => 1,
            'approved_at_dilg_po' => null,
            'approved_at_dilg_ro' => null,
        ];

        $fdpDocumentMock = (object)[
            'fdp_file_path' => 'uploads/test2.pdf',
            'fdp_status' => 'pending',
            'fdp_uploaded_at' => '2026-06-01 10:00:00',
            'fdp_approved_at' => null,
            'fdp_encoder_id' => 1,
            'approved_at_dilg_po' => null,
            'approved_at_dilg_ro' => null,
        ];

        $quarterDocuments = [
            'Q1' => [
                'mov' => $movUploadMock,
                'batch_document' => null,
                'written_notice' => null,
                'fdp' => $fdpDocumentMock,
            ]
        ];

        $summary = $method->invoke($controller, $quarterDocuments);
        $this->assertSame('Returned', $summary['label']);
        $this->assertSame('#b91c1c', $summary['text_color']);
        $this->assertSame('#fef2f2', $summary['background_color']);
    }

    #[Test]
    public function it_resolves_quarter_colors_correctly(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'resolveFundUtilizationQuarterColor');
        $method->setAccessible(true);

        // Case 1: No uploads
        $c1 = $method->invoke($controller, null, null, null, null, 'Q1', []);
        $this->assertSame('#f3f4f6', $c1['bg']);
        $this->assertSame('#6b7280', $c1['text']);
        $this->assertSame('#d1d5db', $c1['border']);
        $this->assertSame('no uploads', $c1['tooltip']);

        // Case 2: Returned document
        $movMockReturned = (object)[
            'mov_file_path' => 'uploads/mov.pdf',
            'status' => 'returned',
            'mov_encoder_id' => 1,
            'approved_at_dilg_po' => null,
            'approved_at_dilg_ro' => null,
        ];
        $c2 = $method->invoke($controller, $movMockReturned, null, null, null, 'Q1', []);
        $this->assertSame('#fee2e2', $c2['bg']);
        $this->assertSame('#991b1b', $c2['text']);
        $this->assertSame('#fca5a5', $c2['border']);
        $this->assertSame('with upload and have returned documents', $c2['tooltip']);

        // Case 3: Pending PO validation
        $movMockPendingPo = (object)[
            'mov_file_path' => 'uploads/mov.pdf',
            'status' => 'pending',
            'mov_encoder_id' => 1, // LGU user
            'approved_at_dilg_po' => null,
            'approved_at_dilg_ro' => null,
        ];
        $c3 = $method->invoke($controller, $movMockPendingPo, null, null, null, 'Q1', []);
        $this->assertSame('#fef9c3', $c3['bg']);
        $this->assertSame('#854d0e', $c3['text']);
        $this->assertSame('#fef08a', $c3['border']);
        $this->assertSame('with upload but not 100%', $c3['tooltip']);

        // Case 4: Pending RO validation
        $movMockPendingRo = (object)[
            'mov_file_path' => 'uploads/mov.pdf',
            'status' => 'pending',
            'mov_encoder_id' => 1, // LGU user, PO approved it
            'approved_at_dilg_po' => '2026-06-01 09:00:00',
            'approved_at_dilg_ro' => null,
        ];
        $c4 = $method->invoke($controller, $movMockPendingRo, null, null, null, 'Q1', []);
        $this->assertSame('#ffedd5', $c4['bg']);
        $this->assertSame('#9a3412', $c4['text']);
        $this->assertSame('#fed7aa', $c4['border']);
        $this->assertSame('with new upload and with approval by the regional user', $c4['tooltip']);

        // Case 5: Approved document (100% complete for simplicity)
        $movMockApproved = (object)[
            'mov_file_path' => 'uploads/mov.pdf',
            'status' => 'approved',
            'mov_encoder_id' => 1,
            'approved_at_dilg_po' => '2026-06-01 09:00:00',
            'approved_at_dilg_ro' => '2026-06-01 10:00:00',
        ];
        $batchMockApproved = (object)[
            'status' => 'approved',
            'batch_document_encoder_id' => 1,
            'approved_at_dilg_po' => '2026-06-01 09:00:00',
            'approved_at_dilg_ro' => '2026-06-01 10:00:00',
        ];
        $c5 = $method->invoke($controller, $movMockApproved, null, null, $batchMockApproved, 'Q1', []);
        $this->assertSame('#ecfdf5', $c5['bg']);
        $this->assertSame('#065f46', $c5['text']);
        $this->assertSame('#a7f3d0', $c5['border']);
        $this->assertSame('fully approved', $c5['tooltip']);
    }

    #[Test]
    public function it_builds_quarterly_submission_dashboard_correctly(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'buildQuarterlySubmissionDashboard');
        $method->setAccessible(true);

        // Prepare a collection of reports with background colors matching our dashboard status mapping logic:
        // Case 1: #f3f4f6 (no uploads)
        // Case 2: #ecfdf5 (fully approved/compliant)
        // Case 3: #fee2e2 (returned)
        // Case 4: #fef9c3 / #ffedd5 (pending validation)
        $reports = collect([
            (object)[
                'quarter_q1_bg' => '#f3f4f6', // No upload
                'quarter_q1_percentage' => 0,
                'quarter_q2_bg' => '#ecfdf5', // Compliant
                'quarter_q2_percentage' => 100,
                'quarter_q3_bg' => '#fee2e2', // Returned
                'quarter_q3_percentage' => 50,
                'quarter_q4_bg' => '#fef9c3', // Pending
                'quarter_q4_percentage' => 25,
            ],
            (object)[
                'quarter_q1_bg' => '#ffedd5', // Pending
                'quarter_q1_percentage' => 75,
                'quarter_q2_bg' => '#ecfdf5', // Compliant
                'quarter_q2_percentage' => 100,
                'quarter_q3_bg' => '#f3f4f6', // No upload
                'quarter_q3_percentage' => 0,
                'quarter_q4_bg' => '#f3f4f6', // No upload
                'quarter_q4_percentage' => 0,
            ],
        ]);

        $dashboard = $method->invoke($controller, $reports);

        $this->assertSame(2, $dashboard['total_projects']);
        $this->assertSame(8, $dashboard['total_slots']);

        // Assert Q1
        $q1 = $dashboard['quarters']['q1'];
        $this->assertSame(1, $q1['with_submissions']);
        $this->assertSame(1, $q1['no_submission']);
        $this->assertSame(0, $q1['fully_compliant']);
        $this->assertSame(1, $q1['pending_validation']);
        $this->assertSame(0, $q1['returned']);
        $this->assertEquals(0, $q1['submission_rate']);

        // Assert Q2
        $q2 = $dashboard['quarters']['q2'];
        $this->assertSame(2, $q2['with_submissions']);
        $this->assertSame(0, $q2['no_submission']);
        $this->assertSame(2, $q2['fully_compliant']);
        $this->assertSame(0, $q2['pending_validation']);
        $this->assertSame(0, $q2['returned']);
        $this->assertEquals(100, $q2['submission_rate']);

        // Assert Q3
        $q3 = $dashboard['quarters']['q3'];
        $this->assertSame(1, $q3['with_submissions']);
        $this->assertSame(1, $q3['no_submission']);
        $this->assertSame(0, $q3['fully_compliant']);
        $this->assertSame(0, $q3['pending_validation']);
        $this->assertSame(1, $q3['returned']);
        $this->assertEquals(0, $q3['submission_rate']);

        // Assert Q4
        $q4 = $dashboard['quarters']['q4'];
        $this->assertSame(1, $q4['with_submissions']);
        $this->assertSame(1, $q4['no_submission']);
        $this->assertSame(0, $q4['fully_compliant']);
        $this->assertSame(1, $q4['pending_validation']);
        $this->assertSame(0, $q4['returned']);
        $this->assertEquals(0, $q4['submission_rate']);

        // Assert overall statistics
        $this->assertEquals(25, $dashboard['overall_submission_rate']); // 2 approved slots out of 8 = 25%
        $this->assertSame(2, $dashboard['overall_compliant']);
        $this->assertSame(2, $dashboard['overall_pending']);
        $this->assertSame(1, $dashboard['overall_returned']);
        $this->assertSame(3, $dashboard['overall_no_submission']);
        $this->assertSame(5, $dashboard['overall_submitted']);
    }

    #[Test]
    public function it_filters_reports_by_submission_year(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'applyFundUtilizationFiltersToQueries');
        $method->setAccessible(true);

        $furQuery = \App\Models\FundUtilizationReport::query();
        $lfpQuery = \App\Models\LocallyFundedProject::query();

        $filters = [
            'submission_year' => '2026',
        ];
        $expressions = [
            'fur_city' => 'city_municipality',
            'lfp_city' => 'city_municipality',
            'fur_barangay' => 'barangay',
            'lfp_barangay' => 'barangay',
            'fur_program' => 'program',
            'lfp_program' => 'program',
            'fur_fund_source' => 'fund_source',
            'lfp_fund_source' => 'fund_source',
            'fur_project_status' => 'project_status',
            'lfp_project_status' => 'project_status',
            'fur_province' => 'province',
            'lfp_province' => 'province',
        ];

        $method->invoke($controller, $furQuery, $lfpQuery, $filters, $expressions);
        $this->assertTrue(true);
    }

    #[Test]
    public function it_sorts_reports_prioritizing_returned_before_pending(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'sortFundUtilizationReportsForListing');
        $method->setAccessible(true);

        $reports = collect([
            (object)[
                'project_code' => 'P-PENDING-ONLY',
                'validation_summary' => [
                    'pending_total' => 1,
                    'returned_count' => 0,
                ],
                'validation_listing' => [],
            ],
            (object)[
                'project_code' => 'P-RETURNED-ONLY',
                'validation_summary' => [
                    'pending_total' => 0,
                    'returned_count' => 1,
                ],
                'validation_listing' => [],
            ],
            (object)[
                'project_code' => 'P-BOTH',
                'validation_summary' => [
                    'pending_total' => 1,
                    'returned_count' => 1,
                ],
                'validation_listing' => [],
            ],
            (object)[
                'project_code' => 'P-NONE',
                'validation_summary' => [
                    'pending_total' => 0,
                    'returned_count' => 0,
                ],
                'validation_listing' => [],
            ],
        ]);

        $sorted = $method->invoke($controller, $reports);
        $sortedCodes = $sorted->pluck('project_code')->all();

        $this->assertSame(['P-BOTH', 'P-RETURNED-ONLY', 'P-PENDING-ONLY', 'P-NONE'], $sortedCodes);
    }

    #[Test]
    public function it_allows_superadmins_to_upload_fund_utilization_documents(): void
    {
        $controller = new FundUtilizationReportController();
        $method = new \ReflectionMethod(FundUtilizationReportController::class, 'canUploadFundUtilizationDocuments');
        $method->setAccessible(true);

        $superadmin = new User(['role' => User::ROLE_SUPERADMIN]);
        $provincial = new User(['role' => User::ROLE_PROVINCIAL, 'agency' => 'DILG', 'province' => 'Abra']);
        $lgu = new User(['role' => User::ROLE_LGU, 'agency' => 'LGU']);
        $regional = new User(['role' => User::ROLE_REGIONAL, 'agency' => 'DILG', 'province' => 'Regional Office']);

        $this->assertTrue($method->invoke($controller, $superadmin));
        $this->assertTrue($method->invoke($controller, $provincial));
        $this->assertTrue($method->invoke($controller, $lgu));
        $this->assertFalse($method->invoke($controller, $regional));
    }
}

