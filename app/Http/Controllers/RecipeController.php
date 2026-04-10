<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Product;
use App\Models\Ingredient; // Now using Ingredient model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::with('product')->get();
        return view('recipes.index', compact('recipes'));
    }

    public function create()
    {
        $products = Product::orderBy('name_en')->get();
        $ingredients = Ingredient::orderBy('name_en')->get();
        // Sub-products can be any product except likely the one being created (handled in validation or UI logic)
        $subProducts = Product::orderBy('name_en')->get();

        return view('recipes.create', compact('products', 'ingredients', 'subProducts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id|unique:recipes,product_id',
            'cost' => 'nullable|numeric',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.type' => 'required|in:ingredient,sub_product',
            'ingredients.*.ingredient_id' => 'nullable|required_if:ingredients.*.type,ingredient|exists:ingredients,id',
            'ingredients.*.child_product_id' => 'nullable|required_if:ingredients.*.type,sub_product|exists:products,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.001',
            'ingredients.*.unit' => 'required|in:kg,g,ltr,ml,piece',
            'ingredients.*.cost_per_unit' => 'nullable|numeric',
        ]);

        try {
            DB::beginTransaction();

            $recipe = Recipe::create([
                'product_id' => $request->product_id,
                'name_ar' => $request->name_ar,
                'name_en' => $request->name_en,
                'instructions' => $request->instructions,
                'cost' => 0,
            ]);

            $totalCost = 0;

            foreach ($request->ingredients as $ing) {
                $costPerUnit = 0;
                $ingredientId = null;
                $childProductId = null;

                if ($ing['type'] === 'ingredient') {
                    $ingredientId = $ing['ingredient_id'];
                    $ingredient = Ingredient::find($ingredientId);
                    $costPerUnit = $ingredient->cost_price ?? 0;
                } else {
                    $childProductId = $ing['child_product_id'];
                    $subProduct = Product::find($childProductId);
                    $costPerUnit = $subProduct->base_purchase_price ?? 0;
                }

                $quantity = $ing['quantity'];
                $lineCost = $quantity * $costPerUnit;
                $totalCost += $lineCost;

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'ingredient_id' => $ingredientId,
                    'child_product_id' => $childProductId,
                    'quantity' => $ing['quantity'],
                    'unit' => $ing['unit'],
                    'cost_per_unit' => $costPerUnit,
                ]);
            }

            $recipe->update(['cost' => $totalCost]);

            DB::commit();
            return redirect()->route('recipes.index')->with('success', 'Recipe created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error creating recipe: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Recipe $recipe)
    {
        // Load ingredients relationship
        $recipe->load(['product', 'ingredients.ingredient']);
        return view('recipes.show', compact('recipe'));
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();
        return back()->with('success', 'Recipe deleted.');
    }
}
