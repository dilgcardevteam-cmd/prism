<?php

namespace App\Modules\FundUtilization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FURUploadBatchDocumentReview extends Model
{
    protected $table = 'tbfur_upload_batch_document_reviews';

    protected $fillable = [
        'batch_project_id',
        'document_type',
        'status',
        'remarks',
        'validated_by',
        'validation_level',
        'validation_token',
        'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function batchProject()
    {
        return $this->belongsTo(FURUploadBatchProject::class, 'batch_project_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by', 'idno');
    }
}
