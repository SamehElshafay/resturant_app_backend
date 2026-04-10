<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'display_name', 'group', 'guard_name'];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
