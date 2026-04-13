<?php

namespace App\Support;

use App\Models\UserRole;
use App\Models\User;

class RolePermissionRegistry
{
    public const CRUD_ACTION_OPTIONS = [
        'view' => 'VIEW',
        'add' => 'ADD',
        'update' => 'UPDATE',
        'delete' => 'DELETE',
    ];

    public static function actionOptions(): array
    {
        return self::CRUD_ACTION_OPTIONS;
    }

    public static function modules(): array
    {
        return [
            [
                'module' => 'Project Monitoring',
                'description' => 'Project monitoring modules for project profiles, updates, and accomplishment tracking.',
                'items' => [
                    [
                        'aspect' => 'locally_funded_projects',
                        'label' => 'Locally Funded Projects',
                        'description' => 'View and manage locally funded project records, including profile details and monitoring updates within the role scope.',
                    ],
                    [
                        'aspect' => 'rlip_lime_projects',
                        'label' => 'RLIP / LIME-20% Development Fund',
                        'description' => 'Open the RLIP / LIME-20% project monitoring pages and review scoped project dashboards and tables.',
                        'actions' => ['view'],
                    ],
                    [
                        'aspect' => 'project_at_risk_projects',
                        'label' => 'Project At Risk',
                        'description' => 'Open the project-at-risk monitoring page and review scoped project risk records and exports.',
                        'actions' => ['view'],
                    ],
                    [
                        'aspect' => 'sglgif_portal',
                        'label' => 'SGLGIF Portal',
                        'description' => 'Open the SGLGIF dashboard and table views for scoped project performance and accomplishment tracking.',
                        'actions' => ['view'],
                    ],
                ],
            ],
            [
                'module' => 'LGU Reportorial Requirements',
                'description' => 'Annual, quarterly, and monthly LGU reportorial submissions handled by the current system.',
                'items' => [
                    [
                        'aspect' => 'rbis_annual_certification',
                        'label' => 'Annual / RBIS Annual Certification',
                        'description' => 'Manage annual RBIS certification documents and related validation actions.',
                    ],
                    [
                        'aspect' => 'annual_rpmes_form_4',
                        'label' => 'Annual / RPMES Form 4 : Project Results',
                        'description' => 'Manage annual RPMES Form 4 project results submissions, uploads, and DILG validation workflow.',
                    ],
                    [
                        'aspect' => 'fund_utilization_reports',
                        'label' => 'Quarterly / Fund Utilization Report',
                        'description' => 'Manage quarterly fund utilization records, MOV uploads, notices, and supporting reportorial documents.',
                    ],
                    [
                        'aspect' => 'local_project_monitoring_committee',
                        'label' => 'Quarterly / Local Project Monitoring Committee',
                        'description' => 'Manage quarterly LPMC submissions, uploaded documents, and validation workflow.',
                    ],
                    [
                        'aspect' => 'road_maintenance_status_reports',
                        'label' => 'Quarterly / Road Maintenance Status Report',
                        'description' => 'Manage quarterly road maintenance status submissions, document uploads, and validation steps.',
                    ],
                    [
                        'aspect' => 'quarterly_rpmes_form_2',
                        'label' => 'Quarterly / RPMES Form 2',
                        'description' => 'Manage quarterly RPMES Form 2 physical and financial accomplishment report submissions and validation workflow.',
                    ],
                    [
                        'aspect' => 'quarterly_rpmes_form_5',
                        'label' => 'Quarterly / RPMES Form 5 : Summary of Financial and Physical Accomplishments including Project Results',
                        'description' => 'Open the quarterly RPMES Form 5 workspace for the summary of financial and physical accomplishments, including project results.',
                        'actions' => ['view'],
                    ],
                    [
                        'aspect' => 'quarterly_rpmes_form_6',
                        'label' => 'Quarterly / RPMES Form 6 : Report on the Status of Projects Encountering Problems',
                        'description' => 'Open the quarterly RPMES Form 6 workspace for reporting the status of projects encountering problems.',
                        'actions' => ['view'],
                    ],
                    [
                        'aspect' => 'pd_no_pbbm_monthly_reports',
                        'label' => 'Monthly / PD No. PBBM-2025-1572-1573',
                        'description' => 'Manage monthly report submissions, uploaded files, and document approval actions.',
                    ],
                    [
                        'aspect' => 'swa_annex_f_monthly_reports',
                        'label' => 'Monthly / SWA- Annex F',
                        'description' => 'Manage monthly SGLGIF SWA- Annex F submissions, uploaded files, and document approval actions.',
                    ],
                ],
            ],
            [
                'module' => 'Pre-Implementation Documents',
                'description' => 'Document requirements collected before implementation begins.',
                'items' => [
                    [
                        'aspect' => 'pre_implementation_documents',
                        'label' => 'SBDP Pre-Implementation Documents',
                        'description' => 'View and add the pre-implementation document set required for SBDP projects before project execution.',
                    ],
                ],
            ],
            [
                'module' => 'Support Services',
                'description' => 'Operational support modules for issue resolution, ticket routing, and user assistance.',
                'items' => [
                    [
                        'aspect' => 'ticketing_system',
                        'label' => 'Ticketing System',
                        'description' => 'Submit, track, review, escalate, forward, and monitor support tickets based on the assigned role scope.',
                        'actions' => ['view', 'add', 'update'],
                    ],
                ],
            ],
            [
                'module' => 'Data Management',
                'description' => 'Upload and manage source datasets used by the project monitoring and reportorial pages.',
                'items' => [
                    [
                        'aspect' => 'subaybayan_data_uploads',
                        'label' => 'Upload LFP Data',
                        'description' => 'View the SubayBAYAN upload manager, import new files, load approved datasets, and remove stale imports.',
                    ],
                    [
                        'aspect' => 'rlip_lime_data_uploads',
                        'label' => 'Upload RLIP / LIME-20 Data',
                        'description' => 'View the RLIP / LIME upload manager, import new files, load refreshed datasets, and delete old imports.',
                    ],
                    [
                        'aspect' => 'project_at_risk_data_uploads',
                        'label' => 'Upload Project-at-Risk Data',
                        'description' => 'View the project-at-risk upload manager, import new files, load refreshed datasets, and delete old imports.',
                    ],
                    [
                        'aspect' => 'sglgif_data_uploads',
                        'label' => 'Upload SGLGIF Data',
                        'description' => 'View the SGLGIF upload manager, import new files, load refreshed datasets, and delete old imports.',
                    ],
                ],
            ],
            [
                'module' => 'Utilities',
                'description' => 'Administrative utility pages for configuration, notifications, deadlines, location references, and database maintenance.',
                'items' => [
                    [
                        'aspect' => 'utilities_system_setup',
                        'label' => 'System Setup',
                        'description' => 'Open the utilities landing page and browse the configuration workspaces available to the assigned role.',
                        'actions' => ['view'],
                    ],
                    [
                        'aspect' => 'utilities_bulk_notifications',
                        'label' => 'Bulk Notification',
                        'description' => 'Open the bulk notification workspace and send announcement emails to the selected audience.',
                        'actions' => ['view', 'add'],
                    ],
                    [
                        'aspect' => 'utilities_deadlines_configuration',
                        'label' => 'Deadlines Configuration',
                        'description' => 'Review reportorial deadline workspaces and save LGU deadline configuration changes.',
                        'actions' => ['view', 'update'],
                    ],
                    [
                        'aspect' => 'utilities_location_configuration',
                        'label' => 'Location Configuration',
                        'description' => 'Review location reference datasets, import refreshed files, load approved records, and delete stale import history.',
                    ],
                    [
                        'aspect' => 'utilities_backup_restore',
                        'label' => 'Backup and Restore',
                        'description' => 'Open the backup workspace, download backups, restore database snapshots, and update automation settings.',
                        'actions' => ['view', 'update'],
                    ],
                ],
            ],
        ];
    }

