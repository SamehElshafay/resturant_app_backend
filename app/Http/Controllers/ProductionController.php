<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\Product;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $query = Production::with(['product', 'branch'])->orderBy('created_at', 'desc');

        // Search Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                // Search in Products
                $q->whereHas('product', function($sq) use ($search) {
                    $sq->where('name_ar', 'LIKE', "%{$search}%")
                       ->orWhere('name_en', 'LIKE', "%{$search}%");
                })
                // Search in Branches (Name or Account Code)
                ->orWhereHas('branch', function($sq) use ($search) {
                    $sq->where('name_ar', 'LIKE', "%{$search}%")
                       ->orWhere('name_en', 'LIKE', "%{$search}%")
                       ->orWhere('account_code', 'LIKE', "%{$search}%");
                });
            });
        }

        // Date Filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $productions = $query->paginate(10)->withQueryString();
        return view('productions.index', compact('productions'));
    }

    public function show(Production $production)
    {
        $production->load(['product.recipe.ingredients.ingredient', 'product.recipe.ingredients.childProduct', 'branch']);
        return view('productions.show', compact('production'));
    }

    public function create()
    {
        // NO LONGER loading all products here for performance
        $branches = \App\Models\Branch::all();
        return view('productions.create', compact('branches'));
    }

    // Ajax search for products with recipes
    public function searchProducts(Request $request)
    {
        $term = $request->q;
        $products = Product::where(function($q) use ($term) {
            $q->where('name_ar', 'LIKE', "%{$term}%")
              ->orWhere('name_en', 'LIKE', "%{$term}%");
        })
        ->whereHas('recipe') // Only items that can actually be produced
        ->limit(15)
        ->get(['id', 'name_ar', 'name_en']);

        $formatted = $products->map(function($p) {
            return [
                'id' => $p->id,
                'text' => ($p->name_ar ?? $p->name_en) . ' (' . ($p->name_en ?? '') . ')'
            ];
        });

        return response()->json($formatted);
    }

    // Ajax helper to calculate estimated cost and ingredient usage for multiple items
    public function calculate(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $aggregatedIngredients = [];
        $totalProductionCost = 0;
        $allProductsData = [];

        foreach ($request->items as $item) {
            $product = Product::with(['recipe.ingredients.ingredient', 'recipe.ingredients.childProduct'])->find($item['product_id']);
            $quantity = $item['quantity'];

            if (!$product->recipe) {
                return response()->json(['error' => 'Product "' . ($product->name_en ?? $product->name) . '" has no recipe defined.'], 400);
            }

            $productCost = 0;

            foreach ($product->recipe->ingredients as $recipeItem) {
                $requiredQty = $recipeItem->quantity * $quantity;
                $ingredientKey = '';
                $ingredientName = 'Unknown';
                $stockQuantity = 0;
                $unitCost = 0;

                if ($recipeItem->ingredient) {
                    $ing = $recipeItem->ingredient;
                    $ingredientKey = 'ing_' . $ing->id;
                    $ingredientName = $ing->name_ar ?? $ing->name_en ?? $ing->name;
                    $stockQuantity = $ing->stock_quantity;
                    $unitCost = $ing->cost_price;
                } elseif ($recipeItem->childProduct) {
                    $child = $recipeItem->childProduct;
                    $ingredientKey = 'prod_' . $child->id;
                    $ingredientName = $child->name_ar ?? $child->name_en ?? $child->name;

                    if ($request->branch_id) {
                        $bp = \App\Models\BranchProduct::where('branch_id', $request->branch_id)
                            ->where('product_id', $child->id)->first();
                        $stockQuantity = $bp ? $bp->stock_quantity : 0;
                    } else {
                        $stockQuantity = $child->stock_quantity ?? 0;
                    }
                    $unitCost = $child->base_purchase_price ?? 0;
                }

                $lineCost = $requiredQty * $unitCost;
                $totalProductionCost += $lineCost;
                $productCost += $lineCost;

                if (!isset($aggregatedIngredients[$ingredientKey])) {
                    $aggregatedIngredients[$ingredientKey] = [
                        'name' => $ingredientName,
                        'required_qty' => 0,
                        'unit' => $recipeItem->unit,
                        'unit_cost' => $unitCost,
                        'current_stock' => $stockQuantity,
                    ];
                }
                $aggregatedIngredients[$ingredientKey]['required_qty'] += $requiredQty;
            }

            $allProductsData[] = [
                'name' => $product->name_ar ?? $product->name_en ?? $product->name,
                'quantity' => $quantity,
                'cost' => $productCost
            ];
        }

        // Final status check for items
        $finalIngredients = [];
        foreach ($aggregatedIngredients as $key => $ing) {
            $ing['line_cost'] = $ing['required_qty'] * $ing['unit_cost'];
            $ing['sufficient_stock'] = $ing['current_stock'] >= $ing['required_qty'];
            $finalIngredients[] = $ing;
        }

        return response()->json([
            'ingredients' => $finalIngredients,
            'total_cost' => $totalProductionCost,
            'products' => $allProductsData
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $branch = \App\Models\Branch::with('account')->findOrFail($request->branch_id);
            $rawAccCode = \App\Models\AccountingEntityConfig::where('entity_type', 'PRODUCTION_RAW_MATERIALS')->value('parent_account_code')
                ?? \App\Models\AccountingEntityConfig::where('entity_type', 'MERCHANT_BILL_DEBIT')->value('parent_account_code');
            $profitAcc = \App\Models\AccountingEntityConfig::where('entity_type', 'PRODUCTION_PROFIT_ACCOUNT')->value('parent_account_code');
            $templateCode = \App\Models\AccountingEntityConfig::where('entity_type', 'PRODUCTION_FINISHED_GOODS')->value('parent_account_code');
            $branchCode = $branch->account->code ?? '000';

            foreach ($request->items as $itemData) {
                $product = \App\Models\Product::with(['recipe.ingredients.ingredient', 'recipe.ingredients.childProduct'])->find($itemData['product_id']);
                $producedQty = $itemData['quantity'];
                
                if (!$product->recipe) {
                    throw new \Exception("Product " . ($product->name_en ?? $product->name) . " has no recipe.");
                }

                $totalItemBatchCost = 0;

                // 1. Deduct Ingredients
                foreach ($product->recipe->ingredients as $recipeItem) {
                    $requiredQty = $recipeItem->quantity * $producedQty;

                    if ($recipeItem->ingredient) {
                        $ingredient = $recipeItem->ingredient;
                        if ($ingredient->stock_quantity < $requiredQty) {
                            throw new \Exception("Insufficient stock for: " . ($ingredient->name_en ?? $ingredient->name));
                        }
                        $totalItemBatchCost += ($requiredQty * $ingredient->cost_price);
                        $ingredient->deductStock($requiredQty);
                    } elseif ($recipeItem->childProduct) {
                        $child = $recipeItem->childProduct;
                        $bp = \App\Models\BranchProduct::where('branch_id', $request->branch_id)->where('product_id', $child->id)->first();
                        if (!$bp || $bp->stock_quantity < $requiredQty) {
                            throw new \Exception("Insufficient stock for sub-product: " . ($child->name_en ?? $child->name) . " in selected branch.");
                        }
                        $bp->stock_quantity -= $requiredQty;
                        $bp->save();
                        $totalItemBatchCost += ($requiredQty * ($child->base_purchase_price ?? 0));
                        $child->stock_quantity = ($child->stock_quantity ?? 0) - $requiredQty;
                        $child->save();
                    }
                }

                $unitCost = ($producedQty > 0) ? ($totalItemBatchCost / $producedQty) : 0;

                // 2. Add to Branch Stock
                $branchProduct = \App\Models\BranchProduct::firstOrCreate(
                    ['branch_id' => $request->branch_id, 'product_id' => $product->id],
                    ['stock_quantity' => 0, 'price' => 0]
                );

                if (!($branchProduct->price > 0)) {
                    throw new \Exception("Selling price not set for " . ($product->name_en ?? $product->name) . " in branch " . $branch->name);
                }

                $branchProduct->stock_quantity += $producedQty;
                $branchProduct->save();

                // 3. Update Average Price & Log Production (Weighted Average)
                $totalSystemStockBefore = $product->stock_quantity + ($product->branchPrices->sum('stock_quantity') - $producedQty);
                $currentTotalValue = ($totalSystemStockBefore * ($product->base_purchase_price ?? 0));
                $newTotalValue = $currentTotalValue + $totalItemBatchCost;
                $totalSystemStockAfter = $totalSystemStockBefore + $producedQty;
                
                $product->base_purchase_price = ($totalSystemStockAfter > 0) ? ($newTotalValue / $totalSystemStockAfter) : $unitCost;
                $product->save();

                $production = Production::create([
                    'branch_id' => $request->branch_id,
                    'product_id' => $product->id,
                    'quantity_produced' => $producedQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalItemBatchCost,
                    'production_date' => now(),
                    'performed_by' => auth()->id() ?? 1,
                ]);

                // 4. Accounting (Per Item)
                $targetCode = $branchCode . '-' . $templateCode;
                $branchProductsAccount = \App\Models\Account::firstOrCreate(
                    ['code' => $targetCode],
                    [
                        'name_ar' => 'منتجات جاهزة - ' . $branch->name,
                        'name_en' => 'Finished Goods - ' . $branch->name,
                        'name' => 'Finished Goods - ' . $branch->name,
                        'type' => 1, 'parent_id' => $branch->account_id, 'branch_id' => $branch->id
                    ]
                );

                $sellingPrice = $branchProduct->price ?? 0;
                $totalSellingAmount = $producedQty * $sellingPrice;
                $totalProfit = $totalSellingAmount - $totalItemBatchCost;

                \App\Services\AccountingEngine::trigger('PRODUCTION_COMPLETE', [
                    'purchase_cost' => $totalItemBatchCost,
                    'selling_amount' => $totalSellingAmount,
                    'profit_amount' => $totalProfit,
                    'raw_materials_account' => $rawAccCode,
                    'branch_products_account' => $branchProductsAccount->code,
                    'profit_account' => $profitAcc,
                ], [
                    'reference_type' => 'production',
                    'reference_id' => $production->id,
                    'description' => "Produced {$producedQty} of {$product->name_en}"
                ]);
            }

            DB::commit();
            return redirect()->route('productions.index')->with('success', "Production for multiple items recorded successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Production Failed: ' . $e->getMessage())->withInput();
        }
    }
}
