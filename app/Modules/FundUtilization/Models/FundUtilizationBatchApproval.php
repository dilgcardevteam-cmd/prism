<?php

namespace App\Modules\FundUtilization\Models;

use App\Models\LocallyFundedProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class FundUtilizationBatchApproval extends Model
{
    protected $table = 'fund_utilization_batch_approvals';

    protected $fillable = [
        'batch_code',
        'quarter',
        'year',
        'uploader_id',
        'uploader_role',
        'uploader_province',
        'uploader_office',
        'project_codes',
        'project_count',
        'documents_manifest',
        'current_approval_level',
        'last_approved_level',
        'returned_from_level',
        'current_approver_id',
        'current_approver_role',
        'status',
        'approval_remarks',
        'revision_number',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'project_codes' => 'array',
        'documents_manifest' => 'array',
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'year' => 'integer',
        'project_count' => 'integer',
        'current_approval_level' => 'integer',
        'last_approved_level' => 'integer',
        'returned_from_level' => 'integer',
        'revision_number' => 'integer',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id', 'idno');
    }

    public function currentApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_approver_id', 'idno');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FundUtilizationBatchApprovalLog::class, 'batch_id')->orderBy('created_at', 'asc');
    }

    public function isPendingLevel1(): bool
    {
        return (int) $this->current_approval_level === 1 && str_starts_with((string) $this->status, 'Pending');
    }

    public function isPendingLevel2(): bool
    {
        return (int) $this->current_approval_level === 2 && str_starts_with((string) $this->status, 'Pending');
    }

    public function isApproved(): bool
    {
        return $this->status === 'Approved' && $this->completed_at !== null;
    }

    public function isReturned(): bool
    {
        return str_starts_with((string) $this->status, 'Returned');
    }

    /**
     * Retrieve the project instances for this batch.
     */
    public function getProjects()
    {
        $codes = (array) ($this->project_codes ?? []);
        if (empty($codes)) {
            return collect();
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('subay_project_profiles')) {
            $profiles = DB::table('subay_project_profiles')->whereIn('project_code', $codes)->get();
            if ($profiles->isNotEmpty()) {
                return $profiles;
            }
        }

        return LocallyFundedProject::whereIn('subaybayan_project_code', $codes)->get();
    }

    /**
     * Generate an atomic, unique batch approval code.
     * Format: FUR-BATCH-{YEAR}-{QUARTER}-{0001}
     */
    public static function generateBatchCode(int $year, string $quarter): string
    {
        $normalizedQuarter = strtoupper(trim($quarter));
        $prefix = "FUR-BATCH-{$year}-{$normalizedQuarter}-";

        return DB::transaction(function () use ($year, $normalizedQuarter, $prefix) {
            $latest = self::where('year', $year)
                ->where('quarter', $normalizedQuarter)
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;
            if ($latest && preg_match('/-(\d+)$/', (string) $latest->batch_code, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            } else {
                $count = self::where('year', $year)
                    ->where('quarter', $normalizedQuarter)
                    ->count();
                $sequence = $count + 1;
            }

            $candidate = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            // Double check collision
            while (self::where('batch_code', $candidate)->exists()) {
                $sequence++;
                $candidate = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            }

            return $candidate;
        });
    }
}
