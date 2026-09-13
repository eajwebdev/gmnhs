<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repair extends Model
{
    use HasFactory;
    protected $table = 'repairs';
    protected $fillable = [
        'uid',
        'prop_id',
        'findings',
        'urgency',
        'diagnosis', 
        'diagnose_by',
        'repair_status',
        'date_diagnose',
        'release_by',
        'release_date',
    ];

    protected $casts = [
        'date_diagnose' => 'datetime',
        'release_date'  => 'datetime',
    ];

}

