<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ticket;
use App\Models\TicketCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTicketPoolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed category table
        TicketCategory::create([
            'name' => 'System Issue',
            'description' => 'System issue category',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        TicketCategory::create([
            'name' => 'Others',
            'description' => 'Other issues',
            'is_active' => true,
            'sort_order' => 9,
        ]);
    }

    public function test_superadmin_can_see_all_unassigned_tickets(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
            'status' => 'active',
        ]);

        $ticket = Ticket::create([
            'title' => 'Admin Unassigned Ticket',
            'description' => 'Test',
            'category_id' => TicketCategory::first()->id,
            'priority' => Ticket::PRIORITY_LOW,
            'status' => Ticket::STATUS_SUBMITTED,
            'current_level' => Ticket::LEVEL_PROVINCIAL,
            'assigned_role' => User::ROLE_PROVINCIAL,
            'contact_information' => 'test@example.com',
            'region_scope' => 'Cordillera Administrative Region',
            'province_scope' => 'Benguet',
            'submitted_by' => User::factory()->create()->idno,
            'date_submitted' => now(),
            'last_status_changed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('task-management.index'));
        $response->assertStatus(200);
        $response->assertSee('Unassigned Tickets Pool');
        $response->assertSee('Admin Unassigned Ticket');
    }

    public function test_provincial_user_only_sees_unassigned_tickets_for_their_province(): void
    {
        $provincialUserBenguet = User::factory()->create([
            'role' => User::ROLE_PROVINCIAL,
            'province' => 'Benguet',
            'status' => 'active',
        ]);

        $ticketBenguet = Ticket::create([
            'title' => 'Benguet Ticket',
            'description' => 'Test Benguet',
            'category_id' => TicketCategory::first()->id,
            'priority' => Ticket::PRIORITY_LOW,
            'status' => Ticket::STATUS_SUBMITTED,
            'current_level' => Ticket::LEVEL_PROVINCIAL,
            'assigned_role' => User::ROLE_PROVINCIAL,
            'contact_information' => 'test@example.com',
            'region_scope' => 'Cordillera Administrative Region',
            'province_scope' => 'Benguet',
            'submitted_by' => User::factory()->create()->idno,
            'date_submitted' => now(),
            'last_status_changed_at' => now(),
        ]);

        $ticketIfugao = Ticket::create([
            'title' => 'Ifugao Ticket',
            'description' => 'Test Ifugao',
            'category_id' => TicketCategory::first()->id,
            'priority' => Ticket::PRIORITY_LOW,
            'status' => Ticket::STATUS_SUBMITTED,
            'current_level' => Ticket::LEVEL_PROVINCIAL,
            'assigned_role' => User::ROLE_PROVINCIAL,
            'contact_information' => 'test@example.com',
            'region_scope' => 'Cordillera Administrative Region',
            'province_scope' => 'Ifugao',
            'submitted_by' => User::factory()->create()->idno,
            'date_submitted' => now(),
            'last_status_changed_at' => now(),
        ]);

        $response = $this->actingAs($provincialUserBenguet)->get(route('task-management.index'));
        $response->assertStatus(200);
        $response->assertSee('Unassigned Tickets Pool');
        $response->assertSee('Benguet Ticket');
        $response->assertDontSee('Ifugao Ticket');
    }

    public function test_lgu_user_cannot_see_unassigned_tickets_pool(): void
    {
        $lgu = User::factory()->create([
            'role' => User::ROLE_LGU,
            'province' => 'Benguet',
            'status' => 'active',
        ]);

        $response = $this->actingAs($lgu)->get(route('task-management.index'));
        $response->assertStatus(200);
        $response->assertDontSee('Unassigned Tickets Pool');
    }
}

