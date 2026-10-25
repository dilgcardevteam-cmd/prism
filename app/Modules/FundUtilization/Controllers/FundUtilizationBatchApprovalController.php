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
                    ->select(['project_code', 'project_title', 'province', 'city_municipality', 'barangay', 'status', 'allocation_num', 'total_project_cost'])
                    ->get()
                    ->map(fn ($p) => [
                        'project_code' => $p->project_code,
                        'project_title' => $p->project_title ?: 'Untitled Project',
                        'province' => $p->province ?: '',
                        'city_municipality' => $p->city_municipality ?: '',
                        'barangay' => $p->barangay ?: '',
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
                        'barangay' => $p->barangay ?: '',
                        'status' => 'Ongoing',
                        'allocation' => (float) ($p->lgsf_allocation ?? 0),
                    ]);
            }
        }

        // Gather uploaded documents
        $documents = [];

        // 1. Batch Documents
        $batchDoc = FURBatchDocument::where('batch_code', $batchCode)->first();
        if (!$batchDoc && !empty($projectCodes)) {
            $batchDoc = FURBatchDocument::whereNull('batch_code')
                ->where('quarter', $batch->quarter)
                ->whereIn('project_code', $projectCodes)
                ->first();
        }
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
                    'section' => 'Batch Documents',
                    'field_name' => 'Batch Document ' . ($idx + 1),
                    'label' => 'Batch Document ' . ($idx + 1),
                    'file_name' => basename($fp),
                    'url' => $this->documentViewPath($projectCodes[0] ?? '', $batch->quarter, 'batch-document', $idx),
                    'is_link' => false,
                ];
            }
        }

        // 2. MOV
        $mov = FURMovUpload::where('batch_code', $batchCode)->first();
        if (!$mov && !empty($projectCodes)) {
            $mov = FURMovUpload::whereNull('batch_code')
                ->where('quarter', $batch->quarter)
                ->whereIn('project_code', $projectCodes)
                ->first();
        }
        if ($mov && !empty($mov->mov_file_path)) {
            $documents[] = [
                'type' => 'mov',
                'section' => 'Fund Utilization Report (MOV)',
                'field_name' => 'MOV',
                'label' => 'Means of Verification (MOV)',
                'file_name' => basename($mov->mov_file_path),
                'url' => $this->documentViewPath($projectCodes[0] ?? '', $batch->quarter, 'mov'),
                'is_link' => false,
            ];
        }

        // 3. Written Notices
        $wn = FURWrittenNotice::where('batch_code', $batchCode)->first();
        if (!$wn && !empty($projectCodes)) {
            $wn = FURWrittenNotice::whereNull('batch_code')
                ->where('quarter', $batch->quarter)
                ->whereIn('project_code', $projectCodes)
                ->first();
        }
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
                        'type' => 'written-notice:' . str_replace('_path', '', $col),
                        'section' => 'Written Notices',
                        'field_name' => $lbl,
                        'label' => $lbl,
                        'file_name' => basename($wn->$col),
                        'url' => $this->documentViewPath($projectCodes[0] ?? '', $batch->quarter, $this->writtenNoticeDocumentType($col)),
                        'is_link' => false,
                    ];
                }
            }
        }

        // 4. FDP & Posting Link
        $fdp = FURFDP::where('batch_code', $batchCode)->first();
        if (!$fdp && !empty($projectCodes)) {
            $fdp = FURFDP::whereNull('batch_code')
                ->where('quarter', $batch->quarter)
                ->whereIn('project_code', $projectCodes)
                ->first();
        }
        if ($fdp) {
            if (!empty($fdp->fdp_file_path)) {
                $documents[] = [
                    'type' => 'fdp',
                    'section' => 'Full Disclosure Policy (FDP)',
                    'field_name' => 'FDP Document',
                    'label' => 'Full Disclosure Policy (FDP) Document',
                    'file_name' => basename($fdp->fdp_file_path),
                    'url' => $this->documentViewPath($projectCodes[0] ?? '', $batch->quarter, 'fdp'),
                    'is_link' => false,
                ];
            }
            if (!empty($fdp->posting_link)) {
                $documents[] = [
                    'type' => 'posting-link',
                    'section' => 'Full Disclosure Policy (FDP)',
                    'field_name' => 'FDP Portal / Website Posting Link',
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

        $documents = collect($documents)->map(function (array $document) use ($batchCode, $batch) {
            $document['key'] = $document['type'];
            $document['statuses'] = $this->documentStatuses($batchCode, (array) ($batch->project_codes ?? []), $document['type']);
            return $document;
        })->unique('key')->filter(function ($doc) use ($batch) {
            $projectCodes = (array) ($batch->project_codes ?? []);
            if (empty($projectCodes)) return true;
            return collect($projectCodes)->contains(fn ($code) => ($doc['statuses'][$code] ?? 'pending') !== 'approved');
        })->values()->all();

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
     * Approve or return the selected document for the given project codes,
     * saving the document to the individual FUR profile records with upload and approval timestamps.
     */
    public function validateDocument(Request $request, string $batchCode): JsonResponse
    {
        $user = Auth::user();
        if (!$user instanceof User || $user->isLguUser()) {
            return response()->json(['message' => 'LGU users cannot validate submissions.'], 403);
        }

        $batch = FundUtilizationBatchApproval::where('batch_code', $batchCode)->firstOrFail();
        $this->authorizeScopedAction($batch, $user);
        $validated = $request->validate([
            'action' => 'required|in:approve,return',
            'document_key' => 'required|string|max:80',
            'project_codes' => 'required|array|min:1',
            'project_codes.*' => 'string|max:255',
            'remarks' => 'nullable|string|max:2000',
        ]);
        $remarks = trim((string) ($validated['remarks'] ?? ''));
        if ($validated['action'] === 'return' && $remarks === '') {
            return response()->json(['message' => 'Remarks are required when returning a document.'], 422);
        }

        $allowedCodes = collect((array) ($batch->project_codes ?? []))->map(fn ($code) => trim((string) $code))->filter();
        $projectCodes = collect($validated['project_codes'])
            ->map(fn ($code) => trim((string) $code))
            ->filter(fn ($code) => $allowedCodes->contains($code))
            ->unique()->values();
        $config = $this->documentConfig((string) $validated['document_key']);
        if ($projectCodes->isEmpty() || $config === null) {
            return response()->json(['message' => 'Select valid projects and a document before submitting.'], 422);
        }

        DB::transaction(function () use ($batch, $config, $projectCodes, $user, $remarks, $validated) {
            $isApprove = $validated['action'] === 'approve';
            $docKey = (string) $validated['document_key'];
            $quarter = $batch->quarter;
            $batchCode = $batch->batch_code;
            $level = (int) $batch->current_approval_level;
            $approverId = $user->idno ?? $user->getKey();
            $now = now();

            if (str_starts_with($docKey, 'written-notice:')) {
                $field = substr($docKey, strlen('written-notice:'));
                $colPath = $field . '_path';
                $prefix = match ($field) {
                    'secretary_dbm' => 'dbm',
                    'secretary_dilg' => 'dilg',
                    'speaker_house' => 'speaker',
                    'president_senate' => 'president',
                    'house_committee' => 'house',
                    'senate_committee' => 'senate',
                    default => 'dilg',
                };
                $statusCol = $config['status_columns'][0] ?? ($prefix . '_status');

                // Find source notice attached to this batch or project codes
                $sourceNotice = FURWrittenNotice::where('batch_code', $batchCode)
                    ->whereNotNull($colPath)
                    ->first();
                if (!$sourceNotice && !empty($batch->project_codes)) {
                    $sourceNotice = FURWrittenNotice::where('quarter', $quarter)
                        ->whereIn('project_code', (array) $batch->project_codes)
                        ->whereNotNull($colPath)
                        ->first();
                }

                $filePath = $sourceNotice ? $sourceNotice->$colPath : null;
                $uploadedAt = $sourceNotice ? ($sourceNotice->{$prefix . '_uploaded_at'} ?? $sourceNotice->created_at) : ($batch->submitted_at ?? $now);
                $encoderId = $sourceNotice ? ($sourceNotice->{$prefix . '_encoder_id'} ?? $sourceNotice->encoder_id) : ($batch->uploader_id ?? null);
                $speakerOriginalName = $sourceNotice?->speaker_house_original_name;

                foreach ($projectCodes as $projectCode) {
                    $record = FURWrittenNotice::firstOrNew([
                        'project_code' => $projectCode,
                        'quarter' => $quarter,
                    ]);

                    if ($filePath && empty($record->$colPath)) {
                        $record->$colPath = $filePath;
                    }
                    if ($field === 'speaker_house' && $speakerOriginalName && empty($record->speaker_house_original_name)) {
                        $record->speaker_house_original_name = $speakerOriginalName;
                    }
                    if (empty($record->{$prefix . '_uploaded_at'})) {
                        $record->{$prefix . '_uploaded_at'} = $uploadedAt;
                    }
                    if ($encoderId && empty($record->{$prefix . '_encoder_id'})) {
                        $record->{$prefix . '_encoder_id'} = $encoderId;
                    }
                    if ($encoderId && empty($record->encoder_id)) {
                        $record->encoder_id = $encoderId;
                    }
                    $record->batch_code = $batchCode;

                    if ($isApprove) {
                        $record->$statusCol = 'approved';
                        $record->status = 'approved';
                        $record->{$prefix . '_approved_at'} = $now;
                        $record->{$prefix . '_approved_by'} = $approverId;
                        $record->approved_at = $now;
                        $record->approved_by = $approverId;

                        if ($level === 1) {
                            $record->{$prefix . '_approved_at_dilg_po'} = $now;
                            $record->{$prefix . '_approved_by_dilg_po'} = $approverId;
                            $record->approved_at_dilg_po = $now;
                            $record->approved_by_dilg_po = $approverId;
                        } else {
                            $record->{$prefix . '_approved_at_dilg_ro'} = $now;
                            $record->{$prefix . '_approved_by_dilg_ro'} = $approverId;
                            $record->approved_at_dilg_ro = $now;
                            $record->approved_by_dilg_ro = $approverId;
                        }

                        if ($remarks !== '') {
                            $record->{$prefix . '_remarks'} = $remarks;
                            $record->approval_remarks = $remarks;
                        }
                    } else {
                        $record->$statusCol = 'returned';
                        $record->status = 'returned';
                        $record->{$prefix . '_remarks'} = $remarks;
                        $record->approval_remarks = $remarks;
                        $record->{$prefix . '_approved_at'} = null;
                        $record->{$prefix . '_approved_by'} = null;
                    }

                    $record->save();

                    $this->syncIndividualProjectWorkflow(
                        $projectCode,
                        $quarter,
                        'written-notice-' . str_replace('secretary_', '', $field),
                        $isApprove,
                        $level,
                        $approverId,
                        $remarks
                    );
                }
            } elseif ($docKey === 'mov') {
                $sourceMov = FURMovUpload::where('batch_code', $batchCode)->whereNotNull('mov_file_path')->first();
                if (!$sourceMov && !empty($batch->project_codes)) {
                    $sourceMov = FURMovUpload::where('quarter', $quarter)->whereIn('project_code', (array) $batch->project_codes)->whereNotNull('mov_file_path')->first();
                }

                $filePath = $sourceMov ? $sourceMov->mov_file_path : null;
                $uploadedAt = $sourceMov ? ($sourceMov->mov_uploaded_at ?? $sourceMov->created_at) : ($batch->submitted_at ?? $now);
                $encoderId = $sourceMov ? ($sourceMov->mov_encoder_id ?? $sourceMov->encoder_id) : ($batch->uploader_id ?? null);

                foreach ($projectCodes as $projectCode) {
                    $record = FURMovUpload::firstOrNew([
                        'project_code' => $projectCode,
                        'quarter' => $quarter,
                    ]);

                    if ($filePath && empty($record->mov_file_path)) {
                        $record->mov_file_path = $filePath;
                    }
                    if (empty($record->mov_uploaded_at)) {
                        $record->mov_uploaded_at = $uploadedAt;
                    }
                    if ($encoderId && empty($record->mov_encoder_id)) {
                        $record->mov_encoder_id = $encoderId;
                    }
                    if ($encoderId && empty($record->encoder_id)) {
                        $record->encoder_id = $encoderId;
                    }
                    $record->batch_code = $batchCode;

                    if ($isApprove) {
                        $record->status = 'approved';
                        $record->approved_at = $now;
                        $record->approved_by = $approverId;
                        if ($level === 1) {
                            $record->approved_at_dilg_po = $now;
                            $record->approved_by_dilg_po = $approverId;
                        } else {
                            $record->approved_at_dilg_ro = $now;
                            $record->approved_by_dilg_ro = $approverId;
                        }
                        if ($remarks !== '') {
                            $record->approval_remarks = $remarks;
                        }
                    } else {
                        $record->status = 'returned';
                        $record->approval_remarks = $remarks;
                        $record->approved_at = null;
                        $record->approved_by = null;
                    }

                    $record->save();

                    $this->syncIndividualProjectWorkflow($projectCode, $quarter, 'mov', $isApprove, $level, $approverId, $remarks);
                }
            } elseif ($docKey === 'fdp') {
                $sourceFdp = FURFDP::where('batch_code', $batchCode)->whereNotNull('fdp_file_path')->first();
                if (!$sourceFdp && !empty($batch->project_codes)) {
                    $sourceFdp = FURFDP::where('quarter', $quarter)->whereIn('project_code', (array) $batch->project_codes)->whereNotNull('fdp_file_path')->first();
                }

                $filePath = $sourceFdp ? $sourceFdp->fdp_file_path : null;
                $uploadedAt = $sourceFdp ? ($sourceFdp->fdp_uploaded_at ?? $sourceFdp->created_at) : ($batch->submitted_at ?? $now);
                $encoderId = $sourceFdp ? ($sourceFdp->fdp_encoder_id ?? $sourceFdp->encoder_id) : ($batch->uploader_id ?? null);

                foreach ($projectCodes as $projectCode) {
                    $record = FURFDP::firstOrNew([
                        'project_code' => $projectCode,
                        'quarter' => $quarter,
                    ]);

                    if ($filePath && empty($record->fdp_file_path)) {
                        $record->fdp_file_path = $filePath;
                    }
                    if (empty($record->fdp_uploaded_at)) {
                        $record->fdp_uploaded_at = $uploadedAt;
                    }
                    if ($encoderId && empty($record->fdp_encoder_id)) {
                        $record->fdp_encoder_id = $encoderId;
                    }
                    if ($encoderId && empty($record->encoder_id)) {
                        $record->encoder_id = $encoderId;
                    }
                    $record->batch_code = $batchCode;

                    if ($isApprove) {
                        $record->fdp_status = 'approved';
                        $record->status = 'approved';
                        $record->fdp_approved_at = $now;
                        $record->fdp_approved_by = $approverId;
                        $record->approved_at = $now;
                        $record->approved_by = $approverId;
                        if ($level === 1) {
                            $record->approved_at_dilg_po = $now;
                            $record->approved_by_dilg_po = $approverId;
                        } else {
                            $record->approved_at_dilg_ro = $now;
                            $record->approved_by_dilg_ro = $approverId;
                        }
                        if ($remarks !== '') {
                            $record->fdp_remarks = $remarks;
                            $record->approval_remarks = $remarks;
                        }
                    } else {
                        $record->fdp_status = 'returned';
                        $record->status = 'returned';
                        $record->fdp_remarks = $remarks;
                        $record->approval_remarks = $remarks;
                        $record->fdp_approved_at = null;
                        $record->fdp_approved_by = null;
                    }

                    $record->save();

                    $this->syncIndividualProjectWorkflow($projectCode, $quarter, 'fdp', $isApprove, $level, $approverId, $remarks);
                }
            } elseif ($docKey === 'posting-link') {
                $sourceFdp = FURFDP::where('batch_code', $batchCode)->whereNotNull('posting_link')->first();
                if (!$sourceFdp && !empty($batch->project_codes)) {
                    $sourceFdp = FURFDP::where('quarter', $quarter)->whereIn('project_code', (array) $batch->project_codes)->whereNotNull('posting_link')->first();
                }

                $link = $sourceFdp ? $sourceFdp->posting_link : null;
                $uploadedAt = $sourceFdp ? ($sourceFdp->posting_uploaded_at ?? $sourceFdp->created_at) : ($batch->submitted_at ?? $now);
                $encoderId = $sourceFdp ? ($sourceFdp->posting_encoder_id ?? $sourceFdp->encoder_id) : ($batch->uploader_id ?? null);

                foreach ($projectCodes as $projectCode) {
                    $record = FURFDP::firstOrNew([
                        'project_code' => $projectCode,
                        'quarter' => $quarter,
                    ]);

                    if ($link && empty($record->posting_link)) {
                        $record->posting_link = $link;
                    }
                    if (empty($record->posting_uploaded_at)) {
                        $record->posting_uploaded_at = $uploadedAt;
                    }
                    if ($encoderId && empty($record->posting_encoder_id)) {
                        $record->posting_encoder_id = $encoderId;
                    }
                    if ($encoderId && empty($record->encoder_id)) {
                        $record->encoder_id = $encoderId;
                    }
                    $record->batch_code = $batchCode;

                    if ($isApprove) {
                        $record->posting_status = 'approved';
                        $record->posting_approved_at = $now;
                        $record->posting_approved_by = $approverId;
                        if ($level === 1) {
                            $record->posting_approved_at_dilg_po = $now;
                            $record->posting_approved_by_dilg_po = $approverId;
                        } else {
                            $record->posting_approved_at_dilg_ro = $now;
                            $record->posting_approved_by_dilg_ro = $approverId;
                        }
                        if ($remarks !== '') {
                            $record->posting_remarks = $remarks;
                        }
                    } else {
                        $record->posting_status = 'returned';
                        $record->posting_remarks = $remarks;
                        $record->posting_approved_at = null;
                        $record->posting_approved_by = null;
                    }

                    $record->save();

                    $this->syncIndividualProjectWorkflow($projectCode, $quarter, 'posting-link', $isApprove, $level, $approverId, $remarks);
                }
            } elseif ($docKey === 'batch-document') {
                $sourceDoc = FURBatchDocument::where('batch_code', $batchCode)->first();
                if (!$sourceDoc && !empty($batch->project_codes)) {
                    $sourceDoc = FURBatchDocument::where('quarter', $quarter)->whereIn('project_code', (array) $batch->project_codes)->first();
                }

                $filePath = $sourceDoc ? $sourceDoc->batch_document_file_path : null;
                $filesJson = $sourceDoc ? $sourceDoc->batch_document_files_json : null;
                $uploadedAt = $sourceDoc ? ($sourceDoc->batch_document_uploaded_at ?? $sourceDoc->created_at) : ($batch->submitted_at ?? $now);
                $encoderId = $sourceDoc ? ($sourceDoc->batch_document_encoder_id ?? $sourceDoc->encoder_id) : ($batch->uploader_id ?? null);

                foreach ($projectCodes as $projectCode) {
                    $record = FURBatchDocument::firstOrNew([
                        'project_code' => $projectCode,
                        'quarter' => $quarter,
                    ]);

                    if ($filePath && empty($record->batch_document_file_path)) {
                        $record->batch_document_file_path = $filePath;
                    }
                    if ($filesJson && empty($record->batch_document_files_json)) {
                        $record->batch_document_files_json = $filesJson;
                    }
                    if (empty($record->batch_document_uploaded_at)) {
                        $record->batch_document_uploaded_at = $uploadedAt;
                    }
                    if ($encoderId && empty($record->batch_document_encoder_id)) {
                        $record->batch_document_encoder_id = $encoderId;
                    }
                    if ($encoderId && empty($record->encoder_id)) {
                        $record->encoder_id = $encoderId;
                    }
                    $record->batch_code = $batchCode;

                    if ($isApprove) {
                        $record->status = 'approved';
                        $record->approved_at = $now;
                        $record->approved_by = $approverId;
                        if ($level === 1) {
                            $record->approved_at_dilg_po = $now;
                            $record->approved_by_dilg_po = $approverId;
                        } else {
                            $record->approved_at_dilg_ro = $now;
                            $record->approved_by_dilg_ro = $approverId;
                        }
                        if ($remarks !== '') {
                            $record->approval_remarks = $remarks;
                        }
                    } else {
                        $record->status = 'returned';
                        $record->approval_remarks = $remarks;
                        $record->approved_at = null;
                        $record->approved_by = null;
                    }

                    $record->save();

                    $this->syncIndividualProjectWorkflow($projectCode, $quarter, 'batch-document', $isApprove, $level, $approverId, $remarks);
                }
            }

            FundUtilizationBatchApprovalLog::create([
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'actor_id' => $user->getKey(),
                'action' => ucfirst($validated['action']) . ' Document',
                'approval_level' => $batch->current_approval_level,
                'previous_status' => $batch->status,
                'new_status' => $batch->status,
                'remarks' => sprintf('%s (%d project%s): %s', $config['label'], $projectCodes->count(), $projectCodes->count() === 1 ? '' : 's', $remarks ?: 'No remarks.'),
                'created_at' => now(),
            ]);
        });

        $updatedStatuses = $this->documentStatuses($batchCode, (array) ($batch->project_codes ?? []), $config['key']);
        $allBatchProjectCodes = (array) ($batch->project_codes ?? []);
        $isFullyApproved = !empty($allBatchProjectCodes) && collect($allBatchProjectCodes)->every(fn ($code) => ($updatedStatuses[$code] ?? '') === 'approved');

        return response()->json([
            'success' => true,
            'action' => $validated['action'],
            'document_key' => $validated['document_key'],
            'is_fully_approved' => $isFullyApproved,
            'message' => sprintf(
                '%s %s for %d selected project%s and saved to individual FUR profiles.',
                $config['label'],
                $validated['action'] === 'approve' ? 'approved' : 'returned',
                $projectCodes->count(),
                $projectCodes->count() === 1 ? '' : 's'
            ),
            'statuses' => $updatedStatuses,
        ]);
    }

    private function syncIndividualProjectWorkflow(
        string $projectCode,
        string $quarter,
        string $documentType,
        bool $isApprove,
        int $level,
        $approverId,
        string $remarks = ''
    ): void {
        if (!Schema::hasTable('fund_utilization_approval_workflows')) {
            return;
        }

        try {
            $wf = FundUtilizationApprovalWorkflow::where('project_code', $projectCode)
                ->where('quarter', $quarter)
                ->where('document_type', $documentType)
                ->first();

            if ($wf) {
                if ($isApprove) {
                    if ($level === 1) {
                        $wf->last_approved_level = 1;
                        $wf->current_approval_level = 2;
                        $wf->status = 'Pending Level 2 - Regional Officer';
                    } else {
                        $wf->last_approved_level = 2;
                        $wf->status = 'Approved';
                        $wf->completed_at = now();
                    }
                } else {
                    $wf->status = $level === 1 ? 'Returned by Provincial Officer' : 'Returned by Regional Officer';
                    $wf->current_approval_level = 1;
                }
                $wf->save();
            }
        } catch (\Throwable $e) {
            // Non-critical workflow sync failure shouldn't abort transaction
        }
    }

    private function authorizeScopedAction(FundUtilizationBatchApproval $batch, User $user): void
    {
        if (!str_starts_with(strtolower((string) $batch->status), 'pending')) {
            abort(response()->json(['message' => 'This batch is not currently pending validation.'], 422));
        }

        $level = (int) $batch->current_approval_level;
        $approvalLevel = $user->approvalLevel();
        $poolService = app(RegionalApprovalPoolService::class);
        if ($level === 1) {
            $province = $batch->uploader_province ?: $poolService->resolveProvinceFromOffice($batch->uploader_office);
            $allowed = $approvalLevel === 'provincial' || $user->isProvincialUser() || $user->isProvincialDilgAssignment() || $user->isSuperAdmin();
            if (!$allowed || (!$user->isSuperAdmin() && $province !== '' && !$poolService->matchesProvince($province, $user->province))) {
                abort(response()->json(['message' => 'You cannot validate batch submissions outside your assigned province.'], 403));
            }
            return;
        }

        if ($level !== 2 || !($approvalLevel === 'regional' || $user->isRegionalUser() || $user->isSuperAdmin())) {
            abort(response()->json(['message' => 'Only an authorized validator can act on this batch.'], 403));
        }
    }

    private function documentConfig(string $key): ?array
    {
        $configs = [
            'mov' => ['key' => 'mov', 'table' => 'tbfur_mov_uploads', 'label' => 'MOV', 'status_columns' => []],
            'batch-document' => ['key' => 'batch-document', 'table' => 'tbfur_batch_documents', 'label' => 'Batch Document', 'status_columns' => []],
            'fdp' => ['key' => 'fdp', 'table' => 'tbfur_fdp', 'label' => 'FDP Document', 'status_columns' => ['fdp_status']],
            'posting-link' => ['key' => 'posting-link', 'table' => 'tbfur_fdp', 'label' => 'FDP Posting Link', 'status_columns' => ['posting_status']],
        ];
        if (str_starts_with($key, 'written-notice:')) {
            $field = substr($key, strlen('written-notice:'));
            $statusField = [
                'secretary_dbm' => 'dbm_status',
                'secretary_dilg' => 'dilg_status',
                'speaker_house' => 'speaker_status',
                'president_senate' => 'president_status',
                'house_committee' => 'house_status',
                'senate_committee' => 'senate_status',
            ][$field] ?? null;
            if ($statusField === null) {
                return null;
            }
            return ['key' => $key, 'table' => 'tbfur_written_notice', 'label' => ucwords(str_replace('_', ' ', $field)) . ' Notice', 'status_columns' => [$statusField]];
        }
        return $configs[$key] ?? null;
    }

    private function documentViewPath(string $projectCode, string $quarter, string $documentType, ?int $fileIndex = null): string
    {
        $path = '/fund-utilization/' . rawurlencode($projectCode)
            . '/view-document/' . rawurlencode($documentType)
            . '/' . rawurlencode($quarter);
        if ($fileIndex !== null) {
            $path .= '?file=' . $fileIndex;
        }
        return $path;
    }

    private function writtenNoticeDocumentType(string $pathColumn): string
    {
        return match ($pathColumn) {
            'secretary_dbm_path' => 'written-notice-dbm',
            'secretary_dilg_path' => 'written-notice-dilg',
            'speaker_house_path' => 'written-notice-speaker',
            'president_senate_path' => 'written-notice-president',
            'house_committee_path' => 'written-notice-house',
            'senate_committee_path' => 'written-notice-senate',
            default => 'written-notice-dbm',
        };
    }

    private function documentStatuses(string $batchCode, array $projectCodes, string $key): array
    {
        $config = $this->documentConfig($key);
        if ($config === null) {
            return [];
        }
        $statusColumn = 'status';
        if (str_starts_with($key, 'written-notice:')) {
            $statusColumn = $this->documentConfig($key)['status_columns'][0];
        } elseif ($key === 'fdp' || $key === 'posting-link') {
            $statusColumn = $key === 'fdp' ? 'fdp_status' : 'posting_status';
        }
        if (!Schema::hasColumn($config['table'], $statusColumn)) {
            $statusColumn = 'status';
        }
        $batch = FundUtilizationBatchApproval::where('batch_code', $batchCode)->first();
        $query = DB::table($config['table'])
            ->where(function ($builder) use ($batchCode) {
                $builder->where('batch_code', $batchCode)
                    ->orWhereNull('batch_code');
            })
            ->when($batch, fn ($builder) => $builder->where('quarter', $batch->quarter))
            ->whereIn('project_code', $projectCodes)
            ->get(['project_code', $statusColumn]);
        return $query
            ->mapWithKeys(fn ($row) => [$row->project_code => strtolower((string) ($row->{$statusColumn} ?? 'pending'))])
            ->all();
    }

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
