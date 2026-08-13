<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundUtilizationApprovalRouteTest extends TestCase
{
    use RefreshDatabase;
    public function test_get_request_to_approval_route_redirects_back_to_the_report_page(): void
    {
        $user = User::factory()->create([
            'agency' => 'DILG',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/fund-utilization/ADM-LA-17-14-01-01-000-2/approve/mov/Q1');

        $response->assertRedirect('/fund-utilization/ADM-LA-17-14-01-01-000-2');
        $response->assertSessionHas('error', 'Approval actions must be submitted from the document approval form.');
    }
}