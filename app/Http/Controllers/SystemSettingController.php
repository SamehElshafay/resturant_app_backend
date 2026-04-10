<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Models\SystemSetting;

class SystemSettingController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        $settings = SystemSetting::where('group', 'tokens')->get()->pluck('value', 'key');
        
        return view('system.settings', compact('roles', 'settings'));
    }

    public function updateTokenSettings(Request $request)
    {
        $request->validate([
            'role_lifetimes' => 'nullable|array',
            'role_lifetimes.*' => 'nullable|integer|min:1',
            'unlimited' => 'nullable|array',
        ]);

        foreach (Role::all() as $role) {
            $isUnlimited = isset($request->unlimited[$role->id]);
            
            if ($isUnlimited) {
                $role->token_lifetime_minutes = null;
            } else {
                $role->token_lifetime_minutes = $request->role_lifetimes[$role->id] ?? 1440;
            }
            
            $role->save();
        }

        return redirect()->back()->with('success', __('Token expiration settings updated successfully.'));
    }
}
