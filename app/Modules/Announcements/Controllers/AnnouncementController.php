<?php

namespace App\Modules\Announcements\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Services\AnnouncementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnnouncementController extends Controller
{
    public function __construct(
        protected AnnouncementService $announcementService
    ) {}

    /**
     * Check if current user is authorized to manage announcements.
     */
    protected function canManageAnnouncements(?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        if (!$user) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->isRegionalUser()
            || $user->hasCrudPermission('announcement_management', 'add')
            || $user->hasCrudPermission('announcement_management', 'update');
    }

    /**
     * Get list of regions for audience filter.
     */
    /**
     * Get list of CAR provinces for audience filter.
     */
    protected function getAvailableProvinces(): array
    {
        $carProvinces = [];

        if (Schema::hasTable('location_provinces') && Schema::hasTable('location_regions')) {
            $carRegion = DB::table('location_regions')
                ->where('region_name', 'like', '%Cordillera%')
                ->orWhere('region_name', 'like', '%CAR%')
                ->first();

            if ($carRegion) {
                $carProvinces = DB::table('location_provinces')
                    ->where('region_id', $carRegion->id)
                    ->whereNotNull('province_name')
                    ->where('province_name', '<>', '')
                    ->orderBy('province_name')
                    ->pluck('province_name')
                    ->map(fn($p) => strtoupper(trim((string) $p)))
                    ->values()
                    ->toArray();
            }
        }

        if (empty($carProvinces)) {
            $carProvinces = ['ABRA', 'APAYAO', 'BENGUET', 'IFUGAO', 'KALINGA', 'MOUNTAIN PROVINCE'];
        }

        if (!in_array('BAGUIO CITY', $carProvinces, true)) {
            $carProvinces[] = 'BAGUIO CITY';
        }

        return array_values(array_unique($carProvinces));
    }

