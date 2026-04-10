<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeIngredient extends Model
{
    protected $fillable = ['recipe_id', 'ingredient_id', 'child_product_id', 'quantity', 'unit', 'cost_per_unit'];

    protected $casts = [
        'recipe_id' => 'integer',
        'ingredient_id' => 'integer',
        'child_product_id' => 'integer',
        'quantity' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function childProduct()
    {
        return $this->belongsTo(Product::class, 'child_product_id');
    }
}
