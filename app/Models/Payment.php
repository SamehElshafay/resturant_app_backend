<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payment';

    protected $fillable = [
        'order_id',
        'user_id',
        'amount',
        'method_id',
        'transaction_id',
        'waiting_status_id',
        'paid_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'amount' => 'float',
        'method_id' => 'integer',
        'waiting_status_id' => 'integer',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function method()
    {
        return $this->belongsTo(Method::class, 'method_id');
    }

    public function logs()
    {
        return $this->hasMany(PaymentLog::class, 'payment_id');
    }
}