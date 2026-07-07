@extends('layouts.dashboard')

@section('title', 'Fund Utilization Report - ' . $report->project_code)
@section('page-title', 'Fund Utilization Report Details')

@section('content')
    <div class="ops-detail-page">
    <style>
        .ops-detail-page .ops-upload-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 12px !important;
            border: 1.5px dashed #9fb2d4 !important;
            border-radius: 10px !important;
            font-size: 12px !important;
            line-height: 1.4;
            color: #1f2937;
            background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%) !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .ops-detail-page .ops-upload-input:focus {
            outline: none;
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .ops-detail-page .ops-upload-input.drag-active {
            border-color: #1d4ed8 !important;
            background: #e8f0ff !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .ops-detail-page .ops-upload-input.is-disabled {
            cursor: not-allowed;
            opacity: 0.65;
            background: #f3f4f6 !important;
            border-style: solid !important;
        }

        .ops-detail-page .ops-upload-input::-webkit-file-upload-button {
            margin-right: 10px;
            border: none;
            border-radius: 999px;
            padding: 6px 12px;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.02em;
            color: #1e3a8a;
            background: #dbeafe;
            cursor: pointer;
        }

        .ops-detail-page .ops-upload-submit {
            background: linear-gradient(135deg, #059669, #047857) !important;
            box-shadow: 0 8px 14px rgba(5, 150, 105, 0.2);
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .ops-detail-page .ops-upload-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 11px 18px rgba(5, 150, 105, 0.28);
            filter: brightness(1.03);
        }

        .ops-detail-page .ops-upload-filename {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #f8fafc;
            color: #334155;
            font-size: 11px;
            font-weight: 600;
        }

        .ops-detail-page .ops-upload-filename.has-file {
            border-color: #86efac;
            background: #ecfdf3;
            color: #166534;
        }

        @media (max-width: 640px) {
            .ops-detail-page .ops-upload-input {
                min-width: 100%;
            }
        }
    </style>
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
        <div style="flex: 1; min-width: 0;">
            <div style="display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #002C76, #003d9e); padding: 5px 14px; border-radius: 999px; margin-bottom: 10px;">
                <i class="fas fa-file-invoice-dollar" style="color: rgba(255,255,255,0.85); font-size: 11px;"></i>
                <span style="color: white; font-size: 11px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase;">{{ $report->project_code }}</span>
            </div>
            <h1 style="color: #0f172a; font-size: 20px; font-weight: 700; margin: 0; line-height: 1.35;">{{ $report->project_title }}</h1>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-shrink: 0;">
            <a href="{{ route('fund-utilization.index') }}" style="display: inline-flex; padding: 10px 18px; background-color: #6b7280; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; align-items: center; gap: 6px; white-space: nowrap;">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
        </div>
    </div>

    <!-- Project Information Card -->
    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(15,23,42,0.09); margin-bottom: 28px; overflow: hidden;">
        <div style="display: flex; align-items: center; gap: 12px; padding: 16px 24px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%);">
            <div style="width: 34px; height: 34px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-info-circle" style="color: white; font-size: 14px;"></i>
            </div>
            <h2 style="color: white; font-size: 15px; font-weight: 700; margin: 0; letter-spacing: 0.01em;">Project Information</h2>
        </div>
        <div style="padding: 24px 28px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Project Code</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->project_code }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Province</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->province }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Implementing Unit</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->implementing_unit }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Barangay</label>
                @php
                    $barangayList = collect(preg_split('/[\\r\\n,]+/', $report->barangay ?? ''))
                        ->map(fn($item) => trim($item))
                        ->filter();
                @endphp
                @if($barangayList->isEmpty())
                    <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">Not specified</p>
                @else
                    <ul style="color: #111827; font-size: 16px; font-weight: 500; margin: 0; padding-left: 18px;">
                        @foreach($barangayList as $barangay)
                            <li>{{ $barangay }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Fund Source</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->fund_source }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Funding Year</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->funding_year }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Allocation</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->allocation ? '₱' . number_format($report->allocation, 2) : 'Not specified' }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Contract Amount</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">{{ $report->contract_amount ? '₱' . number_format($report->contract_amount, 2) : 'Not specified' }}</p>
            </div>
            <div>
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Project Status</label>
                <p style="color: #111827; font-size: 16px; font-weight: 500; margin: 0;">
                    @if($report->project_status)
                        <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;
                            @if($report->project_status === 'Ongoing')
                                background-color: #dbeafe; color: #1d4ed8;
                            @elseif($report->project_status === 'Completed')
                                background-color: #d1fae5; color: #065f46;
                            @elseif($report->project_status === 'Cancelled')
                                background-color: #fee2e2; color: #991b1b;
                            @elseif($report->project_status === 'On Hold')
                                background-color: #fef3c7; color: #92400e;
                            @else
                                background-color: #f3f4f6; color: #374151;
                            @endif">
                            {{ $report->project_status }}
                        </span>
                    @else
                        Not specified
                    @endif
                </p>
            </div>
            <div style="grid-column: 1 / -1;">
                <label style="display: block; color: #6b7280; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 4px;">Project Title</label>
                <p style="color: #111827; font-size: 15px; font-weight: 500; margin: 0;">{{ $report->project_title }}</p>
            </div>
        </div>
        </div>
    </div>

    @php
        $currentUser = Auth::user();
        $userLookupCache = [];
        $isProvincialValidator = $currentUser && $currentUser->isProvincialDilgAssignment();
        $isRegionalValidator = $currentUser && ($currentUser->normalizedRole() === \App\Models\User::ROLE_REGIONAL || $currentUser->isRegionalOfficeAssignment());
        $isWorkflowValidator = $isProvincialValidator || $isRegionalValidator;
        $isLguWorkflowUser = $currentUser && $currentUser->isLguScopedUser();
        $canUploadFundUtilizationDocuments = $currentUser
            && ($currentUser->isLguScopedUser() || $currentUser->isProvincialDilgAssignment());
        $isProvincialDilgViewer = $isProvincialValidator;
        $resolveUserById = function ($userId) use (&$userLookupCache) {
            $normalizedUserId = trim((string) $userId);
            if ($normalizedUserId === '') {
                return null;
            }

            if (!array_key_exists($normalizedUserId, $userLookupCache)) {
                $userLookupCache[$normalizedUserId] = \App\Models\User::where('idno', $normalizedUserId)->first();
            }

            return $userLookupCache[$normalizedUserId];
        };
        $resolveUploaderLevel = function ($record, ?string $encoderField = null) use ($resolveUserById) {
            if (!$record) {
                return null;
            }

            $encoderId = $encoderField ? ($record->{$encoderField} ?? null) : null;
            if (!$encoderId) {
                $encoderId = $record->encoder_id ?? null;
            }

            $uploader = $resolveUserById($encoderId);
            if (!$uploader) {
                return null;
            }

            if ($uploader->isProvincialDilgAssignment()) {
                return 'provincial';
            }

            if ($uploader->isLguScopedUser() || $uploader->normalizedAgency() === 'lgu') {
                return 'lgu';
            }

            if ($uploader->normalizedAgency() === 'dilg' && !$uploader->isRegionalOfficeAssignment()) {
                return 'provincial';
            }

            return null;
        };
        $resolveValidationState = function (
            $record,
            bool $hasDocument,
            string $workflowDocumentType,
            string $quarter,
            ?string $statusField = 'status',
            ?string $poTimestampField = 'approved_at_dilg_po',
            ?string $roTimestampField = 'approved_at_dilg_ro',
            ?string $encoderField = null
        ) use ($resolveUploaderLevel, $submissionWorkflows, $currentUser, $isProvincialValidator, $isRegionalValidator) {
            $status = $record && $statusField ? strtolower(trim((string) ($record->{$statusField} ?? ''))) : '';
            $uploaderLevel = $resolveUploaderLevel($record, $encoderField);
            $poApprovedAt = $record && $poTimestampField ? ($record->{$poTimestampField} ?? null) : null;
            $roApprovedAt = $record && $roTimestampField ? ($record->{$roTimestampField} ?? null) : null;
            $requiredValidator = 'provincial';
            $workflowKey = $workflowDocumentType . '::' . $quarter;
            $workflow = $submissionWorkflows[$workflowKey] ?? null;
            $workflowStatus = trim((string) ($workflow->status ?? ''));
            $currentApproverId = $workflow?->current_approver_id ? (int) $workflow->current_approver_id : null;
            $currentApprovalLevel = (int) ($workflow->current_approval_level ?? 0);
            $currentApproverRole = trim((string) ($workflow->current_approver_role ?? ''));
            $isReturnOnly = $workflow
                && $workflowStatus === 'Returned by Regional Officer'
                && $currentApprovalLevel === 1
                && trim((string) ($workflow->uploader_role ?? '')) === \App\Models\User::ROLE_LGU;
            if ($workflow) {
                $requiredValidator = ((int) ($workflow->current_approval_level ?? 1)) >= 2
                    ? 'regional'
                    : 'provincial';
            } elseif ($uploaderLevel === 'provincial') {
                $requiredValidator = 'regional';
            } elseif ($status === 'pending' && $poApprovedAt && !$roApprovedAt) {
                // LGU uploads proceed to Regional validation after Provincial approval.
                $requiredValidator = 'regional';
            }

            $isReturned = $workflow
                ? $hasDocument && str_starts_with($workflowStatus, 'Returned by ')
                : $hasDocument && $status === 'returned';
            $isPendingProvincial = $workflow
                ? $hasDocument && $workflowStatus === 'Pending Level 1 Approval'
                : $hasDocument && $status === 'pending' && $requiredValidator === 'provincial';
            $isPendingRegional = $workflow
                ? $hasDocument && $workflowStatus === 'Pending Level 2 Approval'
                : $hasDocument && $status === 'pending' && $requiredValidator === 'regional';
            $isApproved = $workflow
                ? $hasDocument && $workflowStatus === 'Approved'
                : $hasDocument && $status === 'approved';
            $canValidate = $workflow
                ? ($currentUser
                    ? \Illuminate\Support\Facades\Gate::forUser($currentUser)->allows('fund-utilization.validateWorkflow', $workflow)
                    : false)
                : (($requiredValidator === 'regional' ? $isRegionalValidator : $isProvincialValidator) ?? false);

            return [
                'uploader_level' => $uploaderLevel,
                'required_validator' => $requiredValidator,
                'validator_label' => $requiredValidator === 'regional' ? 'DILG Regional Office' : 'DILG Provincial Office',
                'is_returned' => $isReturned,
                'is_pending_provincial' => $isPendingProvincial,
                'is_pending_regional' => $isPendingRegional,
                'is_approved' => $isApproved,
                'po_approved_at' => $poApprovedAt,
                'ro_approved_at' => $roApprovedAt,
                'workflow_status' => $workflowStatus !== '' ? $workflowStatus : null,
                'current_approver_id' => $currentApproverId,
                'can_validate' => $canValidate,
                'return_only' => $isReturnOnly,
            ];
        };
        $canValidateDocument = function (array $validationState) use ($isProvincialValidator, $isRegionalValidator) {
            if (array_key_exists('can_validate', $validationState)) {
                return (bool) $validationState['can_validate'];
            }

            return ($validationState['required_validator'] ?? 'provincial') === 'regional'
                ? $isRegionalValidator
                : $isProvincialValidator;
        };
        $shouldHideLguDeleteUntilProvincialReturn = function (array $validationState) {
            return ($validationState['uploader_level'] ?? null) === 'lgu'
                && (($validationState['required_validator'] ?? 'provincial') === 'provincial')
                && !(($validationState['is_returned'] ?? false));
        };
        $isDocumentPendingValidation = function (array $validationState) {
            return ($validationState['is_pending_provincial'] ?? false)
                || ($validationState['is_pending_regional'] ?? false);
        };
        $shouldShowValidationActions = function (array $validationState) use ($canValidateDocument, $isDocumentPendingValidation) {
            $isReturnOnly = (bool) ($validationState['return_only'] ?? false);

            return ($isDocumentPendingValidation($validationState) || $isReturnOnly)
                && $canValidateDocument($validationState)
                && (!($validationState['is_returned'] ?? false) || $isReturnOnly);
        };
        $canDeleteFundUtilizationDocument = function ($record, ?string $statusField = 'status', ?string $encoderField = null) use ($currentUser, $resolveUserById) {
            if (!$currentUser || !$record) {
                return false;
            }

            $uploaderId = $encoderField ? ($record->{$encoderField} ?? null) : null;
            if ($uploaderId === null || trim((string) $uploaderId) === '') {
                $uploaderId = $record->encoder_id ?? null;
            }

            $normalizedUploaderId = trim((string) $uploaderId);
            if ($normalizedUploaderId === '') {
                return false;
            }

            if ($normalizedUploaderId === trim((string) $currentUser->idno)) {
                return true;
            }

            $status = strtolower(trim((string) ($statusField ? ($record->{$statusField} ?? '') : '')));
            if ($status !== 'returned') {
                return false;
            }

            $uploader = $resolveUserById($normalizedUploaderId);
            if (!$uploader) {
                return false;
            }

            if ($uploader->isLguScopedUser()) {
                if (!$currentUser->isLguScopedUser()) {
                    return false;
                }

                return $currentUser->normalizedProvince() !== ''
                    && $currentUser->normalizedProvince() === $uploader->normalizedProvince()
                    && $currentUser->normalizedOfficeComparable() !== ''
                    && $currentUser->normalizedOfficeComparable() === $uploader->normalizedOfficeComparable();
            }

            if ($uploader->isProvincialDilgAssignment()) {
                if (!$currentUser->isProvincialDilgAssignment()) {
                    return false;
                }

                return $currentUser->normalizedProvince() !== ''
                    && $currentUser->normalizedProvince() === $uploader->normalizedProvince();
            }

            return false;
        };
        $resolveUploaderMeta = function ($record, ?string $uploadedAtField = null, ?string $encoderField = null) use ($isProvincialDilgViewer, $resolveUserById) {
            if (!$record) {
                return ['time' => null, 'name' => 'Unknown'];
            }

            $uploadedAt = $uploadedAtField ? ($record->{$uploadedAtField} ?? null) : null;
            if (!$uploadedAt) {
                $uploadedAt = $record->created_at ?? $record->updated_at ?? null;
            }

            $uploadedTime = null;
            if ($uploadedAt) {
                $uploadedTime = is_string($uploadedAt)
                    ? \Carbon\Carbon::parse($uploadedAt)->setTimezone(config('app.timezone'))
                    : $uploadedAt->setTimezone(config('app.timezone'));
            }

            $encoderId = $encoderField ? ($record->{$encoderField} ?? null) : null;
            if (!$encoderId) {
                $encoderId = $record->encoder_id ?? null;
            }
            if (!$encoderId && $isProvincialDilgViewer) {
                $encoderId = $record->approved_by_dilg_po ?? $record->approved_by ?? null;
            }

            $encoderUser = $resolveUserById($encoderId);
            $encoderName = $encoderUser ? trim($encoderUser->fname . ' ' . $encoderUser->lname) : 'Unknown';

            return ['time' => $uploadedTime, 'name' => $encoderName];
        };
        $resolveSubmissionTimelinessTag = function ($uploadedAt, $configuredDeadline) {
            if (!$uploadedAt || !is_array($configuredDeadline)) {
                return null;
            }

            $deadlineAt = $configuredDeadline['deadline_at'] ?? null;
            if (!$deadlineAt) {
                return null;
            }

            $timezone = config('app.timezone');
            $submittedAt = $uploadedAt instanceof \Carbon\CarbonInterface
                ? $uploadedAt->copy()->setTimezone($timezone)
                : \Carbon\Carbon::parse($uploadedAt)->setTimezone($timezone);
            $deadlineTime = $deadlineAt instanceof \Carbon\CarbonInterface
                ? $deadlineAt->copy()->setTimezone($timezone)
                : \Carbon\Carbon::parse($deadlineAt)->setTimezone($timezone);
            $isLate = $submittedAt->greaterThan($deadlineTime);

            return [
                'label' => $isLate ? 'Late' : 'On Time',
                'background' => $isLate ? '#fef2f2' : '#ecfdf5',
                'color' => $isLate ? '#b91c1c' : '#047857',
                'border' => $isLate ? '#fecaca' : '#a7f3d0',
                'title' => $isLate
                    ? 'Submitted after the configured deadline of ' . $deadlineTime->format('M d, Y h:i A')
                    : 'Submitted on or before the configured deadline of ' . $deadlineTime->format('M d, Y h:i A'),
            ];
        };
    @endphp

    <!-- Quarterly Sections -->
    @foreach ($quarters as $quarter)
        @php
            $quarterLabels = ['Q1' => 'Quarter 1', 'Q2' => 'Quarter 2', 'Q3' => 'Quarter 3', 'Q4' => 'Quarter 4'];
            $quarterWindows = [
                'Q1' => 'January - March',
                'Q2' => 'April - June',
                'Q3' => 'July - September',
                'Q4' => 'October - December',
            ];
            $quarterLabel = $quarterLabels[$quarter] ?? $quarter;
            $quarterWindow = $quarterWindows[$quarter] ?? '';
            $configuredQuarterDeadline = $configuredQuarterDeadlines[$quarter] ?? null;
            $quarterDeadlineDisplay = trim((string) ($configuredQuarterDeadline['display'] ?? ''));
            $isExpandedByDefault = false;
            $displayStyle = 'none';
            $iconRotation = 'rotate(0deg)';

            // Define FDP variables early to avoid undefined variable errors
            $isFdpReturned = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_status === 'returned';
        @endphp
        <div style="background: white; border-radius: 12px; box-shadow: 0 4px 16px rgba(15,23,42,0.09); margin-bottom: 24px; border: 1px solid #e5e7eb; overflow: hidden;">
            <!-- Quarter Accordion Header -->
            <button type="button" onclick="toggleAccordion('quarter-{{ $quarter }}')" style="width: 100%; padding: 18px 24px; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); color: white; border: none; text-align: left; cursor: pointer; font-weight: 700; font-size: 15px; display: flex; justify-content: space-between; align-items: center;" onmouseover="this.style.filter='brightness(1.08)'" onmouseout="this.style.filter='brightness(1)'">
                <span style="display: flex; align-items: center; gap: 12px;">
                    <span style="width: 34px; height: 34px; background: rgba(255,255,255,0.15); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-calendar-alt" style="font-size: 14px;"></i>
                    </span>
                    <span>{{ $quarterLabel }}</span>
                    <span style="display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: {{ $quarterDeadlineDisplay !== '' ? 'rgba(15,118,110,0.32)' : 'rgba(107,114,128,0.35)' }}; color: #fff;">
                        {{ $quarterDeadlineDisplay !== '' ? 'Deadline Set' : 'No Deadline' }}
                    </span>
                    <span style="font-size: 11px; opacity: 0.95;">{{ $quarterWindow }}</span>
                    <span style="font-size: 11px; opacity: 0.95;">Deadline: {{ $quarterDeadlineDisplay !== '' ? $quarterDeadlineDisplay : 'No superadmin deadline set' }}</span>
                    <span style="display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.15); padding: 4px 12px; border-radius: 999px; font-size: 12px;">
                        <span style="width: 60px; height: 5px; background: rgba(255,255,255,0.25); border-radius: 999px; overflow: hidden; display: inline-block;">
                            <span style="width: {{ $accomplishmentPercentages[$quarter] }}%; height: 100%; background: #34d399; display: block;"></span>
                        </span>
                        <span style="font-weight: 700;">{{ $accomplishmentPercentages[$quarter] }}%</span>
                    </span>
                </span>
                <i class="fas fa-chevron-down" id="icon-quarter-{{ $quarter }}" style="transition: transform 0.3s; transform: {{ $iconRotation }}; opacity: 0.9;"></i>
            </button>

            <!-- Quarter Content -->
            <div id="quarter-{{ $quarter }}" style="display: {{ $displayStyle }}; padding: 22px 24px;">
            @php
                $isQuarterIndividualUploadLocked = (
                    ($batchDocuments[$quarter] && $batchDocuments[$quarter]->approved_at_dilg_ro)
                    || ($movUploads[$quarter] && $movUploads[$quarter]->approved_at_dilg_ro)
                );
                $individualUploadLockTitle = $isQuarterIndividualUploadLocked
                    ? 'This quarter is already validated by DILG Regional Office. Individual document uploads are disabled.'
                    : '';
            @endphp
            <div style="border: 1px solid #dbe3f0; border-radius: 12px; overflow: hidden; background: #f8fafc; margin-bottom: 18px;">
                <button type="button" onclick="toggleAccordion('individual-documents-{{ $quarter }}')" style="width: 100%; padding: 14px 18px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1e3a8a; border: none; text-align: left; cursor: pointer; font-weight: 700; font-size: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 30px; height: 30px; background: rgba(37,99,235,0.12); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-folder-open" style="font-size: 13px;"></i>
                        </span>
                        <span>Individual Documents</span>
                    </span>
                    <i class="fas fa-chevron-down" id="icon-individual-documents-{{ $quarter }}" style="transition: transform 0.3s; transform: rotate(0deg);"></i>
                </button>
                <div id="individual-documents-{{ $quarter }}" style="display: none; padding: 18px 18px 0;">
            <!-- Fund Utilization Report (MOV) Section -->
            @php
                $hasMovFile = $movUploads[$quarter] && $movUploads[$quarter]->mov_file_path;
                $movValidationState = $resolveValidationState(
                    $movUploads[$quarter],
                    (bool) $hasMovFile,
                    'mov',
                    $quarter,
                    'status',
                    'approved_at_dilg_po',
                    'approved_at_dilg_ro',
                    'mov_encoder_id'
                );
                $movStatusColor = $hasMovFile ? '#10b981' : '#f59e0b';
                $movBackgroundColor = $hasMovFile ? '#fffbeb' : 'transparent';
                
                $isPendingDilgRoValidation = $movValidationState['is_pending_regional'];
                $isApprovedByDilgRo = $movValidationState['is_approved'] && $movValidationState['required_validator'] === 'regional';
                $isMovReturned = $movValidationState['is_returned'];
                
                if ($isMovReturned) {
                    $movStatusColor = '#ef4444';
                    $movStatusLabel = 'Returned';
                    $movBackgroundColor = '#fee2e2';
                } else {
                    if ($movValidationState['is_approved']) {
                        $movStatusColor = '#059669';
                        $movStatusLabel = 'Approved';
                    } elseif ($movValidationState['is_pending_regional']) {
                        $movStatusColor = '#3b82f6';
                        $movStatusLabel = 'For DILG Regional Office Validation';
                    } else {
                        $movStatusLabel = $hasMovFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                    }
                }

                $isMovForPoValidation = $movValidationState['is_pending_provincial'];
                $isMovUnderValidation = $isPendingDilgRoValidation || $isMovForPoValidation;
            @endphp
            <div style="border: 1px solid #e5e7eb; border-left: 4px solid {{ $movStatusColor }}; border-radius: 8px; margin-bottom: 18px; overflow: hidden; background-color: white;">
                <h3 style="display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 0 0 0 0; padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb; font-weight: 400;">
                    <span style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        <span style="width: 30px; height: 30px; background: rgba(220,38,38,0.1); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-file-pdf" style="color: #dc2626; font-size: 13px;"></i>
                        </span>
                        <span style="display: flex; flex-direction: column; gap: 1px;">
                            <span style="color: #1e293b; font-size: 13px; font-weight: 700; line-height: 1.3;">Fund Utilization Report</span>
                            <span style="color: #64748b; font-size: 11px; font-weight: 400;">MOV on PDF Format</span>
                        </span>
                    </span>
                    @php
                    @endphp
                    <span style="display: inline-flex; align-items: center; padding: 3px 10px; background-color: {{ $movStatusColor }}; color: white; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $movStatusLabel }}
                    </span>
                </h3>
                <div style="padding: 16px;">
                <div style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <label style="display: none;"></label>
                    <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                        @if($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path)
                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                @php
                                    $uploadedInfo = $resolveUploaderMeta($movUploads[$quarter], 'mov_uploaded_at', 'mov_encoder_id');
                                    $uploadedTime = $uploadedInfo['time'];
                                    $encoderName = $uploadedInfo['name'];
                                @endphp
                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                @php
                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                @endphp
                                @if($submissionTimeliness)
                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                        {{ $submissionTimeliness['label'] }}
                                    </span>
                                @endif
                            </span>
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPO = $isWorkflowValidator && in_array(Auth::user()->province, $cordilleraProvinces);
                                $hasPoApproval = $movUploads[$quarter] && $movUploads[$quarter]->approved_at_dilg_po;
                            @endphp
                            @if($hasPoApproval)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">
                                    @php
                                        $poApprovedAt = is_string($movUploads[$quarter]->approved_at_dilg_po) ? \Carbon\Carbon::parse($movUploads[$quarter]->approved_at_dilg_po)->setTimezone(config('app.timezone')) : $movUploads[$quarter]->approved_at_dilg_po->setTimezone(config('app.timezone'));
                                        $poApproverId = $movUploads[$quarter]->approved_by_dilg_po ?? $movUploads[$quarter]->approved_by;
                                        $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                        $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                    @endphp
                                    DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}
                                </span>
                            @endif
                            @if($movUploads[$quarter] && $movUploads[$quarter]->approved_at_dilg_ro)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">
                                    @php
                                        $roApprovedAt = is_string($movUploads[$quarter]->approved_at_dilg_ro) ? \Carbon\Carbon::parse($movUploads[$quarter]->approved_at_dilg_ro)->setTimezone(config('app.timezone')) : $movUploads[$quarter]->approved_at_dilg_ro->setTimezone(config('app.timezone'));
                                        $roApproverId = $movUploads[$quarter]->approved_by_dilg_ro ?? $movUploads[$quarter]->approved_by;
                                        $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                        $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                    @endphp
                                    DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}
                                </span>
                            @endif
                            @if($isMovReturned && $movUploads[$quarter] && $movUploads[$quarter]->approved_at)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">
                                    @php
                                        $returnedAt = is_string($movUploads[$quarter]->approved_at) ? \Carbon\Carbon::parse($movUploads[$quarter]->approved_at)->setTimezone(config('app.timezone')) : $movUploads[$quarter]->approved_at->setTimezone(config('app.timezone'));
                                        $returnedByUser = $movUploads[$quarter]->approver ? trim($movUploads[$quarter]->approver->fname . ' ' . $movUploads[$quarter]->approver->lname) : 'Unknown';
                                    @endphp
                                    Returned at: {{ $returnedAt->format('M d, Y h:i A') }} by {{ $returnedByUser }}
                                </span>
                            @endif
                        @endif
                    </label>
                    <form action="{{ route('fund-utilization.upload-mov', $report->project_code) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                        @csrf
                        <input type="hidden" name="quarter" value="{{ $quarter }}">
                        <input type="file" name="mov_file" class="dashboard-file-input" accept="application/pdf" style="flex: 1; min-width: 200px;" onchange="showSaveButton(this, 'mov-save-btn-{{ $quarter }}', 'mov-filename-{{ $quarter }}')" {{ !$canUploadFundUtilizationDocuments || $isQuarterIndividualUploadLocked || ($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path && $isMovReturned) ? 'disabled' : '' }} title="{{ !$canUploadFundUtilizationDocuments ? 'Only LGU User and DILG Provincial Office users can upload documents.' : ($isQuarterIndividualUploadLocked ? $individualUploadLockTitle : (($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path && $isMovReturned) ? 'Document was returned. Delete the current file to upload a new one.' : '')) }}">
                        <button type="submit" id="mov-save-btn-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                            <i class="fas fa-upload"></i> Submit
                        </button>
                    </form>
                    <div id="mov-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                        @if($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path)
                            <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($movUploads[$quarter]->mov_file_path) }}
                        @endif
                    </div>

                    @if($isLguWorkflowUser)
                        @if($movUploads[$quarter] && ($movUploads[$quarter]->mov_file_path || $isMovReturned))
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                @if($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path)
                                    <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'mov', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                @endif
                                @if($canDeleteFundUtilizationDocument($movUploads[$quarter], 'status', 'mov_encoder_id') && !$isMovUnderValidation && $movUploads[$quarter]->status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($movValidationState))
                                    <button type="button" onclick="deleteDocument('mov', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
            @endif

        </div>

    @endif

