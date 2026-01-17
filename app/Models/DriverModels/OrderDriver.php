<?php

namespace App\Models\DriverModels;

use App\Models\OrdersModels\Order;
use App\Models\OrdersModels\State;
use Illuminate\Database\Eloquent\Model;

class OrderDriver extends Model
{
    protected $table = 'order_driver';

    protected $fillable = [
        'order_id',
        'driver_id',
        'status_id',
    ];

    public $timestamps = true;

    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function driver() {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function status() {
        return $this->belongsTo(State::class, 'status_id');
    }
}