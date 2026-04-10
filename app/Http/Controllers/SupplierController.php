<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = \App\Models\Supplier::all();
        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => 'nullable|string',
            'name_en' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        // Check for accounting configuration
        $config = \App\Models\AccountingEntityConfig::where('entity_type', 'supplier')->first();
        if (!$config) {
            return back()->withErrors(['accounting' => 'Accounting configuration for "supplier" missing. Please add it first in Entity Configs.'])->withInput();
        }

        if (empty($request->name_ar) && empty($request->name_en)) {
            return back()->withErrors(['name' => 'At least one name (Arabic or English) is required.'])->withInput();
        }

        \App\Models\Supplier::create($request->only(['name_ar', 'name_en', 'email', 'phone', 'address']));
        return redirect()->back()->with('success', 'Supplier created successfully');
    }

    public function update(Request $request, \App\Models\Supplier $supplier)
    {
        $request->validate([
            'name_ar' => 'nullable|string',
            'name_en' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        if (empty($request->name_ar) && empty($request->name_en)) {
            return back()->withErrors(['name' => 'At least one name (Arabic or English) is required.'])->withInput();
        }

        $supplier->update($request->only(['name_ar', 'name_en', 'email', 'phone', 'address']));
        return redirect()->back()->with('success', 'Supplier updated successfully');
    }

    public function destroy(\App\Models\Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->back()->with('success', 'Supplier deleted successfully');
    }
}
