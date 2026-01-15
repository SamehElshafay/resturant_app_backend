<?php

namespace App\Models\MerchantModels;

use App\Models\CommercialPlaceModels\CommercialPlace;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Merchant extends Authenticatable implements JWTSubject {
    use HasFactory;

    protected $table = 'merchant';

    protected $fillable = [
        'name',
        'commercial_place_id',
        'password',
        'verifcation_id',
        'phoneNumber',
    ];

    protected $with = [
        'commercialPlace',
        'verifcation',
        'merchant_image'
    ];

    protected $hidden = [
        'password',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function commercialPlace(){
        return $this->belongsTo(CommercialPlace::class , 'commercial_place_id' );
    }

    public function merchant_image(){
        return $this->hasOne(MerchantImage::class, 'merchant_id');
    }

    public function verifcation(){
        return $this->belongsTo(Verifcation::class, 'verifcation_id' );
    }

    public function isVerified(){
        return $this->verifcation && $this->verifcation->is_verified;
    }
    
    /*public function hasPermission($permissionName){
        $permissions = $this->role->permissions()->pluck('name')->toArray();

        return in_array($permissionName, $permissions);
    }*/
}