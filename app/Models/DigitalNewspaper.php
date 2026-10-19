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
        'ai_provider',
        'headline',
        'content_html',
        'sections_json',
        'raw_payload',
        'generated_at',
        'error_message',
    ];

    protected $casts = [
        'report_date' => 'date',
        'raw_payload' => 'array',
        'sections_json' => 'array',
        'generated_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
