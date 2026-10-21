<?php

namespace App\Modules\FundUtilization\Models;

use Illuminate\Database\Eloquent\Model;

class FURUploadBatchProject extends Model
{
    protected $table = 'tbfur_upload_batch_projects';

    protected $fillable = [
        'batch_id',
        'project_code',
        'items_json',
        'status',
        'selected_by',
        'selected_at',
        'distribution_status',
    ];

    protected $casts = [
        'items_json' => 'array',
        'selected_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(FURUploadBatch::class, 'batch_id');
    }

    public function documentReviews()
    {
        return $this->hasMany(FURUploadBatchDocumentReview::class, 'batch_project_id');
    }
}
