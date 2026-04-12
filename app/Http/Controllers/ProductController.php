<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $categoryId = $request->get('category_id');
        
        $products = \App\Models\Product::with('category', 'branchPrices.branch', 'recipe')
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
            'name' => 'required',
            'category_id' => 'required',
            'base_purchase_price' => 'required|numeric',
            'image' => 'nullable|image',
            'branch_prices' => 'nullable|array',
            'branch_prices.*' => 'nullable|numeric',
        ]);

        $data = $request->except(['image', 'branch_prices']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = \App\Models\Product::create($data);

        // Save branch-specific prices
        if ($request->has('branch_prices')) {
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
            return response()->json(['success' => true, 'message' => __('messages.product_added_success')]);
        }

        return redirect()->back()->with('success', __('messages.product_added_success'));
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

    public function bulkUpdatePrices(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'updates' => 'required|array',
            'updates.*' => 'array',
        ]);

        foreach ($request->updates as $productId => $branchPrices) {
            foreach ($branchPrices as $branchId => $price) {
                if ($price === null || $price === '') {
                    \App\Models\BranchProduct::where('product_id', $productId)
                        ->where('branch_id', $branchId)
                        ->delete();
                } else {
                    \App\Models\BranchProduct::updateOrCreate(
                        ['product_id' => $productId, 'branch_id' => $branchId],
                        ['price' => $price]
                    );
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Bulk prices updated successfully']);
    }

    public function bulkSyncRecipes(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'source_product_id' => 'required|exists:products,id',
            'target_product_ids' => 'required|array',
            'target_product_ids.*' => 'exists:products,id',
        ]);

        $sourceProduct = \App\Models\Product::with('recipe.ingredients')->findOrFail($request->source_product_id);
        
        if (!$sourceProduct->recipe) {
            return response()->json(['success' => false, 'message' => 'Source product has no recipe'], 422);
        }

        foreach ($request->target_product_ids as $targetId) {
            // Skip if target is same as source
            if ($targetId == $request->source_product_id) continue;

            $targetProduct = \App\Models\Product::findOrFail($targetId);
            
            // 1. Delete old recipe if exists
            if ($targetProduct->recipe) {
                $targetProduct->recipe->ingredients()->delete();
                $targetProduct->recipe->delete();
            }

            // 2. Clone the recipe record
            $newRecipe = $sourceProduct->recipe->replicate();
            $newRecipe->product_id = $targetId;
            $newRecipe->save();

            // 3. Clone ingredients
            foreach ($sourceProduct->recipe->ingredients as $ingredient) {
                $newIngredient = $ingredient->replicate();
                $newIngredient->recipe_id = $newRecipe->id;
                $newIngredient->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Recipe synced successfully to ' . count($request->target_product_ids) . ' products']);
    }
}
