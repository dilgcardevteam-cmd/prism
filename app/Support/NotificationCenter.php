<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationCenter
{
    /**
     * @param iterable<mixed> $notifications
     * @return Collection<int, array<string, mixed>>
     */
    public static function presentMany(iterable $notifications): Collection
    {
        return collect($notifications)
            ->map(fn ($notification) => self::present($notification))
            ->values();
    }

    /**
     * @param object|array<string, mixed> $notification
     * @return array<string, mixed>
     */
    public static function present(object|array $notification): array
    {
        $message = trim((string) self::value($notification, 'message'));
        $url = trim((string) self::value($notification, 'url'));
        $documentType = trim((string) self::value($notification, 'document_type'));
        $urlPath = Str::lower(trim((string) parse_url($url, PHP_URL_PATH)));

        $module = self::resolveModule($documentType, $urlPath, $message);
        $queue = self::resolveQueue($documentType, $message);
        $location = self::extractLocationMetadata($message);

        return [
            'id' => self::value($notification, 'id'),
            'message' => $message,
            'url' => $url,
            'document_type' => $documentType,
            'quarter' => self::value($notification, 'quarter'),
            'sender_name' => self::value($notification, 'sender_name'),
            'sender_user_id' => self::value($notification, 'sender_user_id'),
            'read_at' => self::value($notification, 'read_at'),
            'is_read' => !empty(self::value($notification, 'read_at')),
            'created_at' => self::value($notification, 'created_at'),
            'updated_at' => self::value($notification, 'updated_at'),
            'project_code' => $location['project_code'],
            'province' => $location['province'],
            'city_municipality' => $location['city_municipality'],
            'barangay' => $location['barangay'],
            'module_key' => $module['key'],
            'module_label' => $module['label'],
            'queue_key' => $queue['key'],
            'queue_label' => $queue['label'],
            'queue_short_label' => $queue['short_label'],
            'queue_icon' => $queue['icon'],
            'queue_tone' => $queue['tone'],
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $notifications
     * @return array<int, array<string, mixed>>
     */
    public static function groupByInboxSections(Collection $notifications): array
    {
        $sections = [
            'returned' => [
                'label' => 'Returned to You',
                'description' => 'Returned items that need revision from the uploader or editor.',
                'tone' => 'danger',
                'icon' => 'fa-rotate-left',
            ],
            'pending_provincial' => [
                'label' => 'For Your Provincial Approval',
                'description' => 'Items awaiting provincial review or validation.',
                'tone' => 'warning',
                'icon' => 'fa-building',
            ],
            'pending_regional' => [
                'label' => 'For Your Regional Approval',
                'description' => 'Items awaiting regional review or validation.',
                'tone' => 'info',
                'icon' => 'fa-map',
            ],
            'approved' => [
                'label' => 'Approved Submissions',
                'description' => 'Submissions that have been successfully validated and approved.',
                'tone' => 'success',
                'icon' => 'fa-check-circle',
            ],
            'announcement' => [
                'label' => 'Announcements',
                'description' => 'Broadcast messages and general notices.',
                'tone' => 'neutral',
                'icon' => 'fa-bullhorn',
            ],
            'other' => [
                'label' => 'Other Updates',
                'description' => 'Everything else that does not fit the approval queues.',
                'tone' => 'success',
                'icon' => 'fa-bell',
            ],
        ];

        $grouped = [];

        foreach ($sections as $sectionKey => $sectionMeta) {
            $sectionItems = $notifications->where('queue_key', $sectionKey)->values();
            if ($sectionItems->isEmpty()) {
                continue;
            }

            $modules = $sectionItems
                ->groupBy('module_label')
                ->map(function (Collection $items, string $moduleLabel): array {
                    return [
                        'module_label' => $moduleLabel,
                        'module_key' => $items->first()['module_key'] ?? Str::slug($moduleLabel),
                        'count' => $items->count(),
                        'items' => $items->values()->all(),
                    ];
                })
                ->sortKeys()
                ->values()
                ->all();

            $grouped[] = [
                'key' => $sectionKey,
                'label' => $sectionMeta['label'],
                'description' => $sectionMeta['description'],
                'tone' => $sectionMeta['tone'],
                'icon' => $sectionMeta['icon'],
                'count' => $sectionItems->count(),
                'modules' => $modules,
            ];
        }

        return $grouped;
    }

    /**
     * @param Collection<int, array<string, mixed>> $notifications
     * @return array<int, array<string, mixed>>
     */
    public static function summarizeQueues(Collection $notifications): array
    {
        return collect(self::groupByInboxSections($notifications))
            ->map(fn (array $section): array => [
                'key' => $section['key'],
                'label' => $section['label'],
                'count' => $section['count'],
                'tone' => $section['tone'],
                'icon' => $section['icon'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param object|array<string, mixed> $source
     */
    protected static function value(object|array $source, string $key, mixed $default = null): mixed
    {
        if (is_array($source)) {
            return $source[$key] ?? $default;
        }

        return $source->{$key} ?? $default;
    }

    /**
     * @return array{key: string, label: string}
     */
    protected static function resolveModule(string $documentType, string $urlPath, string $message): array
    {
        $moduleRules = [
            ['/fund-utilization', 'fund-utilization', 'Fund Utilization Report'],
            ['/initial-project-documents', 'pre-implementation', 'Pre-Implementation Documents'],
            ['/road-maintenance-status', 'road-maintenance-status', 'Road Maintenance Status'],
            ['/rbis-annual-certification', 'rbis-annual-certification', 'RBIS Annual Certification'],
            ['/local-project-monitoring-committee', 'local-project-monitoring-committee', 'Local Project Monitoring Committee'],
            ['/reports/monthly/pd-no-pbbm-2025-1572-1573', 'pd-no-pbbm-2025-1572-1573', 'PD No. PBBM-2025-1572-1573'],
            ['/reports/monthly/swa-annex-f', 'swa-annex-f', 'SWA Annex F Report'],
            ['/annual-maintenance-work-program', 'annual-maintenance-work-program', 'Annual Maintenance Work Program'],
            ['/reports/one-time/project-completion-reports/sglgif', 'sglgif-pcr', 'SGLGIF Project Completion Reports'],
            ['/reports/one-time/project-completion-reports/falgu-gef-sbdp', 'lgsf-pcr', 'LGSF Project Completion Reports'],
            ['/projects/locally-funded', 'locally-funded-projects', 'Locally Funded Projects'],
            ['/notifications', 'announcements', 'Announcements'],
        ];

        foreach ($moduleRules as [$needle, $key, $label]) {
            if ($needle !== '' && str_contains($urlPath, $needle)) {
                return ['key' => $key, 'label' => $label];
            }
        }

        $normalizedDocumentType = Str::lower(trim($documentType));
        if ($normalizedDocumentType === 'bulk-notification') {
            return ['key' => 'announcements', 'label' => 'Announcements'];
        }

        if (in_array($normalizedDocumentType, [
            'mov',
            'batch-document',
            'posting-link',
            'fdp',
            'written-notice',
            'written-notice-dbm',
            'written-notice-dilg',
            'written-notice-speaker',
            'written-notice-president',
            'written-notice-house',
            'written-notice-senate',
            'dbm',
            'dilg',
            'speaker',
            'president',
            'house',
            'senate',
        ], true)) {
            return ['key' => 'fund-utilization', 'label' => 'Fund Utilization Report'];
        }

        $messageRules = [
            ['annual work and financial plan', 'annual-maintenance-work-program', 'Annual Maintenance Work Program'],
            ['monitoring conducted', 'local-project-monitoring-committee', 'Local Project Monitoring Committee'],
            ['road maintenance', 'road-maintenance-status', 'Road Maintenance Status'],
            ['rbis', 'rbis-annual-certification', 'RBIS Annual Certification'],
            ['proof on the transfer of fund', 'pre-implementation', 'Pre-Implementation Documents'],
            ['confirmation on the receipt of fund', 'pre-implementation', 'Pre-Implementation Documents'],
            ['nadai', 'pre-implementation', 'Pre-Implementation Documents'],
            ['itb posting on philgeps', 'pre-implementation', 'Pre-Implementation Documents'],
        ];

        $lowerMessage = Str::lower($message);
        foreach ($messageRules as [$needle, $key, $label]) {
            if (str_contains($lowerMessage, $needle)) {
                return ['key' => $key, 'label' => $label];
            }
        }

        $fallbackLabel = trim((string) Str::of($documentType)
            ->replace(['_', '-'], ' ')
            ->title());

        return [
            'key' => $fallbackLabel !== '' ? Str::slug($fallbackLabel) : 'other-updates',
            'label' => $fallbackLabel !== '' ? $fallbackLabel : 'Other Updates',
        ];
    }

    /**
     * @return array{key: string, label: string, short_label: string, icon: string, tone: string}
     */
    protected static function resolveQueue(string $documentType, string $message): array
    {
        $lowerMessage = Str::lower(trim($message));

        if (Str::lower(trim($documentType)) === 'bulk-notification') {
            return [
                'key' => 'announcement',
                'label' => 'Announcement',
                'short_label' => 'Announcement',
                'icon' => 'fa-bullhorn',
                'tone' => 'neutral',
            ];
        }

        if (
            str_contains($lowerMessage, 'returned by regional office and requires your review')
            || str_contains($lowerMessage, 'returned by the regional office and requires your review')
            || str_contains($lowerMessage, 'for provincial review')
            || str_contains($lowerMessage, 'awaiting dilg provincial office validation')
            || str_contains($lowerMessage, 'awaiting dilg provincial office review')
        ) {
            return [
                'key' => 'pending_provincial',
                'label' => 'Pending Provincial Approval',
                'short_label' => 'Provincial',
                'icon' => 'fa-building',
                'tone' => 'warning',
            ];
        }

        if (
            str_contains($lowerMessage, 'awaiting dilg regional office validation')
            || str_contains($lowerMessage, 'awaiting dilg regional office review')
        ) {
            return [
                'key' => 'pending_regional',
                'label' => 'Pending Regional Approval',
                'short_label' => 'Regional',
                'icon' => 'fa-map',
                'tone' => 'info',
            ];
        }

        if (str_contains($lowerMessage, 'returned')) {
            return [
                'key' => 'returned',
                'label' => 'Returned to Uploader',
                'short_label' => 'Returned',
                'icon' => 'fa-rotate-left',
                'tone' => 'danger',
            ];
        }

        if (
            str_contains($lowerMessage, 'approved')
            || str_contains($lowerMessage, 'validated (dilg ro)')
        ) {
            return [
                'key' => 'approved',
                'label' => 'Approved Submissions',
                'short_label' => 'Approved',
                'icon' => 'fa-check-circle',
                'tone' => 'success',
            ];
        }

        return [
            'key' => 'other',
            'label' => 'Other Update',
            'short_label' => 'Update',
            'icon' => 'fa-bell',
            'tone' => 'success',
        ];
    }

    /**
     * @return array{project_code: string, province: string, city_municipality: string, barangay: string}
     */
    protected static function extractLocationMetadata(string $message): array
    {
        $upperMessage = Str::upper($message);
        preg_match('/\b[A-Z0-9]+(?:-[A-Z0-9]+){3,}\b/', $upperMessage, $projectCodeMatch);
        $projectCode = $projectCodeMatch[0] ?? '';

        $province = '';
        $cityMunicipality = '';
        $barangay = '';

        if (preg_match('/\bfor\s+(.+?)\s*-\s*([A-Za-z .()]+?)(?:\s+and\s|\s*$)/i', $message, $locationMatch)) {
            $rawLocationLeft = trim((string) ($locationMatch[1] ?? ''));
            $rawProvince = trim((string) ($locationMatch[2] ?? ''));
            $province = Str::upper($rawProvince);

            $locationParts = collect(explode(',', $rawLocationLeft))
                ->map(fn ($part) => trim((string) $part))
                ->filter(fn ($part) => $part !== '')
                ->values();

            if ($locationParts->count() >= 2) {
                $barangay = Str::upper((string) $locationParts->first());
                $cityMunicipality = Str::upper((string) $locationParts->last());
            } elseif ($locationParts->count() === 1) {
                $cityMunicipality = Str::upper((string) $locationParts->first());
            }
        }

        return [
            'project_code' => $projectCode,
            'province' => $province,
            'city_municipality' => $cityMunicipality,
            'barangay' => $barangay,
        ];
    }
}
