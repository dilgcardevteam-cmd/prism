<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

class SystemMaintenanceState
{
    private const STORAGE_FILE = 'system-maintenance.json';

    private ?array $cachedState = null;

    public function state(): array
    {
        if ($this->cachedState !== null) {
            return $this->cachedState;
        }

        $rawState = [];
        $path = $this->storagePath();

        if (is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $rawState = $decoded;
            }
        }

        return $this->cachedState = $this->normalizeState($rawState);
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->state()['enabled'] ?? false);
    }

    public function setEnabled(bool $enabled, ?User $user = null): array
    {
        $timestamp = now();
        $updatedByName = trim((string) ($user?->fullName() ?: $user?->username ?: ''));

        $state = [
            'enabled' => $enabled,
            'updated_at' => $timestamp->toIso8601String(),
            'updated_by_id' => $user?->getAuthIdentifier(),
            'updated_by_name' => $updatedByName !== '' ? $updatedByName : null,
        ];

        $directory = dirname($this->storagePath());
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            $this->storagePath(),
            json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );

        return $this->cachedState = $this->normalizeState($state);
    }

    private function normalizeState(array $state): array
    {
        $updatedAt = null;
        if (is_string($state['updated_at'] ?? null) && trim((string) $state['updated_at']) !== '') {
            try {
                $updatedAt = Carbon::parse((string) $state['updated_at']);
            } catch (\Throwable) {
                $updatedAt = null;
            }
        }

        $updatedByName = trim((string) ($state['updated_by_name'] ?? ''));

        return [
            'enabled' => (bool) ($state['enabled'] ?? false),
            'updated_at' => $updatedAt?->toIso8601String(),
            'updated_at_display' => $updatedAt?->format('M d, Y h:i A'),
            'updated_by_id' => isset($state['updated_by_id']) && is_numeric($state['updated_by_id'])
                ? (int) $state['updated_by_id']
                : null,
            'updated_by_name' => $updatedByName !== '' ? $updatedByName : null,
        ];
    }

    private function storagePath(): string
    {
        return storage_path('app/' . self::STORAGE_FILE);
    }
}
