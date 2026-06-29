<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Models\User;
use App\Support\RolePermissionRegistry;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TicketingRegionalCommentPermissionTest extends TestCase
{
    public function test_regional_role_defaults_include_ticketing_add_permission(): void
    {
        $permissions = RolePermissionRegistry::permissionsForRole(User::ROLE_REGIONAL);

        $this->assertContains('ticketing_system.add', $permissions);
    }

    public function test_regional_user_can_add_comment_to_visible_regional_ticket_without_accepting_it(): void
    {
        $user = (new User())->forceFill([
            'idno' => 501,
            'role' => User::ROLE_REGIONAL,
            'region' => 'Region IV-A (CALABARZON)',
            'province' => 'Regional Office',
            'agency' => 'DILG',
            'office' => 'Regional Office',
        ]);

        $ticket = new Ticket([
            'submitted_by' => 9001,
            'region_scope' => 'Region IV-A (CALABARZON)',
            'province_scope' => 'Laguna',
            'status' => Ticket::STATUS_ESCALATED_TO_REGION,
            'current_level' => Ticket::LEVEL_REGIONAL,
            'assigned_to' => null,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('ticketing.view', $ticket));
        $this->assertTrue(Gate::forUser($user)->allows('ticketing.addComment', $ticket));
    }

    public function test_provincial_user_still_cannot_add_comment_before_accepting_ticket(): void
    {
        $user = (new User())->forceFill([
            'idno' => 601,
            'role' => User::ROLE_PROVINCIAL,
            'region' => 'Region IV-A (CALABARZON)',
            'province' => 'Laguna',
            'agency' => 'DILG',
            'office' => 'Provincial Office',
        ]);

        $ticket = new Ticket([
            'submitted_by' => 9002,
            'region_scope' => 'Region IV-A (CALABARZON)',
            'province_scope' => 'Laguna',
            'status' => Ticket::STATUS_SUBMITTED,
            'current_level' => Ticket::LEVEL_PROVINCIAL,
            'assigned_to' => null,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('ticketing.view', $ticket));
        $this->assertFalse(Gate::forUser($user)->allows('ticketing.addComment', $ticket));
    }
}
