@extends('layouts.app')

@section('title', 'Production History')

@section('content')
    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 p-4">
        <form action="{{ route('productions.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold text-muted">Global Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="fa-solid fa-search text-primary"></i></span>
                    <input type="text" name="search" class="form-control border-0 rounded-end-3" 
                        placeholder="Product, Branch, or Account Code..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">From Date</label>
                <input type="date" name="start_date" class="form-control border-0" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted">To Date</label>
                <input type="date" name="end_date" class="form-control border-0" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill flex-grow-1">
                    <i class="fa-solid fa-filter me-1"></i> Filter
                </button>
                <a href="{{ route('productions.index') }}" class="btn btn-light rounded-pill border">
                    <i class="fa-solid fa-undo"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header border-0 d-flex justify-content-between align-items-center" style="background: transparent;">
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Production History
            </h5>
            <a href="{{ route('productions.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="fa-solid fa-plus me-2"></i>New Production
            </a>
        </div>

        <div class="table-responsive" style="min-height: 400px;">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light bg-opacity-75">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Date & Time</th>
                        <th>Product & Branch</th>
                        <th class="text-center">Quantity</th>
                        <th>Unit Cost</th>
                        <th>Total Cost</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productions as $production)
                        <tr>
                            <td class="ps-4 text-muted small">#{{ $production->id }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $production->created_at->format('Y-m-d') }}</div>
                                <div class="small text-muted">{{ $production->created_at->format('H:i') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">
                                    {{ $production->product->name_ar ?? $production->product->name_en ?? 'Product Deleted' }}
                                </div>
                                <div class="small badge bg-light text-muted border">
                                    <i class="fa-solid fa-store me-1"></i>{{ $production->branch->name ?? 'Main Store' }}
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill fs-7">
                                    {{ number_format($production->quantity_produced, 2) }} Units
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small">$</span>{{ number_format($production->unit_cost, 2) }}
                            </td>
                            <td>
                                <span class="fw-bold text-success">${{ number_format($production->total_cost, 2) }}</span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light rounded-circle shadow-none border-0" type="button" 
                                        data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li>
                                            <a class="dropdown-item py-2" href="{{ route('productions.show', $production->id) }}">
                                                <i class="fa-solid fa-eye me-2 opacity-50 text-primary"></i> View Production Sheet
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider opacity-50"></li>
                                        <li>
                                            <a class="dropdown-item py-2 text-muted" href="#" onclick="alert('Reproduction feature coming soon...')">
                                                <i class="fa-solid fa-copy me-2 opacity-50"></i> Repeat Production
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="mb-2"><i class="fa-solid fa-box-open fa-3x opacity-20"></i></div>
                                No production history found matching your filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($productions->hasPages())
            <div class="card-footer border-0 py-3" style="background: transparent;">
                {{ $productions->links() }}
            </div>
        @endif
    </div>
@endsection