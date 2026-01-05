<?php

namespace App\Models\ProductsModel;

use App\Models\CategoryModels\Category;
use App\Models\CommercialPlaceModels\CommercialPlace;
use App\Models\CommercialPlaceModels\SingleOffer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product';

    protected $fillable = [
        'id',
        'name',
        'category_id',
        'price',
        'note',
        'commercial_place_id',
        'preparation_time',
        'active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'price' => 'float',
        'preparation_time' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = [
        'image' ,
        'offer'
    ];
    
    public function image(){
        return $this->hasOne(ProductImage::class, 'product_id');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function commercialPlace() {
        return $this->belongsTo(CommercialPlace::class, 'commercial_place_id');
    }

    public function images(){
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    /*public function modifiers() {
        return $this->belongsToMany(Modifier::class,'product_modifiers','product_id','modifier_id');
    }*/

    public function modifiers() {
        return $this->hasMany(ProductModifier::class , 'product_id');
    }


    public function scopeSearch($query, $name = null, $categoryId = null, $commercialPlaceId = null){
        return $query
            ->when($name, function ($q) use ($name) {
                $q->where('name', 'like', "%{$name}%");
            })
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            })
            ->when($commercialPlaceId, function ($q) use ($commercialPlaceId) {
                $q->where('commercial_place_id', $commercialPlaceId);
            });
    }

    public function offer() {
        return $this->hasOne(SingleOffer::class , 'product_id');
    }

    public function activeOffer(){
        return $this->hasOne(SingleOffer::class, 'product_id')
            ->where('active', true)
            ->where('expiration_date', '>=', Carbon::now());
    }
}