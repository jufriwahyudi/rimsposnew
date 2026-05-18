<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    use HasFactory;
    // Model ini fungsinya menghubungkan iduser ke role apa saja yang diberikan
    protected $connection = 'mysql';
    protected $table = 'role_user';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'role_id'
    ];

    public function roles()
    {
        return $this->belongsTo(RoleMaster::class, 'role_id', 'id');
    }
    public function pengguna()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
