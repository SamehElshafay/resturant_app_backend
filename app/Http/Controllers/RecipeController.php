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
    public function index(Request $request)
    {
        $query = Recipe::with(['product', 'ingredients']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                    ->orWhere('cost', 'LIKE', "%{$search}%")
                    ->orWhere('name_ar', 'LIKE', "%{$search}%")
                    ->orWhere('name_en', 'LIKE', "%{$search}%")
                    ->orWhere('instructions', 'LIKE', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('name_ar', 'LIKE', "%{$search}%")
                            ->orWhere('name_en', 'LIKE', "%{$search}%")
                            ->orWhere('id', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('ingredients.ingredient', function ($iq) use ($search) {
                        $iq->where('name_ar', 'LIKE', "%{$search}%")
                            ->orWhere('name_en', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('ingredients.childProduct', function ($pq) use ($search) {
                        $pq->where('name_ar', 'LIKE', "%{$search}%")
                            ->orWhere('name_en', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Specific Column Filters
        if ($request->filled('product_name')) {
            $name = $request->product_name;
            $query->whereHas('product', function ($q) use ($name) {
                $q->where('name_ar', 'LIKE', "%{$name}%")
                    ->orWhere('name_en', 'LIKE', "%{$name}%")
                    ->orWhere('name', 'LIKE', "%{$name}%");
            });
        }

        if ($request->filled('min_cost')) {
            $query->where('cost', '>=', $request->min_cost);
        }
        if ($request->filled('max_cost')) {
            $query->where('cost', '<=', $request->max_cost);
        }

        if ($request->filled('min_stock')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('stock_quantity', '>=', $request->min_stock);
            });
        }
        if ($request->filled('max_stock')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('stock_quantity', '<=', $request->max_stock);
            });
        }

        if ($request->filled('min_ingredients')) {
            $query->has('ingredients', '>=', $request->min_ingredients);
        }
        if ($request->filled('max_ingredients')) {
            $query->has('ingredients', '<=', $request->max_ingredients);
        }

        $recipes = $query->latest()->paginate(10)->withQueryString();
        return view('recipes.index', compact('recipes'));
    }

    public function create()
    {
        $products = Product::orderBy('name_en')->limit(10)->get();
        $ingredients = Ingredient::orderBy('name_ar')->limit(10)->get();
        $subProducts = Product::orderBy('name_en')->limit(10)->get();

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
