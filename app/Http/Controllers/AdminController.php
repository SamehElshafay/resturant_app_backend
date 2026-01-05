<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

#[Middleware('permission:view_post', only: ['index'])]
#[Middleware('permission:create_post', only: ['store'])]
#[Middleware('permission:delete_post', only: ['destroy'])]

class AdminController extends Controller{
    
    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:6' ,
                'role_id' => 'required|integer',
            ]);
            
            $validated['password'] = bcrypt($validated['password']);
            $admin = Admin::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Admin created successfully'
            ], 200);

        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Admin not created ' . $e->getMessage(),
            ], 200);
        }
    }

    public function login(Request $request){
        try {
            $validatedData = Validator::make($request->all(), [
                'email' => 'required|string',
                'password' => 'required|string|min:6',
            ])->validate();

            $credentials = [
                'email' => $validatedData['email'],
                'password' => $validatedData['password'],
            ];

            if (!$token = auth()->guard('admin')->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password',
                ], 401);
            }

            $admin = auth('admin')->user();

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'admin'    => $admin,
                'token'   => $token,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Login failed: " . $e->getMessage(),
            ], 400);
        }
    }

    public function logout() {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }


    public function index(Request $request)
    {
        try {
            $request->validate([
                'name'    => 'sometimes|string',
                'email'   => 'sometimes|email',
                'role_id' => 'sometimes|integer|exists:roles,id',
            ]);

            $admins = Admin::with('role')
                ->when($request->name, function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->name . '%');
                })
                ->when($request->email, function ($q) use ($request) {
                    $q->where('email', 'like', '%' . $request->email . '%');
                })
                ->when($request->role_id, function ($q) use ($request) {
                    $q->where('role_id', $request->role_id);
                })
                ->get();

            return response()->json([
                'success' => true,
                'admins'  => $admins,
                'message' => 'Admins fetched successfully'
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
            $admin = Admin::with('role')->find($id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found'
                ], 200);
            }

            return response()->json([
                'success' => true,
                'admin' => $admin
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch admin: ' . $e->getMessage(),
            ], 200);
        }
    }

    public function update(Request $request){
        try {
            $validated = $request->validate([
                'id' => 'required|integer',
                'name' => 'sometimes|required|string',
                'email' => 'sometimes|required|email',
                'password' => 'sometimes|required|string|min:6',
                'role_id' => 'sometimes|required|integer|exists:roles,id',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = bcrypt($validated['password']);
            }

            $admin = Admin::findOrFail($request->id);

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin not found'
                ], 404);
            }

            $admin->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Admin updated successfully',
                'admin' => $admin
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
            $admin = Admin::find($id);

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

    //$user = JWTAuth::parseToken()->authenticate();
}