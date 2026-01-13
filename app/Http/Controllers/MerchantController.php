<?php

namespace App\Http\Controllers;

use App\Models\MerchantModels\Merchant;
use App\Models\MerchantModels\OtpCode;
use App\Models\MerchantModels\Verifcation;
use App\Traits\TransactionResponse;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MerchantController extends Controller{
    
    use TransactionResponse;
    
    public function register(Request $request){
        return $this->transactionResponseWithoutReturn(function () use ($request){
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'phoneNumber' => 'required|string|max:20|unique:merchant',
                'commercial_place_id' => 'nullable|integer|exists:commercial_places,id',
                //'password' => 'required|string|min:6',
            ]);

            $validated['password'] = 12345678 ;

            $validated['password'] = bcrypt($validated['password']);

            $merchant = Merchant::create($validated);
            
            $otpCode = OtpCode::create([
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
                //'token' => $token,
                'message' => 'Merchant created successfully',
                'otpCode' => $otpCode->code,
            ], 201);
        });
    }

    public function verifiyOtpCode(Request $request) {
        return $this->transactionResponse(function () use ($request){
            $validated = $request->validate([
                'phone_number' => 'required|string',
                'otp_code' => 'required|string|max:6',
            ]);

            $merchant = Merchant::where('phoneNumber' , $request->phone_number)->get()->first() ;

            if (!$merchant) {
                throw ValidationException::withMessages([
                    'message' => 'Merchant not found'
                ]);
            }

            $otpCode = OtpCode::where('user_id', $merchant->id)->where('code', $validated['otp_code'])->get()->first();

            if (!$otpCode) {
                throw ValidationException::withMessages([
                    'otp_code' => 'Invalid OTP code'
                ]);
            }

            $otpCode->delete();
            $verifcation = Verifcation::findOrFail($merchant->verifcation_id);
            $verifcation->is_verified = true;
            $verifcation->save();

            $token = auth()->guard('merchant')->attempt([
                'phoneNumber' => $validated['phone_number'],
                'password' => '12345678',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP code sent successfully' ,
                'token' => $token
            ], 200);
        });
    }

    public function login(Request $request) {
        return $this->transactionResponseWithoutReturn(function () use ($request){
            $validated = $request->validate([
                'phoneNumber' => 'required|string',
                'password' => 'sometimes|string',
            ]);

            $validated['password'] = 12345678 ;
            if (!$token = auth()->guard('merchant')->attempt($validated)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number or password',
                ], 401);
            }

            $merchant = auth('merchant')->user();

            /*$otpCode = OtpCode::create([
                'code' => rand(100000, 999999),
                'user_id' => $merchant->id,
            ]);*/

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'merchant' => $merchant,
                'token' => $token,
                //'otpCode' => $otpCode->code,
            ], 200);
        });
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
        return $this->transactionResponseWithoutReturn(function () use ($request){
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
                Throw ValidationException::withMessages([
                    'message' => 'Merchant not found'
                ]);
            }

            $merchant->update($validated);
            return response()->json([
                'success' => true,
                'message' => 'Merchant updated successfully',
                'merchant' => $merchant
            ], 200);
        });
    }

    public function destroy($id){
        try {
            $merchant = Merchant::find($id);

            if (!$merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found'
                ], 404);
            }

            $merchant->delete();

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