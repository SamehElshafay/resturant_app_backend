<?php

namespace App\Models\ProductsModel;

use App\Models\MultiOffer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOfferList extends Model
{
    use HasFactory;

    protected $table = 'product_offer_list';

    protected $fillable = [
        'multi_offer_id',
        'product_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'multi_offer_id' => 'integer',
        'product_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function multiOffer()
    {
        return $this->belongsTo(MultiOffer::class, 'multi_offer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}