    public static function actionsForItem(array $item): array
    {
        $defaultActions = array_keys(self::actionOptions());

        $actions = collect($item['actions'] ?? $defaultActions)
            ->map(fn ($action) => strtolower(trim((string) $action)))
            ->filter(fn (string $action) => array_key_exists($action, self::actionOptions()))
            ->unique()
            ->values()
            ->all();

        return $actions === [] ? $defaultActions : $actions;
    }

    public static function actionsForAspect(string $aspect): array
    {
        $normalizedAspect = strtolower(trim($aspect));

        foreach (self::modules() as $module) {
            foreach ($module['items'] ?? [] as $item) {
                if (strtolower(trim((string) ($item['aspect'] ?? ''))) === $normalizedAspect) {
                    return self::actionsForItem($item);
                }
            }
        }

        return array_keys(self::actionOptions());
    }

    public static function validPermissionKeys(): array
    {
        return collect(self::modules())
            ->flatMap(function (array $module) {
                return collect($module['items'] ?? []);
            })
            ->flatMap(function (array $item) {
                $aspect = strtolower(trim((string) ($item['aspect'] ?? '')));

                if ($aspect === '') {
                    return [];
                }

                return collect(self::actionsForItem($item))
                    ->map(fn (string $action) => $aspect . '.' . $action);
            })
            ->unique()
            ->values()
            ->all();
    }

