<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasStore;

    protected $connection = 'mysql';
    protected $table = 'sales';
    protected $fillable = [
        'store_id',
        'ref_sale_id',
        'invoice_number',
        'table_number',
        'sale_date',
        'sale_type',
        'customer_id',
        'member_id',
        'customer_name',
        'customer_phone',
        'receipt_name',
        'user_id',
        'sales_person_id',
        'cash_register_id',
        'subtotal',
        'discount_total',
        'trans_discount',
        'tax_total',
        'grand_total',
        'points_earned',
        'points_redeemed',
        'point_discount_amount',
        'paid_amount',
        'change_amount',
        'tip_amount',
        'nojurnal',
        'status',
        'payment_status',
        'has_exchange',
        'voucher_code',
        'voucher_discount_amount',
        'kitchen_printed_at',
        'bar_printed_at',
        'printed_stations_log',

        // Farmasi Resep Dokter & Pasien
        'doctor_name',
        'doctor_sip',
        'patient_name',
        'patient_age',
        'patient_gender',
        'patient_phone',
        'prescription_number',
        'prescription_date',
        'total_tuslah',
        'total_embalase',

        // Diskon Promosi
        'discount_id',
        'discount_name',
    ];

    protected $casts = [
        'sale_date'            => 'datetime',
        'prescription_date'    => 'date',
        'kitchen_printed_at'   => 'datetime',
        'bar_printed_at'       => 'datetime',
        'printed_stations_log' => 'array',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function pointHistories()
    {
        return $this->hasMany(MemberPointHistory::class);
    }

    protected static function booted()
    {
        static::saved(function ($sale) {
            try {
                // Only sync self-service orders (invoice starts with 'QR-')
                if (!str_starts_with($sale->invoice_number, 'QR-')) {
                    return;
                }

                $store = $sale->store;
                if ($store && $store->business_type === 'fnb' && $store->addon_self_service) {
                    app(\App\Services\FirestoreService::class)->syncOrder($sale);
                }
            } catch (\Throwable $e) {
                \Log::error("Failed to sync sale #{$sale->id} to Firestore: " . $e->getMessage());
            }
        });
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salesPerson()
    {
        return $this->belongsTo(SalesPerson::class, 'sales_person_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function refundOf()
    {
        return $this->belongsTo(Sale::class, 'ref_sale_id');
    }

    public function refunds()
    {
        return $this->hasMany(Sale::class, 'ref_sale_id');
    }

    public function payments()
    {
        return $this->hasMany(CashTransaction::class, 'ref_id')->where('transaction_type', 'sale')->where('direction', 'in');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function biodata()
    {
        return $this->belongsTo(NseCalonSiswa::class, 'customer_id', 'id_biodatadiri');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }
}
