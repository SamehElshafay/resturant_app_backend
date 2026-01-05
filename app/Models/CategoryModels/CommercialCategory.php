<?php

namespace App\Models\CategoryModels;

use App\Models\ProductsModel\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialCategory extends Model {
    use HasFactory;

    protected $table = 'commercial_category';

    protected $fillable = [
        'commercial_place_id',
        'category_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['category'] ;

    public function category(){
        return $this->belongsTo(Category::class, 'category_id');
    }

    protected $hidden = [
        'id',
        'products',
        //'category',
        'commercial_place_id',
        'category_id',
        'created_at' ,
        'updated_at' ,
    ];
}

// pending
// rejected
// deliverd