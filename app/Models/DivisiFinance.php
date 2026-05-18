<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DivisiFinance extends Model
{
    use HasFactory;
    protected $connection = 'financedb';
    protected $table = 'master_divisi';
}
