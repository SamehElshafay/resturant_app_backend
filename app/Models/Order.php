<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AutoCastTypes;

class Order extends Model
{
    use AutoCastTypes;
    protected $fillable = [
        'branch_id',
        'cashier_id',
        'driver_id',
        'pos_id',
        'table_id',
        'order_number',
        'is_offline',
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

    protected $casts = [
        'id' => 'integer',
        'branch_id' => 'integer',
        'cashier_id' => 'integer',
        'driver_id' => 'integer',
        'pos_id' => 'integer',
        'table_id' => 'integer',
        'daily_number' => 'integer',
        'is_offline' => 'boolean',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
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
