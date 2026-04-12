@extends('layouts.app')

@section('title', __('messages.menu_products'))

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-1">{{ __('messages.menu_products') }}</h2>
            <p class="text-secondary mb-0">Manage your menu items and prices across all branches</p>
        </div>
        <div class="d-flex gap-2">
            <div class="form-check d-flex align-items-center bg-white shadow-sm px-3 py-2 rounded-pill border">
                <input class="form-check-input me-2 ms-0" type="checkbox" id="selectAllProducts">
                <label class="form-check-label fw-semibold text-secondary small" for="selectAllProducts" style="cursor: pointer;">
                    Select All
                </label>
            </div>
            <button class="btn btn-primary btn-lg rounded-pill shadow-sm px-4 d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fa-solid fa-plus-circle fs-5 me-2"></i>
                <span>Add New Product</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible border-0 shadow-sm fade show mb-4 rounded-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-check-circle fs-5 me-3"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Products Grid -->
    <div class="row g-4">
        @foreach($products as $product)
            <div class="col-xl-3 col-lg-4 col-md-6 product-item">
                <div class="card product-card shadow-sm h-100 border-0 rounded-4 overflow-hidden bg-white position-relative">
                    <!-- Checkbox Overlay -->
                    <div class="selection-overlay">
                        <input class="form-check-input product-checkbox" type="checkbox" value="{{ $product->id }}" 
                               data-name="{{ $product->name }}" 
                               data-prices="{{ json_encode($product->branchPrices->pluck('price', 'branch_id')) }}">
                    </div>

                    <!-- Action Menu -->
                    <div class="position-absolute top-0 end-0 p-3" style="z-index: 5;">
                        <div class="dropdown">
                            <button class="btn btn-white btn-sm rounded-circle shadow-sm action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical text-dark"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 rounded-3">
                                <li>
                                    <button class="dropdown-item rounded-2 py-2" data-bs-toggle="modal" data-bs-target="#editProductModal{{ $product->id }}">
                                        <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Details
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button type="button" class="dropdown-item rounded-2 py-2 text-danger" onclick="confirmDelete('delete-form-{{ $product->id }}', '{{ $product->name }}')">
                                        <i class="fa-solid fa-trash-can me-2"></i> Delete Product
                                    </button>
                                    <form id="delete-form-{{ $product->id }}" action="{{ route('products.destroy', $product->id) }}" method="POST" class="d-none">
                                        @csrf @method('DELETE')
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="image-wrapper bg-light" style="height: 180px;">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="w-100 h-100 object-fit-cover" alt="{{ $product->name }}">
                        @else
                            <div class="w-100 h-100 d-flex align-items-center justify-content-center">
                                <i class="fa-solid fa-pizza-slice fa-3x text-secondary opacity-25"></i>
                            </div>
                        @endif
                    </div>
                    
                    <div class="card-body p-4 text-center">
                        <h6 class="fw-bold text-dark mb-1">{{ $product->name }}</h6>
                        <p class="text-secondary small mb-3">{{ $product->category->name ?? 'No Category' }}</p>
                        
                        <div class="price-container mb-3">
                            <span class="text-primary fw-bold fs-5">Base: ${{ number_format($product->base_purchase_price, 2) }}</span>
                        </div>

                        @if($product->branchPrices->count() > 0)
                            <div class="border-top pt-3">
                                <p class="text-muted small fw-bold mb-2">Branch Prices</p>
                                <div class="d-flex flex-wrap justify-content-center gap-1">
                                    @foreach($product->branchPrices as $bp)
                                        <span class="badge bg-light text-secondary border rounded-pill py-1 px-2" style="font-size: 0.75rem;">
                                            {{ $bp->branch->name }}: <span class="text-dark fw-bold">${{ number_format($bp->price, 2) }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Edit Single Modal -->
            <div class="modal fade" id="editProductModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header border-0 p-4">
                                <h5 class="fw-bold mb-0">Edit Product: {{ $product->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4 pt-0">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" name="name" class="form-control rounded-3" value="{{ $product->name }}" required placeholder="Name">
                                            <label>Product Name</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select name="category_id" class="form-select rounded-3" required>
                                                @foreach($categories as $cat)
                                                    <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                            <label>Category</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" step="0.01" name="base_purchase_price" class="form-control rounded-3" value="{{ $product->base_purchase_price }}" required placeholder="Price">
                                            <label>Base Price ($)</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-secondary">Update Image</label>
                                        <input type="file" name="image" class="form-control rounded-3">
                                    </div>
                                </div>

                                <hr class="my-4 opacity-10">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-code-branch me-2 text-primary"></i> Branch-Specific Pricing</h6>
                                <div class="row g-3">
                                    @foreach($branches as $branch)
                                        @php $bp = $product->branchPrices->where('branch_id', $branch->id)->first(); @endphp
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="number" step="0.01" name="branch_prices[{{ $branch->id }}]" class="form-control rounded-3" value="{{ $bp ? $bp->price : '' }}" placeholder="Price">
                                                <label>{{ $branch->name }} ($)</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer border-0 p-4 pt-0">
                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    @if($products->count() == 0)
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border mt-4">
            <div class="py-5">
                <i class="fa-solid fa-pizza-slice fa-4x text-light-emphasis mb-3"></i>
                <h4 class="text-secondary">No products added yet</h4>
                <p class="text-secondary mb-4">You haven't added any products to your menu yet.</p>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    Add Your First Product
                </button>
            </div>
        </div>
    @endif
</div>

<!-- Bulk Actions Floating Bar -->
<div id="bulkActionBar" class="bulk-bar bg-dark text-white shadow-lg d-none">
    <div class="container d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center">
            <div class="bg-primary p-2 rounded-circle me-3">
                <i class="fa-solid fa-check text-white"></i>
            </div>
            <div>
                <span id="selectedCount" class="fw-bold fs-5">0</span>
                <span class="ms-1 opacity-75">Products Selected</span>
            </div>
        </div>
        <div class="d-flex gap-3">
            <button class="btn btn-outline-light rounded-pill px-4" id="clearSelection">Clear Selection</button>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow" id="openBulkEditModal">
                <i class="fa-solid fa-tags me-2"></i> Bulk Edit Prices
            </button>
        </div>
    </div>
</div>

<!-- Bulk Edit Modal -->
<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-dark text-white p-4">
                <h5 class="fw-bold mb-0">Bulk Update Product Prices</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-4" style="width: 250px;">Product</th>
                                @foreach($branches as $branch)
                                    <th class="text-center">{{ $branch->name }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody id="bulkEditTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 bg-light">
                <button type="button" class="btn btn-white border rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveBulkUpdates" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                    <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                    Update All Prices
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="addProductForm" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 p-4">
                    <h5 class="fw-bold mb-0">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Classic Burger" required>
                                <label>Product Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select name="category_id" class="form-select rounded-3" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                <label>Category</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="number" step="0.01" name="base_purchase_price" class="form-control rounded-3" placeholder="0.00" required>
                                <label>Base Price ($)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Product Image</label>
                            <input type="file" name="image" class="form-control rounded-3">
                        </div>
                    </div>

                    <hr class="my-4 opacity-10">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-code-branch me-2 text-primary"></i> Set Initial Branch Prices</h6>
                    <div class="row g-3">
                        @foreach($branches as $branch)
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="number" step="0.01" name="branch_prices[{{ $branch->id }}]" class="form-control rounded-3" placeholder="0.00">
                                    <label>{{ $branch->name }} ($)</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="addProductSubmitBtn" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                        Create Product
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    body { background-color: #f8f9fa; }
    .product-card { transition: all 0.3s ease; position: relative; }
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important; }
    
    .selection-overlay {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .product-card:hover .selection-overlay, .product-card.selected .selection-overlay {
        opacity: 1;
    }
    
    .product-checkbox {
        width: 24px !important;
        height: 24px !important;
        cursor: pointer;
        border-radius: 6px !important;
        border: 2px solid #6366f1 !important;
    }
    
    .product-card.selected {
        border: 2px solid #6366f1 !important;
        background: #f5f6ff !important;
    }
    
    .action-btn { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    
    .bulk-bar {
        position: fixed;
        bottom: 0;
        left: 260px; /* Sidebar width */
        right: 0;
        z-index: 1000;
        border-radius: 20px 20px 0 0;
        animation: slideUp 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    
    [dir="rtl"] .bulk-bar {
        left: 0;
        right: 260px;
    }
    
    @keyframes slideUp {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
    
    .product-input {
        width: 100px;
        text-align: center;
        margin: 0 auto;
    }
    
    .sticky-top { top: 0; z-index: 1020; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        const selectAllCheckbox = document.getElementById('selectAllProducts');
        const bulkActionBar = document.getElementById('bulkActionBar');
        const selectedCountSpan = document.getElementById('selectedCount');
        const clearSelectionBtn = document.getElementById('clearSelection');
        const openBulkEditBtn = document.getElementById('openBulkEditModal');
        const bulkEditBody = document.getElementById('bulkEditTableBody');
        const branches = {!! json_encode($branches) !!};

        function updateUI() {
            const selected = document.querySelectorAll('.product-checkbox:checked');
            selectedCountSpan.innerText = selected.length;
            
            if (selected.length > 0) {
                bulkActionBar.classList.remove('d-none');
            } else {
                bulkActionBar.classList.add('d-none');
                selectAllCheckbox.checked = false;
            }
            
            // Highlight cards
            productCheckboxes.forEach(cb => {
                const card = cb.closest('.product-card');
                if (cb.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }
            });
        }

        productCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateUI);
        });

        selectAllCheckbox.addEventListener('change', function() {
            productCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateUI();
        });

        clearSelectionBtn.addEventListener('click', function() {
            productCheckboxes.forEach(cb => cb.checked = false);
            updateUI();
        });

        openBulkEditBtn.addEventListener('click', function() {
            const selected = document.querySelectorAll('.product-checkbox:checked');
            bulkEditBody.innerHTML = '';
            
            selected.forEach(cb => {
                const productId = cb.value;
                const productName = cb.getAttribute('data-name');
                const existingPrices = JSON.parse(cb.getAttribute('data-prices') || '{}');
                
                let rowHtml = `
                    <tr data-product-id="${productId}">
                        <td class="ps-4">
                            <div class="fw-bold text-dark">${productName}</div>
                            <div class="text-secondary small">ID: #${productId}</div>
                        </td>`;
                
                branches.forEach(branch => {
                    const price = existingPrices[branch.id] || '';
                    rowHtml += `
                        <td class="text-center">
                            <div class="input-group input-group-sm product-input">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control price-input" 
                                       data-branch-id="${branch.id}" 
                                       value="${price}" placeholder="0.00">
                            </div>
                        </td>`;
                });
                
                rowHtml += `</tr>`;
                bulkEditBody.insertAdjacentHTML('beforeend', rowHtml);
            });
            
            new bootstrap.Modal(document.getElementById('bulkEditModal')).show();
        });

        document.getElementById('saveBulkUpdates').addEventListener('click', async function() {
            const btn = this;
            const spinner = btn.querySelector('.spinner-border');
            const updates = {};
            
            document.querySelectorAll('#bulkEditTableBody tr').forEach(row => {
                const productId = row.getAttribute('data-product-id');
                updates[productId] = {};
                row.querySelectorAll('.price-input').forEach(input => {
                    const branchId = input.getAttribute('data-branch-id');
                    updates[productId][branchId] = input.value;
                });
            });

            btn.disabled = true;
            spinner.classList.remove('d-none');

            try {
                const response = await fetch('{{ route('products.bulk-update-prices') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ updates })
                });

                const data = await response.json();
                if (response.ok) {
                    window.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    window.showToast(data.message || 'Error updating prices', 'error');
                }
            } catch (e) {
                window.showToast('Unexpect error occurred', 'error');
            } finally {
                btn.disabled = false;
                spinner.classList.add('d-none');
            }
        });

        // Single add form handling
        document.getElementById('addProductForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('addProductSubmitBtn');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Saving...`;

            try {
                const formData = new FormData(this);
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (response.ok) {
                    window.showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    window.showToast(data.message || 'Validation error', 'error');
                }
            } catch (error) {
                window.showToast('Submission error', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    });
</script>
@endsection