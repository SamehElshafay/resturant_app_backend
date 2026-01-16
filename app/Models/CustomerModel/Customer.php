<?php

namespace App\Models\CustomerModel;

use App\Models\CouponModels\Coupons;
use App\Models\LoyaltyPoint;
use App\Models\MerchantModels\Verifcation;
use App\Models\OrdersModels\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject {
    use HasFactory;

    protected $table = 'customer';

    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'password',
        'image_path',
        'receive_notification',
        'verifcation_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'receive_notification' => 'boolean',
        'verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['image_full_path' , 'default_address'];
    protected $with = ['address' , 'verifcation'];
    protected $hidden = [
        'password',
    ];

    public function getImageFullPathAttribute() {
        return asset('storage/' . $this->image_path);
    }

    public function coupons() {
        return $this->belongsToMany(Coupons::class, 'user_coupon', 'customer_id', 'coupon_id')->withPivot('is_used')->withTimestamps();
    }

    public function loyaltyPoints() {
        return $this->hasMany(LoyaltyPoint::class, 'customer_id');
    }

    public function orders() {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function address() {
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function getDefaultAddressAttribute() {
        return $this->address()->where('defaultCase', 1)->first();
    }

    public function verifcation() {
        return $this->belongsTo(Verifcation::class, 'verifcation_id');
    }


    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }
}