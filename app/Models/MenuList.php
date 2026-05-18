<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuList extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'menu_list';

    protected $fillable = [
        'nama',
        'routename',
        'icon',
        'id_parent',
        'jnsmenu',
        'urutan',
        'stts',
    ];
    public function children()
    {
        return $this->hasMany(MenuList::class, 'id_parent')->orderBy('urutan', 'asc');
    }
    public function roles()
    {
        return $this->belongsToMany(RoleMaster::class, 'menuby_role', 'menu_id', 'role_id');
    }
}
