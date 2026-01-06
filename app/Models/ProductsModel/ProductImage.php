<?php

namespace App\Models\ProductsModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'image_path',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    protected $appends = ['images'];
    
    public function getImagesAttribute(){
        return $this->image_path ? url($this->image_path) : null ;
    }
}