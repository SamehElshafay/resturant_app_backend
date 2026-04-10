<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasBilingualName;

class Branch extends Model
{
    use HasBilingualName;

    protected $fillable = ['name', 'name_ar', 'name_en', 'address', 'phone', 'account_id', 'account_code'];

    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
    public function posDevices()
    {
        return $this->hasMany(PosDevice::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
