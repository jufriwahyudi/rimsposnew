<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpensePayment extends Model
{
    protected $table = 'expense_payments';

    protected $fillable = [
        'expense_id',
        'payment_date',
        'amount',
        'payment_method',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount'       => 'decimal:2',
    ];

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
