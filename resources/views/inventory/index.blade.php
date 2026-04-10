@extends('layouts.app')

@section('title', 'Inventory Management')

@section('extra_css')
    <style>
        .avatar-md {
            width: 48px;
            height: 48px;
        }

        .btn-light-primary {
            background-color: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            border: none;
        }

        .btn-light-primary:hover {
            background-color: #6366f1;
            color: white;
        }

        .modal-content {
            border-radius: 20px;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(99, 102, 241, 0.02);
        }
    </style>
@endsection

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ __('messages.inventory_management') }}</h4>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($products->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">{{ __('messages.product') }}</th>
                            <th>{{ __('messages.category') }}</th>
                            <th>{{ __('messages.base_price') }}</th>
                            <th class="text-center">{{ __('messages.total_stock') }}</th>
                            <th class="text-center">{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            @php
                                $branchStock = $product->branchPrices->sum('stock_quantity');
                                $centralStock = $product->stock_quantity ?? 0;
                            @endphp
                            <tr style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#detailsModal{{ $product->id }}">
                                <td class="ps-4">
                                    <span class="fw-bold">{{ $product->name_ar ?? $product->name_en ?? $product->name }}</span>
                                </td>
                                <td>{{ $product->category->name_ar ?? $product->category->name_en ?? ($product->category->name ?? 'N/A') }}
                                </td>
                                <td class="fw-bold text-primary">${{ number_format($product->base_purchase_price, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $centralStock > 10 ? 'bg-success' : ($centralStock > 0 ? 'bg-warning' : 'bg-danger') }} px-3 py-2 rounded-pill fs-6">
                                        {{ number_format($centralStock, 0) }}
                                    </span>
                                    <div class="small text-muted mt-1">Total (Global): {{ number_format($centralStock + $branchStock, 0) }}</div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light-primary rounded-3">
                                        <i class="fa-solid fa-eye me-1"></i> {{ __('messages.view_details') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Product Details Modals -->
            @foreach($products as $product)
                <div class="modal fade" id="detailsModal{{ $product->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header bg-primary text-white border-0 py-3">
                                <h5 class="modal-title fw-bold">
                                    <i class="fa-solid fa-boxes-stacked me-2"></i>{{ __('messages.stock_details') }}
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3">
                                    <div class="avatar-md bg-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm"
                                        style="width: 50px; height: 50px;">
                                        <i class="fa-solid fa-box text-primary fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 fw-bold text-dark">
                                            {{ $product->name_ar ?? $product->name_en ?? $product->name }}</h5>
                                        <small
                                            class="text-muted">{{ $product->category->name_ar ?? $product->category->name_en ?? ($product->category->name ?? 'N/A') }}</small>
                                    </div>
                                </div>

                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">{{ __('messages.branches') }}</th>
                                                <th class="text-center">{{ __('messages.stock_quantity') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Central Warehouse Stock first -->
                                            <tr class="table-primary border-bottom border-primary-subtle">
                                                <td class="ps-4 fw-bold text-primary">
                                                    <i class="fa-solid fa-warehouse me-2"></i>Central Warehouse (المخزن الرئيسي)
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary px-3 py-2 rounded-pill">
                                                        {{ number_format($product->stock_quantity ?? 0, 0) }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @foreach($branches as $branch)
                                                @php
                                                    $branchPrice = $product->branchPrices->where('branch_id', $branch->id)->first();
                                                    $stock = $branchPrice->stock_quantity ?? 0;
                                                @endphp
                                                <tr>
                                                    <td class="ps-4 fw-semibold text-dark">
                                                        {{ $branch->name_ar ?? $branch->name_en ?? $branch->name }}
                                                    </td>
                                                    <td class="text-center">
                                                        <span
                                                            class="badge {{ $stock > 10 ? 'bg-success' : ($stock > 0 ? 'bg-warning' : 'bg-danger') }} px-3 py-2 rounded-pill">
                                                            {{ $stock }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Single Edit Modal for all -->
            <div class="modal fade" id="updateStockModal" tabindex="-1" style="z-index: 1060;">
                <div class="modal-dialog">
                    <form id="updateStockForm" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold">{{ __('messages.update_stock') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-1"><small class="text-muted">{{ __('messages.product') }}:</small> <strong
                                        id="editProdName"></strong></p>
                                <p class="mb-3"><small class="text-muted">{{ __('messages.branches') }}:</small> <strong
                                        id="editBranchName"></strong></p>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">{{ __('messages.stock_quantity') }}</label>
                                    <input type="number" name="quantity" id="editQuantity" class="form-control rounded-3"
                                        required>
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light rounded-3"
                                    data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                                <button type="submit"
                                    class="btn btn-primary rounded-3 px-4">{{ __('messages.update_stock') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                function showUpdateStock(pId, bId, stock, pName, bName) {
                    document.getElementById('editProdName').textContent = pName;
                    document.getElementById('editBranchName').textContent = bName;
                    document.getElementById('editQuantity').value = stock;
                    document.getElementById('updateStockForm').action = `/inventory/${pId}/${bId}`;

                    const editModal = new bootstrap.Modal(document.getElementById('updateStockModal'));
                    editModal.show();
                }
            </script>
        @else
            <div class="text-center py-5">
                <i class="fa-solid fa-boxes-stacked fa-4x text-secondary opacity-50 mb-3"></i>
                <p class="text-secondary">No products found in inventory.</p>
            </div>
        @endif
    </div>
@endsection