<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage ;
use Illuminate\Support\Str ;

class ImagesServices {
    static public function uploadImage(string $categoryName , $image) {
        $fileName = Str::random(10) . '_' . time() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs($categoryName , $fileName , 'public') ;
        return $path ;
    }
}