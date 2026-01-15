<?php

namespace App\Models\CommercialPlaceModels;

use App\Models\CommercialPlaceModels\CommercialPlace;
use App\Models\ProductsModel\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SingleOffer extends Model
{
    use HasFactory;

    protected $table = 'single_offer';

    protected $fillable = [
        'product_id',
        'commercial_place_id',
        'active',
        'price',
        'expire_date',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'price' => 'decimal:2',
        'active' => 'boolean',
        'expiration_date' => 'datetime',
        'commercial_place_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function commercialPlace(){
        return $this->belongsTo(CommercialPlace::class, 'commercial_place_id');
    }
}