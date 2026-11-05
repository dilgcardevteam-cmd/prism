<?php

namespace App\Modules\ReportorialRequirements\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PisatDocument extends Model
{
    protected $table = 'pisat_documents';

    protected $fillable = [
        'project_code',
        'project_title',
        'province',
        'city_municipality',
        'file_name',
        'file_path',
        'uploaded_by',
        'office',
    ];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'idno');
    }
}