    public static function configurableRoles(): array
    {
        return array_values(array_merge([
            ...array_values(array_filter([
                User::ROLE_REGIONAL,
                User::ROLE_PROVINCIAL,
                User::ROLE_MLGOO,
                User::ROLE_LGU,
            ], fn (string $role): bool => array_key_exists($role, User::activeBuiltInRoleOptions()))),
        ], array_keys(UserRole::roleOptions())));
    }

    public static function roleDescriptions(): array
    {
        return array_merge([
            User::ROLE_SUPERADMIN => 'Highest access. Superadmin keeps full access across all modules and system utilities.',
            User::ROLE_REGIONAL => 'Can oversee projects within the designated region, including its provinces and LGUs.',
            User::ROLE_PROVINCIAL => 'Can oversee projects within the designated province, including LGUs inside that province.',
            User::ROLE_MLGOO => 'Municipal LGU Operations Officer scope. Access is limited to the assigned municipality or city and its submitted records.',
            User::ROLE_LGU => 'Lowest operational scope. Access is limited to the assigned LGU and its submitted records.',
        ], UserRole::descriptions());
    }

    public static function defaultPermissionsByRole(): array
    {
        $projectMonitoringPermissions = [
            'locally_funded_projects.view',
            'rlip_lime_projects.view',
            'project_at_risk_projects.view',
            'sglgif_portal.view',
        ];

        $reportorialPermissions = [
            'fund_utilization_reports.view',
            'fund_utilization_reports.add',
            'fund_utilization_reports.update',
            'fund_utilization_reports.delete',
            'local_project_monitoring_committee.view',
            'local_project_monitoring_committee.add',
            'local_project_monitoring_committee.update',
            'local_project_monitoring_committee.delete',
            'road_maintenance_status_reports.view',
            'road_maintenance_status_reports.add',
            'road_maintenance_status_reports.update',
            'road_maintenance_status_reports.delete',
            'quarterly_rpmes_form_2.view',
            'quarterly_rpmes_form_2.add',
            'quarterly_rpmes_form_2.update',
            'quarterly_rpmes_form_2.delete',
            'quarterly_rpmes_form_5.view',
            'quarterly_rpmes_form_6.view',
            'rbis_annual_certification.view',
            'rbis_annual_certification.add',
            'rbis_annual_certification.update',
            'rbis_annual_certification.delete',
            'annual_rpmes_form_4.view',
            'annual_rpmes_form_4.add',
            'annual_rpmes_form_4.update',
            'annual_rpmes_form_4.delete',
            'pd_no_pbbm_monthly_reports.view',
            'pd_no_pbbm_monthly_reports.add',
            'pd_no_pbbm_monthly_reports.update',
            'pd_no_pbbm_monthly_reports.delete',
            'swa_annex_f_monthly_reports.view',
            'swa_annex_f_monthly_reports.add',
            'swa_annex_f_monthly_reports.update',
            'swa_annex_f_monthly_reports.delete',
        ];

        $dataManagementPermissions = [
            'subaybayan_data_uploads.view',
            'subaybayan_data_uploads.add',
            'subaybayan_data_uploads.update',
            'subaybayan_data_uploads.delete',
            'rlip_lime_data_uploads.view',
            'rlip_lime_data_uploads.add',
            'rlip_lime_data_uploads.update',
            'rlip_lime_data_uploads.delete',
            'project_at_risk_data_uploads.view',
            'project_at_risk_data_uploads.add',
            'project_at_risk_data_uploads.update',
            'project_at_risk_data_uploads.delete',
            'sglgif_data_uploads.view',
            'sglgif_data_uploads.add',
            'sglgif_data_uploads.update',
            'sglgif_data_uploads.delete',
        ];

        $ticketingPermissions = [
            User::ROLE_SUPERADMIN => ['*'],
            User::ROLE_REGIONAL => [
                'ticketing_system.view',
                'ticketing_system.update',
            ],
            User::ROLE_PROVINCIAL => [
                'ticketing_system.view',
                'ticketing_system.update',
            ],
            User::ROLE_LGU => [
                'ticketing_system.view',
                'ticketing_system.add',
            ],
            User::ROLE_MLGOO => [
                'ticketing_system.view',
                'ticketing_system.add',
            ],
        ];

        return [
            User::ROLE_SUPERADMIN => ['*'],
            User::ROLE_REGIONAL => array_merge($reportorialPermissions, $projectMonitoringPermissions, $dataManagementPermissions, $ticketingPermissions[User::ROLE_REGIONAL], [
                'locally_funded_projects.update',
            ]),
            User::ROLE_PROVINCIAL => array_merge($reportorialPermissions, $projectMonitoringPermissions, $ticketingPermissions[User::ROLE_PROVINCIAL], [
                'locally_funded_projects.update',
                'pre_implementation_documents.view',
                'pre_implementation_documents.add',
            ]),
            User::ROLE_MLGOO => array_merge($reportorialPermissions, $projectMonitoringPermissions, $ticketingPermissions[User::ROLE_MLGOO], [
                'pre_implementation_documents.view',
                'pre_implementation_documents.add',
            ]),
            User::ROLE_LGU => array_merge($reportorialPermissions, $projectMonitoringPermissions, $ticketingPermissions[User::ROLE_LGU], [
                'pre_implementation_documents.view',
                'pre_implementation_documents.add',
            ]),
        ];
    }

    public static function permissionsForRole(string $role, ?array $configuredPermissions = null): array
    {
        $normalizedRole = strtolower(trim($role));

        if ($normalizedRole === User::ROLE_SUPERADMIN) {
            return ['*'];
        }

        if (is_array($configuredPermissions)) {
            return self::normalizePermissions($configuredPermissions);
        }

        return self::normalizePermissions(self::defaultPermissionsByRole()[$normalizedRole] ?? []);
    }

    public static function normalizePermissions(array $permissions): array
    {
        return collect($permissions)
            ->map(fn ($permission) => strtolower(trim((string) $permission)))
            ->filter(fn ($permission) => $permission === '*' || in_array($permission, self::validPermissionKeys(), true))
            ->unique()
            ->values()
            ->all();
    }
}
