<?php

namespace App\Http\Controllers;

use App\Models\ProductsModel\Product;
use App\Models\ProductsModel\ProductModifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductModifiersController extends Controller {
    public function index() {
        try {
            $data = DB::table('product_modifiers')
                ->join('product', 'product_modifiers.product_id', '=', 'product.id')
                ->join('modifiers', 'product_modifiers.modifier_id', '=', 'modifiers.id')
                ->select(
                    'product_modifiers.*',
                    'product.name as product_name',
                    'modifiers.name as modifier_name'
                )
                ->get();

            return ['success' => true, 'data' => $data];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }

    public function show($product_id) {
        try {
            $product = Product::with('modifiers')->findOrFail($product_id);
            return [
                'success' => true,
                'data' => $product->modifiers
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $e->getMessage()
            ];
        }
    }

    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'product_id'   => 'required|integer|exists:product,id',
                'modifier_id' => 'required|integer|exists:modifiers,id',
            ]);

            Product::findOrFail($validated['product_id']);
            ProductModifier::create($validated);

            return [
                'success' => true ,
                'message' => 'Modifier added to product successfully',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function update(Request $request, $product_id) {
        try {
            $validated = $request->validate([
                'modifier_ids' => 'required|array',
                'modifier_ids.*' => 'integer|exists:modifiers,id',
            ]);

            $product = Product::findOrFail($product_id);
            $product->modifiers()->sync($validated['modifier_ids']);

            return [
                'success' => true,
                'data' => $product->modifiers
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'data' => $e->getMessage()
            ];
        }
    }

    public function destroy(Request $request, $product_id) {
        try {
            $validated = $request->validate([
                'modifier_ids' => 'required|array',
                'modifier_ids.*' => 'integer|exists:modifiers,id',
            ]);

            $product = Product::findOrFail($product_id);
            $product->modifiers()->detach($validated['modifier_ids']);

            return ['success' => true, 'data' => 'Modifiers detached successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => $e->getMessage()];
        }
    }
}