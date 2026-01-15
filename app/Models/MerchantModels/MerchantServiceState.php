<?php

namespace App\Models\MerchantModels;

use App\Models\MerchantModels\MerchantService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantServiceState extends Model
{
    protected $table = 'merchant_services_state';

    protected $fillable = [
        'merchant_services_id',
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
        return $this->belongsTo(MerchantService::class, 'merchant_services_id');
    }

    public function state()
    {
        return $this->belongsTo(MerchantServState::class, 'state_id');
    }

    public function getStatusAttribute() {
        return $this->state?->name;
    }

    public function getStatusIdAttribute() {
        return $this->state?->id;
    }
}