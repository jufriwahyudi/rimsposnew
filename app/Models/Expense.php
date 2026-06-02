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
        'description',
        'payment_method',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount'           => 'decimal:2',
    ];

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
}
