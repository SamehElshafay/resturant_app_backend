<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $table = 'zone';

    protected $fillable = [
        'zone_name',
        'lang',
        'lat',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'lang' => 'float',
        'lat' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // علاقة مع Branches
    public function branches()
    {
        return $this->hasMany(Branch::class, 'zone_id');
    }
}