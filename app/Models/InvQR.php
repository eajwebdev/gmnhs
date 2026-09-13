<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvQR extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'inv_id',
        'accnt_type',
        'person_accnt',
        'remarks',
        'comment',
    ];
}
