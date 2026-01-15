<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CommercialPlaceModels\PhoneNumbers;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class PhoneNumberController extends Controller
{
    use TransactionResponse;

    public function index() {
        return $this->transactionResponse(function () {
            $merchant = auth('merchant')->user();
            $commercial_place_id = $merchant->commercial_place_id;

            return PhoneNumbers::where('commercial_place_id', $commercial_place_id)->get();
        });
    }

    public function store(Request $request) {
        return $this->transactionResponse(function () use ($request) {
            $merchant = auth('merchant')->user();

            $validated = $request->validate([
                'phoneNumber' => 'required|string|max:20',
            ]);

            return PhoneNumbers::create([
                'commercial_place_id' => $merchant->commercial_place_id,
                'phoneNumber'        => $validated['phoneNumber'],
            ]);
        });
    }

    public function update(Request $request, $id) {
        return $this->transactionResponse(function () use ($request, $id) {
            $merchant = auth('merchant')->user();
            $commercial_place_id = $merchant->commercial_place_id;

            $validated = $request->validate([
                'phoneNumber' => 'required|string|max:20',
            ]);

            $phone = PhoneNumbers::where('id', $id)
                ->where('commercial_place_id', $commercial_place_id)
                ->firstOrFail();

            $phone->update($validated);

            return $phone;
        });
    }

    public function destroy($id) {
        return $this->transactionResponse(function () use ($id) {
            $merchant = auth('merchant')->user();
            $commercial_place_id = $merchant->commercial_place_id;

            $phone = PhoneNumbers::where('id', $id)
                ->where('commercial_place_id', $commercial_place_id)
                ->firstOrFail();

            $phone->delete();

            return true;
        });
    }
}