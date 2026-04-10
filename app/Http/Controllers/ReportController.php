<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportType = $request->get('type', 'sales');
        $startDate = $request->get('start_date', Carbon::today()->toDateString());
        $endDate = $request->get('end_date', Carbon::today()->toDateString());
        $branchId = $request->get('branch_id');

        $branches = \App\Models\Branch::all();
        $data = [];

        switch ($reportType) {
            case 'sales':
                $data = $this->getSalesReport($startDate, $endDate, $branchId);
                break;
            case 'products':
                $data = $this->getProductsReport($startDate, $endDate, $branchId);
                break;
            case 'inventory':
                $data = $this->getInventoryReport($branchId);
                break;
        }

        return view('reports.index', compact('reportType', 'startDate', 'endDate', 'branchId', 'branches', 'data'));
    }

    private function getSalesReport($startDate, $endDate, $branchId)
    {
        $query = \App\Models\Order::whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        return [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_price'),
            'orders' => $orders,
        ];
    }

    private function getProductsReport($startDate, $endDate, $branchId)
    {
        $products = \App\Models\Product::with(['branchPrices'])->get();

        return [
            'products' => $products,
            'total_products' => $products->count(),
        ];
    }

    private function getInventoryReport($branchId)
    {
        $query = \App\Models\BranchProduct::with(['product', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $inventory = $query->get();

        return [
            'inventory' => $inventory,
            'total_stock' => $inventory->sum('stock_quantity'),
            'low_stock_items' => $inventory->where('stock_quantity', '<', 10)->count(),
        ];
    }
}
