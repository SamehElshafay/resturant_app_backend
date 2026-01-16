<?php

namespace App\Models\DriverModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DriverService extends Model {
    protected $table = 'driver_services';

    protected $fillable = [
        'phone_number',
        'country_code',
    ];

    public function states(): HasMany{
        return $this->hasMany(DriverServiceState::class, 'driver_services_id');
    }

    public function latestState(): HasOne{
        return $this->hasOne(DriverServiceState::class, 'driver_services_id')->latestOfMany();
    }
}