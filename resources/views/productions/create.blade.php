@extends('layouts.app')

@section('title', 'New Production')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">New Production (Assembly)</h4>
            <a href="{{ route('productions.index') }}" class="btn btn-secondary">
                Back to List
            </a>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('productions.store') }}" method="POST">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold">Production Location (Branch)</label>
                    <select name="branch_id" class="form-select" required>
                        <option value="">Select Branch...</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name_ar ?? $branch->name_en ?? $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Finished Product</label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">Select Product...</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name_ar ?? $product->name_en ?? $product->name }}
                                {{ $product->recipe ? '' : '(No Recipe)' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Select a product to calculate required ingredients.</small>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Quantity to Produce</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" step="0.01" min="0.01"
                        placeholder="e.g., 80" required>
                </div>
            </div>

            <div id="calculation-area" class="mt-4 d-none">
                <h5>Raw Materials Required</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Ingredient</th>
                                <th>Required Qty</th>
                                <th>Current Stock</th>
                                <th>Unit Cost (Avg)</th>
                                <th>Total Cost</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="ingredients-list">
                            <!-- Populated via JS -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total Estimated Production Cost</th>
                                <th class="fw-bold fs-5 text-primary">$<span id="grand-total">0.00</span></th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-end">Estimated Cost Per Unit</th>
                                <th class="fw-bold text-secondary">$<span id="unit-cost">0.00</span></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="button" id="calculate-btn" class="btn btn-info text-white">
                    <i class="fa-solid fa-calculator me-2"></i> Calculate & Check Stock
                </button>
                <button type="submit" id="submit-btn" class="btn btn-success fw-bold" disabled>
                    <i class="fa-solid fa-check me-2"></i> Confirm Production
                </button>
            </div>
        </form>
    </div>

    <!-- Error Modal Trigger Button (Hidden) -->
    <button type="button" id="triggerErrorModalBtn" class="d-none" data-bs-toggle="modal"
        data-bs-target="#errorModal"></button>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-circle-exclamation me-2"></i> Action Required</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="mb-3">
                        <i class="fa-solid fa-triangle-exclamation fa-3x text-danger opacity-50"></i>
                    </div>
                    <h5 class="mb-3 fw-bold text-dark">Cannot Proceed</h5>
                    <p class="text-muted" id="errorModalMessage">Something went wrong.</p>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const productSelect = document.getElementById('product_id');
            const quantityInput = document.getElementById('quantity');
            const calculateBtn = document.getElementById('calculate-btn');
            const submitBtn = document.getElementById('submit-btn');
            const calculationArea = document.getElementById('calculation-area');
            const ingredientsList = document.getElementById('ingredients-list');
            const grandTotalSpan = document.getElementById('grand-total');
            const unitCostSpan = document.getElementById('unit-cost');

            const errorMsgEl = document.getElementById('errorModalMessage');
            const triggerErrorModalBtn = document.getElementById('triggerErrorModalBtn');

            function showError(msg) {
                errorMsgEl.innerHTML = msg;
                triggerErrorModalBtn.click(); // Trigger modal via button click (safest method)
            }

            calculateBtn.addEventListener('click', function () {
                const productId = productSelect.value;
                const quantity = quantityInput.value;

                if (!productId || !quantity || quantity <= 0) {
                    showError('Please select a product and enter a valid quantity greater than 0.');
                    return;
                }

                // Show loading state
                calculateBtn.disabled = true;
                calculateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Calculating...';
                ingredientsList.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 text-muted">Analyzing Recipe & Stock...</div></td></tr>';
                calculationArea.classList.remove('d-none');
                submitBtn.disabled = true;

                fetch("{{ route('productions.calculate') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        branch_id: document.querySelector('select[name="branch_id"]').value,
                        product_id: productId,
                        quantity: quantity
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        calculateBtn.disabled = false;
                        calculateBtn.innerHTML = '<i class="fa-solid fa-calculator me-2"></i> Calculate & Check Stock';

                        if (data.error) {
                            ingredientsList.innerHTML = `<tr><td colspan="6" class="text-danger text-center fw-bold py-3"><i class="fa-solid fa-circle-xmark me-2"></i>${data.error}</td></tr>`;
                            showError(data.error);
                            return;
                        }

                        ingredientsList.innerHTML = '';
                        let allStockSufficient = true;

                        data.ingredients.forEach(ing => {
                            const statusBadge = ing.sufficient_stock
                                ? '<div class="d-flex align-items-center text-success"><i class="fa-solid fa-check-circle me-1"></i> Available</div>'
                                : '<div class="d-flex align-items-center text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i> Shortage</div>';

                            if (!ing.sufficient_stock) allStockSufficient = false;

                            const row = `
                            <tr class="${!ing.sufficient_stock ? 'table-danger' : ''}">
                                <td class="fw-semibold">${ing.name}</td>
                                <td>${parseFloat(ing.required_qty).toFixed(3)} <small class="text-muted">${ing.unit}</small></td>
                                <td>${parseFloat(ing.current_stock).toFixed(3)} <small class="text-muted">${ing.unit}</small></td>
                                <td>$${parseFloat(ing.unit_cost).toFixed(2)}</td>
                                <td>$${parseFloat(ing.line_cost).toFixed(2)}</td>
                                <td>${statusBadge}</td>
                            </tr>
                        `;
                            ingredientsList.insertAdjacentHTML('beforeend', row);
                        });

                        grandTotalSpan.textContent = parseFloat(data.total_cost).toFixed(2);
                        unitCostSpan.textContent = parseFloat(data.unit_cost).toFixed(2);

                        if (allStockSufficient) {
                            submitBtn.disabled = false;
                        } else {
                            let msg = 'Insufficient stock for one or more ingredients.<br>Please check the list highlighted in red.';
                            if (data.max_possible_quantity !== undefined && data.max_possible_quantity >= 0) {
                                msg += `<br><br><div class="alert alert-warning border-0 shadow-sm mb-0"><i class="fa-solid fa-lightbulb me-2"></i> Based on available stock, you can produce a maximum of <strong>${data.max_possible_quantity}</strong> units.</div>`;
                            }
                            showError(msg);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        calculateBtn.disabled = false;
                        calculateBtn.innerHTML = '<i class="fa-solid fa-calculator me-2"></i> Calculate & Check Stock';
                        ingredientsList.innerHTML = '<tr><td colspan="6" class="text-danger text-center">Error calculating details. Please try again.</td></tr>';
                        showError('A technical error occurred while calculating. Please check console or try again.');
                    });
            });
        });
    </script>
@endsection