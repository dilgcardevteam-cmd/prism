<?php

namespace App\Modules\Announcements\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Announcement extends Model
{
    use HasFactory;

    protected $table = 'announcements';

    public const TYPE_GENERAL = 'general';
    public const TYPE_ADVISORY = 'advisory';
    public const TYPE_DEADLINE = 'deadline';
    public const TYPE_URGENT = 'urgent';
    public const TYPE_MAINTENANCE = 'maintenance';
    public const TYPE_UPDATE = 'update';
    public const TYPE_EVENT = 'event';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const AUDIENCE_ALL = 'all';
    public const AUDIENCE_ROLE = 'role';
    public const AUDIENCE_PROVINCE = 'province';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'title',
        'content',
        'type',
        'priority',
        'target_audience',
        'target_roles',
        'target_regions',
        'target_provinces',
        'attachment_path',
        'attachment_name',
        'attachment_disk',
        'action_url',
        'action_text',
        'is_pinned',
        'popup_on_login',
        'status',
        'published_at',
        'expires_at',
        'deadline_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'target_regions' => 'array',
        'target_provinces' => 'array',
        'is_pinned' => 'boolean',
        'popup_on_login' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    public static function typeOptions(): array
    {
        return [
            self::TYPE_GENERAL => 'General',
            self::TYPE_ADVISORY => 'Advisory',
            self::TYPE_DEADLINE => 'Submission Deadline',
            self::TYPE_URGENT => 'Urgent',
            self::TYPE_MAINTENANCE => 'System Maintenance',
            self::TYPE_UPDATE => 'System Update',
            self::TYPE_EVENT => 'Event / Activity',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public static function audienceOptions(): array
    {
        return [
            self::AUDIENCE_ALL => 'All Users',
            self::AUDIENCE_ROLE => 'By Role',
            self::AUDIENCE_PROVINCE => 'By CAR Province',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ARCHIVED => 'Archived',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'idno');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'idno');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class, 'announcement_id');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads', 'announcement_id', 'user_id', 'id', 'idno')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Scope query to only active published announcements.
     */
    public function scopeActivePublished(Builder $query): Builder
    {
        $now = now();
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>=', $now);
            });
    }

    /**
     * Scope query to announcements visible for a given user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        $userRole = strtolower(trim((string) ($user->normalizedRole() ?? $user->role ?? '')));
        $userProvince = strtoupper(trim((string) ($user->province ?? '')));

        return $query->activePublished()->where(function (Builder $q) use ($user, $userRole, $userProvince) {
            // Superadmins see all active published announcements
            if ($user->isSuperAdmin()) {
                $q->whereRaw('1 = 1');
                return;
            }

            // 1. All users
            $q->where('target_audience', self::AUDIENCE_ALL);

            // 2. Targeted by role
            $q->orWhere(function (Builder $sub) use ($userRole) {
                $sub->where('target_audience', self::AUDIENCE_ROLE)
                    ->where(function (Builder $roleSub) use ($userRole) {
                        $roleSub->whereJsonContains('target_roles', $userRole)
                                ->orWhereJsonContains('target_roles', 'all')
                                ->orWhereNull('target_roles');
                    });
            });

            // 3. Targeted by CAR province
            if ($userProvince !== '') {
                $q->orWhere(function (Builder $sub) use ($userProvince) {
                    $sub->where('target_audience', self::AUDIENCE_PROVINCE)
                        ->where(function (Builder $provSub) use ($userProvince) {
                            $provSub->whereJsonContains('target_provinces', $userProvince)
                                    ->orWhereJsonContains('target_provinces', ucfirst(strtolower($userProvince)))
                                    ->orWhereNull('target_provinces');
                        });
                });
            }
        });
    }

    /**
     * Scope query to announcements configured to popup on login.
     */
    public function scopePopupOnLogin(Builder $query): Builder
    {
        return $query->where('popup_on_login', true);
    }

    /**
     * Check if a specific user has read this announcement.
     */
    public function isReadByUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->relationLoaded('reads')) {
            return $this->reads->contains('user_id', $user->idno);
        }

        return $this->reads()->where('user_id', $user->idno)->exists();
    }

    /**
     * Get visual badge details for type/category.
     */
    public function typeBadge(): array
    {
        return match ($this->type) {
            self::TYPE_URGENT => [
                'label' => 'Urgent',
                'tone' => 'danger',
                'icon' => 'fa-exclamation-circle',
                'bg' => '#fef2f2',
                'border' => '#fecaca',
                'color' => '#dc2626',
            ],
            self::TYPE_ADVISORY => [
                'label' => 'Advisory',
                'tone' => 'info',
                'icon' => 'fa-info-circle',
                'bg' => '#eff6ff',
                'border' => '#bfdbfe',
                'color' => '#2563eb',
            ],
            self::TYPE_DEADLINE => [
                'label' => 'Deadline Notice',
                'tone' => 'danger',
                'icon' => 'fa-calendar-times',
                'bg' => '#fff1f2',
                'border' => '#fecdd3',
                'color' => '#e11d48',
            ],
            self::TYPE_MAINTENANCE => [
                'label' => 'Maintenance',
                'tone' => 'warning',
                'icon' => 'fa-screwdriver-wrench',
                'bg' => '#fffbeb',
                'border' => '#fde68a',
                'color' => '#d97706',
            ],
            self::TYPE_UPDATE => [
                'label' => 'System Update',
                'tone' => 'success',
                'icon' => 'fa-sparkles',
                'bg' => '#f0fdf4',
                'border' => '#bbf7d0',
                'color' => '#16a34a',
            ],
            self::TYPE_EVENT => [
                'label' => 'Event',
                'tone' => 'purple',
                'icon' => 'fa-calendar-alt',
                'bg' => '#faf5ff',
                'border' => '#e9d5ff',
                'color' => '#9333ea',
            ],
            default => [
                'label' => 'General',
                'tone' => 'primary',
                'icon' => 'fa-bullhorn',
                'bg' => '#f8fafc',
                'border' => '#cbd5e1',
                'color' => '#002C76',
            ],
        };
    }

    /**
     * Get visual badge details for priority.
     */
    public function priorityBadge(): array
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => ['label' => 'Urgent Priority', 'color' => '#dc2626', 'bg' => '#fee2e2'],
            self::PRIORITY_HIGH => ['label' => 'High Priority', 'color' => '#ea580c', 'bg' => '#ffedd5'],
            self::PRIORITY_LOW => ['label' => 'Low Priority', 'color' => '#64748b', 'bg' => '#f1f5f9'],
            default => ['label' => 'Normal Priority', 'color' => '#0284c7', 'bg' => '#e0f2fe'],
        };
    }

    /**
     * Audience summary string.
     */
    public function audienceDescription(): string
    {
        if ($this->target_audience === self::AUDIENCE_ALL) {
            return 'All PRISM Users';
        }

        if ($this->target_audience === self::AUDIENCE_ROLE) {
            $roles = (array) ($this->target_roles ?? []);
            if (empty($roles)) {
                return 'All Roles';
            }
            $roleLabels = [
                User::ROLE_SUPERADMIN => 'Superadmin',
                User::ROLE_REGIONAL => 'Regional',
                User::ROLE_PROVINCIAL => 'Provincial',
                User::ROLE_MLGOO => 'MLGOO',
                User::ROLE_LGU => 'LGU',
            ];
            $mapped = array_map(fn($r) => $roleLabels[$r] ?? ucfirst($r), $roles);
            return 'Roles: ' . implode(', ', $mapped);
        }

        if ($this->target_audience === self::AUDIENCE_PROVINCE) {
            $provinces = (array) ($this->target_provinces ?? []);
            if (empty($provinces)) {
                return 'All CAR Provinces';
            }
            return 'CAR: ' . implode(', ', $provinces);
        }

        return 'Custom Audience';
    }

    /**
     * Is announcement currently expired?
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if this announcement has a deadline set.
     */
    public function hasDeadline(): bool
    {
        return $this->deadline_at !== null;
    }

    /**
     * Check if the set deadline has already passed.
     */
    public function isDeadlinePassed(): bool
    {
        return $this->deadline_at !== null && $this->deadline_at->isPast();
    }

    /**
     * Get computed visual badge details for the deadline.
     */
    public function deadlineBadge(): ?array
    {
        if (!$this->deadline_at) {
            return null;
        }

        $now = now();
        $isPast = $this->deadline_at->isPast();
        $hoursDiff = (int) ceil($now->diffInHours($this->deadline_at, false));
        $daysDiff = (int) ceil($now->diffInDays($this->deadline_at, false));

        if ($isPast) {
            return [
                'is_past' => true,
                'status' => 'passed',
                'label' => 'Deadline Passed',
                'relative' => 'Ended ' . $this->deadline_at->diffForHumans(),
                'formatted' => $this->deadline_at->format('M d, Y h:i A'),
                'tone' => 'danger',
                'bg' => '#fee2e2',
                'color' => '#991b1b',
                'border' => '#fca5a5',
                'icon' => 'fa-calendar-times',
            ];
        }

        if ($hoursDiff <= 24) {
            $h = max(1, $hoursDiff);
            return [
                'is_past' => false,
                'status' => 'due_soon',
                'label' => 'Due in ' . $h . ' ' . Str::plural('hour', $h),
                'relative' => 'Due ' . $this->deadline_at->diffForHumans(),
                'formatted' => $this->deadline_at->format('M d, Y h:i A'),
                'tone' => 'urgent',
                'bg' => '#fef2f2',
                'color' => '#dc2626',
                'border' => '#fecaca',
                'icon' => 'fa-clock',
            ];
        }

        if ($daysDiff <= 3) {
            $d = max(1, $daysDiff);
            return [
                'is_past' => false,
                'status' => 'approaching',
                'label' => 'Due in ' . $d . ' ' . Str::plural('day', $d),
                'relative' => 'Due ' . $this->deadline_at->diffForHumans(),
                'formatted' => $this->deadline_at->format('M d, Y h:i A'),
                'tone' => 'warning',
                'bg' => '#fffbeb',
                'color' => '#b45309',
                'border' => '#fde68a',
                'icon' => 'fa-hourglass-half',
            ];
        }

        return [
            'is_past' => false,
            'status' => 'upcoming',
            'label' => 'Deadline: ' . $this->deadline_at->format('M d, Y'),
            'relative' => 'Due ' . $this->deadline_at->diffForHumans(),
            'formatted' => $this->deadline_at->format('M d, Y h:i A'),
            'tone' => 'info',
            'bg' => '#eff6ff',
            'color' => '#1d4ed8',
            'border' => '#bfdbfe',
            'icon' => 'fa-calendar-check',
        ];
    }
}

