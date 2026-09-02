<?php

namespace App\Http\Controllers;

use App\Support\NotificationCenter;
use App\Models\User;
use App\Services\RegionalApprovalPoolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskManagementController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of tasks for the user grouped by module.
     */
    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        $ticketsByCategory = collect();
        if ($user instanceof User) {
            if ($user->isSuperAdmin()) {
                $ticketsByCategory = \App\Models\Ticket::with(['category', 'submitter'])
                    ->whereNull('assigned_to')
                    ->where('status', '!=', \App\Models\Ticket::STATUS_CLOSED)
                    ->get()
                    ->groupBy(fn($t) => $t->category->name ?? 'Uncategorized');
            } elseif ($user->isRegionalUser()) {
                $normalizedRegion = $user->normalizedRegionComparable();
                $ticketsByCategory = \App\Models\Ticket::with(['category', 'submitter'])
                    ->where('current_level', \App\Models\Ticket::LEVEL_REGIONAL)
                    ->whereNull('assigned_to')
                    ->whereRaw('LOWER(TRIM(SUBSTRING_INDEX(COALESCE(region_scope, ""), "(", 1))) = ?', [$normalizedRegion])
                    ->get()
                    ->groupBy(fn($t) => $t->category->name ?? 'Uncategorized');
            } elseif ($user->isProvincialUser()) {
                $ticketsByCategory = \App\Models\Ticket::with(['category', 'submitter'])
                    ->where('current_level', \App\Models\Ticket::LEVEL_PROVINCIAL)
                    ->whereNull('assigned_to')
                    ->whereRaw('LOWER(TRIM(COALESCE(province_scope, ""))) = ?', [$user->normalizedProvince()])
                    ->get()
                    ->groupBy(fn($t) => $t->category->name ?? 'Uncategorized');
            }
        }

        if ($user instanceof User && $user->isSuperAdmin()) {
            $pendingApprovals = app(RegionalApprovalPoolService::class)->pendingTasks(true);
            $pendingByModule = $pendingApprovals->groupBy('module_label');

            $allNotificationRows = DB::table('tbnotifications')
                ->where('user_id', $user->getKey())
                ->whereNull('read_at')
                ->get();
            $returnedByModule = NotificationCenter::presentMany($allNotificationRows)
                ->reject(fn ($n) => ($n['module_key'] ?? '') === 'locally-funded-projects')
                ->filter(fn ($n) => $n['queue_key'] === 'returned')
                ->groupBy('module_label');

            return view('task-management.index', compact('pendingByModule', 'returnedByModule', 'ticketsByCategory'));
        }

        if ($user instanceof User && $user->isRegionalUser()) {
            $pendingApprovals = app(RegionalApprovalPoolService::class)->pendingTasks();
            $pendingByModule = $pendingApprovals->groupBy('module_label');

            $allNotificationRows = DB::table('tbnotifications')
                ->where('user_id', $user->getKey())
                ->whereNull('read_at')
                ->get();
            $returnedByModule = NotificationCenter::presentMany($allNotificationRows)
                ->reject(fn ($n) => ($n['module_key'] ?? '') === 'locally-funded-projects')
                ->filter(fn ($n) => $n['queue_key'] === 'returned')
                ->groupBy('module_label');

            return view('task-management.index', compact('pendingByModule', 'returnedByModule', 'ticketsByCategory'));
        }

        // Non-regional task lists continue to use personal notifications.
        $notificationQuery = DB::table('tbnotifications')
            ->whereNull('read_at');

        if ($user instanceof User && $user->isRegionalUser()) {
            $notificationQuery->whereIn('user_id', NotificationCenter::regionalPoolUserIds($user));
        } else {
            $notificationQuery->where('user_id', $user->getKey());
        }

        $allNotificationRows = $notificationQuery
            ->orderByDesc('created_at')
            ->get();

        $notifications = NotificationCenter::presentMany($allNotificationRows)
            ->reject(fn ($n) => ($n['module_key'] ?? '') === 'locally-funded-projects');

        // 1. Pending Approvals Pool (for Validators - Regional, Provincial, Superadmin)
        $pendingApprovals = collect();
        if ($user->isSuperAdmin()) {
            $pendingApprovals = $notifications->filter(fn ($n) => in_array($n['queue_key'], ['pending_provincial', 'pending_regional'], true));
        } elseif ($user->isRegionalUser()) {
            $pendingApprovals = $notifications->filter(fn ($n) => $n['queue_key'] === 'pending_regional');
        } elseif ($user->isProvincialUser()) {
            $pendingApprovals = $notifications->filter(fn ($n) => $n['queue_key'] === 'pending_provincial');
        }

        // 2. Returned Documents (for Uploaders)
        $returnedDocuments = $notifications->filter(fn ($n) => $n['queue_key'] === 'returned');

        // Group by module_label
        $pendingByModule = $pendingApprovals->groupBy('module_label');
        $returnedByModule = $returnedDocuments->groupBy('module_label');

        return view('task-management.index', compact('pendingByModule', 'returnedByModule', 'ticketsByCategory'));
    }
}