@if($isLguWorkflowUser && $movUploads[$quarter])

    <button type="button" onclick="toggleAccordion('mov-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">

        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>

        <i class="fas fa-chevron-down" id="icon-mov-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>

    </button>

    <div id="mov-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">

        <textarea id="textarea-mov-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $isMovReturned ? ($movUploads[$quarter]->approval_remarks ?? '') : ($movUploads[$quarter]->user_remarks ?? '') }}</textarea>

        <button type="button" onclick="saveRemarksAjax('mov', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>

    </div>

@endif

@elseif($isWorkflowValidator)
                        @if($movUploads[$quarter] && $movUploads[$quarter]->mov_file_path)
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPOMov = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                $hasMovFile = $movUploads[$quarter] && $movUploads[$quarter]->mov_file_path;
                                $shouldHideDeleteForDilgMov = $isDilgPOMov || $hasMovFile;
                            @endphp
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'mov', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                @if($shouldShowValidationActions($movValidationState))
                                    @if($canDeleteFundUtilizationDocument($movUploads[$quarter], 'status', 'mov_encoder_id') && !$isMovUnderValidation && $movUploads[$quarter]->status !== 'approved')
                                        <button type="button" onclick="deleteDocument('mov', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    @endif
                                    @if($movUploads[$quarter]->status === 'pending')
                                        @if(!($movValidationState['return_only'] ?? false))
                                            <button type="button" onclick="openRemarksModal('mov', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        @endif
                                        <button type="button" onclick="openRemarksModal('mov', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-undo"></i> Return
                                        </button>
                                    @endif
                                @endif
                            </div>
                        @endif
                        @if($movUploads[$quarter] && ($movUploads[$quarter]->mov_file_path || $movUploads[$quarter]->user_remarks || $isMovReturned))
                            <button type="button" onclick="toggleAccordion('mov-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                <i class="fas fa-chevron-down" id="icon-mov-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                            </button>
                            <div id="mov-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                <textarea id="textarea-mov-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;" {{ $isMovReturned ? 'readonly' : '' }}>{{ $isMovReturned ? ($movUploads[$quarter]->approval_remarks ?? '') : ($movUploads[$quarter]->user_remarks ?? '') }}</textarea>
                                @if(!$isMovReturned)
                                    <button type="button" onclick="saveRemarksAjax('mov', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
                @if ($movUploads[$quarter])
                    <!-- DILG Approval Buttons -->
                    @if($isWorkflowValidator)
                        <!-- Remarks Section -->
                        @if($movUploads[$quarter]->approval_remarks)
                            <div style="margin-top: 12px; padding: 10px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                                <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Approval Remarks:</p>
                                <p style="color: #374151; font-size: 13px; margin: 0;">{{ $movUploads[$quarter]->approval_remarks }}</p>
                            </div>
                        @endif
                    @elseif($isLguWorkflowUser && $movUploads[$quarter]->approval_remarks)
                        <!-- View Remarks for LGU -->
                        <div style="margin-top: 12px; padding: 10px; background-color: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 4px;">
                            <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">DILG Remarks:</p>
                            <p style="color: #374151; font-size: 13px; margin: 0;">{{ $movUploads[$quarter]->approval_remarks }}</p>
                        </div>
                    @endif
                @endif
            </div>
            </div>

            <!-- Written Notice Section -->
            @php
                $hasAnyWrittenFile = $writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dbm_path || $writtenNotices[$quarter]->secretary_dilg_path || $writtenNotices[$quarter]->speaker_house_path || $writtenNotices[$quarter]->president_senate_path || $writtenNotices[$quarter]->house_committee_path || $writtenNotices[$quarter]->senate_committee_path);
                $writtenBackgroundColor = $hasAnyWrittenFile ? '#fffbeb' : 'transparent';
            @endphp
            <div style="border: 1px solid #e5e7eb; border-left: 4px solid #2563eb; border-radius: 8px; margin-bottom: 18px; overflow: hidden; background-color: white;">
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        <span style="width: 30px; height: 30px; background: rgba(37,99,235,0.1); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-envelope-open-text" style="color: #2563eb; font-size: 13px;"></i>
                        </span>
                        <div>
                            <p style="margin: 0; color: #1e293b; font-size: 13px; font-weight: 700; line-height: 1.3;">Written Notice</p>
                            <p style="margin: 0; color: #64748b; font-size: 11px;">MOV Screenshot of Emailed Notice &amp; Written Notice PDF</p>
                        </div>
                    </div>
                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->updated_at)
                        <span style="font-size: 11px; color: #6b7280; flex-shrink: 0; white-space: nowrap;">
                            @php
                                $createdAt = $writtenNotices[$quarter]->updated_at;
                                $uploadedTime = is_string($createdAt) ? \Carbon\Carbon::parse($createdAt)->setTimezone(config('app.timezone')) : $createdAt->setTimezone(config('app.timezone'));
                            @endphp
                            Updated: {{ $uploadedTime->format('M d, Y h:i A') }}
                        </span>
                    @endif
                </div>
                <div style="padding: 16px;">
                <form id="written-notice-form-{{ $quarter }}" action="{{ route('fund-utilization.upload-written-notice', $report->project_code) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="quarter" value="{{ $quarter }}">

                    <div style="border-top: 1px solid #e5e7eb; padding-top: 15px; margin-top: 15px;">
                        <p style="color: #374151; font-weight: 600; font-size: 13px; margin-bottom: 15px;">Distribution Recipients:</p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <!-- Secretary of DBM -->
                            @php
                                $hasDbmFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path;
                                $dbmValidationState = $resolveValidationState(
                                    $writtenNotices[$quarter],
                                    (bool) $hasDbmFile,
                                    'written-notice-dbm',
                                    $quarter,
                                    'dbm_status',
                                    'dbm_approved_at_dilg_po',
                                    'dbm_approved_at_dilg_ro',
                                    'dbm_encoder_id'
                                );
                                $dbmFieldBg = $hasDbmFile ? '#fffbeb' : '#f9fafb';

                                $isDbmPendingDilgRoValidation = $dbmValidationState['is_pending_regional'];
                                $isDbmApprovedByDilgRo = $dbmValidationState['is_approved'] && $dbmValidationState['required_validator'] === 'regional';
                                $isDbmReturned = $dbmValidationState['is_returned'];
                                
                                if ($isDbmReturned) {
                                    $dbmStatusColor = '#ef4444';
                                    $dbmStatusLabel = 'Returned';
                                    $dbmFieldBg = '#fee2e2';
                                } else {
                                    if ($dbmValidationState['is_approved']) {
                                        $dbmStatusColor = '#059669';
                                        $dbmStatusLabel = 'Approved';
                                    } elseif ($dbmValidationState['is_pending_regional']) {
                                        $dbmStatusColor = '#3b82f6';
                                        $dbmStatusLabel = 'For DILG Regional Office Validation';
                                    } else {
                                        $dbmStatusColor = $hasDbmFile ? '#10b981' : '#f59e0b';
                                        $dbmStatusLabel = $hasDbmFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                    }
                                }

                                $isDbmForPoValidation = $dbmValidationState['is_pending_provincial'];
                                $isDbmUnderValidation = $isDbmPendingDilgRoValidation || $isDbmForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $dbmFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Secretary of DBM</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $dbmStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $dbmStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if(
                                    ($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
                                    || ($isDbmReturned && $writtenNotices[$quarter] && $writtenNotices[$quarter]->dbm_approved_at)
                                )
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                                @php
                                                    $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'dbm_uploaded_at', 'dbm_encoder_id');
                                                    $uploadedTime = $uploadedInfo['time'];
                                                    $encoderName = $uploadedInfo['name'];
                                                @endphp
                                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                                @php
                                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                                @endphp
                                                @if($submissionTimeliness)
                                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                        {{ $submissionTimeliness['label'] }}
                                                    </span>
                                                @endif
                                            </span>
                                            @php
                                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                                $isDilgPO = $isWorkflowValidator && in_array(Auth::user()->province, $cordilleraProvinces);
                                                $hasPoApproval = $writtenNotices[$quarter]
                                                    && $writtenNotices[$quarter]->dbm_approved_at_dilg_po;
                                            @endphp
                                            @if($hasPoApproval)
                                                @php
                                                    $poTimestamp = $writtenNotices[$quarter]->dbm_approved_at_dilg_po;
                                                    $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                    $poApproverId = $writtenNotices[$quarter]->dbm_approved_by_dilg_po ?? $writtenNotices[$quarter]->dbm_approved_by;
                                                    $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                    $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                                @endphp
                                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                            @endif
                                            @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->dbm_approved_at_dilg_ro && $writtenNotices[$quarter]->dbm_status === 'approved')
                                                @php
                                                    $roTimestamp = $writtenNotices[$quarter]->dbm_approved_at_dilg_ro;
                                                    $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                    $roApproverId = $writtenNotices[$quarter]->dbm_approved_by_dilg_ro ?? $writtenNotices[$quarter]->dbm_approved_by;
                                                    $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                    $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                                @endphp
                                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                            @endif
                                        @endif
                                        @if($isDbmReturned && $writtenNotices[$quarter] && $writtenNotices[$quarter]->dbm_approved_at)
                                            @php
                                                $dbmReturnedAt = is_string($writtenNotices[$quarter]->dbm_approved_at) ? \Carbon\Carbon::parse($writtenNotices[$quarter]->dbm_approved_at)->setTimezone(config('app.timezone')) : $writtenNotices[$quarter]->dbm_approved_at->setTimezone(config('app.timezone'));
                                                $dbmApproverUser = $writtenNotices[$quarter]->dbm_approved_by ? \App\Models\User::where('idno', $writtenNotices[$quarter]->dbm_approved_by)->first() : null;
                                                $dbmApproverName = $dbmApproverUser ? trim($dbmApproverUser->fname . ' ' . $dbmApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">Returned at: {{ $dbmReturnedAt->format('M d, Y h:i A') }} by {{ $dbmApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="secretary_dbm" class="dashboard-file-input" accept="image/*,.pdf" style="flex: 1; min-width: 200px;" onchange="showSaveButton(this, 'dbm-save-btn-{{ $quarter }}', 'dbm-filename-{{ $quarter }}')" {{ !$canUploadFundUtilizationDocuments || $isQuarterIndividualUploadLocked || ($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path && $isDbmReturned) ? 'disabled' : '' }} title="{{ !$canUploadFundUtilizationDocuments ? 'Only LGU User and DILG Provincial Office users can upload documents.' : ($isQuarterIndividualUploadLocked ? $individualUploadLockTitle : (($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path && $isDbmReturned) ? 'Document was returned. Delete the current file to upload a new one.' : '')) }}">
                                    <button type="submit" id="dbm-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="dbm-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->secretary_dbm_path) }}
                                    @endif
                                </div>


@if($isLguWorkflowUser)
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-dbm', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'dbm_status', 'dbm_encoder_id') && !$isDbmUnderValidation && $writtenNotices[$quarter]->dbm_status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($dbmValidationState))
                <button type="button" onclick="deleteDocument('written-notice-dbm', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dbm_path || $isDbmReturned))
        <button type="button" onclick="toggleAccordion('dbm-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
            <i class="fas fa-chevron-down" id="icon-dbm-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
        </button>
        <div id="dbm-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
            <textarea id="textarea-dbm-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->dbm_remarks ?? '' }}</textarea>
            <button type="button" onclick="saveRemarksAjax('dbm-secretary', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
        </div>
    @endif
@elseif($isWorkflowValidator)
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPODbm = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasDbmFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path;
                                        $shouldHideDeleteForDilgDbm = $isDilgPODbm || $hasDbmFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dbm_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-dbm', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'dbm_status', 'dbm_encoder_id') && !$isDbmUnderValidation && $writtenNotices[$quarter]->dbm_status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($dbmValidationState))
                <button type="button" onclick="deleteDocument('written-notice-dbm', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if($shouldShowValidationActions($dbmValidationState))
                @if($writtenNotices[$quarter]->dbm_status === 'pending')
                    @if(!($dbmValidationState['return_only'] ?? false))
                        <button type="button" onclick="openRemarksModal('written-notice-dbm', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    @endif
                    <button type="button" onclick="openRemarksModal('written-notice-dbm', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dbm_path || $writtenNotices[$quarter]->user_remarks || $isDbmReturned))
                                    <button type="button" onclick="toggleAccordion('dbm-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-dbm-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
        <div id="dbm-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
            <textarea id="textarea-dbm-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->dbm_remarks ?? '' }}</textarea>
            <button type="button" onclick="saveRemarksAjax('dbm-secretary', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
        </div>
                                    @endif
                                @endif
                            </div>
                            <!-- Secretary of DILG -->
                            @php
                                $hasDilgFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path;
                                $dilgValidationState = $resolveValidationState(
                                    $writtenNotices[$quarter],
                                    (bool) $hasDilgFile,
                                    'written-notice-dilg',
                                    $quarter,
                                    'dilg_status',
                                    'dilg_approved_at_dilg_po',
                                    'dilg_approved_at_dilg_ro',
                                    'dilg_encoder_id'
                                );
                                $dilgFieldBg = $hasDilgFile ? '#fffbeb' : '#f9fafb';

                                $isDilgPendingDilgRoValidation = $dilgValidationState['is_pending_regional'];
                                $isDilgApprovedByDilgRo = $dilgValidationState['is_approved'] && $dilgValidationState['required_validator'] === 'regional';
                                $isDilgReturned = $dilgValidationState['is_returned'];
                                
                                if ($isDilgReturned) {
                                    $dilgStatusColor = '#ef4444';
                                    $dilgStatusLabel = 'Returned';
                                    $dilgFieldBg = '#fee2e2';
                                } else {
                                    if ($dilgValidationState['is_approved']) {
                                        $dilgStatusColor = '#059669';
                                        $dilgStatusLabel = 'Approved';
                                    } elseif ($dilgValidationState['is_pending_regional']) {
                                        $dilgStatusColor = '#3b82f6';
                                        $dilgStatusLabel = 'For DILG Regional Office Validation';
                                    } else {
                                        $dilgStatusColor = $hasDilgFile ? '#10b981' : '#f59e0b';
                                        $dilgStatusLabel = $hasDilgFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                    }
                                }

                                $isDilgForPoValidation = $dilgValidationState['is_pending_provincial'];
                                $isDilgUnderValidation = $isDilgPendingDilgRoValidation || $isDilgForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $dilgFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Secretary of DILG</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $dilgStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $dilgStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if(
                                    ($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
                                    || ($isDilgReturned && $writtenNotices[$quarter] && $writtenNotices[$quarter]->dilg_approved_at)
                                )
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                                @php
                                                    $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'dilg_uploaded_at', 'dilg_encoder_id');
                                                    $uploadedTime = $uploadedInfo['time'];
                                                    $encoderName = $uploadedInfo['name'];
                                                @endphp
                                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                                @php
                                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                                @endphp
                                                @if($submissionTimeliness)
                                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                        {{ $submissionTimeliness['label'] }}
                                                    </span>
                                                @endif
                                            </span>
                                            @php
                                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                                $isDilgPO = $isWorkflowValidator && in_array(Auth::user()->province, $cordilleraProvinces);
                                                $hasPoApproval = $writtenNotices[$quarter]
                                                    && $writtenNotices[$quarter]->dilg_approved_at_dilg_po;
                                            @endphp
                                            @if($hasPoApproval)
                                                @php
                                                    $poTimestamp = $writtenNotices[$quarter]->dilg_approved_at_dilg_po;
                                                    $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                    $poApproverId = $writtenNotices[$quarter]->dilg_approved_by_dilg_po ?? $writtenNotices[$quarter]->dilg_approved_by;
                                                    $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                    $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                                @endphp
                                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                            @endif
                                            @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->dilg_approved_at_dilg_ro && $writtenNotices[$quarter]->dilg_status === 'approved')
                                                @php
                                                    $roTimestamp = $writtenNotices[$quarter]->dilg_approved_at_dilg_ro;
                                                    $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                    $roApproverId = $writtenNotices[$quarter]->dilg_approved_by_dilg_ro ?? $writtenNotices[$quarter]->dilg_approved_by;
                                                    $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                    $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                                @endphp
                                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                            @endif
                                        @endif
                                        @if($isDilgReturned && $writtenNotices[$quarter] && $writtenNotices[$quarter]->dilg_approved_at)
                                            @php
                                                $returnedAt = is_string($writtenNotices[$quarter]->dilg_approved_at) ? \Carbon\Carbon::parse($writtenNotices[$quarter]->dilg_approved_at)->setTimezone(config('app.timezone')) : $writtenNotices[$quarter]->dilg_approved_at->setTimezone(config('app.timezone'));
                                                $dilgApproverUser = $writtenNotices[$quarter]->dilg_approved_by ? \App\Models\User::where('idno', $writtenNotices[$quarter]->dilg_approved_by)->first() : null;
                                                $approverName = $dilgApproverUser ? trim($dilgApproverUser->fname . ' ' . $dilgApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">Returned at: {{ $returnedAt->format('M d, Y h:i A') }} by {{ $approverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="secretary_dilg" class="dashboard-file-input" accept="image/*,.pdf" style="flex: 1; min-width: 200px;" onchange="showSaveButton(this, 'dilg-save-btn-{{ $quarter }}', 'dilg-filename-{{ $quarter }}')" {{ !$canUploadFundUtilizationDocuments || $isQuarterIndividualUploadLocked || ($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path && !$isDilgReturned) ? 'disabled' : '' }} title="{{ !$canUploadFundUtilizationDocuments ? 'Only LGU User and DILG Provincial Office users can upload documents.' : ($isQuarterIndividualUploadLocked ? $individualUploadLockTitle : (($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path && !$isDilgReturned) ? 'File already uploaded. Delete the current file to upload a new one.' : '')) }}">
                                    <button type="submit" id="dilg-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="dilg-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->secretary_dilg_path) }}
                                    @endif
                                </div>


@if($isLguWorkflowUser)
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dilg_path || $isDilgReturned))
            @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
                <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-dilg', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-eye"></i> View
                </a>
            @endif
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'dilg_status', 'dilg_encoder_id') && !$isDilgUnderValidation && $writtenNotices[$quarter]->dilg_status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($dilgValidationState))
                <button type="button" onclick="deleteDocument('written-notice-dilg', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dilg_path || $isDilgReturned))
        <button type="button" onclick="toggleAccordion('dilg-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
            <i class="fas fa-chevron-down" id="icon-dilg-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
        </button>
        <div id="dilg-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
            <textarea id="textarea-dilg-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->dilg_remarks ?? '' }}</textarea>
            <button type="button" onclick="saveRemarksAjax('dilg-secretary', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
        </div>
    @endif
@elseif($isWorkflowValidator)
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPODilg = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasDilgFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path;
                                        $shouldHideDeleteForDilgDilg = $isDilgPODilg || $hasDilgFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->secretary_dilg_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-dilg', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'dilg_status', 'dilg_encoder_id') && !$isDilgUnderValidation && $writtenNotices[$quarter]->dilg_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-dilg', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if($shouldShowValidationActions($dilgValidationState))
                @if($writtenNotices[$quarter]->dilg_status === 'pending')
                    @if(!($dilgValidationState['return_only'] ?? false))
                        <button type="button" onclick="openRemarksModal('written-notice-dilg', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    @endif
                    <button type="button" onclick="openRemarksModal('written-notice-dilg', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->secretary_dilg_path || $writtenNotices[$quarter]->user_remarks || $isDilgReturned))
                                    <button type="button" onclick="toggleAccordion('dilg-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-dilg-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="dilg-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-dilg-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;" {{ $isDilgReturned ? 'readonly' : '' }}>{{ $isDilgReturned ? ($writtenNotices[$quarter]->dilg_remarks ?? '') : ($writtenNotices[$quarter]->user_remarks ?? '') }}</textarea>
                                        @if(!$isDilgReturned)
                                            <button type="button" onclick="saveRemarksAjax('dilg-secretary', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                        @endif
                                    </div>
                                    @endif
                                @endif
                            </div>

                            <!-- Speaker of the House -->
                            @php
                                $hasSpeakerFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path;
                                $speakerValidationState = $resolveValidationState(
                                    $writtenNotices[$quarter],
                                    (bool) $hasSpeakerFile,
                                    'written-notice-speaker',
                                    $quarter,
                                    'speaker_status',
                                    'speaker_approved_at_dilg_po',
                                    'speaker_approved_at_dilg_ro',
                                    'speaker_encoder_id'
                                );
                                $speakerFieldBg = $hasSpeakerFile ? '#fffbeb' : '#f9fafb';

                                $isSpeakerPendingDilgRoValidation = $speakerValidationState['is_pending_regional'];
                                $isSpeakerApprovedByDilgRo = $speakerValidationState['is_approved'] && $speakerValidationState['required_validator'] === 'regional';
                                $isSpeakerReturned = $speakerValidationState['is_returned'];
                                
                                if ($isSpeakerReturned) {
                                    $speakerStatusColor = '#ef4444';
                                    $speakerStatusLabel = 'Returned';
                                    $speakerFieldBg = '#fee2e2';
                                } else {
                                    if ($speakerValidationState['is_approved']) {
                                        $speakerStatusColor = '#059669';
                                        $speakerStatusLabel = 'Approved';
                                    } elseif ($speakerValidationState['is_pending_regional']) {
                                        $speakerStatusColor = '#3b82f6';
                                        $speakerStatusLabel = 'For DILG Regional Office Validation';
                                    } else {
                                        $speakerStatusColor = $hasSpeakerFile ? '#10b981' : '#f59e0b';
                                        $speakerStatusLabel = $hasSpeakerFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                    }
                                }

                                $isSpeakerForPoValidation = $speakerValidationState['is_pending_provincial'];
                                $isSpeakerUnderValidation = $isSpeakerPendingDilgRoValidation || $isSpeakerForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $speakerFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Speaker of the House</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $speakerStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $speakerStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path)
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                            @php
                                                $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'speaker_uploaded_at', 'speaker_encoder_id');
                                                $uploadedTime = $uploadedInfo['time'];
                                                $encoderName = $uploadedInfo['name'];
                                            @endphp
                                            Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                            @php
                                                $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                            @endphp
                                            @if($submissionTimeliness)
                                                <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                    {{ $submissionTimeliness['label'] }}
                                                </span>
                                            @endif
                                        </span>
                                        @php
                                            $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                            $isDilgPO = $isWorkflowValidator && in_array(Auth::user()->province, $cordilleraProvinces);
                                            $hasPoApproval = $writtenNotices[$quarter]
                                                && $writtenNotices[$quarter]->speaker_approved_at_dilg_po;
                                        @endphp
                                        @if($hasPoApproval)
                                            @php
                                                $poTimestamp = $writtenNotices[$quarter]->speaker_approved_at_dilg_po;
                                                $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                $poApproverId = $writtenNotices[$quarter]->speaker_approved_by_dilg_po ?? $writtenNotices[$quarter]->speaker_approved_by;
                                                $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                        @endif
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_approved_at_dilg_ro && $writtenNotices[$quarter]->speaker_status === 'approved')
                                            @php
                                                $roTimestamp = $writtenNotices[$quarter]->speaker_approved_at_dilg_ro;
                                                $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                $roApproverId = $writtenNotices[$quarter]->speaker_approved_by_dilg_ro ?? $writtenNotices[$quarter]->speaker_approved_by;
                                                $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="speaker_house" class="dashboard-file-input" accept="image/*,.pdf" style="flex: 1; min-width: 200px;" onchange="showSaveButton(this, 'speaker-save-btn-{{ $quarter }}', 'speaker-filename-{{ $quarter }}')" {{ !$canUploadFundUtilizationDocuments || $isQuarterIndividualUploadLocked || ($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path && !$isSpeakerReturned) ? 'disabled' : '' }} title="{{ !$canUploadFundUtilizationDocuments ? 'Only LGU User and DILG Provincial Office users can upload documents.' : ($isQuarterIndividualUploadLocked ? $individualUploadLockTitle : (($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path && !$isSpeakerReturned) ? 'File already uploaded. Delete the current file to upload a new one.' : '')) }}">
                                    <button type="submit" id="speaker-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="speaker-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->speaker_house_path) }}
                                    @endif
                                </div>

@if($isLguWorkflowUser)
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-speaker', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'speaker_status', 'speaker_encoder_id') && !$isSpeakerUnderValidation && $writtenNotices[$quarter]->speaker_status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($speakerValidationState))
                <button type="button" onclick="deleteDocument('written-notice-speaker', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->speaker_house_path || $isSpeakerReturned))
        <button type="button" onclick="toggleAccordion('speaker-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
            <i class="fas fa-chevron-down" id="icon-speaker-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
        </button>
        <div id="speaker-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
            <textarea id="textarea-speaker-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->speaker_remarks ?? '' }}</textarea>
            <button type="button" onclick="saveRemarksAjax('speaker-house', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
        </div>
    @endif
@elseif($isWorkflowValidator)
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPOSpeaker = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasSpeakerFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path;
                                        $shouldHideDeleteForDilgSpeaker = $isDilgPOSpeaker || $hasSpeakerFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->speaker_house_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-speaker', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'speaker_status', 'speaker_encoder_id') && !$isSpeakerUnderValidation && $writtenNotices[$quarter]->speaker_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-speaker', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if($shouldShowValidationActions($speakerValidationState))
                @if($writtenNotices[$quarter]->speaker_status === 'pending')
                    @if(!($speakerValidationState['return_only'] ?? false))
                        <button type="button" onclick="openRemarksModal('written-notice-speaker', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    @endif
                    <button type="button" onclick="openRemarksModal('written-notice-speaker', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->speaker_house_path || $writtenNotices[$quarter]->user_remarks))
                                    <button type="button" onclick="toggleAccordion('speaker-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-speaker-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="speaker-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-speaker-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->speaker_remarks ?? '' }}</textarea>
                                        <button type="button" onclick="saveRemarksAjax('speaker-house', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                    </div>
                                    @endif
                                @endif
                            </div>

                            <!-- President of the Senate -->
                            @php
                                $hasPresidentFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path;
                                $presidentValidationState = $resolveValidationState(
                                    $writtenNotices[$quarter],
                                    (bool) $hasPresidentFile,
                                    'written-notice-president',
                                    $quarter,
                                    'president_status',
                                    'president_approved_at_dilg_po',
                                    'president_approved_at_dilg_ro',
                                    'president_encoder_id'
                                );
                                $presidentFieldBg = $hasPresidentFile ? '#fffbeb' : '#f9fafb';
                                $isPresidentReturned = $presidentValidationState['is_returned'];
                                if ($isPresidentReturned) {
                                    $presidentFieldBg = '#fee2e2';
                                }
                                $isPresidentPendingDilgRoValidation = $presidentValidationState['is_pending_regional'];
                                $isPresidentApprovedByDilgRo = $presidentValidationState['is_approved'] && $presidentValidationState['required_validator'] === 'regional';
                                
                                if ($isPresidentReturned) {
                                    $presidentStatusColor = '#ef4444';
                                    $presidentStatusLabel = 'Returned';
                                } elseif ($presidentValidationState['is_approved']) {
                                    $presidentStatusColor = '#059669';
                                    $presidentStatusLabel = 'Approved';
                                } elseif ($presidentValidationState['is_pending_regional']) {
                                    $presidentStatusColor = '#3b82f6';
                                    $presidentStatusLabel = 'For DILG Regional Office Validation';
                                } else {
                                    $presidentStatusColor = $hasPresidentFile ? '#10b981' : '#f59e0b';
                                    $presidentStatusLabel = $hasPresidentFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                }

                                $isPresidentForPoValidation = $presidentValidationState['is_pending_provincial'];
                                $isPresidentUnderValidation = $isPresidentPendingDilgRoValidation || $isPresidentForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $presidentFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>President of the Senate</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $presidentStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $presidentStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path)
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                            @php
                                                $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'president_uploaded_at', 'president_encoder_id');
                                                $uploadedTime = $uploadedInfo['time'];
                                                $encoderName = $uploadedInfo['name'];
                                            @endphp
                                            Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                            @php
                                                $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                            @endphp
                                            @if($submissionTimeliness)
                                                <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                    {{ $submissionTimeliness['label'] }}
                                                </span>
                                            @endif
                                        </span>
                                        @php
                                            $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                            $isDilgPO = $isWorkflowValidator && in_array(Auth::user()->province, $cordilleraProvinces);
                                            $hasPoApproval = $writtenNotices[$quarter]
                                                && $writtenNotices[$quarter]->president_approved_at_dilg_po;
                                        @endphp
                                        @if($hasPoApproval)
                                            @php
                                                $poTimestamp = $writtenNotices[$quarter]->president_approved_at_dilg_po;
                                                $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                $poApproverId = $writtenNotices[$quarter]->president_approved_by_dilg_po ?? $writtenNotices[$quarter]->president_approved_by;
                                                $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                        @endif
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_approved_at_dilg_ro && $writtenNotices[$quarter]->president_status === 'approved')
                                            @php
                                                $roTimestamp = $writtenNotices[$quarter]->president_approved_at_dilg_ro;
                                                $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                $roApproverId = $writtenNotices[$quarter]->president_approved_by_dilg_ro ?? $writtenNotices[$quarter]->president_approved_by;
                                                $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="president_senate" class="dashboard-file-input" accept="image/*,.pdf" style="flex: 1; min-width: 200px;" onchange="showSaveButton(this, 'president-save-btn-{{ $quarter }}', 'president-filename-{{ $quarter }}')" {{ !$canUploadFundUtilizationDocuments || $isQuarterIndividualUploadLocked || ($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path && !$isPresidentReturned) ? 'disabled' : '' }} title="{{ !$canUploadFundUtilizationDocuments ? 'Only LGU User and DILG Provincial Office users can upload documents.' : ($isQuarterIndividualUploadLocked ? $individualUploadLockTitle : (($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path && !$isPresidentReturned) ? 'File already uploaded. Delete the current file to upload a new one.' : '')) }}">
                                    <button type="submit" id="president-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="president-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->president_senate_path) }}
                                    @endif
                                </div>

@if($isLguWorkflowUser)
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-president', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'president_status', 'president_encoder_id') && !$isPresidentUnderValidation && $writtenNotices[$quarter]->president_status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($presidentValidationState))
                <button type="button" onclick="deleteDocument('written-notice-president', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
@elseif($isWorkflowValidator)
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPOPresident = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasPresidentFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path;
                                        $shouldHideDeleteForDilgPresident = $isDilgPOPresident || $hasPresidentFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->president_senate_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-president', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'president_status', 'president_encoder_id') && !$isPresidentUnderValidation && $writtenNotices[$quarter]->president_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-president', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if($shouldShowValidationActions($presidentValidationState))
                @if($writtenNotices[$quarter]->president_status === 'pending')
                    @if(!($presidentValidationState['return_only'] ?? false))
                        <button type="button" onclick="openRemarksModal('written-notice-president', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    @endif
                    <button type="button" onclick="openRemarksModal('written-notice-president', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->president_senate_path || $writtenNotices[$quarter]->user_remarks))
                                    <button type="button" onclick="toggleAccordion('president-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-president-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="president-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-president-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->president_remarks ?? '' }}</textarea>
                                        <button type="button" onclick="saveRemarksAjax('president-senate', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                    </div>
                                    @endif
                                @endif
                            </div>

                            <!-- House Committee on Appropriation -->
                            @php
                                $hasHouseFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path;
                                $houseValidationState = $resolveValidationState(
                                    $writtenNotices[$quarter],
                                    (bool) $hasHouseFile,
                                    'written-notice-house',
                                    $quarter,
                                    'house_status',
                                    'house_approved_at_dilg_po',
                                    'house_approved_at_dilg_ro',
                                    'house_encoder_id'
                                );
                                $houseFieldBg = $hasHouseFile ? '#fffbeb' : '#f9fafb';

                                $isHousePendingDilgRoValidation = $houseValidationState['is_pending_regional'];
                                $isHouseApprovedByDilgRo = $houseValidationState['is_approved'] && $houseValidationState['required_validator'] === 'regional';
                                $isHouseReturned = $houseValidationState['is_returned'];
                                
                                if ($isHouseReturned) {
                                    $houseStatusColor = '#ef4444';
                                    $houseStatusLabel = 'Returned';
                                    $houseFieldBg = '#fee2e2';
                                } else {
                                    if ($houseValidationState['is_approved']) {
                                        $houseStatusColor = '#059669';
                                        $houseStatusLabel = 'Approved';
                                    } elseif ($houseValidationState['is_pending_regional']) {
                                        $houseStatusColor = '#3b82f6';
                                        $houseStatusLabel = 'For DILG Regional Office Validation';
                                    } else {
                                        $houseStatusColor = $hasHouseFile ? '#10b981' : '#f59e0b';
                                        $houseStatusLabel = $hasHouseFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                    }
                                }

                                $isHouseForPoValidation = $houseValidationState['is_pending_provincial'];
                                $isHouseUnderValidation = $isHousePendingDilgRoValidation || $isHouseForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $houseFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>House Committee on Appropriation</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $houseStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $houseStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path)
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                            @php
                                                $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'house_uploaded_at', 'house_encoder_id');
                                                $uploadedTime = $uploadedInfo['time'];
                                                $encoderName = $uploadedInfo['name'];
                                            @endphp
                                            Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                            @php
                                                $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                            @endphp
                                            @if($submissionTimeliness)
                                                <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                    {{ $submissionTimeliness['label'] }}
                                                </span>
                                            @endif
                                        </span>
                                        @php
                                            $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                            $isDilgPO = $isWorkflowValidator && in_array(Auth::user()->province, $cordilleraProvinces);
                                            $hasPoApproval = $writtenNotices[$quarter]
                                                && $writtenNotices[$quarter]->house_approved_at_dilg_po;
                                        @endphp
                                        @if($hasPoApproval)
                                            @php
                                                $poTimestamp = $writtenNotices[$quarter]->house_approved_at_dilg_po;
                                                $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                $poApproverId = $writtenNotices[$quarter]->house_approved_by_dilg_po ?? $writtenNotices[$quarter]->house_approved_by;
                                                $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                        @endif
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_approved_at_dilg_ro && $writtenNotices[$quarter]->house_status === 'approved')
                                            @php
                                                $roTimestamp = $writtenNotices[$quarter]->house_approved_at_dilg_ro;
                                                $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                $roApproverId = $writtenNotices[$quarter]->house_approved_by_dilg_ro ?? $writtenNotices[$quarter]->house_approved_by;
                                                $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="house_committee" class="dashboard-file-input" accept="image/*,.pdf" style="flex: 1; min-width: 200px;" onchange="showSaveButton(this, 'house-save-btn-{{ $quarter }}', 'house-filename-{{ $quarter }}')" {{ !$canUploadFundUtilizationDocuments || $isQuarterIndividualUploadLocked || ($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path && !$isHouseReturned) ? 'disabled' : '' }} title="{{ !$canUploadFundUtilizationDocuments ? 'Only LGU User and DILG Provincial Office users can upload documents.' : ($isQuarterIndividualUploadLocked ? $individualUploadLockTitle : (($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path && !$isHouseReturned) ? 'File already uploaded. Delete the current file to upload a new one.' : '')) }}">
                                    <button type="submit" id="house-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="house-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->house_committee_path) }}
                                    @endif
                                </div>

@if($isLguWorkflowUser)
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-house', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'house_status', 'house_encoder_id') && !$isHouseUnderValidation && $writtenNotices[$quarter]->house_status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($houseValidationState))
                <button type="button" onclick="deleteDocument('written-notice-house', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
@elseif($isWorkflowValidator)
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPOHouse = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasHouseFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path;
                                        $shouldHideDeleteForDilgHouse = $isDilgPOHouse || $hasHouseFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->house_committee_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-house', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'house_status', 'house_encoder_id') && !$isHouseUnderValidation && $writtenNotices[$quarter]->house_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-house', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if($shouldShowValidationActions($houseValidationState))
                @if($writtenNotices[$quarter]->house_status === 'pending')
                    @if(!($houseValidationState['return_only'] ?? false))
                        <button type="button" onclick="openRemarksModal('written-notice-house', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    @endif
                    <button type="button" onclick="openRemarksModal('written-notice-house', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->house_committee_path || $writtenNotices[$quarter]->user_remarks))
                                    <button type="button" onclick="toggleAccordion('house-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-house-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="house-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-house-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->house_remarks ?? '' }}</textarea>
                                        <button type="button" onclick="saveRemarksAjax('house-committee', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                    </div>
                                    @endif
                                @endif
                            </div>

                            <!-- Senate Committee on Finance -->
                            @php
                                $hasSenateFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path;
                                $senateValidationState = $resolveValidationState(
                                    $writtenNotices[$quarter],
                                    (bool) $hasSenateFile,
                                    'written-notice-senate',
                                    $quarter,
                                    'senate_status',
                                    'senate_approved_at_dilg_po',
                                    'senate_approved_at_dilg_ro',
                                    'senate_encoder_id'
                                );
                                $senateFieldBg = $hasSenateFile ? '#fffbeb' : '#f9fafb';
                                $isSenateReturned = $senateValidationState['is_returned'];
                                
                                if ($isSenateReturned) {
                                    $senateFieldBg = '#fee2e2';
                                }
                                $isSenatePendingDilgRoValidation = $senateValidationState['is_pending_regional'];
                                $isSenateApprovedByDilgRo = $senateValidationState['is_approved'] && $senateValidationState['required_validator'] === 'regional';
                                
                                if ($isSenateReturned) {
                                    $senateStatusColor = '#ef4444';
                                    $senateStatusLabel = 'Returned';
                                } elseif ($senateValidationState['is_approved']) {
                                    $senateStatusColor = '#059669';
                                    $senateStatusLabel = 'Approved';
                                } elseif ($senateValidationState['is_pending_regional']) {
                                    $senateStatusColor = '#3b82f6';
                                    $senateStatusLabel = 'For DILG Regional Office Validation';
                                } else {
                                    $senateStatusColor = $hasSenateFile ? '#10b981' : '#f59e0b';
                                    $senateStatusLabel = $hasSenateFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                                }

                                $isSenateForPoValidation = $senateValidationState['is_pending_provincial'];
                                $isSenateUnderValidation = $isSenatePendingDilgRoValidation || $isSenateForPoValidation;
                            @endphp
                            <div style="padding: 12px; background-color: {{ $senateFieldBg }}; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Senate Committee on Finance</span>
                                    <span style="display: inline-block; padding: 2px 8px; background-color: {{ $senateStatusColor }}; color: white; border-radius: 12px; font-size: 10px; font-weight: 600; white-space: nowrap;">
                                        {{ $senateStatusLabel }}
                                    </span>
                                </label>
                                <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                                @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path)
                                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                                        <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                            @php
                                                $uploadedInfo = $resolveUploaderMeta($writtenNotices[$quarter], 'senate_uploaded_at', 'senate_encoder_id');
                                                $uploadedTime = $uploadedInfo['time'];
                                                $encoderName = $uploadedInfo['name'];
                                            @endphp
                                            Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                            @php
                                                $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                            @endphp
                                            @if($submissionTimeliness)
                                                <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                                    {{ $submissionTimeliness['label'] }}
                                                </span>
                                            @endif
                                        </span>
                                        @php
                                            $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                            $isDilgPO = $isWorkflowValidator && in_array(Auth::user()->province, $cordilleraProvinces);
                                            $hasPoApproval = $writtenNotices[$quarter]
                                                && $writtenNotices[$quarter]->senate_approved_at_dilg_po;
                                        @endphp
                                        @if($hasPoApproval)
                                            @php
                                                $poTimestamp = $writtenNotices[$quarter]->senate_approved_at_dilg_po;
                                                $poApprovedAt = is_string($poTimestamp) ? \Carbon\Carbon::parse($poTimestamp)->setTimezone(config('app.timezone')) : $poTimestamp->setTimezone(config('app.timezone'));
                                                $poApproverId = $writtenNotices[$quarter]->senate_approved_by_dilg_po ?? $writtenNotices[$quarter]->senate_approved_by;
                                                $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                                $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}</span>
                                        @endif
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_approved_at_dilg_ro && $writtenNotices[$quarter]->senate_status === 'approved')
                                            @php
                                                $roTimestamp = $writtenNotices[$quarter]->senate_approved_at_dilg_ro;
                                                $roApprovedAt = is_string($roTimestamp) ? \Carbon\Carbon::parse($roTimestamp)->setTimezone(config('app.timezone')) : $roTimestamp->setTimezone(config('app.timezone'));
                                                $roApproverId = $writtenNotices[$quarter]->senate_approved_by_dilg_ro ?? $writtenNotices[$quarter]->senate_approved_by;
                                                $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                                $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                            @endphp
                                            <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}</span>
                                        @endif
                                    </label>
                                @endif
                                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                                    <input type="file" name="senate_committee" class="dashboard-file-input" accept="image/*,.pdf" style="flex: 1; min-width: 200px;" onchange="showSaveButton(this, 'senate-save-btn-{{ $quarter }}', 'senate-filename-{{ $quarter }}')" {{ !$canUploadFundUtilizationDocuments || $isQuarterIndividualUploadLocked || ($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path && !$isSenateReturned) ? 'disabled' : '' }} title="{{ !$canUploadFundUtilizationDocuments ? 'Only LGU User and DILG Provincial Office users can upload documents.' : ($isQuarterIndividualUploadLocked ? $individualUploadLockTitle : (($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path && !$isSenateReturned) ? 'File already uploaded. Delete the current file to upload a new one.' : '')) }}">
                                    <button type="submit" id="senate-save-btn-{{ $quarter }}" form="written-notice-form-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                                        <i class="fas fa-upload"></i> Submit
                                    </button>
                                </div>
                                <div id="senate-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                                    @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path)
                                        <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($writtenNotices[$quarter]->senate_committee_path) }}
                                    @endif
                                </div>

@if($isLguWorkflowUser)
    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-senate', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'senate_status', 'senate_encoder_id') && !$isSenateUnderValidation && $writtenNotices[$quarter]->senate_status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($senateValidationState))
                <button type="button" onclick="deleteDocument('written-notice-senate', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
        @endif
    </div>
                                @elseif($isWorkflowValidator)
                                    @php
                                        $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                        $isDilgPOSenate = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                        $hasSenateFile = $writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path;
                                        $shouldHideDeleteForDilgSenate = $isDilgPOSenate || $hasSenateFile;
                                    @endphp
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                        @if($writtenNotices[$quarter] && $writtenNotices[$quarter]->senate_committee_path)
            <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'written-notice-senate', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                <i class="fas fa-eye"></i> View
            </a>
            @if($canDeleteFundUtilizationDocument($writtenNotices[$quarter], 'senate_status', 'senate_encoder_id') && !$isSenateUnderValidation && $writtenNotices[$quarter]->senate_status !== 'approved')
                <button type="button" onclick="deleteDocument('written-notice-senate', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
            @endif
            @if($shouldShowValidationActions($senateValidationState))
                @if($writtenNotices[$quarter]->senate_status === 'pending')
                    @if(!($senateValidationState['return_only'] ?? false))
                        <button type="button" onclick="openRemarksModal('written-notice-senate', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    @endif
                    <button type="button" onclick="openRemarksModal('written-notice-senate', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                        <i class="fas fa-undo"></i> Return
                    </button>
                @endif
            @endif
                                        @endif
                                    </div>
                                    @if($writtenNotices[$quarter] && ($writtenNotices[$quarter]->senate_committee_path || $writtenNotices[$quarter]->user_remarks))
                                    <button type="button" onclick="toggleAccordion('senate-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                        <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                                        <i class="fas fa-chevron-down" id="icon-senate-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                                    </button>
                                    <div id="senate-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                                        <textarea id="textarea-senate-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $writtenNotices[$quarter]->senate_remarks ?? '' }}</textarea>
                                        <button type="button" onclick="saveRemarksAjax('senate-committee', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                                    </div>
                        @endif
                    @endif
                </div>


            </div>
                    </div>
                </form>

                @if($writtenNotices[$quarter])
                    <!-- DILG Approval Buttons -->
                    @if($isWorkflowValidator)
                        <!-- Remarks Section -->
                        @if($writtenNotices[$quarter]->approval_remarks)
                            <div style="margin-top: 12px; padding: 10px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
                                <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">Approval Remarks:</p>
                                <p style="color: #374151; font-size: 13px; margin: 0;">{{ $writtenNotices[$quarter]->approval_remarks }}</p>
                            </div>
                        @endif
                    @elseif($isLguWorkflowUser && $writtenNotices[$quarter]->approval_remarks)
                        <!-- View Remarks for LGU -->
                        <div style="margin-top: 12px; padding: 10px; background-color: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 4px;">
                            <p style="color: #374151; font-weight: 600; font-size: 12px; margin-bottom: 4px;">DILG Remarks:</p>
                            <p style="color: #374151; font-size: 13px; margin: 0;">{{ $writtenNotices[$quarter]->approval_remarks }}</p>
                        </div>
                    @endif
                @endif


            </div>
            </div>

            <!-- Full Disclosure Policy Section -->
            @php
                $hasFdpFile = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path;
                $isFdpReturned = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_status === 'returned';
                $fdpBackgroundColor = $isFdpReturned ? '#fee2e2' : ($hasFdpFile ? '#fffbeb' : 'transparent');
                $fdpBorderColor = $isFdpReturned ? '#ef4444' : ($hasFdpFile ? '#059669' : '#f59e0b');
            @endphp
            <div style="border: 1px solid #e5e7eb; border-left: 4px solid {{ $fdpBorderColor }}; border-radius: 8px; margin-bottom: 18px; overflow: hidden; background-color: white;">
                <h3 style="margin: 0; padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb; font-weight: 400; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                    <span style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        <span style="width: 30px; height: 30px; background: rgba(220,38,38,0.1); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-file-pdf" style="color: #dc2626; font-size: 13px;"></i>
                        </span>
                        <span style="display: flex; flex-direction: column; gap: 1px;">
                            <span style="color: #1e293b; font-size: 13px; font-weight: 700; line-height: 1.3;">Full Disclosure Policy (FDP)</span>
                            <span style="color: #64748b; font-size: 11px; font-weight: 400;">On PDF Format</span>
                        </span>
                    </span>
                    @php
                        $hasFdpFile = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path;
                        $fdpValidationState = $resolveValidationState(
                            $fdpDocuments[$quarter],
                            (bool) $hasFdpFile,
                            'fdp',
                            $quarter,
                            'fdp_status',
                            'approved_at_dilg_po',
                            'approved_at_dilg_ro',
                            'fdp_encoder_id'
                        );
                        $isFdpPendingDilgRoValidation = $fdpValidationState['is_pending_regional'];
                        $isFdpApprovedByDilgRo = $fdpValidationState['is_approved'] && $fdpValidationState['required_validator'] === 'regional';
                        $isFdpReturned = $fdpValidationState['is_returned'];

                        if ($isFdpReturned) {
                            $fdpStatusColor = '#ef4444';
                            $fdpStatusLabel = 'Returned';
                            $fdpBackgroundColor = '#fee2e2';
                        } else {
                            if ($fdpValidationState['is_approved']) {
                                $fdpStatusColor = '#059669';
                                $fdpStatusLabel = 'Approved';
                            } elseif ($fdpValidationState['is_pending_regional']) {
                                $fdpStatusColor = '#3b82f6';
                                $fdpStatusLabel = 'For DILG Regional Office Validation';
                            } else {
                                $fdpStatusColor = $hasFdpFile ? '#10b981' : '#f59e0b';
                                $fdpStatusLabel = $hasFdpFile ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                            }
                        }

                        $isFdpForPoValidation = $fdpValidationState['is_pending_provincial'];
                        $isFdpUnderValidation = $isFdpPendingDilgRoValidation || $isFdpForPoValidation;
                    @endphp
                    <span style="display: inline-flex; align-items: center; padding: 3px 10px; background-color: {{ $fdpStatusColor }}; color: white; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $fdpStatusLabel }}
                    </span>
                </h3>
                <div style="padding: 16px;">
                <div style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <label style="display: none;"></label>
                    <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                        @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path)
                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                @php
                                    $uploadedInfo = $resolveUploaderMeta($fdpDocuments[$quarter], 'fdp_uploaded_at', 'fdp_encoder_id');
                                    $uploadedTime = $uploadedInfo['time'];
                                    $encoderName = $uploadedInfo['name'];
                                @endphp
                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $encoderName }}
                                @php
                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                @endphp
                                @if($submissionTimeliness)
                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                        {{ $submissionTimeliness['label'] }}
                                    </span>
                                @endif
                            </span>
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPO = $isWorkflowValidator && in_array(Auth::user()->province, $cordilleraProvinces);
                                $hasPoApproval = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->approved_at_dilg_po;
                            @endphp
                            @if($hasPoApproval)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">
                                    @php
                                        $poApprovedAt = is_string($fdpDocuments[$quarter]->approved_at_dilg_po) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->approved_at_dilg_po)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->approved_at_dilg_po->setTimezone(config('app.timezone'));
                                        $poApproverId = $fdpDocuments[$quarter]->approved_by_dilg_po ?? $fdpDocuments[$quarter]->approved_by;
                                        $poApproverUser = $poApproverId ? \App\Models\User::where('idno', $poApproverId)->first() : null;
                                        $poApproverName = $poApproverUser ? trim($poApproverUser->fname . ' ' . $poApproverUser->lname) : 'Unknown';
                                    @endphp
                                    DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }} by {{ $poApproverName }}
                                </span>
                            @endif
                            @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->approved_at_dilg_ro)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">
                                    @php
                                        $roApprovedAt = is_string($fdpDocuments[$quarter]->approved_at_dilg_ro) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->approved_at_dilg_ro)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->approved_at_dilg_ro->setTimezone(config('app.timezone'));
                                        $roApproverId = $fdpDocuments[$quarter]->approved_by_dilg_ro ?? $fdpDocuments[$quarter]->approved_by;
                                        $roApproverUser = $roApproverId ? \App\Models\User::where('idno', $roApproverId)->first() : null;
                                        $roApproverName = $roApproverUser ? trim($roApproverUser->fname . ' ' . $roApproverUser->lname) : 'Unknown';
                                    @endphp
                                    DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }} by {{ $roApproverName }}
                                </span>
                            @endif
                            @if($isFdpReturned && $fdpDocuments[$quarter]->fdp_approved_at)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">
                                    @php
                                        $returnedAt = is_string($fdpDocuments[$quarter]->fdp_approved_at) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->fdp_approved_at)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->fdp_approved_at->setTimezone(config('app.timezone'));
                                        $fdpApproverUser = $fdpDocuments[$quarter]->fdp_approved_by ? \App\Models\User::where('idno', $fdpDocuments[$quarter]->fdp_approved_by)->first() : null;
                                        $fdpApproverName = $fdpApproverUser ? trim($fdpApproverUser->fname . ' ' . $fdpApproverUser->lname) : 'Unknown';
                                    @endphp
                                    Returned at: {{ $returnedAt->format('M d, Y h:i A') }} by {{ $fdpApproverName }}
                                </span>
                            @endif
                        @endif
                    </label>
                    <form action="{{ route('fund-utilization.upload-fdp', $report->project_code) }}" method="POST" enctype="multipart/form-data" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                        @csrf
                        <input type="hidden" name="quarter" value="{{ $quarter }}">
                        <input type="file" name="fdp_file" class="dashboard-file-input" accept="image/*,.pdf" style="flex: 1; min-width: 200px;" onchange="showSaveButton(this, 'fdp-save-btn-{{ $quarter }}', 'fdp-filename-{{ $quarter }}')" {{ !$canUploadFundUtilizationDocuments || $isQuarterIndividualUploadLocked || ($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path && !$isFdpReturned) ? 'disabled' : '' }} title="{{ !$canUploadFundUtilizationDocuments ? 'Only LGU User and DILG Provincial Office users can upload documents.' : ($isQuarterIndividualUploadLocked ? $individualUploadLockTitle : (($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path && !$isFdpReturned) ? 'File already uploaded. Delete the current file to upload a new one.' : '')) }}">
                        <button type="submit" id="fdp-save-btn-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                            <i class="fas fa-upload"></i> Submit
                        </button>
                    </form>
                    <div id="fdp-filename-{{ $quarter }}" style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                        @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path)
                            <i class="fas fa-file" style="margin-right: 4px;"></i>Uploaded: {{ basename($fdpDocuments[$quarter]->fdp_file_path) }}
                        @endif
                    </div>

                    @if($isLguWorkflowUser)
                        @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path)
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'fdp', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                @if($canDeleteFundUtilizationDocument($fdpDocuments[$quarter], 'fdp_status', 'fdp_encoder_id') && !$isFdpUnderValidation && $fdpDocuments[$quarter]->fdp_status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($fdpValidationState))
                                    <button type="button" onclick="deleteDocument('fdp', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                @endif
                            </div>
                        @endif
                    @elseif($isWorkflowValidator)
                        @if($fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path)
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPOFdp = in_array(Auth::user()->province, $cordilleraProvinces) || Auth::user()->province === 'Regional Office';
                                $hasFdpFile = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->fdp_file_path;
                                $shouldHideDeleteForDilgFdp = $isDilgPOFdp || $hasFdpFile;
                            @endphp
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                <a href="{{ route('fund-utilization.view-document', ['projectCode' => $report->project_code, 'docType' => 'fdp', 'quarter' => $quarter]) }}" target="_blank" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                @if($shouldShowValidationActions($fdpValidationState))
                                    @if($canDeleteFundUtilizationDocument($fdpDocuments[$quarter], 'fdp_status', 'fdp_encoder_id') && !$isFdpUnderValidation && $fdpDocuments[$quarter]->fdp_status !== 'approved' && !$shouldHideLguDeleteUntilProvincialReturn($fdpValidationState))
                                        <button type="button" onclick="deleteDocument('fdp', '{{ $quarter }}')" title="Delete document" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    @endif
                                    @if($fdpDocuments[$quarter]->fdp_status === 'pending')
                                        @if(!($fdpValidationState['return_only'] ?? false))
                                            <button type="button" onclick="openRemarksModal('fdp', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        @endif
                                        <button type="button" onclick="openRemarksModal('fdp', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-undo"></i> Return
                                        </button>
                                    @endif
                                @endif
                            </div>
                        @endif
                        @if($fdpDocuments[$quarter] && ($fdpDocuments[$quarter]->fdp_file_path || $fdpDocuments[$quarter]->user_remarks))
                        <button type="button" onclick="toggleAccordion('fdp-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                            <i class="fas fa-chevron-down" id="icon-fdp-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                        </button>
                        <div id="fdp-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                            <textarea id="textarea-fdp-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $fdpDocuments[$quarter]->user_remarks ?? '' }}</textarea>
                            <button type="button" onclick="saveRemarksAjax('fdp', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                        </div>
                        @endif
                    @endif

                    @if($isLguWorkflowUser && $fdpDocuments[$quarter])
                        <button type="button" onclick="toggleAccordion('fdp-notes-{{ $quarter }}')" style="width: 100%; padding: 6px; background-color: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; text-align: left; cursor: pointer; font-weight: 600; font-size: 11px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                            <span><i class="fas fa-comment" style="margin-right: 4px;"></i> Notes</span>
                            <i class="fas fa-chevron-down" id="icon-fdp-notes-{{ $quarter }}" style="transition: transform 0.3s; font-size: 10px;"></i>
                        </button>
                        <div id="fdp-notes-{{ $quarter }}" style="display: none; margin-top: 6px; padding: 6px; background-color: white; border: 1px solid #e5e7eb; border-radius: 4px;">
                            <textarea id="textarea-fdp-notes-{{ $quarter }}" placeholder="Add notes..." style="width: 100%; padding: 6px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 11px; font-family: inherit; resize: vertical; min-height: 50px;">{{ $fdpDocuments[$quarter]->user_remarks ?? '' }}</textarea>
                            <button type="button" onclick="saveRemarksAjax('fdp', '{{ $quarter }}')" style="margin-top: 4px; width: 100%; padding: 4px; background-color: #059669; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 10px;"><i class="fas fa-check" style="margin-right: 8px;"></i>Save</button>
                        </div>
                    @endif
                </div>
            </div>
            </div>

            <!-- LGU Posting Link Section -->
            @php
                $postingLinkValue = $fdpDocuments[$quarter]->posting_link ?? '';
                $safePostingLink = \App\Support\InputSanitizer::sanitizeHttpUrl($postingLinkValue);
                $hasPostingLink = $fdpDocuments[$quarter] && $postingLinkValue !== '';
                $postingValidationState = $resolveValidationState(
                    $fdpDocuments[$quarter],
                    $hasPostingLink,
                    'posting-link',
                    $quarter,
                    'posting_status',
                    'posting_approved_at_dilg_po',
                    'posting_approved_at_dilg_ro',
                    'posting_encoder_id'
                );
                $hasSafePostingLink = !empty($safePostingLink);
                $isPostingReturned = $postingValidationState['is_returned'];
                $postingBackgroundColor = $hasPostingLink ? '#fffbeb' : 'transparent';

                if ($isPostingReturned) {
                    $postingStatusColor = '#ef4444';
                    $postingStatusLabel = 'Returned';
                    $postingBackgroundColor = '#fee2e2';
                } elseif ($postingValidationState['is_approved']) {
                    $postingStatusColor = '#059669';
                    $postingStatusLabel = 'Approved';
                } elseif ($postingValidationState['is_pending_regional']) {
                    $postingStatusColor = '#3b82f6';
                    $postingStatusLabel = 'For DILG Regional Office Validation';
                } else {
                    $postingStatusColor = $hasPostingLink ? '#10b981' : '#f59e0b';
                    $postingStatusLabel = $hasPostingLink ? 'For DILG Provincial Office Validation' : 'Pending Upload';
                }
            @endphp
            <div style="border: 1px solid #e5e7eb; border-left: 4px solid {{ $postingStatusColor }}; border-radius: 8px; margin-bottom: 18px; overflow: hidden; background-color: white;">
                <h3 style="margin: 0; padding: 12px 16px; background-color: #f8fafc; border-bottom: 1px solid #e5e7eb; font-weight: 400; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                    <span style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        <span style="width: 30px; height: 30px; background: rgba(5,150,105,0.1); border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-link" style="color: #059669; font-size: 13px;"></i>
                        </span>
                        <span style="display: flex; flex-direction: column; gap: 1px;">
                            <span style="color: #1e293b; font-size: 13px; font-weight: 700; line-height: 1.3;">LGU Website / Social Media</span>
                            <span style="color: #64748b; font-size: 11px; font-weight: 400;">Posting Link</span>
                        </span>
                    </span>
                    <span style="display: inline-flex; align-items: center; padding: 3px 10px; background-color: {{ $postingStatusColor }}; color: white; border-radius: 999px; font-size: 10px; font-weight: 700; white-space: nowrap; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.04em;">
                        {{ $postingStatusLabel }}
                    </span>
                </h3>
                <div style="padding: 16px;">
                <div style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                    <label style="display: none;"></label>
                    <div data-pagasa-time style="display: none; margin-bottom: 8px; color: #059669; font-size: 11px; font-weight: 600; min-height: 16px;"></div>
                    <label style="display: block; margin-bottom: 8px; color: #374151; font-weight: 600; font-size: 12px;">
                        @if($hasPostingLink)
                            <span style="display: block; font-size: 10px; font-weight: normal; color: #6b7280; margin-top: 4px;">
                                @php
                                    $uploadedInfo = $resolveUploaderMeta($fdpDocuments[$quarter], 'posting_uploaded_at', 'posting_encoder_id');
                                    $uploadedTime = $uploadedInfo['time'];
                                    $postingEncoderName = $uploadedInfo['name'];
                                @endphp
                                Uploaded at: {{ $uploadedTime ? $uploadedTime->format('M d, Y h:i A') : '-' }} by {{ $postingEncoderName }}
                                @php
                                    $submissionTimeliness = $resolveSubmissionTimelinessTag($uploadedTime, $configuredQuarterDeadline);
                                @endphp
                                @if($submissionTimeliness)
                                    <span title="{{ $submissionTimeliness['title'] }}" style="display: inline-flex; align-items: center; margin-left: 8px; padding: 3px 8px; background-color: {{ $submissionTimeliness['background'] }}; color: {{ $submissionTimeliness['color'] }}; border: 1px solid {{ $submissionTimeliness['border'] }}; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                                        {{ $submissionTimeliness['label'] }}
                                    </span>
                                @endif
                            </span>
                            @php
                                $cordilleraProvinces = ['Abra', 'Apayao', 'Benguet', 'City of Baguio', 'Ifugao', 'Kalinga', 'Mountain Province'];
                                $isDilgPO = $isWorkflowValidator && in_array(Auth::user()->province, $cordilleraProvinces);
                                $hasPoApproval = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->posting_approved_at_dilg_po;
                                $hasRoApproval = $fdpDocuments[$quarter] && $fdpDocuments[$quarter]->posting_approved_at_dilg_ro;
                            @endphp
                            @if($hasPoApproval)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #059669; margin-top: 4px;">
                                    @php
                                        $poApprovedAt = is_string($fdpDocuments[$quarter]->posting_approved_at_dilg_po) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->posting_approved_at_dilg_po)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->posting_approved_at_dilg_po->setTimezone(config('app.timezone'));
                                    @endphp
                                    DILG Provincial Validated at: {{ $poApprovedAt->format('M d, Y h:i A') }}
                                </span>
                            @endif
                            @if($hasRoApproval)
                                <span style="display: block; font-size: 10px; font-weight: normal; color: #0891b2; margin-top: 4px;">
                                    @php
                                        $roApprovedAt = is_string($fdpDocuments[$quarter]->posting_approved_at_dilg_ro) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->posting_approved_at_dilg_ro)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->posting_approved_at_dilg_ro->setTimezone(config('app.timezone'));
                                    @endphp
                                    DILG Regional Validated at: {{ $roApprovedAt->format('M d, Y h:i A') }}
                                </span>
                            @endif
                        @endif
                        @if($isPostingReturned && $fdpDocuments[$quarter]->posting_approved_at)
                            <span style="display: block; font-size: 10px; font-weight: normal; color: #dc2626; margin-top: 4px;">
                                @php
                                    $returnedAt = is_string($fdpDocuments[$quarter]->posting_approved_at) ? \Carbon\Carbon::parse($fdpDocuments[$quarter]->posting_approved_at)->setTimezone(config('app.timezone')) : $fdpDocuments[$quarter]->posting_approved_at->setTimezone(config('app.timezone'));
                                    $postingApproverUser = $fdpDocuments[$quarter]->posting_approved_by ? \App\Models\User::where('idno', $fdpDocuments[$quarter]->posting_approved_by)->first() : null;
                                    $postingApproverName = $postingApproverUser ? trim($postingApproverUser->fname . ' ' . $postingApproverUser->lname) : 'Unknown';
                                @endphp
                                Returned at: {{ $returnedAt->format('M d, Y h:i A') }} by {{ $postingApproverName }}
                            </span>
                        @endif
                    </label>
                    <form action="{{ route('fund-utilization.save-posting-link', $report->project_code) }}" method="POST" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; align-items: center;">
                        @csrf
                        <input type="hidden" name="quarter" value="{{ $quarter }}">
                        <input type="text" name="posting_link" value="{{ $postingLinkValue }}" placeholder="https://example.com/post" style="flex: 1; min-width: 240px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px;" oninput="showSaveButtonForText(this, 'posting-save-btn-{{ $quarter }}')" {{ !$canUploadFundUtilizationDocuments || $isQuarterIndividualUploadLocked ? 'disabled' : '' }} title="{{ !$canUploadFundUtilizationDocuments ? 'Only LGU User and DILG Provincial Office users can upload documents.' : ($isQuarterIndividualUploadLocked ? $individualUploadLockTitle : '') }}">
                        <button type="submit" id="posting-save-btn-{{ $quarter }}" style="padding: 10px 20px; background-color: #059669; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; width: auto;">
                            <i class="fas fa-save"></i> Submit
                        </button>
                    </form>
                    <div style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 8px;">
                        @if($hasPostingLink)
                            <i class="fas fa-link" style="margin-right: 4px;"></i>Current link:
                            @if($hasSafePostingLink)
                                <a href="{{ $safePostingLink }}" target="_blank" rel="noopener noreferrer" style="color: #2563eb; text-decoration: underline; word-break: break-all;">
                                    {{ $postingLinkValue }}
                                </a>
                            @else
                                <span style="color: #374151; word-break: break-all;">{{ $postingLinkValue }}</span>
                            @endif
                        @endif
                    </div>

                    @if($isLguWorkflowUser)
                        @if($hasPostingLink || $isPostingReturned)
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                @if($hasSafePostingLink)
                                    <a href="{{ $safePostingLink }}" target="_blank" rel="noopener noreferrer" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-eye"></i> Open Link
                                    </a>
                                @endif
                                @if($canDeleteFundUtilizationDocument($fdpDocuments[$quarter], 'posting_status', 'posting_encoder_id') && (!$fdpDocuments[$quarter] || $fdpDocuments[$quarter]->posting_status !== 'approved') && !$shouldHideLguDeleteUntilProvincialReturn($postingValidationState))
                                    <button type="button" onclick="deleteDocument('posting-link', '{{ $quarter }}')" title="Delete link" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                @endif
                            </div>
                        @endif
                    @elseif($isWorkflowValidator)
                        @if($hasPostingLink)
                            <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;">
                                @if($hasSafePostingLink)
                                    <a href="{{ $safePostingLink }}" target="_blank" rel="noopener noreferrer" style="padding: 6px 12px; background-color: #3b82f6; color: white; border: none; border-radius: 4px; text-align: center; text-decoration: none; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-eye"></i> Open Link
                                    </a>
                                @endif
                                @if($canDeleteFundUtilizationDocument($fdpDocuments[$quarter], 'posting_status', 'posting_encoder_id') && (!$fdpDocuments[$quarter] || $fdpDocuments[$quarter]->posting_status !== 'approved') && !$shouldHideLguDeleteUntilProvincialReturn($postingValidationState))
                                    <button type="button" onclick="deleteDocument('posting-link', '{{ $quarter }}')" title="Delete link" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                @endif
                                @if($shouldShowValidationActions($postingValidationState) && (!$fdpDocuments[$quarter] || $fdpDocuments[$quarter]->posting_status !== 'approved'))
                                    @if(!($postingValidationState['return_only'] ?? false))
                                        <button type="button" onclick="openRemarksModal('posting-link', '{{ $quarter }}', 'approve')" style="padding: 6px 12px; background-color: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    @endif
                                    <button type="button" onclick="openRemarksModal('posting-link', '{{ $quarter }}', 'return')" style="padding: 6px 12px; background-color: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 11px; white-space: nowrap;">
                                        <i class="fas fa-undo"></i> Return
                                    </button>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            </div>
                </div>
            </div>

            <div style="border: 1px solid #fde7c7; border-radius: 12px; overflow: hidden; background: #fffaf0;">
                <button type="button" onclick="toggleAccordion('batch-documents-{{ $quarter }}')" style="width: 100%; padding: 14px 18px; background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%); color: #9a3412; border: none; text-align: left; cursor: pointer; font-weight: 700; font-size: 14px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <span style="width: 30px; height: 30px; background: rgba(217,119,6,0.12); border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fas fa-box-archive" style="font-size: 13px;"></i>
                        </span>
                        <span>Batch Documents</span>
                    </span>
                    <i class="fas fa-chevron-down" id="icon-batch-documents-{{ $quarter }}" style="transition: transform 0.3s; transform: rotate(0deg);"></i>
                </button>
                <div id="batch-documents-{{ $quarter }}" style="display: none; padding: 18px 18px 0;">
                    @include('reports.fund-utilization.partials.batch-documents-card')
                </div>
            </div>

            </div>
        </div>
    @endforeach

    <style>
        button:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        a:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        input[type="file"], textarea {
            transition: all 0.3s ease;
        }

        input[type="file"]:focus, textarea:focus {
            border-color: #002C76 !important;
            box-shadow: 0 0 0 3px rgba(0, 44, 118, 0.1);
            outline: none;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1300;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }

        body.no-scroll {
            overflow: hidden;
        }

        body.viewer-modal-open .ops-detail-page > :not(#batchDocumentViewerModal) {
            user-select: none;
        }

        body.viewer-modal-open #batchDocumentViewerModal,
        body.viewer-modal-open #batchDocumentViewerModal * {
            pointer-events: auto;
        }

        .modal-content {
            background-color: #ffffff;
            margin: 6% auto;
            padding: 30px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 48px rgba(15, 23, 42, 0.22);
            position: relative;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            color: #002C76;
            font-size: 18px;
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }

        .close-modal:hover {
            color: #000;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 10px;

            border: none;

                    const form = formId
                        ? document.getElementById(formId)
                        : saveBtn.closest('form');

                    if (!form) {
                        console.error(formId ? `Form with ID ${formId} not found` : 'No form found for button');
                        return;
                    }

                    if (buttonId.startsWith('batch-document-save-btn-')) {
                        const quarter = buttonId.replace('batch-document-save-btn-', '');
                        openActionConfirmModal({
                            title: `Submit batch documents for ${quarter}?`,
                            message: 'This will submit the selected batch document files for validation. Do you want to continue?',
                            confirmLabel: 'Submit',
                            confirmBackground: 'linear-gradient(135deg, #002C76 0%, #003d9e 100%)',
                            headerBackground: 'linear-gradient(135deg, #002C76 0%, #003d9e 100%)',
                            headerBorderColor: 'rgba(255,255,255,0.12)',
                            titleColor: '#ffffff',
                            closeColor: 'rgba(255,255,255,0.85)',
                            iconBackground: 'rgba(255,255,255,0.15)',
                            iconColor: '#ffffff',
                            iconHtml: '<i class="fas fa-upload"></i>',
                            maxWidth: '620px',
                            onConfirm: function() {
                                form.dataset.batchUploadConfirmed = '1';
                                if (typeof form.requestSubmit === 'function') {
                                    form.requestSubmit();
                                } else {
                                    form.submit();
                                }
                            }
                        });
                        return;
                    }

                    form.submit();
            justify-content: center;
            align-items: center;
            padding: 24px;
            box-sizing: border-box;
        }

        #batchDocumentViewerModal .modal-content {
            margin: 0;
            width: min(1100px, 96vw);
            max-width: min(1100px, 96vw);
            max-height: calc(100vh - 48px);
        }

        .action-confirm-modal .modal-content {
            max-width: 520px;
            padding: 0;
            overflow: hidden;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.25);
        }

        .action-confirm-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 18px 10px;
            border-bottom: 1px solid #f1f5f9;
            background: #ffffff;
        }

        .action-confirm-header-main {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .action-confirm-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 44, 118, 0.1);
            color: #002c76;
            flex-shrink: 0;
        }

        .action-confirm-close {
            border: none;
            background: transparent;
            color: #64748b;
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            flex-shrink: 0;
        }

        .action-confirm-body {
            padding: 14px 18px;
            background: #ffffff;
            color: #334155;
            font-size: 14px;
            line-height: 1.6;
        }

        .toast-container {
            position: fixed;
            top: 16px;
            right: 16px;
            width: min(360px, calc(100% - 32px));
            z-index: 1300;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .toast {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            color: white;
            font-size: 13px;
            line-height: 1.4;
            box-shadow: 0 22px 44px rgba(15, 23, 42, 0.18);
            opacity: 0;
            transform: translateX(18px);
            animation: toastEnter 220ms ease-out forwards;
            pointer-events: auto;
        }

        .toast.success { background: #059669; }
        .toast.error { background: #dc2626; }
        .toast.info { background: #2563eb; }

        .toast button {
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.95);
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            padding: 0;
        }

        @keyframes toastEnter {
            from { opacity: 0; transform: translateX(18px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes toastExit {
            from { opacity: 1; transform: translateX(0); }
            to { opacity: 0; transform: translateX(18px); }
        }

        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .logs-table th,
        .logs-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
        }

        .log-pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .log-pill.upload { background-color: #d1fae5; color: #065f46; }
        .log-pill.delete { background-color: #fee2e2; color: #991b1b; }
        .log-pill.approve { background-color: #dbeafe; color: #1d4ed8; }
        .log-pill.return { background-color: #fde68a; color: #92400e; }
        .log-pill.remarks { background-color: #e0e7ff; color: #4338ca; }
        .log-pill.update { background-color: #e5e7eb; color: #374151; }

        /* Section card hover accent */
        div[style*="border-left: 4px solid"] {
            transition: box-shadow 0.18s ease;
        }
        div[style*="border-left: 4px solid"]:hover {
            box-shadow: 0 4px 14px rgba(0, 44, 118, 0.1);
        }

        /* Prevent full-width accordion buttons from jumping on hover */
        button[style*="width: 100%"]:hover {
            transform: none !important;
        }

        /* Notes toggle button polish */
        button[onclick*="toggleAccordion"] {
            transition: background-color 0.15s, color 0.15s;
        }

        @media (max-width: 768px) {
            .content-header h1 {
                font-size: 20px;
            }

            div[style*="grid-template-columns: 1fr 1fr"] {
                grid-template-columns: 1fr !important;
            }
        }
    </style>

    <!-- Logs Modal -->
    <div id="logsModal" class="modal">
        <div class="modal-content" style="max-width: 900px; padding: 0; overflow: hidden;">
            <div class="modal-header" style="margin: 0; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-history" style="color: white; font-size: 13px;"></i>
                    </div>
                    <h2 style="margin: 0; color: white; font-size: 16px; font-weight: 700;">Activity Logs</h2>
                </div>
                <button class="close-modal" onclick="closeLogsModal()" style="color: rgba(255,255,255,0.8); font-size: 22px; line-height: 1;">&times;</button>
            </div>
            <div style="padding: 20px; max-height: 60vh; overflow-y: auto;">
                @if(empty($activityLogs))
                    <div style="padding: 16px; background-color: #f9fafb; border: 1px dashed #d1d5db; border-radius: 8px; text-align: center; color: #6b7280; font-size: 13px;">
                        No activity logs found for this project.
                    </div>
                @else
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Document</th>
                                <th>Quarter</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activityLogs as $log)
                                @php
                                    $actionLabel = strtoupper($log['action'] ?? 'update');
                                    $actionClass = $log['action'] ?? 'update';
                                    $docType = $log['document_type'] ?? 'n/a';
                                    $docLabelMap = [
                                        'mov' => 'MOV',
                                        'batch-document' => 'Batch Documents',
                                        'fdp' => 'FDP',
                                        'posting-link' => 'LGU Posting Link',
                                        'written-notice' => 'Written Notice',
                                        'written-notice-dbm' => 'Written Notice (DBM)',
                                        'written-notice-dilg' => 'Written Notice (DILG)',
                                        'written-notice-speaker' => 'Written Notice (Speaker)',
                                        'written-notice-president' => 'Written Notice (President)',
                                        'written-notice-house' => 'Written Notice (House)',
                                        'written-notice-senate' => 'Written Notice (Senate)',
                                    ];
                                    $docLabel = $docLabelMap[$docType] ?? $docType;
                                    $userDisplay = $log['user_name'] ?? 'Unknown';
                                    if (!empty($log['user_agency'])) {
                                        $userDisplay .= ' (' . $log['user_agency'] . ')';
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $log['timestamp']->format('M d, Y h:i A') }}</td>
                                    <td>{{ $userDisplay }}</td>
                                    <td><span class="log-pill {{ $actionClass }}">{{ $actionLabel }}</span></td>
                                    <td>{{ $docLabel }}</td>
                                    <td>{{ $log['quarter'] ?? '—' }}</td>
                                    <td>{{ $log['remarks'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <!-- Remarks Modal -->
    <div id="remarksModal" class="modal">
        <div class="modal-content" style="padding: 0; overflow: hidden;">
            <div class="modal-header" style="margin: 0; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-comment-alt" style="color: white; font-size: 13px;"></i>
                    </div>
                    <h2 id="modalTitle" style="margin: 0; color: white; font-size: 16px; font-weight: 700;">Add Remarks</h2>
                </div>
                <button class="close-modal" onclick="closeRemarksModal()" style="color: rgba(255,255,255,0.8); font-size: 22px; line-height: 1;">&times;</button>
            </div>
            <form id="remarksForm" method="POST" style="display: none; padding: 20px;">
                @csrf
                <textarea id="remarksText" name="remarks" placeholder="Enter remarks..." style="width: 100%; padding: 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; font-family: inherit; resize: vertical; min-height: 120px;"></textarea>
                <div class="modal-buttons">
                    <button type="button" onclick="closeRemarksModal()" style="background-color: #6b7280; color: white;"><i class="fas fa-times" style="margin-right: 8px;"></i>Cancel</button>
                    <button type="submit" id="submitBtn" style="background-color: #002C76; color: white;">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div id="actionConfirmModal" class="modal action-confirm-modal">
        <div class="modal-content system-dialog-card">
            <div id="actionConfirmHeader" class="modal-header action-confirm-header system-dialog-header">
                <div class="action-confirm-header-main">
                    <div id="actionConfirmIcon" class="action-confirm-icon">
                        <i class="fas fa-circle-question"></i>
                    </div>
                    <div>
                        <h2 id="actionConfirmTitle" class="system-dialog-title" style="margin: 0; color: #0f172a; font-size: 18px;">Confirm action</h2>
                    </div>
                </div>
                <button id="actionConfirmCloseBtn" class="close-modal action-confirm-close" data-confirm-skip="true" onclick="closeActionConfirmModal()">&times;</button>
            </div>
            <div id="actionConfirmBody" class="action-confirm-body system-dialog-body">
                <div id="actionConfirmMessage">Are you sure you want to continue?</div>
                <div class="system-dialog-actions" style="padding: 12px 0 0;">
                    <button type="button" class="system-dialog-btn cancel" data-confirm-skip="true" onclick="closeActionConfirmModal()">Cancel</button>
                    <button type="button" id="actionConfirmBtn" class="system-dialog-btn confirm" data-confirm-skip="true" onclick="handleActionConfirmButtonClick()">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container" aria-live="polite" aria-atomic="true"></div>

    <div id="batchDocumentViewerModal" class="modal" aria-hidden="true">
        <div class="modal-content" style="padding: 0; overflow: hidden; background: #f8fafc;">
            <div class="modal-header" style="margin: 0; background: linear-gradient(135deg, #002C76 0%, #003d9e 100%); padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.12);">
                <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                    <div style="width: 32px; height: 32px; background: rgba(255,255,255,0.15); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-eye" style="color: white; font-size: 13px;"></i>
                    </div>
                    <div style="min-width: 0;">
                        <h2 id="batchDocumentViewerTitle" style="margin: 0; color: white; font-size: 16px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">View Document</h2>
                        <div id="batchDocumentViewerSubtitle" style="margin-top: 2px; color: rgba(255,255,255,0.78); font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></div>
                    </div>
                </div>
                <button class="close-modal" onclick="closeBatchDocumentViewerModal()" style="color: rgba(255,255,255,0.85); font-size: 22px; line-height: 1; flex-shrink: 0;">&times;</button>
            </div>
            <div style="padding: 0; background: #e2e8f0;">
                <iframe id="batchDocumentViewerFrame" class="document-viewer-frame" title="Batch document viewer" loading="lazy"></iframe>
            </div>
            <div style="display: flex; justify-content: flex-end; padding: 14px 20px; background: #f8fafc; border-top: 1px solid #cbd5e1;">
                <button type="button" onclick="closeBatchDocumentViewerModal()" style="padding: 10px 18px; border: none; border-radius: 8px; background: #475569; color: white; font-size: 13px; font-weight: 700; cursor: pointer;">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentUploadType = '';
        let currentQuarter = '';
        let currentAction = '';
        let batchDocumentViewerInertElements = [];
        let actionConfirmCallback = null;
        let flashToastsShown = false;
        const projectCode = '{{ $report->project_code }}';
        const baseUrl = '{{ url("/fund-utilization") }}';

        function openLogsModal() {
            const modal = document.getElementById('logsModal');
            modal.style.display = 'block';
        }

        function closeLogsModal() {
            const modal = document.getElementById('logsModal');
            modal.style.display = 'none';
        }

        function showToast(message, type = 'success', duration = 3800) {
            const toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                return;
            }

            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const messageSpan = document.createElement('span');
            messageSpan.textContent = message;

            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.setAttribute('aria-label', 'Dismiss toast');
            closeButton.innerHTML = '&times;';

            closeButton.addEventListener('click', () => {
                toast.style.animation = 'toastExit 180ms ease-out forwards';
                toast.addEventListener('animationend', () => toast.remove());
            });

            toast.appendChild(messageSpan);
            toast.appendChild(closeButton);
            toastContainer.appendChild(toast);

            setTimeout(() => {
                if (!toast.parentElement) return;
                toast.style.animation = 'toastExit 180ms ease-out forwards';
                toast.addEventListener('animationend', () => toast.remove());
            }, duration);
        }

        function openActionConfirmModal(options = {}) {
            const modal = document.getElementById('actionConfirmModal');
            const modalContent = modal ? modal.querySelector('.modal-content') : null;
            const headerElement = document.getElementById('actionConfirmHeader');
            const bodyElement = document.getElementById('actionConfirmBody');
            const titleElement = document.getElementById('actionConfirmTitle');
            const messageElement = document.getElementById('actionConfirmMessage');
            const confirmButton = document.getElementById('actionConfirmBtn');
            const iconElement = document.getElementById('actionConfirmIcon');
            const closeButton = document.getElementById('actionConfirmCloseBtn');

            if (!modal || !modalContent || !headerElement || !bodyElement || !titleElement || !messageElement || !confirmButton || !iconElement || !closeButton) {
                return;
            }

            titleElement.textContent = options.title || 'Confirm action';
            if (options.messageHtml) {
                messageElement.innerHTML = options.messageHtml;
            } else {
                messageElement.textContent = options.message || 'Are you sure you want to continue?';
            }
            confirmButton.textContent = options.confirmLabel || 'Confirm';
            confirmButton.style.background = options.confirmBackground || options.confirmColor || '#002c76';
            confirmButton.style.color = options.confirmTextColor || '#ffffff';
            modalContent.style.maxWidth = options.maxWidth || '520px';
            headerElement.style.background = options.headerBackground || '#ffffff';
            headerElement.style.borderBottomColor = options.headerBorderColor || '#f1f5f9';
            bodyElement.style.background = options.bodyBackground || '#ffffff';
            titleElement.style.color = options.titleColor || '#0f172a';
            closeButton.style.color = options.closeColor || '#64748b';
            iconElement.style.background = options.iconBackground || 'rgba(0, 44, 118, 0.1)';
            iconElement.style.color = options.iconColor || '#002c76';
            iconElement.innerHTML = options.iconHtml || '<i class="fas fa-circle-question"></i>';
            actionConfirmCallback = typeof options.onConfirm === 'function' ? options.onConfirm : null;

            modal.style.display = 'block';
        }

        function closeActionConfirmModal() {
            const modal = document.getElementById('actionConfirmModal');
            const modalContent = modal ? modal.querySelector('.modal-content') : null;
            const headerElement = document.getElementById('actionConfirmHeader');
            const bodyElement = document.getElementById('actionConfirmBody');
            const messageElement = document.getElementById('actionConfirmMessage');
            const confirmButton = document.getElementById('actionConfirmBtn');
            const titleElement = document.getElementById('actionConfirmTitle');
            const iconElement = document.getElementById('actionConfirmIcon');
            const closeButton = document.getElementById('actionConfirmCloseBtn');

            if (modal) {
                modal.style.display = 'none';
            }

            if (modalContent) {
                modalContent.style.maxWidth = '520px';
            }

            if (headerElement) {
                headerElement.style.background = '#ffffff';
                headerElement.style.borderBottomColor = '#f1f5f9';
            }

            if (bodyElement) {
                bodyElement.style.background = '#ffffff';
            }

            if (messageElement) {
                messageElement.textContent = 'Are you sure you want to continue?';
            }

            if (confirmButton) {
                confirmButton.style.background = '#002c76';
                confirmButton.style.color = '#ffffff';
            }

            if (titleElement) {
                titleElement.style.color = '#0f172a';
            }

            if (iconElement) {
                iconElement.style.background = 'rgba(0, 44, 118, 0.1)';
                iconElement.style.color = '#002c76';
                iconElement.innerHTML = '<i class="fas fa-circle-question"></i>';
            }

            if (closeButton) {
                closeButton.style.color = '#64748b';
            }

            actionConfirmCallback = null;
        }

        function handleActionConfirmButtonClick() {
            if (typeof actionConfirmCallback === 'function') {
                actionConfirmCallback();
            }
            closeActionConfirmModal();
        }

        function openRemarksModal(uploadType, quarter, action) {
            currentUploadType = uploadType;
            currentQuarter = quarter;
            currentAction = action;

            const modal = document.getElementById('remarksModal');
            const form = document.getElementById('remarksForm');
            const titleElement = document.getElementById('modalTitle');
            const remarksField = document.getElementById('remarksText');
            const submitBtn = document.getElementById('submitBtn');

            let title = '';
            let actionLabel = '';

            if (action === 'approve') {
                title = 'Approve ' + uploadType.replace('-', ' ');
                actionLabel = 'Approve (Optional remarks)';
                submitBtn.style.backgroundColor = '#10b981';
                remarksField.placeholder = 'Enter optional remarks for approval...';
                remarksField.required = false;
            } else if (action === 'return') {
                title = 'Return ' + uploadType.replace('-', ' ');
                actionLabel = 'Return (Required remarks)';
                submitBtn.style.backgroundColor = '#dc2626';
                remarksField.placeholder = 'Enter reason for return...';
                remarksField.required = true;
            } else if (action === 'remark') {
                title = 'Add Remarks for ' + uploadType.replace('-', ' ');
                actionLabel = 'Add Remarks';
                submitBtn.style.backgroundColor = '#6366f1';
                remarksField.placeholder = 'Enter remarks...';
                remarksField.required = true;
            }

            titleElement.textContent = title;
            remarksField.value = '';

            // Construct the form action URL directly
            form.action = `${baseUrl}/${projectCode}/approve/${uploadType}/${quarter}`;
            form.style.display = 'block';

            // Create hidden input for action
            let actionInput = document.getElementById('actionInput');
            if (!actionInput) {
                actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.id = 'actionInput';
                actionInput.name = 'action';
                form.appendChild(actionInput);
            }
            actionInput.value = action;

            modal.style.display = 'block';
        }

        function closeRemarksModal() {
            const modal = document.getElementById('remarksModal');
            modal.style.display = 'none';
        }

        function getBatchUploadReminderMessageHtml() {
            return `
                <div style="margin: 0 0 14px; color: #475569; font-size: 13px; line-height: 1.6;">
                    Please check if the following are available in the document to be uploaded:
                </div>
                <div style="padding: 16px 18px; border: 1px solid #c9d8ef; border-radius: 12px; background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,0.75);">
                    <div style="color: #002C76; font-size: 13px; font-weight: 700; margin-bottom: 10px; letter-spacing: 0.01em;">Required items</div>
                    <div style="color: #334155; font-size: 13px; line-height: 1.72;">
                        <div style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Fund Utilization Report</div>
                        <div style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">Written Notices</div>
                        <div style="color: #475569; margin-bottom: 4px;">Distribution Recipients:</div>
                        <ul style="margin: 0 0 10px 0; padding-left: 20px; color: #475569; line-height: 1.72; list-style-type: disc;">
                            <li>Secretary of DBM</li>
                            <li>Secretary of DILG</li>
                            <li>Speaker of the House</li>
                            <li>President of the Senate</li>
                            <li>House Committee on Appropriation</li>
                            <li>Senate Committee on Finance</li>
                        </ul>
                        <div style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Full Disclosure Policy (FDP)</div>
                        <div style="font-weight: 700; color: #0f172a;">LGU Website / Social Media</div>
                    </div>
                </div>
            `;
        }

        function openBatchUploadSubmitConfirmation(form, onConfirm) {
            if (!form) {
                return;
            }

            const quarter = String(form.querySelector('input[name="quarter"]')?.value || '').trim() || 'Selected quarter';
            const selectedFiles = Array.from(form.querySelector('input[name="batch_document_file[]"], input[name="batch_document_file"]')?.files || []);
            const fileCount = selectedFiles.length;
            const fileLabel = fileCount === 1 ? '1 document' : `${fileCount} documents`;

            openActionConfirmModal({
                title: 'Confirm Batch Document Submission',
                messageHtml: `
                    <div style="display: grid; gap: 14px;">
                        <div style="color: #475569; font-size: 13px; line-height: 1.7;">
                            You are about to submit <strong style="color: #0f172a;">${fileLabel}</strong> for <strong style="color: #0f172a;">${quarter}</strong>.
                        </div>
                        <div style="padding: 16px 18px; border: 1px solid #c9d8ef; border-radius: 12px; background: linear-gradient(180deg, #f8fbff 0%, #eef4ff 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,0.75); color: #334155; font-size: 13px; line-height: 1.72;">
                            <div style="color: #002C76; font-size: 13px; font-weight: 700; margin-bottom: 10px; letter-spacing: 0.01em;">Required items</div>
                            <div style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Fund Utilization Report</div>
                            <div style="font-weight: 700; color: #0f172a; margin-bottom: 4px;">Written Notices</div>
                            <div style="color: #475569; margin-bottom: 4px;">Distribution Recipients:</div>
                            <ul style="margin: 0 0 10px 0; padding-left: 20px; color: #475569; line-height: 1.72; list-style-type: disc;">
                                <li>Secretary of DBM</li>
                                <li>Secretary of DILG</li>
                                <li>Speaker of the House</li>
                                <li>President of the Senate</li>
                                <li>House Committee on Appropriation</li>
                                <li>Senate Committee on Finance</li>
                            </ul>
                            <div style="font-weight: 700; color: #0f172a; margin-bottom: 6px;">Full Disclosure Policy (FDP)</div>
                            <div style="font-weight: 700; color: #0f172a;">LGU Website / Social Media</div>
                        </div>
                    </div>
                `,
                confirmLabel: 'Confirm',
                confirmBackground: 'linear-gradient(135deg, #002C76 0%, #003d9e 100%)',
                headerBackground: 'linear-gradient(135deg, #002C76 0%, #003d9e 100%)',
                headerBorderColor: 'rgba(255,255,255,0.12)',
                titleColor: '#ffffff',
                closeColor: 'rgba(255,255,255,0.85)',
                iconBackground: 'rgba(255,255,255,0.15)',
                iconColor: '#ffffff',
                iconHtml: '<i class="fas fa-circle-check"></i>',
                maxWidth: '560px',
                onConfirm: onConfirm,
            });
        }

        function openBatchUploadReminder(onConfirm) {
            openActionConfirmModal({
                title: 'Batch Upload Reminder',
                messageHtml: getBatchUploadReminderMessageHtml(),
                confirmLabel: 'Confirm and Continue',
                confirmBackground: 'linear-gradient(135deg, #002C76 0%, #003d9e 100%)',
                headerBackground: 'linear-gradient(135deg, #002C76 0%, #003d9e 100%)',
                headerBorderColor: 'rgba(255,255,255,0.12)',
                titleColor: '#ffffff',
                closeColor: 'rgba(255,255,255,0.85)',
                iconBackground: 'rgba(255,255,255,0.15)',
                iconColor: '#ffffff',
                iconHtml: '<i class="fas fa-clipboard-check"></i>',
                maxWidth: '640px',
                onConfirm: onConfirm,
            });
        }

        function showFlashToasts() {
            if (flashToastsShown) {
                return;
            }

            flashToastsShown = true;

            const successMessage = @json(session('success'));
            const errorMessage = @json(session('error'));
            const validationErrorMessage = @json($errors->any() ? $errors->first() : null);

            if (successMessage) {
                showToast(successMessage, 'success');
            }

            if (errorMessage) {
                showToast(errorMessage, 'error');
            }

            if (validationErrorMessage && validationErrorMessage !== errorMessage) {
                showToast(validationErrorMessage, 'error');
            }
        }

        function initializeCustomConfirmationBypass() {
            const selectors = [
                '.ops-detail-page button[onclick*="deleteDocument("]',
                '.ops-detail-page a[onclick*="deleteDocument("]',
                '.ops-detail-page button[onclick*="deleteProjectConfirm("]',
                '#actionConfirmBtn',
            ];

            document.querySelectorAll(selectors.join(', ')).forEach((element) => {
                element.dataset.confirmSkip = 'true';
            });
        }

        function setBatchDocumentViewerBackgroundState(isOpen) {
            const page = document.querySelector('.ops-detail-page');
            if (!page) {
                return;
            }

            if (isOpen) {
                batchDocumentViewerInertElements = [];

                Array.from(page.children).forEach((child) => {
                    if (child.id === 'batchDocumentViewerModal') {
                        return;
                    }

                    batchDocumentViewerInertElements.push({
                        element: child,
                        hadInert: child.hasAttribute('inert'),
                        ariaHidden: child.getAttribute('aria-hidden'),
                    });

                    child.setAttribute('inert', '');
                    child.setAttribute('aria-hidden', 'true');
                });

                return;
            }

            batchDocumentViewerInertElements.forEach(({ element, hadInert, ariaHidden }) => {
                if (!element) {
                    return;
                }

                if (hadInert) {
                    element.setAttribute('inert', '');
                } else {
                    element.removeAttribute('inert');
                }

                if (ariaHidden === null) {
                    element.removeAttribute('aria-hidden');
                } else {
                    element.setAttribute('aria-hidden', ariaHidden);
                }
            });

            batchDocumentViewerInertElements = [];
        }

        function openBatchDocumentViewerModal(documentUrl, documentTitle = '') {
            const modal = document.getElementById('batchDocumentViewerModal');
            const frame = document.getElementById('batchDocumentViewerFrame');
            const title = document.getElementById('batchDocumentViewerTitle');
            const subtitle = document.getElementById('batchDocumentViewerSubtitle');

            if (!modal || !frame || !title || !subtitle || !documentUrl) {
                return;
            }

            title.textContent = documentTitle || 'View Document';
            subtitle.textContent = 'Previewing the selected batch document inside the page.';
            frame.src = documentUrl;
            modal.style.display = 'flex';
            modal.style.justifyContent = 'center';
            modal.style.alignItems = 'center';
            document.body.classList.add('no-scroll');
            document.body.classList.add('viewer-modal-open');
            setBatchDocumentViewerBackgroundState(true);
        }

        function closeBatchDocumentViewerModal() {
            const modal = document.getElementById('batchDocumentViewerModal');
            const frame = document.getElementById('batchDocumentViewerFrame');

            if (frame) {
                frame.src = 'about:blank';
            }

            if (modal) {
                modal.style.display = 'none';
            }

            document.body.classList.remove('no-scroll');
            document.body.classList.remove('viewer-modal-open');
            setBatchDocumentViewerBackgroundState(false);
        }

        window.onclick = function(event) {
            const modal = document.getElementById('remarksModal');
            const logsModal = document.getElementById('logsModal');
            const actionConfirmModal = document.getElementById('actionConfirmModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
            if (event.target === logsModal) {
                logsModal.style.display = 'none';
            }
            if (event.target === actionConfirmModal) {
                closeActionConfirmModal();
            }
        }

        document.getElementById('remarksForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (this.dataset.submitting === '1') {
                return;
            }

            this.dataset.submitting = '1';

            const submitBtn = document.getElementById('submitBtn');
            if (submitBtn) {
                submitBtn.disabled = true;
            }

            const formData = new FormData(this);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            })
            .then(async (response) => {
                if (!response.ok) {
                    let errorMessage = 'Failed to submit approval action.';

                    try {
                        const payload = await response.json();
                        if (payload && typeof payload.message === 'string' && payload.message.trim() !== '') {
                            errorMessage = payload.message;
                        } else if (payload && typeof payload.error === 'string' && payload.error.trim() !== '') {
                            errorMessage = payload.error;
                        }
                    } catch (error) {
                        try {
                            const text = await response.text();
                            if (text.trim() !== '') {
                                errorMessage = text;
                            }
                        } catch (readError) {
                            // Keep the default error message.
                        }
                    }

                    throw new Error(errorMessage);
                }

                window.location.reload();
            })
            .catch((error) => {
                console.error('Error:', error);
                showToast(error.message || 'Error submitting approval action. Please try again.', 'error');
            })
            .finally(() => {
                this.dataset.submitting = '0';
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
        });

        // Toggle Accordion Function
        function toggleAccordion(elementId) {
            const element = document.getElementById(elementId);
            const icon = document.getElementById('icon-' + elementId);
            if (!element) return;

            const isOpen = !(element.style.display === 'none' || element.style.display === '');

            // Collapse other quarter panels when opening one
            if (!isOpen && elementId.startsWith('quarter-')) {
                document.querySelectorAll('[id^="quarter-"]').forEach(function (otherPanel) {
                    if (otherPanel === element) return;
                    if (otherPanel.style.display === 'block') {
                        otherPanel.style.display = 'none';
                        const otherId = otherPanel.getAttribute('id');
                        const otherIcon = document.getElementById('icon-' + otherId);
                        if (otherIcon) otherIcon.style.transform = 'rotate(0deg)';
                    }
                    });
                }

                if (
                    elementId.startsWith('individual-documents-')
                    || elementId.startsWith('batch-documents-')
                ) {
                    const quarter = elementId.replace('individual-documents-', '').replace('batch-documents-', '');
                    const siblingId = elementId.startsWith('individual-documents-')
                        ? `batch-documents-${quarter}`
                        : `individual-documents-${quarter}`;
                    const siblingElement = document.getElementById(siblingId);
                    const siblingIcon = document.getElementById('icon-' + siblingId);

                    if (siblingElement && siblingElement.style.display === 'block') {
                        siblingElement.style.display = 'none';
                        if (siblingIcon) siblingIcon.style.transform = 'rotate(0deg)';
                    }
                }

                if (!isOpen) {
                    element.style.display = 'block';
                    if (icon) icon.style.transform = 'rotate(180deg)';
                } else {
                element.style.display = 'none';
                if (icon) icon.style.transform = 'rotate(0deg)';
            }
        }

        // Save Quarter Function
        function saveQuarter(quarter) {
            // Collect all unsaved changes from the quarter
            const movForm = document.querySelector(`form[action*="upload-mov"]`);
            const writtenNoticeForm = document.querySelector(`form[action*="upload-written-notice"]`);
            const fdpForm = document.querySelector(`form[action*="upload-fdp"]`);
            const remarksTextareas = document.querySelectorAll(`textarea[id$="-remarks-${quarter}"]`);

            let hasChanges = false;
            const formsToSubmit = [];

            // Check for file inputs with selected files
            if (movForm && movForm.querySelector('input[name="mov_file"]').files.length > 0) {
                formsToSubmit.push(movForm);
                hasChanges = true;
            }
            if (writtenNoticeForm && writtenNoticeForm.querySelector('input[name="written_notice_file"]').files.length > 0) {
                formsToSubmit.push(writtenNoticeForm);
                hasChanges = true;
            }
            if (fdpForm && fdpForm.querySelector('input[name="fdp_file"]').files.length > 0) {
                formsToSubmit.push(fdpForm);
                hasChanges = true;
            }

            // Check for remarks in accordions
            remarksTextareas.forEach(textarea => {
                if (textarea.value.trim()) {
                    hasChanges = true;
                }
            });

            if (!hasChanges) {
                showToast('No changes to save for this quarter.', 'info');
                return;
            }

            // Submit all forms with changes
            formsToSubmit.forEach(form => {
                form.submit();
            });

            // Show success message
            showToast(`Quarter ${quarter} saved successfully!`, 'success');
        }

        // Save Remarks via AJAX
        function saveRemarksAjax(uploadType, quarter) {
            const textarea = document.getElementById(`textarea-${uploadType}-remarks-${quarter}`)
                || document.getElementById(`textarea-${uploadType}-notes-${quarter}`);
            
            if (!textarea) {
                showToast('Error: Could not find remarks field.', 'error');
                return;
            }

            const remarks = textarea.value;
            const url = `${baseUrl}/${projectCode}/save-remarks/${uploadType}/${quarter}`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    remarks: remarks
                })
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Failed to save remarks');
            })
            .then(data => {
                showToast('Remarks saved successfully.', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error saving remarks. Please try again.', 'error');
            });
        }

        // Delete Document
        function deleteDocument(docType, quarter, confirmed = false) {
            if (!confirmed) {
                openActionConfirmModal({
                    title: 'Delete document',
                    message: 'This document will be permanently removed. Do you want to continue?',
                    confirmLabel: 'Delete',
                    confirmColor: '#dc2626',
                    iconBackground: 'rgba(220, 38, 38, 0.12)',
                    iconColor: '#dc2626',
                    iconHtml: '<i class="fas fa-trash-alt"></i>',
                    onConfirm: function() {
                        deleteDocument(docType, quarter, true);
                    }
                });
                return;
            }

            const url = `${baseUrl}/${projectCode}/delete-document/${docType}/${quarter}`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    showToast(data.message, 'success');
                    if (data.message.toLowerCase().includes('deleted successfully')) {
                        setTimeout(() => window.location.reload(), 800);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error deleting document. Please try again.', 'error');
            });
        }

        function deleteProjectConfirm(projectCode) {
            const message = `Are you sure you want to delete this project and ALL its associated data and logs?\n\nProject Code: ${projectCode}\n\nThis action CANNOT be undone.`;
            openActionConfirmModal({
                title: 'Delete project',
                message: message,
                confirmLabel: 'Delete project',
                confirmColor: '#dc2626',
                iconBackground: 'rgba(220, 38, 38, 0.12)',
                iconColor: '#dc2626',
                iconHtml: '<i class="fas fa-trash-alt"></i>',
                onConfirm: function() {
                    const url = `${baseUrl}/${projectCode}`;

                    fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.message) {
                            showToast(data.message, 'success');
                            if (data.message.toLowerCase().includes('deleted successfully')) {
                                setTimeout(() => {
                                    window.location.href = '/fund-utilization';
                                }, 800);
                            }
                        } else if (data.error) {
                            showToast('Error: ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Error deleting project. Please try again.', 'error');
                    });
                }
            });
        }

        function initializeBatchUploadConfirmation() {
            document.querySelectorAll('form[data-batch-upload-form="1"]').forEach(form => {
                if (form.dataset.batchUploadSubmitBound !== '1') {
                    form.dataset.batchUploadSubmitBound = '1';
                    form.addEventListener('submit', function(event) {
                        if (form.dataset.batchUploadConfirmed === '1') {
                            delete form.dataset.batchUploadConfirmed;
                            return;
                        }

                        event.preventDefault();
                        openBatchUploadSubmitConfirmation(form, function() {
                            form.dataset.batchUploadConfirmed = '1';
                            if (typeof form.requestSubmit === 'function') {
                                form.requestSubmit();
                            } else {
                                form.submit();
                            }
                        });
                    });
                }

                const batchInput = form.querySelector('input[name="batch_document_file[]"], input[name="batch_document_file"]');
                if (!batchInput || batchInput.disabled || batchInput.dataset.batchUploadInputBound === '1') {
                    return;
                }

                batchInput.dataset.batchUploadInputBound = '1';
                batchInput.addEventListener('click', function(event) {
                    if (batchInput.dataset.batchUploadPickerConfirmed === '1') {
                        delete batchInput.dataset.batchUploadPickerConfirmed;
                        return;
                    }

                    event.preventDefault();
                    openBatchUploadReminder(function() {
                        batchInput.dataset.batchUploadPickerConfirmed = '1';
                        batchInput.click();
                    });
                });
            });
        }

        function initializeUploadStyling() {
            const fileInputs = document.querySelectorAll('.ops-detail-page input[type="file"]');

            fileInputs.forEach(input => {
                if (input.dataset.uploadStyled === '1') {
                    return;
                }

                input.dataset.uploadStyled = '1';
                input.classList.add('ops-upload-input');

                if (input.disabled) {
                    input.classList.add('is-disabled');
                }

                ['dragenter', 'dragover'].forEach(evt => {
                    input.addEventListener(evt, function(e) {
                        e.preventDefault();
                        if (!input.disabled) {
                            input.classList.add('drag-active');
                        }
                    });
                });

                ['dragleave', 'drop', 'dragend'].forEach(evt => {
                    input.addEventListener(evt, function() {
                        input.classList.remove('drag-active');
                    });
                });

                input.addEventListener('change', function() {
                    input.classList.remove('drag-active');
                    if (input.files && input.files.length > 0) {
                        input.classList.add('has-selection');
                    } else {
                        input.classList.remove('has-selection');
                    }
                });

                const submitBtn = input.parentElement ? input.parentElement.querySelector('button[type="submit"]') : null;
                if (submitBtn) {
                    submitBtn.classList.add('ops-upload-submit');
                }

                const onchangeValue = input.getAttribute('onchange') || '';
                const filenameMatch = onchangeValue.match(/'([^']*filename-[^']*)'/);
                if (filenameMatch && filenameMatch[1]) {
                    const filenameDiv = document.getElementById(filenameMatch[1]);
                    if (filenameDiv) {
                        filenameDiv.classList.add('ops-upload-filename');
                        if (filenameDiv.textContent && filenameDiv.textContent.trim().length > 0) {
                            filenameDiv.classList.add('has-file');
                        }
                    }
                }
            });

            document.querySelectorAll('.ops-detail-page button[id$="-save-btn"]').forEach(btn => {
                btn.classList.add('ops-upload-submit');
            });
        }

        document.addEventListener('DOMContentLoaded', initializeUploadStyling);
        document.addEventListener('DOMContentLoaded', initializeBatchUploadConfirmation);
        document.addEventListener('DOMContentLoaded', initializeCustomConfirmationBypass);
        document.addEventListener('DOMContentLoaded', showFlashToasts);
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            initializeUploadStyling();
            initializeBatchUploadConfirmation();
            initializeCustomConfirmationBypass();
            showFlashToasts();
        }

        // Show save button and filename when file is selected
        function renderSelectedFileName(filenameDiv, fileName) {
            const icon = document.createElement('i');
            icon.className = 'fas fa-file';
            icon.style.marginRight = '4px';
            filenameDiv.replaceChildren(icon, document.createTextNode(`Selected: ${fileName}`));
        }

        function showSaveButton(fileInput, buttonId, filenameId) {
            const saveBtn = document.getElementById(buttonId);
            const filenameDiv = document.getElementById(filenameId);

            if (!saveBtn || !filenameDiv) {
                return;
            }

            saveBtn.classList.add('ops-upload-submit');
            filenameDiv.classList.add('ops-upload-filename');
            
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                const fileNames = Array.from(fileInput.files).map((file) => file.name);
                const fileName = fileNames.length === 1
                    ? fileNames[0]
                    : `${fileNames.length} files selected: ${fileNames.slice(0, 3).join(', ')}${fileNames.length > 3 ? ` + ${fileNames.length - 3} more` : ''}`;
                // Show the save button
                saveBtn.style.opacity = '1';
                saveBtn.style.pointerEvents = 'auto';
                // Display filename
                renderSelectedFileName(filenameDiv, fileName);
                filenameDiv.style.display = 'block';
                filenameDiv.classList.add('has-file');
                saveBtn.onclick = null;
            } else {
                // Hide the save button if no new file selected
                saveBtn.style.opacity = '0';
                saveBtn.style.pointerEvents = 'none';
                saveBtn.onclick = null;
                // Keep filename div visible if there's already uploaded content (from Blade)
                // Only hide if it's empty
                if (!filenameDiv.textContent.trim()) {
                    filenameDiv.style.display = 'none';
                    filenameDiv.classList.remove('has-file');
                }
            }
        }

        function showSaveButtonForText(input, buttonId) {
            const saveBtn = document.getElementById(buttonId);
            if (!saveBtn) {
                return;
            }

            const hasValue = input && input.value && input.value.trim().length > 0;
            saveBtn.style.opacity = hasValue ? '1' : '0';
            saveBtn.style.pointerEvents = hasValue ? 'auto' : 'none';
        }
    </script>

    <!-- Floating Activity Logs Button -->
    <button onclick="openLogsModal()" id="activityLogsFab" style="position: fixed; bottom: 24px; right: 24px; display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; background-color: #002C76; color: white; border: none; border-radius: 999px; cursor: pointer; font-size: 13px; font-weight: 600; box-shadow: 0 8px 20px rgba(0, 44, 118, 0.35); z-index: 1200; transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease; white-space: nowrap;" onmouseover="this.style.backgroundColor='#003d9e'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 24px rgba(0, 44, 118, 0.4)';" onmouseout="this.style.backgroundColor='#002C76'; this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 20px rgba(0, 44, 118, 0.35)';">
        <i class="fas fa-clipboard-list" style="font-size: 14px;"></i>
        <span>Activity Logs</span>
    </button>

    <style>
        @media (max-width: 640px) {
            #activityLogsFab span { display: none; }
            #activityLogsFab { padding: 14px; border-radius: 50%; }
        }
    </style>


    </div>
@endsection
