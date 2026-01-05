<?php

namespace App\Models\CommercialPlaceModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionType extends Model {
    use HasFactory;

    protected $table = 'commission_type';

    protected $fillable = [
        'id' ,
        'name' ,
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}