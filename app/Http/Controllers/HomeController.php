<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $todayOrders = \App\Models\Order::whereDate('created_at', today())->count();
        $yesterdayOrders = \App\Models\Order::whereDate('created_at', today()->subDay())->count();
        $ordersChange = $yesterdayOrders > 0 ? (($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100 : 0;

        $thisMonthProfit = \App\Models\Order::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');
        $lastMonthProfit = \App\Models\Order::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->sum('total_amount');
        $profitChange = $lastMonthProfit > 0 ? (($thisMonthProfit - $lastMonthProfit) / $lastMonthProfit) * 100 : 0;

        $totalBookings = \App\Models\Booking::whereDate('booking_time', '>=', today())->count();
        $pendingBookings = \App\Models\Booking::where('status', 'pending')->count();

        $lowStockItems = \App\Models\BranchProduct::where('stock_quantity', '<', 10)->count();

        $branches = \App\Models\Branch::withCount([
            'orders' => function ($q) {
                $q->whereDate('created_at', today());
            }
        ])->get();

        $recentOrders = \App\Models\Order::with(['branch', 'cashier'])
            ->latest()
            ->take(6)
            ->get();

        $topProducts = \App\Models\OrderItem::select('product_id', \DB::raw('SUM(quantity) as total_qty'), \DB::raw('SUM(item_total) as total_revenue'))
            ->with(['product'])
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        $pendingVouchers = \App\Models\Voucher::where('status', 'DRAFT')->count();
        $recentVouchers = \App\Models\Voucher::latest()->take(5)->get();

        return view('home', compact(
            'todayOrders',
            'ordersChange',
            'thisMonthProfit',
            'profitChange',
            'totalBookings',
            'pendingBookings',
            'lowStockItems',
            'branches',
            'recentOrders',
            'topProducts',
            'pendingVouchers',
            'recentVouchers'
        ));
    }
}
