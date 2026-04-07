<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketCategory extends Model
{
    use HasFactory;

    public const NAME_OTHERS = 'Others';

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    public static function defaultCategories(): array
    {
        return [
            [
                'name' => 'System Issue',
                'description' => 'Errors, bugs, or parts of the system that are not working as expected.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Account Concern',
                'description' => 'Login, password, account access, or permission-related problems.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Data Concern',
                'description' => 'Wrong, missing, inconsistent, or duplicate data concerns.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Process Inquiry',
                'description' => 'Questions about procedures, workflow steps, or business processes.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Report Issue',
                'description' => 'Problems encountered in reports, generated outputs, or report views.',
                'sort_order' => 5,
            ],
            [
                'name' => 'Request / Enhancement',
                'description' => 'Requests for new features, improvements, or system enhancements.',
                'sort_order' => 6,
            ],
            [
                'name' => 'Training / Help',
                'description' => 'Guidance on how to use the system or complete specific tasks.',
                'sort_order' => 7,
            ],
            [
                'name' => self::NAME_OTHERS,
                'description' => 'Anything not covered by the other ticket categories.',
                'sort_order' => 8,
            ],
        ];
    }

    public function isOthers(): bool
    {
        return strcasecmp((string) $this->name, self::NAME_OTHERS) === 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
