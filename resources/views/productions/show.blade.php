@extends('layouts.app')

@section('title', 'Production Details #' . $production->id)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <!-- Header Section -->
            <div class="card-header bg-white border-0 py-4 px-4 bg-gradient" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold text-primary mb-1">
                            <i class="fa-solid fa-industry me-2"></i>Production Order
                        </h4>
                        <span class="badge bg-primary rounded-pill px-3">#PROD-{{ str_pad($production->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="text-end">
                        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4 btn-sm d-print-none">
                            <i class="fa-solid fa-print me-2"></i>Print Details
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <!-- Info Grid -->
                <div class="row mb-5 g-4">
                    <div class="col-md-3">
                        <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Finished Product</label>
                        <div class="h6 fw-bold mb-0">{{ $production->product->name_ar ?? $production->product->name_en }}</div>
                        <small class="text-muted">{{ $production->product->code }}</small>
                    </div>
                    <div class="col-md-3 border-start">
                        <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Destination Branch</label>
                        <div class="h6 fw-bold mb-0 text-primary">{{ $production->branch->name ?? 'Main Store or Deleted' }}</div>
                    </div>
                    <div class="col-md-3 border-start">
                        <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Production Date</label>
                        <div class="h6 fw-bold mb-0">{{ $production->created_at->format('M d, Y') }}</div>
                        <small class="text-muted">{{ $production->created_at->format('H:i') }}</small>
                    </div>
                    <div class="col-md-3 border-start">
                        <label class="small fw-bold text-muted text-uppercase mb-1 d-block">Quantity & Cost</label>
                        <div class="h6 fw-bold mb-0">Qty: {{ number_format($production->quantity_produced, 2) }}</div>
                        <div class="text-success fw-bold">Total: ${{ number_format($production->total_cost, 2) }}</div>
                    </div>
                </div>

                <!-- Ingredients Table -->
                <div class="mb-4">
                    <h6 class="fw-bold mb-3 border-bottom pb-2">
                        <i class="fa-solid fa-flask me-2 text-info"></i>Ingredients Used (Consumption Detail)
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3 ps-3">Ingredient / Item</th>
                                    <th class="py-3 text-center">Qty per Unit</th>
                                    <th class="py-3 text-center">Total Consumed</th>
                                    <th class="py-3 text-end pe-3">Estimated Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($production->product->recipe)
                                    @foreach($production->product->recipe->ingredients as $item)
                                        @php
                                            $ref = $item->ingredient ?? $item->childProduct;
                                            $name = $ref->name_ar ?? $ref->name_en ?? $ref->name;
                                            $consumed = $item->quantity * $production->quantity_produced;
                                            $unitCost = $item->ingredient ? $item->ingredient->cost_price : ($item->childProduct->base_purchase_price ?? 0);
                                        @endphp
                                        <tr>
                                            <td class="ps-3 border-0">
                                                <div class="fw-semibold">{{ $name }}</div>
                                                <small class="text-muted">{{ $ref->code ?? '' }}</small>
                                            </td>
                                            <td class="text-center border-0">{{ number_format($item->quantity, 3) }}</td>
                                            <td class="text-center border-0">
                                                <span class="badge bg-light text-dark px-3">{{ number_format($consumed, 2) }}</span>
                                            </td>
                                            <td class="text-end pe-3 border-0 fw-bold">
                                                ${{ number_format($consumed * $unitCost, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small italic">Recipe data not available for this production batch.</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot class="border-top">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold py-3">Batch Production Cost:</td>
                                    <td class="text-end pe-3 fw-bold py-3 text-success fs-5">${{ number_format($production->total_cost, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Footer Summary -->
                <div class="alert alert-light border-0 rounded-4 bg-light bg-opacity-50 p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-1">Production Summary</h6>
                            <p class="text-muted small mb-0">This production was authorized and performed by registered user ID#{{ $production->performed_by }}. All ingredients have been deducted from central/branch inventory according to the approved recipe.</p>
                        </div>
                        <div class="col-md-4 text-center border-start">
                            <label class="small text-muted d-block mb-1">Unit Production Cost</label>
                            <div class="h4 fw-bold mb-0">${{ number_format($production->unit_cost, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 d-print-none text-center">
                    <a href="{{ route('productions.index') }}" class="btn btn-secondary rounded-pill px-5">
                        <i class="fa-solid fa-arrow-left me-2"></i>Back to History
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, .navbar, .sidebar { display: none !important; }
        .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        body { background: white !important; }
        .content-wrapper { margin: 0 !important; padding: 0 !important; }
    }
</style>
@endsection
