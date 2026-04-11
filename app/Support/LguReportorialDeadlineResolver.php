<?php

namespace App\Support;

use App\Models\LguReportorialDeadline;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class LguReportorialDeadlineResolver
{
    public function resolveMany(string $aspect, int $reportingYear, array $reportingPeriods): array
    {
        $resolved = [];

        foreach ($reportingPeriods as $reportingPeriod) {
            $resolved[$reportingPeriod] = $this->resolve($aspect, $reportingYear, (string) $reportingPeriod);
        }

        return $resolved;
    }

    public function resolve(string $aspect, int $reportingYear, string $reportingPeriod): ?array
    {
        if (!Schema::hasTable('lgu_reportorial_deadlines')) {
            return null;
        }

        $record = LguReportorialDeadline::query()
            ->where('aspect', strtolower(trim($aspect)))
            ->where('reporting_year', $reportingYear)
            ->where('reporting_period', trim($reportingPeriod))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (!$record) {
            return null;
        }

        $deadlineDate = $record->deadline_date instanceof Carbon
            ? $record->deadline_date->format('Y-m-d')
            : trim((string) $record->deadline_date);
        $deadlineTime = $this->normalizeTime($record->deadline_time);

        if ($deadlineDate === '') {
            return null;
        }

        $timezone = config('app.timezone');
        $deadlineAt = $this->parseDeadlineAt($deadlineDate, $deadlineTime, $timezone);

        if (!$deadlineAt) {
            return null;
        }

        return [
            'display' => $this->formatDisplay($deadlineDate, $deadlineTime, $deadlineAt),
            'deadline_iso' => $deadlineAt->toIso8601String(),
            'deadline_at' => $deadlineAt,
            'deadline_date' => $deadlineDate,
            'deadline_time' => $deadlineTime,
            'is_closed' => Carbon::now($timezone)->greaterThanOrEqualTo($deadlineAt),
        ];
    }

    private function parseDeadlineAt(string $deadlineDate, string $deadlineTime, string $timezone): ?Carbon
    {
        if ($deadlineTime !== '') {
            foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
                try {
                    return Carbon::createFromFormat(
                        $format,
                        $deadlineDate . ' ' . $deadlineTime,
                        $timezone
                    );
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        try {
            return Carbon::parse($deadlineDate, $timezone)->endOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDisplay(string $deadlineDate, string $deadlineTime, Carbon $deadlineAt): string
    {
        try {
            $formattedDate = Carbon::parse($deadlineDate)->format('M j, Y');
        } catch (\Throwable) {
            $formattedDate = $deadlineDate;
        }

        if ($deadlineTime === '') {
            return $formattedDate;
        }

        return $formattedDate . ' ' . $deadlineAt->format('h:i A');
    }

    private function normalizeTime(mixed $value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $normalized)->format($format === 'H:i:s' ? 'H:i:s' : 'H:i');
            } catch (\Throwable) {
                continue;
            }
        }

        return '';
    }
}
