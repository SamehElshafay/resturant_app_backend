<?php

namespace App\Models\CustomerModel;

use App\Models\ProductsModel\ModifierOption;
use App\Models\ProductsModel\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $table = 'cart_items';

    protected $fillable = [
        'id',
        'cart_id',
        'product_id',
        'qty',
        'unit_price',
        'total_price',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'cart_id' => 'integer',
        'product_id' => 'integer',
        'qty' => 'integer',
        'unit_price' => 'float',
        'total_price' => 'float',
        'resturant_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['cart_item_data' , 'product'];

    public function cart() {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function cart_item_data() {
        return $this->hasMany(CartItemOption::class, 'cart_item_id');
    }
}