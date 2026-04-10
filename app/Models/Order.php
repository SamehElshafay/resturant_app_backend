<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'branch_id',
        'cashier_id',
        'driver_id',
        'pos_id',
        'table_id',
        'daily_number',
        'type',
        'status',
        'total_amount',
        'paid_amount',
        'discount',
        'tax',
        'service_charge',
        'delivery_fee',
        'notes'
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function pos()
    {
        return $this->belongsTo(PosDevice::class, 'pos_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
