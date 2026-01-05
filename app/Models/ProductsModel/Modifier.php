<?php

namespace App\Models\ProductsModel;

use App\Models\CommercialPlaceModels\CommercialPlace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modifier extends Model {
    use HasFactory;

    protected $table = 'modifiers';

    protected $fillable = [
        'name',
        'selection_type',
        'is_required',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'store_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    //protected $with = ['options'];

    public function store(){
        return $this->belongsTo(CommercialPlace::class, 'commercial_place_id');
    }

    public function options(){
        return $this->hasMany(ModifierOption::class, 'modifier_id');
    }
}