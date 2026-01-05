<?php

namespace App\Models\CommercialPlaceModels;

use App\Models\CommercialPlaceModels\CommercialPlace;
use App\Models\ProductsModel\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferProduct extends Model
{
    use HasFactory;

    protected $table = 'offer_product';

    protected $fillable = [
        'offer_id',
        'product_id',
    ];

    protected $casts = [
        'offer_id' => 'integer',
        'product_id' => 'integer',
    ];
    protected $with = [
        'product',
    ];

    public function offer() {
        return $this->belongsTo(MultiOffer::class, 'offer_id');
    }

    public function product() {
        return $this->belongsTo(Product::class, 'product_id');
    }
}