<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $table = 'menu';

    protected $fillable = [
        'commercial_place_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'commercial_place_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // العلاقة مع CommercialPlace
    public function commercialPlace()
    {
        return $this->belongsTo(CommercialPlace::class, 'commercial_place_id');
    }

    // علاقة مع Category
    public function categories()
    {
        return $this->hasMany(Category::class, 'menu_id');
    }
}