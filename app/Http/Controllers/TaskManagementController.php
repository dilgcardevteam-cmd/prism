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

        $poolService = app(RegionalApprovalPoolService::class);
        $pendingApprovals = $user ? $poolService->pendingTasksForUser($user) : collect();
        $returnedDocuments = $user ? $poolService->returnedTasksForUser($user) : collect();

        $pendingByModule = $pendingApprovals->groupBy('module_label');
        $returnedByModule = $returnedDocuments->groupBy('module_label');

        return view('task-management.index', compact('pendingByModule', 'returnedByModule', 'ticketsByCategory'));
    }
}
