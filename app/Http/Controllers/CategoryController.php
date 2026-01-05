<?php

namespace App\Http\Controllers;

use App\Models\CategoryModels\Category;
use App\Models\CategoryModels\CommercialCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(Request $request) {
        try {
            $request->validate([
                'name' => 'sometimes|string',
                'parent_category_id' => 'sometimes|integer|exists:category,id',
            ]);

            $categories = Category::query()
                ->search($request->name, $request->parent_category_id)
                ->orderBy('id', 'DESC')
                ->get();

            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id){
        $category = Category::findOrFail($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image_path' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048',
            'parent_category_id' => 'required|integer',
        ]);

        if ($request->hasFile('image_path')) {
            $validated['image_path'] = $request->file('image_path')->store('categories', 'public');
        }

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'data' => $category
        ], 201);
    }

    public function update(Request $request, $id){
        DB::beginTransaction();

        try {
            $category = Category::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'image_path' => 'sometimes|file|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($request->hasFile('image_path')) {
                if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
                    Storage::disk('public')->delete($category->image_path);
                }
                $validated['image_path'] = $request->file('image_path')->store('categories', 'public');
            }

            $category->update($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $category
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id){
        DB::beginTransaction();

        try {
            $category = Category::findOrFail($id);

            if ($category->image_path && Storage::disk('public')->exists($category->image_path)) {
                Storage::disk('public')->delete($category->image_path);
            }

            $category->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllCategoriesOfParent(Request $request){
        try{
            $validated = $request->validate([
                'parent_id' => 'required|integer' ,
                'length' => 'required|integer' ,
                'name' => 'nullable|string|max:255' ,
            ]);
            
            $categories = Category::where('parent_category_id', $validated['parent_id'])->search($request->name)->take($request->length)->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllCategoriesOfCommercial($commercial_place_id){
        try{
            $categories = CommercialCategory::where('commercial_place_id', $commercial_place_id)->get();

            return response()->json([
                'success' => true,
                'data' => $categories->pluck('category')->toArray()
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}