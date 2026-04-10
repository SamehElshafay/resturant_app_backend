<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantTable extends Model
{
    protected $fillable = ['zone_id', 'number', 'status', 'active_order_id'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'table_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    public function activeOrder()
    {
        return $this->belongsTo(Order::class, 'active_order_id');
    }
}
