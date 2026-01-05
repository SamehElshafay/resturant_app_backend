<?php

namespace App\Models\CustomerModel;

use App\Models\CouponModels\Coupons;
use App\Services\CouponServices;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'customer_id',
        'commercial_id',
        'delivery_price',
        'services',
        'coupon_id',
        'created_at',
        'updated_at',
    ];

    protected $with = ['cart_items'];
    protected $appends = ['total_price' , 'total_discount'];

    protected $casts = [
        'user_id' => 'integer',
        'delivery_price' => 'decimal:2',
        'commercial_id' => 'integer',
        'coupon_id' => 'integer',
        'discount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function cart_items() {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    public function getTotalPriceAttribute() {
        return $this->cart_items->sum('total_price');
    }

    public function getTotalDiscountAttribute() {
        if($this->coupon_id != null){
            $couponService = new CouponServices();
            $coupon = Coupons::findOrFail($this->coupon_id);
            return $this->total_discount = $couponService->calculateDiscount($coupon , $this->total_price , $this->id) * 1.0;
        }
        return null;
    }
}