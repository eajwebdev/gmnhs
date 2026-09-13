<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearlyInventory extends Model
{
    protected $table = 'yearly_inventories';
    protected $fillable = [
        'inv_status',	
    ];
}
