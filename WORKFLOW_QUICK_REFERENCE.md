# Fund Utilization Workflow - Quick Reference Card

## Workflow Statuses
```
LGU UPLOADER FLOW:
  Submit → Pending Level 1 Validation
         ↓
       [PO Decision]
         ├→ Approve: Pending Level 2 Validation
         └→ Return:  Returned by Provincial Officer → Resubmit
         ↓
       [RO Decision]
         ├→ Approve: Approved ✓
         └→ Return:  Returned by Regional Officer
                     ↓
                     [Goes to PO, NOT LGU]
                     [PO reviews RO remarks]
                     ├→ Return to LGU: Returned by Provincial Officer
                     │                 ↓ Resubmit to Level 1
                     └→ Approve: Pending Level 2 Validation ↻

PO UPLOADER FLOW:
  Submit → Pending Level 2 Validation (SKIPS LEVEL 1)
         ↓
       [RO Decision]
         ├→ Approve: Approved ✓
         └→ Return:  Returned by Regional Officer → Resubmit at Level 2
```

## Key Business Rules

| Rule | Details |
|------|---------|
| **LGU Submission Entry** | Always starts at Level 1 (Provincial Officer) |
| **PO Submission Entry** | Skips Level 1, goes directly to Level 2 (Regional Officer) |
| **RO Return to LGU** | NOT direct to LGU - routes to PO first |
| **PO Return to LGU** | Direct return to LGU |
| **Resubmission** | From returned-from level, revision number increments |
| **Self-Validation** | Not allowed - cannot approve own submission |
| **Regional Scope** | Regional Officer can only approve submissions from their region |
| **Final Approval** | At Level 2 - marks workflow as Approved and complete |

## API Usage Quick Start

### Submit Document
```php
$workflow = $workflowService->submitOrResubmit(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $document,
    uploader: auth()->user()
);
```

### Approve Document
```php
$workflow = $workflowService->approve(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $document,
    actor: auth()->user()
);
```

### Return for Revision
```php
$workflow = $workflowService->returnForRevision(
    report: $report,
    quarter: 'Q1',
    documentType: 'mov',
    record: $document,
    actor: auth()->user(),
    remarks: "Please provide detailed breakdown of costs."
);
```

### Check Status
```php
if ($workflow->isFullyApproved()) { /* approved */ }
if ($workflow->isPendingApproval()) { /* waiting */ }
if ($workflow->isReturned()) { /* returned */ }
if ($workflow->isPendingProvinceReturnToLgu()) { /* PO needs to return to LGU */ }
```

## Authorization Checks

```php
// Check if user can validate
if (!Gate::forUser($user)->allows('fund-utilization.validateWorkflow', $workflow)) {
    abort(403);
}

// Service-level checks
if (!$workflowService->canActorValidate($workflow, $user)) { /* fail */ }
if (!$workflowService->canActorReturn($workflow, $user)) { /* fail */ }
```

## Audit Trail Query

```php
// Get all actions for submission
$logs = $workflow->logs()->orderBy('created_at')->get();

foreach ($logs as $log) {
    // $log->action: Submitted, Resubmitted, Approved, Returned, etc.
    // $log->approval_level: 1, 2, or null
    // $log->approver_id: Who took action
    // $log->revision_number: Document revision at time of action
    // $log->remarks: Return comments or approval remarks
    // $log->previous_status / $log->new_status: State change
}
```

## Error Messages

| Error | Cause | Fix |
|-------|-------|-----|
| "You cannot validate your own submission" | Self-validation | Use different approver |
| "You are not allowed to validate this submission" | Not assigned or wrong region | Ensure proper assignment |
| "This submission is already fully approved" | Trying to approve completed | No action needed |
| "Remarks are mandatory when returning" | Return without remarks | Provide detailed remarks |
| Regional mismatch | RO from wrong region | Assign to correct RO |

