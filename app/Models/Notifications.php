<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'driver_id',
        'message',
        'type',
        'receive',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'driver_id' => 'integer',
        'receive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // العلاقة مع User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // العلاقة مع Driver
    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}