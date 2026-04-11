<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;
use App\Models\Role;
use App\Models\AccountingEntityConfig;

class DriverController extends Controller
{
    public function index()
    {
        // Filter users who have the 'driver' role
        $drivers = User::whereHas('roles', function ($q) {
            $q->where('name', 'driver');
        })->with(['roles', 'branch'])->latest()->get();
        
        $branches = Branch::all();
        
        return view('drivers.index', compact('drivers', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'branch_id' => 'required|exists:branches,id', // Branch is required as per user request
            'phone' => 'nullable|string',
        ]);

        // Drivers must have a 'driver' role in the system
        $roleName = 'driver';
        
        // Check for accounting configuration for the driver role
        $config = AccountingEntityConfig::where('entity_type', $roleName)->first();
        if (!$config) {
            $err = app()->getLocale() == 'ar' ? "يرجى إضافة إعدادات الحساب التلقائية للنوع \"{$roleName}\" أولاً" : "Please add accounting config for \"{$roleName}\" first.";
            return back()->withErrors(['role' => $err])->withInput();
        }

        try {
            $role = Role::where('name', $roleName)->first();
            if (!$role) {
                // Create role if it doesn't exist (failsafe)
                $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
            }

            $driver = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'branch_id' => $request->branch_id,
                'role_id' => $role->id,
                'phone' => $request->phone,
                'is_active' => true,
            ]);

            $driver->assignRole($roleName);

            return redirect()->back()->with('success', app()->getLocale() == 'ar' ? 'تم إضافة السائق بنجاح' : 'Driver created successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', (app()->getLocale() == 'ar' ? 'فشل إضافة السائق' : 'Failed to create driver') . ': ' . $e->getMessage());
        }
    }

    public function update(Request $request, User $driver)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $driver->id,
            'password' => 'nullable|min:6',
            'branch_id' => 'required|exists:branches,id',
            'phone' => 'nullable|string',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'branch_id' => $request->branch_id,
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $driver->update($data);

        return redirect()->back()->with('success', 'Driver updated successfully');
    }

    public function destroy(User $driver)
    {
        // Check if driver has associated orders
        $hasOrders = \App\Models\Order::where('driver_id', $driver->id)->exists();
        
        // Check if driver has ledger entries
        $hasLedgers = \App\Models\Ledger::where('user_id', $driver->id)->exists();

        if ($hasOrders || $hasLedgers) {
            $errorMsg = app()->getLocale() == 'ar' 
                ? 'لا يمكن حذف السائق لوجود عمليات مرتبطة به. يمكنك تعطيل الحساب بدلاً من الحذف.' 
                : 'Cannot delete driver because there are associated operations. You can deactivate the account instead.';
            
            return redirect()->back()->with('error', $errorMsg);
        }

        $driver->delete();
        return redirect()->back()->with('success', app()->getLocale() == 'ar' ? 'تم حذف السائق بنجاح' : 'Driver deleted successfully');
    }
}
