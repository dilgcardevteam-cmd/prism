<?php

namespace App\Modules\FundUtilization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FURUploadBatch extends Model
{
    protected $table = 'tbfur_upload_batches';

    protected $fillable = [
        'batch_number',
        'quarter',
        'uploaded_by',
        'project_count',
        'item_count',
        'status',
        'submitted_at',
        'completed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function projects()
    {
        return $this->hasMany(FURUploadBatchProject::class, 'batch_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'idno');
    }
}
