<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokTransferJurnal extends Model
{
    protected $table = 'stok_transfer_jurnals';

    protected $fillable = [
        'stock_transfer_id',
        'nojurnal',
        'jumlah',
    ];

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'nojurnal');
    }
}
