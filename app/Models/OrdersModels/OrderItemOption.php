<?php

namespace App\Models\OrdersModels;

use App\Models\ProductsModel\ModifierOption;
use App\Models\ProductsModel\Product;
use Illuminate\Database\Eloquent\Model;

class OrderItemOption extends Model
{
    protected $table = 'order_item_options';

    protected $fillable = [
        'order_item_id',
        'option_id',
        'price'
    ];

    protected $with = [
        'option'
    ];
    
    protected $casts = [
        'order_item_id' => 'integer',
        'option_id' => 'integer'
    ];

    public function option(){
        return $this->belongsTo(ModifierOption::class, 'option_id', 'id');
    }
}