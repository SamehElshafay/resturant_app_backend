<?php

namespace App\Models\OrdersModels;

use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    protected $table = 'order_address';

    protected $fillable = [
        'id',
        'zone_id',
        'lng',
        'lat',
        'city',
        'street_name' ,
        'building_number',
        'order_id',
        'floor_number',
        'apartment_number',
    ];

    protected $casts = [
        'id'          => 'integer',
        'order_id'    => 'integer',
        'user_id'     => 'integer',
        'lng'         => 'float',
        'lat'         => 'float',
        'defaultCase' => 'boolean',
    ];
}