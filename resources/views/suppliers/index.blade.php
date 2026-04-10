@extends('layouts.app')

@section('title', __('messages.suppliers'))

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ __('messages.suppliers') }}</h4>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="fa-solid fa-plus me-2"></i> Add Supplier
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($suppliers->count() > 0)
            <div style="min-height: 300px;">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Acc. Code</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suppliers as $supplier)
                            <tr>
                                <td>#{{ $supplier->id }}</td>
                                <td class="fw-semibold">{{ $supplier->name_ar ?? $supplier->name_en }}</td>
                                <td>{{ $supplier->email ?? 'N/A' }}</td>
                                <td>{{ $supplier->phone ?? 'N/A' }}</td>
                                <td>{{ $supplier->address ?? 'N/A' }}</td>
                                <td><code>{{ $supplier->account_code ?? 'N/A' }}</code></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle" type="button" 
                                            data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            @if($supplier->account_id)
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('accounting.statement', $supplier->account_id) }}">
                                                        <i class="fa-solid fa-file-invoice me-2 opacity-50 text-primary"></i> 
                                                        {{ __('messages.account_statement') ?? 'Statement of Account' }}
                                                    </a>
                                                </li>
                                            @endif
                                            <li>
                                                <button class="dropdown-item py-2" data-bs-toggle="modal"
                                                    data-bs-target="#editSupplierModal{{ $supplier->id }}">
                                                    <i class="fa-solid fa-pen me-2 opacity-50"></i> Edit
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider opacity-50"></li>
                                            <li>
                                                <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="dropdown-item py-2 text-danger"
                                                        onclick="return confirm('Delete supplier?')">
                                                        <i class="fa-solid fa-trash me-2 opacity-50"></i> Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editSupplierModal{{ $supplier->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Supplier</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <div class="mb-3">
                                                    <label class="form-label">Name (Arabic)</label>
                                                    <input type="text" name="name_ar" class="form-control"
                                                        value="{{ $supplier->name_ar }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Name (English)</label>
                                                    <input type="text" name="name_en" class="form-control"
                                                        value="{{ $supplier->name_en }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control"
                                                        value="{{ $supplier->email }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Phone</label>
                                                    <input type="text" name="phone" class="form-control"
                                                        value="{{ $supplier->phone }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Address</label>
                                                    <textarea name="address" class="form-control"
                                                        rows="2">{{ $supplier->address }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fa-solid fa-truck-field fa-4x text-secondary opacity-50 mb-3"></i>
                <p class="text-secondary">No suppliers found.</p>
            </div>
        @endif
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Supplier</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Error!</strong>
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Name (Arabic)</label>
                            <input type="text" name="name_ar" class="form-control" placeholder="مثال: شركة التوريدات"
                                value="{{ old('name_ar') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name (English)</label>
                            <input type="text" name="name_en" class="form-control" placeholder="e.g. Supply Company"
                                value="{{ old('name_en') }}">
                        </div>
                        <div class="alert alert-info small">
                            <i class="fa-solid fa-info-circle me-1"></i> At least one name (Arabic or English) is required.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="supplier@example.com"
                                value="{{ old('email') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="+20 123 456 7890"
                                value="{{ old('phone') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"
                                placeholder="Supplier address">{{ old('address') }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Supplier</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modal = new bootstrap.Modal(document.getElementById('addSupplierModal'));
                modal.show();
            });
        </script>
    @endif
@endsection