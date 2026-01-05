<?php

namespace App\Models\OrdersModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherUser extends Model{
    use HasFactory;

    protected $table = 'other_user';

    protected $fillable = [
        'order_id',
        'phone_number',
        'address',
        'user_name',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}