<?php

namespace App\Http\Controllers;

use App\Models\DriverModels\DriverRequest;
use App\Models\DriverModels\OrderDriver;
use App\Models\OrdersModels\Order;
use App\Models\OrdersModels\OrderState;
use App\Models\OrdersModels\State;
use App\Services\OrderServices;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class DriverRequestController extends Controller {
    use TransactionResponse ;
    
    public function index(Request $request) {
        $query = DriverRequest::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->order_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(10),
        ]);
    }

    public function getDashboard(Request $request) {
        $driver = auth()->guard('driver')->user() ;
        
        if(!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to perform this action',
            ], 403);
        }
        $driver_id = $driver->id ;
        //$pending = DriverRequest::where('driver_id', $driver->id)->get()->count();

        $orderStats = OrderDriver::where('driver_id', $driver_id)
        ->whereIn('status_id', [6, 8, 9, 11])
        ->selectRaw('status_id, COUNT(*) as count')
        ->groupBy('status_id')
        ->pluck('count', 'status_id');

        $rejectedCount = DriverRequest::where('driver_id', $driver_id)
            ->where('status', 'rejected')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'pending_count'   => $orderStats[6]  ?? 0,
                'onTheWay_count'  => $orderStats[8]  ?? 0,
                'arrived_count'   => $orderStats[9]  ?? 0,
                'changed_count'   => $orderStats[11] ?? 0,
                'rejected_count'  => $rejectedCount,
            ],
        ]);
    }

    public function getOrderDashboard(Request $request){
        $STATUS_MAP = [
            'pending'    => 6,
            'on_the_way' => 8,
            'arrived'    => 9,
            'changed'    => 11,
        ];

        $driver = auth('driver')->user();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'status' => 'required|string|in:pending,on_the_way,arrived,changed',
        ]);

        $statusKey = $request->status;
        $statusId  = $STATUS_MAP[$statusKey];

        $orders = OrderDriver::with('order')->where('driver_id', $driver->id)
            ->where('status_id', $statusId)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $statusKey,
                'orders'  => $orders,
            ],
        ]);
    }

    public function updateOrderStatus(Request $request, $id , OrderServices $orderServices) {
        return $this->transactionResponseWithoutReturn(function () use ($request, $id , $orderServices) {
            $user = auth()->guard('driver')->user();
            $driverRequest = DriverRequest::findOrFail($id);
            
            if(!$user || $user->id !== $driverRequest->driver_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to perform this action',
                ], 403);
            }

            $validated = $request->validate([
                'status' => 'required|in:On The Way,delivered',
            ]);

            $order = Order::findOrFail($driverRequest->order_id);
            $currentState = $order->latestState->state ;
            $newState = ucfirst($request->status);
            $orderServices->isAllowedToChangeStatus($currentState->state_name_en , $newState);
            $state_id = $orderServices->updateOrderStatus($order , $newState);
            
            OrderDriver::where('order_id' , $order->id)->where('driver_id',$user->id)->update(['status_id' => $state_id]) ;

            return response()->json([
                'success' => true,
                'message' => 'Order status updated',
                'data' => [
                    'order' => $order,
                    'state' => $newState,
                ],
            ]);
        });
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'order_id'  => 'required|integer|exists:orders,id',
            'driver_id' => 'required|integer|exists:drivers,id',
        ]);

        $driverRequest = DriverRequest::create([
            'order_id'     => $validated['order_id'],
            'driver_id'    => $validated['driver_id'],
            'status'       => 'pending',
            'requested_at' => now(),
            'expire_at'    => now()->addSeconds(30),
        ]);

        return response()->json([
            'success' => true,
            'data' => $driverRequest,
        ], 201);
    }

    public function show($id) {
        return response()->json([
            'success' => true,
            'data' => DriverRequest::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id , OrderServices $orderServices) {
        return $this->transactionResponseWithoutReturn(function () use ($request, $id, $orderServices) {
            $user = auth()->guard('driver')->user();
            $driverRequest = DriverRequest::findOrFail($id);
            if(!$user || $user->id !== $driverRequest->driver_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to perform this action',
                ], 403);
            }

            $validated = $request->validate([
                'status' => 'required|in:accepted,rejected,expired',
            ]);

            if($driverRequest->status === 'accepted') {
                $orderServices->driver_accept_order($driverRequest->order_id , $user->id);
            }else if($driverRequest->status === 'rejected') {
                $driverRequest->update([
                    'expire_at' => now() ,
                ]);
                $order = Order::findOrFail($driverRequest->order_id);
                $orderServices->order_assigning($order , "Ready");
            }

            $driverRequest->update([
                'status' => $validated['status'],
                'responded_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $driverRequest,
            ]);
        });
    }

    public function destroy($id) {
        DriverRequest::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Driver request deleted',
        ]);
    }
}