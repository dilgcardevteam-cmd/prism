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
