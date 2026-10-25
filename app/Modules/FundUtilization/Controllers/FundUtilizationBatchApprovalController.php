<?php

namespace App\Modules\FundUtilization\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LocallyFundedProject;
use App\Models\User;
use App\Modules\FundUtilization\Models\FURFDP;
use App\Modules\FundUtilization\Models\FURMovUpload;
use App\Modules\FundUtilization\Models\FURWrittenNotice;
use App\Modules\FundUtilization\Models\FURBatchDocument;
use App\Modules\FundUtilization\Models\FundUtilizationApprovalWorkflow;
use App\Modules\FundUtilization\Models\FundUtilizationBatchApproval;
use App\Modules\FundUtilization\Models\FundUtilizationBatchApprovalLog;
use App\Modules\TaskManagement\Services\RegionalApprovalPoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FundUtilizationBatchApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the specified batch approval details, projects, documents, and history.
     */
    public function show(Request $request, string $batchCode)
    {
        $batch = FundUtilizationBatchApproval::with(['uploader', 'currentApprover', 'logs.actor'])
            ->where('batch_code', $batchCode)
            ->firstOrFail();

        $projectCodes = (array) ($batch->project_codes ?? []);
        $projects = collect();

        if (!empty($projectCodes)) {
            if (Schema::hasTable('subay_project_profiles')) {
                $projects = DB::table('subay_project_profiles')
                    ->whereIn('project_code', $projectCodes)
                    ->select(['project_code', 'project_title', 'province', 'city_municipality', 'status', 'allocation_num', 'total_project_cost'])
                    ->get()
                    ->map(fn ($p) => [
                        'project_code' => $p->project_code,
                        'project_title' => $p->project_title ?: 'Untitled Project',
                        'province' => $p->province ?: '',
                        'city_municipality' => $p->city_municipality ?: '',
                        'status' => $p->status ?: 'Ongoing',
                        'allocation' => (float) ($p->allocation_num ?? $p->total_project_cost ?? 0),
                    ]);
            }

            if ($projects->isEmpty()) {
                $projects = LocallyFundedProject::whereIn('subaybayan_project_code', $projectCodes)
                    ->get()
                    ->map(fn ($p) => [
                        'project_code' => $p->subaybayan_project_code,
                        'project_title' => $p->project_name ?: 'Untitled Project',
                        'province' => $p->province ?: '',
                        'city_municipality' => $p->city_municipality ?: '',
                        'status' => 'Ongoing',
                        'allocation' => (float) ($p->lgsf_allocation ?? 0),
                    ]);
            }
        }

        // Gather uploaded documents
        $documents = [];

        // 1. Batch Documents
        $batchDoc = FURBatchDocument::where('batch_code', $batchCode)->first();
        if ($batchDoc) {
            $batchFilePaths = [];
            for ($i = 1; $i <= 10; $i++) {
                $col = "batch_document_file_path_{$i}";
                if (!empty($batchDoc->$col)) {
                    $batchFilePaths[] = $batchDoc->$col;
                }
            }
            if (empty($batchFilePaths) && !empty($batchDoc->batch_document_file_path)) {
                $batchFilePaths[] = $batchDoc->batch_document_file_path;
            }
            foreach ($batchFilePaths as $idx => $fp) {
                $documents[] = [
                    'type' => 'batch-document',
                    'label' => 'Batch Document ' . ($idx + 1),
                    'file_name' => basename($fp),
                    'url' => Storage::disk('public')->url($fp),
                    'is_link' => false,
                ];
            }
        }

        // 2. MOV
        $mov = FURMovUpload::where('batch_code', $batchCode)->first();
        if ($mov && !empty($mov->mov_file_path)) {
            $documents[] = [
                'type' => 'mov',
                'label' => 'Memorandum of Voucher (MOV)',
                'file_name' => basename($mov->mov_file_path),
                'url' => Storage::disk('public')->url($mov->mov_file_path),
                'is_link' => false,
            ];
        }

        // 3. Written Notices
        $wn = FURWrittenNotice::where('batch_code', $batchCode)->first();
        if ($wn) {
            $noticeFields = [
                'secretary_dbm_path' => 'Secretary DBM Notice',
                'secretary_dilg_path' => 'Secretary DILG Notice',
                'speaker_house_path' => 'Speaker of the House Notice',
                'president_senate_path' => 'Senate President Notice',
                'house_committee_path' => 'House Committee on Appropriations Notice',
                'senate_committee_path' => 'Senate Committee on Finance Notice',
            ];
            foreach ($noticeFields as $col => $lbl) {
                if (!empty($wn->$col)) {
                    $documents[] = [
                        'type' => 'written-notice',
                        'label' => $lbl,
                        'file_name' => basename($wn->$col),
                        'url' => Storage::disk('public')->url($wn->$col),
                        'is_link' => false,
                    ];
                }
            }
        }

        // 4. FDP & Posting Link
        $fdp = FURFDP::where('batch_code', $batchCode)->first();
        if ($fdp) {
            if (!empty($fdp->fdp_file_path)) {
                $documents[] = [
                    'type' => 'fdp',
                    'label' => 'Full Disclosure Policy (FDP) Document',
                    'file_name' => basename($fdp->fdp_file_path),
                    'url' => Storage::disk('public')->url($fdp->fdp_file_path),
                    'is_link' => false,
                ];
            }
            if (!empty($fdp->posting_link)) {
                $documents[] = [
                    'type' => 'posting-link',
                    'label' => 'FDP Portal / Website Posting Link',
                    'file_name' => $fdp->posting_link,
                    'url' => $fdp->posting_link,
                    'is_link' => true,
                ];
            }
        }

        // Check validator authorization
        $user = Auth::user();
        $approvalLevel = $user instanceof User ? $user->approvalLevel() : null;
        $canApprove = false;
        $canReturn = false;
        $actionLabel = '';

        if ($user instanceof User && !$user->isLguUser()) {
            $poolService = app(RegionalApprovalPoolService::class);
            $currentLevel = (int) $batch->current_approval_level;
            $batchStatus = strtolower((string) $batch->status);
            $isPending = str_starts_with($batchStatus, 'pending');

            if ($isPending) {
                if ($currentLevel === 1) {
                    // Level 1: Provincial validator
                    if ($approvalLevel === 'provincial' || $user->isProvincialUser() || $user->isProvincialDilgAssignment()) {
                        $batchProvince = $batch->uploader_province ?: $poolService->resolveProvinceFromOffice($batch->uploader_office);
                        if ($batchProvince === '' || $poolService->matchesProvince($batchProvince, $user->province)) {
                            $canApprove = true;
                            $canReturn = true;
                            $actionLabel = 'Approve & Forward to Regional Office';
                        }
                    }
                } elseif ($currentLevel === 2) {
                    // Level 2: Regional validator or SuperAdmin
                    if ($approvalLevel === 'regional' || $user->isRegionalUser() || $user->isSuperAdmin()) {
                        $canApprove = true;
                        $canReturn = true;
                        $actionLabel = 'Final Approve';
                    }
                }
            }
        }

        $logs = $batch->logs->map(function ($log) {
            return [
                'action' => $log->action,
                'actor_name' => $log->actor?->fullName() ?: 'System',
                'actor_role' => $log->actor?->role ?: '',
                'level' => $log->approval_level,
                'status' => $log->new_status,
                'remarks' => $log->remarks,
                'created_at' => $log->created_at ? $log->created_at->format('M d, Y h:i A') : '',
            ];
        });

        $payload = [
            'success' => true,
            'batch' => [
                'id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'quarter' => $batch->quarter,
                'year' => $batch->year,
                'status' => $batch->status,
                'current_approval_level' => $batch->current_approval_level,
                'project_count' => $batch->project_count,
                'uploader_name' => $batch->uploader?->fullName() ?: 'Unknown Uploader',
                'uploader_role' => $batch->uploader_role,
                'uploader_province' => $batch->uploader_province,
                'uploader_office' => $batch->uploader_office,
                'submitted_at' => $batch->submitted_at ? $batch->submitted_at->format('M d, Y h:i A') : '',
                'approval_remarks' => $batch->approval_remarks,
                'documents_manifest' => $batch->documents_manifest,
                'documents' => $documents,
                'projects' => $projects,
                'logs' => $logs,
            ],
            'can_approve' => $canApprove,
            'can_return' => $canReturn,
            'approval_action_label' => $actionLabel,
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($payload);
        }

        return redirect()->route('task-management.index', ['open_batch' => $batchCode]);
    }

    /**
     * Approve the batch submission and advance it or grant final approval.
     */
    public function approve(Request $request, string $batchCode): JsonResponse
    {
        $user = Auth::user();
        if (!$user instanceof User || $user->isLguUser()) {
            return response()->json(['message' => 'LGU users cannot validate or approve submissions.'], 403);
        }

        $batch = FundUtilizationBatchApproval::where('batch_code', $batchCode)->firstOrFail();
        $currentLevel = (int) $batch->current_approval_level;
        $poolService = app(RegionalApprovalPoolService::class);
        $approvalLevel = $user->approvalLevel();

        // Authorization checks
        if ($currentLevel === 1) {
            $isProvincial = $approvalLevel === 'provincial' || $user->isProvincialUser() || $user->isProvincialDilgAssignment();
            if (!$isProvincial) {
                return response()->json(['message' => 'Only a Provincial Validator can act on Level 1 approvals.'], 403);
            }
            $batchProvince = $batch->uploader_province ?: $poolService->resolveProvinceFromOffice($batch->uploader_office);
            if ($batchProvince !== '' && !$poolService->matchesProvince($batchProvince, $user->province)) {
                return response()->json(['message' => 'You cannot validate batch submissions outside your assigned province.'], 403);
            }
        } elseif ($currentLevel === 2) {
            $isRegional = $approvalLevel === 'regional' || $user->isRegionalUser() || $user->isSuperAdmin();
            if (!$isRegional) {
                return response()->json(['message' => 'Only a Regional Validator can act on Level 2 approvals.'], 403);
            }
        } else {
            return response()->json(['message' => 'This batch is not currently pending validation.'], 422);
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);
        $remarks = trim((string) ($validated['remarks'] ?? ''));

        DB::transaction(function () use ($batch, $currentLevel, $user, $remarks) {
            $previousStatus = $batch->status;
            $projectCodes = (array) ($batch->project_codes ?? []);
            $quarter = $batch->quarter;

            if ($currentLevel === 1) {
                // Forward to Regional Officer
                $newStatus = 'Pending Level 2 - Regional Officer';
                $batch->last_approved_level = 1;
                $batch->current_approval_level = 2;
                $batch->status = $newStatus;
                $batch->current_approver_id = null;
                $batch->current_approver_role = User::ROLE_REGIONAL;
                $batch->save();

                FundUtilizationBatchApprovalLog::create([
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'actor_id' => $user->getKey(),
                    'action' => 'Approved & Forwarded',
                    'approval_level' => 1,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'remarks' => $remarks !== '' ? $remarks : 'Approved by Provincial Officer and forwarded to Regional Officer.',
                    'created_at' => now(),
                ]);

                // Update document tables for provincial approval
                $poUpdates = [
                    'approved_by_dilg_po' => $user->getKey(),
                    'approved_at_dilg_po' => now(),
                ];
                FURMovUpload::where('batch_code', $batch->batch_code)->update($poUpdates);
                FURBatchDocument::where('batch_code', $batch->batch_code)->update($poUpdates);
                FURWrittenNotice::where('batch_code', $batch->batch_code)->update($poUpdates);
                FURFDP::where('batch_code', $batch->batch_code)->update($poUpdates);

                // Update workflows if present
                if (!empty($projectCodes) && Schema::hasTable('fund_utilization_approval_workflows')) {
                    FundUtilizationApprovalWorkflow::whereIn('project_code', $projectCodes)
                        ->where('quarter', $quarter)
                        ->where('current_approval_level', 1)
                        ->update([
                            'current_approval_level' => 2,
                            'last_approved_level' => 1,
                            'status' => 'Pending Level 2 - Regional Officer',
                        ]);
                }
            } else {
                // Final Regional Approval
                $newStatus = 'Approved';
                $batch->last_approved_level = 2;
                $batch->current_approval_level = null;
                $batch->current_approver_id = null;
                $batch->current_approver_role = null;
                $batch->status = $newStatus;
                $batch->completed_at = now();
                $batch->save();

                FundUtilizationBatchApprovalLog::create([
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'actor_id' => $user->getKey(),
                    'action' => 'Final Approved',
                    'approval_level' => 2,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'remarks' => $remarks !== '' ? $remarks : 'Final approval granted by Regional Officer.',
                    'created_at' => now(),
                ]);

                // Cascade final approval to all project document tables
                $finalDocUpdates = [
                    'status' => 'approved',
                    'approved_by_dilg_ro' => $user->getKey(),
                    'approved_at_dilg_ro' => now(),
                ];

                FURMovUpload::where('batch_code', $batch->batch_code)->update($finalDocUpdates);
                FURBatchDocument::where('batch_code', $batch->batch_code)->update($finalDocUpdates);

                FURWrittenNotice::where('batch_code', $batch->batch_code)->update([
                    'status' => 'approved',
                    'dbm_status' => 'approved',
                    'dilg_status' => 'approved',
                    'speaker_status' => 'approved',
                    'president_status' => 'approved',
                    'house_status' => 'approved',
                    'senate_status' => 'approved',
                    'approved_by_dilg_ro' => $user->getKey(),
                    'approved_at_dilg_ro' => now(),
                ]);

                FURFDP::where('batch_code', $batch->batch_code)->update([
                    'status' => 'approved',
                    'fdp_status' => 'approved',
                    'posting_status' => 'approved',
                    'approved_by_dilg_ro' => $user->getKey(),
                    'approved_at_dilg_ro' => now(),
                ]);

                // Update workflows to Approved
                if (!empty($projectCodes) && Schema::hasTable('fund_utilization_approval_workflows')) {
                    FundUtilizationApprovalWorkflow::whereIn('project_code', $projectCodes)
                        ->where('quarter', $quarter)
                        ->update([
                            'status' => 'Approved',
                            'last_approved_level' => 2,
                            'current_approval_level' => null,
                            'completed_at' => now(),
                        ]);
                }
            }
        });

        $message = $currentLevel === 1
            ? "Batch submission {$batchCode} has been approved and forwarded to the Regional Office."
            : "Batch submission {$batchCode} has been granted final approval.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'batch_code' => $batchCode,
            'status' => $batch->fresh()->status,
        ]);
    }

    /**
     * Return the batch submission for revision.
     */
    public function returnBatch(Request $request, string $batchCode): JsonResponse
    {
        $user = Auth::user();
        if (!$user instanceof User || $user->isLguUser()) {
            return response()->json(['message' => 'LGU users cannot return submissions.'], 403);
        }

        $validated = $request->validate([
            'remarks' => 'required|string|min:3|max:2000',
        ]);
        $remarks = trim($validated['remarks']);

        $batch = FundUtilizationBatchApproval::where('batch_code', $batchCode)->firstOrFail();
        $currentLevel = (int) $batch->current_approval_level;
        $poolService = app(RegionalApprovalPoolService::class);
        $approvalLevel = $user->approvalLevel();

        // Authorization checks
        if ($currentLevel === 1) {
            $isProvincial = $approvalLevel === 'provincial' || $user->isProvincialUser() || $user->isProvincialDilgAssignment();
            if (!$isProvincial) {
                return response()->json(['message' => 'Only a Provincial Validator can return Level 1 submissions.'], 403);
            }
            $batchProvince = $batch->uploader_province ?: $poolService->resolveProvinceFromOffice($batch->uploader_office);
            if ($batchProvince !== '' && !$poolService->matchesProvince($batchProvince, $user->province)) {
                return response()->json(['message' => 'You cannot return batch submissions outside your assigned province.'], 403);
            }
        } elseif ($currentLevel === 2) {
            $isRegional = $approvalLevel === 'regional' || $user->isRegionalUser() || $user->isSuperAdmin();
            if (!$isRegional) {
                return response()->json(['message' => 'Only a Regional Validator can return Level 2 submissions.'], 403);
            }
        } else {
            return response()->json(['message' => 'This batch is not currently pending validation.'], 422);
        }

        DB::transaction(function () use ($batch, $currentLevel, $user, $remarks) {
            $previousStatus = $batch->status;
            $projectCodes = (array) ($batch->project_codes ?? []);
            $quarter = $batch->quarter;

            if ($currentLevel === 1) {
                $newStatus = 'Returned by Provincial Officer';
                $batch->status = $newStatus;
                $batch->current_approval_level = null;
                $batch->returned_from_level = 1;
                $batch->approval_remarks = $remarks;
                $batch->save();

                FundUtilizationBatchApprovalLog::create([
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'actor_id' => $user->getKey(),
                    'action' => 'Returned',
                    'approval_level' => 1,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'remarks' => $remarks,
                    'created_at' => now(),
                ]);
            } else {
                $newStatus = 'Returned by Regional Officer';
                $batch->status = $newStatus;
                $batch->returned_from_level = 2;
                $batch->approval_remarks = $remarks;

                if ($batch->uploader_role === User::ROLE_LGU) {
                    $batch->current_approval_level = 1;
                } else {
                    $batch->current_approval_level = null;
                }
                $batch->save();

                FundUtilizationBatchApprovalLog::create([
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'actor_id' => $user->getKey(),
                    'action' => 'Returned',
                    'approval_level' => 2,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus,
                    'remarks' => $remarks,
                    'created_at' => now(),
                ]);
            }

            // Sync returned status across document tables
            $docReturnUpdates = [
                'status' => 'returned',
                'approval_remarks' => $remarks,
            ];

            FURMovUpload::where('batch_code', $batch->batch_code)->update($docReturnUpdates);
            FURBatchDocument::where('batch_code', $batch->batch_code)->update($docReturnUpdates);

            FURWrittenNotice::where('batch_code', $batch->batch_code)->update([
                'status' => 'returned',
                'dbm_status' => 'returned',
                'dilg_status' => 'returned',
                'speaker_status' => 'returned',
                'president_status' => 'returned',
                'house_status' => 'returned',
                'senate_status' => 'returned',
                'approval_remarks' => $remarks,
            ]);

            FURFDP::where('batch_code', $batch->batch_code)->update([
                'status' => 'returned',
                'fdp_status' => 'returned',
                'posting_status' => 'returned',
                'approval_remarks' => $remarks,
            ]);

            if (!empty($projectCodes) && Schema::hasTable('fund_utilization_approval_workflows')) {
                FundUtilizationApprovalWorkflow::whereIn('project_code', $projectCodes)
                    ->where('quarter', $quarter)
                    ->update([
                        'status' => $newStatus,
                        'current_approval_level' => $batch->current_approval_level,
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Batch submission {$batchCode} has been returned for revision.",
            'batch_code' => $batchCode,
            'status' => $batch->fresh()->status,
        ]);
    }
}
