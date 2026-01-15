<?php

namespace App\Http\Controllers;

use App\Models\CommercialPlaceModels\SingleOffer;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class SingleOfferController extends Controller
{
    use TransactionResponse;

    public function index(Request $request) {
        return $this->transactionResponse(function () use ($request) {

            $query = SingleOffer::with(['product', 'commercialPlace']);

            if ($request->filled('commercial_place_id')) {
                $query->where('commercial_place_id', $request->commercial_place_id);
            }

            return $query->latest()->get();
        });
    }

    public function show($id){
        return $this->transactionResponse(function () use ($id) {
            return SingleOffer::with(['product', 'commercialPlace'])->findOrFail($id);
        });
    }

    public function store(Request $request){
        return $this->transactionResponse(function () use ($request) {

            $data = $request->validate([
                'product_id' => 'required|exists:product,id',
                'commercial_place_id' => 'required|exists:commercial_place,id',
                'price' => 'required|numeric|min:0',
                'expire_date' => 'required|date|after:today',
            ]);

            $exists = SingleOffer::where('product_id', $data['product_id'])
                ->where('commercial_place_id', $data['commercial_place_id'])
                ->exists();

            if ($exists) {
                throw new \Exception('This product already has a single offer in this commercial place');
            }

            $singleOffer = SingleOffer::create($data);
            return $singleOffer;
        });
    }

    public function update(Request $request){
        return $this->transactionResponse(function () use ($request) {


            $data = $request->validate([
                'id'                   => 'sometimes|exists:offer,id',
                'price'                => 'sometimes|numeric|min:0',
                'expire_date'          => 'sometimes|date|after:today',
                'active'               => 'sometimes|boolean',
            ]);

            $offer = SingleOffer::findOrFail($request->id);

            $offer->update($data);

            return $offer->fresh();
        });
    }

    public function destroy($id){
        return $this->transactionResponse(function () use ($id) {

            $offer = SingleOffer::findOrFail($id);
            $offer->delete();

            return true;
        });
    }
}