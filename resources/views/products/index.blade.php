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
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Add New Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Product Name</label>
                                <input type="text" name="name" class="form-control rounded-3"
                                    placeholder="e.g. Classic Burger" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Category</label>
                                <select name="category_id" class="form-select rounded-3" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Base Purchase Price ($)</label>
                                <input type="number" step="0.01" name="base_purchase_price" class="form-control rounded-3"
                                    placeholder="0.00" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Product Image</label>
                                <input type="file" name="image" class="form-control rounded-3">
                            </div>
                        </div>

                        <hr>
                        <h6 class="fw-bold mb-3">Branch Prices (Optional)</h6>
                        <p class="text-muted small">Set different selling prices for each branch. Leave empty to use base
                            price.</p>
                        <div class="row">
                            @foreach($branches as $branch)
                                <div class="col-md-6 mb-3">
                                    <label class="form-label small">{{ $branch->name }} Price ($)</label>
                                    <input type="number" step="0.01" name="branch_prices[{{ $branch->id }}]"
                                        class="form-control rounded-3" placeholder="Leave empty to skip">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-3 px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="addProductSubmitBtn" class="btn btn-primary rounded-pill px-5 fw-bold">
                            {{ __('messages.add_product_btn') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
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