<?php

namespace App\Modules\Announcements\Services;

use App\Models\User;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Models\AnnouncementRead;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementService
{
    /**
     * Get recent announcements and badge count for the topbar dropdown.
     */
    public function getTopBarData(User $user, int $limit = 5): array
    {
        $baseQuery = Announcement::query()->forUser($user);

        // Fetch recent announcements
        $recentAnnouncements = (clone $baseQuery)
            ->with(['reads' => function ($query) use ($user) {
                $query->where('user_id', $user->idno);
            }])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        // Calculate total active visible count
        $totalActiveCount = (clone $baseQuery)->count();

        // Calculate unread count (visible announcements that don't have a read record by this user)
        $readAnnouncementIds = AnnouncementRead::where('user_id', $user->idno)
            ->pluck('announcement_id')
            ->toArray();

        $unreadCount = (clone $baseQuery)
            ->whereNotIn('id', $readAnnouncementIds)
            ->count();

        return [
            'recent' => $recentAnnouncements,
            'unread_count' => $unreadCount,
            'total_count' => $totalActiveCount,
        ];
    }

    /**
     * Get active published announcements configured to show as a popup upon login.
     */
    public function getLoginPopupAnnouncements(User $user): Collection
    {
        return Announcement::query()
            ->forUser($user)
            ->popupOnLogin()
            ->with(['creator', 'reads' => function ($query) use ($user) {
                $query->where('user_id', $user->idno);
            }])
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->get();
    }

    /**
     * Get paginated announcements visible to a user for the feed / center.
     */
    public function getFeedAnnouncements(User $user, array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $query = Announcement::query()->forUser($user)
            ->with(['creator', 'reads' => function ($q) use ($user) {
                $q->where('user_id', $user->idno);
            }]);

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('content', 'like', $search);
            });
        }

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['read_state'])) {
            if ($filters['read_state'] === 'unread') {
                $query->whereDoesntHave('reads', function ($q) use ($user) {
                    $q->where('user_id', $user->idno);
                });
            } elseif ($filters['read_state'] === 'read') {
                $query->whereHas('reads', function ($q) use ($user) {
                    $q->where('user_id', $user->idno);
                });
            }
        }

        return $query->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get management list of announcements for administrators.
     */
    public function getManagementAnnouncements(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Announcement::query()->with(['creator', 'updater'])->withCount('reads');

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                  ->orWhere('content', 'like', $search);
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['audience']) && $filters['audience'] !== 'all') {
            $query->where('target_audience', $filters['audience']);
        }

        if (!empty($filters['province']) && $filters['province'] !== 'all') {
            $prov = $filters['province'];
            $query->where(function ($q) use ($prov) {
                $q->whereJsonContains('target_provinces', $prov);
            });
        }

        return $query->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get stats for management overview.
     */
    public function getManagementStats(): array
    {
        $now = now();
        return [
            'total' => Announcement::count(),
            'published' => Announcement::where('status', Announcement::STATUS_PUBLISHED)->count(),
            'draft' => Announcement::where('status', Announcement::STATUS_DRAFT)->count(),
            'urgent' => Announcement::where('priority', Announcement::PRIORITY_URGENT)->count(),
            'total_reads' => AnnouncementRead::count(),
        ];
    }

    /**
     * Mark an announcement as read for a given user.
     */
    public function markAsRead(int $announcementId, int $userId): bool
    {
        AnnouncementRead::updateOrCreate(
            ['announcement_id' => $announcementId, 'user_id' => $userId],
            ['read_at' => now()]
        );

        return true;
    }

    /**
     * Mark all visible announcements as read for a given user.
     */
    public function markAllAsRead(User $user): int
    {
        $visibleAnnouncementIds = Announcement::query()->forUser($user)->pluck('id');
        $now = now();
        $count = 0;

        foreach ($visibleAnnouncementIds as $announcementId) {
            AnnouncementRead::updateOrCreate(
                ['announcement_id' => $announcementId, 'user_id' => $user->idno],
                ['read_at' => $now]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Store a new announcement.
     */
    public function storeAnnouncement(array $data, ?UploadedFile $attachment, User $creator): Announcement
    {
        $attachmentPath = null;
        $attachmentName = null;
        $attachmentDisk = 'public';

        if ($attachment && $attachment->isValid()) {
            $originalName = $attachment->getClientOriginalName();
            $extension = $attachment->getClientOriginalExtension();
            $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;
            $attachmentPath = $attachment->storeAs('announcements', $safeName, $attachmentDisk);
            $attachmentName = $originalName;
        }

        $status = $data['status'] ?? Announcement::STATUS_PUBLISHED;
        $publishedAt = null;

        if ($status === Announcement::STATUS_PUBLISHED) {
            $publishedAt = !empty($data['published_at']) ? Carbon::parse($data['published_at']) : now();
        }

        $expiresAt = !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;
        $deadlineAt = !empty($data['deadline_at']) ? Carbon::parse($data['deadline_at']) : null;

        return Announcement::create([
            'title' => trim($data['title']),
            'content' => $data['content'],
            'type' => $data['type'] ?? Announcement::TYPE_GENERAL,
            'priority' => $data['priority'] ?? Announcement::PRIORITY_NORMAL,
            'target_audience' => $data['target_audience'] ?? Announcement::AUDIENCE_ALL,
            'target_roles' => ($data['target_audience'] ?? '') === Announcement::AUDIENCE_ROLE ? ($data['target_roles'] ?? null) : null,
            'target_regions' => null,
            'target_provinces' => ($data['target_audience'] ?? '') === Announcement::AUDIENCE_PROVINCE ? ($data['target_provinces'] ?? null) : null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_disk' => $attachmentDisk,
            'action_url' => !empty($data['action_url']) ? trim($data['action_url']) : null,
            'action_text' => !empty($data['action_text']) ? trim($data['action_text']) : null,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'popup_on_login' => isset($data['popup_on_login']) ? (bool) $data['popup_on_login'] : true,
            'status' => $status,
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
            'deadline_at' => $deadlineAt,
            'created_by' => $creator->idno,
            'updated_by' => $creator->idno,
        ]);
    }

    /**
     * Update an existing announcement.
     */
    public function updateAnnouncement(
        Announcement $announcement,
        array $data,
        ?UploadedFile $attachment,
        bool $removeAttachment,
        User $updater
    ): Announcement {
        $attachmentPath = $announcement->attachment_path;
        $attachmentName = $announcement->attachment_name;
        $attachmentDisk = $announcement->attachment_disk ?: 'public';

        if ($removeAttachment) {
            if ($attachmentPath && Storage::disk($attachmentDisk)->exists($attachmentPath)) {
                Storage::disk($attachmentDisk)->delete($attachmentPath);
            }
            $attachmentPath = null;
            $attachmentName = null;
        }

        if ($attachment && $attachment->isValid()) {
            // Delete old file if present
            if ($attachmentPath && Storage::disk($attachmentDisk)->exists($attachmentPath)) {
                Storage::disk($attachmentDisk)->delete($attachmentPath);
            }

            $originalName = $attachment->getClientOriginalName();
            $extension = $attachment->getClientOriginalExtension();
            $safeName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;
            $attachmentPath = $attachment->storeAs('announcements', $safeName, $attachmentDisk);
            $attachmentName = $originalName;
        }

        $status = $data['status'] ?? $announcement->status;
        $publishedAt = $announcement->published_at;

        if ($status === Announcement::STATUS_PUBLISHED) {
            if (!empty($data['published_at'])) {
                $publishedAt = Carbon::parse($data['published_at']);
            } elseif (!$publishedAt) {
                $publishedAt = now();
            }
        }

        $expiresAt = !empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;
        $deadlineAt = !empty($data['deadline_at']) ? Carbon::parse($data['deadline_at']) : null;

        $announcement->update([
            'title' => trim($data['title']),
            'content' => $data['content'],
            'type' => $data['type'] ?? $announcement->type,
            'priority' => $data['priority'] ?? $announcement->priority,
            'target_audience' => $data['target_audience'] ?? Announcement::AUDIENCE_ALL,
            'target_roles' => ($data['target_audience'] ?? '') === Announcement::AUDIENCE_ROLE ? ($data['target_roles'] ?? null) : null,
            'target_regions' => null,
            'target_provinces' => ($data['target_audience'] ?? '') === Announcement::AUDIENCE_PROVINCE ? ($data['target_provinces'] ?? null) : null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_disk' => $attachmentDisk,
            'action_url' => !empty($data['action_url']) ? trim($data['action_url']) : null,
            'action_text' => !empty($data['action_text']) ? trim($data['action_text']) : null,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'popup_on_login' => (bool) ($data['popup_on_login'] ?? false),
            'status' => $status,
            'published_at' => $publishedAt,
            'expires_at' => $expiresAt,
            'deadline_at' => $deadlineAt,
            'updated_by' => $updater->idno,
        ]);

        return $announcement;
    }

    /**
     * Delete an announcement and its attachment.
     */
    public function deleteAnnouncement(Announcement $announcement): bool
    {
        if ($announcement->attachment_path && Storage::disk($announcement->attachment_disk)->exists($announcement->attachment_path)) {
            Storage::disk($announcement->attachment_disk)->delete($announcement->attachment_path);
        }

        return (bool) $announcement->delete();
    }

    /**
     * Toggle status between published and draft.
     */
    public function toggleStatus(Announcement $announcement, User $updater): Announcement
    {
        $newStatus = $announcement->status === Announcement::STATUS_PUBLISHED
            ? Announcement::STATUS_DRAFT
            : Announcement::STATUS_PUBLISHED;

        $publishedAt = $announcement->published_at;
        if ($newStatus === Announcement::STATUS_PUBLISHED && !$publishedAt) {
            $publishedAt = now();
        }

        $announcement->update([
            'status' => $newStatus,
            'published_at' => $publishedAt,
            'updated_by' => $updater->idno,
        ]);

        return $announcement;
    }

    /**
     * Get users who have read the announcement.
     */
    public function getReaders(Announcement $announcement): Collection
    {
        return $announcement->reads()
            ->with('user:idno,fname,lname,username,role,province,emailaddress')
            ->orderByDesc('read_at')
            ->get();
    }
}

