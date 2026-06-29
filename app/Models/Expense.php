<?php

namespace App\Models;

use App\Models\Traits\HasStore;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasStore;

    protected $table = 'expenses';

    protected $fillable = [
        'store_id',
        'expense_category_id',
        'transaction_date',
        'amount',
        'paid_amount',
        'payment_status',
        'description',
        'payment_method',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
        'paid_amount'      => 'decimal:2',
    ];

    public function getRemainingAmountAttribute()
    {
        return max(0, $this->amount - $this->paid_amount);
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payments()
    {
        return $this->hasMany(ExpensePayment::class, 'expense_id');
    }
}
