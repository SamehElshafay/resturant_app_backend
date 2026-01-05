<?php

namespace App\Http\Controllers;

use App\Models\admin\Role;
use Illuminate\Http\Request;

class RolesController extends Controller
{
    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required|string',
                'description' => 'required|string',
                'permissions_ids' => 'required|array',
                'permissions_ids.*' => 'integer|exists:permissions,id',
            ]);

            $role = Role::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
            ]);
            
            $role->permissions()->sync($validated['permissions_ids']);

            return response()->json([
                'success' => true,
                'message' => 'role created successfully' ,
            ], 200);

        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'role not created'  . $e->getMessage()
            ], 500);
        }
    }

    public function index() {
        try{
            $roles = Role::all();
            return response()->json([
                'success' => true,
                'message' => 'role fetched successfully',
                'roles' => $roles
            ], 200);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'role not created'
            ], 200);
        }
    }
    
    public function update(Request $request){
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:roles,id',
                'name' => 'sometimes|required|string',
                'description' => 'sometimes|required|string',
                'permissions_ids' => 'sometimes|array',
                'permissions_ids.*' => 'integer|exists:permissions,id',
            ]);

            $role = Role::findOrFail($validated['id']);
            
            $role->update(
                collect($validated)->only(['name', 'description'])->toArray()
            );

            if (isset($validated['permissions_ids'])) {
                $role->permissions()->sync($validated['permissions_ids']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not updated: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request) {
        try {
            $role = Role::findOrFail($request->id);
            $role->permissions()->detach();
            $role->delete();
            return response()->json([
                'success' => true,
                'message' => 'role deleted successfully' ,
            ], 200);

        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'role not deleted' . $e->getMessage()
            ], 200);
        }
    }

    public function show($id) {
        try {
            $role = Role::findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'role fetched successfully',
                'role' => $role
            ], 200);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'role not fetched'
            ], 200);
        }
    }
}