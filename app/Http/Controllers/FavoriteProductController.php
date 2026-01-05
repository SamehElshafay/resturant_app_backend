<?php

namespace App\Http\Controllers;

use App\Models\CustomerModel\FavoriteProduct;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class FavoriteProductController extends Controller {
    use TransactionResponse;

    public function index(){
        return $this->transactionResponse(function () {
            $user = auth('customer')->user();
            if(!$user) {
                throw new \Exception('unauthorized');
            }

            return FavoriteProduct::with('product')
                ->where('customer_id', $user->id)
                ->latest()
                ->get();
        });
    }

    public function store(Request $request) {
        return $this->transactionResponse(function () use ($request) {
            $user = auth('customer')->user();
        
            if(!$user) {
                throw new \Exception('unauthorized');
            }

            $request->validate([
                'product_id' => 'required|exists:product,id',
            ]);

            $exists = FavoriteProduct::where('customer_id', $user->id)
                ->where('product_id', $request->product_id)
                ->exists();

            if ($exists) {
                throw new \Exception('Product already in favorites');
            }

            return FavoriteProduct::create([
                'customer_id' => $user->id,
                'product_id' => $request->product_id,
            ]);
        });
    }

    public function destroy($id) {
        return $this->transactionResponse(function () use ($id) {

            $favorite = FavoriteProduct::findOrFail($id);
            $favorite->delete();

            return true;
        });
    }
}