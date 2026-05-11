@extends('layouts.app')

@section('title', __('messages.dashboard'))

@section('content')
    <!-- Top Stats Row -->
    <div class="row g-4 mb-4">
        <!-- Today's Orders -->
        <div class="col-md-3">
            <div class="card card-stats border-0 p-4 shadow-sm h-100"
                style="background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); transition: transform 0.3s ease;">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon-circle me-3">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div class="text-white">
                        <p class="mb-0 small opacity-75">{{ __('messages.today_orders') }}</p>
                        <h2 class="fw-bold mb-0">{{ $todayOrders }}</h2>
                    </div>
                </div>
                <p class="mb-0 small text-white opacity-75">
                    <i class="fa-solid fa-arrow-{{ $ordersChange >= 0 ? 'up' : 'down' }}"></i>
                    {{ number_format(abs($ordersChange), 1) }}% from yesterday
                </p>
            </div>
        </div>

        <!-- Net Profit -->
        <div class="col-md-3">
            <div class="card card-stats border-0 p-4 shadow-sm h-100"
                style="background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon-circle me-3">
                        <i class="fa-solid fa-dollar-sign"></i>
                    </div>
                    <div class="text-white">
                        <p class="mb-0 small opacity-75">{{ __('messages.net_profit') }}</p>
                        <h2 class="fw-bold mb-0">${{ number_format($thisMonthProfit, 0) }}</h2>
                    </div>
                </div>
                <p class="mb-0 small text-white opacity-75">
                    <i class="fa-solid fa-arrow-{{ $profitChange >= 0 ? 'up' : 'down' }}"></i>
                    {{ number_format(abs($profitChange), 1) }}% this month
                </p>
            </div>
        </div>

        <!-- Pending Vouchers (Financial Control) -->
        <div class="col-md-3">
            <div class="card card-stats border-0 p-4 shadow-sm h-100"
                style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon-circle me-3">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                    <div class="text-white">
                        <p class="mb-0 small opacity-75">Draft Vouchers</p>
                        <h2 class="fw-bold mb-0">{{ $pendingVouchers ?? 0 }}</h2>
                    </div>
                </div>
                <p class="mb-0 small text-white opacity-75">Awaiting posting to ledger</p>
            </div>
        </div>

        <!-- Inventory Alerts -->
        <div class="col-md-3">
            <div class="card card-stats border-0 p-4 shadow-sm h-100"
                style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="d-flex align-items-center mb-3">
                    <div class="stat-icon-circle me-3">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="text-white">
                        <p class="mb-0 small opacity-75">{{ __('messages.inventory_alerts') }}</p>
                        <h2 class="fw-bold mb-0">{{ $lowStockItems }}</h2>
                    </div>
                </div>
                <p class="mb-0 small text-white opacity-75">Products with low stock</p>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4 mb-4">
        <!-- Recent Orders -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm overflow-hidden h-100"
                style="background: var(--card-bg); color: var(--text-main);">
                <div class="card-header border-0 p-4 d-flex justify-content-between align-items-center"
                    style="background: transparent;">
                    <h5 class="fw-bold mb-0">Recent Orders</h5>
                    <a href="{{ route('orders.index') }}" class="btn btn-light-custom btn-sm rounded-pill px-3">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="color: var(--text-main);">
                        <thead style="background: rgba(0,0,0,0.03);">
                            <tr>
                                <th class="ps-4 border-0">Order Number</th>
                                <th class="border-0 text-center">Source</th>
                                <th class="border-0">Customer</th>
                                <th class="border-0">Branch</th>
                                <th class="border-0">Amount</th>
                                <th class="text-center border-0">Status</th>
                                <th class="pe-4 border-0">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                <tr onclick="window.location='{{ route('orders.show', $order->id) }}'" 
                                    style="border-bottom: 1px solid var(--border-color); cursor: pointer;" 
                                    class="order-row">
                                    <td class="ps-4 fw-bold text-primary">#{{ $order->order_number ?? $order->daily_number }}</td>
                                    <td class="text-center">
                                        @if($order->is_offline)
                                            <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2" title="Offline Order">
                                                <i class="fa-solid fa-cloud-slash small me-1"></i> Offline
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2" title="Online Order">
                                                <i class="fa-solid fa-wifi small me-1"></i> Online
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ $order->customer->first_name ?? 'Walk-in' }}</td>
                                    <td><span
                                            class="badge bg-light-custom text-main fw-normal">{{ $order->branch->name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="fw-bold">${{ number_format($order->total_amount, 2) }}</td>
                                    <td class="text-center">
                                        <span
                                            class="badge rounded-pill bg-{{ $order->status === 'completed' ? 'success' : 'warning' }} bg-opacity-10 text-{{ $order->status === 'completed' ? 'success' : 'warning' }} px-3">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="pe-4 text-secondary small">{{ $order->created_at->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-secondary">No recent orders found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Selling Products -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="background: var(--card-bg); color: var(--text-main);">
                <div class="card-header border-0 p-4" style="background: transparent;">
                    <h5 class="fw-bold mb-0">Top Selling Products</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($topProducts as $item)
                            <li class="list-group-item border-0 p-4 d-flex align-items-center"
                                style="background: transparent; color: var(--text-main); border-bottom: 1px solid var(--border-color) !important;">
                                <div class="p-3 bg-light-custom rounded-3 me-3">
                                    <i class="fa-solid fa-box text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 fw-bold">{{ $item->product->name ?? 'Unknown Product' }}</h6>
                                    <small class="text-secondary">{{ $item->total_qty }} units sold</small>
                                </div>
                                <div class="text-end">
                                    <span
                                        class="fw-bold text-success d-block">${{ number_format($item->total_revenue, 2) }}</span>
                                </div>
                            </li>
                        @empty
                            <p class="text-center py-5 text-secondary">No sales data yet</p>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="row g-4">
        <!-- Branch Performance -->
        <div class="col-md-8">
            <div class="card border-0 p-4 shadow-sm" style="background: var(--card-bg); color: var(--text-main);">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">{{ __('messages.branch_performance') }} Today</h5>
                    <a href="{{ route('branches.index') }}" class="btn btn-outline-primary rounded-pill btn-sm">Manage
                        Branches</a>
                </div>

                @if($branches->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="color: var(--text-main);">
                            <thead>
                                <tr style="background: rgba(0,0,0,0.03);">
                                    <th class="border-0">Branch Name</th>
                                    <th class="text-center border-0">Orders</th>
                                    <th class="text-center border-0">Status</th>
                                    <th class="border-0">Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branches as $branch)
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td class="fw-semibold">{{ $branch->name }}</td>
                                        <td class="text-center">
                                            <span
                                                class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">{{ $branch->orders_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-soft text-success">
                                                <i class="fa-solid fa-circle small me-1"></i> Running
                                            </span>
                                        </td>
                                        <td class="small text-secondary">{{ $branch->phone ?? 'No phone' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fa-solid fa-code-branch fa-3x text-secondary opacity-50 mb-3"></i>
                        <p class="text-secondary">No branches found.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions Redesigned -->
        <div class="col-md-4">
            <div class="card border-0 p-4 shadow-sm h-100" style="background: var(--card-bg); color: var(--text-main);">
                <h5 class="fw-bold mb-4 d-flex align-items-center">
                    <i class="fa-solid fa-bolt text-warning me-2"></i>
                    {{ __('messages.quick_actions') }}
                </h5>

                <div class="row g-3">
                    <!-- Register Employee -->
                    <div class="col-6">
                        <a href="{{ route('employees.index') }}"
                            class="quick-action-card p-3 text-center d-block text-decoration-none h-100">
                            <div class="icon-circle mb-2 mx-auto"
                                style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            <span class="small fw-semibold d-block text-main">New Employee</span>
                        </a>
                    </div>

                    <!-- Add Invoice -->
                    <div class="col-6">
                        <a href="{{ route('purchase_invoices.create') }}"
                            class="quick-action-card p-3 text-center d-block text-decoration-none h-100">
                            <div class="icon-circle mb-2 mx-auto"
                                style="background: rgba(236, 72, 153, 0.1); color: #ec4899;">
                                <i class="fa-solid fa-file-invoice"></i>
                            </div>
                            <span class="small fw-semibold d-block text-main">Supplier Bill</span>
                        </a>
                    </div>

                    <!-- Create Voucher -->
                    <div class="col-6">
                        <a href="{{ route('accounting.vouchers') }}"
                            class="quick-action-card p-3 text-center d-block text-decoration-none h-100">
                            <div class="icon-circle mb-2 mx-auto"
                                style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                <i class="fa-solid fa-money-bill-transfer"></i>
                            </div>
                            <span class="small fw-semibold d-block text-main">Accounting Voucher</span>
                        </a>
                    </div>

                    <!-- Setup Branch -->
                    <div class="col-6">
                        <a href="{{ route('branches.index') }}"
                            class="quick-action-card p-3 text-center d-block text-decoration-none h-100">
                            <div class="icon-circle mb-2 mx-auto"
                                style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="fa-solid fa-shop"></i>
                            </div>
                            <span class="small fw-semibold d-block text-main">Add Branch</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .quick-action-card {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .quick-action-card:hover {
            transform: translateY(-8px);
            background: var(--card-bg);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.1);
            border-color: #6366f1;
        }

        .icon-circle {
            width: 54px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            font-size: 1.4rem;
            transition: 0.3s;
        }

        .bg-success-soft {
            background: rgba(16, 185, 129, 0.1);
        }

        .card {
            border-radius: 24px;
            transition: 0.3s;
            background: var(--card-bg);
            color: var(--text-main);
            border: none;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        /* Stats Cards - Ensure gradients work and text is white */
        .card-stats {
            color: white !important;
            overflow: hidden;
        }
        .card-stats .text-white { color: white !important; }
        .card-stats .opacity-75 { opacity: 0.8 !important; }
        
        .stat-icon-circle {
            width: 64px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.25) !important;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .stat-icon-circle i {
            color: white !important;
            font-size: 1.75rem;
        }

        /* Table & List Dark Mode Fixes */
        .table {
            --bs-table-bg: transparent !important;
            color: var(--text-main) !important;
        }

        .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: var(--text-secondary) !important;
            border-bottom: 2px solid var(--border-color) !important;
            background: rgba(0, 0, 0, 0.02) !important;
        }

        [data-theme="dark"] .table thead th {
            background: rgba(255, 255, 255, 0.02) !important;
        }

        .table td {
            border-bottom-color: var(--border-color) !important;
            color: var(--text-main) !important;
            background: transparent !important;
        }

        .list-group-item {
            background-color: transparent !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }

        .btn-light-custom {
            background: var(--bg-color);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }

        .btn-light-custom:hover {
            background: var(--border-color);
        }

        .bg-light-custom {
            background: var(--bg-color) !important;
        }

        .text-main {
            color: var(--text-main) !important;
        }

        .text-secondary {
            color: var(--text-secondary) !important;
        }

        /* General UI Fixes */
        .bg-white {
            background-color: var(--card-bg) !important;
        }

        .text-dark {
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .bg-white {
            background-color: var(--card-bg) !important;
        }

        .table-light {
            --bs-table-bg: rgba(0, 0, 0, 0.02) !important;
        }

        .order-row:hover {
            background: rgba(99, 102, 241, 0.05) !important;
        }
    </style>
@endsection