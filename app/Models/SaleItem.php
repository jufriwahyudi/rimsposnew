<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $connection = 'mysql';
    protected $table = 'sale_items';
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_variant_id',
        'sku',
        'product_name',
        'price',
        'qty',
        'discount_amount',
        'subtotal',
        'status',
        'ref_sale_item_id',
        
        // Virtual/Delegated fields for FnB details
        'kitchen_printed_qty',
        'kds_status',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'cost_price',
    ];

    protected $fnbDetailData = [];

    protected static function booted()
    {
        static::saved(function ($model) {
            $model->saveFnBDetail();
        });
    }

    public function fnbDetail()
    {
        return $this->hasOne(SaleItemFnBDetail::class, 'sale_item_id');
    }

    public function getKdsStatusAttribute()
    {
        return $this->fnbDetail ? $this->fnbDetail->kds_status : 'pending';
    }

    public function setKdsStatusAttribute($value)
    {
        if ($this->exists) {
            $this->fnbDetail()->updateOrCreate([], ['kds_status' => $value]);
            $this->unsetRelation('fnbDetail');
        } else {
            $this->fnbDetailData['kds_status'] = $value;
        }
    }

    public function getKitchenPrintedQtyAttribute()
    {
        return $this->fnbDetail ? $this->fnbDetail->kitchen_printed_qty : 0;
    }

    public function setKitchenPrintedQtyAttribute($value)
    {
        if ($this->exists) {
            $this->fnbDetail()->updateOrCreate([], ['kitchen_printed_qty' => $value]);
            $this->unsetRelation('fnbDetail');
        } else {
            $this->fnbDetailData['kitchen_printed_qty'] = $value;
        }
    }

    public function getCommissionTypeAttribute()
    {
        return $this->fnbDetail ? $this->fnbDetail->commission_type : null;
    }

    public function setCommissionTypeAttribute($value)
    {
        if ($this->exists) {
            $this->fnbDetail()->updateOrCreate([], ['commission_type' => $value]);
            $this->unsetRelation('fnbDetail');
        } else {
            $this->fnbDetailData['commission_type'] = $value;
        }
    }

    public function getCommissionRateAttribute()
    {
        return $this->fnbDetail ? $this->fnbDetail->commission_rate : 0.00;
    }

    public function setCommissionRateAttribute($value)
    {
        if ($this->exists) {
            $this->fnbDetail()->updateOrCreate([], ['commission_rate' => $value]);
            $this->unsetRelation('fnbDetail');
        } else {
            $this->fnbDetailData['commission_rate'] = $value;
        }
    }

    public function getCommissionAmountAttribute()
    {
        return $this->fnbDetail ? $this->fnbDetail->commission_amount : 0.00;
    }

    public function setCommissionAmountAttribute($value)
    {
        if ($this->exists) {
            $this->fnbDetail()->updateOrCreate([], ['commission_amount' => $value]);
            $this->unsetRelation('fnbDetail');
        } else {
            $this->fnbDetailData['commission_amount'] = $value;
        }
    }

    public function getCostPriceAttribute()
    {
        return $this->fnbDetail ? $this->fnbDetail->cost_price : 0.00;
    }

    public function setCostPriceAttribute($value)
    {
        if ($this->exists) {
            $this->fnbDetail()->updateOrCreate([], ['cost_price' => $value]);
            $this->unsetRelation('fnbDetail');
        } else {
            $this->fnbDetailData['cost_price'] = $value;
        }
    }

    public function saveFnBDetail()
    {
        $storeId = \App\Support\Tenant::get();
        if (!$storeId && $this->sale_id) {
            $sale = \App\Models\Sale::find($this->sale_id);
            if ($sale) {
                $storeId = $sale->store_id;
            }
        }

        if ($storeId) {
            $store = \App\Models\Store::find($storeId);
            if ($store && $store->business_type === 'fnb') {
                $data = [
                    'kds_status' => $this->fnbDetailData['kds_status'] ?? ($this->fnbDetail?->kds_status ?? 'pending'),
                    'kitchen_printed_qty' => $this->fnbDetailData['kitchen_printed_qty'] ?? ($this->fnbDetail?->kitchen_printed_qty ?? 0),
                    'commission_type' => $this->fnbDetailData['commission_type'] ?? ($this->fnbDetail?->commission_type ?? null),
                    'commission_rate' => $this->fnbDetailData['commission_rate'] ?? ($this->fnbDetail?->commission_rate ?? 0),
                    'commission_amount' => $this->fnbDetailData['commission_amount'] ?? ($this->fnbDetail?->commission_amount ?? 0),
                    'cost_price' => $this->fnbDetailData['cost_price'] ?? ($this->fnbDetail?->cost_price ?? 0),
                ];

                // If cost_price wasn't set, look it up from variant
                if (!isset($this->fnbDetailData['cost_price']) && (!$this->fnbDetail || !$this->fnbDetail->cost_price)) {
                    if ($this->product_variant_id) {
                        $variant = \App\Models\ProductVariant::find($this->product_variant_id);
                        $data['cost_price'] = $variant->cost_price_manual ?? 0;
                    }
                }

                // If commission fields weren't set, calculate them
                if (!isset($this->fnbDetailData['commission_amount']) && (!$this->fnbDetail || !$this->fnbDetail->commission_amount)) {
                    $tenantId = null;
                    $commissionType = null;
                    $commissionRate = 0;
                    $commissionAmount = 0;

                    if ($this->product_variant_id) {
                        $variant = \App\Models\ProductVariant::with('product.tenant')->find($this->product_variant_id);
                        if ($variant && $variant->product && $variant->product->tenant_id) {
                            $tenantId = $variant->product->tenant_id;
                            $commissionType = $variant->commission_type ?? 'global';
                            
                            if ($commissionType === 'percentage') {
                                $commissionRate = $variant->commission_rate;
                                $commissionAmount = $this->price * ($commissionRate / 100);
                            } elseif ($commissionType === 'nominal') {
                                $commissionRate = $variant->commission_rate;
                                $commissionAmount = $commissionRate;
                            } else {
                                // global
                                $commissionRate = $variant->product->tenant->commission_rate ?? 0;
                                $commissionAmount = $this->price * ($commissionRate / 100);
                            }
                        }
                    } elseif ($this->product_id) {
                        $product = \App\Models\Product::with('tenant')->find($this->product_id);
                        if ($product && $product->tenant_id) {
                            $tenantId = $product->tenant_id;
                            $commissionType = 'global';
                            $commissionRate = $product->tenant->commission_rate ?? 0;
                            $commissionAmount = $this->price * ($commissionRate / 100);
                        }
                    }

                    if ($tenantId !== null) {
                        $data['commission_type'] = $commissionType;
                        $data['commission_rate'] = $commissionRate;
                        $data['commission_amount'] = $commissionAmount;
                    }
                }

                $this->fnbDetail()->updateOrCreate([], $data);
                $this->unsetRelation('fnbDetail');
            }
        }
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function batches()
    {
        return $this->hasMany(SaleItemBatch::class, 'sale_item_id');
    }
}