    /**
     * Display Announcement Center and Management dashboard.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $canManage = $this->canManageAnnouncements($user);

        $activeTab = $request->get('tab', 'feed');
        if ($activeTab === 'manage' && !$canManage) {
            $activeTab = 'feed';
        }

        // Feed Announcements
        $feedFilters = [
            'search' => $request->get('search'),
            'type' => $request->get('type'),
            'priority' => $request->get('priority'),
            'read_state' => $request->get('read_state'),
        ];
        $feedAnnouncements = $this->announcementService->getFeedAnnouncements($user, $feedFilters, 9);

        // Management Announcements (if admin/manager)
        $managementAnnouncements = null;
        $stats = null;
        if ($canManage) {
            $manageFilters = [
                'search' => $request->get('manage_search', $request->get('search')),
                'status' => $request->get('status'),
                'type' => $request->get('type'),
                'priority' => $request->get('priority'),
                'audience' => $request->get('audience'),
                'province' => $request->get('province'),
            ];
            $managementAnnouncements = $this->announcementService->getManagementAnnouncements($manageFilters, 15);
            $stats = $this->announcementService->getManagementStats();
        }

        $availableProvinces = $this->getAvailableProvinces();
        $availableRoles = [
            User::ROLE_SUPERADMIN => 'Superadmin',
            User::ROLE_REGIONAL => 'Regional User',
            User::ROLE_PROVINCIAL => 'Provincial User',
            User::ROLE_MLGOO => 'MLGOO User',
            User::ROLE_LGU => 'LGU User',
        ];

        return view('announcements.index', compact(
            'canManage',
            'activeTab',
            'feedAnnouncements',
            'managementAnnouncements',
            'stats',
            'availableProvinces',
            'availableRoles'
        ));
    }

    /**
     * Show full announcement details.
     */
    public function show($id): View|RedirectResponse
    {
        $user = Auth::user();
        $announcement = Announcement::with(['creator', 'updater'])->findOrFail($id);

        $canManage = $this->canManageAnnouncements($user);

        // If not manager, verify visibility
        if (!$canManage) {
            $isVisible = Announcement::query()->forUser($user)->where('id', $announcement->id)->exists();
            if (!$isVisible) {
                abort(403, 'You are not authorized to view this announcement.');
            }
        }

        // Mark as read
        $this->announcementService->markAsRead($announcement->id, $user->idno);

        return view('announcements.show', compact('announcement', 'canManage'));
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$this->canManageAnnouncements($user)) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:general,advisory,deadline,urgent,maintenance,update,event'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'target_audience' => ['required', 'in:all,role,province'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['string'],
            'target_provinces' => ['nullable', 'array'],
            'target_provinces.*' => ['string'],
            'attachment' => ['nullable', 'file', 'max:15360', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,zip'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'action_text' => ['nullable', 'string', 'max:100'],
            'is_pinned' => ['nullable', 'boolean'],
            'popup_on_login' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date'],
        ]);

        $this->announcementService->storeAnnouncement($validated, $request->file('attachment'), $user);

        return redirect()->route('announcements.index', ['tab' => 'manage'])
            ->with('success', 'Announcement created successfully.');
    }

    /**
     * Update an existing announcement.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$this->canManageAnnouncements($user)) {
            abort(403, 'Unauthorized action.');
        }

        $announcement = Announcement::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['required', 'in:general,advisory,deadline,urgent,maintenance,update,event'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'target_audience' => ['required', 'in:all,role,province'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['string'],
            'target_provinces' => ['nullable', 'array'],
            'target_provinces.*' => ['string'],
            'attachment' => ['nullable', 'file', 'max:15360', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,png,jpg,jpeg,zip'],
            'remove_attachment' => ['nullable', 'boolean'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'action_text' => ['nullable', 'string', 'max:100'],
            'is_pinned' => ['nullable', 'boolean'],
            'popup_on_login' => ['nullable', 'boolean'],
            'status' => ['required', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date'],
        ]);

        $removeAttachment = (bool) $request->input('remove_attachment', false);

        $this->announcementService->updateAnnouncement(
            $announcement,
            $validated,
            $request->file('attachment'),
            $removeAttachment,
            $user
        );

        return redirect()->route('announcements.index', ['tab' => 'manage'])
            ->with('success', 'Announcement updated successfully.');
    }

    /**
     * Delete an announcement.
     */
    public function destroy($id): RedirectResponse
    {
        $user = Auth::user();
        if (!$this->canManageAnnouncements($user)) {
            abort(403, 'Unauthorized action.');
        }

        $announcement = Announcement::findOrFail($id);
        $this->announcementService->deleteAnnouncement($announcement);

        return redirect()->route('announcements.index', ['tab' => 'manage'])
            ->with('success', 'Announcement deleted successfully.');
    }

    /**
     * Toggle status between published and draft.
     */
    public function toggleStatus($id): RedirectResponse
    {
        $user = Auth::user();
        if (!$this->canManageAnnouncements($user)) {
            abort(403, 'Unauthorized action.');
        }

        $announcement = Announcement::findOrFail($id);
        $this->announcementService->toggleStatus($announcement, $user);

        $statusMsg = $announcement->status === Announcement::STATUS_PUBLISHED
            ? 'Announcement published.'
            : 'Announcement reverted to draft.';

        return back()->with('success', $statusMsg);
    }

    /**
     * AJAX endpoint to mark an announcement as read.
     */
    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $this->announcementService->markAsRead((int) $id, $user->idno);

        $topBarData = $this->announcementService->getTopBarData($user);

        return response()->json([
            'success' => true,
            'unread_count' => $topBarData['unread_count'],
        ]);
    }

    /**
     * AJAX endpoint to mark all announcements as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $count = $this->announcementService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'marked_count' => $count,
            'unread_count' => 0,
        ]);
    }

    /**
     * AJAX endpoint to get announcement details for the reader modal.
     */
    public function detailJson($id): JsonResponse
    {
        $user = Auth::user();
        $announcement = Announcement::with('creator')->findOrFail($id);

        $canManage = $this->canManageAnnouncements($user);
        if (!$canManage) {
            $isVisible = Announcement::query()->forUser($user)->where('id', $announcement->id)->exists();
            if (!$isVisible) {
                return response()->json(['error' => 'Not found or unauthorized.'], 403);
            }
        }

        // Mark as read automatically when opened
        $this->announcementService->markAsRead($announcement->id, $user->idno);
        $topBarData = $this->announcementService->getTopBarData($user);

        $typeBadge = $announcement->typeBadge();
        $priorityBadge = $announcement->priorityBadge();

        $authorName = $announcement->creator
            ? trim(($announcement->creator->fname ?? '') . ' ' . ($announcement->creator->lname ?? ''))
            : 'System Administrator';

        return response()->json([
            'id' => $announcement->id,
            'title' => $announcement->title,
            'content' => $announcement->content,
            'type' => $announcement->type,
            'type_badge' => $typeBadge,
            'priority' => $announcement->priority,
            'priority_badge' => $priorityBadge,
            'status' => $announcement->status,
            'target_audience' => $announcement->target_audience,
            'target_roles' => $announcement->target_roles ?? [],
            'target_provinces' => $announcement->target_provinces ?? [],
            'is_pinned' => (bool) $announcement->is_pinned,
            'popup_on_login' => (bool) $announcement->popup_on_login,
            'author_name' => $authorName ?: 'Administrator',
            'audience_desc' => $announcement->audienceDescription(),
            'published_at_formatted' => $announcement->published_at ? $announcement->published_at->format('M d, Y h:i A') : 'Immediately',
            'expires_at_raw' => $announcement->expires_at ? $announcement->expires_at->format('Y-m-d\TH:i') : null,
            'expires_at_formatted' => $announcement->expires_at ? $announcement->expires_at->format('M d, Y h:i A') : null,
            'has_deadline' => $announcement->hasDeadline(),
            'deadline_badge' => $announcement->deadlineBadge(),
            'deadline_at_raw' => $announcement->deadline_at ? $announcement->deadline_at->format('Y-m-d\TH:i') : null,
            'deadline_at_formatted' => $announcement->deadline_at ? $announcement->deadline_at->format('M d, Y h:i A') : null,
            'action_url' => $announcement->action_url,
            'action_text' => $announcement->action_text ?: 'Open Link',
            'has_attachment' => !empty($announcement->attachment_path),
            'attachment_name' => $announcement->attachment_name,
            'attachment_download_url' => route('announcements.download', ['id' => $announcement->id]),
            'unread_count' => $topBarData['unread_count'],
        ]);
    }

    /**
     * AJAX endpoint to get announcement readers list for admins.
     */
    public function readersJson($id): JsonResponse
    {
        $user = Auth::user();
        if (!$this->canManageAnnouncements($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $announcement = Announcement::findOrFail($id);
        $readers = $this->announcementService->getReaders($announcement);

        $data = $readers->map(function ($read) {
            $readerUser = $read->user;
            return [
                'name' => $readerUser ? trim(($readerUser->fname ?? '') . ' ' . ($readerUser->lname ?? '')) : 'Unknown User',
                'username' => $readerUser?->username ?? 'N/A',
                'role' => $readerUser?->roleLabel() ?? ucfirst($readerUser?->role ?? 'User'),
                'province' => $readerUser?->province ?? 'N/A',
                'read_at' => $read->read_at ? $read->read_at->format('M d, Y h:i A') : 'N/A',
            ];
        });

        return response()->json([
            'announcement_id' => $announcement->id,
            'title' => $announcement->title,
            'total_reads' => $readers->count(),
            'readers' => $data,
        ]);
    }

    /**
     * Download or view attached file.
     */
    public function downloadAttachment($id): StreamedResponse|BinaryFileResponse
    {
        $user = Auth::user();
        $announcement = Announcement::findOrFail($id);

        if (!$this->canManageAnnouncements($user)) {
            $isVisible = Announcement::query()->forUser($user)->where('id', $announcement->id)->exists();
            if (!$isVisible) {
                abort(403, 'Unauthorized.');
            }
        }

        if (!$announcement->attachment_path || !Storage::disk($announcement->attachment_disk)->exists($announcement->attachment_path)) {
            abort(404, 'File not found or missing from disk.');
        }

        $filename = $announcement->attachment_name ?: basename($announcement->attachment_path);

        return Storage::disk($announcement->attachment_disk)->download($announcement->attachment_path, $filename);
    }
}

