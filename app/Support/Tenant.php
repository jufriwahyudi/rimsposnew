<?php

namespace App\Support;

class Tenant
{
    protected static $storeId = null;

    public static function set($storeId)
    {
        static::$storeId = $storeId;
    }

    public static function get()
    {
        return static::$storeId;
    }

    public static function clear()
    {
        static::$storeId = null;
    }
}
