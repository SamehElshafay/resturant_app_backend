@extends('layouts.app')

@section('title', __('messages.drivers'))

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ __('messages.drivers') }}</h4>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                <i class="fa-solid fa-plus me-2"></i> {{ __('messages.add_driver') }}
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($drivers->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>{{ __('messages.name') }}</th>
                            <th>Email</th>
                            <th>{{ __('messages.phone') ?? 'Phone' }}</th>
                            <th>{{ __('messages.branch') }}</th>
                            <th>{{ __('messages.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drivers as $driver)
                            <tr>
                                <td>#{{ $driver->id }}</td>
                                <td class="fw-semibold">{{ $driver->name }}</td>
                                <td>{{ $driver->email }}</td>
                                <td>{{ $driver->phone ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-info-soft text-info">
                                        {{ $driver->branch->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-light" data-bs-toggle="modal"
                                        data-bs-target="#editDriverModal{{ $driver->id }}">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <form action="{{ route('drivers.destroy', $driver->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger"
                                            onclick="return confirm('Delete driver?')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editDriverModal{{ $driver->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('drivers.update', $driver->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Driver</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" name="name" class="form-control"
                                                        value="{{ $driver->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control"
                                                        value="{{ $driver->email }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Phone</label>
                                                    <input type="text" name="phone" class="form-control"
                                                        value="{{ $driver->phone }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Password (leave empty to keep current)</label>
                                                    <input type="password" name="password" class="form-control"
                                                        placeholder="••••••••">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">{{ __('messages.branch') }}</label>
                                                    <select name="branch_id" class="form-select" required>
                                                        @foreach($branches as $branch)
                                                            <option value="{{ $branch->id }}" {{ $driver->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
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
                <i class="fa-solid fa-truck fa-4x text-secondary opacity-50 mb-3"></i>
                <p class="text-secondary">No drivers found.</p>
            </div>
        @endif
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('drivers.store') }}" method="POST">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="fa-solid fa-truck me-2"></i>{{ app()->getLocale() == 'ar' ? 'إضافة سائق جديد' : 'Add New Driver' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Full Name</label>
                            <input type="text" name="name" class="form-control rounded-3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Email</label>
                            <input type="email" name="email" class="form-control rounded-3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Phone</label>
                            <input type="text" name="phone" class="form-control rounded-3">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Password</label>
                            <input type="password" name="password" class="form-control rounded-3" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">{{ __('messages.branch') }}</label>
                            <select name="branch_id" class="form-select rounded-3" required>
                                <option value="" disabled selected>Select Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-4"
                            data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            Save Driver
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <style>
        .bg-info-soft {
            background: rgba(13, 202, 240, 0.1);
        }
    </style>
@endsection
