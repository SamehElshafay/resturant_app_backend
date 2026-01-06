<?php

namespace App\Models\CommercialPlaceModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialPlaceProfileImages extends Model
{
    use HasFactory;

    protected $table = 'commercial_place_profile_images';

    protected $fillable = [
        'id',
        'commercial_place_id',
        'path'
    ];

    protected $casts = [
        'id' => 'integer',
        'commercial_place_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['full_path'];

    public function getFullPathAttribute(){
        return url($this->path);
    }

}