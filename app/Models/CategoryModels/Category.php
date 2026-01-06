<?php

namespace App\Models\CategoryModels;

use App\Models\ProductsModel\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'category' ;

    protected $fillable = [
        'name',
        'image_path',
        'parent_category_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'menu_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'image_full_path',
    ];

    public function getImageFullPathAttribute(){
        return url($this->image_path);
    }

    public function scopeSearch($query, $name = null, $parentId = null){
        return $query
            ->when($name, function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            })
            ->when($parentId, function ($q) use ($parentId) {
                $q->where('parent_category_id', $parentId);
            });
    }

    public function products(){
        return $this->hasMany(Product::class, 'category_id');
    }
}