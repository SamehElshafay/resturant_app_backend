<?php

namespace App\Http\Controllers;

use App\Models\CustomerModel\Wallet;
use App\Models\DriverModels\OrderDriver;
use App\Models\OrdersModels\OrderState;
use App\Models\OrdersModels\Order;
use App\Models\OrdersModels\State;
use App\Services\LocationServices;
use App\Services\OrderServices;
use App\Traits\TransactionResponse;
use Exception;
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
                'pending'    => ['Pending', 'Confirmed'],
                'preparing'  => ['Preparing'],
                'rejected'   => ['Rejected', 'Cancelled'],
                'ready'      => ['Ready', 'Assigned'],
                'assigned'   => ['Assigned'],
                'delivered'  => ['Delivered', 'User Received'],
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

    public function updateOrderStatus(Request $request , OrderServices $orderServices) {
        return $this->transactionResponseWithoutReturn(function () use ($request , $orderServices) {
            $merchant = auth('merchant')->user();

            $validator = validator($request->all(), [
                'order_id' => 'required|integer|exists:order,id',
                'state' => 'required|string|in:confirmed,preparing,ready,assigned,on_the_way,delivered,rejected,user_received',
                'preparation_time' => 'nullable|integer',
                'note'  => 'nullable|string',
            ]);

            $order = Order::with('commercial_place')->findOrFail($request->order_id);
            $orderServices->increasePreparationTime($order , $request->preparation_time);

            $currentState = $order->latestState->state ;
            $newState = ucfirst($request->state);
            
            $orderServices->isAllowedToChangeStatus($currentState->state_name_en , $newState);

            $orderServices->order_assigning($order , $newState);
            
            $state = State::where('state_name_en', $newState)->get()->first();
            
            $orderState = OrderState::create(['state_id' => $state->id ,'order_id' => $request->order_id]);
            
            $order->status_id = $state->id ;            
            
            if($request->note && $state->state_name_en == 'Rejected'){
                $orderState->note = $request->note ;
            }

            $order->save();

            $order->nextState = $orderServices->get_next_status($newState);

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

            $rows = DB::table('orders as o')
                ->joinSub(
                    DB::table('order_state')
                        ->select('order_id', DB::raw('MAX(id) as last_state_id'))
                        ->groupBy('order_id'),
                    'ls',
                    'o.id',
                    '=',
                    'ls.order_id'
                )
                ->join('order_state as os', 'os.id', '=', 'ls.last_state_id')
                ->join('state as s', 's.id', '=', 'os.state_id')
                ->where('o.commercial_place_id', $merchant->commercial_place_id)
                ->select(
                    DB::raw("
                        CASE
                            WHEN s.state_name_en IN ('Pending','Confirmed') THEN 'pending'
                            WHEN s.state_name_en = 'Preparing' THEN 'preparing'
                            WHEN s.state_name_en IN ('Rejected','Cancelled')
                                AND DATE(os.created_at) = CURDATE() THEN 'rejected'
                            WHEN s.state_name_en IN ('Ready','Assigned') THEN 'ready_to_ship'
                            WHEN s.state_name_en = 'On The Way' THEN 'on_the_way'
                            WHEN s.state_name_en IN ('Delivered','User Received')
                                AND DATE(os.created_at) = CURDATE() THEN 'received'
                            ELSE NULL
                        END as dashboard_key
                    "),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('dashboard_key')
                ->get()
                ->pluck('total', 'dashboard_key');

            $defaults = [
                'pending' => 0,
                'preparing' => 0,
                'rejected' => 0,
                'ready_to_ship' => 0,
                'on_the_way' => 0,
                'received' => 0,
            ];

            $counts = array_merge($defaults, $rows->toArray());

            return [
                "pending" => [
                    "count" => $counts['pending'],
                    "states" => "في الانتظار",
                ],
                "preparing" => [
                    "count" => $counts['preparing'],
                    "states" => "قيد التجهيز",
                ],
                "rejected" => [
                    "count" => $counts['rejected'],
                    "states" => "مرفوض (اليوم)",
                ],
                "ready_to_ship" => [
                    "count" => $counts['ready_to_ship'],
                    "states" => "جاهز للإرسال",
                ],
                "on_the_way" => [
                    "count" => $counts['on_the_way'],
                    "states" => "في الطريق",
                ],
                "received" => [
                    "count" => $counts['received'],
                    "states" => "تم الاستلام (اليوم)",
                ],
            ];
        });
    }


    public function getOrderByStatus(Request $request) {
        return $this->transactionResponse(function () use ($request) {

            $validated = $request->validate([
                'status' => 'required|string|in:Received,Rejected,Cancelled',
            ]);

            $merchant = auth('merchant')->user();

            $orders = DB::table('order as o')
                ->joinSub(
                    DB::table('order_state')
                        ->select('order_id', DB::raw('MAX(id) as last_state_id'))
                        ->groupBy('order_id'),
                    'ls',
                    'o.id',
                    '=',
                    'ls.order_id'
                )
                ->join('order_state as os', 'os.id', '=', 'ls.last_state_id')
                ->join('state as s', 's.id', '=', 'os.state_id')
                ->where('o.commercial_place_id', $merchant->commercial_place_id)
                ->where('s.state_name_en', $validated['status'])
                ->get();

            return [
                'status' => $validated['status'],
                'orders'  => $orders,
            ];
        });
    }

    /*public function getDriverOrderDashboard(Request $request) {
        return $this->transactionResponse(function () {
            $merchant = auth('driver')->user();

            
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
    }*/

    /*public function getOrders(Request $request) {
        return $this->transactionResponseWithoutReturn(function () use ($request) {
            $validator = validator($request->all(), [
                'state' => 'nullable|string|in:pending',
            ]);
            $states = $this->resolveDbStatesFromDashboardState($request->state);

            $orders = Order::with('latestState.state')
                ->where('commercial_place_id', $merchant->commercial_place_id)
                ->whereHas('latestState.state', function ($q) use ($states) {
                    $q->whereIn('state_name_en', $states);
                })
                ->latest()
                ->paginate(10);
        });
    }*/

    private function resolveDbStatesFromDashboardState(string $state): array{
        static $map = [
            'arrived'  => ['Delivered', 'User Received'],
            'rejected' => ['Rejected'],
        ];

        return $map[$state] ?? [];
    }

}