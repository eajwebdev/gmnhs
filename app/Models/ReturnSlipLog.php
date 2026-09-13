<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnSlipLog extends Model
{
    protected $table = 'return_slip_logs';

    protected $fillable = [
        'return_slip_id',
        'return_slip_item_id',
        'enduser_property_id',
        'property_no_generated',
        'user_id',
        'user_name',
        'user_role',
        'action',
        'description',
        'from_value',
        'to_value',
        'remarks',
    ];

    public function slip()
    {
        return $this->belongsTo(ReturnSlip::class, 'return_slip_id');
    }

    public function item()
    {
        return $this->belongsTo(ReturnSlipItem::class, 'return_slip_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
