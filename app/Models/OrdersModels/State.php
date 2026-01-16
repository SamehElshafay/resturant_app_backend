<?php

namespace App\Models\OrdersModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $table = 'state';

    protected $fillable = [
        'state_name_en',
        'state_name_ar',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'name',
    ];

    public function getNameAttribute() {
        return $this->state_name_en;
    }
    
    public function orderStates() {
        return $this->hasMany(OrderState::class, 'state_id');
    }
}
