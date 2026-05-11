@extends('layouts.app')

@section('title', 'Order Details - #' . ($order->order_number ?? $order->daily_number))

@section('content')
<div class="container-fluid animate__animated animate__fadeIn">
    <!-- Header with Action Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Orders</a></li>
                    <li class="breadcrumb-item active">Order Details</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0">Order #{{ $order->order_number ?? $order->daily_number }}</h3>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4">
                <i class="fa-solid fa-print me-2"></i> Print Invoice
            </button>
            <a href="{{ route('home') }}" class="btn btn-primary rounded-pill px-4">
                <i class="fa-solid fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Order Details -->
        <div class="col-md-8">
            <!-- Order Items Card -->
            <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg); color: var(--text-main); border-radius: 20px;">
                <div class="card-header bg-transparent border-0 p-4">
                    <h5 class="fw-bold mb-0">Order Items</h5>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="bg-light-custom text-secondary">
                            <tr>
                                <th class="ps-4 border-0">Product</th>
                                <th class="border-0">Price</th>
                                <th class="border-0">Qty</th>
                                <th class="pe-4 border-0 text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-light-custom rounded-3 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-burger text-primary"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">{{ $item->product->name ?? 'Unknown Product' }}</h6>
                                            <small class="text-secondary">{{ $item->product->category->name ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>${{ number_format($item->price, 2) }}</td>
                                <td><span class="badge bg-light-custom text-main px-3 rounded-pill fw-normal">x{{ $item->quantity }}</span></td>
                                <td class="pe-4 text-end fw-bold">${{ number_format($item->item_total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-0">
                            <tr>
                                <td colspan="3" class="text-end py-3 border-0">Subtotal</td>
                                <td class="text-end pe-4 py-3 border-0 fw-semibold">${{ number_format($order->total_amount - $order->tax, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end py-2 border-0">Tax (VAT)</td>
                                <td class="text-end pe-4 py-2 border-0 text-danger">+ ${{ number_format($order->tax, 2) }}</td>
                            </tr>
                            <tr class="fs-5">
                                <td colspan="3" class="text-end py-3 border-0 fw-bold">Total Amount</td>
                                <td class="text-end pe-4 py-3 border-0 text-primary fw-bold">${{ number_format($order->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Additional Info Section -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 h-100" style="background: var(--card-bg); border-radius: 20px;">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i> Order Notes</h6>
                        <p class="text-secondary mb-0">{{ $order->notes ?? 'No special instructions provided for this order.' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 h-100" style="background: var(--card-bg); border-radius: 20px;">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-credit-card text-success me-2"></i> Payment Summary</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Amount Paid:</span>
                            <span class="fw-bold">${{ number_format($order->paid_amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Balance:</span>
                            <span class="fw-bold text-{{ $order->total_amount - $order->paid_amount > 0 ? 'danger' : 'success' }}">
                                ${{ number_format($order->total_amount - $order->paid_amount, 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Details -->
        <div class="col-md-4">
            <!-- Order Status & Metadata -->
            <div class="card border-0 shadow-sm p-4 mb-4" style="background: var(--card-bg); border-radius: 20px;">
                <h6 class="fw-bold mb-4">Metadata & Status</h6>
                
                <div class="mb-4">
                    <label class="small text-secondary d-block mb-1">Status</label>
                    <span class="badge rounded-pill bg-{{ $order->status === 'completed' ? 'success' : 'warning' }} bg-opacity-10 text-{{ $order->status === 'completed' ? 'success' : 'warning' }} px-3 py-2 w-100 fs-6">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>

                <div class="mb-4">
                    <label class="small text-secondary d-block mb-1">Order Type</label>
                    <div class="d-flex align-items-center p-2 bg-light-custom rounded-3">
                        <i class="fa-solid fa-{{ $order->type === 'dine_in' ? 'chair' : ($order->type === 'delivery' ? 'motorcycle' : 'bag-shopping') }} text-primary me-3"></i>
                        <span class="fw-bold">{{ ucfirst(str_replace('_', ' ', $order->type)) }}</span>
                    </div>
                </div>

                <div class="mb-0">
                    <label class="small text-secondary d-block mb-1">Source Connection</label>
                    @if($order->is_offline)
                        <div class="d-flex align-items-center p-2 bg-secondary bg-opacity-10 text-secondary rounded-3 border border-secondary border-opacity-25">
                            <i class="fa-solid fa-cloud-slash me-3"></i>
                            <span class="fw-bold">Offline Sync</span>
                        </div>
                    @else
                        <div class="d-flex align-items-center p-2 bg-info bg-opacity-10 text-info rounded-3 border border-info border-opacity-25">
                            <i class="fa-solid fa-wifi me-3"></i>
                            <span class="fw-bold">Real-time Online</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Entity Info Card -->
            <div class="card border-0 shadow-sm p-4" style="background: var(--card-bg); border-radius: 20px;">
                <h6 class="fw-bold mb-4">Personnel & Location</h6>
                
                <div class="d-flex align-items-center mb-4">
                    <div class="p-3 bg-light-custom rounded-circle me-3">
                        <i class="fa-solid fa-user-tie text-primary"></i>
                    </div>
                    <div>
                        <small class="text-secondary d-block">Cashier</small>
                        <span class="fw-bold">{{ $order->cashier->name ?? 'System' }}</span>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-4">
                    <div class="p-3 bg-light-custom rounded-circle me-3">
                        <i class="fa-solid fa-shop text-primary"></i>
                    </div>
                    <div>
                        <small class="text-secondary d-block">Branch</small>
                        <span class="fw-bold">{{ $order->branch->name ?? 'Central' }}</span>
                    </div>
                </div>

                @if($order->table_id)
                <div class="d-flex align-items-center mb-0">
                    <div class="p-3 bg-light-custom rounded-circle me-3">
                        <i class="fa-solid fa-table-cells-large text-primary"></i>
                    </div>
                    <div>
                        <small class="text-secondary d-block">Table / Zone</small>
                        <span class="fw-bold">{{ $order->table->number }} ({{ $order->table->zone->name ?? 'General' }})</span>
                    </div>
                </div>
                @endif

                @if($order->driver_id)
                <div class="d-flex align-items-center mb-0 mt-4">
                    <div class="p-3 bg-light-custom rounded-circle me-3">
                        <i class="fa-solid fa-motorcycle text-primary"></i>
                    </div>
                    <div>
                        <small class="text-secondary d-block">Delivery Driver</small>
                        <span class="fw-bold">{{ $order->driver->name ?? 'N/A' }}</span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-custom {
        background: var(--bg-color) !important;
    }
    .breadcrumb-item + .breadcrumb-item::before {
        color: var(--text-secondary);
    }
    .table th {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    @media print {
        .btn, nav, .sidebar { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #eee !important; }
    }
</style>
@endsection
