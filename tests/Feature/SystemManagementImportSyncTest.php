<?php

namespace Tests\Feature;

use App\Http\Controllers\SystemManagementController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemManagementImportSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_replacement_removes_stale_and_duplicate_rows_only_in_its_scope(): void
    {
        $legacyKeepId = $this->insertProject('SBDP-KEEP');
        $legacyStaleId = $this->insertProject('SBDP-STALE');
        $legacyDuplicateId = $this->insertProject('SBDP-DUP');
        $duplicateToRemoveId = $this->insertProject('sbdp-dup');
        $newerScopeId = $this->insertProject('SBDP-2025', 'SBDP', '2025_above');
        $sglgifScopeId = $this->insertProject('SGLGIF-001', 'SGLGIF');

        $this->invokeControllerMethod('removeProjectsMissingFromSnapshot', [
            ['snapshotScope' => 'subaybayan'],
            [
                'sbdp-keep' => ['project_code' => 'SBDP-KEEP'],
                'sbdp-dup' => ['project_code' => 'SBDP-DUP'],
            ],
        ]);

        $remainingIds = DB::table('subay_project_profiles')->pluck('id')->all();
        $this->assertContains($legacyKeepId, $remainingIds);
        $this->assertContains($legacyDuplicateId, $remainingIds);
        $this->assertNotContains($legacyStaleId, $remainingIds);
        $this->assertNotContains($duplicateToRemoveId, $remainingIds);
        $this->assertContains($newerScopeId, $remainingIds);
        $this->assertContains($sglgifScopeId, $remainingIds);
    }

    public function test_snapshot_updates_blank_values_without_overwriting_other_dataset_scopes(): void
    {
        $legacyId = $this->insertProject('SBDP-SHARED', 'SBDP', null, 'Existing legacy title');
        $newerScopeId = $this->insertProject('SBDP-SHARED', 'SBDP', '2025_above', 'Newer dataset title');

        $this->invokeControllerMethod('upsertProjectProfileRow', [
            'subay_project_profiles',
            'sbdp-shared',
            [
                'project_code' => 'SBDP-SHARED',
                'project_title' => null,
                'program' => 'SBDP',
                'subaybayan_dataset_group' => null,
            ],
            now(),
            ['snapshotScope' => 'subaybayan'],
        ]);

        $this->assertNull(DB::table('subay_project_profiles')->where('id', $legacyId)->value('project_title'));
        $this->assertSame(
            'Newer dataset title',
            DB::table('subay_project_profiles')->where('id', $newerScopeId)->value('project_title')
        );
    }

    public function test_snapshot_import_rejects_missing_and_duplicate_project_codes_without_changing_data(): void
    {
        $existingId = $this->insertProject('SBDP-KEEP', 'SBDP', null, 'Existing title');
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'subay-import-' . bin2hex(random_bytes(8)) . '.csv';
        file_put_contents($path, implode("\n", [
            'project_code,project_title,funding_year',
            'SBDP-NEW,First title,2020',
            'sbdp-new,Second title,2020',
            ',Missing code,2020',
        ]));

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Project Code is missing on row(s): 4. Duplicate Project Code(s): "sbdp-new" (rows 2, 3)');

            $this->invokeControllerMethod('importSnapshot', [
                $path,
                ['snapshotScope' => 'subaybayan', 'yearRange' => ['max' => 2024]],
            ]);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }

            $this->assertSame(
                ['SBDP-KEEP'],
                DB::table('subay_project_profiles')->pluck('project_code')->all()
            );
            $this->assertSame(
                'Existing title',
                DB::table('subay_project_profiles')->where('id', $existingId)->value('project_title')
            );
        }
    }

    public function test_fund_utilization_sync_removes_thousand_separators_from_amounts(): void
    {
        $this->invokeControllerMethod('synchronizeFundUtilizationProjects', [
            [
                'cbd-21-14-01-01-000-1' => [
                    'project_code' => 'CBD-21-14-01-01-000-1',
                    'program' => 'DILG-BLDGS',
                    'province' => 'ABRA',
                    'implementing_unit' => 'DILG RO',
                    'funding_year' => '2021',
                    'project_title' => 'DILG TWO STOREY PROVINCIAL BUILDING',
                    'national_subsidy_original_allocation' => '7,000,000.00',
                    'contract_price' => '6,500,000.00',
                    'status' => 'CANCELLED',
                ],
            ],
        ]);

        $project = DB::table('tbfur')
            ->where('project_code', 'cbd-21-14-01-01-000-1')
            ->first();

        $this->assertNotNull($project);
        $this->assertSame(7000000.0, (float) $project->allocation);
        $this->assertSame(6500000.0, (float) $project->contract_amount);
    }

    public function test_fund_utilization_project_code_can_be_inserted_without_other_fields(): void
    {
        DB::table('tbfur')->insert([
            'project_code' => 'PROJECT-CODE-ONLY',
        ]);

        $project = DB::table('tbfur')
            ->where('project_code', 'PROJECT-CODE-ONLY')
            ->first();

        $this->assertNotNull($project);
        $this->assertNull($project->province);
        $this->assertNull($project->implementing_unit);
        $this->assertNull($project->fund_source);
        $this->assertNull($project->funding_year);
        $this->assertNull($project->project_title);
        $this->assertNull($project->allocation);
        $this->assertNull($project->contract_amount);
        $this->assertNull($project->project_status);
        $this->assertNull($project->barangay);
        $this->assertNull($project->created_at);
        $this->assertNull($project->updated_at);
    }

    private function insertProject(
        string $projectCode,
        string $program = 'SBDP',
        ?string $datasetGroup = null,
        ?string $projectTitle = 'Project title'
    ): int {
        return (int) DB::table('subay_project_profiles')->insertGetId([
            'project_code' => $projectCode,
            'project_title' => $projectTitle,
            'program' => $program,
            'subaybayan_dataset_group' => $datasetGroup,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invokeControllerMethod(string $methodName, array $arguments): mixed
    {
        $method = new \ReflectionMethod(SystemManagementController::class, $methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(new SystemManagementController(), $arguments);
    }
}
