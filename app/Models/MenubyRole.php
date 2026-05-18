<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenubyRole extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'menuby_role';
    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'menu_id',
    ];

    // public function role()
    // {
    //     return $this->hasMany(RoleMaster::class, 'role_id', 'id');
    // }
    public function role()
    {
        return $this->belongsTo(RoleMaster::class, 'role_id', 'id');
    }


    // public function menus()
    // {
    //     return $this->hasMany(MenuList::class, 'menu_id', 'id');
    // }
    public function menu()
    {
        return $this->belongsTo(MenuList::class, 'menu_id', 'id');
    }
}
