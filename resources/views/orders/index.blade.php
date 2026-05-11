@extends('layouts.app')

@section('title', __('Orders'))

@section('content')
<div class="container-fluid animate__animated animate__fadeIn">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('messages.dashboard') ?? 'Dashboard' }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Orders') }}</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0">{{ __('All Orders') }}</h3>
        </div>
    </div>

    <!-- Filters & Search Form -->
    <div class="card border-0 shadow-sm mb-4" style="background: var(--card-bg); border-radius: 20px;">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('orders.index') }}" class="row g-3">
                
                <!-- Search -->
                <div class="col-md-3">
                    <label class="form-label small text-secondary">{{ __('Search') }}</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light-custom border-0 text-secondary">
                            <i class="fa-solid fa-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-0 bg-light-custom" 
                               placeholder="{{ __('Order #, Customer Name, Phone...') }}" 
                               value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Source Filter -->
                <div class="col-md-2">
                    <label class="form-label small text-secondary">{{ __('Source') }}</label>
                    <select name="source" class="form-select border-0 bg-light-custom">
                        <option value="">{{ __('All Sources') }}</option>
                        <option value="online" {{ request('source') == 'online' ? 'selected' : '' }}>{{ __('Online') }}</option>
                        <option value="offline" {{ request('source') == 'offline' ? 'selected' : '' }}>{{ __('Offline Sync') }}</option>
                    </select>
                </div>

                <!-- Branch Filter -->
                <div class="col-md-2">
                    <label class="form-label small text-secondary">{{ __('Branch') }}</label>
                    <select name="branch_id" class="form-select border-0 bg-light-custom">
                        <option value="">{{ __('All Branches') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <label class="form-label small text-secondary">{{ __('messages.status') ?? 'Status' }}</label>
                    <select name="status" class="form-select border-0 bg-light-custom">
                        <option value="">{{ __('All Statuses') }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('Completed') }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                    </select>
                </div>

                <!-- Date Filter -->
                <div class="col-md-2">
                    <label class="form-label small text-secondary">{{ __('Date') }}</label>
                    <input type="date" name="date" class="form-control border-0 bg-light-custom" 
                           value="{{ request('date') }}">
                </div>

                <!-- Buttons -->
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 rounded-3" title="{{ __('Filter') }}">
                        <i class="fa-solid fa-filter"></i>
                    </button>
                    @if(request()->anyFilled(['search', 'source', 'branch_id', 'status', 'date']))
                        <a href="{{ route('orders.index') }}" class="btn btn-light-custom ms-2 rounded-3" title="{{ __('Clear Filters') }}">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Orders Table Card -->
    <div class="card border-0 shadow-sm overflow-hidden" style="background: var(--card-bg); border-radius: 20px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="color: var(--text-main);">
                <thead style="background: rgba(0,0,0,0.03);">
                    <tr>
                        <th class="ps-4 border-0">{{ __('Order Number') }}</th>
                        <th class="border-0 text-center">{{ __('Source') }}</th>
                        <th class="border-0">{{ __('Customer') }}</th>
                        <th class="border-0">{{ __('Branch') }}</th>
                        <th class="border-0">{{ __('Amount') }}</th>
                        <th class="text-center border-0">{{ __('messages.status') ?? 'Status' }}</th>
                        <th class="pe-4 border-0">{{ __('Time') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr onclick="window.location='{{ route('orders.show', $order->id) }}'" 
                            style="border-bottom: 1px solid var(--border-color); cursor: pointer;" 
                            class="order-row">
                            <td class="ps-4 fw-bold text-primary">#{{ $order->order_number ?? $order->daily_number }}</td>
                            <td class="text-center">
                                @if($order->is_offline)
                                    <span class="badge rounded-pill bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2" title="{{ __('Offline Order') }}">
                                        <i class="fa-solid fa-cloud-slash small me-1"></i> {{ __('Offline') }}
                                    </span>
                                @else
                                    <span class="badge rounded-pill bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2" title="{{ __('Online Order') }}">
                                        <i class="fa-solid fa-wifi small me-1"></i> {{ __('Online') }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $order->customer->first_name ?? __('Walk-in') }}</td>
                            <td>
                                <span class="badge bg-light-custom text-main fw-normal">{{ $order->branch->name ?? 'N/A' }}</span>
                            </td>
                            <td class="fw-bold">${{ number_format($order->total_amount, 2) }}</td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-{{ $order->status === 'completed' ? 'success' : 'warning' }} bg-opacity-10 text-{{ $order->status === 'completed' ? 'success' : 'warning' }} px-3">
                                    {{ __($order->status) }}
                                </span>
                            </td>
                            <td class="pe-4 text-secondary small">{{ $order->created_at->format('Y-m-d h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-secondary">
                                <i class="fa-solid fa-receipt fa-3x mb-3 opacity-50"></i>
                                <p class="mb-0">{{ __('No orders found matching your criteria.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($orders->hasPages())
        <div class="card-footer border-0 bg-transparent p-4 d-flex justify-content-center">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<style>
    .bg-light-custom {
        background: var(--bg-color) !important;
        color: var(--text-main) !important;
    }
    .btn-light-custom {
        background: var(--bg-color);
        color: var(--text-main);
        border: 1px solid var(--border-color);
    }
    .btn-light-custom:hover {
        background: var(--border-color);
    }
    .order-row:hover {
        background: rgba(99, 102, 241, 0.05) !important;
    }
    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: var(--text-secondary) !important;
    }
    .form-select, .form-control {
        color: var(--text-main) !important;
    }
    .form-select option {
        background: var(--card-bg);
        color: var(--text-main);
    }
</style>
@endsection
