<?php

namespace App\Models\MerchantModels;

use App\Models\CommercialPlaceModels\CommercialPlace;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model {
    use HasFactory;

    protected $table = 'otp_code';

    protected $fillable = [
        'code',
        'user_id',
    ];

    /*public function hasPermission($permissionName){
        $permissions = $this->role->permissions()->pluck('name')->toArray();

        return in_array($permissionName, $permissions);
    }*/
}