<?php

namespace App\Models\DriverModels;

use App\Models\Driver;
use App\Models\MerchantModels\MerchantServState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverServiceState extends Model
{
    protected $table = 'driver_services_state';

    protected $fillable = [
        'driver_services_id',
        'state_id',
        'note',
    ];

    protected $appends = [
        'status',
        'status_id',
    ];
    
    protected $hidden = [
        'updated_at',
        'merchant_services_id',
        'state_id',
        'id' ,
        'state'
    ];

    public function service(): BelongsTo{
        return $this->belongsTo(DriverService::class, 'driver_services_id');
    }

    public function state(){
        return $this->belongsTo(MerchantServState::class, 'state_id');
    }

    public function getStatusAttribute() {
        return $this->state?->name;
    }

    public function getStatusIdAttribute() {
        return $this->state?->id;
    }
}