<?php

namespace App\Models\MerchantModels;

use App\Models\MerchantModels\MerchantServiceState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MerchantService extends Model
{
    protected $table = 'merchant_services';

    protected $fillable = [
        'phone_number',
        'country_code',
    ];

    public function states(): HasMany{
        return $this->hasMany(MerchantServiceState::class, 'merchant_services_id');
    }

    public function latestState(): HasOne{
        return $this->hasOne(MerchantServiceState::class, 'merchant_services_id')
                    ->latestOfMany();
    }
}