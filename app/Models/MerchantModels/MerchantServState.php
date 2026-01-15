<?php

namespace App\Models\MerchantModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantServState extends Model
{
    protected $table = 'merchant_serv_state';

    protected $fillable = [
        'name',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function serviceStates(): HasMany
    {
        return $this->hasMany(MerchantServiceState::class, 'state_id');
    }
}