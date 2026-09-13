<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnduserPropertyTest extends Model
{
    protected $table = 'enduser_property_test';
    protected $fillable = [
        'purch_id',
        'property_id',	
        'categories_id',
        'properties_id',	
        'office_id',
        'location',
        'item_id',	
        'item_descrip',	
        'item_model',
        'description',	
        'item_number',	
        'serial_number',
        'unit_id',	
        'item_cost',	
        'qty',	
        'total_cost',	
        'property_no_generated',
        'selected_account_id',
        'status',	
        'remarks',
        'date_acquired',	
        'date_stat',
        'price_stat',
        'person_accnt',
        'person_accnt1',
        'serial_owned',
        'person_accnt_name',
        'print_stat'
    ];
}
