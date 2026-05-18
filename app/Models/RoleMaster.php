<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleMaster extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'role_master';

    protected $fillable = [
        'nama',
        'role_type',
        'can_access_all_divisi',
        'stts'
    ];

    public function menus()
    {
        return $this->belongsToMany(MenuList::class, 'menuby_role', 'role_id', 'menu_id');
    }
    public function users()
    {
        return $this->hasMany(RoleUser::class, 'role_id', 'id');
    }
}
