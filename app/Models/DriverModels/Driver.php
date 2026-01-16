<?php

namespace App\Models\DriverModels;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Driver extends Authenticatable implements JWTSubject {
    use HasFactory;

    protected $table = 'driver';

    protected $fillable = [
        'name',
        'password',
        'phone_number',
        'country_code',
        'active' ,
        'image_url',
        'location_id',
        'driver_status',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'full_image_url',
    ];

    protected $hidden = [
        'password',
        'image_url',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'active' => 'integer',
        'location_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getFullImageUrlAttribute() {
        return $this->image_url ? url($this->image_url) : null ;
    }
    // العلاقة مع Location
    /*public function location(){
        return $this->belongsTo(Location::class, 'location_id');
    }*/

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }
}