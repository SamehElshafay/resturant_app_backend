<?php

namespace App\Services;

use App\Models\CouponModels\Coupons;
use App\Models\CouponModels\CouponUsage;
use App\Models\CustomerModel\Cart;
use App\Models\CustomerModel\Customer;
use App\Models\OrdersModels\Order;
use Exception;
use Illuminate\Support\Facades\DB;

class CouponServices { 
    public function validateCoupon(Coupons $coupon, $total , Customer $customer): void{
        if (! $coupon->active) {
            throw new Exception('Coupon not active');
        }

        if (now()->lt($coupon->start_at) || now()->gt($coupon->expire_at)) {
            throw new Exception('Coupon expired');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new Exception('Coupon usage limit reached');
        }

        if ($coupon->min_order_amount !== null && $total < $coupon->min_order_amount) {
            throw new Exception('Minimum order amount not reached');
        }

        if (! $this->canCustomerUseCoupon($coupon, $customer)) {
            throw new Exception('Customer usage limit reached');
        }
    }

    public function applyCoupon(Cart $cart, Coupons $coupon, Customer $customer): float {
        return DB::transaction(function () use ($cart , $coupon, $customer) {
            $total = $cart->total_price ;
            $this->validateCoupon($coupon, $total , $customer);

            $discount = $this->calculateDiscount($coupon, $total , $cart->id);

            $cart->update([
                'coupon_id' => $coupon->id ,
                'discount'  => $discount ,
            ]);

            CouponUsage::create([
                'coupon_id'   => $coupon->id,
                'customer_id' => $customer->id,
                //'order_id'    => $order->id,
            ]);

            $this->incrementUsage($coupon);

            return $discount;
        });
    }

    public function rollbackCoupon(Order $order): void{
        DB::transaction(function () use ($order) {

            if (! $order->coupon_id) {
                return;
            }

            CouponUsage::where('order_id', $order->id)->delete();

            $coupon = Coupons::find($order->coupon_id);

            $this->decrementUsage($coupon);

            $order->update([
                'coupon_id' => null,
                'discount'  => 0,
            ]);
        });
    }

    public function calculateDiscount(Coupons $coupon, float $total , $cart_id): float {
        $discount = 0;
        if($coupon->type == 'free_delivery') {
            Cart::find($cart_id)->update([
                'delivery_price' => 0 ,
            ]);
        }
        else{
            $discount = $coupon->type === 'percent'
            ? ($total * $coupon->value / 100)
            : $coupon->value;

            if ($coupon->max_discount !== null) {
                $discount = min($discount, $coupon->max_discount);
            }
        }

        return round($discount, 2);
    }

    public function canCustomerUseCoupon(Coupons $coupon, Customer $customer): bool
    {
        if ($coupon->usage_per_user === null) {
            return true;
        }

        return CouponUsage::where('coupon_id', $coupon->id)
            ->where('customer_id', $customer->id)
            ->count() < $coupon->usage_per_user;
    }

    public function isCouponValidForProducts(Coupons $coupon, $products): bool
    {
        if ($coupon->products()->count() === 0) {
            return true;
        }

        return $products->pluck('id')
            ->intersect($coupon->products->pluck('id'))
            ->isNotEmpty();
    }

    public function incrementUsage(Coupons $coupon): void
    {
        $coupon->increment('used_count');
    }

    public function decrementUsage(Coupons $coupon): void
    {
        $coupon->decrement('used_count');
    }
}