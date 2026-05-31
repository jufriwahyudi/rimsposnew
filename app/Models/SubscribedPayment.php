<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscribedPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscribed_invoice_id',
        'payment_date',
        'amount',
        'payment_method',
        'payment_proof',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function invoice()
    {
        return $this->belongsTo(SubscribedInvoice::class, 'subscribed_invoice_id');
    }
}
