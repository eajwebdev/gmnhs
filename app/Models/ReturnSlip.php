<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnSlip extends Model
{
    protected $table = 'return_slips';

    protected $fillable = [
        'user_id',
        'requested_by',
        'school_id',
        'returned_by_id',
        'returned_by_name',
        'received_by',
        'returned_at',
        'request_type',
        'target_office_id',
        'reason',
        'status',
        'confirmed_by',
        'confirmed_at',
        'remarks',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(ReturnSlipItem::class, 'return_slip_id');
    }

    /**
     * A return slip now covers exactly one property, recorded one item at a time.
     */
    public function item()
    {
        return $this->hasOne(ReturnSlipItem::class, 'return_slip_id');
    }

    public function logs()
    {
        return $this->hasMany(ReturnSlipLog::class, 'return_slip_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(Accountable::class, 'returned_by_id');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function targetOffice()
    {
        return $this->belongsTo(Office::class, 'target_office_id');
    }
}

