<?php

namespace App\Models;

use App\Models\Admin\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Admin extends Authenticatable implements JWTSubject {
    protected $table = 'admin' ;

    protected $fillable = [
        'id' ,
        'name' ,
        'email',
        'password',
        'role_id'
    ];

    protected $with = [
        'role' ,
    ];

    protected $casts = [
        'id' => 'integer' ,
    ];

    protected $hidden = [
        'password',
    ];

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /*public function hasPermission($permissionName) {
        foreach ($this->roles as $role) {
            $permissions = $role->permissions()->pluck('name')->toArray();
            if (in_array($permissionName, $permissions)) {
                return true;
            }
        }
        return false;
    }*/

    public function hasPermission($permissionName){
        $permissions = $this->role->permissions()->pluck('name')->toArray();

        return in_array($permissionName, $permissions);
    }
}