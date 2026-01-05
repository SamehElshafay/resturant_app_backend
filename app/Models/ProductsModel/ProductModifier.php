<?php

namespace App\Models\ProductsModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModifier extends Model
{
    use HasFactory;

    protected $table = 'product_modifiers';

    protected $fillable = [
        'product_id',
        'modifier_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'modifier_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['modifier','options_data'];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function modifier(){
        return $this->belongsTo(Modifier::class, 'modifier_id');
    }

    public function options_data(){
        return $this->hasMany(ProductModifierOptions::class, 'product_modifiers_id');
    }
}