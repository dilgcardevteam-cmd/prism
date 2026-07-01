# Fund Utilization Validation and Return Workflow Implementation

## Overview

This document describes the comprehensive Fund Utilization Document Approval Workflow implementation, including sophisticated multi-level validation, intelligent return routing, and complete audit trails.

## Architecture Overview

### Core Components

1. **FundUtilizationWorkflowService** - Orchestrates all workflow actions
2. **FundUtilizationApprovalWorkflow Model** - Tracks workflow state
3. **ApprovalLog Model** - Maintains complete audit trail with revision tracking
4. **FundUtilizationWorkflowPolicy** - Authorizes workflow actions
5. **Form Requests** - Validates user input

### Key Database Tables

- `fund_utilization_approval_workflows` - Current workflow state
- `approval_logs` - Complete approval history with revision numbers
- Document tables (MOV, batch documents, FDP, etc.) - Store document states

## Workflow Flow Diagrams

### LGU User Submission Flow

```
LGU submits document
    ↓
Status: "Pending Level 1 Validation"
Assigned to: Provincial Officer
    ↓
[Provincial Officer Decision]
    ├─→ Approve → Status: "Pending Level 2 Validation", Assigned to: Regional Officer
    └─→ Return → Status: "Returned by Provincial Officer", Assigned to: LGU User
            ↓
            LGU revises and resubmits
            ↓
            [Restart at Level 1 Validation]
    ↓
[Regional Officer Decision]
    ├─→ Approve → Status: "Approved", Completed, Notify LGU
    └─→ Return → Status: "Returned by Regional Officer", Assigned to: Provincial Officer
            ↓
            [Provincial Officer reviews RO remarks]
            ├─→ Returns to LGU with RO remarks
            └─→ Status: "Returned by Provincial Officer", Assigned to: LGU User
                    ↓
                    LGU revises and resubmits
                    ↓
                    [Restart at Level 1 Validation]
```

### Provincial Officer Submission Flow

```
Provincial Officer submits document
    ↓
Status: "Pending Level 2 Validation"
Assigned to: Regional Officer
    ↓
[Regional Officer Decision]
    ├─→ Approve → Status: "Approved", Completed
    └─→ Return → Status: "Returned by Regional Officer", Assigned to: Provincial Officer
            ↓
            PO revises and resubmits
            ↓
            [Restart at Level 2 Validation - skips Level 1]
```

## Workflow Rules and Business Logic

### Submission Rules

1. **LGU User Submission**
   - Starts at Level 1 Validation (Provincial Officer)
   - Status: "Pending Level 1 Validation"
   - Revision number increments with each resubmission

2. **Provincial Officer Submission**
   - Bypasses Level 1, starts at Level 2 Validation
   - Status: "Pending Level 2 Validation"
   - Assigned directly to Regional Officer

3. **Resubmission After Return**
   - Must be from the user who received the return
   - Restarts from the returned-from level
   - Increments revision number in workflow and audit log
   - Clears previous approval remarks

### Provincial Officer (Level 1) Rules

- **Authority**: Only handles submissions where uploader_role is LGU
- **Can Approve**: Forwards to Regional Officer (Level 2)
- **Can Return**: Directly returns to LGU with mandatory remarks
- **Cannot**:
  - Validate their own submissions
  - Bypass Level 1 to approve at Level 2
  - Return submissions that are already approved

### Regional Officer (Level 2) Rules

- **Authority**: Validates at Level 2 across entire region
- **Can Approve**: Marks submission as "Approved" and completes workflow
- **Can Return**: 
  - If uploader is LGU: Returns to Provincial Officer (NOT directly to LGU)
  - If uploader is Provincial Officer: Returns directly to them
- **Must Not**: Return submissions directly to LGU - always through Provincial Officer intermediary
- **Cannot**:
  - Validate their own submissions
  - Approve at Level 1

### Return Routing Logic

#### Scenario 1: Provincial Officer Returns LGU Submission
```
PO at Level 1 → Returns → LGU (direct)
Status: "Returned by Provincial Officer"
```

#### Scenario 2: Regional Officer Returns LGU Submission
```
RO at Level 2 → Returns → PO at Level 1 (NOT LGU)
Status: "Returned by Regional Officer"
PO then formally returns to LGU with RO remarks
```

#### Scenario 3: Regional Officer Returns PO Submission
```
RO at Level 2 → Returns → PO (direct, at Level 2)
Status: "Returned by Regional Officer"
PO revises and resubmits at Level 2 (skips Level 1)
```

