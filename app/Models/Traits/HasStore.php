<?php

namespace App\Models\Traits;

use App\Scopes\StoreScope;
use App\Support\Tenant;

trait HasStore
{
    public static function bootHasStore()
    {
        // tambahkan global scope
        static::addGlobalScope(new StoreScope());

        // set store_id otomatis saat create
        static::creating(function ($model) {
            if (Tenant::get()) {
                $model->store_id = Tenant::get();
            }
        });
    }
}
