<?php

namespace App\Policies;

use App\Models\FundUtilizationApprovalWorkflow;
use App\Models\User;
use App\Services\FundUtilizationWorkflowService;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * FundUtilizationWorkflowPolicy
 * 
 * Authorizes workflow actions (validate, approve, return) based on user role,
 * assignment, and workflow state. Ensures proper separation of concerns and
 * prevents unauthorized actions.
 */
class FundUtilizationWorkflowPolicy
{
    use HandlesAuthorization;

    /**
     * Check if user can validate a workflow submission.
     * Only the currently assigned validator can validate, and they cannot
     * validate their own submission.
     */
    public function validate(User $user, FundUtilizationApprovalWorkflow $workflow): bool
    {
        return app(FundUtilizationWorkflowService::class)->canActorValidate($workflow, $user);
    }

    /**
     * Check if user can approve a workflow submission.
     * Same as validate - both approval and return require validation authority.
     */
    public function approve(User $user, FundUtilizationApprovalWorkflow $workflow): bool
    {
        return $this->validate($user, $workflow);
    }

    /**
     * Check if user can return a workflow submission for revision.
     * Regional Officer cannot directly return to LGU - only to Provincial Officer.
     * Provincial Officer can always return.
     */
    public function returnForRevision(User $user, FundUtilizationApprovalWorkflow $workflow): bool
    {
        if (!$this->validate($user, $workflow)) {
            return false;
        }

        $currentLevel = (int) ($workflow->current_approval_level ?? 0);
        $isRegionalOfficer = $user->normalizedRole() === User::ROLE_REGIONAL 
            || $user->isRegionalOfficeAssignment();
        $uploaderRole = $workflow->uploader_role;

        // Regional Officer cannot directly return LGU submissions - return to PO instead (allowed)
        if ($isRegionalOfficer && $currentLevel === 2 && $uploaderRole === User::ROLE_LGU) {
            return true; // Will return to PO, handled in service
        }

        // Provincial Officer and others can return
        return true;
    }

    /**
     * Check if user can formally return a submission to LGU after Regional Officer review.
     * Only Provincial Officer can do this, and only when in appropriate state.
     */
    public function returnToLguAfterRegionalReview(User $user, FundUtilizationApprovalWorkflow $workflow): bool
    {
        // Only Provincial Officers can formally return to LGU
        if ($user->normalizedRole() !== User::ROLE_PROVINCIAL) {
            return false;
        }

        // Submission must be returned by Regional Officer
        return $workflow->status === 'Returned by Regional Officer';
    }

    /**
     * Check if user can forward a submission to next approval level.
     * Currently equivalent to approve, but separated for future extensibility.
     */
    public function forward(User $user, FundUtilizationApprovalWorkflow $workflow): bool
    {
        return $this->approve($user, $workflow);
    }

    /**
     * Check if user can view a workflow submission.
     * Uploader, assigned validator, or same-region regional officer can view.
     */
    public function view(User $user, FundUtilizationApprovalWorkflow $workflow): bool
    {
        if ((int) $workflow->uploader_id === (int) $user->getKey()) {
            return true;
        }

        if ((int) ($workflow->current_approver_id ?? 0) === (int) $user->getKey()) {
            return true;
        }

        return $this->isRegionalAuthorityCheck($user, $workflow);
    }

    /**
     * Check regional authority for the workflow.
     * Regional users can only see workflows for submissions from their region.
     */
    protected function isRegionalAuthorityCheck(User $user, FundUtilizationApprovalWorkflow $workflow): bool
    {
        $currentLevel = (int) ($workflow->current_approval_level ?? 0);
        
        // Only relevant for regional approval levels
        if ($currentLevel < 2) {
            return false;
        }

        // User must be a regional officer
        if (!($user->isRegionalUser() || $user->isRegionalOfficeAssignment())) {
            return false;
        }

        // Get uploader and match regions
        $uploader = $workflow->uploader;
        if (!$uploader instanceof User) {
            return false;
        }

        $actorRegionComparable = method_exists($user, 'normalizedRegionComparable')
            ? $user->normalizedRegionComparable()
            : $user->normalizedRegion();
        $uploaderRegionComparable = method_exists($uploader, 'normalizedRegionComparable')
            ? $uploader->normalizedRegionComparable()
            : $uploader->normalizedRegion();

        return $actorRegionComparable !== ''
            && $uploaderRegionComparable !== ''
            && $actorRegionComparable === $uploaderRegionComparable;
    }
}
