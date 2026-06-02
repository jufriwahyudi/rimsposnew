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
        'customer_name',
        'customer_phone',
        'receipt_name',
        'user_id',
        'subtotal',
        'discount_total',
        'trans_discount',
        'tax_total',
        'grand_total',
        'paid_amount',
        'change_amount',
        'nojurnal',
        'status',
        'payment_status',
        'has_exchange',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
    ];

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
    public function biodata()
    {
        return $this->belongsTo(NseCalonSiswa::class, 'customer_id', 'id_biodatadiri');
    }
}
