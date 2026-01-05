<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    protected $table = 'role_permission';

    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'permission_id',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'permission_id' => 'integer',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permissions::class, 'permission_id');
    }
}
