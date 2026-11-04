<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectDashboardFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function seedSampleProjects(): void
    {
        DB::table('subay_project_profiles')->insert([
            [
                'project_code' => 'FALGU-2025-ABRA-1',
                'project_title' => 'Abra Ongoing Project',
                'region' => 'CAR',
                'province' => 'Abra',
                'city_municipality' => 'Bangued',
                'barangay' => "Zone 1\nZone 2",
                'program' => 'FALGU',
                'funding_year' => '2025',
                'type_of_project' => 'Road',
                'status' => 'Ongoing',
            ],
            [
                'project_code' => 'FALGU-2024-ABRA-2',
                'project_title' => 'Abra Completed Project',
                'region' => 'CAR',
                'province' => 'Abra',
                'city_municipality' => 'Bangued',
                'barangay' => 'Zone 3',
                'program' => 'FALGU',
                'funding_year' => '2024',
                'type_of_project' => 'Bridge',
                'status' => 'Completed',
            ],
            [
                'project_code' => 'SBDP-2025-BENGUET-1',
                'project_title' => 'Benguet Project',
                'region' => 'CAR',
                'province' => 'Benguet',
                'city_municipality' => 'La Trinidad',
                'barangay' => 'Poblacion',
                'program' => 'SBDP',
                'funding_year' => '2025',
                'type_of_project' => 'Road',
                'status' => 'Ongoing',
            ],
        ]);
    }

    public function test_filter_options_follow_the_selected_province_city_and_other_filters(): void
    {
        $this->seedSampleProjects();

        $regionalUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $response = $this->actingAs($regionalUser)->get(route('dashboard', [
            'province' => 'Abra',
            'city_municipality' => 'Bangued',
            'project_status' => 'Ongoing',
            'funding_year_filter_submitted' => '1',
        ]));

        $response->assertOk();
        $response->assertSee('<option value="Bangued"', false);
        $response->assertDontSee('<option value="La Trinidad"', false);
        $response->assertSee('<option value="FALGU"', false);
        $response->assertDontSee('<option value="SBDP"', false);
        $response->assertSee('<option value="Road"', false);
        $response->assertDontSee('<option value="Bridge"', false);
        $response->assertSee('<option value="Zone 1"', false);
        $response->assertDontSee('<option value="Zone 3"', false);
    }

    public function test_dashboard_city_options_match_province_case_insensitively(): void
    {
        $this->seedSampleProjects();

        $regionalUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $response = $this->actingAs($regionalUser)->get(route('dashboard', [
            'province' => 'ABRA',
            'funding_year_filter_submitted' => '1',
        ]));

        $response->assertOk();
        $response->assertSee('<option value="Bangued"', false);
    }

    public function test_dashboard_filters_ajax_endpoint_returns_localized_and_cascading_options(): void
    {
        $this->seedSampleProjects();

        $regionalUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        // When Province=Abra and Status=Ongoing, cities should include Abra's municipalities, programs should include FALGU but not SBDP
        $response = $this->actingAs($regionalUser)->getJson(route('dashboard.filters', [
            'province' => 'Abra',
            'project_status' => 'Ongoing',
        ]));

        $response->assertOk();
        $data = $response->json();

        // Cities with available projects for Abra should be populated
        $this->assertContains('Bangued', $data['cities']);
        $this->assertNotContains('Boliney', $data['cities']);
        $this->assertNotContains('La Trinidad', $data['cities']);

        // Programs should only show FALGU (Abra Ongoing), not SBDP (Benguet)
        $this->assertContains('FALGU', $data['programs']);
        $this->assertNotContains('SBDP', $data['programs']);

        // Project Types should only show Road (Abra Ongoing), not Bridge (Completed)
        $this->assertContains('Road', $data['project_types']);
        $this->assertNotContains('Bridge', $data['project_types']);
    }

    public function test_dashboard_filters_ajax_endpoint_matches_uppercase_province(): void
    {
        $this->seedSampleProjects();

        $regionalUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $response = $this->actingAs($regionalUser)->getJson(route('dashboard.filters', [
            'province' => 'ABRA',
        ]));

        $response->assertOk();
        $this->assertContains('Bangued', $response->json('cities'));
    }

    public function test_dashboard_filters_by_type_endpoint(): void
    {
        $this->seedSampleProjects();

        $regionalUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        $citiesResponse = $this->actingAs($regionalUser)->getJson(route('dashboard.filters.by-type', [
            'type' => 'city_municipality',
            'province' => 'Abra',
            'preserve_city_municipality' => 'Bangued',
        ]));

        $citiesResponse->assertOk();
        $this->assertContains('Bangued', $citiesResponse->json('options'));
        $this->assertNotContains('Bucay', $citiesResponse->json('options'));
        $this->assertNotContains('La Trinidad', $citiesResponse->json('options'));
        $this->assertEquals(['Bangued'], $citiesResponse->json('preserve'));

        $programsResponse = $this->actingAs($regionalUser)->getJson(route('dashboard.filters.by-type', [
            'type' => 'programs',
            'province' => 'Abra',
            'project_status' => 'Ongoing',
        ]));

        $programsResponse->assertOk();
        $this->assertContains('FALGU', $programsResponse->json('options'));
        $this->assertNotContains('SBDP', $programsResponse->json('options'));
    }

    public function test_city_options_are_filtered_strictly_by_available_projects(): void
    {
        $this->seedSampleProjects();

        $regionalUser = User::factory()->create([
            'role' => User::ROLE_REGIONAL,
            'agency' => 'DILG',
            'region' => 'CAR',
            'province' => 'Regional Office',
            'office' => 'Regional Office',
            'status' => 'active',
        ]);

        // When Program is SBDP, available cities should only be La Trinidad (Benguet), not Bangued (Abra)
        $response = $this->actingAs($regionalUser)->getJson(route('dashboard.filters', [
            'program' => 'SBDP',
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertContains('La Trinidad', $data['cities']);
        $this->assertNotContains('Bangued', $data['cities']);

        // When Program is FALGU, available cities should only be Bangued, not La Trinidad
        $falguResponse = $this->actingAs($regionalUser)->getJson(route('dashboard.filters', [
            'program' => 'FALGU',
        ]));

        $falguResponse->assertOk();
        $falguData = $falguResponse->json();
        $this->assertContains('Bangued', $falguData['cities']);
        $this->assertNotContains('La Trinidad', $falguData['cities']);
    }
}
