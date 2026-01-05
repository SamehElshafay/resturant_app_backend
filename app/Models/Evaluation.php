<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $table = 'evaluation';

    protected $fillable = [
        'driver_id',
        'user_id',
        'rating',
        'comment',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'delevery_id' => 'integer',
        'user_id' => 'integer',
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // العلاقة مع User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // العلاقة مع Delivery
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
