<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $connection = 'mysql';
    protected $table = 'stock_transfers';
    protected $fillable = [
        'transfer_code',
        'from_position',
        'to_position',
        'transfer_type',
        'requested_by',
        'approved_by',
        'status',
        'request_date',
        'approve_date',
        'ship_date',
        'receive_date',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'datetime',
        'approve_date' => 'datetime',
        'ship_date' => 'datetime',
        'receive_date' => 'datetime',
    ];

    /* ================= RELATION ================= */

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function jurnal()
    {
        return $this->hasMany(StokTransferJurnal::class, 'stock_transfer_id');
    }

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class, 'stock_transfer_id');
    }

    /* ================= BUSINESS RULE ================= */

    public function isWarehouseToStore(): bool
    {
        return $this->from_position === 'warehouse'
            && $this->to_position === 'store';
    }

    public function isStoreToWarehouse(): bool
    {
        return $this->from_position === 'store'
            && $this->to_position === 'warehouse';
    }

    public function canApprove(): bool
    {
        return $this->status === 'REQUESTED';
    }

    public function canShip(): bool
    {
        return $this->status === 'APPROVED';
    }

    public function canReceive(): bool
    {
        return $this->status === 'IN_TRANSIT';
    }

    // attribut label transfer type
    public function getTransferTypeLabelAttribute(): string
    {
        return $this->transfer_type === 'REQUEST' ? 'Permintaan Stok' : 'Pengembalian Stok';
    }

    // atribut badge color untuk status
    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'REQUESTED' => 'warning',
            'APPROVED' => 'primary',
            'REJECTED' => 'danger',
            'IN_TRANSIT' => 'info',
            'PARTIAL_RECEIVED' => 'secondary',
            'RECEIVED' => 'success',
            'CANCELLED' => 'dark',
            default => 'secondary',
        };
    }
}
