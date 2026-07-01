<?php

namespace App\Services;

use App\Models\ApprovalLog;
use App\Models\FundUtilizationApprovalWorkflow;
use App\Models\FundUtilizationReport;
use App\Models\User;
use App\Notifications\FundUtilizationWorkflowNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class FundUtilizationWorkflowService
{
    private const ACTION_SUBMITTED = 'Submitted';
    private const ACTION_RESUBMITTED = 'Resubmitted';
    private const ACTION_FORWARDED = 'Forwarded';
    private const ACTION_APPROVED = 'Approved';
    private const ACTION_RETURNED = 'Returned';

    public function __construct(
        protected FundUtilizationWorkflowRoutingService $routingService,
    ) {
    }

    public function submitOrResubmit(
        FundUtilizationReport $report,
        string $quarter,
        string $documentType,
        Model $record,
        User $uploader,
    ): FundUtilizationApprovalWorkflow {
        return DB::transaction(function () use ($report, $quarter, $documentType, $record, $uploader): FundUtilizationApprovalWorkflow {
            $projectCode = $report->project_code;

            $workflow = FundUtilizationApprovalWorkflow::query()
                ->where('project_code', $projectCode)
                ->where('quarter', $quarter)
                ->where('document_type', $documentType)
                ->first();

            $isResubmission = $workflow !== null;
            $previousStatus = $workflow?->status;

            if ($workflow && $workflow->status === 'Approved') {
                throw new RuntimeException('This submission is already fully approved and cannot be resubmitted.');
            }

            if ($workflow && !$this->isReturnedStatus($workflow->status)) {
                throw new RuntimeException('This submission is already under validation and cannot be resubmitted yet.');
            }

            if (!$workflow) {
                $workflow = new FundUtilizationApprovalWorkflow();
                $workflow->project_code = $projectCode;
                $workflow->quarter = $quarter;
                $workflow->document_type = $documentType;
                $workflow->uploader_id = $uploader->getKey();
                $workflow->uploader_role = $uploader->normalizedRole();
                $workflow->revision_number = 1;
            } else {
                $workflow->revision_number = ($workflow->revision_number ?? 1) + 1;
            }

            $targetLevel = $this->entryLevelForUploader($workflow, $uploader);
            $validator = $this->resolveValidatorForLevel($report, $uploader, $targetLevel);

            $workflow->current_approval_level = $targetLevel;
            $workflow->current_approver_id = $validator->getKey();
            $workflow->current_approver_role = $validator->normalizedRole();
            $workflow->status = $this->pendingStatusForLevel($workflow, $targetLevel);
            $workflow->returned_from_level = null;
            $workflow->submitted_at = $workflow->submitted_at ?? now();
            $workflow->completed_at = null;
            $workflow->last_approved_level = 0;
            $workflow->save();
            $this->syncRecordAfterSubmission($record, $documentType);

            $this->logWorkflowAction(
                workflow: $workflow,
                actor: $uploader,
                action: $isResubmission ? self::ACTION_RESUBMITTED : self::ACTION_SUBMITTED,
                approvalLevel: $targetLevel,
                previousStatus: $previousStatus,
                newStatus: $workflow->status,
                remarks: null,
                returnedToId: $validator->getKey(),
                forwardedToId: $validator->getKey(),
            );

            DB::afterCommit(function () use ($validator, $report, $quarter, $documentType, $uploader, $workflow): void {
                $this->notifyUser(
                    $validator,
                    sprintf(
                        'A fund utilization %s for %s (%s) is pending your approval.',
                        str_replace('-', ' ', $documentType),
                        $report->project_code,
                        $quarter
                    ),
                    $report,
                    $quarter,
                    $documentType,
                    $uploader
                );
            });

            return $workflow->fresh(['uploader', 'currentApprover', 'logs']);
        });
    }

    public function approve(
        FundUtilizationReport $report,
        string $quarter,
        string $documentType,
        Model $record,
        User $actor,
    ): FundUtilizationApprovalWorkflow {
        return DB::transaction(function () use ($report, $quarter, $documentType, $record, $actor): FundUtilizationApprovalWorkflow {
            $projectCode = $report->project_code;

            $workflow = $this->workflowFor($projectCode, $quarter, $documentType);
            if (!$workflow) {
                throw new RuntimeException('Workflow not found for this submission.');
            }

            $this->assertActorCanAct($workflow, $actor);

            $currentLevel = (int) ($workflow->current_approval_level ?? 0);
            if (
                $workflow->uploader_role === User::ROLE_LGU
                && $workflow->status === 'Returned by Regional Officer'
                && $currentLevel === 1
            ) {
                throw new RuntimeException('This returned submission must be sent back to the LGU user for revision.');
            }

            $nextLevel = $this->nextLevelForWorkflow($workflow, $currentLevel);
            $previousStatus = $workflow->status;

            if ($nextLevel !== null) {
                $nextValidator = $this->resolveValidatorForLevel($report, $workflow->uploader, $nextLevel);
                $workflow->last_approved_level = $currentLevel;
                $workflow->current_approval_level = $nextLevel;
                $workflow->current_approver_id = $nextValidator->getKey();
                $workflow->current_approver_role = $nextValidator->normalizedRole();
                $workflow->status = $this->pendingStatusForLevel($workflow, $nextLevel);
            } else {
                $workflow->last_approved_level = $currentLevel;
                $workflow->status = 'Approved';
                $workflow->current_approver_id = null;
                $workflow->current_approver_role = null;
                $workflow->current_approval_level = null;
                $workflow->completed_at = now();
            }

            $workflow->returned_from_level = null;

            $workflow->save();
            $this->syncRecordAfterApproval($record, $documentType, $currentLevel, $actor, $nextLevel === null);

            $this->logWorkflowAction(
                workflow: $workflow,
                actor: $actor,
                action: $nextLevel !== null ? self::ACTION_FORWARDED : self::ACTION_APPROVED,
                approvalLevel: $currentLevel,
                previousStatus: $previousStatus,
                newStatus: $workflow->status,
                remarks: null,
                returnedToId: $nextLevel !== null ? $workflow->current_approver_id : null,
                forwardedToId: $nextLevel !== null ? $workflow->current_approver_id : null,
            );

            DB::afterCommit(function () use ($workflow, $report, $quarter, $documentType, $nextLevel, $actor): void {
                if ($nextLevel !== null) {
                    $this->notifyUser(
                        $workflow->currentApprover,
                        sprintf(
                            'A fund utilization %s for %s (%s) has been forwarded to you for approval.',
                            str_replace('-', ' ', $documentType),
                            $report->project_code,
                            $quarter
                        ),
                        $report,
                        $quarter,
                        $documentType,
                        $actor
                    );

                    return;
                }

                $this->notifyUser(
                    $workflow->uploader,
                    sprintf(
                        'Your fund utilization %s for %s (%s) has been approved.',
                        str_replace('-', ' ', $documentType),
                        $report->project_code,
                        $quarter
                    ),
                    $report,
                    $quarter,
                    $documentType,
                    $actor
                );
            });

            return $workflow->fresh(['uploader', 'currentApprover', 'logs']);
        });
    }

    public function returnForRevision(
        FundUtilizationReport $report,
        string $quarter,
        string $documentType,
        Model $record,
        User $actor,
        string $remarks,
    ): FundUtilizationApprovalWorkflow {
        return DB::transaction(function () use ($report, $quarter, $documentType, $record, $actor, $remarks): FundUtilizationApprovalWorkflow {
            $projectCode = $report->project_code;
            $remarks = trim((string) $remarks);

            if ($remarks === '') {
                throw new RuntimeException('Remarks are mandatory when returning a submission for revision.');
            }

            $workflow = $this->workflowFor($projectCode, $quarter, $documentType);
            if (!$workflow) {
                throw new RuntimeException('Workflow not found for this submission.');
            }

            $this->assertActorCanAct($workflow, $actor);

            $currentLevel = (int) ($workflow->current_approval_level ?? 0);
            $uploaderRole = $workflow->uploader_role;
            $previousStatus = $workflow->status;
            $actorRole = $actor->normalizedRole();
            $isLguUploader = $uploaderRole === User::ROLE_LGU;
            $isProvincialUploader = $uploaderRole === User::ROLE_PROVINCIAL;
            $isRegionalActor = $actorRole === User::ROLE_REGIONAL || $actor->isRegionalOfficeAssignment();
            $isProvincialActor = $actorRole === User::ROLE_PROVINCIAL;

            if ($isLguUploader && $currentLevel === 2 && $isRegionalActor) {
                $provincialOfficer = $this->resolveValidatorForLevel($report, $workflow->uploader, 1);
                $workflow->status = 'Returned by Regional Officer';
                $workflow->current_approval_level = 1;
                $workflow->current_approver_id = $provincialOfficer->getKey();
                $workflow->current_approver_role = $provincialOfficer->normalizedRole();
                $workflow->returned_from_level = 2;
                $workflow->save();

                $this->logWorkflowAction(
                    workflow: $workflow,
                    actor: $actor,
                    action: self::ACTION_RETURNED,
                    approvalLevel: $currentLevel,
                    previousStatus: $previousStatus,
                    newStatus: $workflow->status,
                    remarks: $remarks,
                    returnedToId: $provincialOfficer->getKey(),
                    forwardedToId: $provincialOfficer->getKey(),
                );

                DB::afterCommit(function () use ($provincialOfficer, $report, $quarter, $documentType, $actor, $workflow): void {
                    $this->notifyUser(
                        $provincialOfficer,
                        sprintf(
                            'A fund utilization %s for %s (%s) was returned by the Regional Office and requires your review.',
                            str_replace('-', ' ', $documentType),
                            $report->project_code,
                            $quarter
                        ),
                        $report,
                        $quarter,
                        $documentType,
                        $actor
                    );
                });

                return $workflow->fresh(['uploader', 'currentApprover', 'logs']);
            }

            if ($isLguUploader && $currentLevel === 1 && $isProvincialActor) {
                $workflow->status = 'Returned by Provincial Officer';
                $workflow->current_approval_level = null;
                $workflow->current_approver_id = null;
                $workflow->current_approver_role = null;
                $workflow->returned_from_level = 1;
                $workflow->save();

                $this->logWorkflowAction(
                    workflow: $workflow,
                    actor: $actor,
                    action: self::ACTION_RETURNED,
                    approvalLevel: $currentLevel,
                    previousStatus: $previousStatus,
                    newStatus: $workflow->status,
                    remarks: $remarks,
                    returnedToId: $workflow->uploader_id,
                    forwardedToId: $workflow->uploader_id,
                );

                DB::afterCommit(function () use ($workflow, $report, $quarter, $documentType, $actor): void {
                    $this->notifyUser(
                        $workflow->uploader,
                        sprintf(
                            'Your fund utilization %s for %s (%s) was returned by the Provincial Office.',
                            str_replace('-', ' ', $documentType),
                            $report->project_code,
                            $quarter
                        ),
                        $report,
                        $quarter,
                        $documentType,
                        $actor
                    );
                });

                return $workflow->fresh(['uploader', 'currentApprover', 'logs']);
            }

            if ($isLguUploader && $currentLevel === 1 && $isRegionalActor && $workflow->status !== 'Returned by Regional Officer') {
                $workflow->status = 'Returned by Regional Officer';
                $workflow->current_approval_level = 1;
                $workflow->current_approver_id = $this->resolveValidatorForLevel($report, $workflow->uploader, 1)->getKey();
                $workflow->current_approver_role = User::ROLE_PROVINCIAL;
                $workflow->returned_from_level = 2;
                $workflow->save();

                $this->logWorkflowAction(
                    workflow: $workflow,
                    actor: $actor,
                    action: self::ACTION_RETURNED,
                    approvalLevel: $currentLevel,
                    previousStatus: $previousStatus,
                    newStatus: $workflow->status,
                    remarks: $remarks,
                    returnedToId: $workflow->current_approver_id,
                    forwardedToId: $workflow->current_approver_id,
                );

                DB::afterCommit(function () use ($workflow, $report, $quarter, $documentType, $actor): void {
                    $this->notifyUser(
                        $workflow->currentApprover,
                        sprintf(
                            'A fund utilization %s for %s (%s) was returned by the Regional Office for provincial review.',
                            str_replace('-', ' ', $documentType),
                            $report->project_code,
                            $quarter
                        ),
                        $report,
                        $quarter,
                        $documentType,
                        $actor
                    );
                });

                return $workflow->fresh(['uploader', 'currentApprover', 'logs']);
            }

            if ($isProvincialUploader && $currentLevel === 2 && $isRegionalActor) {
                $workflow->status = 'Returned by Regional Officer';
                $workflow->current_approval_level = null;
                $workflow->current_approver_id = null;
                $workflow->current_approver_role = null;
                $workflow->returned_from_level = 2;
                $workflow->save();

                $this->logWorkflowAction(
                    workflow: $workflow,
                    actor: $actor,
                    action: self::ACTION_RETURNED,
                    approvalLevel: $currentLevel,
                    previousStatus: $previousStatus,
                    newStatus: $workflow->status,
                    remarks: $remarks,
                    returnedToId: $workflow->uploader_id,
                    forwardedToId: $workflow->uploader_id,
                );

                DB::afterCommit(function () use ($workflow, $report, $quarter, $documentType, $actor): void {
                    $this->notifyUser(
                        $workflow->uploader,
                        sprintf(
                            'Your fund utilization %s for %s (%s) was returned by the Regional Office for revision.',
                            str_replace('-', ' ', $documentType),
                            $report->project_code,
                            $quarter
                        ),
                        $report,
                        $quarter,
                        $documentType,
                        $actor
                    );
                });

                return $workflow->fresh(['uploader', 'currentApprover', 'logs']);
            }

            throw new RuntimeException('This submission cannot be returned by the selected user in its current state.');
        });
    }

    public function canActorValidate(FundUtilizationApprovalWorkflow $workflow, User $actor): bool
    {
        try {
            $this->assertActorCanAct($workflow, $actor);
            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function canActorReturn(FundUtilizationApprovalWorkflow $workflow, User $actor): bool
    {
        return $this->canActorValidate($workflow, $actor);
    }

    protected function assertActorCanAct(FundUtilizationApprovalWorkflow $workflow, User $actor): void
    {
        if ($workflow->status === 'Approved') {
            throw new RuntimeException('This submission is already fully approved and cannot be modified.');
        }

        if ((int) $workflow->uploader_id === (int) $actor->getKey()) {
            throw new RuntimeException('You cannot act on your own submission.');
        }

        if (
            (int) ($workflow->current_approver_id ?? 0) !== (int) $actor->getKey()
            && !$this->routingService->userCanActOnWorkflowLevel($workflow, $actor)
        ) {
            throw new RuntimeException('You are not allowed to act on this submission.');
        }
    }

    public function isReturnedStatus(?string $status): bool
    {
        $status = strtolower(trim((string) $status));
        return str_contains($status, 'returned');
    }

    public function workflowFor(string $projectCode, string $quarter, string $documentType): ?FundUtilizationApprovalWorkflow
    {
        return FundUtilizationApprovalWorkflow::query()
            ->where('project_code', $projectCode)
            ->where('quarter', $quarter)
            ->where('document_type', $documentType)
            ->first();
    }

    protected function entryLevelForUploader(FundUtilizationApprovalWorkflow $workflow, User $uploader): int
    {
        if ($workflow->uploader_role === User::ROLE_PROVINCIAL || $uploader->normalizedRole() === User::ROLE_PROVINCIAL) {
            return 2;
        }

        return 1;
    }

    protected function pendingStatusForLevel(FundUtilizationApprovalWorkflow $workflow, int $level): string
    {
        $levelConfig = $this->levelConfigForUploaderRole($workflow->uploader_role, $level);

        return $levelConfig['pending_status'] ?? sprintf('Pending Level %d Approval', $level);
    }

    protected function levelConfigForUploaderRole(?string $uploaderRole, int $level): array
    {
        $chainKey = $uploaderRole === User::ROLE_PROVINCIAL ? 'provincial' : 'lgu';
        $chain = config('fund_utilization_workflow.uploader_chains.' . $chainKey, []);

        return collect($chain)->firstWhere('level', $level) ?? [];
    }

    protected function nextLevelForWorkflow(FundUtilizationApprovalWorkflow $workflow, int $currentLevel): ?int
    {
        $chainKey = $workflow->uploader_role === User::ROLE_PROVINCIAL ? 'provincial' : 'lgu';
        $chain = collect(config('fund_utilization_workflow.uploader_chains.' . $chainKey, []));

        $nextLevel = $currentLevel + 1;
        if (!$chain->contains('level', $nextLevel)) {
            return null;
        }

        return $nextLevel;
    }

    protected function resolveValidatorForLevel(FundUtilizationReport $report, User $uploader, int $level): User
    {
        $validator = $this->routingService->getValidatorForLevel($report->project_code, $level, $uploader);

        if (!$validator instanceof User) {
            throw new RuntimeException(sprintf('No validator is configured for level %d.', $level));
        }

        return $validator;
    }

    protected function notifyUser(?User $user, string $message, FundUtilizationReport $report, string $quarter, string $documentType, User $actor): void
    {
        if (!$user) {
            return;
        }

        Notification::send($user, new FundUtilizationWorkflowNotification(
            $message,
            url()->current(),
            $documentType,
            $quarter,
            (int) $actor->getKey(),
            trim($actor->fullName())
        ));
    }

    protected function logWorkflowAction(
        FundUtilizationApprovalWorkflow $workflow,
        User $actor,
        string $action,
        int $approvalLevel,
        ?string $previousStatus,
        ?string $newStatus,
        ?string $remarks,
        ?int $returnedToId = null,
        ?int $forwardedToId = null,
    ): ApprovalLog {
        return ApprovalLog::create([
            'submission_id' => $workflow->id,
            'project_code' => $workflow->project_code,
            'quarter' => $workflow->quarter,
            'document_type' => $workflow->document_type,
            'approver_id' => $actor->getKey(),
            'uploader_id' => $workflow->uploader_id,
            'approval_level' => $approvalLevel,
            'action' => $action,
            'remarks' => $remarks,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'returned_to_id' => $returnedToId,
            'forwarded_to_id' => $forwardedToId,
            'revision_number' => $workflow->revision_number,
        ]);
    }

    protected function syncRecordAfterSubmission(Model $record, string $documentType): void
    {
        $fieldMap = $this->documentFieldMap($documentType);
        if ($fieldMap === []) {
            return;
        }

        $statusField = $fieldMap['status'] ?? null;
        if ($statusField) {
            $record->setAttribute($statusField, 'pending');
        }

        $remarksField = $fieldMap['remarks'] ?? null;
        if ($remarksField) {
            $record->setAttribute($remarksField, $record->getAttribute($remarksField));
        }

        $record->save();
    }

    protected function syncRecordAfterApproval(
        Model $record,
        string $documentType,
        int $approvalLevel,
        User $actor,
        bool $isFinalApproval,
    ): void {
        $fieldMap = $this->documentFieldMap($documentType);
        if ($fieldMap === []) {
            return;
        }

        $approvedAt = now();

        if ($approvalLevel === 1) {
            $this->fillRecordAttributes($record, [
                $fieldMap['status'] ?? null => 'pending',
                $fieldMap['po_approved_at'] ?? null => $approvedAt,
                $fieldMap['po_approved_by'] ?? null => $actor->getKey(),
            ]);
        }

        if ($approvalLevel >= 2) {
            $this->fillRecordAttributes($record, [
                $fieldMap['ro_approved_at'] ?? null => $approvedAt,
                $fieldMap['ro_approved_by'] ?? null => $actor->getKey(),
            ]);
        }

        if ($isFinalApproval) {
            $this->fillRecordAttributes($record, [
                $fieldMap['status'] ?? null => 'approved',
                $fieldMap['approved_at'] ?? null => $approvedAt,
                $fieldMap['approved_by'] ?? null => $actor->getKey(),
            ]);
        }

        $record->save();
    }

    protected function fillRecordAttributes(Model $record, array $attributes): void
    {
        foreach ($attributes as $field => $value) {
            if (!$field) {
                continue;
            }

            $record->setAttribute($field, $value);
        }
    }

    protected function documentFieldMap(string $documentType): array
    {
        return match ($documentType) {
            'mov' => [
                'status' => 'status',
                'remarks' => 'approval_remarks',
                'approved_at' => 'approved_at',
                'approved_by' => 'approved_by',
                'po_approved_at' => 'approved_at_dilg_po',
                'po_approved_by' => 'approved_by_dilg_po',
                'ro_approved_at' => 'approved_at_dilg_ro',
                'ro_approved_by' => 'approved_by_dilg_ro',
            ],
            'batch-document' => [
                'status' => 'status',
                'remarks' => 'approval_remarks',
                'approved_at' => 'approved_at',
                'approved_by' => 'approved_by',
                'po_approved_at' => 'approved_at_dilg_po',
                'po_approved_by' => 'approved_by_dilg_po',
                'ro_approved_at' => 'approved_at_dilg_ro',
                'ro_approved_by' => 'approved_by_dilg_ro',
            ],
            'written-notice-dbm' => [
                'status' => 'dbm_status',
                'remarks' => 'dbm_remarks',
                'approved_at' => 'dbm_approved_at',
                'approved_by' => 'dbm_approved_by',
                'po_approved_at' => 'dbm_approved_at_dilg_po',
                'po_approved_by' => 'dbm_approved_by_dilg_po',
                'ro_approved_at' => 'dbm_approved_at_dilg_ro',
                'ro_approved_by' => 'dbm_approved_by_dilg_ro',
            ],
            'written-notice-dilg' => [
                'status' => 'dilg_status',
                'remarks' => 'dilg_remarks',
                'approved_at' => 'dilg_approved_at',
                'approved_by' => 'dilg_approved_by',
                'po_approved_at' => 'dilg_approved_at_dilg_po',
                'po_approved_by' => 'dilg_approved_by_dilg_po',
                'ro_approved_at' => 'dilg_approved_at_dilg_ro',
                'ro_approved_by' => 'dilg_approved_by_dilg_ro',
            ],
            'written-notice-speaker' => [
                'status' => 'speaker_status',
                'remarks' => 'speaker_remarks',
                'approved_at' => 'speaker_approved_at',
                'approved_by' => 'speaker_approved_by',
                'po_approved_at' => 'speaker_approved_at_dilg_po',
                'po_approved_by' => 'speaker_approved_by_dilg_po',
                'ro_approved_at' => 'speaker_approved_at_dilg_ro',
                'ro_approved_by' => 'speaker_approved_by_dilg_ro',
            ],
            'written-notice-president' => [
                'status' => 'president_status',
                'remarks' => 'president_remarks',
                'approved_at' => 'president_approved_at',
                'approved_by' => 'president_approved_by',
                'po_approved_at' => 'president_approved_at_dilg_po',
                'po_approved_by' => 'president_approved_by_dilg_po',
                'ro_approved_at' => 'president_approved_at_dilg_ro',
                'ro_approved_by' => 'president_approved_by_dilg_ro',
            ],
            'written-notice-house' => [
                'status' => 'house_status',
                'remarks' => 'house_remarks',
                'approved_at' => 'house_approved_at',
                'approved_by' => 'house_approved_by',
                'po_approved_at' => 'house_approved_at_dilg_po',
                'po_approved_by' => 'house_approved_by_dilg_po',
                'ro_approved_at' => 'house_approved_at_dilg_ro',
                'ro_approved_by' => 'house_approved_by_dilg_ro',
            ],
            'written-notice-senate' => [
                'status' => 'senate_status',
                'remarks' => 'senate_remarks',
                'approved_at' => 'senate_approved_at',
                'approved_by' => 'senate_approved_by',
                'po_approved_at' => 'senate_approved_at_dilg_po',
                'po_approved_by' => 'senate_approved_by_dilg_po',
                'ro_approved_at' => 'senate_approved_at_dilg_ro',
                'ro_approved_by' => 'senate_approved_by_dilg_ro',
            ],
            'fdp' => [
                'status' => 'fdp_status',
                'remarks' => 'fdp_remarks',
                'approved_at' => 'fdp_approved_at',
                'approved_by' => 'fdp_approved_by',
                'po_approved_at' => 'approved_at_dilg_po',
                'po_approved_by' => 'approved_by_dilg_po',
                'ro_approved_at' => 'approved_at_dilg_ro',
                'ro_approved_by' => 'approved_by_dilg_ro',
            ],
            'posting-link' => [
                'status' => 'posting_status',
                'remarks' => 'posting_remarks',
                'approved_at' => 'posting_approved_at',
                'approved_by' => 'posting_approved_by',
                'po_approved_at' => 'posting_approved_at_dilg_po',
                'po_approved_by' => 'posting_approved_by_dilg_po',
                'ro_approved_at' => 'posting_approved_at_dilg_ro',
                'ro_approved_by' => 'posting_approved_by_dilg_ro',
            ],
            default => [],
        };
    }
}
