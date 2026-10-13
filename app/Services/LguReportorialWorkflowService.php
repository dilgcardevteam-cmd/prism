<?php

namespace App\Services;

use App\Models\User;
use App\Support\InputSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        if ($isPlgu) {
            if ($uploader && ($uploader->isProvincialUser() || $uploader->isProvincialDilgAssignment())) {
                return self::STATUS_PENDING_RO;
            }
            return self::STATUS_PENDING_PO;
        }

        // Municipal/City office
        if ($uploader && $uploader->isMlgooUser()) {
            // C/MLGOO submitting on behalf of LGU satisfies C/MLGOO stage
            return self::STATUS_PENDING_PO;
        }

        return self::STATUS_PENDING_MLGOO;
    }

    /**
     * Determine the current validation stage of a document.
     */
    public function currentStage($document, ?string $officeName = null): string
    {
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
     *
     * @return array{allowed: bool, reason: ?string, current_stage: string, target_stage: ?string}
     */
    public function canUserValidate(User $user, $document, ?string $officeName = null, ?string $province = null): array
    {
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

        // Supervisor can oversee and validate at any stage
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
     * Process an approve or return action on a document.
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

        $check = $this->canUserValidate($user, $document, $officeName, $province);
        if (!$check['allowed']) {
            throw new RuntimeException($check['reason'] ?? 'Unauthorized validation action.');
        }

        $sanitizedRemarks = InputSanitizer::sanitizeNullablePlainText($remarks, true);
        if ($action === 'return' && $sanitizedRemarks === null) {
            throw new InvalidArgumentException('Return remarks must contain plain text.');
        }

        return DB::transaction(function () use ($user, $document, $action, $sanitizedRemarks, $check) {
            $now = now();
            $actorId = (int) ($user->idno ?? $user->getKey());
            $currentStage = $check['current_stage'];
            $updates = [];

            if ($action === 'approve') {
                $targetStage = $check['target_stage'];

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
            $table = $document->getTable();
            $filteredUpdates = [];
            foreach ($updates as $col => $val) {
                if (\Illuminate\Support\Facades\Schema::hasColumn($table, $col)) {
                    $filteredUpdates[$col] = $val;
                }
            }

            $document->update($filteredUpdates);

            return [
                'success' => true,
                'action' => $action,
                'status' => $document->status,
                'message' => $message,
            ];
        });
    }

    /**
     * Resolves human-readable status badge data for any reportorial document.
     */
    public function resolveStatusBadge($document, ?string $officeName = null): array
    {
        $stage = $this->currentStage($document, $officeName);

        return match ($stage) {
            self::STATUS_APPROVED => [
                'label' => 'Approved',
                'stage' => self::STATUS_APPROVED,
                'badgeBg' => '#059669',
                'badgeColor' => '#ffffff',
                'icon' => 'fa-check-circle',
            ],
            self::STATUS_RETURNED => [
                'label' => 'Returned',
                'stage' => self::STATUS_RETURNED,
                'badgeBg' => '#dc2626',
                'badgeColor' => '#ffffff',
                'icon' => 'fa-undo',
            ],
            self::STATUS_PENDING_RO => [
                'label' => 'For DILG Regional Office Validation',
                'stage' => self::STATUS_PENDING_RO,
                'badgeBg' => '#eab308',
                'badgeColor' => '#ffffff',
                'icon' => 'fa-clock',
            ],
            self::STATUS_PENDING_PO => [
                'label' => 'For DILG Provincial Office Validation',
                'stage' => self::STATUS_PENDING_PO,
                'badgeBg' => '#3b82f6',
                'badgeColor' => '#ffffff',
                'icon' => 'fa-clock',
            ],
            self::STATUS_PENDING_MLGOO => [
                'label' => 'For C/MLGOO Validation',
                'stage' => self::STATUS_PENDING_MLGOO,
                'badgeBg' => '#8b5cf6',
                'badgeColor' => '#ffffff',
                'icon' => 'fa-clock',
            ],
            default => [
                'label' => 'Pending Upload',
                'stage' => 'pending_upload',
                'badgeBg' => '#9ca3af',
                'badgeColor' => '#ffffff',
                'icon' => 'fa-hourglass-start',
            ],
        };
    }
}
