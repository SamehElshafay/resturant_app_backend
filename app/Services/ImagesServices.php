<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage ;
use Illuminate\Support\Str ;

class ImagesServices {
    static public function uploadImage(string $categoryName , $image) {
        /*$fileName = Str::random(10) . '_' . time() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs($categoryName , $fileName , 'public') ;
        return $path ;*/
        $fileName = Str::random(10) . '_' . time() . '.' . $image->getClientOriginalExtension();

        $path = public_path("uploads/$categoryName");

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $image->move($path, $fileName);

        return "/uploads/$categoryName/$fileName";
    }
}