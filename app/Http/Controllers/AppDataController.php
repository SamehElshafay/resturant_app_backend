<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AppData\Banners;
use App\Models\CustomerModel\Address;
use App\Services\ImagesServices;
use Exception;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

use function PHPUnit\Framework\isEmpty;

class AppDataController extends Controller {
    public function index()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => Banners::all()
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $banner = Banners::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $banner
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Banner not found'
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            $path = ImagesServices::uploadImage('banners' , $request->file('image')) ;

            $banner = Banners::create([
                'path' => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Banner created successfully',
                'data' => $banner
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id){
        try {
            $banner = Banners::findOrFail($id);

            $request->validate([
                'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            if ($request->hasFile('image')) {
                if ($banner->path && Storage::disk('public')->exists($banner->path)) {
                    Storage::disk('public')->delete($banner->path);
                }
                $banner->path = $request->file('image')->store('banners', 'public');
            }

            $banner->save();

            return response()->json([
                'success' => true,
                'message' => 'Banner updated successfully',
                'data' => $banner
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $banner = Banners::findOrFail($id);

            if ($banner->path && Storage::disk('public')->exists($banner->path)) {
                Storage::disk('public')->delete($banner->path);
            }

            $banner->delete();

            return response()->json([
                'success' => true,
                'message' => 'Banner deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}