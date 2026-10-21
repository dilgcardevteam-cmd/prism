<?php

namespace Tests\Feature;

use App\Models\RoadMaintenanceStatusDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoadMaintenanceStatusReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
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

    public function test_superadmin_can_view_delete_button_and_delete_form_is_not_nested(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $path = 'road-maintenance-status/La_Trinidad/test_q1.pdf';
        Storage::disk('public')->put($path, 'dummy content');

        $doc = RoadMaintenanceStatusDocument::create([
            'office' => 'La Trinidad',
            'province' => 'Benguet',
            'doc_type' => 'road_maintenance_status',
            'year' => (int) now()->year,
            'quarter' => 'Q1',
            'file_path' => $path,
            'uploaded_by' => $superAdmin->idno,
            'uploaded_at' => now(),
            'status' => 'pending_po',
        ]);

        $response = $this->actingAs($superAdmin)->get('/road-maintenance-status/La Trinidad/edit');
        $response->assertStatus(200);

        $content = $response->getContent();
        // Assert delete button references standalone form
        $response->assertSee('form="road-maintenance-delete-form-Q1"', false);
        $response->assertSee('id="road-maintenance-delete-form-Q1"', false);

        // Verify there is no nested <form> inside another <form> in the rendered HTML
        $tokens = [];
        preg_match_all('/<form\b|<\/form>/i', $content, $matches);
        $depth = 0;
        $hasNested = false;
        foreach ($matches[0] as $token) {
            if (strtolower($token) === '</form>') {
                $depth--;
            } else {
                $depth++;
                if ($depth > 1) {
                    $hasNested = true;
                }
            }
        }
        $this->assertFalse($hasNested, 'Rendered page contains nested <form> elements.');
        $this->assertSame(0, $depth, 'All <form> elements are properly closed.');
    }

    public function test_superadmin_can_delete_document_successfully(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $path = 'road-maintenance-status/La_Trinidad/test_q1.pdf';
        Storage::disk('public')->put($path, 'dummy content');

        $doc = RoadMaintenanceStatusDocument::create([
            'office' => 'La Trinidad',
            'province' => 'Benguet',
            'doc_type' => 'road_maintenance_status',
            'year' => (int) now()->year,
            'quarter' => 'Q1',
            'file_path' => $path,
            'uploaded_by' => $superAdmin->idno,
            'uploaded_at' => now(),
            'status' => 'pending_po',
        ]);

        $this->assertTrue(Storage::disk('public')->exists($path));

        $response = $this->actingAs($superAdmin)
            ->delete("/road-maintenance-status/La Trinidad/document/{$doc->id}");

        $response->assertRedirect('/road-maintenance-status/La%20Trinidad/edit?year=' . now()->year . '&quarter=Q1');
        $response->assertSessionHas('success', 'Uploaded document deleted successfully.');

        // Verify file is deleted from disk
        $this->assertFalse(Storage::disk('public')->exists($path));

        // Verify database record is deleted
        $this->assertDatabaseMissing('tblroad_maintenance_status_documents', [
            'id' => $doc->id,
        ]);
    }

    public function test_authorized_provincial_user_can_delete_pending_document(): void
    {
        $provincialUser = $this->createProvincialUser('Benguet');

        $path = 'road-maintenance-status/La_Trinidad/test_q2.pdf';
        Storage::disk('public')->put($path, 'dummy content');

        $doc = RoadMaintenanceStatusDocument::create([
            'office' => 'La Trinidad',
            'province' => 'Benguet',
            'doc_type' => 'road_maintenance_status',
            'year' => (int) now()->year,
            'quarter' => 'Q2',
            'file_path' => $path,
            'uploaded_by' => $provincialUser->idno,
            'uploaded_at' => now(),
            'status' => 'pending_ro',
        ]);

        $response = $this->actingAs($provincialUser)
            ->delete("/road-maintenance-status/La Trinidad/document/{$doc->id}");

        $response->assertRedirect('/road-maintenance-status/La%20Trinidad/edit?year=' . now()->year . '&quarter=Q2');
        $this->assertDatabaseMissing('tblroad_maintenance_status_documents', ['id' => $doc->id]);
        $this->assertFalse(Storage::disk('public')->exists($path));
    }

    public function test_non_superadmin_cannot_delete_approved_document(): void
    {
        $provincialUser = $this->createProvincialUser('Benguet');

        $path = 'road-maintenance-status/La_Trinidad/test_q3.pdf';
        Storage::disk('public')->put($path, 'dummy content');

        $doc = RoadMaintenanceStatusDocument::create([
            'office' => 'La Trinidad',
            'province' => 'Benguet',
            'doc_type' => 'road_maintenance_status',
            'year' => (int) now()->year,
            'quarter' => 'Q3',
            'file_path' => $path,
            'uploaded_by' => $provincialUser->idno,
            'uploaded_at' => now(),
            'status' => 'approved',
            'approved_at_dilg_ro' => now(),
        ]);

        $response = $this->actingAs($provincialUser)
            ->delete("/road-maintenance-status/La Trinidad/document/{$doc->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('tblroad_maintenance_status_documents', ['id' => $doc->id]);
    }

    public function test_user_cannot_delete_outside_geographic_scope(): void
    {
        // Abra provincial user attempting to delete Benguet document
        $abraProvincialUser = $this->createProvincialUser('Abra');

        $doc = RoadMaintenanceStatusDocument::create([
            'office' => 'La Trinidad',
            'province' => 'Benguet',
            'doc_type' => 'road_maintenance_status',
            'year' => (int) now()->year,
            'quarter' => 'Q1',
            'file_path' => 'some/path.pdf',
            'uploaded_by' => 9999,
            'uploaded_at' => now(),
            'status' => 'pending_po',
        ]);

        $response = $this->actingAs($abraProvincialUser)
            ->delete("/road-maintenance-status/La Trinidad/document/{$doc->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('tblroad_maintenance_status_documents', ['id' => $doc->id]);
    }
}

