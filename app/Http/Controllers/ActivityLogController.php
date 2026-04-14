<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;
use App\Support\InputSanitizer;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
        $this->middleware(['auth', 'superadmin']);
    }

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $tableReady = Schema::hasTable('activity_logs');
        $query = ActivityLog::query()->with('user:idno,fname,lname,role');

        if ($tableReady) {
            $query = $this->applyFilters($query, $filters);
        }

        $logs = $tableReady
            ? $query->paginate(25)->withQueryString()
            : new LengthAwarePaginator(
                items: collect(),
                total: 0,
                perPage: 25,
                currentPage: $request->integer('page', 1),
                options: [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ],
            );

        return view('admin.utilities.activity-logs', [
            'logs' => $logs,
            'tableReady' => $tableReady,
            'filters' => $filters,
            'actionOptions' => $tableReady
                ? ActivityLog::query()->distinct()->orderBy('action')->pluck('action')->all()
                : $this->defaultActionOptions(),
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        if (!Schema::hasTable('activity_logs')) {
            return redirect()
                ->route('utilities.activity-logs.index')
                ->with('error', 'The activity logs table is not available yet. Run the migration first.');
        }

        $filters = $this->validatedFilters($request);
        $query = $this->applyFilters(
            ActivityLog::query()->with('user:idno,fname,lname,role'),
            $filters,
        );

        $this->activityLogService->log(
            $request->user(),
            ActivityLog::ACTION_EXPORT,
            'Exported activity logs to CSV.',
            [
                'request' => $request,
                'properties' => [
                    'module' => 'activity_logs',
                    'filters' => $filters,
                ],
            ],
        );

        $filename = 'activity-logs-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fputcsv($output, [
                'ID',
                'Timestamp',
                'Timezone',
                'User ID',
                'Username',
                'Action',
                'Description',
                'IP Address',
                'Device',
                'User Agent',
            ]);

            foreach ($query->cursor() as $log) {
                fputcsv($output, [
                    $log->id,
                    optional($log->created_at)->format('Y-m-d H:i:s'),
                    $log->timezone,
                    $log->user_id,
                    $log->username,
                    $log->action,
                    $log->description,
                    $log->ip_address,
                    $log->device,
                    $log->user_agent,
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'user' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:120'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort' => ['nullable', Rule::in(['latest', 'oldest'])],
        ]);

        return [
            'user' => InputSanitizer::sanitizePlainText((string) ($validated['user'] ?? '')),
            'action' => InputSanitizer::sanitizePlainText((string) ($validated['action'] ?? '')),
            'search' => InputSanitizer::sanitizePlainText((string) ($validated['search'] ?? ''), true),
            'date_from' => $validated['date_from'] ?? '',
            'date_to' => $validated['date_to'] ?? '',
            'sort' => $validated['sort'] ?? 'latest',
        ];
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if ($filters['user'] !== '') {
            $needle = '%' . strtolower($filters['user']) . '%';

            $query->where(function (Builder $userQuery) use ($needle): void {
                $userQuery
                    ->whereRaw('LOWER(TRIM(COALESCE(username, ""))) LIKE ?', [$needle])
                    ->orWhereRaw('CAST(COALESCE(user_id, 0) AS CHAR) LIKE ?', [$needle]);
            });
        }

        if ($filters['action'] !== '') {
            $query->where('action', strtoupper($filters['action']));
        }

        if ($filters['search'] !== '') {
            $needle = '%' . strtolower($filters['search']) . '%';

            $query->where(function (Builder $searchQuery) use ($needle): void {
                $searchQuery
                    ->whereRaw('LOWER(COALESCE(description, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(username, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(action, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(ip_address, "")) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(COALESCE(device, "")) LIKE ?', [$needle]);
            });
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', Carbon::parse($filters['date_from'])->toDateString());
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', Carbon::parse($filters['date_to'])->toDateString());
        }

        if (($filters['sort'] ?? 'latest') === 'oldest') {
            $query->orderBy('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query;
    }

    private function defaultActionOptions(): array
    {
        return [
            ActivityLog::ACTION_LOGIN,
            ActivityLog::ACTION_LOGOUT,
            ActivityLog::ACTION_REGISTER,
            ActivityLog::ACTION_FAILED_LOGIN,
            ActivityLog::ACTION_PASSWORD_CHANGE,
            ActivityLog::ACTION_PASSWORD_RESET_REQUEST,
            ActivityLog::ACTION_PASSWORD_RESET,
            ActivityLog::ACTION_CREATE,
            ActivityLog::ACTION_READ,
            ActivityLog::ACTION_UPDATE,
            ActivityLog::ACTION_DELETE,
            ActivityLog::ACTION_UPLOAD,
            ActivityLog::ACTION_EXPORT,
            ActivityLog::ACTION_ROLE_CHANGE,
            ActivityLog::ACTION_PERMISSION_CHANGE,
            ActivityLog::ACTION_STATUS_CHANGE,
            ActivityLog::ACTION_VALIDATION_FAILED,
            ActivityLog::ACTION_MAINTENANCE_MODE_CHANGE,
        ];
    }
}
