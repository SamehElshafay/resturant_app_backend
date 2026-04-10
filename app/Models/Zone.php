<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = ['branch_id', 'name'];

    protected $casts = [
        'id' => 'integer',
        'branch_id' => 'integer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function tables()
    {
        return $this->hasMany(RestaurantTable::class, 'zone_id');
    }
}
