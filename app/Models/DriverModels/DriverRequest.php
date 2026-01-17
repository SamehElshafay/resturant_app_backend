<?php

namespace App\Models\DriverModels;

use Illuminate\Database\Eloquent\Model;

class DriverRequest extends Model
{
    protected $table = 'driver_requests';

    protected $fillable = [
        'order_id',
        'driver_id',
        'status',
        'requested_at',
        'responded_at',
        'expire_at'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'expire_at'  => 'datetime',
    ];
}