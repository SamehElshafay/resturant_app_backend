<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\AutoCastTypes;

class OrderItem extends Model
{
    use AutoCastTypes;
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'item_total'
    ];

    protected $casts = [
        'id' => 'integer',
        'order_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'decimal:3',
        'price' => 'decimal:2',
        'item_total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
