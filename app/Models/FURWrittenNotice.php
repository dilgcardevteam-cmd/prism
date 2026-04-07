<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FURWrittenNotice extends Model
{
    protected $table = 'tbfur_written_notice';
    
    protected $fillable = [
        'project_code',
        'quarter',
        'secretary_dbm_path',
        'secretary_dilg_path',
        'speaker_house_path',
        'president_senate_path',
        'house_committee_path',
        'senate_committee_path',
        'status',
        'approval_remarks',
        'approved_by',
        'approved_at',
        'approved_at_dilg_po',
        'approved_at_dilg_ro',
        'user_remarks',
        'encoder_id',
        // Individual document approval fields
        'dbm_status', 'dbm_approved_by', 'dbm_approved_at', 'dbm_approved_at_dilg_po', 'dbm_approved_at_dilg_ro', 'dbm_remarks', 'dbm_uploaded_at', 'dbm_encoder_id',
        'dbm_approved_by_dilg_po', 'dbm_approved_by_dilg_ro',
        'dilg_status', 'dilg_approved_by', 'dilg_approved_at', 'dilg_approved_at_dilg_po', 'dilg_approved_at_dilg_ro', 'dilg_remarks', 'dilg_uploaded_at', 'dilg_encoder_id',
        'dilg_approved_by_dilg_po', 'dilg_approved_by_dilg_ro',
        'speaker_status', 'speaker_approved_by', 'speaker_approved_at', 'speaker_approved_at_dilg_po', 'speaker_approved_at_dilg_ro', 'speaker_remarks', 'speaker_uploaded_at', 'speaker_encoder_id',
        'speaker_approved_by_dilg_po', 'speaker_approved_by_dilg_ro',
        'president_status', 'president_approved_by', 'president_approved_at', 'president_approved_at_dilg_po', 'president_approved_at_dilg_ro', 'president_remarks', 'president_uploaded_at', 'president_encoder_id',
        'president_approved_by_dilg_po', 'president_approved_by_dilg_ro',
        'house_status', 'house_approved_by', 'house_approved_at', 'house_approved_at_dilg_po', 'house_approved_at_dilg_ro', 'house_remarks', 'house_uploaded_at', 'house_encoder_id',
        'house_approved_by_dilg_po', 'house_approved_by_dilg_ro',
        'senate_status', 'senate_approved_by', 'senate_approved_at', 'senate_approved_at_dilg_po', 'senate_approved_at_dilg_ro', 'senate_remarks', 'senate_uploaded_at', 'senate_encoder_id',
        'senate_approved_by_dilg_po', 'senate_approved_by_dilg_ro',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'approved_at' => 'datetime',
        'approved_at_dilg_po' => 'datetime',
        'approved_at_dilg_ro' => 'datetime',
        'dbm_approved_at' => 'datetime',
        'dbm_approved_at_dilg_po' => 'datetime',
        'dbm_approved_at_dilg_ro' => 'datetime',
        'dbm_uploaded_at' => 'datetime',
        'dilg_approved_at' => 'datetime',
        'dilg_approved_at_dilg_po' => 'datetime',
        'dilg_approved_at_dilg_ro' => 'datetime',
        'dilg_uploaded_at' => 'datetime',
        'speaker_approved_at' => 'datetime',
        'speaker_approved_at_dilg_po' => 'datetime',
        'speaker_approved_at_dilg_ro' => 'datetime',
        'speaker_uploaded_at' => 'datetime',
        'president_approved_at' => 'datetime',
        'president_approved_at_dilg_po' => 'datetime',
        'president_approved_at_dilg_ro' => 'datetime',
        'president_uploaded_at' => 'datetime',
        'house_approved_at' => 'datetime',
        'house_approved_at_dilg_po' => 'datetime',
        'house_approved_at_dilg_ro' => 'datetime',
        'house_uploaded_at' => 'datetime',
        'senate_approved_at' => 'datetime',
        'senate_approved_at_dilg_po' => 'datetime',
        'senate_approved_at_dilg_ro' => 'datetime',
        'senate_uploaded_at' => 'datetime',
    ];
    
    public function encoder()
    {
        return $this->belongsTo(\App\Models\User::class, 'encoder_id', 'idno');
    }
    
    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by', 'idno');
    }
}
