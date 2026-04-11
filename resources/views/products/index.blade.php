@extends('layouts.app')

@section('title', __('messages.menu_products'))

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ __('messages.menu_products') }}</h4>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fa-solid fa-plus me-2"></i> Add Product
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            @foreach($products as $product)
                <div class="col-md-3">
                    <div class="card border p-3 text-center position-relative h-100">
                        <div class="dropdown position-absolute top-0 end-0 p-2">
                            <button class="btn btn-sm btn-light rounded-circle" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu shadow">
                                <li><a class="dropdown-item" href="javascript:void(0)" data-bs-toggle="modal"
                                        data-bs-target="#editProductModal{{ $product->id }}">Edit</a></li>
                                <li>
                                    <form action="{{ route('products.destroy', $product->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger"
                                            onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </li>
                            </ul>
                        </div>

                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="rounded mb-3 mx-auto object-fit-cover"
                                width="120" height="120">
                        @else
                            <div class="bg-light rounded mb-3 mx-auto d-flex align-items-center justify-content-center"
                                style="width: 120px; height: 120px;">
                                <i class="fa-solid fa-image fa-2x text-secondary"></i>
                            </div>
                        @endif

                        <h6 class="fw-bold">{{ $product->name }}</h6>
                        <p class="text-secondary small">{{ $product->category->name ?? 'No Category' }}</p>
                        <p class="text-primary fw-bold mb-2">Base: ${{ number_format($product->base_purchase_price, 2) }}</p>

                        @if($product->branchPrices->count() > 0)
                            <div class="border-top pt-2 mt-2">
                                <p class="text-muted small mb-1 fw-semibold">Branch Prices:</p>
                                @foreach($product->branchPrices as $branchPrice)
                                    <small class="d-block text-secondary">{{ $branchPrice->branch->name }}:
                                        ${{ number_format($branchPrice->price, 2) }}</small>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Edit Modals outside the loop to prevent event bubbling and stacking issues -->
        @foreach($products as $product)
            <div class="modal fade" id="editProductModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf @method('PUT')
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Product: {{ $product->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $product->name }}"
                                            required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category</label>
                                        <select name="category_id" class="form-select" required>
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Base Purchase Price ($)</label>
                                        <input type="number" step="0.01" name="base_purchase_price" class="form-control"
                                            value="{{ $product->base_purchase_price }}" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">New Image (Optional)</label>
                                        <input type="file" name="image" class="form-control">
                                    </div>
                                </div>

                                <hr>
                                <h6 class="fw-bold mb-3">Branch Prices (Optional)</h6>
                                <div class="row">
                                    @foreach($branches as $branch)
                                        @php
                                            $branchPrice = $product->branchPrices()->where('branch_id', $branch->id)->first();
                                        @endphp
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">{{ $branch->name }} Price ($)</label>
                                            <input type="number" step="0.01" name="branch_prices[{{ $branch->id }}]"
                                                class="form-control" value="{{ $branchPrice ? $branchPrice->price : '' }}"
                                                placeholder="Leave empty to skip">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="addProductForm" action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content text-start border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle me-2"></i>Bulk Add Products</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row mb-4 bg-light p-3 rounded-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Select Category</label>
                                <select name="category_id" class="form-select rounded-3 shadow-sm" required>
                                    <option value="">Choose Category...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Common Image</label>
                                <input type="file" name="image" class="form-control rounded-3 shadow-sm">
                                <small class="text-muted">This image will be used for all names below.</small>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                            <span>Product Variations (Names & Prices)</span>
                            <button type="button" class="btn btn-sm btn-success rounded-pill px-3" onclick="addProductRow()">
                                <i class="fa-solid fa-plus me-1"></i> Add More
                            </button>
                        </h6>

                        <div id="variationRows">
                            <div class="row g-2 mb-2 variation-row">
                                <div class="col-md-7">
                                    <input type="text" name="names[]" class="form-control rounded-3" placeholder="Variation Name (e.g. Pizza L)" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" step="0.01" name="prices[]" class="form-control rounded-3" placeholder="Price" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-outline-danger border-0 h-100 w-100" onclick="removeProductRow(this)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="addProductSubmitBtn" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                            Save All Products
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addProductRow() {
            const container = document.getElementById('variationRows');
            const newRow = document.createElement('div');
            newRow.className = 'row g-2 mb-2 variation-row animate__animated animate__fadeInDown';
            newRow.innerHTML = `
                <div class="col-md-7">
                    <input type="text" name="names[]" class="form-control rounded-3" placeholder="Variation Name" required>
                </div>
                <div class="col-md-4">
                    <input type="number" step="0.01" name="prices[]" class="form-control rounded-3" placeholder="Price" required>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger border-0 h-100 w-100" onclick="removeProductRow(this)">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
        }

        function removeProductRow(btn) {
            const rows = document.querySelectorAll('.variation-row');
            if (rows.length > 1) {
                btn.closest('.variation-row').remove();
            } else {
                window.showToast('At least one variation is required', 'error');
            }
        }

        document.getElementById('addProductForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const form = this;
            const btn = document.getElementById('addProductSubmitBtn');
            const originalHtml = btn.innerHTML;

            // Show loading
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> {{ __('messages.saving') }}`;

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    window.showToast(data.message, 'success');
                    // Hide modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                    modal.hide();
                    // Optional: reload after a short delay
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    const errorMsg = data.message || 'Validation error occurred';
                    window.showToast(errorMsg, 'error');
                }
            } catch (error) {
                console.error('Submission error:', error);
                window.showToast('An error occurred. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        });
    </script>
@endsection