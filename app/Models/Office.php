<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;
    protected $table = 'offices';

    protected $fillable = [
        'office_code',
        'office_name',
        'office_abbr',
        'office_officer',
        'school_id'
    ];

}

