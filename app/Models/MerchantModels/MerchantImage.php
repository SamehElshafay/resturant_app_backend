<?php

namespace App\Models\MerchantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantImage extends Model
{
    use HasFactory;

    protected $table = 'merchant_images';

    protected $fillable = [
        'merchant_id',
        'image_path' ,
        'created_at' ,
        'updated_at' ,
    ];

    protected $casts = [
        'merchant_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function merchant(){
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    protected $appends = ['images'];
    
    public function getImagesAttribute(){
        return $this->image_path ? url($this->image_path) : null ;
    }
}