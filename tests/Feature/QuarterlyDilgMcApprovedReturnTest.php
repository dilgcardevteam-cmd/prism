<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\QuarterlyDilgMc201819Upload;
use App\Models\QuarterlyDilgMc201830Upload;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuarterlyDilgMcApprovedReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_regional_user_can_return_approved_mc_uploads_individually_with_audit_history(): void
    {
        $regionalUser = $this->createRegionalUser();

        $modules = [
            [
                QuarterlyDilgMc201819Upload::class,
                'reports.quarterly.dilg-mc-2018-19.approve',
            ],
            [
                QuarterlyDilgMc201830Upload::class,
                'reports.quarterly.dilg-mc-2018-30.approve',
            ],
        ];

        foreach ($modules as [$modelClass, $routeName]) {
            $document = $modelClass::query()->create([
                'office' => 'La Trinidad',
                'province' => 'Benguet',
                'year' => 2025,
                'quarter' => 'Q1',
                'file_path' => strtolower(class_basename($modelClass)) . '-approved.pdf',
                'original_name' => 'approved-report.pdf',
                'uploaded_by' => 987654,
                'uploaded_at' => now()->subDay(),
                'status' => 'approved',
                'approved_at' => now(),
                'approved_at_dilg_ro' => now(),
                'approved_by_dilg_ro' => 987653,
            ]);
            $otherDocument = $modelClass::query()->create([
                'office' => 'La Trinidad',
                'province' => 'Benguet',
                'year' => 2025,
                'quarter' => 'Q2',
                'file_path' => strtolower(class_basename($modelClass)) . '-untouched.pdf',
                'original_name' => 'another-approved-report.pdf',
                'uploaded_by' => 987654,
                'uploaded_at' => now()->subDay(),
                'status' => 'approved',
                'approved_at' => now(),
                'approved_at_dilg_ro' => now(),
                'approved_by_dilg_ro' => 987653,
            ]);

            $response = $this->actingAs($regionalUser)
                ->post(route($routeName, ['office' => 'La Trinidad', 'docId' => $document->id, 'year' => 2025]), [
                    'action' => 'return',
                    'remarks' => 'Please correct the reporting period.',
                ]);

            $response->assertRedirect();
            $document->refresh();
            $otherDocument->refresh();

            $this->assertSame('returned', $document->status);
            $this->assertSame('Please correct the reporting period.', $document->approval_remarks);
            $this->assertSame('Please correct the reporting period.', $document->user_remarks);
            $this->assertNull($document->approved_at_dilg_ro);
            $this->assertSame('approved', $otherDocument->status);

            $auditLog = ActivityLog::query()
                ->where('action', 'REPORT_RETURNED')
                ->get()
                ->first(fn (ActivityLog $log) => (int) ($log->properties['report_id'] ?? 0) === (int) $document->id
                    && ($log->properties['report_table'] ?? null) === $document->getTable());

            $this->assertNotNull($auditLog);
            $this->assertSame($regionalUser->idno, $auditLog->user_id);
            $this->assertSame('approved', $auditLog->properties['previous_status']);
            $this->assertSame('returned', $auditLog->properties['resulting_status']);
            $this->assertSame('Please correct the reporting period.', $auditLog->properties['remarks']);
            $this->assertDatabaseHas('activity_logs', [
                'action' => 'RETURN',
                'user_id' => $regionalUser->idno,
            ]);
        }
    }

    public function test_non_regional_user_cannot_return_an_approved_mc_upload(): void
    {
        $provincialUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'Benguet Provincial Office',
            'status' => 'active',
        ]);
        $document = QuarterlyDilgMc201819Upload::query()->create([
            'office' => 'La Trinidad',
            'province' => 'Benguet',
            'year' => 2025,
            'quarter' => 'Q1',
            'file_path' => 'mc-2018-19-approved.pdf',
            'original_name' => 'approved-report.pdf',
            'uploaded_by' => 987654,
            'uploaded_at' => now()->subDay(),
            'status' => 'approved',
            'approved_at' => now(),
            'approved_at_dilg_ro' => now(),
            'approved_by_dilg_ro' => 987653,
        ]);

        $this->actingAs($provincialUser)
            ->post(route('reports.quarterly.dilg-mc-2018-19.approve', [
                'office' => 'La Trinidad',
                'docId' => $document->id,
                'year' => 2025,
            ]), [
                'action' => 'return',
                'remarks' => 'Please correct this.',
            ])
            ->assertForbidden();

        $this->assertSame('approved', $document->fresh()->status);
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'REPORT_RETURNED',
        ]);
    }

    public function test_approved_return_requires_remarks(): void
    {
        $regionalUser = $this->createRegionalUser();
        $document = QuarterlyDilgMc201819Upload::query()->create([
            'office' => 'La Trinidad',
            'province' => 'Benguet',
            'year' => 2025,
            'quarter' => 'Q1',
            'file_path' => 'mc-2018-19-approved.pdf',
            'original_name' => 'approved-report.pdf',
            'uploaded_by' => 987654,
            'uploaded_at' => now()->subDay(),
            'status' => 'approved',
            'approved_at' => now(),
            'approved_at_dilg_ro' => now(),
            'approved_by_dilg_ro' => 987653,
        ]);
        $showUrl = route('reports.quarterly.dilg-mc-2018-19.show', [
            'office' => 'La Trinidad',
            'year' => 2025,
        ]);

        $this->actingAs($regionalUser)
            ->from($showUrl)
            ->post(route('reports.quarterly.dilg-mc-2018-19.approve', [
                'office' => 'La Trinidad',
                'docId' => $document->id,
                'year' => 2025,
            ]), ['action' => 'return'])
            ->assertRedirect($showUrl)
            ->assertSessionHasErrors('remarks');

        $this->assertSame('approved', $document->fresh()->status);
        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'REPORT_RETURNED',
        ]);
    }

    public function test_approved_return_control_is_shown_only_to_regional_users(): void
    {
        $regionalUser = $this->createRegionalUser();
        $document = QuarterlyDilgMc201830Upload::query()->create([
            'office' => 'La Trinidad',
            'province' => 'Benguet',
            'year' => 2025,
            'quarter' => 'Q1',
            'file_path' => 'mc-2018-30-approved.pdf',
            'original_name' => 'approved-report.pdf',
            'uploaded_by' => 987654,
            'uploaded_at' => now()->subDay(),
            'status' => 'approved',
            'approved_at' => now(),
            'approved_at_dilg_ro' => now(),
            'approved_by_dilg_ro' => 987653,
        ]);
        $showUrl = route('reports.quarterly.dilg-mc-2018-30.show', [
            'office' => 'La Trinidad',
            'year' => 2025,
        ]);

        $this->actingAs($regionalUser)
            ->get($showUrl)
            ->assertOk()
            ->assertSee('data-decision-action="return"', false);

        $provincialUser = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'agency' => 'DILG',
            'province' => 'Benguet',
            'office' => 'Benguet Provincial Office',
            'status' => 'active',
        ]);

        $this->actingAs($provincialUser)
            ->get($showUrl)
            ->assertOk()
            ->assertDontSee('data-decision-action="return"', false);
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
}
