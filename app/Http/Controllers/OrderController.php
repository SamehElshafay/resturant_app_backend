<?php

namespace App\Http\Controllers;

use App\Models\CustomerModel\Wallet;
use App\Models\OrdersModels\OrderState;
use App\Models\OrdersModels\Order;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class OrderController extends Controller {
    use TransactionResponse;

    public function cancelOrder(Request $request) {
        return $this->transactionResponse(function () use ($request) {
            $validator = validator($request->all(), [
                'order_id' => 'required|integer|exists:order,id',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $user = auth('customer')->user();

            $order = Order::where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $lastState = OrderState::where('order_id', $order->id)
                ->latest()
                ->first();

            if (in_array($lastState->state_id, [7, 8, 9])) {
                throw new \Exception('Order cannot be canceled in this state');
            }

            OrderState::create([
                'order_id' => $order->id,
                'state_id' => 4,
            ]);

            if ($order->payment_method_id != 1) {
                $wallet = Wallet::where('user_id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($wallet) {
                    $wallet->increment('balance', $order->total_value);
                }
            }

            return [
                'message' => 'Order canceled successfully',
            ];
        });
    }

    public function allOrderOfUser(Request $request){
        return $this->transactionResponse(function () use ($request) {

            $validator = validator($request->all(), [
                'type' => 'nullable|in:past,current',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $user = auth('customer')->user();

            $pastStates = ['Rejected', 'Cancelled', 'Delivered'];
            $currentStates = [
                'Pending',
                'Confirmed',
                'Preparing',
                'Ready',
                'Assigned',
                'On The Way',
            ];

            $orders = Order::with(['latestState.state'])
                ->where('user_id', $user->id)
                ->when($request->type === 'past', function ($q) use ($pastStates) {
                    $q->whereHas('latestState.state', function ($q2) use ($pastStates) {
                        $q2->whereIn('state_name_en', $pastStates);
                    });
                })
                ->when($request->type === 'current', function ($q) use ($currentStates) {
                    $q->whereHas('latestState.state', function ($q2) use ($currentStates) {
                        $q2->whereIn('state_name_en', $currentStates);
                    });
                })
                ->latest()
                ->paginate(10);

            return [
                'orders' => $orders->items(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page'    => $orders->lastPage(),
                    'per_page'     => $orders->perPage(),
                    'total'        => $orders->total(),
                ],
            ];
        });
    }


    public function getOrderOfUser(Request $request) {
        return $this->transactionResponseWithoutReturn(function () use ($request) {
            $validator = validator($request->all(), [
                'order_id' => 'required|integer|exists:order,id',
            ]);
            
            $user = auth('customer')->user();

            $order = Order::with(['orderItems','latestState','commercial_place','orderStates' ,'address'])->findOrFail($request->order_id);

            return [
                'success' => true,
                'orders' => $order,
            ];
        });
    }
}