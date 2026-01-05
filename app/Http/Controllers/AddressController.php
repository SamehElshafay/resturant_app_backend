<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CustomerModel\Address;
use Tymon\JWTAuth\Facades\JWTAuth;

use function PHPUnit\Framework\isEmpty;

class AddressController extends Controller {
    public function index() {
        try {
            $user = auth('customer')->user();
            
            $data = Address::where('customer_id' , $user->id)->get();

            return response()->json([
                'success' => true,
                'data'    => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request) {
        try {
            $customer = auth('customer')->user();
            $validated = $request->validate([
                'name'              => 'required|string',
                'lng'               => 'required|numeric',
                'lat'               => 'required|numeric',
                'city'              => 'required|string',
                'street_name'       => 'required|string',
                'zone_id'           => 'required|integer',
                'building_number'   => 'nullable|string',
                'floor_number'      => 'nullable|string',
                'apartment_number'  => 'nullable|string',
                'defaultCase'       => 'boolean',
            ]);

            $validated["customer_id"] = $customer->id;
            $add = Address::where('customer_id' , $customer->id)->where('defaultCase' , true)->get();
            
            if($add->count() == 0) {
                $validated["defaultCase"] = true;
            }

            $address = Address::create($validated);

            return response()->json([
                'success' => true,
                'data'    => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => $e->getMessage()
            ], 500);
        }
    }

    public function show($id) {
        try {
            $address = Address::findOrFail($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'data'    => 'Address not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data'    => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id) {
        try {
            $address = Address::findOrFail($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'data'    => 'Address not found'
                ], 404);
            }

            $validated = $request->validate([
                'name'              => 'sometimes|string',
                'lng'               => 'sometimes|numeric',
                'lat'               => 'sometimes|numeric',
                'city'              => 'sometimes|string',
                'zone_id'           => 'sometimes|integer',
                'street_name'       => 'sometimes|string',
                'building_number'   => 'nullable|string',
                'floor_number'      => 'nullable|string',
                'apartment_number'  => 'nullable|string',
                'defaultCase'       => 'boolean',
            ]);
            
            $user = JWTAuth::parseToken()->authenticate();

            if(!isEmpty($request->defaultCase) && $request->defaultCase == true)
                Address::where('customer_id' , $user->id)->update(['defaultCase' => false]);

            $address->update($validated);

            return response()->json([
                'success' => true,
                'data'    => $address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id) {
        try {
            $address = Address::findOrFail($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'data'    => 'Address not found'
                ], 404);
            }

            $address->delete();

            return response()->json([
                'success' => true,
                'data'    => 'Address deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => $e->getMessage()
            ], 500);
        }
    }
}