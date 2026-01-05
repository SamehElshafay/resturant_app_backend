<?php

namespace App\Models\CustomerModel;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'address';

    protected $fillable = [
        'id',
        'name',
        'customer_id',
        'zone_id',
        'lng',
        'lat',
        'city',
        'street_name' ,
        'building_number',
        'floor_number',
        'apartment_number',
        'defaultCase',
    ];

    protected $casts = [
        'id'          => 'integer',
        'user_id'     => 'integer',
        'lng'         => 'float',
        'lat'         => 'float',
        'defaultCase' => 'boolean',
    ];
}