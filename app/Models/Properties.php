<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Properties extends Model
{
    protected $table = 'properties';
    protected $fillable = [
        'property_id',
        'category_id',      
        'account_number',  
        'code',
        'account_title',
        'account_title_abbr',
    ];

}
