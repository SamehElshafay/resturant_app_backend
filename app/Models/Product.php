<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasBilingualName;

class Product extends Model
{
    use HasBilingualName;

    protected $fillable = ['category_id', 'name', 'name_ar', 'name_en', 'image', 'base_purchase_price'];

    protected $casts = [
        'category_id' => 'integer',
        'base_purchase_price' => 'decimal:2',
    ];

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        if (filter_var($this->image, FILTER_VALIDATE_URL)) return $this->image;
        return asset('storage/' . $this->image);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function branchPrices()
    {
        return $this->hasMany(BranchProduct::class);
    }

    public function recipe()
    {
        return $this->hasOne(Recipe::class);
    }

    public function productions()
    {
        return $this->hasMany(Production::class);
    }
}
