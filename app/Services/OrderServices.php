<?php

namespace App\Services;

use App\Models\DriverModels\DriverRequest;
use App\Models\DriverModels\OrderDriver;
use App\Models\OrdersModels\Order;
use App\Models\OrdersModels\OrderState;
use App\Models\OrdersModels\State;
use Carbon\Carbon;

class OrderServices{
    public function increasePreparationTime($order, int $minutes): void
    {
        if ($minutes <= 0) {
            return;
        }

        if (is_null($order->time_order)) {
            $order->time_order = now()->addMinutes($minutes);
        } else {
            $order->time_order = Carbon::parse($order->time_order)->addMinutes($minutes);
        }
    }

    public function isAllowedToChangeStatus($currentState, string $newState) {
        $allowedTransitions = [
            'Pending' => ['Confirmed', 'Rejected'],
            'Confirmed' => ['Preparing', 'Rejected'],
            'Preparing' => ['Ready', 'Rejected'],
            'Ready' => ['Assigned', 'Rejected'],
            'Assigned' => ['On The Way', 'Rejected'],
            'On The Way' => ['Delivered', 'Rejected'],
            'Delivered' => ['User Received'],
        ];
            
        if (!isset($allowedTransitions[$currentState]) ||
            !in_array($newState, $allowedTransitions[$currentState])) {
            throw new \Exception("Invalid state transition from {$currentState} to {$newState}");
        }
    }

    public function order_assigning($order, $newState) {
        if($newState == 'Ready'){
            $locationServices = new LocationServices();
            $driver_location = $locationServices->searchForDriver($order->commercial_place->location);
            
            $driverRequest = DriverRequest::create([
                'order_id'     => $order->id ,
                'driver_id'    => $driver_location->driver_id,
                'status'       => 'pending',
                'requested_at' => now(),
                'expire_at'    => now()->addSeconds(30),
            ]);
        }
    }

    public function driver_accept_order($order_id, $driver_id){
        $order = Order::findOrFail($order_id);
        if($order->driver_id == $driver_id){
            throw new \Exception("Order already assigned to you");
        }
        if($order->driver_id != null){
            throw new \Exception("Order already assigned to driver");
        }

        $order_driver = OrderDriver::create([
            'order_id' => $order->id,
            'status_id' => 6 ,
            'driver_id' => $driver_id ,
        ]);
        
        $order->driver_id = $driver_id ;
        $order->save() ;
        // FCM: send notification to commercial place and customer 
    }

    public function get_next_status($newState){
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

        return $nextState[$newState];
    }

    public function updateOrderStatus($order, $newState){
        $state = State::where('state_name_en', $newState)->get()->first();    
        $orderState = OrderState::create(['state_id' => $state->id ,'order_id' => $order->id]);
        $order->status_id = $state->id ;
        $order->save() ;
        return $order->status_id;
    }
}