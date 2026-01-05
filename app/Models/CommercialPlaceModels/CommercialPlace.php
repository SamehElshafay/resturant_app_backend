<?php

namespace App\Models\CommercialPlaceModels;

use App\Models\CategoryModels\Category;
use App\Models\CategoryModels\CommercialCategory;
use Carbon\Carbon;
use App\Models\CommercialPlaceModels\SingleOffer;
use App\Models\ProductsModel\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialPlace extends Model {
    use HasFactory;

    protected $table = 'commercial_place';

    protected $fillable = [
        'id' ,
        'name' ,
        'profile_image_id',
        'parent_category_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'rating',
    ];


    protected $with = [
        //'phoneNumbers',
        //'locations',
        //'images',
        //'commission',
        //'appointment',
        'profile_image_path',
    ];
    
    public function getRatingAttribute(){
        return 4 ;
    }

    public function phoneNumbers(){
        return $this->hasMany(PhoneNumbers::class, 'commercial_place_id');
    }

    public function location(){
        return $this->hasOne(Location::class, 'commercial_place_id');
    }

    public function images(){
        return $this->hasMany(CommercialPlaceImages::class, 'commercial_place_id');
    }

    public function profile_image_path(){
        return $this->hasOne(CommercialPlaceProfileImages::class, 'commercial_place_id');
    }
    
    public function appointment(){
        return $this->hasMany(Appointment::class, 'commercial_place');
    }

    public function getTodayDayAttribute(){
        return Carbon::now()->format('l');
    }

    public function getIsClosedAttribute(){
        $now = Carbon::now();

        $today = $now->format('l');

        $appointment = $this->appointment
            ->where('day_name', $today)
            ->first();

        if (!$appointment) {
            return true;
        }

        $currentTime = $now->format('H:i:s');

        return ! (
            $currentTime >= $appointment->open_time &&
            $currentTime <= $appointment->close_time
        );
    }

    public function offers() {
        return $this->hasMany(MultiOffer::class, 'commercial_place_id');
    }

    public function product_offer(){
        return $this->hasMany(SingleOffer::class, 'commercial_place_id')
            ->where('expiration_date', '>=', Carbon::now())->where('active' , 1);
    }

    public function all_offers(){
        return $this->hasMany(MultiOffer::class, 'commercial_place_id')
            ->where('expire_date', '>=', Carbon::now())->where('active' , 1)
            ->whereHas('offer_products') ;
    }

    public function productsWithOffers(){
        return $this->hasMany(Product::class, 'commercial_place_id')
            ->whereHas('offer', function($q) {
                $q->where('active', 1)
                ->where('expiration_date', '>=', Carbon::now());
            });
    }

    public function commission(){
        return $this->hasOne(CommercialPlaceCommission::class, 'commercial_place_id');
    }

    public function getAllTypesOffersAttribute(){
        return [
            $this->all_offers() ,
            $this->product_offer()
        ];
    }

    public function categories(){
        return $this->hasManyThrough(
            Category::class,
            CommercialCategory::class,
            'commercial_place_id',
            'id',
            'id',
            'category_id'
        );
    }
}