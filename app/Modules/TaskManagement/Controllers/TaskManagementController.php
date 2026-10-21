<?php

namespace App\Modules\TaskManagement\Controllers;

use App\Http\Controllers\Controller;

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

        $approvalLevel = $user instanceof User ? $user->approvalLevel() : null;

        $ticketsByCategory = collect();
        if ($user instanceof User) {
            if ($user->isSuperAdmin()) {
                if ($approvalLevel === 'provincial' && $user->normalizedProvince() !== '') {
                    $ticketsByCategory = \App\Models\Ticket::with(['category', 'submitter'])
                        ->where('current_level', \App\Models\Ticket::LEVEL_PROVINCIAL)
                        ->whereNull('assigned_to')
                        ->whereRaw('LOWER(TRIM(COALESCE(province_scope, ""))) = ?', [$user->normalizedProvince()])
                        ->get()
                        ->groupBy(fn($t) => $t->category->name ?? 'Uncategorized');
                } else {
                    $ticketsByCategory = \App\Models\Ticket::with(['category', 'submitter'])
                        ->whereNull('assigned_to')
                        ->where('status', '!=', \App\Models\Ticket::STATUS_CLOSED)
                        ->get()
                        ->groupBy(fn($t) => $t->category->name ?? 'Uncategorized');
                }
            } elseif ($user->isRegionalUser() || $approvalLevel === 'regional') {
                $normalizedRegion = $user->normalizedRegionComparable();
                $regionalTicketsQuery = \App\Models\Ticket::with(['category', 'submitter'])
                    ->where('current_level', \App\Models\Ticket::LEVEL_REGIONAL)
                    ->whereNull('assigned_to')
                    ->where('status', '!=', \App\Models\Ticket::STATUS_CLOSED);

                if (DB::connection()->getDriverName() === 'sqlite') {
                    $regionalTicketsQuery->whereRaw('LOWER(TRIM(COALESCE(region_scope, ""))) LIKE ?', ['%' . $normalizedRegion . '%']);
                } else {
                    $regionalTicketsQuery->whereRaw('LOWER(TRIM(SUBSTRING_INDEX(COALESCE(region_scope, ""), "(", 1))) = ?', [$normalizedRegion]);
                }

                $ticketsByCategory = $regionalTicketsQuery->get()
                    ->groupBy(fn($t) => $t->category->name ?? 'Uncategorized');
            } elseif ($user->isProvincialUser() || $approvalLevel === 'provincial') {
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

        $userLevelLabel = 'User';
        $userJurisdictionLabel = '';

        if ($user) {
            if ($approvalLevel === 'regional') {
                $userLevelLabel = 'Regional Validator';
                $userJurisdictionLabel = $user->region ?: 'Cordillera Administrative Region (CAR)';
            } elseif ($approvalLevel === 'provincial') {
                $userLevelLabel = 'Provincial Validator';
                $userJurisdictionLabel = 'Province of ' . ($user->province ?: 'Assigned Province');
            } elseif ($approvalLevel === 'cmlgoo') {
                $userLevelLabel = 'C/MLGOO Validator';
                $userJurisdictionLabel = ($user->office ?: 'Assigned Municipality') . ($user->province ? ', ' . $user->province : '');
            } elseif ($user->isLguUser()) {
                $userLevelLabel = 'LGU Submitter';
                $userJurisdictionLabel = ($user->office ?: 'Assigned LGU') . ($user->province ? ', ' . $user->province : '');
            }
        }

        return view('task-management.index', compact(
            'pendingByModule',
            'returnedByModule',
            'ticketsByCategory',
            'userLevelLabel',
            'userJurisdictionLabel',
            'approvalLevel'
        ));
    }
}
