# Fund Utilization Workflow - Usage Guide

This guide demonstrates how to use the Fund Utilization Workflow Service in the Laravel application.

## Basic Usage Examples

### 1. Submitting a Document for Approval

```php
use App\Models\FundUtilizationReport;
use App\Models\FURMovUpload; // or other document type
use App\Services\FundUtilizationWorkflowService;

// Get the service from container or inject it
$workflowService = app(FundUtilizationWorkflowService::class);

// Get the report and document
$report = FundUtilizationReport::find($projectCode);
$movUpload = FURMovUpload::where('project_code', $projectCode)
    ->where('quarter', 'Q1')
    ->first();

// Get the uploader (usually Auth::user())
$uploader = auth()->user();

// Submit for approval
$workflow = $workflowService->submitOrResubmit(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $movUpload,
    uploader: $uploader
);

// Returns FundUtilizationApprovalWorkflow with relationships loaded
// Workflow now has status: "Pending Level 1 Validation"
// Assigned to Provincial Officer in uploader's province
```

### 2. Provincial Officer Approving Level 1

```php
// Provincial Officer reviews and approves
$actor = auth()->user(); // Provincial Officer

$workflow = $workflowService->approve(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $movUpload,
    actor: $actor
);

// Workflow now has:
// - status: "Pending Level 2 Validation"
// - current_approver_id: Regional Officer's ID
// - Notification sent to Regional Officer
// - $movUpload updated with approval timestamps
```

### 3. Provincial Officer Returning LGU Document

```php
// Provincial Officer reviews and finds issues
$remarks = "Please provide additional documentation for component costs.";

$workflow = $workflowService->returnForRevision(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $movUpload,
    actor: $actor, // Provincial Officer
    remarks: $remarks
);

// Workflow now has:
// - status: "Returned by Provincial Officer"
// - current_approver_id: null
// - current_approval_level: null
// - returned_from_level: 1
// - Notification sent to LGU User with remarks
// - Audit log records the return with revision number
```

### 4. Regional Officer Returning LGU Document (Returns to PO First)

```php
// Regional Officer finds issues with Level 2
$remarks = "Component costs need to be itemized according to DBM guidelines.";

$workflow = $workflowService->returnForRevision(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $movUpload,
    actor: $regionalOfficer,
    remarks: $remarks
);

// KEY BEHAVIOR: Document does NOT return directly to LGU!
// Workflow now has:
// - status: "Returned by Regional Officer"
// - current_approver_id: Provincial Officer's ID (returned to PO)
// - current_approval_level: 1
// - returned_from_level: null
// - Notification sent to Provincial Officer (not LGU)
```

### 5. Provincial Officer Formally Returning to LGU After RO Return

```php
// Provincial Officer reviews the RO's comments and agrees to return to LGU
// PO can optionally add their own notes to accompany RO's remarks

$workflow = $workflowService->returnForRevision(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $movUpload,
    actor: $provincialOfficer,
    remarks: "I concur with the Regional Officer's findings. Please revise accordingly."
);

// Workflow now has:
// - status: "Returned by Provincial Officer"
// - current_approver_id: null
// - current_approval_level: null
// - returned_from_level: 2
// - Notification sent to LGU User with combined PO + RO remarks
// - Audit log shows PO's formal return action
```

### 6. LGU User Resubmitting After Return

```php
// LGU User revises and resubmits
$lguUser = auth()->user();

$workflow = $workflowService->submitOrResubmit(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $movUpload, // Updated with revisions
    uploader: $lguUser
);

// Workflow now has:
// - status: "Pending Level 1 Validation"
// - current_approver_id: Provincial Officer's ID (same region)
// - revision_number: 2 (incremented from 1)
// - Audit log records resubmission with new revision number
// - Workflow restarts at Level 1 for full review
```

### 7. Complete Approval Flow (Both Levels)

```php
// Assuming we're continuing from a resubmission scenario:

// 1. Provincial Officer approves revision
$workflow = $workflowService->approve(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $movUpload,
    actor: $provincialOfficer
);
// Status: "Pending Level 2 Validation"

// 2. Regional Officer approves final
$workflow = $workflowService->approve(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $movUpload,
    actor: $regionalOfficer
);

// Workflow now has:
// - status: "Approved"
// - completed_at: now()
// - current_approver_id: null
// - Notification sent to LGU User: submission fully approved
// - All approval timestamps recorded in $movUpload
```

### 8. Provincial Officer Direct Submission (Skips Level 1)

```php
// Provincial Officer submitting their own document
$provincialOfficer = auth()->user();

$workflow = $workflowService->submitOrResubmit(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $movUpload,
    uploader: $provincialOfficer
);

// Workflow automatically determines level:
// - Uploader is Provincial Officer
// - Workflow starts at Level 2 (skips Level 1)
// - status: "Pending Level 2 Validation"
// - Assigned directly to Regional Officer
```

## Authorization Checks in Controllers

### Using Policy Gates

```php
use Illuminate\Support\Facades\Gate;

public function approveUpload(Request $request, $projectCode, $uploadType, $quarter)
{
    $user = auth()->user();
    $workflow = $this->workflowService->workflowFor($projectCode, $quarter, $uploadType);

    // Check if user can validate
    if (!Gate::forUser($user)->allows('fund-utilization.validateWorkflow', $workflow)) {
        abort(403, 'You are not authorized to validate this submission.');
    }

    // Check if user can return (if action is return)
    if ($request->input('action') === 'return') {
        if (!$this->workflowService->canActorReturn($workflow, $user)) {
            abort(403, 'You cannot return this submission.');
        }
    }

    // Perform action...
}
```

