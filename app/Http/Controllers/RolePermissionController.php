<?php

namespace App\Http\Controllers;

use App\Models\Admin\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Exceptions;

class RolePermissionController extends Controller {
    public function linkRolePermision(Request $request) {
        try{
            $request->validate([
                'role_id' => 'required|integer|exists:roles,id',
                'permission_id' => 'required|array',
                'permission_id.*' => 'integer|exists:permissions,id',
            ]);

            $role = Role::findOrFail($request->role_id);

            $role->permissions()->sync($request->permission_id);

            return response()->json([
                'success' => true,
                'message' => 'Link Role Permission Success',
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Link Role Permission Failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}