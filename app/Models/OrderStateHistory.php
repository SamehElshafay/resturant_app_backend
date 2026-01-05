<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStateHistory extends Model
{
    use HasFactory;

    protected $table = 'order_state_history';

    protected $fillable = [
        'order_id',
        'state_id',
        'changed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'state_id' => 'integer',
        'changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}