<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfirmationOfFundReceiptDocument extends Model
{
    protected $table = 'tblconfirmation_of_fund_receipt_documents';

    protected $fillable = [
        'nadai_document_id',
        'office',
        'province',
        'project_title',
        'confirmation_date',
        'original_filename',
        'file_path',
        'uploaded_by',
        'uploaded_at',
        'accepted_at',
        'accepted_by',
    ];

    protected $casts = [
        'confirmation_date' => 'date',
        'uploaded_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];
}
