<?php

namespace App\Models\CouponModels;

use App\Models\CustomerModel\Customer;
use App\Models\OrdersModels\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    use HasFactory;

    protected $table = 'coupon_usages';

    public $timestamps = false;

    protected $fillable = [
        'coupon_id',
        'customer_id',
        'order_id',
        'used_at',
    ];

    protected $casts = [
        'coupon_id' => 'integer',
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'used_at' => 'datetime',
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupons::class, 'coupon_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}