<?php

namespace App\Models\OrdersModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderState extends Model
{
    use HasFactory;

    protected $table = 'order_state';

    protected $fillable = [
        'state_id',
        'order_id',
        'note',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'state_id' => 'integer',
        'order_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['state'];

    public function state() {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function order() {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // العلاقة مع Order
    /*public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }*/
}