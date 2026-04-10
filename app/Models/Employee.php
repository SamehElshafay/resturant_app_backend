<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'phone',
        'address',
        'salary',
        'hire_date',
        'termination_date',
        'is_active',
    ];

    protected $casts = [
        'id' => 'integer',
        'salary' => 'decimal:2',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'is_active' => 'boolean',
    ];
}
