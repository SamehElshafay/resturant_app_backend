<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    protected $fillable = ['zone_id', 'number', 'status', 'active_order_id'];

    protected $casts = [
        'id' => 'integer',
        'zone_id' => 'integer',
        'active_order_id' => 'integer',
        'number' => 'integer',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function activeOrder()
    {
        return $this->belongsTo(Order::class, 'active_order_id');
    }
}
