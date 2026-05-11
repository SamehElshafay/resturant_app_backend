<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display the specified order with all its details.
     */
    public function show($id)
    {
        $order = Order::with([
            'items.product',
            'branch',
            'cashier',
            'driver',
            'table.zone',
        ])->findOrFail($id);

        return view('orders.show', compact('order'));
    }

    /**
     * List all orders (Optional for now, but good to have)
     */
    public function index(Request $request)
    {
        $query = Order::with(['branch', 'cashier', 'customer']);

        // Search by Order Number or Customer Name/Phone
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('daily_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by Source (Online/Offline)
        if ($request->filled('source')) {
            $query->where('is_offline', $request->source === 'offline');
        }

        // Filter by Branch
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by Date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();
        $branches = \App\Models\Branch::all();

        return view('orders.index', compact('orders', 'branches'));
    }
}
