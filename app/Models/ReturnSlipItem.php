<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnSlipItem extends Model
{
    protected $table = 'return_slip_items';

    protected $fillable = [
        'return_slip_id',
        'enduser_property_id',
        'property_no_generated',
        'item_name',
        'serial_number',
        'current_office_id',
        'current_location_id',
        'current_status',
        'previous_remarks',
        'status',
        'action_type',
        'transferred_to_enduser_id',
        'transferred_to_enduser_name',
        'transferred_to_office_id',
        'transferred_to_location_id',
        'actioned_by',
        'actioned_at',
        'confirmed_by',
        'confirmed_at',
        'cancelled_by',
        'cancelled_at',
        'action_remarks',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'actioned_at' => 'datetime',
    ];

    /**
     * Item is still sitting with Supply and can be transferred or tagged unserviceable.
     */
    public function isAwaitingAction(): bool
    {
        return in_array($this->status, ['Returned', 'Pending', ''], true) || $this->status === null;
    }

    public function slip()
    {
        return $this->belongsTo(ReturnSlip::class, 'return_slip_id');
    }

    public function logs()
    {
        return $this->hasMany(ReturnSlipLog::class, 'return_slip_item_id');
    }

    public function property()
    {
        return $this->belongsTo(EnduserProperty::class, 'enduser_property_id');
    }

    public function currentOffice()
    {
        return $this->belongsTo(Office::class, 'current_office_id');
    }

    public function currentLocation()
    {
        return $this->belongsTo(Office::class, 'current_location_id');
    }

    public function transferredToEnduser()
    {
        return $this->belongsTo(Accountable::class, 'transferred_to_enduser_id');
    }

    public function transferredToOffice()
    {
        return $this->belongsTo(Office::class, 'transferred_to_office_id');
    }

    public function actioner()
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
