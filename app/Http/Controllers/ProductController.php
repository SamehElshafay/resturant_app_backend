<?php

namespace App\Http\Controllers;

use App\Models\CategoryModels\Category;
use App\Models\CommercialPlaceModels\SingleOffer;
use App\Models\CustomerModel\FavoriteProduct;
use App\Models\ProductsModel\Product;
use App\Models\ProductsModel\ProductImage;
use App\Models\ProductsModel\ProductModifier;
use App\Models\ProductsModel\ProductModifierOptions;
use App\Services\ImagesServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller {
    public function index(Request $request) {
        try {
            $request->validate([
                'name' => 'sometimes|string',
                'category_id' => 'sometimes|integer|exists:category,id',
                'commercial_place_id' => 'sometimes|integer|exists:commercial_place,id',
            ]);

            $products = Product::with(['category', 'images'])
                ->search(
                    $request->name,
                    $request->category_id,
                    $request->commercial_place_id
                )
                ->orderBy('id', 'DESC')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id){
        $customer = auth('customer')->user();

        $product = Product::with(['category' , 'commercialPlace' , 'modifiers'])->findOrFail($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        if($customer != null){
            $favorite = FavoriteProduct::where('customer_id', $customer->id)->where('product_id', $product->id)->get()->first();
            $product->favorite = $favorite != null ? $favorite->id : null ;
        }

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'category_id'         => 'required|integer|exists:category,id',
            'price'               => 'required|numeric',
            'commercial_place_id' => 'required|integer|exists:commercial_place,id',
            'note'                => 'required|nullable|string',
            'preparation_time'    => 'required|integer',
            
            'images'              => 'required|array',
            'images.*'            => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            'modifiers'                       => 'sometimes|array',
            'modifiers.*.modifier_id'         => 'required|integer|exists:modifiers,id',
            'modifiers.*.options'             => 'required|array',
            'modifiers.*.options.*.option_id' => 'required|integer|exists:modifier_options,id',
            'modifiers.*.options.*.price'     => 'required|numeric|min:0',
        ]);
        
        DB::beginTransaction();
        try {
            $product = Product::create($validated);
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $img) {
                    $path = ImagesServices::uploadImage('products' , $img ) ;
                    //$path = $img->store('products', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path
                    ]);
                }
            }
            if ($request->filled('modifiers')) {
                foreach ($request->modifiers as $modifierData) {
                    $productModifier = ProductModifier::create([
                        'product_id'  => $product->id,
                        'modifier_id' => $modifierData['modifier_id'],
                    ]);
                
                    foreach ($modifierData['options'] as $option) {
                        ProductModifierOptions::create([
                            'product_modifiers_id' => $productModifier->id,
                            'option_id'            => $option['option_id'],
                            'price'                => $option['price'],
                        ]);
                    }
                }
            }


            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load('images')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id){
        DB::beginTransaction();

        try {
            $product = Product::with('images')->findOrFail($id);

            $validated = $request->validate([
                'name'             => 'sometimes|string|max:255',
                'category_id'      => 'sometimes|integer|exists:category,id',
                'price'            => 'sometimes|numeric',
                'note'             => 'sometimes|nullable|string',
                'preparation_time' => 'sometimes|integer',

                'images'           => 'sometimes|array',
                'images.*'         => 'file|mimes:jpg,jpeg,png,webp',

                'deleted_images'   => 'sometimes|array',
                'deleted_images.*' => 'integer|exists:product_images,id',
                'active'           => 'sometimes|boolean',
            ]);

            $product->update(collect($validated)->except(['images', 'deleted_images'])->toArray());

            if ($request->filled('active')){
                if(!$request->active){
                    SingleOffer::where("product_id", $product->id)->update('active' , 0);
                }
            }

            if ($request->filled('deleted_images')) {
                $imagesToDelete = ProductImage::whereIn('id', $request->deleted_images)
                    ->where('product_id', $product->id)
                    ->get();

                foreach ($imagesToDelete as $image) {
                    ImagesServices::deleteImage($image->image_path);
                    $image->delete();
                }
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $img) {
                    //$path = $img->store('products', 'public');
                    $path = ImagesServices::uploadImage('products' , $img) ;
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => $product->fresh('images'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id){
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);

            foreach ($product->images as $img) {
                if (Storage::disk('public')->exists($img->image_path)) {
                    Storage::disk('public')->delete($img->image_path);
                }
                $img->delete();
            }

            $product->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductOfCategory(Request $request){
        try{
            $validated = $request->validate([
                'category_id' => 'required|integer|exists:category,id',
                'length' => 'required|integer',
                'search' => 'nullable|string'
            ]);

            $products = Product::where('category_id', $validated['category_id'])->search($request->search)->take($request->length)->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        }catch (\Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function searchOnProducts(Request $request){
        try{
            $validated = $request->validate([
                'length' => 'required|integer',
                'search' => 'nullable|string'
            ]);

            $products = Product::with("commercialPlace")->search($request->search)->take($request->length)->get();

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        }catch (\Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductsOfMerchant(Request $request){
        try{
            $validated = $request->validate([
                'state' => 'required|string|in:all,active,inactive',
            ]);

            $merchant = auth('merchant')->user();
            
            $categories = Category::whereHas('products', function ($q) use ($merchant, $validated) {
                $q->where('commercial_place_id', $merchant->commercial_place_id);
                if ($validated['state'] !== 'all') {
                    $q->where('active', $validated['state'] === 'active' ? 1 : 0);
                }
            })->with(['products' => function ($q) use ($merchant, $validated) {
                $q->where('commercial_place_id', $merchant->commercial_place_id);
                if ($validated['state'] !== 'all') {
                    $q->where('active', $validated['state'] === 'active' ? 1 : 0);
                }
            }])
            ->get();
            
            return response()->json([
                'success' => true,
                'categories' => $categories
            ]);
        }catch (\Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function productVisibility(Request $request){
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:product,id',
            ]);
            
            $product = Product::findOrFail($validated['product_id']);
            
            if(!$product){
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 500);
            }

            $active = $product->active ;
            if($product->active == 1){
                $active = 0 ;
            }else{
                $active = 1 ;
            }
            
            $product->update([
                'active' => $active,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message'    => 'Product ' . ($active ? 'activated' : 'hidden') . ' successfully',
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function modifierVisibility(Request $request){
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:product,id',
                'modifier_id' => 'required|integer|exists:modifiers,id',
            ]);

            $productModifier = ProductModifier::where('product_id', $validated['product_id'])
                ->where('modifier_id', $validated['modifier_id'])->get()->first();
            
                if(!$productModifier){
                return response()->json([
                    'success' => false,
                    'message' => 'Modifier not found for this product',
                ], 500);
            }

            $active = $productModifier->active ;

            if($productModifier->active == 0){
                $active = 1 ;
            }else{
                $active = 0 ;
            }

            $productModifier->update(['active' => $active]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message'    => 'Modifier ' . ($active ? 'activated' : 'hidden') . ' successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function optionVisibility(Request $request){
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:product,id',
                'modifier_id' => 'required|integer|exists:modifiers,id',
                'option_id' => 'required|integer|exists:modifier_options,id',
            ]);

            $productModifier = ProductModifier::where('product_id', $validated['product_id'])
                ->where('modifier_id', $validated['modifier_id'])
                ->update(['active' => 0]);

            $productModifierOptions = ProductModifierOptions::where('product_modifiers_id', $productModifier->id)
                ->where('option_id', $validated['option_id'])->get()->first();

                if(!$productModifierOptions){
                    return response()->json([
                        'success' => false,
                        'message' => 'Option not found for this modifier',
                    ], 500);
                }


            $active = $productModifierOptions->active ;
            if($productModifierOptions->active == 0){
                $active = 1 ;
            }else{
                $active = 0 ;
            }

            $productModifierOptions->update(['active' => $active]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message'    => 'Option ' . ($active ? 'activated' : 'hidden') . ' successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}