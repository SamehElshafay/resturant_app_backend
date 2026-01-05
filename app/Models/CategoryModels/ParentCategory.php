<?php

namespace App\Models\CategoryModels;

use App\Models\CommercialPlaceModels\CommercialPlace;
use App\Services\NearestCommercialPlacesService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentCategory extends Model
{
    use HasFactory;

    protected $table = 'perant_category';

    protected $fillable = [
        'id',
        'name',
    ];

    protected $casts = [
        'id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category() {
        return $this->hasMany(Category::class , 'parent_category_id');
    }

    public function categories() {
        return $this->hasMany(Category::class , 'parent_category_id')->take(4);
    }

    public function getCategories($length) {
        return $this->hasMany(Category::class , 'parent_category_id')->take($length == null ? 1 : $length + 3);
    }

    public function nearby_commercial_places(){
        return $this->hasMany(CommercialPlace::class , 'parent_category_id');
    }

    public function recommended_commercial_places(){
        return $this->hasMany(CommercialPlace::class , 'parent_category_id');
    }

    public function other_commercial_places(){
        return $this->hasMany(CommercialPlace::class , 'parent_category_id');
    }

    public function groupedPlaces(){
        return [
            [
                'name'   => 'الاماكن القريبة',
                'places' => $this->nearby_commercial_places->toArray(),
            ],
            [
                'name'   => 'الاماكن الموصى بها',
                'places' => $this->recommended_commercial_places->toArray(),
            ],
        ];
    }

    public function scopeLimitCategories($query, $limit = 4){
        return $query->with(['categories' => function ($q) use ($limit) {
            $q->limit($limit);
        }]);
    }
}