<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundUtilizationReport extends Model
{
    protected $table = 'tbfur';
    protected $primaryKey = 'project_code';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_code',
        'province',
        'implementing_unit',
        'barangay',
        'project_title',
        'funding_source',
        'allocation',
        'contract_amount',
        'project_status',
        'fund_source',
        'funding_year',
    ];

    public function movUploads()
    {
        return $this->hasMany(FURMovUpload::class, 'project_code', 'project_code');
    }

    public function writtenNotices()
    {
        return $this->hasMany(FURWrittenNotice::class, 'project_code', 'project_code');
    }

    public function fdpDocuments()
    {
        return $this->hasMany(FURFDP::class, 'project_code', 'project_code');
    }

    public function adminRemarks()
    {
        return $this->hasMany(FURAdminRemark::class, 'project_code', 'project_code');
    }
}
