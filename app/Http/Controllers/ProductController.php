<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->get('category_id');
        
        $products = \App\Models\Product::with('category', 'branchPrices.branch')
            ->when($categoryId, function ($q) use ($categoryId) {
                return $q->where('category_id', $categoryId);
            })
            ->get();
            
        $categories = \App\Models\Category::all();
        $branches = \App\Models\Branch::all();
        return view('products.index', compact('products', 'categories', 'branches'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'image' => 'nullable|image',
            'names' => 'required|array|min:1',
            'names.*' => 'required|string',
            'prices' => 'required|array|min:1',
            'prices.*' => 'required|numeric',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $createdCount = 0;
        foreach ($request->names as $index => $name) {
            $price = $request->prices[$index] ?? 0;
            
            $product = \App\Models\Product::create([
                'name' => $name,
                'category_id' => $request->category_id,
                'base_purchase_price' => $price,
                'image' => $imagePath,
            ]);

            // If branch prices were provided (legacy or global), we apply them to each variation
            if ($request->has('branch_prices')) {
                foreach ($request->branch_prices as $branchId => $bPrice) {
                    if ($bPrice !== null && $bPrice > 0) {
                        \App\Models\BranchProduct::create([
                            'product_id' => $product->id,
                            'branch_id' => $branchId,
                            'price' => $bPrice
                        ]);
                    }
                }
            }
            $createdCount++;
        }

        $msg = app()->getLocale() == 'ar' 
            ? "تم إضافة {$createdCount} منتجات بنجاح" 
            : "Successfully added {$createdCount} products";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Product $product)
    {
        $request->validate([
            'name' => 'required',
            'category_id' => 'required',
            'base_purchase_price' => 'required|numeric',
            'branch_prices' => 'nullable|array',
        ]);

        $data = $request->except(['image', 'branch_prices']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        // Update branch prices
        if ($request->has('branch_prices')) {
            // Delete old prices
            \App\Models\BranchProduct::where('product_id', $product->id)->delete();

            // Add new prices
            foreach ($request->branch_prices as $branchId => $price) {
                if ($price !== null && $price > 0) {
                    \App\Models\BranchProduct::create([
                        'product_id' => $product->id,
                        'branch_id' => $branchId,
                        'price' => $price
                    ]);
                }
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => __('messages.product_updated_success')]);
        }

        return redirect()->back()->with('success', __('messages.product_updated_success'));
    }

    public function destroy(\App\Models\Product $product)
    {
        $product->delete();
        return redirect()->back()->with('success', 'Product deleted successfully');
    }

    public function getRecipe(\App\Models\Product $product)
    {
        $product->load(['recipe.ingredients.ingredient', 'recipe.ingredients.childProduct']);

        if (!$product->recipe) {
            return response()->json(['has_recipe' => false]);
        }

        $ingredients = $product->recipe->ingredients->map(function ($item) {
            $name = '';
            $type = '';
            if ($item->ingredient) {
                $name = $item->ingredient->name_ar ?? $item->ingredient->name_en ?? $item->ingredient->name;
                $type = 'Raw Material';
            } elseif ($item->childProduct) {
                $name = $item->childProduct->name_ar ?? $item->childProduct->name_en ?? $item->childProduct->name;
                $type = 'Sub-Product';
            }

            return [
                'name' => $name,
                'quantity' => floatval($item->quantity),
                'unit' => $item->unit,
                'type' => $type
            ];
        });

        return response()->json([
            'has_recipe' => true,
            'ingredients' => $ingredients
        ]);
    }
}
