<?php

namespace App\Models\CustomerModel;

use App\Models\CommercialPlaceModels\CommercialPlace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoritePlace extends Model {
    use HasFactory;

    protected $table = 'favorite_places';

    protected $fillable = [
        'customer_id',
        'commercial_place_id',
    ];

    public function customer(){
        return $this->belongsTo(Customer::class);
    }

    public function commercialPlace(){
        return $this->belongsTo(CommercialPlace::class);
    }
}