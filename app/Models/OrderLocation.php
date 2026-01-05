<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLocation extends Model
{
    use HasFactory;

    protected $table = 'order_location';

    protected $fillable = [
        'driver_location_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'driver_location_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function driverLocation()
    {
        return $this->belongsTo(Location::class, 'driver_location_id');
    }
}