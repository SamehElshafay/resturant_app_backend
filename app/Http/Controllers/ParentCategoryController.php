<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CategoryModels\ParentCategory;
use App\Services\NearestCommercialPlacesService;
use Illuminate\Http\Request;

class ParentCategoryController extends Controller {
    
    public function index(){
        try {
            $data = ParentCategory::with('category')->get();

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

    public function show($id) {
        try {
            $category = ParentCategory::findOrFail($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'data' => 'Parent category not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request) {
        try {
            $validated = $request->validate([
                'name' => 'required|string'
            ]);

            $category = ParentCategory::create($validated);

            return response()->json([
                'success' => true,
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id) {
        try {
            $category = ParentCategory::findOrFail($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'data' => 'Parent category not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string'
            ]);

            $category->update($validated);

            return response()->json([
                'success' => true,
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id) {
        try {
            $category = ParentCategory::find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'data' => 'Parent category not found'
                ], 404);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'data' => 'Parent category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function getAllParentCategories() {
        try {
            $data = ParentCategory::all();

            return response()->json([
                'success' => true,
                'data' => $data ,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function parentCategoryData(Request $request , $id) {
        $validated = $request->validate([
            'language' => 'nullable|string',
        ]);
        
        try {
            $language = $validated['language'] ?? 'en';

            $parent = ParentCategory::with([
                'category',
                'nearby_commercial_places',
                'recommended_commercial_places',
            ])->findOrFail($id);

            $nearestService = new NearestCommercialPlacesService();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id'             => $parent->id,
                    'name'           => $parent->name,
                    'category'     => $parent->category,
                    'grouped_places' => [
                        [
                            'name' => $language == "en" ? 'Nearest Places' : 'قريب منك',
                            'places' => $nearestService->nearestStoresWithin10Km(31.21 , 30.04 , 10000.55)
                        ],
                        [
                            'name' => $language == "en" ? 'Recommended Places' : "موصى به",
                            'places' => $nearestService->nearestStoresWithin10Km(31.21 , 30.04 , 10000.55)
                        ],
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage()
            ], 500);
        }
    }
}