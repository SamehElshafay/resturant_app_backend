<?php

namespace App\Models\ProductsModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Modifiers;

class ProductModifierOptions extends Model
{
    use HasFactory;

    protected $table = 'product_modifiers_options';

    protected $fillable = [
        'id' ,
        'product_modifiers_id',
        'option_id',
        'price',
        'active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'product_modifiers_id' => 'integer',
        'option_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['option'];
    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function modifier(){
        return $this->belongsTo(Modifiers::class, 'modifier_id');
    }

    public function option(){
        return $this->belongsTo(ModifierOption::class, 'option_id');
    }
}