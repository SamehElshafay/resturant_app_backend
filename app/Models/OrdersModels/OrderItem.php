<?php

namespace App\Models\OrdersModels;

use App\Models\ProductsModel\Product;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'unit_price',
        'total_price',
    ];

    //protected $appends = ['options'];

    protected $with = [
        //'orderItemOptions.option' ,
        'product'
    ];
    public $timestamps = true;

    public function order(){
        return $this->belongsTo(Order::class);
    }

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function orderItemOptions(){
        return $this->hasMany(OrderItemOption::class);
    }

    /*public function getOptionsAttribute(){
        return $this->orderItemOptions
            ->map(fn ($o) => $o->option->name);
    }*/
}