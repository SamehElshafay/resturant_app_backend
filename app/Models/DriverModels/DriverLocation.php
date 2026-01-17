<?php

namespace App\Models\DriverModels;

use Illuminate\Database\Eloquent\Model;

class DriverLocation extends Model
{
    protected $table = 'driver_locations';

    protected $fillable = [
        'driver_id',
        'zone_id',
        'expires_at',
        'lat',
        'lng',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public $timestamps = true;

    public function driver() {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
