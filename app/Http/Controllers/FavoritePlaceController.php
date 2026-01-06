<?php

namespace App\Http\Controllers;

use App\Models\CustomerModel\FavoritePlace;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class FavoritePlaceController extends Controller
{
    use TransactionResponse;

    public function index(){
        return $this->transactionResponse(function () {

            $user = auth('customer')->user();
            if(!$user) {
                throw new \Exception('unauthorized');
            }

            return FavoritePlace::with('commercialPlace')
                ->where('customer_id', $user->id)
                ->latest()
                ->get();
        });
    }

    public function store(Request $request){
        return $this->transactionResponse(function () use ($request) {
            $user = auth('customer')->user();
        
            if(!$user) {
                throw new \Exception('unauthorized');
            }
        
            $request->validate([
                'commercial_place_id' => 'required|exists:commercial_place,id',
            ]);

            $exists = FavoritePlace::where('customer_id', $user->id)
                ->where('commercial_place_id', $request->commercial_place_id)
                ->exists();

            if ($exists) {
                throw new \Exception('Place already in favorites');
            }

            return FavoritePlace::create([
                'customer_id' => $user->id,
                'commercial_place_id' => $request->commercial_place_id,
            ]);
        });
    }

    public function destroy($id) {
        return $this->transactionResponse(function () use ($id) {
            $user = auth('customer')->user();
            $favorite = FavoritePlace::where('customer_id', $user->id)->where('commercial_place_id', $id)->get()->first() ;
            $favorite->delete();
            return true;
        });
    }
}