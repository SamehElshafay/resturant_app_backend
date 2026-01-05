<?php

namespace App\Models\CustomerModel;

use App\Models\ProductsModel\Modifier;
use App\Models\ProductsModel\ModifierOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItemOption extends Model
{
    use HasFactory;

    protected $table = 'cart_item_options';

    protected $fillable = [
        'cart_item_id',
        'modifier_option_id',
        'price',
    ];

    protected $casts = [
        'cart_item_id'        => 'integer',
        'modifier_id'         => 'integer',
        'modifier_option_id'  => 'integer',
        'price'               => 'float',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
    ];

    protected $with = ['option'];

    public function cartItem() {
        return $this->belongsTo(CartItem::class, 'cart_item_id');
    }

    public function modifier() {
        return $this->belongsTo(Modifier::class, 'modifier_id');
    }

    public function option() {
        return $this->belongsTo(ModifierOption::class, 'modifier_option_id');
    }
}