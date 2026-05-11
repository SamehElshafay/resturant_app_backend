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
            'items.modifiers' // If you have modifiers later
        ])->findOrFail($id);

        return view('orders.show', compact('order'));
    }

    /**
     * List all orders (Optional for now, but good to have)
     */
    public function index()
    {
        $orders = Order::with(['branch', 'cashier'])->latest()->paginate(20);
        return view('orders.index', compact('orders'));
    }
}
