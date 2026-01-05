<?php

namespace App\Models\CommercialPlaceModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneNumbers extends Model {
    use HasFactory;

    protected $table = 'phone_numbers';

    protected $fillable = [
        'id',
        'commercial_place_id',
        'phoneNumber',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'commercial_place_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function commercialPlace(){
        return $this->belongsTo(CommercialPlace::class, 'commercial_place_id');
    }
}