<?php

namespace App\Http\Controllers;

use App\Models\MerchantModels\Merchant;
use App\Models\MerchantModels\OtpCode;
use App\Models\MerchantModels\Verifcation;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MerchantController extends Controller{
    public function register(Request $request){
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'phoneNumber' => 'required|string|max:20|unique:merchant',
                'commercial_place_id' => 'nullable|integer|exists:commercial_places,id',
                'password' => 'required|string|min:6',
            ]);

            $validated['password'] = bcrypt($validated['password']);

            $merchant = Merchant::create($validated);
            
            OtpCode::create([
                'code' => rand(100000, 999999),
                'user_id' => $merchant->id,
            ]);

            $verifcation = Verifcation::create([
                'is_verified' => false,
            ]);
            
            $merchant->verifcation_id = $verifcation->id;
            $merchant->save();

            $token = auth()->guard('merchant')->attempt([
                'phoneNumber' => $validated['phoneNumber'],
                'password' => $request->password,
            ]);

            return response()->json([
                'success' => true,
                'data' => $merchant,
                'token' => $token,
                'message' => 'Merchant created successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifiyOtpCode(Request $request) {
        try {
            $validated = $request->validate([
                'otp_code' => 'required|string|max:6',
            ]);

            $merchant = auth('merchant')->user();

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 404);
            }

            $otpCode = OtpCode::where('user_id', $merchant->id)->where('user_type', 1)->where('code', $validated['otp_code']);

            if (!$otpCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code'
                ], 400);
            }

            $otpCode->delete();
            $verifcation = Verifcation::findOrFail($merchant->verifcation_id);
            $verifcation->is_verified = true;
            $verifcation->save();

            return response()->json([
                'success' => true,
                'message' => 'OTP code sent successfully to ' ,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function login(Request $request) {
        try {
            $validated = $request->validate([
                'phoneNumber' => 'required|string',
                'password' => 'required|string|min:6',
            ]);

            if (!$token = auth()->guard('merchant')->attempt($validated)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number or password',
                ], 401);
            }

            $merchant = auth('merchant')->user();

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'merchant' => $merchant,
                    'token' => $token,
                    'token_type' => 'bearer',
                ],
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function logout(Request $request) {
        $request->user('merchant')->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function getProfile(){
        try {
            $merchant = auth('merchant')->user();
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 200);
            }

            return response()->json([
                'success' => true,
                'merchant' => $merchant
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch merchant: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function index(Request $request) {
        try {
            $request->validate([
                'name'    => 'sometimes|string',
                'phoneNumber'   => 'sometimes|email',
            ]);

            $merchant = Merchant::query()
                ->when($request->name, function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->name . '%');
                })
                ->when($request->phoneNumber, function ($q) use ($request) {
                    $q->where('phoneNumber', 'like', '%' . $request->phoneNumber . '%');
                })
                ->get();

            return response()->json([
                'success' => true,
                'merchants'  => $merchant,
                'message' => 'Merchants fetched successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admins: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id){
        try {
            $merchant = Merchant::findOrFail($id);
            
            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 200);
            }

            return response()->json([
                'success' => true,
                'merchant' => $merchant
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch merchant: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function update(Request $request){
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string',
                'commercial_place_id' => 'sometimes|nullable|integer|exists:commercial_place,id',
                'password' => 'sometimes|required|string|min:6',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $merchant = Merchant::findOrFail($request->id);

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Merchant not found'
                ], 404);
            }

            $merchant->update($validated);
            return response()->json([
                'success' => true,
                'message' => 'Merchant updated successfully',
                'merchant' => $merchant
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    public function destroy($id){
        try {
            $admin = Merchant::find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found'
                ], 404);
            }

            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Admin deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 400);
        }
    }
}