<?php

namespace App\Http\Controllers;

use App\Models\ProductsModel\Modifier;
use App\Models\ProductsModel\ModifierOption;
use Illuminate\Http\Request;

class ModifierOptionsController extends Controller
{
    public function index($modifier_id){
        try{
            $modifier = Modifier::findOrFail($modifier_id);
            return response()->json([
                'success' => true,
                'modifier' => $modifier->options
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request){
        try{
            $validated = $request->validate([
                'modifier_id' => 'required|integer|exists:modifiers,id',
                'name'        => 'required|string|max:255',
                'price'       => 'required|numeric',
                'is_default'  => 'required|boolean',
            ]);

            $option = ModifierOption::create($validated);
            return response()->json([
                'success' => true,
                'option' => $option,
            ],201);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id){
        try{
            $modifirOption = ModifierOption::findOrFail($id);

            $validated = $request->validate([
                'name'        => 'sometimes|string|max:255',
                'price'       => 'sometimes|numeric',
                'is_default'  => 'sometimes|boolean',
            ]);

            $modifirOption->update($validated);
            
            if($modifirOption->is_default){
                $options = ModifierOption::where('modifier_id', $modifirOption->modifier_id)->get();
                foreach($options as $option){
                    if($modifirOption->id != $option->id){
                        $option->is_default = false;
                        $option->save();
                    }
                }
            }
            return response()->json([
                'success' => true,
                'option' => $modifirOption,
            ],200);

        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id){
        try{
            $option = ModifierOption::findOrFail($id);
            $option->delete();
            return response()->json([
                'success' => true,
                'message' => 'Option deleted successfully'
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}