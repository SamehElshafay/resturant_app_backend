<?php

namespace App\Models\CouponModels;
use App\Models\OrdersModels\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupons extends Model
{
    use HasFactory;

    protected $table = 'coupons';

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'start_at',
        'expire_at',
        'active',
    ];

    protected $casts = [
        'value'            => 'float',
        'min_order_amount' => 'float',
        'max_discount'     => 'float',
        'usage_limit'      => 'integer',
        'usage_per_user'   => 'integer',
        'used_count'       => 'integer',
        'active'           => 'boolean',
        'start_at'         => 'datetime',
        'expire_at'        => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    public function usages(){
        return $this->hasMany(CouponUsage::class, 'coupon_id');
    }

    public function customers(){
        return $this->belongsToMany(
            \App\Models\CustomerModel\Customer::class,
            'coupon_usages',
            'coupon_id',
            'customer_id'
        )->withPivot(['order_id', 'used_at']);
    }

    public function orders(){
        return $this->belongsToMany(
            Order::class,
            'coupon_usages',
            'coupon_id',
            'order_id'
        )->withPivot(['customer_id', 'used_at']);
    }
}