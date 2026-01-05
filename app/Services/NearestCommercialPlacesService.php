<?php

namespace App\Services;

use App\Models\CommercialPlaceModels\CommercialPlace;
use Illuminate\Support\Facades\DB;

class NearestCommercialPlacesService
{
    public function nearestStoresWithin10Km(float $lat, float $lng  , float $distance) {
        $sub = DB::table('location')
            ->select('commercial_place_id')
            ->selectRaw("
                MIN(
                    6371 * acos(
                        cos(radians(?))
                        * cos(radians(lat))
                        * cos(radians(lang) - radians(?))
                        + sin(radians(?))
                        * sin(radians(lat))
                    )
                ) as distance
            ", [$lat, $lng, $lat])
            ->groupBy('commercial_place_id')
            ->having('distance', '<=', $distance);

        $stores = DB::table('commercial_place as cp')
            ->joinSub($sub, 'l', function($join) {
                $join->on('l.commercial_place_id', '=', 'cp.id');
            })
            ->orderBy('l.distance')
            ->get();

        $commercialPlaces = [] ;
        foreach ($stores as $store) {
            $commercialPlaces[] = CommercialPlace::find($store->commercial_place_id);
        }
        return $commercialPlaces;
    }
}