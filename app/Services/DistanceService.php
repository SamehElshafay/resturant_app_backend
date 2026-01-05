<?php

namespace App\Services;

use App\Models\CommercialPlaceModels\CommercialPlace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DistanceService {

    public static function getDistanceInKm($fromLat, $fromLng, $toLat, $toLng){
        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins'      => "$fromLat,$fromLng",
            'destinations' => "$toLat,$toLng",
            'key'          => "AIzaSyCVe5iM354ba0S3oeX_iWyVRkHR6vPPuEw",
            'units'        => 'metric',
        ]);

        return $response;
    }

    public static function haversineDistanceKm($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371;

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;

        $a = sin($latDelta / 2) ** 2 +
            cos($lat1) * cos($lat2) *
            sin($lonDelta / 2) ** 2;

        $c = 2 * asin(sqrt($a));

        return $earthRadius * $c;
    }
}