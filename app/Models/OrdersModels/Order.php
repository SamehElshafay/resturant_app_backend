<?php

namespace App\Models\OrdersModels;

use App\Models\CommercialPlaceModels\CommercialPlace;
use App\Models\CustomerModel\Address;
use App\Models\CustomerModel\Customer;
use App\Models\Method;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'order';

    protected $fillable = [
        'status_id',
        'user_id',
        'coupon_id',
        'discount',
        'total_value',
        'delivery_price',
        'arrive_time',
        'services',
        'order_time',
        'driver_id',
        'commercial_place_id',
        'total_recipt',
        'note',
        'payment_method_id',
        'phoneNumber',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'total_value' => 'float',
        'arrive_time' => 'datetime',
        'driver_id' => 'integer',
        'order_state_id' => 'integer',
        'total_recipt' => 'float',
        'order_location_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['payment_method' , 'other_user'] ;

    public function orderStates(){
        return $this->hasMany(OrderState::class, 'order_id');
    }

    public function latestState() {
        return $this->hasOne(OrderState::class, 'order_id')->latestOfMany();
    }

    public function payment_method(){
        return $this->belongsTo(Method::class, 'payment_method_id');
    }

    public function commercial_place(){
        return $this->belongsTo(CommercialPlace::class, 'commercial_place_id');
    }

    public function address(){
        return $this->hasOne(OrderAddress::class, 'order_id');
    }

    public function orderItems(){
        return $this->hasMany(OrderItem::class, 'order_id');
    }
    
    public function other_user(){
        return $this->hasOne(OtherUser::class, 'order_id');
    }

    public function customer(){
        return $this->belongsTo(Customer::class, 'user_id');
    }

    /*// العلاقة مع Driver
    public function driver(){
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    // العلاقة مع OrderState
    public function orderState(){
        return $this->belongsTo(OrderState::class, 'order_state_id');
    }

    // العلاقة مع OrderLocation
    public function orderLocation(){
        return $this->belongsTo(OrderLocation::class, 'order_location_id');
    }*/
}