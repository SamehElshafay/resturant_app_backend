<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountType extends Model
{
    use HasFactory;

    protected $table = 'discount_type';

    protected $fillable = [
        'type',
        'value',
        'operation_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'value' => 'float',
        'operation_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // العلاقة مع Operation
    /*public function operation()
    {
        return $this->belongsTo(Operation::class, 'operation_id');
    }*/
}