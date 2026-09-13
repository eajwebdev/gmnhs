<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryHistory extends Model
{
    protected $table = 'inventory_histories';
    protected $fillable = [
        'uid',
        'prop_id',
        'inv_id',
        'office_id',
        'accnt_type',
        'person_accnt',
        'remarks',
        'item_status',
        'inv_status',
    ];
}