## Authorization and Permissions

### Policy Rules (FundUtilizationWorkflowPolicy)

| Action | PO at L1 | RO at L2 | Uploader | Restrictions |
|--------|----------|---------|----------|--------------|
| validate | ✓ | ✓ | ✗ | Cannot validate own submission |
| approve | ✓ | ✓ | ✗ | Must be assigned approver or regional authority |
| returnForRevision | ✓ | ✓ (→PO) | ✗ | RO must return to PO when uploader is LGU |
| returnToLguAfterRegionalReview | Only PO | ✗ | ✗ | Only when in "Returned by Regional Officer" state |
| view | ✓ | ✓ | ✓ | Uploader or validator can view |

### Regional Authority Matching

- Regional users can only validate submissions from users in their region
- Region comparison uses `normalizedRegionComparable()` method
- Prevents cross-region validation

## Approval Log Tracking

Every workflow action is recorded with:
- `submission_id` - Links to workflow
- `action` - Action type (Submitted, Resubmitted, Approved, Returned, Returned to LGU)
- `approval_level` - Level at which action occurred (1, 2, or null)
- `previous_status` - State before action
- `new_status` - State after action
- `remarks` - Action-specific remarks or return comments
- `revision_number` - Document revision number at time of action
- `approver_id` - Who took the action (null for initial submission)
- `uploader_id` - Original uploader
- `created_at` - When action occurred (immutable)

### Audit Trail Examples

**Complete LGU Submission Lifecycle:**
```
1. Submitted    | Level 1 | null → Pending L1V | User L1 | Rev 1
2. Returned     | Level 1 | Pending L1V → Returned by PO | PO remarks | Rev 1
3. Resubmitted  | Level 1 | null → Pending L1V | User L1 | Rev 2
4. Approved     | Level 1 | Pending L1V → Pending L2V | null | Rev 2
5. Returned     | Level 2 | Pending L2V → Returned by RO | RO remarks | Rev 2
6. Returned to LGU | Level 1 | Returned by RO → Returned by PO | PO + RO remarks | Rev 2
7. Resubmitted  | Level 1 | null → Pending L1V | User L1 | Rev 3
8. Approved     | Level 1 | Pending L1V → Pending L2V | null | Rev 3
9. Approved     | Level 2 | Pending L2V → Approved | null | Rev 3
```

## Service Methods

### submitOrResubmit()

**Purpose**: Initial submission or resubmission after return

**Parameters**:
- `$report` - FundUtilizationReport model
- `$quarter` - Q1, Q2, Q3, Q4
- `$documentType` - Document type being submitted
- `$record` - Document record (MOV, batch doc, etc.)
- `$uploader` - User submitting

**Returns**: FundUtilizationApprovalWorkflow

**Logic**:
1. Retrieve or create workflow record
2. Determine target level (first level for new, returned-from level for resubmission)
3. Route to appropriate validator
4. Increment revision number for resubmission
5. Record audit log
6. Send notification

### approve()

**Purpose**: Approve submission and forward to next level or mark complete

**Parameters**:
- `$report` - FundUtilizationReport model
- `$quarter` - Q1, Q2, Q3, Q4
- `$documentType` - Document type
- `$record` - Document record
- `$actor` - User approving

**Returns**: FundUtilizationApprovalWorkflow

**Logic**:
1. Verify actor can validate
2. Check if next level exists
3. If yes: Forward to next approver, update status to next level's pending status
4. If no: Mark as approved, complete workflow
5. Record audit log with appropriate action and remarks
6. Notify next recipient or uploader

### returnForRevision()

**Purpose**: Return submission for revision with intelligent routing

**Parameters**:
- `$report`, `$quarter`, `$documentType`, `$record`, `$actor` - Standard workflow params
- `$remarks` - Return comments (mandatory)

**Returns**: FundUtilizationApprovalWorkflow

**Logic**:
1. Verify actor can validate and remarks are provided
2. Determine return recipient:
   - RO at L2 returning LGU submission → Return to PO (not LGU)
   - PO at L1 → Return to LGU
   - PO returning own submission → Return to self
3. Update workflow with return status
4. Record audit log with remarks and revision number
5. Notify return recipient

## Document Synchronization

When workflow state changes, document records are updated to reflect approval state:

### After Submission
- Document status: 'pending'
- Cleared approval remarks from previous iteration
- Level 1: Clears PO approval timestamps

