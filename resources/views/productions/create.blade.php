@extends('layouts.app')

@section('title', 'New Production')

@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">New Production (Multi-Assembly)</h4>
                    <p class="text-secondary small mb-0">Produce multiple items and calculate shared raw materials in one batch.</p>
                </div>
                <a href="{{ route('productions.index') }}" class="btn btn-light rounded-pill px-4">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to List
                </a>
            </div>

            @if(session('error'))
                <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4">
                    <i class="fa-solid fa-circle-xmark me-2"></i> {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('productions.store') }}" method="POST" id="productionForm">
                @csrf
                <!-- Branch Selection -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-dark">Production Location (Branch)</label>
                        <select name="branch_id" class="form-select rounded-3 p-3 shadow-sm border-2 border-primary shadow-none" required>
                            <option value="">Select Branch...</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name_ar ?? $branch->name_en ?? $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Multiple Products Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-borderless align-middle" id="itemsTable">
                        <thead class="bg-light rounded-3">
                            <tr class="text-secondary small fw-bold">
                                <th class="ps-3" style="width: 60%">Finished Product</th>
                                <th style="width: 30%">Quantity to Produce</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="itemsList">
                            <tr class="item-row">
                                <td class="ps-0">
                                    <select name="items[0][product_id]" class="form-select rounded-3 p-3 product-select" required>
                                        <option value="">Select Product...</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product->id }}">
                                                {{ $product->name_ar ?? $product->name_en ?? $product->name }}
                                                {{ $product->recipe ? '' : '(No Recipe)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="items[0][quantity]" class="form-control rounded-3 p-3 quantity-input" step="0.01" min="0.01" placeholder="0.00" required>
                                </td>
                                <td class="text-end pe-0">
                                    <button type="button" class="btn btn-outline-danger border-0 rounded-circle remove-row-btn" style="width: 45px; height: 45px;">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mb-5">
                    <button type="button" id="addRowBtn" class="btn btn-outline-primary rounded-pill px-4 border-2 fw-bold">
                        <i class="fa-solid fa-plus me-2"></i> Add Another Product
                    </button>
                </div>

                <hr class="opacity-10 mb-5">

                <!-- Results Area -->
                <div id="calculation-area" class="mt-4 d-none mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info p-2 rounded-circle me-3">
                            <i class="fa-solid fa-list-check text-white"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Total Raw Materials Required (Aggregated)</h5>
                    </div>
                    
                    <div class="table-responsive rounded-4 shadow-sm border overflow-hidden">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="small fw-bold text-secondary">
                                    <th class="ps-4">Ingredient</th>
                                    <th class="text-center">Total Required Qty</th>
                                    <th class="text-center">Current Stock</th>
                                    <th class="text-center">Est. Total Cost</th>
                                    <th class="text-end pe-4">Status</th>
                                </tr>
                            </thead>
                            <tbody id="ingredients-list">
                                <!-- Populated via JS -->
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="3" class="text-end ps-4 py-3">Batch Estimated Production Cost</th>
                                    <th class="text-center py-3 fw-bold fs-5 text-primary">$<span id="grand-total">0.00</span></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-end gap-3">
                    <button type="button" id="calculate-btn" class="btn btn-info btn-lg text-white rounded-pill px-5 shadow-sm">
                        <i class="fa-solid fa-calculator me-2"></i> Calculate & Check Stock
                    </button>
                    <button type="submit" id="submit-btn" class="btn btn-success btn-lg fw-bold rounded-pill px-5 shadow" disabled>
                        <i class="fa-solid fa-check me-2"></i> Confirm Multi-Production
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="itemRowTemplate">
    <tr class="item-row">
        <td class="ps-0">
            <select name="items[REPLACE_INDEX][product_id]" class="form-select rounded-3 p-3 product-select" required>
                <option value="">Select Product...</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">
                        {{ $product->name_ar ?? $product->name_en ?? $product->name }}
                        {{ $product->recipe ? '' : '(No Recipe)' }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="items[REPLACE_INDEX][quantity]" class="form-control rounded-3 p-3 quantity-input" step="0.01" min="0.01" placeholder="0.00" required>
        </td>
        <td class="text-end pe-0">
            <button type="button" class="btn btn-outline-danger border-0 rounded-circle remove-row-btn" style="width: 45px; height: 45px;">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<style>
    body { background-color: #f8f9fa; }
    .form-select, .form-control { border-color: #e2e8f0; transition: all 0.2s; }
    .form-select:focus, .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }
    .btn-info { background-color: #0ea5e9; border: none; }
    .btn-info:hover { background-color: #0284c7; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const itemsList = document.getElementById('itemsList');
        const addRowBtn = document.getElementById('addRowBtn');
        const calculateBtn = document.getElementById('calculate-btn');
        const submitBtn = document.getElementById('submit-btn');
        const calculationArea = document.getElementById('calculation-area');
        const ingredientsList = document.getElementById('ingredients-list');
        const grandTotalSpan = document.getElementById('grand-total');
        const itemRowTemplate = document.getElementById('itemRowTemplate').innerHTML;
        let rowIndex = 1;

        // Add row functionality
        addRowBtn.addEventListener('click', function() {
            const newRow = itemRowTemplate.replace(/REPLACE_INDEX/g, rowIndex);
            itemsList.insertAdjacentHTML('beforeend', newRow);
            rowIndex++;
        });

        // Remove row functionality
        itemsList.addEventListener('click', function(e) {
            if (e.target.closest('.remove-row-btn')) {
                const rows = document.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    e.target.closest('.item-row').remove();
                    submitBtn.disabled = true;
                } else {
                    window.showToast('You must have at least one product.', 'error');
                }
            }
        });

        // Calculate functionality
        calculateBtn.addEventListener('click', function () {
            const branchId = document.querySelector('select[name="branch_id"]').value;
            const itemRows = document.querySelectorAll('.item-row');
            
            const items = [];
            let isValid = true;

            itemRows.forEach(row => {
                const productId = row.querySelector('.product-select').value;
                const quantity = row.querySelector('.quantity-input').value;
                if (!productId || !quantity || quantity <= 0) {
                    isValid = false;
                } else {
                    items.push({ product_id: productId, quantity: parseFloat(quantity) });
                }
            });

            if (!branchId || !isValid || items.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please ensure branch is selected and all products have valid quantities.',
                    confirmButtonColor: '#6366f1'
                });
                return;
            }

            // UI Feedback
            calculateBtn.disabled = true;
            calculateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Calculating...';
            ingredientsList.innerHTML = '<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-info" role="status"></div><div class="mt-3 text-secondary">Analyzing Batch Requirements...</div></td></tr>';
            calculationArea.classList.remove('d-none');
            submitBtn.disabled = true;

            fetch("{{ route('productions.calculate') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    branch_id: branchId,
                    items: items
                })
            })
            .then(response => response.json())
            .then(data => {
                calculateBtn.disabled = false;
                calculateBtn.innerHTML = '<i class="fa-solid fa-calculator me-2"></i> Calculate & Check Stock';

                if (data.error) {
                    Swal.fire({ icon: 'error', title: 'Calculation Error', text: data.error });
                    calculationArea.classList.add('d-none');
                    return;
                }

                ingredientsList.innerHTML = '';
                let allStockSufficient = true;

                data.ingredients.forEach(ing => {
                    if (!ing.sufficient_stock) allStockSufficient = false;

                    const row = `
                        <tr class="${!ing.sufficient_stock ? 'table-danger' : ''}">
                            <td class="ps-4">
                                <div class="fw-bold text-dark">${ing.name}</div>
                            </td>
                            <td class="text-center">${parseFloat(ing.required_qty).toFixed(3)} <small class="text-muted">${ing.unit}</small></td>
                            <td class="text-center">${parseFloat(ing.current_stock).toFixed(3)} <small class="text-muted">${ing.unit}</small></td>
                            <td class="text-center fw-bold">$${parseFloat(ing.line_cost).toFixed(2)}</td>
                            <td class="text-end pe-4">
                                ${ing.sufficient_stock 
                                    ? '<span class="badge bg-success-soft text-success px-3 py-2 rounded-pill"><i class="fa-solid fa-check me-1"></i> Available</span>' 
                                    : '<span class="badge bg-danger-soft text-danger px-3 py-2 rounded-pill"><i class="fa-solid fa-triangle-exclamation me-1"></i> Out of Stock</span>'}
                            </td>
                        </tr>
                    `;
                    ingredientsList.insertAdjacentHTML('beforeend', row);
                });

                grandTotalSpan.textContent = parseFloat(data.total_cost).toFixed(2);

                if (allStockSufficient) {
                    submitBtn.disabled = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Ready for Production',
                        text: 'Stock is sufficient for all items in the batch.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Stock Shortage',
                        text: 'One or more raw materials are insufficient for this batch. Please check the list highlighted in red.',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                calculateBtn.disabled = false;
                calculateBtn.innerHTML = '<i class="fa-solid fa-calculator me-2"></i> Calculate & Check Stock';
                Swal.fire({ icon: 'error', title: 'Server Error', text: 'Something went wrong. Please check your internet connection or contact support.' });
            });
        });
    });
</script>

<style>
    .bg-success-soft { background: rgba(34, 197, 94, 0.1); }
    .bg-danger-soft { background: rgba(239, 68, 68, 0.1); }
</style>
@endsection