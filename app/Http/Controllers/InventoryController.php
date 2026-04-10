<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        // Get all products with their branch stock levels
        $products = \App\Models\Product::with(['category', 'branchPrices.branch'])->get();
        $branches = \App\Models\Branch::all();

        return view('inventory.index', compact('products', 'branches'));
    }

    public function updateStock(Request $request, $productId, $branchId)
    {
        $request->validate([
            'quantity' => 'required|integer',
        ]);

        $branchProduct = \App\Models\BranchProduct::where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->first();

        if ($branchProduct) {
            $branchProduct->update(['stock_quantity' => $request->quantity]);
        } else {
            \App\Models\BranchProduct::create([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'stock_quantity' => $request->quantity,
            ]);
        }

        return redirect()->back()->with('success', 'Stock updated successfully');
    }
}
