<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Ticket extends Model
{
    use HasFactory;

    public const LEVEL_PROVINCIAL = 'provincial';
    public const LEVEL_REGIONAL = 'regional';
    public const LEVEL_CENTRAL_OFFICE = 'central_office';

    public const PRIORITY_LOW = 'Low';
    public const PRIORITY_MEDIUM = 'Medium';
    public const PRIORITY_HIGH = 'High';
    public const PRIORITY_URGENT = 'Urgent';

    public const STATUS_SUBMITTED = 'Submitted';
    public const STATUS_UNDER_REVIEW_BY_PROVINCE = 'Under Review by Province';
    public const STATUS_RESOLVED_BY_PROVINCE = 'Resolved by Province';
    public const STATUS_ESCALATED_TO_REGION = 'Escalated to Region';
    public const STATUS_UNDER_REVIEW_BY_REGION = 'Under Review by Region';
    public const STATUS_RESOLVED_BY_REGION = 'Resolved by Region';
    public const STATUS_FORWARDED_TO_CENTRAL_OFFICE = 'Forwarded to Central Office';
    public const STATUS_RESOLVED_BY_CENTRAL_OFFICE = 'Resolved by Central Office';
    public const STATUS_CLOSED = 'Closed';

    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'category_id',
        'subcategory',
        'priority',
        'status',
        'current_level',
        'assigned_role',
        'contact_information',
        'region_scope',
        'province_scope',
        'office_scope',
        'submitted_by',
        'assigned_to',
        'escalation_reason',
        'escalated_by',
        'escalated_at',
        'forwarded_to_central_office',
        'forwarded_by',
        'forwarded_at',
        'resolved_by',
        'resolved_at',
        'closed_at',
        'date_submitted',
        'last_status_changed_at',
    ];

    protected function casts(): array
    {
        return [
            'forwarded_to_central_office' => 'boolean',
            'escalated_at' => 'datetime',
            'forwarded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'date_submitted' => 'datetime',
            'last_status_changed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $ticket): void {
            if ($ticket->ticket_number) {
                return;
            }

            $ticket->forceFill([
                'ticket_number' => self::formatTicketNumber(
                    $ticket->id,
                    $ticket->date_submitted ?? $ticket->created_at ?? now(),
                ),
            ])->saveQuietly();
        });
    }

    public static function formatTicketNumber(int $id, Carbon|string|null $date = null): string
    {
        $ticketDate = $date instanceof Carbon ? $date : Carbon::parse($date ?? now());

        return sprintf('TKT-%s-%05d', $ticketDate->format('Ymd'), $id);
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_URGENT,
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW_BY_PROVINCE,
            self::STATUS_RESOLVED_BY_PROVINCE,
            self::STATUS_ESCALATED_TO_REGION,
            self::STATUS_UNDER_REVIEW_BY_REGION,
            self::STATUS_RESOLVED_BY_REGION,
            self::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
            self::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
            self::STATUS_CLOSED,
        ];
    }

    public static function levelLabels(): array
    {
        return [
            self::LEVEL_PROVINCIAL => 'Provincial',
            self::LEVEL_REGIONAL => 'Regional',
            self::LEVEL_CENTRAL_OFFICE => 'Central Office',
        ];
    }

    public static function statusColorMap(): array
    {
        return [
            self::STATUS_SUBMITTED => '#1d4ed8',
            self::STATUS_UNDER_REVIEW_BY_PROVINCE => '#7c3aed',
            self::STATUS_RESOLVED_BY_PROVINCE => '#15803d',
            self::STATUS_ESCALATED_TO_REGION => '#b45309',
            self::STATUS_UNDER_REVIEW_BY_REGION => '#0f766e',
            self::STATUS_RESOLVED_BY_REGION => '#047857',
            self::STATUS_FORWARDED_TO_CENTRAL_OFFICE => '#be123c',
            self::STATUS_RESOLVED_BY_CENTRAL_OFFICE => '#166534',
            self::STATUS_CLOSED => '#475569',
        ];
    }

    public static function priorityColorMap(): array
    {
        return [
            self::PRIORITY_LOW => '#475569',
            self::PRIORITY_MEDIUM => '#1d4ed8',
            self::PRIORITY_HIGH => '#b45309',
            self::PRIORITY_URGENT => '#b91c1c',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by', 'idno');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to', 'idno');
    }

    public function escalator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by', 'idno');
    }

    public function forwarder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'forwarded_by', 'idno');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by', 'idno');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->latest();
    }

    public function getStatusColorAttribute(): string
    {
        return self::statusColorMap()[$this->status] ?? '#334155';
    }

    public function getPriorityColorAttribute(): string
    {
        return self::priorityColorMap()[$this->priority] ?? '#334155';
    }

    public function getCurrentLevelLabelAttribute(): string
    {
        return self::levelLabels()[$this->current_level] ?? ucwords(str_replace('_', ' ', (string) $this->current_level));
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($search): void {
            $builder->where('ticket_number', 'like', '%' . $search . '%')
                ->orWhere('title', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%')
                ->orWhere('contact_information', 'like', '%' . $search . '%');
        });
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isLguUser()) {
            return $query->where('submitted_by', $user->getKey());
        }

        if ($user->isProvincialUser()) {
            return $query->whereRaw('LOWER(TRIM(COALESCE(province_scope, ""))) = ?', [$user->normalizedProvince()]);
        }

        if ($user->isRegionalUser()) {
            $normalizedRegion = $user->normalizedRegionComparable();

            return $query
                ->whereRaw('LOWER(TRIM(SUBSTRING_INDEX(COALESCE(region_scope, ""), "(", 1))) = ?', [$normalizedRegion])
                ->where(function (Builder $builder): void {
                    $builder->whereIn('current_level', [
                        self::LEVEL_REGIONAL,
                        self::LEVEL_CENTRAL_OFFICE,
                    ])->orWhereIn('status', [
                        self::STATUS_ESCALATED_TO_REGION,
                        self::STATUS_UNDER_REVIEW_BY_REGION,
                        self::STATUS_RESOLVED_BY_REGION,
                        self::STATUS_FORWARDED_TO_CENTRAL_OFFICE,
                        self::STATUS_RESOLVED_BY_CENTRAL_OFFICE,
                        self::STATUS_CLOSED,
                    ]);
                });
        }

        return $query->whereRaw('1 = 0');
    }
}