### After Approval
- Document status: 'pending' (if next level exists) or 'approved' (if final)
- Sets approver ID and timestamp for current level
- Clears remarks
- Level 1: Sets `approved_at_dilg_po` and `approved_by_dilg_po`
- Level 2: Sets `approved_at_dilg_ro` and `approved_by_dilg_ro`

### After Return
- Document status: 'returned'
- Sets return remarks
- Records approver and timestamp
- Preserves for uploader review

## Notifications

Automatic notifications are sent for:

1. **Submission**: Notifies assigned validator
2. **Forwarded**: Notifies next-level validator
3. **Fully Approved**: Notifies uploader
4. **Returned**: Notifies return recipient (LGU, PO, or PO for formal return)

### Notification Data
- Sender name and ID
- Document type and quarter
- Project code
- Direct link to submission
- Custom message describing action

## Error Handling

### Validation Errors

- `RuntimeException` - Service-level business rule violations
- Form request validation - User input validation
- Policy authorization - Permission checks

### Common Errors

- "This submission is already approved and cannot be resubmitted."
- "You cannot validate your own submission."
- "Remarks are mandatory when returning a submission for revision."
- "You are not allowed to validate this submission."
- "This submission was not returned by the Regional Officer."
- "The original uploader is no longer available."

## Extension Points

The workflow is designed for easy extension:

### Adding New Approval Levels

1. Add level configuration to `fund_utilization_workflow.php` config
2. Update workflow chain for applicable uploader levels
3. Service methods automatically handle additional levels

### Adding New Return Conditions

1. Extend `returnForRevision()` method with new condition checks
2. Update return routing logic in return recipient determination section

### Adding New Document Types

1. Define field mapping methods for new document type in service
2. Add to match expressions in `recordStatusField()`, `recordApprovedByField()`, etc.
3. Workflow automatically routes and approves new document types

### Custom Notification Messages

1. Override message generation methods:
   - `submissionMessage()`
   - `forwardedMessage()`
   - `fullyApprovedMessage()`
   - `returnedMessage()`

## Testing Checklist

- [ ] LGU user submits → Assigned to PO at Level 1
- [ ] PO approves → Status: Pending Level 2, Assigned to RO
- [ ] PO returns → Status: Returned by PO, Assigned to LGU
- [ ] LGU resubmits → Revision number increments, restarts at Level 1
- [ ] RO approves from Level 1 forward → Status: Approved, Completed
- [ ] RO returns LGU submission → Status: Returned by RO, Assigned to PO (not LGU)
- [ ] PO formally returns to LGU after RO return → Includes RO remarks in returned submission
- [ ] PO submits → Skips Level 1, Assigned to RO at Level 2
- [ ] RO returns PO submission → Assigned back to PO at Level 2
- [ ] Cannot validate own submission → Authorization error
- [ ] Revision number tracked in audit log → Each submission increments
- [ ] All actions recorded in approval_logs → Complete audit trail
- [ ] Notifications sent correctly → Each recipient notified appropriately

## Configuration

### fund_utilization_workflow.php

```php
'uploader_chains' => [
    'lgu' => [
        [
            'level' => 1,
            'name' => 'Provincial Officer',
            'role' => 'provincial',
            'scope' => 'province',
            'pending_status' => 'Pending Level 1 Validation',
            'returned_status' => 'Returned by Provincial Officer',
        ],
        [
            'level' => 2,
            'name' => 'Regional Officer',
            'role' => 'regional',
            'scope' => 'region',
            'pending_status' => 'Pending Level 2 Validation',
            'returned_status' => 'Returned by Regional Officer',
        ],
    ],
    'provincial' => [
        [
            'level' => 2,
            'name' => 'Regional Officer',
            'role' => 'regional',
            'scope' => 'region',
            'pending_status' => 'Pending Level 2 Validation',
            'returned_status' => 'Returned by Regional Officer',
        ],
    ],
]
```

## SOLID Principles Implementation

### Single Responsibility
- Service handles workflow orchestration
- Policy handles authorization
- Model handles data and relationships
- Notification handles communication

### Open/Closed
- New approval levels added via config
- New document types added via field mapping methods
- Return routing extensible via service method overrides

### Liskov Substitution
- All validators interchangeable by role/level

### Interface Segregation
- Specific methods for each workflow action
- Policy methods grouped by capability

### Dependency Inversion
- Routing service injected
- Notification facade used
- Config-driven workflow chain

