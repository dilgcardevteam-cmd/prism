<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\InputSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class LguReportorialWorkflowService
{
    public const STATUS_PENDING_MLGOO = 'pending_mlgoo';
    public const STATUS_PENDING_PO = 'pending_po';
    public const STATUS_PENDING_RO = 'pending_ro';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RETURNED = 'returned';

    /**
     * Check if an office name represents a Provincial LGU (PLGU).
     */
    public function isPlguOffice(?string $officeName): bool
    {
        $normalized = Str::lower(trim((string) $officeName));
        if ($normalized === '') {
            return false;
        }

        return str_starts_with($normalized, 'plgu')
            || str_starts_with($normalized, 'provincial government')
            || str_contains($normalized, 'plgu ');
    }

    /**
     * Determine initial status when a document is uploaded.
     *
     * Workflow A (LGU User): Starts at C/MLGOO validation (pending_mlgoo).
     * Workflow B (C/MLGOO User): Starts at Provincial validation (pending_po).
     * Workflow C (Provincial User): Starts at Regional validation (pending_ro).
     */
    public function initialStatusForUpload($uploaderOrOffice, ?string $officeName = null, ?string $province = null): string
    {
        if ($uploaderOrOffice instanceof User) {
            $uploader = $uploaderOrOffice;
        } else {
            $uploader = auth()->user();
            $province = $officeName;
            $officeName = (string) $uploaderOrOffice;
        }

        $isPlgu = $this->isPlguOffice($officeName);

        // Workflow C: Provincial User submission starts directly at Regional validation
        if ($uploader && ($uploader->isProvincialUser() || $uploader->isProvincialDilgAssignment())) {
            return self::STATUS_PENDING_RO;
        }

        // Workflow B: C/MLGOO submitting on behalf of LGU satisfies C/MLGOO stage, starts at Provincial validation
        if ($uploader && $uploader->isMlgooUser()) {
            return self::STATUS_PENDING_PO;
        }

        // PLGU submitted by LGU user has no C/MLGOO stage, starts at Provincial validation
        if ($isPlgu) {
            return self::STATUS_PENDING_PO;
        }

        // Workflow A: Standard LGU User submission starts at C/MLGOO validation
        return self::STATUS_PENDING_MLGOO;
    }

    /**
     * Determine the initial field values for a freshly uploaded report.
     */
    public function initialFieldsForUpload($uploaderOrOffice, ?string $officeName = null, ?string $province = null): array
    {
        if ($uploaderOrOffice instanceof User) {
            $uploader = $uploaderOrOffice;
        } else {
            $uploader = auth()->user();
            $province = $officeName;
            $officeName = (string) $uploaderOrOffice;
        }

        $status = $this->initialStatusForUpload($uploader, $officeName, $province);
        $now = now();
        $actorId = $uploader ? (int) ($uploader->idno ?? $uploader->getKey()) : (int) auth()->id();

        $isProvincial = ($status === self::STATUS_PENDING_RO);
        $isMlgoo = ($status === self::STATUS_PENDING_PO && $uploader && $uploader->isMlgooUser());

        return [
            'status' => $status,
            'uploaded_by' => $actorId,
            'uploaded_at' => $now,
            'approved_at' => $isProvincial ? $now : null,
            'approved_at_c_mlgoo' => $isMlgoo ? $now : null,
            'approved_by_c_mlgoo' => $isMlgoo ? $actorId : null,
            'approved_at_dilg_po' => $isProvincial ? $now : null,
            'approved_by_dilg_po' => $isProvincial ? $actorId : null,
            'approved_at_dilg_ro' => null,
            'approved_by_dilg_ro' => null,
            'approval_remarks' => null,
            'user_remarks' => null,
        ];
    }

    /**
     * Check if the authenticated user is authorized to edit and resubmit a returned report.
     * Returned reports are editable only by the authorized originating submitter (or superadmin).
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function canUserResubmit(User $user, $document): array
    {
        if (!$user->isActive()) {
            return [
                'allowed' => false,
                'reason' => 'User account is inactive.',
            ];
        }

        $status = Str::lower(trim((string) ($document->status ?? '')));
        if ($status !== self::STATUS_RETURNED) {
            return [
                'allowed' => false,
                'reason' => 'Only returned reports can be resubmitted.',
            ];
        }

        if ($user->isSuperAdmin()) {
            return [
                'allowed' => true,
                'reason' => null,
            ];
        }

        $uploaderId = (int) ($document->uploaded_by ?? 0);
        $userId = (int) ($user->idno ?? $user->getKey());

        if ($uploaderId > 0 && $uploaderId !== $userId) {
            return [
                'allowed' => false,
                'reason' => 'Returned reports are editable only by the authorized originating submitter.',
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
        ];
    }

    /**
     * Determine the restart validation stage upon resubmission.
     * Restarts validation sequence at the initial validation stage corresponding to the original route:
     * - Workflow A (LGU origin): C/MLGOO validation (pending_mlgoo)
     * - Workflow B (C/MLGOO origin): Provincial validation (pending_po)
     * - Workflow C (Provincial origin): Regional validation (pending_ro)
     */
    public function restartStatusForResubmission($document, ?User $currentUser = null, ?string $officeName = null, ?string $province = null): string
    {
        $officeName = $officeName ?? ($document->office ?? null);
        $province = $province ?? ($document->province ?? null);

        $uploaderId = (int) ($document->uploaded_by ?? 0);
        $originalUploader = null;
        if ($uploaderId > 0) {
            if ($currentUser && (int) ($currentUser->idno ?? $currentUser->getKey()) === $uploaderId) {
                $originalUploader = $currentUser;
            } else {
                $originalUploader = User::where('idno', $uploaderId)->orWhere('id', $uploaderId)->first();
            }
        }

        if ($originalUploader) {
            return $this->initialStatusForUpload($originalUploader, $officeName, $province);
        }

        if ($currentUser) {
            return $this->initialStatusForUpload($currentUser, $officeName, $province);
        }

        return self::STATUS_PENDING_MLGOO;
    }

    /**
     * Determine field updates when a returned document is resubmitted.
     */
    public function resubmissionFields($document, User $resubmitter, ?string $officeName = null, ?string $province = null): array
    {
        $targetStatus = $this->restartStatusForResubmission($document, $resubmitter, $officeName, $province);
        $now = now();
        $actorId = (int) ($resubmitter->idno ?? $resubmitter->getKey());

        $isProvincialOrigin = ($targetStatus === self::STATUS_PENDING_RO);
        $isMlgooOrigin = ($targetStatus === self::STATUS_PENDING_PO && $resubmitter->isMlgooUser());

        return [
            'status' => $targetStatus,
            'uploaded_at' => $now,
            'approved_at' => $isProvincialOrigin ? $now : null,
            'approved_at_c_mlgoo' => $isMlgooOrigin ? $now : null,
            'approved_by_c_mlgoo' => $isMlgooOrigin ? $actorId : null,
            'approved_at_dilg_po' => $isProvincialOrigin ? $now : null,
            'approved_by_dilg_po' => $isProvincialOrigin ? $actorId : null,
            'approved_at_dilg_ro' => null,
            'approved_by_dilg_ro' => null,
            'approval_remarks' => null,
            'user_remarks' => null,
        ];
    }

    /**
     * Check whether a document or submission record has an uploaded file.
     */
    public function hasUploadedDocument($document): bool
    {
        if (!$document) {
            return false;
        }

        if (is_array($document)) {
            if (!empty($document['file_path']) && trim((string) $document['file_path']) !== '') {
                return true;
            }
            if (!empty($document['files']) && count($document['files']) > 0) {
                return true;
            }
            return false;
        }

        if ($document instanceof Model) {
            $filePath = trim((string) ($document->file_path ?? ''));
            if ($filePath !== '') {
                return true;
            }

            if ($document->relationLoaded('files')) {
                return $document->files->isNotEmpty();
            }

            if (method_exists($document, 'files') && $document->exists) {
                return $document->files()->exists();
            }
        }

        return false;
    }

    /**
     * Determine the current validation stage of a document.
     */
    public function currentStage($document, ?string $officeName = null): string
    {
        if (!$document || !$this->hasUploadedDocument($document)) {
            return 'pending_upload';
        }

        $status = Str::lower(trim((string) ($document->status ?? '')));
        $approvedAtRo = $document->approved_at_dilg_ro ?? null;
        $approvedAtPo = $document->approved_at_dilg_po ?? null;
        $approvedAtMlgoo = $document->approved_at_c_mlgoo ?? null;
        $isPlgu = $this->isPlguOffice($officeName ?? ($document->office ?? null));

        if ($status === self::STATUS_APPROVED || $approvedAtRo !== null) {
            return self::STATUS_APPROVED;
        }

        if ($status === self::STATUS_RETURNED) {
            return self::STATUS_RETURNED;
        }

        if ($status === self::STATUS_PENDING_RO || ($approvedAtPo !== null && $approvedAtRo === null)) {
            return self::STATUS_PENDING_RO;
        }

        if ($status === self::STATUS_PENDING_PO) {
            return self::STATUS_PENDING_PO;
        }

        if ($approvedAtMlgoo !== null && $approvedAtPo === null) {
            return self::STATUS_PENDING_PO;
        }

        if ($status === self::STATUS_PENDING_MLGOO) {
            return self::STATUS_PENDING_MLGOO;
        }

        // Legacy 'pending'
        if ($status === 'pending' || $status === '') {
            if ($isPlgu) {
                return self::STATUS_PENDING_PO;
            }
            return self::STATUS_PENDING_MLGOO;
        }

        return self::STATUS_PENDING_MLGOO;
    }

    /**
     * Determine whether the given user can validate or return the document at its current stage.
     * Enforces:
     * - Inactive account blocking
     * - Self-validation prevention (users cannot approve or return their own submissions)
     * - Role-specific validation stages
     * - Geographic scope restrictions (municipality/province)
     * - Sequential progression (no skipping stages)
     *
     * @return array{allowed: bool, reason: ?string, current_stage: string, target_stage: ?string}
     */
    public function canUserValidate(User $user, $document, ?string $officeName = null, ?string $province = null): array
    {
        if (!$document || !$this->hasUploadedDocument($document)) {
            return [
                'allowed' => false,
                'reason' => 'Cannot validate a submission with no uploaded document or file.',
                'current_stage' => 'pending_upload',
                'target_stage' => null,
            ];
        }

        $officeName = $officeName ?? ($document->office ?? null);
        $province = $province ?? ($document->province ?? null);
        $currentStage = $this->currentStage($document, $officeName);

        if (!$user->isActive()) {
            return [
                'allowed' => false,
                'reason' => 'User account is inactive.',
                'current_stage' => $currentStage,
                'target_stage' => null,
            ];
        }

        // Self-validation prevention: users cannot validate or return their own submissions
        $uploaderId = (int) ($document->uploaded_by ?? 0);
        $userId = (int) ($user->idno ?? $user->getKey());
        if ($uploaderId > 0 && $uploaderId === $userId) {
            return [
                'allowed' => false,
                'reason' => 'Users cannot approve or return their own submissions.',
                'current_stage' => $currentStage,
                'target_stage' => null,
            ];
        }

        if ($user->isLguUser()) {
            return [
                'allowed' => false,
                'reason' => 'LGU users cannot validate or approve submissions.',
                'current_stage' => $currentStage,
                'target_stage' => null,
            ];
        }

        if ($currentStage === self::STATUS_APPROVED) {
            return [
                'allowed' => false,
                'reason' => 'Document has already received final approval.',
                'current_stage' => $currentStage,
                'target_stage' => null,
            ];
        }

        if ($currentStage === self::STATUS_RETURNED) {
            return [
                'allowed' => false,
                'reason' => 'Document has been returned for revision and is awaiting re-upload.',
                'current_stage' => $currentStage,
                'target_stage' => null,
            ];
        }

        // SuperAdmin can oversee and validate if not the uploader
        if ($user->isSuperAdmin()) {
            $targetStage = match ($currentStage) {
                self::STATUS_PENDING_MLGOO => self::STATUS_PENDING_PO,
                self::STATUS_PENDING_PO => self::STATUS_PENDING_RO,
                self::STATUS_PENDING_RO => self::STATUS_APPROVED,
                default => null,
            };

            return [
                'allowed' => true,
                'reason' => null,
                'current_stage' => $currentStage,
                'target_stage' => $targetStage,
            ];
        }

        // Stage 1: C/MLGOO validation
        if ($currentStage === self::STATUS_PENDING_MLGOO) {
            if (!$user->isMlgooUser()) {
                if ($user->isProvincialUser()) {
                    return [
                        'allowed' => false,
                        'reason' => 'Provincial users cannot bypass the required C/MLGOO validation step.',
                        'current_stage' => $currentStage,
                        'target_stage' => null,
                    ];
                }

                if ($user->isRegionalUser()) {
                    return [
                        'allowed' => false,
                        'reason' => 'Regional users cannot bypass preceding validation stages.',
                        'current_stage' => $currentStage,
                        'target_stage' => null,
                    ];
                }

                return [
                    'allowed' => false,
                    'reason' => 'Only the assigned C/MLGOO can validate at this stage.',
                    'current_stage' => $currentStage,
                    'target_stage' => null,
                ];
            }

            if (!$user->matchesAssignedOffice($officeName)) {
                return [
                    'allowed' => false,
                    'reason' => 'C/MLGOO cannot validate submissions belonging to other municipalities or cities.',
                    'current_stage' => $currentStage,
                    'target_stage' => null,
                ];
            }

            return [
                'allowed' => true,
                'reason' => null,
                'current_stage' => $currentStage,
                'target_stage' => self::STATUS_PENDING_PO,
            ];
        }

        // Stage 2: Provincial validation
        if ($currentStage === self::STATUS_PENDING_PO) {
            if ($user->isMlgooUser()) {
                return [
                    'allowed' => false,
                    'reason' => 'C/MLGOO cannot perform provincial or regional validation.',
                    'current_stage' => $currentStage,
                    'target_stage' => null,
                ];
            }

            if ($user->isRegionalUser()) {
                return [
                    'allowed' => false,
                    'reason' => 'Regional users cannot bypass provincial validation.',
                    'current_stage' => $currentStage,
                    'target_stage' => null,
                ];
            }

            if (!$user->isProvincialUser() && !$user->isProvincialDilgAssignment()) {
                return [
                    'allowed' => false,
                    'reason' => 'Only provincial validators can validate at this stage.',
                    'current_stage' => $currentStage,
                    'target_stage' => null,
                ];
            }

            if ($province !== null && $province !== '') {
                $userProvince = $user->normalizedProvince();
                if ($userProvince !== '' && Str::lower(trim($province)) !== $userProvince) {
                    return [
                        'allowed' => false,
                        'reason' => 'Provincial users can only validate within their assigned provincial jurisdiction.',
                        'current_stage' => $currentStage,
                        'target_stage' => null,
                    ];
                }
            }

            return [
                'allowed' => true,
                'reason' => null,
                'current_stage' => $currentStage,
                'target_stage' => self::STATUS_PENDING_RO,
            ];
        }

        // Stage 3: Regional validation
        if ($currentStage === self::STATUS_PENDING_RO) {
            if ($user->isMlgooUser()) {
                return [
                    'allowed' => false,
                    'reason' => 'C/MLGOO cannot perform regional validation.',
                    'current_stage' => $currentStage,
                    'target_stage' => null,
                ];
            }

            if ($user->isProvincialUser()) {
                return [
                    'allowed' => false,
                    'reason' => 'Provincial users cannot perform regional validation.',
                    'current_stage' => $currentStage,
                    'target_stage' => null,
                ];
            }

            if (!$user->isRegionalUser() && !$user->isRegionalOfficeAssignment()) {
                return [
                    'allowed' => false,
                    'reason' => 'Only regional validators can conduct regional validation.',
                    'current_stage' => $currentStage,
                    'target_stage' => null,
                ];
            }

            return [
                'allowed' => true,
                'reason' => null,
                'current_stage' => $currentStage,
                'target_stage' => self::STATUS_APPROVED,
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'Validation is not permitted for the current status.',
            'current_stage' => $currentStage,
            'target_stage' => null,
        ];
    }

    /**
     * Process an approve or return action on a document with pessimistic row locking and database transactions.
     *
     * @return array{success: bool, action: string, status: string, message: string}
     */
    public function processValidation(
        User $user,
        Model $document,
        string $action,
        ?string $remarks = null,
        ?string $officeName = null,
        ?string $province = null
    ): array {
        if (!in_array($action, ['approve', 'return'], true)) {
            throw new InvalidArgumentException('Invalid action: ' . $action);
        }

        $officeName = $officeName ?? ($document->office ?? null);
        $province = $province ?? ($document->province ?? null);

        $check = $this->canUserValidate($user, $document, $officeName, $province);
        if (!$check['allowed']) {
            throw new RuntimeException($check['reason'] ?? 'Unauthorized validation action.');
        }

        $sanitizedRemarks = InputSanitizer::sanitizeNullablePlainText($remarks, true);
        if ($action === 'return' && ($sanitizedRemarks === null || trim($sanitizedRemarks) === '')) {
            throw new InvalidArgumentException('Return remarks must contain plain text.');
        }

        return DB::transaction(function () use ($user, $document, $action, $sanitizedRemarks, $officeName, $province) {
            // Pessimistic row locking to protect against concurrent or duplicate validation attempts
            $lockedDocument = $document->newQuery()->lockForUpdate()->find($document->getKey());
            if (!$lockedDocument) {
                throw new RuntimeException('Document not found.');
            }

            // Re-verify authorization and current stage on the locked fresh database record
            $freshCheck = $this->canUserValidate($user, $lockedDocument, $officeName, $province);
            if (!$freshCheck['allowed']) {
                throw new RuntimeException($freshCheck['reason'] ?? 'Workflow state has changed or action is no longer allowed.');
            }

            $now = now();
            $actorId = (int) ($user->idno ?? $user->getKey());
            $currentStage = $freshCheck['current_stage'];
            $updates = [];

            if ($action === 'approve') {
                if ($currentStage === self::STATUS_PENDING_MLGOO) {
                    $updates['status'] = self::STATUS_PENDING_PO;
                    $updates['approved_at_c_mlgoo'] = $now;
                    $updates['approved_by_c_mlgoo'] = $actorId;
                    $updates['approval_remarks'] = null;
                    $message = 'Submission validated at C/MLGOO level and forwarded for Provincial validation.';
                } elseif ($currentStage === self::STATUS_PENDING_PO) {
                    $updates['status'] = self::STATUS_PENDING_RO;
                    $updates['approved_at_dilg_po'] = $now;
                    $updates['approved_by_dilg_po'] = $actorId;
                    $updates['approval_remarks'] = null;
                    $message = 'Submission validated at Provincial level and forwarded for Regional validation.';
                } else {
                    $updates['status'] = self::STATUS_APPROVED;
                    $updates['approved_at_dilg_ro'] = $now;
                    $updates['approved_by_dilg_ro'] = $actorId;
                    $updates['approved_at'] = $now;
                    $updates['approval_remarks'] = null;
                    $message = 'Submission granted final Regional approval.';
                }
            } else {
                $updates['status'] = self::STATUS_RETURNED;
                $updates['approval_remarks'] = $sanitizedRemarks;
                $updates['user_remarks'] = $sanitizedRemarks;

                if ($currentStage === self::STATUS_PENDING_MLGOO) {
                    $updates['approved_by_c_mlgoo'] = $actorId;
                    $message = 'Submission returned for revision by C/MLGOO.';
                } elseif ($currentStage === self::STATUS_PENDING_PO) {
                    $updates['approved_by_dilg_po'] = $actorId;
                    $message = 'Submission returned for revision by Provincial Office.';
                } else {
                    $updates['approved_by_dilg_ro'] = $actorId;
                    $updates['approved_at_dilg_ro'] = null;
                    $message = 'Submission returned for revision by Regional Office.';
                }
            }

            // Only update columns that exist on the model table
            $table = $lockedDocument->getTable();
            $filteredUpdates = [];
            foreach ($updates as $col => $val) {
                if (Schema::hasColumn($table, $col)) {
                    $filteredUpdates[$col] = $val;
                }
            }

            $lockedDocument->update($filteredUpdates);

            // Record immutable audit history in activity_logs
            $this->recordAuditLog(
                user: $user,
                document: $lockedDocument,
                action: $action === 'approve' ? 'REPORT_VALIDATED' : 'REPORT_RETURNED',
                description: $message,
                previousStatus: $currentStage,
                resultingStatus: $updates['status'],
                remarks: $sanitizedRemarks,
                timestamp: $now
            );

            return [
                'success' => true,
                'action' => $action,
                'status' => $lockedDocument->status,
                'message' => $message,
            ];
        });
    }

    /**
     * Record an immutable audit log entry for workflow events.
     */
    public function recordAuditLog(
        User $user,
        Model $document,
        string $action,
        string $description,
        ?string $previousStatus = null,
        ?string $resultingStatus = null,
        ?string $remarks = null,
        ?Carbon $timestamp = null
    ): void {
        try {
            if (!Schema::hasTable('activity_logs')) {
                return;
            }

            $now = $timestamp ?: now();
            $actorId = (int) ($user->idno ?? $user->getKey());
            $table = $document->getTable();
            $docId = (int) $document->getKey();

            $cycle = DB::table('activity_logs')
                ->where('properties->report_table', $table)
                ->where('properties->report_id', $docId)
                ->where('action', 'REPORT_RETURNED')
                ->count() + 1;

            ActivityLog::create([
                'user_id' => $actorId,
                'username' => $user->username ?? $user->email ?? ('user_' . $actorId),
                'action' => $action,
                'description' => $description,
                'properties' => [
                    'report_table' => $table,
                    'report_id' => $docId,
                    'office' => $document->office ?? null,
                    'province' => $document->province ?? null,
                    'actor_role' => $user->role,
                    'cycle' => $cycle,
                    'previous_status' => $previousStatus,
                    'resulting_status' => $resultingStatus,
                    'remarks' => $remarks,
                ],
                'created_at' => $now,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record activity log in workflow service: ' . $e->getMessage());
        }
    }

    /**
     * Record submission or resubmission event in the audit trail.
     */
    public function recordSubmissionAuditLog(
        User $user,
        Model $document,
        bool $isResubmission = false,
        ?string $officeName = null,
        ?string $province = null
    ): void {
        $action = $isResubmission ? 'REPORT_RESUBMITTED' : 'REPORT_SUBMITTED';
        $desc = $isResubmission
            ? 'Report resubmitted by originating submitter.'
            : 'Report submitted.';

        $this->recordAuditLog(
            user: $user,
            document: $document,
            action: $action,
            description: $desc,
            previousStatus: $isResubmission ? self::STATUS_RETURNED : null,
            resultingStatus: $document->status,
            remarks: null
        );
    }

    /**
     * Retrieve the audit history for a document.
     */
    public function getValidationHistory(Model $document): array
    {
        if (!Schema::hasTable('activity_logs')) {
            return [];
        }

        $table = $document->getTable();
        $docId = (int) $document->getKey();

        return DB::table('activity_logs')
            ->where('properties->report_table', $table)
            ->where('properties->report_id', $docId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($row) {
                $props = is_string($row->properties) ? json_decode($row->properties, true) : (array) $row->properties;
                return [
                    'id' => $row->id,
                    'user_id' => $row->user_id,
                    'username' => $row->username,
                    'action' => $row->action,
                    'description' => $row->description,
                    'cycle' => $props['cycle'] ?? 1,
                    'actor_role' => $props['actor_role'] ?? null,
                    'previous_status' => $props['previous_status'] ?? null,
                    'resulting_status' => $props['resulting_status'] ?? null,
                    'remarks' => $props['remarks'] ?? null,
                    'created_at' => $row->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Resolves human-readable status badge data for any reportorial document.
     */
    public function resolveStatusBadge($document, ?string $officeName = null): array
    {
        if (!$document || !$this->hasUploadedDocument($document)) {
            return [
                'label' => 'Pending Upload',
                'stage' => 'pending_upload',
                'badgeBg' => '#6b7280',
                'badgeColor' => '#ffffff',
                'color' => '#6b7280',
                'icon' => 'fa-hourglass-start',
            ];
        }

        $stage = $this->currentStage($document, $officeName);

        return match ($stage) {
            self::STATUS_APPROVED => [
                'label' => 'Approved',
                'stage' => self::STATUS_APPROVED,
                'badgeBg' => '#059669',
                'badgeColor' => '#ffffff',
                'color' => '#059669',
                'icon' => 'fa-check-circle',
            ],
            self::STATUS_RETURNED => [
                'label' => 'Returned',
                'stage' => self::STATUS_RETURNED,
                'badgeBg' => '#dc2626',
                'badgeColor' => '#ffffff',
                'color' => '#dc2626',
                'icon' => 'fa-undo',
            ],
            self::STATUS_PENDING_RO => [
                'label' => 'For DILG Regional Office Validation',
                'stage' => self::STATUS_PENDING_RO,
                'badgeBg' => '#eab308',
                'badgeColor' => '#ffffff',
                'color' => '#eab308',
                'icon' => 'fa-clock',
            ],
            self::STATUS_PENDING_PO => [
                'label' => 'For DILG Provincial Office Validation',
                'stage' => self::STATUS_PENDING_PO,
                'badgeBg' => '#3b82f6',
                'badgeColor' => '#ffffff',
                'color' => '#3b82f6',
                'icon' => 'fa-clock',
            ],
            self::STATUS_PENDING_MLGOO => [
                'label' => 'For C/MLGOO Validation',
                'stage' => self::STATUS_PENDING_MLGOO,
                'badgeBg' => '#8b5cf6',
                'badgeColor' => '#ffffff',
                'color' => '#8b5cf6',
                'icon' => 'fa-clock',
            ],
            default => [
                'label' => 'Pending Upload',
                'stage' => 'pending_upload',
                'badgeBg' => '#6b7280',
                'badgeColor' => '#ffffff',
                'color' => '#6b7280',
                'icon' => 'fa-hourglass-start',
            ],
        };
    }
}
