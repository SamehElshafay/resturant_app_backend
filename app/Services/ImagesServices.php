<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage ;
use Illuminate\Support\Str ;

class ImagesServices {
    static public function uploadImage(string $categoryName , $image) {
        $fileName = Str::random(10) . '_' . time() . '.' . $image->getClientOriginalExtension();

        $path = public_path("uploads/$categoryName");

        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $image->move($path, $fileName);

        return "/uploads/$categoryName/$fileName";
    }

    public static function deleteImage(?string $imagePath): bool{
        if (!$imagePath) {
            return false;
        }

        $fullPath = public_path(ltrim($imagePath, '/'));

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }


    public static function updateImage(?string $oldImagePath,?object $newImage,string $categoryName): ?string {
        if (!$newImage) {
            return $oldImagePath;
        }

        self::deleteImage($oldImagePath);

        return self::uploadImage($categoryName, $newImage);
    }
}