### Direct Service Authorization Checks

```php
// Using service helper methods
if (!$workflowService->canActorValidate($workflow, $user)) {
    abort(403, 'Not authorized to validate');
}

if (!$workflowService->canActorReturn($workflow, $user)) {
    abort(403, 'Not authorized to return');
}
```

## Querying Workflow State

### Get Current Workflow

```php
$workflow = $workflowService->workflowFor(
    projectCode: 'FA-OI-22-14-01-01-000-1',
    quarter: 'Q1',
    documentType: 'mov'
);

if (!$workflow) {
    // No workflow exists for this submission
    return redirect()->back()->with('info', 'Please submit the document first.');
}
```

### Check Workflow Status

```php
// Workflow state checks
if ($workflow->isFullyApproved()) {
    // Show approved badge
}

if ($workflow->isPendingApproval()) {
    // Show pending badge
}

if ($workflow->isReturned()) {
    // Show return details with remarks
}

if ($workflow->isPendingProvinceReturnToLgu()) {
    // PO needs to formally return to LGU after RO return
    // Show action button: "Return to LGU"
}
```

### Get Approval History

```php
// Get all approval logs for this submission
$logs = $workflow->logs()
    ->orderBy('created_at')
    ->get();

foreach ($logs as $log) {
    echo $log->action . " at Level " . $log->approval_level;
    echo " by " . $log->approver?->fullName();
    echo " (Revision " . $log->revision_number . ")";
    if ($log->remarks) {
        echo " - Remarks: " . $log->remarks;
    }
}
```

### Get Current Approver

```php
if ($workflow->current_approver_id) {
    $approver = $workflow->currentApprover;
    echo "Currently assigned to: " . $approver->fullName();
} else {
    echo "No pending approval";
}
```

## Common Business Logic Patterns

### Check if LGU Can Resubmit

```php
$lguUser = auth()->user();

if ($workflow && $workflow->isReturned()) {
    // Can resubmit if:
    // 1. Workflow exists and is returned
    // 2. User is the original uploader
    if ((int)$workflow->uploader_id === (int)$lguUser->getKey()) {
        // Show resubmit button
    }
}
```

### Check if PO Should Formally Return to LGU

```php
$provincialOfficer = auth()->user();

if ($workflow->isPendingProvinceReturnToLgu()) {
    if ($provincialOfficer->normalizedRole() === User::ROLE_PROVINCIAL) {
        // Check if this is PO's region
        if ($this->isProvinceMatch($provincialOfficer, $workflow->uploader)) {
            // Show "Return to LGU" button
            // This will include RO remarks automatically
        }
    }
}
```

### Display Workflow Timeline

```php
$logs = $workflow->logs()->orderBy('created_at')->get();

foreach ($logs as $log) {
    $timeline[] = [
        'date' => $log->created_at->format('Y-m-d H:i:s'),
        'action' => $log->action,
        'actor' => $log->approver?->fullName() ?? $log->uploader?->fullName(),
        'level' => $log->approval_level,
        'status_change' => "{$log->previous_status} → {$log->new_status}",
        'remarks' => $log->remarks,
        'revision' => "Rev {$log->revision_number}",
    ];
}
```

## Error Handling

### Catching Workflow Exceptions

```php
use RuntimeException;

try {
    $workflow = $workflowService->approve(
        report: $report,
        quarter: 'Q1',
        documentType: 'mov',
        record: $movUpload,
        actor: $actor
    );
} catch (RuntimeException $e) {
    switch($e->getMessage()) {
        case 'You cannot validate your own submission.':
            return redirect()->back()->withErrors('Cannot validate your own submission.');
        case 'You are not allowed to validate this submission.':
            return redirect()->back()->withErrors('You lack authorization for this action.');
        case 'This submission is already fully approved.':
            return redirect()->back()->withErrors('This submission is already approved.');
        case 'This submission is already under validation and cannot be resubmitted yet.':
            return redirect()->back()->withErrors('Submission still under validation.');
        default:
            return redirect()->back()->withErrors($e->getMessage());
    }
}
```

### Form Request Validation

```php
use App\Http\Requests\FundUtilizationApprovalActionRequest;

public function approveUpload(
    FundUtilizationApprovalActionRequest $request,
    $projectCode,
    $uploadType,
    $quarter
) {
    // Form request automatically validates:
    // - action: must be 'approve' or 'return'
    // - remarks: required if action is 'return', max 1000 chars

    $validated = $request->validated();
    // $validated['action'] and $validated['remarks'] are safe

    if ($validated['action'] === 'return') {
        // remarks are guaranteed non-empty and valid
    }
}
```

## Best Practices

1. **Always Load Relationships**: Use `fresh(['uploader', 'currentApprover', 'logs'])` after workflow modifications

2. **Database Transactions**: Service already wraps all operations in transactions - don't wrap again

3. **Check Authorization First**: Use Policy gates before calling service methods

4. **Meaningful Remarks**: Require detailed remarks when returning - helps users understand what to fix

5. **Preserve History**: Never modify or delete approval logs - they form the audit trail

6. **Test Revision Numbers**: Verify revision_number increments correctly in both workflow and logs

7. **Validate State**: Check workflow state before showing action buttons

8. **Regional Matching**: Verify region matching for regional user operations

9. **Notification Content**: Customize notification messages based on workflow context

10. **Error Messages**: Show business rule errors from RuntimeException messages to users

