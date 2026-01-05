<?php

namespace App\Models\Admin;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'id',
        'name',
        'description',
        'created_at',
        'updated_at',
    ];

    protected $with  = [
        'permissions'
    ];
    
    protected $casts = [
        'id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function permissions() {
        return $this->belongsToMany(Permissions::class, 'role_permission', 'role_id', 'permission_id');
    }


    public function admins() {
        return $this->belongsToMany(Admin::class, 'user_role');
    }
}