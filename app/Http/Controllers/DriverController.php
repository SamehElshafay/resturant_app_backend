<?php

namespace App\Http\Controllers;

use App\Models\DriverModels\Driver;
use App\Models\DriverModels\DriverLocation;
use App\Services\ImagesServices;
use App\Traits\TransactionResponse;
use Illuminate\Http\Request;

class DriverController extends Controller {
    use TransactionResponse ;
    
    public function login(Request $request){
        return $this->transactionResponseWithoutReturn(function() use ($request){
            $validation = $request->validate([
                'phone_number' => 'required|string',
                'password' => 'required|string',
            ]);

            $credentials = [
                'phone_number' => $validation['phone_number'],
                'password' => $validation['password'],
            ];

            
            if (!$token = auth()->guard('driver')->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone_number or password',
                ], 401);
            }

            //$user = auth('driver')->user();

            return response()->json([
                'success' => true,
                'token' => $token,
            ]);

        });
    }

    public function addDriver(Request $request){
        return $this->transactionResponseWithoutReturn(function() use ($request){
            $validation = $request->validate([
                'name' => 'required|string',
                'country_code' => 'required|string',
                'phone_number' => 'required|string',
                'password' => 'required|string',
            ]);

            $password = $validation['password'] ;
            
            $driver = Driver::create([
                'name' => $validation['name'],
                'password' => bcrypt($validation['password']),
                'phone_number' => $validation['phone_number'],
                'country_code' => $validation['country_code'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'driver added successfully',
                'data' => $driver,
            ]);
        });
    }

    public function updateDriver(Request $request){
        return $this->transactionResponseWithoutReturn(function() use ($request){
            $validation = $request->validate([
                'id' => 'required|integer|exists:driver,id',
                'image_url' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
                'name' => 'nullable|string',
                'phone_number' => 'nullable|string',
                'active' => 'nullable|string',
            ]);
            
            // $driver = auth()->guard('driver')->user();
            $driver = Driver::findOrFail($validation['id']);

            if($request->hasFile('image_url')){
                if($driver->image_url){
                    ImagesServices::deleteImage('driver_images', $driver->image_url);
                }
                $image_path = ImagesServices::uploadImage('driver_images', $request->file('image_url'));
                $validation['image_url'] = $image_path ;
            }

            $driver->update($validation);
            return response()->json([
                'success' => true,
                'message' => 'driver updated successfully',
                'data' => $driver,
            ]);
        });
    }

    public function changePassword(Request $request){
        return $this->transactionResponseWithoutReturn(function() use ($request){
            $validation = $request->validate([
                'old_password' => 'required|string',
                'new_password' => 'required|string|different:old_password',
            ]);

            $driver = auth()->guard('driver')->user();

            if (!auth()->guard('driver')->attempt(['phone_number' => $driver->phone_number, 'password' => $validation['old_password']])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Old password is incorrect',
                ], 401);
            }

            $driver->password = bcrypt($validation['new_password']);
            $driver->save();

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully',
            ]);
        });
    }

    public function show($id){
        return $this->transactionResponseWithoutReturn(function() use ($id){
            $driver = Driver::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $driver,
            ]);
        });
    }

    public function index(){
        return $this->transactionResponseWithoutReturn(function() {
            $drivers = Driver::all();
            return response()->json([
                'success' => true,
                'data' => $drivers,
            ]);
        });
    }

    public function getProfile(){
        return $this->transactionResponseWithoutReturn(function() {
            $driver = auth()->guard('driver')->user();
            return response()->json([
                'success' => true,
                'data' => $driver,
            ]);
        });
    }

    public function logout(){
        return $this->transactionResponseWithoutReturn(function() {
            auth()->guard('driver')->logout();
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
        });
    }

    public function update(Request $request){
        return $this->transactionResponseWithoutReturn(function() use ($request){
            $validation = $request->validate([
                'image_url' => 'nullable|file|mimes:jpg,jpeg,png',
                //'name' => 'nullable|string',
                //'phone_number' => 'nullable|string',
                'driver_status' => 'nullable|string|in:active,inactive' ,
            ]);
            
            $driver = auth()->guard('driver')->user();

            if($request->hasFile('image_url')){
                if($driver->image_url){
                    ImagesServices::deleteImage('driver_images', $driver->image_url);
                }
                $image_path = ImagesServices::uploadImage('driver_images', $request->file('image_url'));
                $validation['image_url'] = $image_path ;
            }

            $driver->update($validation);
            return response()->json([
                'success' => true,
                'message' => 'driver updated successfully',
                'data' => $driver,
            ]);
        });
    }
    
    ///////////////////////////////////////// location
    
    public function allDriversLocation(Request $request) {
        $query = DriverLocation::query();

        if ($request->filled('driver_id')) {
            $query->where('driver_id', $request->driver_id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(10),
        ]);
    }

    public function locateDriver(Request $request) {
        $validated = $request->validate([
            'driver_id' => 'required|integer|exists:drivers,id',
            'lat'       => 'required|numeric',
            'lng'       => 'required|numeric',
        ]);

        $location = DriverLocation::create($validated);

        return response()->json([
            'success' => true,
            'data' => $location,
        ], 201);
    }

    public function getDriverLocation($id) {
        $location = DriverLocation::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }

    public function updateDriverLocation(Request $request, $id) {
        $location = DriverLocation::findOrFail($id);

        $validated = $request->validate([
            'lat' => 'sometimes|required|numeric',
            'lng' => 'sometimes|required|numeric',
        ]);

        $location->update($validated);

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }

    public function destroyDriverLocation($id) {
        $location = DriverLocation::findOrFail($id);
        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Driver location deleted successfully',
        ]);
    }

    public function lastLocation($driverId) {
        $location = DriverLocation::where('driver_id', $driverId)->latest()->first();

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }
}