<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branch';

    protected $fillable = [
        'name',
        'lang',
        'lat',
        'zone_id',
        'percentage',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'lang' => 'float',
        'lat' => 'float',
        'zone_id' => 'integer',
        'percentage' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    public function zone(){
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}