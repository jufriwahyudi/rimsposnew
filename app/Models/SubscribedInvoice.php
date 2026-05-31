<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscribedInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'invoice_number',
        'billing_amount',
        'period_start',
        'period_end',
        'due_date',
        'status',
    ];

    protected $casts = [
        'period_start'   => 'date',
        'period_end'     => 'date',
        'due_date'       => 'date',
        'billing_amount' => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function payments()
    {
        return $this->hasMany(SubscribedPayment::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Total amount paid for this invoice.
     */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Remaining balance on this invoice.
     */
    public function getRemainingBalanceAttribute(): float
    {
        return max(0, (float) $this->billing_amount - $this->total_paid);
    }

    /**
     * Generate auto-increment invoice number: INV-SUB-YYYY-NNNN
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $prefix = "INV-SUB-{$year}-";

        $lastInvoice = static::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->first();

        if ($lastInvoice) {
            $lastSeq = (int) str_replace($prefix, '', $lastInvoice->invoice_number);
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
