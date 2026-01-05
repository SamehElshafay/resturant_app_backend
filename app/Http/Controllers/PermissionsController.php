<?php

namespace App\Http\Controllers;

use App\Models\Admin\Permissions;
use Illuminate\Http\Request;

class PermissionsController extends Controller
{
    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
            ]);
            
            $permission = Permissions::create($validated);
            return response()->json([
                'success' => true,
                'message' => 'permission created successfully' ,
            ], 200);

        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'permission not created'
            ], 200);
        }
    }

    public function index() {
        try {
            $permissions = Permissions::all();
            return response()->json([
                'success' => true,
                'message' => 'permission fetched successfully' ,
                'data' => $permissions
            ], 200);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'permission not fetched'
            ], 200);
        }
    }
    
    public function show($id) {
        try {
            $permission = Permissions::findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'permission fetched successfully' ,
                'data' => $permission
            ], 200);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'permission not fetched'
            ], 200);
        }
    }

    public function update(Request $request) {
        try {
            $validated = $request->validate([
                'id' => 'required|integer',
                'name' => 'required|string',
            ]);
            
            $permission = Permissions::findOrFail($request->id);
            $permission->update($validated);
            return response()->json([
                'success' => true,
                'message' => 'permission updated successfully' ,
            ], 200);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'permission not updated'
            ], 200);
        }
    }

    public function destroy($id) {
        try {
            $permission = Permissions::findOrFail($id);
            $permission->delete();
            return response()->json([
                'success' => true,
                'message' => 'permission deleted successfully' ,
            ], 200);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'permission not deleted'
            ], 200);
        }
    }
}