@extends('layouts.app')

@section('title', 'View Purchase Invoice')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Invoice #{{ $purchaseInvoice->invoice_number }}</h4>
            <div>
                @if($purchaseInvoice->status == 'draft')
                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#approveModal">
                        Approve
                    </button>
                @elseif($purchaseInvoice->status == 'approved')
                    <span class="badge bg-success">Approved by {{ optional($purchaseInvoice->approver)->name }} at
                        {{ optional($purchaseInvoice->approved_at)->format('Y-m-d H:i') }}</span>
                @else
                    <span class="badge bg-danger">Cancelled</span>
                @endif
            </div>
        </div>

        {{-- ... Existing Invoice Details ... --}}

        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-success text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-check-circle me-2"></i> Confirm Approval</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <h4 class="mb-3">Approve this Invoice?</h4>
                        <p class="text-muted mb-4">
                            This action will <strong>update the stock quantities</strong> and recalculate ingredient costs
                            based on the weighted average.
                            <br><span class="text-danger">This action cannot be undone.</span>
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                            <a href="{{ route('purchase_invoices.approve', $purchaseInvoice->id) }}"
                                class="btn btn-success px-4 fw-bold">
                                Yes, Approve Invoice
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i> Confirm
                            Cancellation</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-center">
                        <h4 class="mb-3">Cancel this Invoice?</h4>
                        <p class="text-muted mb-4">
                            Are you sure you want to cancel this draft invoice?
                            <br>This action will mark the invoice as cancelled.
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Keep Draft</button>
                            <a href="{{ route('purchase_invoices.cancel', $purchaseInvoice->id) }}"
                                class="btn btn-danger px-4 fw-bold">
                                Yes, Cancel Invoice
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <strong>Supplier:</strong>
                {{ optional($purchaseInvoice->supplier)->name_ar ?? optional($purchaseInvoice->supplier)->name_en }}<br>
                <strong>Location:</strong>
                Central Warehouse<br>
                <strong>Date:</strong> {{ $purchaseInvoice->invoice_date->format('Y-m-d') }}
            </div>
            <div class="col-md-4">
                <strong>Payment Status:</strong> {{ ucfirst($purchaseInvoice->payment_status) }}<br>
                <strong>Notes:</strong> {{ $purchaseInvoice->notes ?? 'N/A' }}
            </div>
            <div class="col-md-4 text-end">
                <h3 class="text-primary">${{ number_format($purchaseInvoice->total_amount, 2) }}</h3>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Ingredient / Raw Material</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseInvoice->items as $item)
                    <tr>
                        <td>{{ optional($item->ingredient)->name_ar ?? optional($item->ingredient)->name_en }}</td>
                        <td>{{ $item->quantity }} {{ optional($item->ingredient)->unit }}</td>
                        <td>${{ number_format($item->unit_price, 2) }}</td>
                        <td>${{ number_format($item->total_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th>${{ number_format($purchaseInvoice->total_amount, 2) }}</th>
                </tr>
            </tfoot>
        </table>

        <div class="mt-4">
            <a href="{{ route('purchase_invoices.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
@endsection