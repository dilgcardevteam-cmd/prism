<?php

namespace App\Modules\ReportorialRequirements\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LpmcDocumentFileWorkflowLog extends Model
{
    protected $table = 'tblpmc_document_file_workflow_logs';

    protected $fillable = [
        'lpmc_document_file_id',
        'actor_id',
        'action',
        'previous_status',
        'resulting_status',
        'remarks',
        'previous_file_path',
        'previous_filename',
    ];

    public function file()
    {
        return $this->belongsTo(LpmcDocumentFile::class, 'lpmc_document_file_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id', 'idno');
    }
}
