<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $table = 'driver';

    protected $fillable = [
        'name',
        'username',
        'phone_number',
        'image_url',
        'branch_id',
        'location_id',
        'driver_status',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'branch_id' => 'integer',
        'location_id' => 'integer',
        'driver_status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // العلاقة مع Branch
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // العلاقة مع Location
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
