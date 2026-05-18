<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyAuditDetail extends Model
{
    protected $fillable = [
        'daily_audit_id',
        'reference_type',
        'reference_id',
        'issue_type',
        'description',
        'expected_value',
        'actual_value'
    ];

    public function audit()
    {
        return $this->belongsTo(DailyAudit::class);
    }
}
