<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FURAdminRemark extends Model
{
    protected $table = 'tbfur_admin_remarks';
    
    protected $fillable = [
        'project_code',
        'quarter',
        'remarks',
        'admin_id',
    ];
}
