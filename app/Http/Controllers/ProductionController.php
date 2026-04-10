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
        // Get all products so user can see them, but validation will stop them if no recipe exists
        $products = Product::with('recipe')->orderBy('name_en')->get();
        $branches = \App\Models\Branch::all();
        return view('productions.create', compact('products', 'branches'));
    }

    // Ajax helper to calculate estimated cost and ingredient usage
    public function calculate(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $product = Product::with(['recipe.ingredients.ingredient', 'recipe.ingredients.childProduct'])->find($request->product_id);
        $quantity = $request->quantity;

        if (!$product->recipe) {
            return response()->json(['error' => 'Product has no recipe defined.'], 400);
        }

        $ingredientsNeeded = [];
        $totalCost = 0;
        $maxPossibleQuantity = 999999999;

        foreach ($product->recipe->ingredients as $recipeItem) {
            $requiredQty = $recipeItem->quantity * $quantity;

            $ingredientName = 'Unknown';
            $stockQuantity = 0;
            $unitCost = 0;

            if ($recipeItem->ingredient) {
                $ing = $recipeItem->ingredient;
                $ingredientName = $ing->name_ar ?? $ing->name_en ?? $ing->name;
                $stockQuantity = $ing->stock_quantity;
                $unitCost = $ing->cost_price;
            } elseif ($recipeItem->childProduct) {
                $child = $recipeItem->childProduct;
                $ingredientName = $child->name_ar ?? $child->name_en ?? $child->name;

                if ($request->branch_id) {
                    $bp = \App\Models\BranchProduct::where('branch_id', $request->branch_id)
                        ->where('product_id', $child->id)->first();
                    $stockQuantity = $bp ? $bp->stock_quantity : 0;
                } else {
                    $stockQuantity = $child->stock_quantity ?? 0;
                }
                $unitCost = $child->base_purchase_price ?? 0;
            } else {
                continue; // Skip invalid items
            }

            // Calculate max possible production based on this item
            $maxForItem = ($recipeItem->quantity > 0) ? ($stockQuantity / $recipeItem->quantity) : 999999999;
            if ($maxForItem < $maxPossibleQuantity) {
                $maxPossibleQuantity = $maxForItem;
            }

            $lineCost = $requiredQty * $unitCost;

            $ingredientsNeeded[] = [
                'name' => $ingredientName,
                'required_qty' => $requiredQty,
                'unit' => $recipeItem->unit,
                'unit_cost' => $unitCost,
                'line_cost' => $lineCost,
                'current_stock' => $stockQuantity,
                'sufficient_stock' => $stockQuantity >= $requiredQty
            ];

            $totalCost += $lineCost;
        }

        if ($maxPossibleQuantity == 999999999)
            $maxPossibleQuantity = 0;
        $maxPossibleQuantity = floor($maxPossibleQuantity * 100) / 100;

        return response()->json([
            'ingredients' => $ingredientsNeeded,
            'total_cost' => $totalCost,
            'unit_cost' => ($quantity > 0) ? ($totalCost / $quantity) : 0,
            'max_possible_quantity' => $maxPossibleQuantity
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            DB::beginTransaction();

            $product = \App\Models\Product::with(['recipe.ingredients.ingredient', 'recipe.ingredients.childProduct'])->find($request->product_id);
            $producedQty = $request->quantity;

            if (!$product->recipe) {
                return back()->with('error', 'Product has no recipe.');
            }

            $totalBatchCost = 0;

            // 1. Deduct Ingredients & Calculate Actual Cost
            foreach ($product->recipe->ingredients as $recipeItem) {
                $requiredQty = $recipeItem->quantity * $producedQty;

                if ($recipeItem->ingredient) {
                    $ingredient = $recipeItem->ingredient;

                    if ($ingredient->stock_quantity < $requiredQty) {
                        throw new \Exception("Insufficient stock for ingredient: " . ($ingredient->name_ar ?? $ingredient->name_en ?? $ingredient->name));
                    }

                    $cost = $requiredQty * $ingredient->cost_price;
                    $totalBatchCost += $cost;

                    $ingredient->deductStock($requiredQty);

                } elseif ($recipeItem->childProduct) {
                    $child = $recipeItem->childProduct;

                    // Check Branch Stock
                    $bp = \App\Models\BranchProduct::where('branch_id', $request->branch_id)
                        ->where('product_id', $child->id)->first();

                    if (!$bp || $bp->stock_quantity < $requiredQty) {
                        throw new \Exception("Insufficient stock for sub-product: " . ($child->name_ar ?? $child->name_en ?? $child->name) . " in selected branch.");
                    }

                    $bp->stock_quantity -= $requiredQty;
                    $bp->save();

                    // Calculate cost based on product base price
                    $cost = $requiredQty * ($child->base_purchase_price ?? 0);
                    $totalBatchCost += $cost;

                    // Also decrement central stock to keep in sync (optional but recommended)
                    $child->stock_quantity = ($child->stock_quantity ?? 0) - $requiredQty;
                    $child->save();
                }
            }

            $unitCost = ($producedQty > 0) ? ($totalBatchCost / $producedQty) : 0;

            // 2. Add to Branch Stock (This is what Inventory View shows)
            $branchProduct = \App\Models\BranchProduct::firstOrCreate(
                ['branch_id' => $request->branch_id, 'product_id' => $product->id],
                ['stock_quantity' => 0, 'price' => 0]
            );

            if (!($branchProduct->price > 0)) {
                throw new \Exception(app()->getLocale() == 'ar' 
                    ? "فشل الإنتاج: يجب تحديد سعر البيع للمنتج في هذا الفرع أولاً."
                    : "Production Failed: Please set the product selling price in this branch first.");
            }

            $branchProduct->stock_quantity += $producedQty;
            $branchProduct->save();

            // 3. Update Product Central Stock & Base Purchase Price (Weighted Average)
            // Calculate new average price based on existing central stock value + new batch value
            // We use total system stock for the valuation baseline
            $totalSystemStockBefore = $product->stock_quantity + ($product->branchPrices->sum('stock_quantity') - $producedQty);
            $currentTotalValue = ($totalSystemStockBefore * ($product->base_purchase_price ?? 0));
            $newTotalValue = $currentTotalValue + $totalBatchCost;

            $totalSystemStockAfter = $totalSystemStockBefore + $producedQty;
            $product->base_purchase_price = ($totalSystemStockAfter > 0) ? ($newTotalValue / $totalSystemStockAfter) : $unitCost;

            // ONLY increment central stock if it's NOT for a specific branch (if branch_id logic allows)
            // But here branch_id is required. If the system treats "No Branch" as Warehouse, we need to check that.
            // Based on user feedback, producing for a branch should NOT increase "Store" (Central) stock.
            // If the user wants to produce INTO the warehouse, they would select a specific "Warehouse" branch if it exists,
            // or we could have a null branch_id. But currently branch_id is required.
            // Let's assume as per user's current flow: Production to Branch = -Ingredients from Store, +Product in Branch. Store Product += 0.

            $product->save();

            // 4. Record Production Log with Branch
            $production = Production::create([
                'branch_id' => $request->branch_id,
                'product_id' => $product->id,
                'quantity_produced' => $producedQty,
                'unit_cost' => $unitCost,
                'total_cost' => $totalBatchCost,
                'production_date' => now(),
                'performed_by' => auth()->id() ?? 1,
            ]);

            // 5. Trigger Accounting Scenarios
            // Get Configs
            $rawAccCode = \App\Models\AccountingEntityConfig::where('entity_type', 'PRODUCTION_RAW_MATERIALS')->value('parent_account_code');
            if (!$rawAccCode) {
                 $rawAccCode = \App\Models\AccountingEntityConfig::where('entity_type', 'MERCHANT_BILL_DEBIT')->value('parent_account_code');
            }
            $rawAcc = \App\Models\Account::where('code', $rawAccCode)->first();
            
            $profitAcc = \App\Models\AccountingEntityConfig::where('entity_type', 'PRODUCTION_PROFIT_ACCOUNT')->value('parent_account_code');
            $templateCode = \App\Models\AccountingEntityConfig::where('entity_type', 'PRODUCTION_FINISHED_GOODS')->value('parent_account_code');
            
            $branch = \App\Models\Branch::with('account')->find($request->branch_id);
            $branchCode = $branch->account->code ?? '000';

            // Create/Find Branch Products Account (Tag logic equivalent)
            // Code: BranchCode-TemplateCode
            $targetCode = $branchCode . '-' . $templateCode;
            $targetNameAr = ($rawAcc->name_ar ?? 'مواد خام') . ' - ' . ($branch->name_ar ?? $branch->name);
            $targetNameEn = ($rawAcc->name_en ?? 'Raw Materials') . ' - ' . ($branch->name_en ?? $branch->name);
            
            $branchProductsAccount = \App\Models\Account::firstOrCreate(
                ['code' => $targetCode],
                [
                    'name_ar' => $targetNameAr,
                    'name_en' => $targetNameEn,
                    'name' => $targetNameEn,
                    'type' => 1, // Asset
                    'parent_id' => $branch->account_id,
                    'branch_id' => $branch->id
                ]
            );

            // Calculation based on Selling Price in branch
            $sellingPrice = $branchProduct->price ?? 0;
            $totalSellingAmount = $producedQty * $sellingPrice;
            $totalProfit = $totalSellingAmount - $totalBatchCost;

            // Step A: Production (Raw -> Branch Products + Profit)
            \App\Services\AccountingEngine::trigger('PRODUCTION_COMPLETE', [
                'purchase_cost' => $totalBatchCost,
                'selling_amount' => $totalSellingAmount,
                'profit_amount' => $totalProfit,
                'raw_materials_account' => $rawAccCode,
                'branch_products_account' => $branchProductsAccount->code,
                'profit_account' => $profitAcc,
            ], [
                'reference_type' => 'production',
                'reference_id' => $production->id,
                'description' => "Produced {$producedQty} of {$product->name_en} for branch {$branch->name}"
            ]);

            DB::commit();

            return redirect()->route('productions.index')->with('success', "Production recorded successfully. Added $producedQty {$product->name_en} to stock.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Production Failed: ' . $e->getMessage())->withInput();
        }
    }
}
