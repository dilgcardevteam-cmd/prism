<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'crud_permission:ticketing_system,view']);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $baseQuery = Ticket::query()
            ->with(['category', 'submitter', 'assignee'])
            ->visibleTo($user);

        $cards = $this->summaryCards($user, $baseQuery);
        $recentTickets = (clone $baseQuery)
            ->orderByDesc('last_status_changed_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        $visibleTicketIds = (clone $baseQuery)->select('id');
        $recentActivity = TicketHistory::query()
            ->with(['ticket:id,ticket_number,title', 'actor:idno,fname,lname'])
            ->whereIn('ticket_id', $visibleTicketIds)
            ->latest()
            ->limit(10)
            ->get();

        return view('ticketing-dashboard', [
            'cards' => $cards,
            'recentTickets' => $recentTickets,
            'recentActivity' => $recentActivity,
            'ticketStatuses' => Ticket::statusOptions(),
            'userRoleLabel' => $user->roleLabel(),
        ]);
    }

    protected function summaryCards($user, $baseQuery): array
    {
        if ($user->isLguUser()) {
            return [
                [
                    'label' => 'My Submitted Tickets',
                    'count' => (clone $baseQuery)->count(),
                    'icon' => 'fa-paper-plane',
                    'color' => '#1d4ed8',
                ],
                [
                    'label' => 'In Progress',
                    'count' => (clone $baseQuery)->whereIn('status', [
                        Ticket::STATUS_SUBMITTED,
                        Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE,
                        Ticket::STATUS_ESCALATED_TO_REGION,
                        Ticket::STATUS_UNDER_REVIEW_BY_REGION,
                        Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
                    ])->count(),
                    'icon' => 'fa-spinner',
                    'color' => '#7c3aed',
                ],
                [
                    'label' => 'Resolved',
                    'count' => (clone $baseQuery)->whereIn('status', [
                        Ticket::STATUS_RESOLVED_BY_PROVINCE,
                        Ticket::STATUS_RESOLVED_BY_REGION,
                        Ticket::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                    ])->count(),
                    'icon' => 'fa-circle-check',
                    'color' => '#15803d',
                ],
                [
                    'label' => 'Closed',
                    'count' => (clone $baseQuery)->where('status', Ticket::STATUS_CLOSED)->count(),
                    'icon' => 'fa-box-archive',
                    'color' => '#475569',
                ],
            ];
        }

        if ($user->isProvincialUser()) {
            return [
                [
                    'label' => 'Unassigned Queue',
                    'count' => (clone $baseQuery)
                        ->where('current_level', Ticket::LEVEL_PROVINCIAL)
                        ->whereNull('assigned_to')
                        ->count(),
                    'icon' => 'fa-inbox',
                    'color' => '#1d4ed8',
                ],
                [
                    'label' => 'My Accepted Tickets',
                    'count' => (clone $baseQuery)
                        ->where('current_level', Ticket::LEVEL_PROVINCIAL)
                        ->where('assigned_to', $user->getKey())
                        ->count(),
                    'icon' => 'fa-hand',
                    'color' => '#0f766e',
                ],
                [
                    'label' => 'Under Review by Me',
                    'count' => (clone $baseQuery)
                        ->where('status', Ticket::STATUS_UNDER_REVIEW_BY_PROVINCE)
                        ->where('assigned_to', $user->getKey())
                        ->count(),
                    'icon' => 'fa-magnifying-glass',
                    'color' => '#7c3aed',
                ],
                [
                    'label' => 'Escalated to Region',
                    'count' => (clone $baseQuery)->where('status', Ticket::STATUS_ESCALATED_TO_REGION)->count(),
                    'icon' => 'fa-arrow-up-right-dots',
                    'color' => '#b45309',
                ],
            ];
        }

        if ($user->isRegionalUser()) {
            return [
                [
                    'label' => 'Unassigned Queue',
                    'count' => (clone $baseQuery)
                        ->where('current_level', Ticket::LEVEL_REGIONAL)
                        ->whereNull('assigned_to')
                        ->count(),
                    'icon' => 'fa-inbox',
                    'color' => '#1d4ed8',
                ],
                [
                    'label' => 'My Accepted Tickets',
                    'count' => (clone $baseQuery)
                        ->where('current_level', Ticket::LEVEL_REGIONAL)
                        ->where('assigned_to', $user->getKey())
                        ->count(),
                    'icon' => 'fa-hand',
                    'color' => '#0f766e',
                ],
                [
                    'label' => 'Under Review by Me',
                    'count' => (clone $baseQuery)
                        ->where('status', Ticket::STATUS_UNDER_REVIEW_BY_REGION)
                        ->where('assigned_to', $user->getKey())
                        ->count(),
                    'icon' => 'fa-magnifying-glass',
                    'color' => '#0f766e',
                ],
                [
                    'label' => 'Forwarded to Central Office',
                    'count' => (clone $baseQuery)->where('status', Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE)->count(),
                    'icon' => 'fa-building-columns',
                    'color' => '#be123c',
                ],
            ];
        }

        return [
            [
                'label' => 'All Tickets',
                'count' => (clone $baseQuery)->count(),
                'icon' => 'fa-ticket',
                'color' => '#1d4ed8',
            ],
            [
                'label' => 'Open Tickets',
                'count' => (clone $baseQuery)->where('status', '!=', Ticket::STATUS_CLOSED)->count(),
                'icon' => 'fa-life-ring',
                'color' => '#7c3aed',
            ],
            [
                'label' => 'Forwarded Tickets',
                'count' => (clone $baseQuery)->where('status', Ticket::STATUS_FORWARDED_TO_CENTRAL_OFFICE)->count(),
                'icon' => 'fa-building-columns',
                'color' => '#be123c',
            ],
            [
                'label' => 'Closed Tickets',
                'count' => (clone $baseQuery)->where('status', Ticket::STATUS_CLOSED)->count(),
                'icon' => 'fa-box-archive',
                'color' => '#475569',
            ],
        ];
    }
}
