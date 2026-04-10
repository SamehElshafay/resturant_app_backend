<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = \App\Models\Branch::all();
        return view('branches.index', compact('branches'));
    }

    public function show(\Illuminate\Http\Request $request, \App\Models\Branch $branch)
    {
        $branchId = $branch->id;

        // Default to today if no date is provided
        $startDate = $request->get('start_date', now()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Cache key includes date range for accuracy
        $cacheKey = "branch_stats_{$branchId}_{$startDate}_{$endDate}";

        $stats = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($branch, $startDate, $endDate) {
            // Apply date filters to Orders with optimized join
            $salesData = \App\Models\Order::where('branch_id', $branch->id)
                ->where('status', 'completed')
                ->whereDate('orders.created_at', '>=', $startDate)
                ->whereDate('orders.created_at', '<=', $endDate)
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->selectRaw('
                    SUM(order_items.item_total) as total_sales,
                    SUM(order_items.quantity * products.base_purchase_price) as total_cost
                ')
                ->first();

            // Apply date filters to Expenses
            $totalExpenses = \App\Models\Expense::where('branch_id', $branch->id)
                ->whereDate('expense_date', '>=', $startDate)
                ->whereDate('expense_date', '<=', $endDate)
                ->sum('amount');

            $sales = $salesData->total_sales ?? 0;
            $cogs = $salesData->total_cost ?? 0;
            $grossProfit = $sales - $cogs;
            $netProfit = $grossProfit - $totalExpenses;

            return [
                'total_sales' => $sales,
                'total_profit' => $netProfit,
                'total_expenses' => $totalExpenses,
                'gross_profit' => $grossProfit
            ];
        });

        // Eager load categories and their branch-specific products in a single optimized query sequence
        // This avoids N+1 problems and minimizes memory usage by only pulling what is needed.
        $categories = \App\Models\Category::with([
            'products' => function ($q) use ($branchId) {
                $q->whereHas('branchPrices', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })->with([
                            'branchPrices' => function ($query) use ($branchId) {
                                $query->where('branch_id', $branchId);
                            }
                        ]);
            }
        ])->whereHas('products.branchPrices', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->get();

        return view('branches.show', compact('branch', 'stats', 'categories', 'startDate', 'endDate'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'name' => 'required',
            'address' => 'nullable',
            'phone' => 'nullable',
        ]);

        // Check for accounting configuration
        $config = \App\Models\AccountingEntityConfig::where('entity_type', 'branch')->first();
        if (!$config) {
            $err = 'Accounting configuration for "branch" missing. Please add it first in Entity Configs.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $err], 422);
            }
            return back()->withErrors(['accounting' => $err])->withInput();
        }

        \App\Models\Branch::create($request->all());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Branch created successfully']);
        }

        return redirect()->back()->with('success', 'Branch created successfully');
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Branch $branch)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $branch->update($request->all());

        // Invalidate stats cache on modification
        \Illuminate\Support\Facades\Cache::forget("branch_stats_{$branch->id}");

        return redirect()->back()->with('success', 'Branch updated successfully');
    }

    public function destroy(\App\Models\Branch $branch)
    {
        $branchId = $branch->id;
        $branch->delete();

        // Cleanup cache
        \Illuminate\Support\Facades\Cache::forget("branch_stats_{$branchId}");

        return redirect()->back()->with('success', 'Branch deleted successfully');
    }
    public function pos(\Illuminate\Http\Request $request, \App\Models\Branch $branch)
    {
        $startDate = $request->get('start_date', now()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $branch->load('posDevices.account');
        $posDevices = $branch->posDevices;

        foreach ($posDevices as $pos) {
            $ordersQuery = \App\Models\Order::where('pos_id', $pos->id)
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);

            $orders = $ordersQuery->get();

            $pos->stats = [
                'cashier_name' => $orders->last()?->cashier?->name ?? 'None',
                'order_count' => $orders->count(),
                'total_amount' => $orders->sum('total_amount'),
                'cash_in_hand' => \App\Models\JournalEntry::where('debit_account_code', $pos->account_code)->sum('debit') - \App\Models\JournalEntry::where('credit_account_code', $pos->account_code)->sum('credit')
            ];
        }

        return view('branches.pos', compact('branch', 'posDevices', 'startDate', 'endDate'));
    }

    public function storePos(\Illuminate\Http\Request $request, \App\Models\Branch $branch)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $data = $request->all();
        if (!isset($data['connection_type'])) {
            $data['connection_type'] = 'network'; // default
        }

        $pos = $branch->posDevices()->create($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'POS Terminal created successfully and linked to account: ' . $pos->account_code]);
        }

        return redirect()->back()->with('success', 'POS Terminal created successfully');
    }
}
