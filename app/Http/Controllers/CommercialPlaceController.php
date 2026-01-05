<?php

namespace App\Http\Controllers;

use App\Models\CategoryModels\CommercialCategory;
use App\Models\CommercialPlaceModels\CommercialPlace;
use App\Models\CommercialPlaceModels\CommercialPlaceCommission;
use App\Models\CommercialPlaceModels\CommercialPlaceImages;
use App\Models\CommercialPlaceModels\CommercialPlaceProfileImages;
use App\Models\CommercialPlaceModels\Location;
use App\Models\CommercialPlaceModels\PhoneNumbers;
use App\Models\CustomerModel\FavoritePlace;
use App\Models\ProductsModel\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommercialPlaceController extends Controller {
    public function index(Request $request){
        try {
            $request->validate([
                'name' => 'sometimes|string',
            ]);

            $places = CommercialPlace::with(['phoneNumbers','location','images','commission'])
                ->when($request->name, function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->name . '%');
                })
                ->orderBy('id', 'DESC')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $places->items(),
                'current_page' => $places->currentPage(),
                'last_page'    => $places->lastPage(),
                'has_more'     => $places->hasMorePages(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id){
        try {
            $place = CommercialPlace::findOrFail($id)->load([
                'categories',
                'location',
                'commission',
                'images',
                'appointment' ,
                'phoneNumbers'
            ]);

            if (!$place) {
                return response()->json([
                    'success'  => false,
                    'message' => 'Commercial place not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'commercial_place' => $place
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getCommercialPlace($id){
        try {
            $customer = auth('customer')->user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'unAuthorized'
                ], 401);
            }

            $place = CommercialPlace::with([
                'location',
                'images',
                'phoneNumbers',
                'appointment',
                'product_offer',
                'all_offers.offer_products.product.image'
            ])->findOrFail($id);

            $place->distance = "10 KM";
            $place->delivery_price = 10 * 5.5;
            $place->arriving_time = "40 m";
            
            $place->all_type_offers = [
                [
                    'name' => 'menu' ,
                    'title'  => 'menu',
                    'items' => $place->categories->pluck('category')->toArray(),
                ],
                [
                    'name'  => 'product offers',
                    'title'  => 'product offers',
                    'items' => $place->productsWithOffers
                ],
                [
                    'name'  => 'offers',
                    'title'  => 'offers',
                    'items' => $place->all_offers
                ],
            ];

            if($customer){
                $favorite = FavoritePlace::where('customer_id', $customer->id)->where('commercial_place_id', $place->id)->get()->first();
                $place->favorite = $favorite != null ? $favorite->id : null ;
            }
            

            $place->makeHidden(['categories' , 'product_offer', 'all_offers']);

            return response()->json([
                'success' => true,
                'commercial_place' => $place
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request){
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',

                'location' => 'required|array',
                'location.address' => 'required|string',
                'location.lat' => 'required|numeric',
                'location.lang' => 'required|numeric',
                'location.zone_id' => 'required|integer|exists:zone,id',


                'phoneNumbers' => 'required|array',
                'phoneNumbers.*' => 'string',

                'images' => 'required|array',
                'images.*' => 'file|mimes:jpg,jpeg,png,webp|max:2048' ,

                'profile_image' => 'required|file|mimes:jpg,jpeg,png,webp|max:2048' ,
                'commission_type_id'       => 'required|integer|exists:commission_type,id',
                'value'               => 'required|numeric',
                
                'parent_category_id' => 'sometimes|integer',
                
                'categories' => 'required|array',
                'categories.*' => 'integer|exists:category,id',
            ]);

            $place = CommercialPlace::create([
                'name' => $validated['name'],
                'profile_image_id' => -1 ,
                'parent_category_id' => $validated['parent_category_id'] ?? null ,
            ]);

            CommercialPlaceCommission::create([
                'commercial_place_id' => $place->id,
                'commission_id'       => $validated['commission_type_id'],
                'value'               => $validated['value'],
            ]);

            Location::create([
                'commercial_place_id' => $place->id,
                'address' => $validated['location']['address'],
                'lat'     => $validated['location']['lat'],
                'lang'    => $validated['location']['lang'],
                'zone_id' => $validated['location']['zone_id'],
            ]);


            if (!empty($validated['phoneNumbers'])) {
                foreach ($validated['phoneNumbers'] as $phone) {
                    PhoneNumbers::create([
                        'commercial_place_id' => $place->id,
                        'phoneNumber' => $phone
                    ]);
                }
            }

            if (!empty($validated['categories'])) {
                foreach ($validated['categories'] as $category_id) {
                    CommercialCategory::create([
                        'commercial_place_id' => $place->id,
                        'category_id' => $category_id
                    ]);
                }
            }
            
            $img = $request->file('profile_image');
            $path = $img->store('commercial_places', 'public');
            
            $profile_image = CommercialPlaceProfileImages::create([
                'commercial_place_id' => $place->id,
                'path' => $path ,
            ]);

            $place->profile_image_id = $profile_image->id ;
            $place->save() ;
            
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $img) {
                    $path = $img->store('commercial_places', 'public');
                    CommercialPlaceImages::create([
                        'commercial_place_id' => $place->id,
                        'path' => $path
                    ]);
                }
            }


            //DB::rollBack();
            DB::commit();

            return response()->json([
                'success' => true,
                'data'   => $place
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id) {
        DB::beginTransaction();
        try {
            $place = CommercialPlace::findOrFail($id);

            $validated = $request->validate([
                'name'                => 'sometimes|string|max:255',

                'location' => 'sometimes|array',
                'location.address_id' => 'sometimes|integer',
                'location.address' => 'sometimes|string',
                'location.lat' => 'sometimes|numeric',
                'location.lang' => 'sometimes|numeric',
                'location.zone_id' => 'sometimes|integer|exists:zone,id',

                'phoneNumbers'        => 'sometimes|array',
                'phoneNumbers.*'      => 'string',
                
                'images'              => 'sometimes|array',
                'images.*'            => 'file|mimes:jpg,jpeg,png,webp|max:2048',

                'deleted_images'     => 'sometimes|array',
                'deleted_images.*'   => 'integer|exists:commercial_place_images,id',
                
                'commission_type_id' => 'sometimes|integer|exists:commission_type,id',
                'value'              => 'sometimes|numeric',
                'profile_image'      => 'sometimes|file|mimes:jpg,jpeg,png,webp|max:10048',

                'categories_ids' => 'sometimes|array',
                'categories_ids.*' => 'integer|exists:category,id',

                'deleted_categories_ids' => 'sometimes|array',
                'deleted_categories_ids.*' => 'integer|exists:category,id',
            ]);
            
            if ($request->has('categories_ids')) {
                $categoryIds = collect($request->deleted_categories_ids)->filter()->unique()->values()->toArray();
                foreach ($categoryIds as $categoryId) {
                    CommercialCategory::create([
                        'commercial_place_id' => $place->id,
                        'category_id' => $categoryId
                    ]);
                }
            }
            
            if ($request->has('deleted_categories_ids')) {
                $incomingCategoryIds = collect($request->deleted_categories_ids)->filter()->unique()->values()->toArray();

                foreach ($incomingCategoryIds as $categoryId) {
                    $commercialCategory = CommercialCategory::where('category_id' , $categoryId)->where('commercial_place_id' , $place->id)->get()->first() ;
                    if(count(Product::where('commercial_place_id', $place->id)->where('category_id' , $categoryId)->get()) != 0){
                        return [
                            'success' => false ,
                            'message' => 'category cannout be deleted becauase it have working products'
                        ];
                    }else{
                        $commercialCategory->delete();
                    }
                }
            }
            
            if ($request->has('name')) {
                $place->update(['name' => $request->name]);
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('commercial_places', 'public');
                    CommercialPlaceImages::create([
                        'commercial_place_id' => $place->id,
                        'path' => $path,
                    ]);
                }
            }

            if ($request->has('deleted_images')) {
                $imagesToDelete = CommercialPlaceImages::where('commercial_place_id', $place->id)
                    ->whereIn('id', $request->deleted_images)
                    ->get();

                foreach ($imagesToDelete as $img) {
                    Storage::disk('public')->delete($img->path);
                    $img->delete();
                }
            }

            if ($request->hasAny(['commission_type_id', 'value'])) {
                $place->commission()->updateOrCreate(
                    ['commercial_place_id' => $place->id],
                    [
                        'commission_id' => $request->commission_type_id ?? $place->commission->commission_id,
                        'value'         => $request->value ?? $place->commission->value,
                    ]
                );
            }

            if ($request->has('location')) {
                Location::find($request->location['id'])->update($request->location);
            }


            if ($request->has('phone_numbers')) {
                PhoneNumbers::where('commercial_place_id', $place->id)->delete();
                foreach ($request->phone_numbers as $phone) {
                    PhoneNumbers::create([
                        'commercial_place_id' => $place->id,
                        'phoneNumber' => $phone,
                    ]);
                }
            }

            
            if ($request->hasFile('profile_image')) {
                
                $file = $request->file('profile_image');
                $path = $file->store('commercial_places/profile', 'public');

                $profileImage = CommercialPlaceProfileImages::where('commercial_place_id' , $place->id)->get()->first() ;

                if ($profileImage) {
                    Storage::disk('public')->delete($profileImage->path);

                    $profileImage->update([
                        'path' => $path,
                    ]);
                } else {
                    $profileImage = CommercialPlaceProfileImages::create([
                        'commercial_place_id' => $place->id ,
                        'path' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $place->load([
                    'location',
                    'profile_image_path',
                    'commission',
                    'images',
                    'phoneNumbers'
                ]),
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
            $place = CommercialPlace::findOrFail($id);

            if (!$place) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commercial place not found'
                ], 404);
            }

            PhoneNumbers::where('commercial_place_id', $place->id)->delete();
            CommercialPlaceImages::where('commercial_place_id', $place->id)->delete();

            Location::where('id', $place->location_id)->delete();

            $place->delete();

            DB::commit();

            return response()->json([
                'success'  => true,
                'message' => 'Commercial place deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

}