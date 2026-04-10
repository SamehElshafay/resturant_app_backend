<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index()
    {
        // Performance Engineering: Eager load roles (Spatie), role (relation), and branch
        $employees = \App\Models\User::with(['roles', 'role', 'branch'])->latest()->get();
        $branches = \App\Models\Branch::all();
        return view('employees.index', compact('employees', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'branch_id' => 'nullable|exists:branches,id',
            'role' => 'required|string|exists:roles,name',
            'pin' => 'nullable|digits:4|unique:users,pin',
        ]);

        // Check for accounting configuration for the role
        $config = \App\Models\AccountingEntityConfig::where('entity_type', $request->role)->first();
        if (!$config) {
            $err = app()->getLocale() == 'ar' ? "يرجى إضافة إعدادات الحساب التلقائية لـ \"{$request->role}\" أولاً" : "Please add accounting config for \"{$request->role}\" first.";
            if ($request->expectsJson())
                return response()->json(['success' => false, 'message' => $err], 422);
            return back()->withErrors(['role' => $err])->withInput();
        }

        try {
            // Find the role to get its ID
            $role = \App\Models\Role::where('name', $request->role)->first();

            $pin = $request->pin;
            if ($request->role == 'cashier' && !$pin) {
                $pin = $this->createUniquePin();
            }

            $employee = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'branch_id' => $request->branch_id,
                'role_id' => $role->id,
                'pin' => $request->role == 'cashier' ? $pin : null,
                'is_active' => true,
            ]);

            // Dual system compatibility
            $employee->assignRole($request->role);

            $message = app()->getLocale() == 'ar' ? 'تم إضافة الموظف بنجاح' : 'Employee created successfully';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            $errorMessage = app()->getLocale() == 'ar' ? 'فشل إضافة الموظف' : 'Failed to create employee';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'details' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', $errorMessage . ': ' . $e->getMessage());
        }
    }

    public function update(Request $request, \App\Models\User $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->id,
            'pin' => 'required_if:role,cashier|nullable|digits:4|unique:users,pin,' . $employee->id,
            'password' => 'nullable|min:6',
            'branch_id' => 'nullable|exists:branches,id',
            'role' => 'required|string|exists:roles,name',
        ]);

        $role = \App\Models\Role::where('name', $request->role)->first();

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'branch_id' => $request->branch_id,
            'role_id' => $role->id, // Update the role_id column
            'pin' => $request->role == 'cashier' ? $request->pin : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $employee->update($data);

        // Update Spatie roles
        $employee->syncRoles([$request->role]);

        return redirect()->back()->with('success', 'Employee updated successfully');
    }

    public function destroy(\App\Models\User $employee)
    {
        $employee->delete();
        return redirect()->back()->with('success', 'Employee deleted successfully');
    }

    public function generatePin()
    {
        $pin = $this->createUniquePin();
        return response()->json(['pin' => $pin]);
    }

    private function createUniquePin()
    {
        do {
            $pin = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (\App\Models\User::where('pin', $pin)->exists());

        return $pin;
    }
}
