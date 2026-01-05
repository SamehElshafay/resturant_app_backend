<?php

namespace App\Http\Controllers;

use App\Models\CommercialPlaceModels\MultiOffer;
use App\Models\CommercialPlaceModels\OfferProduct;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MultiOfferController extends Controller
{
    use TransactionResponse;
    
    public function store(Request $request){
        return $this->transactionResponse(function () use ($request) {

            $data = $request->validate([
                'offer_name'          => 'required|string',
                'description'         => 'nullable|string',
                'price'               => 'required|numeric',
                'expire_date'         => 'required|date',
                'commercial_place_id' => 'required|exists:commercial_place,id',
                'image_path'          => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'products'            => 'nullable|array',
                'products.*'          => 'exists:product,id',
            ]);

            $imagePath = $request->file('image_path')->store('offers', 'public');

            $offer = MultiOffer::create([
                'offer_name'          => $data['offer_name'],
                'description'         => $data['description'] ?? null,
                'price'               => $data['price'],
                'expire_date'         => $data['expire_date'],
                'commercial_place_id' => $data['commercial_place_id'],
                'image_path'          => $imagePath,
            ]);

            if (!empty($data['products'])) {
                $offer->offer_products()->createMany(
                    collect($data['products'])->map(fn ($id) => [
                        'product_id' => $id
                    ])->toArray()
                );
            }

            return $offer->load('offer_products.product');
        });
    }

    public function update(Request $request){
        return $this->transactionResponse(function () use ($request) {
            $data = $request->validate([
                'id' => 'required|exists:multi_offer,id',
                'offer_name'  => 'sometimes|string',
                'description' => 'nullable|string',
                'price'       => 'sometimes|numeric',
                'expire_date' => 'sometimes|date',
                'image_path'  => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
                'products'    => 'nullable|array',
                'products.*'  => 'exists:product,id',
                'active'      => 'sometimes|boolean',
            ]);

            $offer = MultiOffer::findOrFail($data['id']);

            if ($request->hasFile('image_path')) {
                if ($offer->image_path && Storage::disk('public')->exists($offer->image_path)) {
                    Storage::disk('public')->delete($offer->image_path);
                }

                $data['image_path'] = $request->file('image_path')->store('offers', 'public');
            }

            $offer->update($data);

            if (array_key_exists('products', $data)) {
                $offer->offer_products()->delete();

                if (!empty($data['products'])) {
                    $offer->offer_products()->createMany(
                        collect($data['products'])->map(fn ($id) => [
                            'product_id' => $id
                        ])->toArray()
                    );
                }
            }

            return $offer->fresh()->load('offer_products.product');
        });
    }


    public function destroy($id){
        return $this->transactionResponse(function () use ($id) {

            $offer = MultiOffer::findOrFail($id);

            if ($offer->image_path && Storage::disk('public')->exists($offer->image_path)) {
                Storage::disk('public')->delete($offer->image_path);
            }

            $offer->offer_products()->delete();

            $offer->delete();

            return [
                'deleted' => true
            ];
        });
    }


    public function addProduct(Request $request, $offerId){
        return $this->transactionResponse(function () use ($request, $offerId) {

            $request->validate([
                'product_id' => 'required|exists:product,id',
            ]);

            $offer = MultiOffer::findOrFail($offerId);

            OfferProduct::firstOrCreate([
                'offer_id' => $offer->id,
                'product_id' => $request->product_id,
            ]);

            return $offer->fresh()->load('offer_products.product');
        });
    }

    public function removeProduct(Request $request, $offerId){
        return $this->transactionResponse(function () use ($request, $offerId) {

            $request->validate([
                'product_id' => 'required|exists:product,id',
            ]);

            OfferProduct::where('offer_id', $offerId)
                ->where('product_id', $request->product_id)
                ->delete();

            return MultiOffer::with('offer_products.product')->findOrFail($offerId);
        });
    }

    public function show($id){
        return $this->tryCatchBody(function () use ($id) {
            return MultiOffer::with('offer_products.product')->findOrFail($id);
        });
    }

    public function index(){
        return $this->tryCatchBody(function () {
            return MultiOffer::with('offer_products.product')->latest()->get();
        });
    }
}