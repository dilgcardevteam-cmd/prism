<?php

namespace App\Services;

use App\Models\User;
use App\Support\NotificationUrl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InterventionNotificationService
{
    /**
     * @param iterable<int|string|null> $recipientIds
     */
    public function notifyRecipientIds(
        iterable $recipientIds,
        int $actorId,
        string $message,
        string $url,
        string $documentType,
        ?string $quarter = null
    ): void {
        $this->insertNotifications($recipientIds, $actorId, $message, $url, $documentType, $quarter);
    }

    public function notifyProvincialDilg(
        ?string $province,
        int $actorId,
        string $message,
        string $url,
        string $documentType,
        ?string $quarter = null
    ): void {
        $province = trim((string) $province);
        if ($province === '') {
            return;
        }

        $recipientIds = User::query()
            ->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['DILG'])
            ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [Str::lower($province)])
            ->whereRaw('LOWER(TRIM(COALESCE(province, ""))) <> ?', ['regional office'])
            ->where('status', 'active')
            ->pluck('idno');

        $this->insertNotifications($recipientIds, $actorId, $message, $url, $documentType, $quarter);
    }

    public function notifyRegionalDilg(
        int $actorId,
        string $message,
        string $url,
        string $documentType,
        ?string $quarter = null
    ): void {
        $recipientIds = User::query()
            ->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['DILG'])
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', ['regional office'])
                    ->orWhereRaw('LOWER(TRIM(COALESCE(office, ""))) LIKE ?', ['%regional office%']);
            })
            ->pluck('idno');

        $this->insertNotifications($recipientIds, $actorId, $message, $url, $documentType, $quarter);
    }

    /**
     * @param iterable<int|string|null> $extraRecipientIds
     */
    public function notifyScopedLgu(
        ?string $province,
        ?string $office,
        iterable $extraRecipientIds,
        int $actorId,
        string $message,
        string $url,
        string $documentType,
        ?string $quarter = null
    ): void {
        $recipientQuery = User::query()
            ->whereRaw('UPPER(TRIM(COALESCE(agency, ""))) = ?', ['LGU'])
            ->where('status', 'active');

        $province = trim((string) $province);
        if ($province !== '') {
            $recipientQuery->whereRaw('LOWER(TRIM(COALESCE(province, ""))) = ?', [Str::lower($province)]);
        }

        $provinceRecipients = $recipientQuery->get(['idno', 'office']);
        $recipientIds = $provinceRecipients->pluck('idno');

        $officeComparable = $this->normalizeOfficeComparable($office);
        if ($officeComparable !== '' && $provinceRecipients->isNotEmpty()) {
            $filteredRecipients = $provinceRecipients->filter(function ($recipient) use ($officeComparable) {
                return $this->normalizeOfficeComparable($recipient->office ?? null) === $officeComparable;
            })->pluck('idno');

            if ($filteredRecipients->isNotEmpty()) {
                $recipientIds = $filteredRecipients;
            }
        }

        $recipientIds = $recipientIds->merge(collect($extraRecipientIds));

        $this->insertNotifications($recipientIds, $actorId, $message, $url, $documentType, $quarter);
    }

    /**
     * @param iterable<int|string|null> $recipientIds
     */
    private function insertNotifications(
        iterable $recipientIds,
        int $actorId,
        string $message,
        string $url,
        string $documentType,
        ?string $quarter = null
    ): void {
        if (!Schema::hasTable('tbnotifications')) {
            return;
        }

        $url = NotificationUrl::normalizeForStorage($url);
        $now = now();
        $rows = collect($recipientIds)
            ->map(function ($recipientId) {
                return (int) $recipientId;
            })
            ->filter(function ($recipientId) use ($actorId) {
                return $recipientId > 0 && $recipientId !== $actorId;
            })
            ->unique()
            ->values()
            ->map(function ($recipientId) use ($message, $url, $documentType, $quarter, $now) {
                return [
                    'user_id' => $recipientId,
                    'message' => $message,
                    'url' => $url,
                    'document_type' => Str::limit($documentType, 100, ''),
                    'quarter' => $quarter,
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->all();

        if (empty($rows)) {
            return;
        }

        DB::table('tbnotifications')->insert($rows);
    }

    private function normalizeOfficeComparable(?string $value): string
    {
        $normalizedValue = Str::lower(trim((string) $value));
        $baseValue = trim((string) preg_replace('/,.*$/', '', $normalizedValue));
        $baseValue = preg_replace('/\([^)]*\)/', ' ', $baseValue) ?? $baseValue;
        $baseValue = preg_replace('/^(municipality|city)\s+of\s+/i', '', $baseValue) ?? $baseValue;
        $baseValue = preg_replace('/\s+(municipality|city)$/i', '', $baseValue) ?? $baseValue;
        $baseValue = preg_replace('/[^a-z0-9\s-]/i', ' ', $baseValue) ?? $baseValue;
        $baseValue = preg_replace('/\s+/', ' ', $baseValue) ?? $baseValue;

        return trim($baseValue);
    }
}
