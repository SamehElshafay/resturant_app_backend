<?php

namespace App\Http\Controllers;

use App\Models\ProductsModel\Modifier;
use App\Models\ProductsModel\ModifierOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModifiersController extends Controller{

    public function index(Request $request){
        try {
            $validated = $request->validate([
                'isSetOptions' => 'nullable|integer|in:0,1',
            ]);

            $isSetOptions = isset($validated['isSetOptions']) ? 1 : 0 ;

            return response()->json([
                'success' => true ,
                'modifiers' => $isSetOptions == 1 ? Modifier::with('options')->get() : Modifier::all()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => true ,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id){
        try{
            return response()->json([
                'success' => true ,
                'modifier' => Modifier::findOrFail($id)
            ]);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false ,
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request){
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'name'           => 'required|string|max:255',
                'selection_type' => 'required|in:single,multiple',
                'is_required'    => 'required|boolean',
                'options' => 'nullable|array',
                'options.*.name'       => 'required|string|max:255',
                'options.*.price'      => 'required|numeric',
                'options.*.is_default' => 'required|boolean',
            ]);

            $modifier = Modifier::create($validated);

            if (!empty($validated['options'])) {
                foreach ($validated['options'] as $opt) {
                    ModifierOption::create([
                        'modifier_id' => $modifier->id,
                        'name'        => $opt['name'],
                        'price'       => $opt['price'],
                        'is_default'  => $opt['is_default'],
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'success' => true ,
                'message' => 'Modifier created successfully',
                'modifier' => $modifier
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false ,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id) {
        try{
            $modifier = Modifier::findOrFail($id);
            $validated = $request->validate([
                'name'           => 'sometimes|string|max:255',
                'selection_type' => 'sometimes|in:single,multiple',
                'is_required'    => 'sometimes|boolean',
                'store_id'       => 'sometimes|integer|exists:commercial_places,id',
            ]);

            $modifier->update($validated);

            return response()->json([
                'success' => true,
                'modifier' => $modifier
            ], 200);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id){
        try{
            $modifier = Modifier::findOrFail($id);
            $modifier->options()->delete();
            $modifier->delete();
            
            return response()->json([
                'success' => true ,
                'message' => 'Modifier deleted successfully'
            ]);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
