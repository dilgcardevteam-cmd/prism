<?php

namespace App\Services;

use App\Models\FundUtilizationApprovalWorkflow;
use App\Models\FundUtilizationReport;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class FundUtilizationWorkflowRoutingService
{
    public function getValidatorForLevel(string $projectCode, int $level, User $uploader): ?User
    {
        $report = FundUtilizationReport::query()
            ->where('project_code', $projectCode)
            ->first();

        if (!$report) {
            return null;
        }

        $chainKey = $uploader->isProvincialDilgAssignment() ? 'provincial' : 'lgu';

        $levelConfig = collect(config('fund_utilization_workflow.uploader_chains.' . $chainKey, []))
            ->firstWhere('level', $level);

        if (!$levelConfig) {
            return null;
        }

        return $this->resolveApprover($levelConfig, $report, $uploader);
    }

    public function userCanActOnWorkflowLevel(FundUtilizationApprovalWorkflow $workflow, User $actor): bool
    {
        $currentLevel = (int) ($workflow->current_approval_level ?? 0);
        if ($currentLevel < 1) {
            return false;
        }

        $chainKey = $workflow->uploader_role === User::ROLE_PROVINCIAL ? 'provincial' : 'lgu';
        $levelConfig = collect(config('fund_utilization_workflow.uploader_chains.' . $chainKey, []))
            ->firstWhere('level', $currentLevel);

        if (!$levelConfig) {
            return false;
        }

        $requiredRole = (string) ($levelConfig['role'] ?? '');

        if ($requiredRole === User::ROLE_PROVINCIAL) {
            return $actor->isActive()
                && $actor->isDilgUser()
                && $actor->isProvincialDilgAssignment();
        }

        if ($requiredRole === User::ROLE_REGIONAL) {
            return $actor->isActive()
                && $actor->isDilgUser()
                && ($actor->matchesRoleAlias('regional') || $actor->isRegionalOfficeAssignment());
        }

        return false;
    }

    public function resolveApprover(array $levelConfig, FundUtilizationReport $report, User $uploader): User
    {
        $candidates = $this->candidatesForLevel($levelConfig, $report, $uploader);

        $assignee = $candidates
            ->reject(fn (User $candidate) => (int) $candidate->getKey() === (int) $uploader->getKey())
            ->sortBy(fn (User $candidate) => (int) $candidate->getKey())
            ->first();

        if (!$assignee) {
            throw new RuntimeException(sprintf(
                'No active %s is configured for %s yet.',
                Str::lower((string) ($levelConfig['name'] ?? 'approver')),
                $report->province ?: 'the selected scope'
            ));
        }

        return $assignee;
    }

    public function candidatesForLevel(array $levelConfig, FundUtilizationReport $report, User $uploader): Collection
    {
        $scope = (string) ($levelConfig['scope'] ?? '');
        $role = (string) ($levelConfig['role'] ?? '');

        $query = User::query()
            ->where('status', 'active');

        if ($role === User::ROLE_PROVINCIAL) {
            $query->where(function ($builder) {
                $builder->whereRaw('LOWER(TRIM(COALESCE(role, ""))) = ?', [User::ROLE_PROVINCIAL])
                    ->orWhere(function ($fallback) {
                        $fallback->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['DILG'])
                            ->whereRaw('LOWER(TRIM(COALESCE(role, ""))) NOT IN (?, ?, ?)', [
                                User::ROLE_REGIONAL,
                                'lgu',
                                'mlgoo',
                            ])
                            ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) <> ?', ['regional office'])
                            ->whereRaw('LOWER(TRIM(COALESCE(office, ""))) NOT LIKE ?', ['%regional office%'])
                            ->whereRaw('TRIM(COALESCE(province, "")) <> ""');
                    });
            });

            $province = trim((string) $report->province);
            if ($province !== '') {
                $query->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [Str::lower($province)]);
            }
        } elseif ($role === User::ROLE_REGIONAL) {
            $query->where(function ($builder) {
                $builder->whereRaw('LOWER(TRIM(COALESCE(role, ""))) = ?', [User::ROLE_REGIONAL])
                    ->orWhere(function ($fallback) {
                        $fallback->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['DILG'])
                            ->where(function ($regional) {
                                $regional->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', ['regional office'])
                                    ->orWhereRaw('LOWER(TRIM(COALESCE(office, ""))) LIKE ?', ['%regional office%']);
                            });
                    });
            });

            if ($scope === 'region') {
                $uploaderRegion = method_exists($uploader, 'normalizedRegionComparable')
                    ? $uploader->normalizedRegionComparable()
                    : Str::lower(trim((string) $uploader->region));

                if ($uploaderRegion !== '') {
                    $query->whereRaw('LOWER(TRIM(COALESCE(region, ""))) = ?', [$uploaderRegion]);
                }
            }
        }

        return $query->get();
    }
}
