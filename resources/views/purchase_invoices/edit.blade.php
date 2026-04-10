@extends('layouts.app')

@section('title', 'Edit Purchase Invoice')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Edit Purchase Invoice #{{ $purchaseInvoice->invoice_number }}</h4>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('purchase_invoices.update', $purchaseInvoice->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ $purchaseInvoice->supplier_id == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name_ar ?? $supplier->name_en }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Invoice Date</label>
                    <input type="date" name="invoice_date" class="form-control" value="{{ $purchaseInvoice->invoice_date->format('Y-m-d') }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="1">{{ $purchaseInvoice->notes }}</textarea>
                </div>
            </div>

            <div class="mt-4">
                <h5>Items</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th width="40%">Ingredient / Raw Material</th>
                            <th width="20%">Quantity</th>
                            <th width="20%">Unit Price</th>
                            <th width="10%">Total</th>
                            <th>#</th>
                        </tr>
                    </thead>
                    <tbody id="items-table">
                        @foreach($purchaseInvoice->items as $index => $item)
                            <tr>
                                <td>
                                    <select name="items[{{ $index }}][ingredient_id]" class="form-control product-select" style="color: #000;">
                                        <option value="" class="text-muted">Select Ingredient...</option>
                                        @foreach($ingredients as $ingredient)
                                            <option value="{{ $ingredient->id }}" style="color: #000;" {{ $item->ingredient_id == $ingredient->id ? 'selected' : '' }}>
                                                {{ $ingredient->name_ar ?? $ingredient->name_en }} ({{ $ingredient->unit }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][quantity]" class="form-control qty" min="0.001" step="0.001" value="{{ $item->quantity }}">
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $index }}][unit_price]" class="form-control price" min="0" step="0.01" value="{{ $item->unit_price }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control total" readonly value="{{ number_format($item->total_price, 2, '.', '') }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <button type="button" class="btn btn-secondary btn-sm" id="add-row">Add Item</button>

                <div class="text-end mt-3">
                    <h4>Total: <span id="grand-total">{{ number_format($purchaseInvoice->total_amount, 2, '.', '') }}</span></h4>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="{{ route('purchase_invoices.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Invoice</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let i = {{ count($purchaseInvoice->items) }};

            // Add Row
            document.getElementById('add-row').addEventListener('click', function () {
                let row = `<tr>
                        <td>
                            <select name="items[${i}][ingredient_id]" class="form-control product-select" style="color: #000;">
                                <option value="" class="text-muted">Select Ingredient...</option>
                                @foreach($ingredients as $ingredient)
                                    <option value="{{ $ingredient->id }}" style="color: #000;">
                                        {{ $ingredient->name_ar ?? $ingredient->name_en }} ({{ $ingredient->unit }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="items[${i}][quantity]" class="form-control qty" min="0.001" step="0.001" value="1">
                        </td>
                        <td>
                            <input type="number" name="items[${i}][unit_price]" class="form-control price" min="0" step="0.01" value="0">
                        </td>
                        <td>
                            <input type="text" class="form-control total" readonly value="0.00">
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>`;
                document.getElementById('items-table').insertAdjacentHTML('beforeend', row);
                i++;
            });

            // Remove Row
            document.getElementById('items-table').addEventListener('click', function (e) {
                if (e.target.closest('.remove-row')) {
                    e.target.closest('tr').remove();
                    calculateTotal();
                }
            });

            // Calculations
            document.getElementById('items-table').addEventListener('input', function (e) {
                if (e.target.classList.contains('qty') || e.target.classList.contains('price')) {
                    let row = e.target.closest('tr');
                    let qty = parseFloat(row.querySelector('.qty').value) || 0;
                    let price = parseFloat(row.querySelector('.price').value) || 0;
                    row.querySelector('.total').value = (qty * price).toFixed(2);
                    calculateTotal();
                }
            });

            function calculateTotal() {
                let total = 0;
                document.querySelectorAll('.total').forEach(function (el) {
                    total += parseFloat(el.value) || 0;
                });
                document.getElementById('grand-total').textContent = total.toFixed(2);
            }
        });
    </script>
@endsection
