<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $branchId = $request->get('branch_id');
        $startDate = $request->get('start_date', now()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Get branches with optional search
        $branches = \App\Models\Branch::when($search, function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%");
        })->get();

        $selectedBranch = null;
        $zones = collect();
        $tableStats = [];

        if ($branchId) {
            $selectedBranch = \App\Models\Branch::find($branchId);

            if ($selectedBranch) {
                // Eager load zones and tables to avoid N+1
                $zones = \App\Models\Zone::where('branch_id', $branchId)
                    ->with(['tables'])
                    ->get();

                // Cache table statistics for 5 minutes
                // Key depends on branch and date range
                $cacheKey = "table_stats_branch_{$branchId}_{$startDate}_{$endDate}";

                $tableStats = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($branchId, $startDate, $endDate) {
                    return \App\Models\Order::where('branch_id', $branchId)
                        ->whereBetween('created_at', [
                            \Carbon\Carbon::parse($startDate)->startOfDay(),
                            \Carbon\Carbon::parse($endDate)->endOfDay()
                        ])
                        ->whereNotNull('table_id')
                        ->where('status', 'completed')
                        ->selectRaw('
                            table_id,
                            COUNT(*) as total_orders,
                            SUM(total_amount) as total_revenue,
                            AVG(total_amount) as avg_order_value
                        ')
                        ->groupBy('table_id')
                        ->get()
                        ->keyBy('table_id')
                        ->toArray();
                });
            }
        }

        return view('tables.index', compact(
            'branches',
            'selectedBranch',
            'zones',
            'tableStats',
            'startDate',
            'endDate',
            'search'
        ));
    }

    // Zone Management
    public function storeZone(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'name' => 'required',
        ]);

        \App\Models\Zone::create($request->all());
        return redirect()->back()->with('success', 'Zone created successfully');
    }

    public function updateZone(\Illuminate\Http\Request $request, \App\Models\Zone $zone)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $zone->update($request->all());
        return redirect()->back()->with('success', 'Zone updated successfully');
    }

    public function destroyZone(\App\Models\Zone $zone)
    {
        $zone->delete();
        return redirect()->back()->with('success', 'Zone deleted successfully');
    }

    // Table Management
    public function storeTable(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'zone_id' => 'required|exists:zones,id',
            'number' => 'required',
            'status' => 'required|in:available,busy,reserved',
        ]);

        \App\Models\RestaurantTable::create($request->all());
        return redirect()->back()->with('success', 'Table created successfully');
    }

    public function updateTable(\Illuminate\Http\Request $request, $id)
    {
        $table = \App\Models\RestaurantTable::findOrFail($id);

        $request->validate([
            'number' => 'required',
            'status' => 'required|in:available,busy,reserved',
        ]);

        $table->update($request->all());
        return redirect()->back()->with('success', 'Table updated successfully');
    }

    public function destroyTable($id)
    {
        $table = \App\Models\RestaurantTable::findOrFail($id);
        $table->delete();
        return redirect()->back()->with('success', 'Table deleted successfully');
    }
}
