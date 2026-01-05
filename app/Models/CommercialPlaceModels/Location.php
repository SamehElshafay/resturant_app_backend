<?php

namespace App\Models\CommercialPlaceModels;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'location';

    protected $fillable = [
        'commercial_place_id',
        'address',
        'zone_id',
        'lat',
        'lang',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'commercial_place_id' => 'integer',
        'lat' => 'float',
        'lang' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['zone'];
    public function zone(){
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    /*public function drivers(){
        return $this->hasMany(Driver::class, 'location_id');
    }*/
}
/*
    ALTER TABLE location
    ADD INDEX idx_location_lat_lang (lat, lang);
*/