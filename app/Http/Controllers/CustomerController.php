<?php

namespace App\Http\Controllers;

use App\Models\CustomerModel\Cart;
use App\Models\CustomerModel\Customer;
use App\Models\CustomerModel\OtpCodeCustomer;
use App\Models\CustomerModel\Wallet;
use App\Models\MerchantModels\Verifcation;
use App\Services\SMS;
use App\Traits\TransactionResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller {
    use TransactionResponse;
    
    public function register(Request $request){
        $validated = $request->validate([
            'first_name'   => 'required|string',
            'last_name'    => 'required|string',
            'phone_number' => 'required|string|unique:customer,phone_number',
        ]);

        return $this->transactionResponseWithoutReturn(function () use ($validated, $request) {

            $rawPassword = "12345678";
            $validated['password'] = bcrypt($rawPassword);

            $user = Customer::create($validated);

            Cart::create(['customer_id' => $user->id]);

            $code = $this->sendOtpCode($validated['phone_number'] , $user->id , 'send_otp');

            $verification = Verifcation::create([
                'is_verified' => false,
            ]);

            $user->update(['verifcation_id' => $verification->id]);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            $token = auth()->guard('customer')->attempt([
                'phone_number' => $user->phone_number,
                'password' => $rawPassword,
            ]);

            return [
                'success' => true,
                'message' => 'otp code sent successfully',
                //'token' => $token,
                //'code' => $code->code
            ];
        });
    }

    public function signUp(Request $request){
        $validated = $request->validate([
            'first_name'   => 'required|string',
            'last_name'    => 'required|string',
            'phone_number' => 'required|string|unique:customer,phone_number',
            'password'     => 'required|min:6',
        ]);
        
        return $this->transactionResponseWithoutReturn(function () use ($validated, $request) {

            $rawPassword = $request->password;
            $validated['password'] = bcrypt($rawPassword);

            $user = Customer::create($validated);

            Cart::create(['customer_id' => $user->id]);

            $code = $this->sendOtpCode($validated['phone_number'] , $user->id , 'send_otp');
            
            $verification = Verifcation::create([
                'is_verified' => false,
            ]);

            $user->update(['verifcation_id' => $verification->id]);

            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            $token = auth()->guard('customer')->attempt([
                'phone_number' => $user->phone_number,
                'password' => $rawPassword,
            ]);

            return [
                'success' => true,
                'message' => 'otp code sent successfully',
                //'token' => $token,
            ];
        });
    }

    public function login(Request $request){
        try {
            $validatedData = Validator::make($request->all(), [
                'phone_number' => 'required|string',
            ])->validate();
            
            $credentials = [
                'phone_number' => $validatedData['phone_number'],
                'password' => "12345678",
            ];

            
            if (!$token = auth()->guard('customer')->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone_number or password',
                ], 401);
            }
            

            $user = auth('customer')->user();
            
            $code = $this->sendOtpCode($validatedData['phone_number'] , $user->id , 'send_otp');
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                //'user'    => $user,
                //'token'   => $token,
                'code' => $code
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Login failed: " . $e->getMessage(),
            ], 400);
        }
    }

    public function getUserProfile(){
        try {
            $user = auth('customer')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'user not found'
                ], 200);
            }

            return response()->json([
                'success' => true,
                'user' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function updateCustomerProfile(Request $request){
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string',
                'last_name'  => 'sometimes|string',
                'image_path' => 'sometimes|nullable|file|mimes:jpg,jpeg,png,webp|max:2048',

                'addresses' => 'sometimes|array',

                'addresses.*.type' => 'required|in:add,update,delete',
                'addresses.*.id'   => 'required_if:addresses.*.type,update,delete|exists:address,id',

                'addresses.*.name'              => 'required_if:addresses.*.type,add,update|string',
                'addresses.*.city'              => 'required_if:addresses.*.type,add,update|string',
                'addresses.*.street_name'       => 'nullable|string',
                'addresses.*.building_number'   => 'nullable|string',
                'addresses.*.floor_number'      => 'nullable|string',
                'addresses.*.apartment_number'  => 'nullable|string',

                'addresses.*.lat' => 'nullable|numeric',
                'addresses.*.lng' => 'nullable|numeric',

                'addresses.*.defaultCase' => 'nullable|boolean',
            ]);
            
            $customer = auth('customer')->user();

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            if ($request->hasFile('image_path')) {
                $path = $request->image_path->store('customers', 'public');
                $validated['image_path'] = $path;
            }

            $customer->update($validated);

            if ($request->has('addresses')) {

                foreach ($request->addresses as $item) {
                    if (!empty($item['defaultCase'])) {
                        $customer->address()->update(['defaultCase' => false]);
                    }

                    switch ($item['type']) {

                        case 'add':
                            $customer->address()->create([
                                'name'             => $item['name'],
                                'city'             => $item['city'],
                                'street_name'      => $item['street_name'] ?? null,
                                'building_number'  => $item['building_number'] ?? null,
                                'floor_number'     => $item['floor_number'] ?? null,
                                'apartment_number' => $item['apartment_number'] ?? null,
                                'lat'              => $item['lat'] ?? null,
                                'lng'              => $item['lng'] ?? null,
                                'defaultCase'      => $item['defaultCase'] ?? false,
                            ]);
                            break;

                        case 'update':
                            $customer->address()
                                ->where('id', $item['id'])
                                ->update([
                                    'name'             => $item['name'],
                                    'city'             => $item['city'],
                                    'street_name'      => $item['street_name'] ?? null,
                                    'building_number'  => $item['building_number'] ?? null,
                                    'floor_number'     => $item['floor_number'] ?? null,
                                    'apartment_number' => $item['apartment_number'] ?? null,
                                    'lat'              => $item['lat'] ?? null,
                                    'lng'              => $item['lng'] ?? null,
                                    'defaultCase'      => $item['defaultCase'] ?? false,
                                ]);
                            break;

                        case 'delete':
                            $customer->address()
                                ->where('id', $item['id'])
                                ->delete();
                            break;
                    }
                }
            }

            DB::commit();
            return [
                'success' => true,
                'data' => $customer
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'data' => $e->getMessage()
            ];
        }
    }

    public function verifiyOtpCode(Request $request) {
        try {
            $validated = $request->validate([
                'otp_code' => 'required|string|max:6',
                'phone_number' => 'required|string',
            ]);

            //$customer = auth('customer')->user();
            $customer = Customer::where('phone_number', $validated['phone_number'])->first() ;
            
            $credentials = [
                'phone_number' => $validated['phone_number'],
                'password' => "12345678",
            ];

                
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 400);
            }

            $otpCode = OtpCodeCustomer::where('user_id', $customer->id)->where('code', $validated['otp_code']);

            if (!$otpCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code'
                ], 400);
            }

           // return 's';
            $otpCode->delete();
            $token = auth()->guard('customer')->attempt($credentials) ;
            /*$verifcation = Verifcation::findOrFail($customer->verifcation_id);
            $verifcation->is_verified = true;
            $verifcation->save();*/

            $sms = new SMS();
            $sms->verify_sms_operation($customer->id);

            return response()->json([
                'success' => true,
                'message' => 'OTP code sent successfully to ' ,
                'token' => $token
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 400);
        }
    }

    public function changePhoneNumber(Request $request){
        $validated = $request->validate([
            'phone_number' => 'required|string|unique:customer,phone_number',
        ]);


        return $this->transactionResponseWithoutReturn(function () use ($validated, $request) {
            $user = auth('customer')->user();
            $user->phone_number = $validated['phone_number'];
            $user->save();
            
            $verifcation = Verifcation::findOrFail($user->verifcation_id);
            $verifcation->is_verified = false ;
            $verifcation->save();

            $code = $this->sendOtpCode($validated['phone_number'] , $user->id , 'send_otp');

            /*$otp = OtpCodeCustomer::create([
                'code' => rand(100000, 999999),
                'user_id' => $user->id,
            ]);*/

            return [
                'success' => true,
                'message' => 'otp code sent successfully',
                //'otp' => $otp->code
            ];
        });
    }

    public function verifiyOtpCodeChangePhoneNumber(Request $request) {
        try {
            $validated = $request->validate([
                'otp_code' => 'required|string|max:6',
            ]);
            
            $customer = auth('customer')->user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            $otpCode = OtpCodeCustomer::where('user_id', $customer->id)->where('code', $validated['otp_code']);

            if (!$otpCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code'
                ], 400);
            }

            $otpCode->delete();
            $verifcation = Verifcation::findOrFail($customer->verifcation_id);
            $verifcation->is_verified = true;
            $verifcation->save();

            $sms = new SMS();
            $sms->verify_sms_operation($customer->id);
            
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

    public function resendOtpCode(Request $request){
        return $this->transactionResponseWithoutReturn(function () use ($request) {
            
            $validated = $request->validate([
                'phone_number' => 'required|string',
            ]);

            $user = Customer::where('phone_number', $validated['phone_number'])->first() ;

            if (!$user) {
                throw new Exception('Customer not found');
            }

            OtpCodeCustomer::where('user_id' , $user->id)->delete();

            $code = $this->sendOtpCode($validated['phone_number'] , $user->id , 'resend_otp');

            
            /*$otp = OtpCodeCustomer::create([
                'code' => rand(100000, 999999),
                'user_id' => $user->id,
            ]);*/

            return [
                'success' => true,
                'message' => 'otp code sent successfully',
                //'otp' => $code
            ];
        });
    }

    private function sendOtpCode($phone_number , $user_id , $operation_type){
        $code = rand(100000, 999999) ;
        $sms = new SMS();
        
        /*$result = $sms->sendSms(
            $phone_number,
            'Your NOW App OTP is: ' . $code . '. Do not share this code.' ,
            $user_id ,
            $operation_type
        );*/

        /*if (!$result['success']) {
            Throw new Exception('Failed to send SMS: ' . $result['message']);
        }*/

        $code = OtpCodeCustomer::create([
            'code' => $code,
            'user_id' => $user_id,
        ]);
        
        return $code;
    }
}