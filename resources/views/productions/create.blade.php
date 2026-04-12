@extends('layouts.app')

@section('title', 'New Production')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Production Assembly</h2>
                    <p class="text-secondary mb-0">Aggregate raw materials and manage multi-product batches</p>
                </div>
                <a href="{{ route('productions.index') }}" class="btn btn-white shadow-sm rounded-pill px-4 border">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to List
                </a>
            </div>

            @if(session('error'))
                <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 p-3 animate__animated animate__fadeIn">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-circle-xmark fs-4 me-3"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                </div>
            @endif

            <form action="{{ route('productions.store') }}" method="POST" id="productionForm">
                @csrf
                
                <!-- Step 1: Branch & Products -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4 p-lg-5">
                        <div class="row align-items-end mb-5 g-4">
                            <div class="col-lg-4">
                                <label class="form-label fw-bold text-dark small text-uppercase tracking-wider">Production Batch Location</label>
                                <div class="input-group overflow-hidden rounded-3 border-2 border-primary-subtle shadow-sm">
                                    <span class="input-group-text bg-white border-0 ps-3">
                                        <i class="fa-solid fa-code-branch text-primary"></i>
                                    </span>
                                    <select name="branch_id" class="form-select border-0 p-3 bg-white" required>
                                        <option value="">Select Target Branch...</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name_ar ?? $branch->name_en ?? $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-8 text-lg-end">
                                <button type="button" id="addRowBtn" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-bold border-2 transition-all hover-lift">
                                    <i class="fa-solid fa-plus-circle me-2"></i> Add Finished Product
                                </button>
                            </div>
                        </div>

                        <!-- Dynamic Items Area -->
                        <div id="itemsContainer" class="row g-3">
                            <div class="col-12 item-row animate__animated animate__fadeIn">
                                <div class="production-item-row p-2 ps-4 pe-2 rounded-4 d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <select name="items[0][product_id]" class="form-select border-0 bg-transparent fw-bold product-select" required>
                                            <option value="">Select Item to Produce...</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">
                                                    {{ $product->name_ar ?? $product->name_en ?? $product->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="vertical-divider"></div>
                                    <div style="width: 120px;">
                                        <input type="number" name="items[0][quantity]" class="form-control border-0 bg-light text-center fw-bold rounded-3 py-2 quantity-input" step="0.01" min="0.01" placeholder="0.00">
                                    </div>
                                    <div class="ms-3">
                                        <button type="button" class="btn btn-remove-item remove-row-btn" title="Remove row">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Aggregated Calculation Results -->
                <div id="calculation-area" class="d-none animate__animated animate__fadeInUp">
                    <!-- Cost Summary Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white overflow-hidden">
                                <div class="card-body p-4 position-relative">
                                    <i class="fa-solid fa-sack-dollar position-absolute top-50 end-0 translate-middle-y me-4 opacity-25 fa-3x"></i>
                                    <p class="mb-1 small opacity-75 fw-bold">ESTIMATED BATCH COST</p>
                                    <h2 class="fw-bold mb-0 lh-1">$<span id="grand-total">0.00</span></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm rounded-4 bg-white border border-info overflow-hidden">
                                <div class="card-body p-4 position-relative">
                                    <i class="fa-solid fa-boxes-stacked position-absolute top-50 end-0 translate-middle-y me-4 text-info opacity-10 fa-3x"></i>
                                    <p class="mb-1 small text-info fw-bold">ITEMS IN BATCH</p>
                                    <h2 class="fw-bold mb-0 lh-1 text-dark"><span id="items-count">0</span> Items</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div id="stock-status-card" class="card border-0 shadow-sm rounded-4 bg-white border overflow-hidden">
                                <div class="card-body p-4 d-flex align-items-center">
                                    <div id="status-icon-box" class="rounded-circle p-3 me-3">
                                        <i id="status-icon" class="fa-solid fs-4"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 small fw-bold opacity-75">STOCK STATUS</p>
                                        <h5 id="status-text" class="fw-bold mb-0">Ready</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ingredients Detail Table -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 p-4 pb-0">
                            <h5 class="fw-bold mb-0"><i class="fa-solid fa-leaf text-success me-2"></i> Aggregated Raw Materials</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive rounded-3 overflow-hidden border">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr class="small text-muted">
                                            <th class="ps-4 py-3">Raw Material</th>
                                            <th class="text-center py-3">Required</th>
                                            <th class="text-center py-3">In Stock</th>
                                            <th class="text-center py-3">Est. Cost</th>
                                            <th class="text-end pe-4 py-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ingredients-list">
                                        <!-- JS Content -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="d-flex justify-content-center align-items-center gap-3 py-4 sticky-bottom bg-white bg-opacity-75 backdrop-blur rounded-pill shadow-lg border mt-5 mx-auto" style="max-width: 600px; z-index: 100;">
                    <button type="button" id="calculate-btn" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm transition-all hover-scale">
                        <i class="fa-solid fa-calculator me-2"></i> Analyze Requirements
                    </button>
                    <button type="submit" id="submit-btn" class="btn btn-success rounded-pill px-5 py-2 fw-bold shadow transition-all hover-scale" disabled>
                        <i class="fa-solid fa-circle-check me-2"></i> Confirm Production
                    </button>
                    <div id="loading-spinner" class="d-none ms-2">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Row Template -->
<template id="itemRowTemplate">
    <div class="col-12 item-row animate__animated animate__fadeIn">
        <div class="production-item-row p-2 ps-4 pe-2 rounded-4 d-flex align-items-center">
            <div class="flex-grow-1">
                <select name="items[REPLACE_INDEX][product_id]" class="form-select border-0 bg-transparent fw-bold product-select" required>
                    <option value="">Select Item to Produce...</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->name_ar ?? $product->name_en ?? $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="vertical-divider"></div>
            <div style="width: 120px;">
                <input type="number" name="items[REPLACE_INDEX][quantity]" class="form-control border-0 bg-light text-center fw-bold rounded-3 py-2 quantity-input" step="0.01" min="0.01" placeholder="0.00">
            </div>
            <div class="ms-3">
                <button type="button" class="btn btn-remove-item remove-row-btn" title="Remove row">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    </div>
</template>

<style>
    body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
    .backdrop-blur { backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
    .transition-all { transition: all 0.3s ease; }
    .hover-lift:hover { transform: translateY(-2px); }
    .hover-scale:hover { transform: scale(1.05); }
    .hover-opacity-100:hover { opacity: 1 !important; }
    
    .form-select, .form-control { box-shadow: none !important; }
    .badge-soft-success { background: rgba(34, 197, 94, 0.1); color: #198754; }
    .badge-soft-danger { background: rgba(244, 63, 94, 0.1); color: #e11d48; }
    
    .sticky-bottom { position: sticky; bottom: 2rem; }

    /* Polished Production Row */
    .production-item-row {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .production-item-row:hover {
        border-color: #6366f1;
        transform: translateX(-5px); /* Soft highlight move for RTL */
    }
    [dir="ltr"] .production-item-row:hover {
        transform: translateX(5px);
    }

    .vertical-divider {
        width: 1px;
        height: 40px;
        background: #f1f5f9;
        margin: 0 15px;
    }

    .btn-remove-item {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff1f2;
        color: #f43f5e;
        border: none;
        transition: all 0.2s;
        opacity: 0.6;
    }
    .btn-remove-item:hover {
        opacity: 1;
        background: #f43f5e;
        color: white;
        transform: rotate(90deg);
    }

    /* Select2 Polishing */
    .select2-container--bootstrap-5 .select2-selection {
        border: none !important;
        background: transparent !important;
        font-weight: 700 !important;
        font-size: 1.05rem !important;
        color: #1e293b !important;
    }
    .select2-container--bootstrap-5 .select2-selection__placeholder {
        color: #94a3b8 !important;
        font-weight: 500 !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const itemsContainer = document.getElementById('itemsContainer');
        const addRowBtn = document.getElementById('addRowBtn');
        const calculateBtn = document.getElementById('calculate-btn');
        const submitBtn = document.getElementById('submit-btn');
        const calculationArea = document.getElementById('calculation-area');
        const ingredientsList = document.getElementById('ingredients-list');
        const grandTotalSpan = document.getElementById('grand-total');
        const itemsCountSpan = document.getElementById('items-count');
        const itemRowTemplate = document.getElementById('itemRowTemplate').innerHTML;
        const loadingSpinner = document.getElementById('loading-spinner');
        
        let rowIndex = 1;

        // Initialize Select2 for a specific element
        function initSelect2(element) {
            $(element).select2({
                placeholder: "Search for a product...",
                allowClear: true,
                theme: "bootstrap-5", // If you have bootstrap-5 theme, otherwise it uses default
                width: '100%'
            });
        }

        // Initialize existing rows
        document.querySelectorAll('.product-select').forEach(sel => initSelect2(sel));

        addRowBtn.addEventListener('click', () => {
            const newRowHtml = itemRowTemplate.replace(/REPLACE_INDEX/g, rowIndex++);
            itemsContainer.insertAdjacentHTML('beforeend', newRowHtml);
            
            // Re-initialize Select2 for the LAST added row's select
            const newRow = itemsContainer.lastElementChild;
            initSelect2(newRow.querySelector('.product-select'));
            
            resetState();
        });

        itemsContainer.addEventListener('click', (e) => {
            if (e.target.closest('.remove-row-btn')) {
                const rows = document.querySelectorAll('.item-row');
                if (rows.length > 1) {
                    e.target.closest('.item-row').remove();
                    resetState();
                } else {
                    window.showToast('At least one product is required.', 'info');
                }
            }
        });

        function resetState() {
            submitBtn.disabled = true;
            calculationArea.classList.add('d-none');
        }

        calculateBtn.addEventListener('click', async function () {
            const branchId = document.querySelector('select[name="branch_id"]').value;
            const itemRows = document.querySelectorAll('.item-row');
            const items = [];
            let isValid = true;

            itemRows.forEach(row => {
                const pId = row.querySelector('.product-select').value;
                const qty = row.querySelector('.quantity-input').value;
                if (!pId || !qty || qty <= 0) isValid = false;
                else items.push({ product_id: pId, quantity: parseFloat(qty) });
            });

            if (!branchId || !isValid || items.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Action Needed', text: 'Select branch and enter valid item quantities.', confirmButtonColor: '#6366f1' });
                return;
            }

            // UI State
            calculateBtn.disabled = true;
            loadingSpinner.classList.remove('d-none');
            
            try {
                const response = await fetch("{{ route('productions.calculate') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ branch_id: branchId, items: items })
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Server error');

                renderResults(data);
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Calculation Failed', text: err.message });
            } finally {
                calculateBtn.disabled = false;
                loadingSpinner.classList.add('d-none');
            }
        });

        function renderResults(data) {
            ingredientsList.innerHTML = '';
            let isSufficient = true;
            
            data.ingredients.forEach(ing => {
                if (!ing.sufficient_stock) isSufficient = false;
                const row = `
                    <tr class="${!ing.sufficient_stock ? 'bg-danger bg-opacity-10' : ''}">
                        <td class="ps-4 fw-bold text-dark">${ing.name}</td>
                        <td class="text-center fw-bold">${parseFloat(ing.required_qty).toFixed(2)} <span class="small text-muted">${ing.unit}</span></td>
                        <td class="text-center">${parseFloat(ing.current_stock).toFixed(2)} <span class="small text-muted">${ing.unit}</span></td>
                        <td class="text-center text-primary fw-bold">$${parseFloat(ing.line_cost).toFixed(2)}</td>
                        <td class="text-end pe-4">
                            <span class="badge rounded-pill ${ing.sufficient_stock ? 'badge-soft-success' : 'badge-soft-danger'} px-3 py-2">
                                <i class="fa-solid ${ing.sufficient_stock ? 'fa-circle-check' : 'fa-triangle-exclamation'} me-1"></i>
                                ${ing.sufficient_stock ? 'Available' : 'Shortage'}
                            </span>
                        </td>
                    </tr>`;
                ingredientsList.insertAdjacentHTML('beforeend', row);
            });

            grandTotalSpan.textContent = parseFloat(data.total_cost).toLocaleString(undefined, { minimumFractionDigits: 2 });
            itemsCountSpan.textContent = data.products.length;
            
            // Status Card Update
            const statusCard = document.getElementById('stock-status-card');
            const statusIconBox = document.getElementById('status-icon-box');
            const statusIcon = document.getElementById('status-icon');
            const statusText = document.getElementById('status-text');

            if (isSufficient) {
                statusCard.className = 'card border-0 shadow-sm rounded-4 bg-white border border-success overflow-hidden';
                statusIconBox.className = 'rounded-circle p-3 me-3 bg-success text-white';
                statusIcon.className = 'fa-solid fa-check-circle fs-4';
                statusText.innerHTML = 'All Clear';
                statusText.className = 'fw-bold mb-0 text-success';
                submitBtn.disabled = false;
            } else {
                statusCard.className = 'card border-0 shadow-sm rounded-4 bg-white border border-danger overflow-hidden';
                statusIconBox.className = 'rounded-circle p-3 me-3 bg-danger text-white';
                statusIcon.className = 'fa-solid fa-triangle-exclamation fs-4';
                statusText.innerHTML = 'Shortage';
                statusText.className = 'fw-bold mb-0 text-danger';
                submitBtn.disabled = false; // Note: We might allow submission and handle in back, but usually disabled
                submitBtn.disabled = !isSufficient; 
            }

            calculationArea.classList.remove('d-none');
            window.scrollTo({ top: calculationArea.offsetTop - 100, behavior: 'smooth' });
        }
    });
</script>
@endsection