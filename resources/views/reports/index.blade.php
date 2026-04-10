@extends('layouts.app')

@section('title', 'Reports')

@section('content')
    <div class="card p-4">
        <h4 class="fw-bold mb-4">Reports</h4>

        <!-- Filters -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select name="type" class="form-select">
                    <option value="sales" {{ $reportType == 'sales' ? 'selected' : '' }}>Sales Report</option>
                    <option value="products" {{ $reportType == 'products' ? 'selected' : '' }}>Products Report</option>
                    <option value="inventory" {{ $reportType == 'inventory' ? 'selected' : '' }}>Inventory Report</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Branch</label>
                <select name="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name_ar ?? $branch->name_en }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>

        <!-- Report Content -->
        @if($reportType == 'sales')
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6>Total Orders</h6>
                            <h2>{{ $data['total_orders'] ?? 0 }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6>Total Revenue</h6>
                            <h2>${{ number_format($data['total_revenue'] ?? 0, 2) }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['orders'] ?? [] as $order)
                            <tr>
                                <td>#{{ $order->id }}</td>
                                <td>{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $order->branch->name_ar ?? $order->branch->name_en ?? 'N/A' }}</td>
                                <td class="fw-bold">${{ number_format($order->total_price, 2) }}</td>
                                <td><span class="badge bg-primary">{{ $order->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No orders found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($reportType == 'products')
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6>Total Products</h6>
                            <h2>{{ $data['total_products'] ?? 0 }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Base Price</th>
                            <th>Total Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['products'] ?? [] as $product)
                            <tr>
                                <td class="fw-semibold">{{ $product->name_ar ?? $product->name_en }}</td>
                                <td>{{ $product->category->name_ar ?? $product->category->name_en ?? 'N/A' }}</td>
                                <td>${{ number_format($product->base_purchase_price, 2) }}</td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $product->branchPrices->sum('stock_quantity') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No products found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($reportType == 'inventory')
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6>Total Stock</h6>
                            <h2>{{ $data['total_stock'] ?? 0 }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6>Low Stock Items</h6>
                            <h2>{{ $data['low_stock_items'] ?? 0 }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Branch</th>
                            <th>Stock Qty</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['inventory'] ?? [] as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->product->name_ar ?? $item->product->name_en }}</td>
                                <td>{{ $item->branch->name_ar ?? $item->branch->name_en }}</td>
                                <td>{{ $item->stock_quantity ?? 0 }}</td>
                                <td>${{ number_format($item->price ?? 0, 2) }}</td>
                                <td>
                                    @if(($item->stock_quantity ?? 0) < 10)
                                        <span class="badge bg-danger">Low Stock</span>
                                    @else
                                        <span class="badge bg-success">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No inventory data found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection