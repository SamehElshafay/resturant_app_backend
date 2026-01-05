<?php

namespace App\Http\Controllers;

use App\Models\CouponModels\Coupons;
use App\Models\CustomerModel\Cart;
use App\Models\OrdersModels\Order;
use App\Services\CouponServices;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderCouponController extends Controller {
    
    use TransactionResponse;

    public function apply(Request $request, CouponServices $service) {
        return $this->transactionResponse(function () use ($request , $service) {
            $customrt = auth('customer')->user() ;
            if(!$customrt){
                return response()->json([
                    'message' => 'You must be logged in to apply a coupon'
                ], 401);
            }

            $service->applyCoupon(
                Cart::where('customer_id', $customrt->id)->get()->first(),
                Coupons::where('code', $request->code)->firstOrFail(),
                auth('customer')->user()
            ) ;

            return 'Coupon applied successfully' ;
        });
    }

    public function rollback(Order $order, CouponServices $service){
        $service->rollbackCoupon($order);
        return response()->noContent();
    }
}