## Document Types Supported
- `mov` - MOV Upload
- `batch-document` - Batch Document
- `fdp` - FDP Document
- `written-notice-dbm` - DBM Written Notice
- `written-notice-dilg` - DILG Written Notice
- `written-notice-speaker` - Speaker Written Notice
- `written-notice-president` - President Written Notice
- `written-notice-house` - House Written Notice
- `written-notice-senate` - Senate Written Notice
- `posting-link` - Posting Link

## Approval Levels
- **Level 1**: Provincial Officer (LGU submissions only)
- **Level 2**: Regional Officer (all submissions eventually)

## Roles
- **LGU**: Local Government Unit user - submits documents
- **Provincial**: Provincial Officer - Level 1 validation
- **Regional**: Regional Officer - Level 2 validation

## Database Tables

| Table | Purpose |
|-------|---------|
| `fund_utilization_approval_workflows` | Current workflow state, revision number |
| `approval_logs` | Complete audit trail with revision numbers |
| Document tables (mov_uploads, etc.) | Document status, approver IDs, timestamps |

## Notification Events
- Submission: Notifies assigned validator
- Forwarded: Notifies next-level validator
- Fully Approved: Notifies uploader
- Returned: Notifies return recipient (LGU, PO, or PO for formal return)

## Testing Commands

```bash
# Run all workflow tests
php artisan test tests/Feature/FundUtilizationWorkflowTest.php

# Run specific test
php artisan test tests/Feature/FundUtilizationWorkflowTest.php::test_regional_officer_return_lgu_goes_to_provincial_officer_not_lgu

# Run with coverage
php artisan test --coverage tests/Feature/FundUtilizationWorkflowTest.php
```

## Configuration File
Location: `config/fund_utilization_workflow.php`

Defines:
- Workflow chains for each uploader role
- Approval levels for each chain
- Pending/returned status messages
- Scope (province, region, national)

## Files Modified/Created
- ✅ `app/Services/FundUtilizationWorkflowService.php` - Core service
- ✅ `app/Policies/FundUtilizationWorkflowPolicy.php` - Authorization
- ✅ `app/Models/FundUtilizationApprovalWorkflow.php` - Enhanced model
- ✅ `app/Models/ApprovalLog.php` - Updated model
- ✅ `app/Http/Requests/FundUtilizationApprovalActionRequest.php` - Form validation
- ✅ `database/migrations/2026_07_01_120001_*.php` - Add revision_number
- ✅ `tests/Feature/FundUtilizationWorkflowTest.php` - Integration tests
- ✅ `WORKFLOW_IMPLEMENTATION.md` - Full documentation
- ✅ `WORKFLOW_USAGE_GUIDE.md` - Usage examples
- ✅ `IMPLEMENTATION_CHECKLIST.md` - Project checklist

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Column not found: revision_number | Run `php artisan migrate` |
| Authorization denied | Check user role and assignment |
| Workflow not found | Ensure document was submitted first |
| Revision not incrementing | Verify resubmission goes to correct level |
| Notification not sent | Check queue is running: `php artisan queue:work` |
| Region mismatch | Normalize region comparison in User model |

## Common Development Tasks

### Add New Document Type
1. Add field mapping in service methods (recordStatusField, etc.)
2. Update config/fund_utilization_workflow.php if needed
3. Create migration to add fields to document table

### Change Approval Chain
1. Edit config/fund_utilization_workflow.php
2. Service automatically routes to new configuration

### Add Custom Return Logic
1. Override returnForRevision() in service subclass
2. Implement custom routing in new method
3. Call super() for base logic

### Add Custom Notifications
1. Override message methods: submissionMessage(), etc.
2. Return custom message content
3. Notification facade sends automatically

## Reference Links
- Service: `app/Services/FundUtilizationWorkflowService.php`
- Policy: `app/Policies/FundUtilizationWorkflowPolicy.php`
- Tests: `tests/Feature/FundUtilizationWorkflowTest.php`
- Docs: `WORKFLOW_IMPLEMENTATION.md`
- Guide: `WORKFLOW_USAGE_GUIDE.md`

---
**Last Updated**: 2024
**Status**: Production Ready ✅
