@extends('layouts.app')

@section('title', $branch->name . ' - ' . __('messages.branch_details'))

@section('content')
    <div class="row">
        <!-- Branch Overview & Stats -->
        <div class="col-md-12 mb-4">
            <div class="card border-0 shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h3 class="fw-bold mb-1">{{ $branch->name }}</h3>
                        <p class="text-muted mb-0">
                            <i class="fa-solid fa-location-dot me-2"></i>{{ $branch->address ?? 'No Address' }} |
                            <i class="fa-solid fa-phone me-2"></i>{{ $branch->phone ?? 'No Phone' }}
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success px-3 py-2 rounded-pill mb-2">Active</span>
                        <div class="text-muted small">
                            <i class="fa-solid fa-calendar-day me-1"></i>
                            Showing: <strong>{{ $startDate }}</strong> to <strong>{{ $endDate }}</strong>
                        </div>
                    </div>
                </div>

                <hr class="opacity-25 mb-4">

                <form action="{{ route('branches.show', $branch->id) }}" method="GET" class="row g-3 align-items-end mb-2">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Start Date</label>
                        <input type="date" name="start_date" class="form-control rounded-3" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">End Date</label>
                        <input type="date" name="end_date" class="form-control rounded-3" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100 rounded-3">
                            <i class="fa-solid fa-filter me-2"></i>Filter Range
                        </button>
                    </div>
                </form>

                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="p-4 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-10 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-primary text-white rounded-3 p-2 me-3">
                                    <i class="fa-solid fa-cart-shopping"></i>
                                </div>
                                <h6 class="mb-0 text-primary fw-bold small">Total Sales</h6>
                            </div>
                            <h4 class="fw-bold mb-0">${{ number_format($stats['total_sales'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-4 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-10 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-info text-white rounded-3 p-2 me-3">
                                    <i class="fa-solid fa-sack-dollar"></i>
                                </div>
                                <h6 class="mb-0 text-info fw-bold small">Gross Profit</h6>
                            </div>
                            <h4 class="fw-bold mb-0">${{ number_format($stats['gross_profit'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-4 rounded-4 bg-danger bg-opacity-10 border border-danger border-opacity-10 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-danger text-white rounded-3 p-2 me-3">
                                    <i class="fa-solid fa-money-bill-transfer"></i>
                                </div>
                                <h6 class="mb-0 text-danger fw-bold small">Total Expenses</h6>
                            </div>
                            <h4 class="fw-bold mb-0">${{ number_format($stats['total_expenses'], 2) }}</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-4 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-10 h-100">
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-success text-white rounded-3 p-2 me-3">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <h6 class="mb-0 text-success fw-bold small">Net Profit</h6>
                            </div>
                            <h4 class="fw-bold mb-0">${{ number_format($stats['total_profit'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Categorized -->
        <div class="col-md-12">
            <div class="card border-0 shadow-sm p-4">
                <h5 class="fw-bold mb-4">Branch Products by Category</h5>

                @forelse($categories as $category)
                    <div class="mb-5 last-child-mb-0">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-light rounded-pill px-3 py-1 border">
                                <h6 class="mb-0 fw-bold">{{ $category->name }}</h6>
                            </div>
                            <div class="flex-grow-1 ms-3 border-bottom opacity-50"></div>
                        </div>

                        <div class="row g-3">
                            @foreach($category->products as $product)
                                @php
                                    $branchData = $product->branchPrices->first();
                                @endphp
                                <div class="col-lg-3 col-md-4 col-sm-6">
                                    <div class="card h-100 border rounded-4 overflow-hidden product-card transition">
                                        @if($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" class="card-img-top"
                                                style="height: 140px; object-fit: cover;">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                style="height: 140px;">
                                                <i class="fa-solid fa-image fa-2x text-secondary opacity-25"></i>
                                            </div>
                                        @endif
                                        <div class="p-3">
                                            <h6 class="fw-bold mb-1 text-truncate">{{ $product->name }}</h6>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <span
                                                    class="text-primary fw-bold">${{ number_format($branchData->price ?? 0, 2) }}</span>
                                                <small class="text-muted">Stock: <span
                                                        class="fw-bold text-dark">{{ $branchData->stock_quantity ?? 0 }}</span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <p class="text-muted">No products assigned to this branch yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <style>
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .transition {
            transition: all 0.3s ease;
        }

        .last-child-mb-0:last-child {
            margin-bottom: 0 !important;
        }
    </style>
@endsection