<?php

namespace Tests\Unit;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TicketingSubmitPermissionTest extends TestCase
{
    public function test_user_with_ticketing_add_permission_can_submit_ticket_regardless_of_role(): void
    {
        $user = (new User())->forceFill([
            'idno' => 701,
            'role' => User::ROLE_PROVINCIAL,
            'access' => 'crud:ticketing_system.view,ticketing_system.add',
            'region' => 'Region IV-A (CALABARZON)',
            'province' => 'Laguna',
            'agency' => 'DILG',
            'office' => 'Provincial Office',
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('ticketing.submit'));
    }

    public function test_user_without_ticketing_add_permission_cannot_submit_ticket(): void
    {
        $user = (new User())->forceFill([
            'idno' => 702,
            'role' => User::ROLE_PROVINCIAL,
            'access' => 'crud:ticketing_system.view,ticketing_system.update',
            'region' => 'Region IV-A (CALABARZON)',
            'province' => 'Laguna',
            'agency' => 'DILG',
            'office' => 'Provincial Office',
        ]);

        $this->assertFalse(Gate::forUser($user)->allows('ticketing.submit'));
    }

    public function test_submitter_can_view_own_ticket_even_if_not_lgu_role(): void
    {
        $user = (new User())->forceFill([
            'idno' => 703,
            'role' => User::ROLE_PROVINCIAL,
            'access' => 'crud:ticketing_system.view,ticketing_system.add',
            'region' => 'Region IV-A (CALABARZON)',
            'province' => 'Laguna',
            'agency' => 'DILG',
            'office' => 'Provincial Office',
        ]);

        $ticket = new Ticket([
            'submitted_by' => 703,
            'region_scope' => 'Region IV-A (CALABARZON)',
            'province_scope' => 'Laguna',
            'status' => Ticket::STATUS_SUBMITTED,
            'current_level' => Ticket::LEVEL_PROVINCIAL,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('ticketing.view', $ticket));
    }
}
