<?php

namespace App\Models\AppData;

use Illuminate\Database\Eloquent\Model;

class Banners extends Model
{
    protected $table = 'banners';

    protected $fillable = [
        'path',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'path'       => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['full_path'];
    
    public function getFullPathAttribute(){
        return url($this->image_path);
    }
}