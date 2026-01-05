<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductsModel\ProductModifierOption;
use App\Models\ProductsModel\ProductModifierOptions;
use Illuminate\Http\Request;

class ProductModifierOptionController extends Controller
{
    public function index(){
        try {
            $data = ProductModifierOptions::all();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request){
        try {
            $validated = $request->validate([
                'product_modifiers_id' => 'required|integer',
                'option_id'            => 'required|integer',
                'price'                => 'required|numeric',
            ]);

            $item = ProductModifierOptions::create($validated);

            return response()->json([
                'success' => true,
                'data' => $item
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id){
        try {
            $item = ProductModifierOptions::findOrFail($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'data' => 'Item not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $item
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $item = ProductModifierOptions::findOrFail($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'data' => 'Item not found'
                ], 404);
            }

            $validated = $request->validate([
                'price' => 'sometimes|numeric'
            ]);

            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $item
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id){
        try {
            $item = ProductModifierOptions::findOrFail($id);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'data' => 'Item not found'
                ], 404);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'data' => 'Deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }
}