<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class DigitalNewspaper extends Model
{
    use HasStore;

    protected $table = 'digital_newspapers';

    protected $fillable = [
        'store_id',
        'report_date',
        'status',
        'headline',
        'content_html',
        'raw_payload',
        'generated_at',
        'error_message',
    ];

    protected $casts = [
        'report_date' => 'date',
        'raw_payload' => 'array',
        'generated_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
