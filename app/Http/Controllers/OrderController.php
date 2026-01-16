<?php

namespace App\Http\Controllers;

use App\Models\CustomerModel\Wallet;
use App\Models\OrdersModels\OrderState;
use App\Models\OrdersModels\Order;
use App\Models\OrdersModels\State;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            
            //$user = auth('customer')->user();

            $order = Order::with(['orderItems','latestState','commercial_place','orderStates' ,'address'])->findOrFail($request->order_id);

            return [
                'success' => true,
                'orders' => $order,
            ];
        });
    }

    public function getOrder(Request $request) {
        return $this->transactionResponseWithoutReturn(function () use ($request) {
            $validator = validator($request->all(), [
                'order_id' => 'required|integer|exists:order,id',
            ]);

            $order = Order::with(['customer','orderItems','latestState','commercial_place','orderStates' ,'address'])->findOrFail($request->order_id);

            $nextState = [
                'Pending' => [
                    [
                        'actionName' => 'الموافقة',
                        'state' => 'confirmed',
                    ],
                    [
                        'actionName' => 'رفض الطلب',
                        'state' => 'rejected',
                    ],
                ],
                'Confirmed' => [
                    [
                        'actionName' => 'البدء بتحضير الطلب',
                        'state' => 'preparing',
                    ],
                    [
                        'actionName' => 'رفض الطلب',
                        'state' => 'rejected',
                    ],
                ],
                'Preparing' => [
                    [
                        'actionName' => 'الطلب جاهز',
                        'state' => 'ready',
                    ],
                ],
                'Ready' => [
                    [
                        'actionName' => 'التسليم',
                        'state' => 'assigned',
                    ],
                ],
                'Assigned' => [
                    [
                        'actionName' => 'في الطريق',
                        'state' => 'on_the_way',
                    ],
                ],
                'On The Way' => [
                    [
                        'actionName' => 'تم التسليم',
                        'state' => 'delivered',
                    ],
                ],
                'Delivered' => [
                    [
                        'actionName' => 'تم التسليم',
                        'state' => 'user_received',
                    ],
                ],
            ];

            $order->nextState = $nextState[$order->latestState->state->state_name_en];

            return [
                'success' => true,
                'orders' => $order,
            ];
        });
    }

    public function getOrdersOfMerchant(Request $request) {
        return $this->transactionResponseWithoutReturn(function () use ($request) {

            $merchant = auth('merchant')->user();

            $validator = validator($request->all(), [
                'state' => 'required|string|in:pending,ready,preparing,assigned,rejected,delivered',
            ]);

            $stateMap = [
                'pending' => ['Pending', 'Confirmed'],
                'preparing' => ['Preparing'],
                'rejected' => ['Rejected', 'Cancelled'],
                'ready' => ['Ready', 'Assigned'],
                'assigned' => ['Assigned'],
                'delivered' => ['Delivered', 'User Received'],
            ];

            $states = $stateMap[$request->state];

            $orders = Order::with('latestState.state')
                ->where('commercial_place_id', $merchant->commercial_place_id)
                ->whereHas('latestState.state', function ($q) use ($states) {
                    $q->whereIn('state_name_en', $states);
                })
                ->latest()
                ->paginate(10);

            return [
                'success' => true,
                'orders' => $orders->getCollection(),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page'    => $orders->lastPage(),
                    'per_page'     => $orders->perPage(),
                    'total'        => $orders->total(),
                ],
            ];
        });
    }


    public function updateOrderStatus(Request $request) {
        return $this->transactionResponseWithoutReturn(function () use ($request) {
            $merchant = auth('merchant')->user();

            $validator = validator($request->all(), [
                'order_id' => 'required|integer|exists:order,id',
                'state' => 'required|string|in:confirmed,preparing,ready,assigned,on_the_way,delivered,rejected,user_received',
                'preparation_time' => 'nullable|integer',
                'note'  => 'nullable|string',
            ]);

            $order = Order::findOrFail($request->order_id);
            
            if ($request->preparation_time) {
                if($order->time_order == null){
                    $order->time_order = now()->addMinutes($request->preparation_time);
                } else {
                    $order->time_order = $order->time_order->addMinutes($request->preparation_time);
                }
            }

            $currentState = $order->latestState->state ;
            $newState = ucfirst($request->state);
            
            $allowedTransitions = [
                'Pending' => ['Confirmed', 'Rejected'],
                'Confirmed' => ['Preparing', 'Rejected'],
                'Preparing' => ['Ready', 'Rejected'],
                'Ready' => ['Assigned', 'Rejected'],
                'Assigned' => ['On The Way', 'Rejected'],
                'On The Way' => ['Delivered', 'Rejected'],
                'Delivered' => ['User Received'],
            ];
            
            if (!isset($allowedTransitions[$currentState->state_name_en]) ||
                !in_array($newState, $allowedTransitions[$currentState->state_name_en])) {
                throw new \Exception("Invalid state transition from {$currentState->state_name_en} to {$newState}");
            }

            $state = State::where('state_name_en', $newState)->get()->first();
            
            $orderState = OrderState::create(['state_id' => $state->id ,'order_id' => $request->order_id]);
            
            if($request->note && $state->state_name_en == 'Rejected'){
                $orderState->note = $request->note ;
            }
            
            $nextState = [
                'Pending' => [
                    [
                        'actionName' => 'الموافقة',
                        'state' => 'confirmed',
                    ],
                    [
                        'actionName' => 'رفض الطلب',
                        'state' => 'rejected',
                    ],
                ],
                'Confirmed' => [
                    [
                        'actionName' => 'البدء بتحضير الطلب',
                        'state' => 'preparing',
                    ],
                    [
                        'actionName' => 'رفض الطلب',
                        'state' => 'rejected',
                    ],
                ],
                'Preparing' => [
                    [
                        'actionName' => 'الطلب جاهز',
                        'state' => 'ready',
                    ],
                ],
                'Ready' => [
                    [
                        'actionName' => 'التسليم',
                        'state' => 'assigned',
                    ],
                ],
                'Assigned' => [
                    [
                        'actionName' => 'في الطريق',
                        'state' => 'end case',//'on_the_way',
                    ],
                ],
                'On The Way' => [
                    [
                        'actionName' => 'في الطريق', //'تم التسليم',
                        'state' => 'end case' //'delivered',
                    ],
                ],
                'Delivered' => [
                    [
                        'actionName' => 'تم التسليم',
                        'state' => 'end case' //'user_received',
                    ],
                ],
                'User Received' => [
                    [
                        'actionName' => 'العميل استلم',
                        'state' => 'end case'
                    ],
                ],
            ];

            $order->nextState = $nextState[$newState];

            return [
                'success' => true,
                'message' => 'Order status updated successfully',
                'orders' => $order ,
            ];
        });
    }

    public function getOrderDashboard(Request $request) {
        return $this->transactionResponse(function () {
            $merchant = auth('merchant')->user();
            $merchant->commercial_place_id ;


            $rawCounts = DB::table('order')
                ->joinSub(
                    DB::table('order_state')
                        ->select('order_id', DB::raw('MAX(id) as last_state_id'))
                        ->groupBy('order_id'),
                    'latest_states',
                    'order.id',
                    '=',
                    'latest_states.order_id'
                )
                ->join('order_state', 'order_state.id', '=', 'latest_states.last_state_id')
                ->join('state', 'state.id', '=', 'order_state.state_id')
                ->where('order.commercial_place_id', $merchant->commercial_place_id)
                ->select(
                    'state.state_name_en as state_name_en',
                    'order_state.created_at as state_created_at'
                )
                ->get();



            $dashboardStates = [
                'pending' => ['Pending', 'Confirmed'],
                'preparing' => ['Preparing'],
                'rejected' => ['Rejected', 'Cancelled'],
                'ready_to_ship' => ['Ready', 'Assigned'],
                'on_the_way' => ['On The Way'],
                'received' => ['Delivered', 'User Received'],
            ];

            $dashboardCounts = array_fill_keys(array_keys($dashboardStates), 0) ;

            $dashboardCounts = array_fill_keys(array_keys($dashboardStates), 0);

            $todayOnlyStates = [
                'rejected',
                'received',
            ];

            foreach ($dashboardStates as $key => $states) {
                foreach ($rawCounts as $row) {

                    if (!in_array($row->state_name_en, $states)) {
                        continue;
                    }

                    if (in_array($key, $todayOnlyStates)) {
                        if (\Carbon\Carbon::parse($row->created_at)->isToday()) {
                            $dashboardCounts[$key]++;
                        }
                    } 
                    else {
                        $dashboardCounts[$key]++;
                    }
                }
            }

            return [
                "pending" => [
                    "count" => $dashboardCounts['pending'],
                    "states" => "في الانتظار",
                ],
                "preparing" => [
                    "count" => $dashboardCounts['preparing'],
                    "states" => "قيد التجهيز",
                ],
                "rejected" => [
                    "count" => $dashboardCounts['rejected'],
                    "states" => "مرفوض",
                ],
                "ready_to_ship" => [
                    "count" => $dashboardCounts['ready_to_ship'],
                    "states" => "جاهز للارسال",
                ],
                "on_the_way" => [
                    "count" => $dashboardCounts['on_the_way'],
                    "states" => "تم الاستلام",
                ],
                "received" => [
                    "count" => $dashboardCounts['received'],
                    "states" => "واصل",
                ]
            ] ;
        });
    }
}