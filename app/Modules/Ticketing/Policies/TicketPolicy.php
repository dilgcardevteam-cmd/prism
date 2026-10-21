<?php

namespace App\Modules\Ticketing\Policies;

use App\Modules\Ticketing\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can submit a ticket.
     */
    public function submit(User $user): bool
    {
        return $user->hasCrudPermission('ticketing_system', 'add');
    }

    /**
     * Determine whether the user can view the ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        $userRegionComparable = method_exists($user, 'normalizedRegionComparable')
            ? $user->normalizedRegionComparable()
            : $user->normalizedRegion();
        $ticketRegionComparable = method_exists($user, 'normalizedRegionComparable')
            ? $user->normalizedRegionComparable($ticket->region_scope)
            : strtolower(trim((string) $ticket->region_scope));

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ((int) $ticket->submitted_by === (int) $user->getKey()) {
            return true;
        }

        if ($user->isProvincialUser()) {
            return $user->normalizedProvince() !== ''
                && strtolower(trim((string) $ticket->province_scope)) === $user->normalizedProvince();
        }

        if ($user->isRegionalUser()) {
            return $userRegionComparable !== ''
                && $ticketRegionComparable === $userRegionComparable
                && in_array($ticket->status, [
                    Ticket::STATUS_ESCALATED_TO_REGION,
                    Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                    Ticket::STATUS_PENDING,
                    Ticket::STATUS_REOPENED,
                    Ticket::STATUS_RESOLVED_BY_REGION,
                    Ticket::STATUS_CLOSED,
                ], true);
        }

        return false;
    }

    /**
     * Determine whether provincial user can accept the ticket.
     */
    public function acceptProvince(User $user, Ticket $ticket): bool
    {
        return $user->isProvincialUser()
            && strtolower(trim((string) $ticket->province_scope)) === $user->normalizedProvince()
            && $ticket->current_level === Ticket::LEVEL_PROVINCIAL
            && $ticket->status === Ticket::STATUS_SUBMITTED
            && $ticket->assigned_to === null;
    }

    /**
     * Determine whether regional user can accept the ticket.
     */
    public function acceptRegion(User $user, Ticket $ticket): bool
    {
        $userRegionComparable = method_exists($user, 'normalizedRegionComparable')
            ? $user->normalizedRegionComparable()
            : $user->normalizedRegion();
        $ticketRegionComparable = method_exists($user, 'normalizedRegionComparable')
            ? $user->normalizedRegionComparable($ticket->region_scope)
            : strtolower(trim((string) $ticket->region_scope));

        return $user->isRegionalUser()
            && $ticketRegionComparable === $userRegionComparable
            && $ticket->current_level === Ticket::LEVEL_REGIONAL
            && $ticket->status === Ticket::STATUS_ESCALATED_TO_REGION
            && $ticket->assigned_to === null;
    }

    /**
     * Determine whether user can add comments to the ticket.
     */
    public function addComment(User $user, Ticket $ticket): bool
    {
        if ($user->isProvincialUser() && $ticket->current_level === Ticket::LEVEL_PROVINCIAL) {
            return $this->manageProvince($user, $ticket);
        }

        return $this->view($user, $ticket);
    }

    /**
     * Determine whether provincial user can manage the ticket.
     */
    public function manageProvince(User $user, Ticket $ticket): bool
    {
        return $user->isProvincialUser()
            && strtolower(trim((string) $ticket->province_scope)) === $user->normalizedProvince()
            && $ticket->current_level === Ticket::LEVEL_PROVINCIAL
            && (int) $ticket->assigned_to === (int) $user->getKey();
    }

    /**
     * Determine whether regional user can manage the ticket.
     */
    public function manageRegion(User $user, Ticket $ticket): bool
    {
        $userRegionComparable = method_exists($user, 'normalizedRegionComparable')
            ? $user->normalizedRegionComparable()
            : $user->normalizedRegion();
        $ticketRegionComparable = method_exists($user, 'normalizedRegionComparable')
            ? $user->normalizedRegionComparable($ticket->region_scope)
            : strtolower(trim((string) $ticket->region_scope));

        if (!$user->isRegionalUser() || $ticketRegionComparable !== $userRegionComparable) {
            return false;
        }

        return $ticket->current_level === Ticket::LEVEL_REGIONAL
            && (int) $ticket->assigned_to === (int) $user->getKey();
    }

    /**
     * Determine whether user is a superadmin.
     */
    public function manageAdmin(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether superadmin can manage the regional ticket.
     */
    public function manageAdminTicket(User $user, Ticket $ticket): bool
    {
        return $user->isSuperAdmin()
            && (int) $ticket->assigned_to === (int) $user->getKey()
            && $ticket->current_level === Ticket::LEVEL_REGIONAL
            && in_array($ticket->status, [
                Ticket::STATUS_ESCALATED_TO_REGION,
                Ticket::STATUS_UNDER_REVIEW_BY_REGION,
            ], true);
    }

    /**
     * Determine whether user can mark the ticket pending.
     */
    public function pending(User $user, Ticket $ticket): bool
    {
        if (!in_array($ticket->status, [
            Ticket::STATUS_SUBMITTED,
            Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE,
            Ticket::STATUS_REOPENED,
            Ticket::STATUS_ESCALATED_TO_REGION,
            Ticket::STATUS_UNDER_REVIEW_BY_REGION,
        ], true)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return (int) $ticket->assigned_to === (int) $user->getKey();
        }

        if ($user->isProvincialUser()) {
            return $ticket->current_level === Ticket::LEVEL_PROVINCIAL
                && (int) $ticket->assigned_to === (int) $user->getKey();
        }

        if ($user->isRegionalUser()) {
            return $this->manageRegion($user, $ticket);
        }

        return false;
    }

    /**
     * Determine whether user can reopen the ticket.
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        return ($user->isSuperAdmin() || (int) $ticket->submitted_by === (int) $user->getKey())
            && in_array($ticket->status, [
                Ticket::STATUS_RESOLVED_BY_PROVINCE,
                Ticket::STATUS_RESOLVED_BY_REGION,
                Ticket::STATUS_CLOSED,
            ], true);
    }

    /**
     * Determine whether user can resume the pending ticket.
     */
    public function resume(User $user, Ticket $ticket): bool
    {
        if ($ticket->status !== Ticket::STATUS_PENDING) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return (int) $ticket->assigned_to === (int) $user->getKey();
        }

        if ($user->isProvincialUser()) {
            return $ticket->current_level === Ticket::LEVEL_PROVINCIAL
                && (int) $ticket->assigned_to === (int) $user->getKey();
        }

        return $user->isRegionalUser()
            && $this->manageRegion($user, $ticket);
    }
}
