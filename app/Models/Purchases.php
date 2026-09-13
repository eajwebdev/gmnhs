<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchases extends Model
{
    protected $table = 'purchases';
    protected $fillable = [
        'po_number',
        'property_id',	
        'categories_id',
        'properties_id',	
        'office_id',
        'item_id',	
        'item_descrip',	
        'item_model',
        'description',	
        'item_number',	
        'serial_number',
        'unit_id',	
        'item_cost',	
        'qty',	
        'qty_release',
        'total_cost',	
        'property_no_generated',
        'selected_account_id',
        'status',	
        'remarks',
        'date_acquired',	
        'date_stat',
        'price_stat',
        'person_accnt',
        'person_accnt_name',
        'print_stat',
        'supply_type'
    ];
    
}
