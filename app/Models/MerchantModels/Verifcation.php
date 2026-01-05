<?php

namespace App\Models\MerchantModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Verifcation extends Model {
    use HasFactory;

    protected $table = 'verifcations';

    protected $fillable = [
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];
}