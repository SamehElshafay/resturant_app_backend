<?php

namespace App\Models\CommercialPlaceModels;

use App\Models\CommercialPlaceModels\CommercialPlace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MultiOffer extends Model
{
    use HasFactory;

    protected $table = 'multi_offer';

    protected $fillable = [
        'offer_name',
        'description',
        'price',
        'expire_date',
        'active',
        'image_path',
        'commercial_place_id',
    ];

    protected $casts = [
        'price' => 'float',
        'commercial_place_id' => 'integer',
        'expire_date' => 'datetime',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['image_url'];

    protected $with = ['offer_products'];

    public function commercialPlace() {
        return $this->belongsTo(CommercialPlace::class, 'commercial_place_id');
    }

    public function offer_products() {
        return $this->hasMany(OfferProduct::class, 'offer_id');
    }

    public function getImageUrlAttribute(){
        return asset('storage/' . $this->image_path);
    }
}