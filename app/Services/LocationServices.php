<?php

namespace App\Services;

use App\Models\CommercialPlaceModels\CommercialPlace;
use App\Models\DriverModels\DriverLocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LocationServices {
    public Function searchForDriver($location) {
        //$url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?location=$commercial_lat,$commercial_lng&radius=5000&type=restaurant&key=AIzaSyCw073765" ;
        $driverLocation = DriverLocation::where('zone_id' , $location->zone->id)->get()->first();
        return  $driverLocation ;
    }
}