<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalProjectMonitoringCommitteeExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_export_route(): void
    {
        $response = $this->get(route('local-project-monitoring-committee.export'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_with_view_permission_can_export_to_excel(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('local-project-monitoring-committee.export', [
            'format' => 'excel',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
    }

    public function test_authenticated_user_with_view_permission_can_export_to_pdf(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('local-project-monitoring-committee.export', [
            'format' => 'pdf',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
