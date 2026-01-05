<?php

namespace App\Models\CustomerModel;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $table = 'wallet';

    protected $fillable = [
        'id',
        'user_id',
        'balance',
    ];

    protected $casts = [
        'id'          => 'integer',
        'user_id'     => 'integer',
        'balance'     => 'float',
    ];
}