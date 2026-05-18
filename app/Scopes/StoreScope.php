<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Support\Tenant;

class StoreScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // Jika tenant telah diset, filter query berdasarkan store
        if (Tenant::get()) {
            $builder->where($model->getTable() . '.store_id', Tenant::get());
        }
    }
}
