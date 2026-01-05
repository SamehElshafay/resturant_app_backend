<?php

namespace App\Models\CommercialPlaceModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialPlaceCommission extends Model
{
    use HasFactory;

    protected $table = 'commercial_place_commission';

    protected $fillable = [
        'id' ,
        'commercial_place_id' ,
        'commission_id' ,
        'value',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'commercial_place_id' => 'integer',
        'commission_id' => 'integer',
        'value' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['CommissionType'];
    
    public function CommissionType() {
        return $this->belongsTo(CommissionType::class, 'commission_id');
    }

}