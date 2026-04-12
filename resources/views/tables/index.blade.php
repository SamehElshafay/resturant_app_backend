@extends('layouts.app')

@section('title', __('messages.tables_zones'))

@section('content')
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(!$selectedBranch)
            {{-- Search and Branch Selection --}}
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="row align-items-center g-3">
                    <div class="col-md-6">
                        <h4 class="fw-bold mb-0">Select a Branch</h4>
                        <p class="text-muted small mb-0">Choose a branch to view and manage its tables and zones.</p>
                    </div>
                    <div class="col-md-6">
                        <form action="{{ route('tables.index') }}" method="GET" class="input-group">
                            <input type="text" name="search" class="form-control rounded-start-pill ps-4"
                                placeholder="Search by branch name..." value="{{ $search }}">
                            <button class="btn btn-primary rounded-end-pill px-4" type="submit">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @forelse($branches as $branch)
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm transition-up branch-card">
                            <div class="card-body p-4 text-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                    style="width: 60px; height: 60px;">
                                    <i class="fa-solid fa-store fa-2x"></i>
                                </div>
                                <h5 class="fw-bold mb-2">{{ $branch->name }}</h5>
                                <p class="text-muted small mb-4">
                                    <i class="fa-solid fa-location-dot me-1"></i>{{ $branch->address ?? 'No Address' }}
                                </p>
                                <a href="{{ route('tables.index', ['branch_id' => $branch->id]) }}"
                                    class="btn btn-primary w-100 rounded-pill">
                                    Manage Tables <i class="fa-solid fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="fa-solid fa-store fa-4x text-light mb-3"></i>
                        <h5 class="text-muted">No branches found matching your search.</h5>
                    </div>
                @endforelse
            </div>
        @else
            {{-- Branch Header with Back Button --}}
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center">
                        <a href="{{ route('tables.index') }}" class="btn btn-outline-secondary rounded-circle me-3"
                            style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-arrow-left"></i>
                        </a>
                        <div>
                            <h4 class="fw-bold mb-0">{{ $selectedBranch->name }} - Tables & Zones</h4>
                            <p class="text-muted small mb-0">Managing zones and table performance statistics.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal"
                            data-bs-target="#addZoneModal">
                            <i class="fa-solid fa-plus me-2"></i>New Zone
                        </button>
                    </div>
                </div>

                <hr class="my-4 opacity-25">

                {{-- Date Range Filter --}}
                <form action="{{ route('tables.index') }}" method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">From Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">To Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-dark w-100 rounded-3">
                            <i class="fa-solid fa-calendar-check me-2"></i>Update Statistics
                        </button>
                    </div>
                </form>
            </div>

            {{-- Zones and Tables --}}
            @forelse($zones as $zone)
                <div class="card border-0 shadow-sm mb-4 overflow-hidden">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <div class="d-flex align-items-center">
                            <div class="bg-dark text-white rounded-3 p-2 me-3">
                                <i class="fa-solid fa-layer-group"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">{{ $zone->name }}</h5>
                                <small class="text-muted">{{ $zone->tables->count() }} Tables</small>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-light btn-sm rounded-circle px-2" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                        data-bs-target="#addTableModal{{ $zone->id }}"><i class="fa-solid fa-plus me-2"></i>Add
                                        Table</a></li>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal"
                                        data-bs-target="#editZoneModal{{ $zone->id }}"><i class="fa-solid fa-pen me-2"></i>Edit
                                        Zone</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form id="delete-zone-{{ $zone->id }}" action="{{ route('zones.destroy', $zone->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="button" class="dropdown-item text-danger"
                                            onclick="confirmDelete('delete-zone-{{ $zone->id }}', '{{ $zone->name }}')">
                                            <i class="fa-solid fa-trash me-2"></i>Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            @forelse($zone->tables as $table)
                                @php
                                    $stats = $tableStats[$table->id] ?? null;
                                @endphp
                                <div class="col-xl-3 col-lg-4 col-md-6">
                                    <div class="card border rounded-4 h-100 table-card transition">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <span
                                                        class="badge bg-{{ $table->status == 'available' ? 'success' : ($table->status == 'busy' ? 'danger' : 'warning') }} mb-2">
                                                        {{ ucfirst($table->status) }}
                                                    </span>
                                                    <h5 class="fw-bold mb-0">Table {{ $table->number }}</h5>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-light p-1" data-bs-toggle="modal"
                                                        data-bs-target="#editTableModal{{ $table->id }}">
                                                        <i class="fa-solid fa-pen-to-square"></i>
                                                    </button>
                                                    <form id="delete-table-{{ $table->id }}" action="{{ route('tables.destroy', $table->id) }}" method="POST"
                                                        class="d-inline">
                                                        @csrf @method('DELETE')
                                                        <button type="button" class="btn btn-sm btn-light p-1 text-danger"
                                                            onclick="confirmDelete('delete-table-{{ $table->id }}', 'Table {{ $table->number }}')">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            {{-- Stats Section --}}
                                            <div class="p-3 bg-light rounded-3">
                                                <div class="row text-center g-2">
                                                    <div class="col-4 border-end">
                                                        <small class="text-muted d-block small">Orders</small>
                                                        <span class="fw-bold">{{ $stats['total_orders'] ?? 0 }}</span>
                                                    </div>
                                                    <div class="col-8">
                                                        <small class="text-muted d-block small">Revenue</small>
                                                        <span
                                                            class="fw-bold text-primary">${{ number_format($stats['total_revenue'] ?? 0, 2) }}</span>
                                                    </div>
                                                </div>
                                                @if($stats && $stats['avg_order_value'] > 0)
                                                    <div class="mt-2 text-center border-top pt-2">
                                                        <small class="text-muted small">Avg. Order: </small>
                                                        <span
                                                            class="fw-bold small">${{ number_format($stats['avg_order_value'], 2) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Edit Table Modal --}}
                                <div class="modal fade" id="editTableModal{{ $table->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="{{ route('tables.update', $table->id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Table {{ $table->number }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Table Number</label>
                                                        <input type="text" name="number" class="form-control"
                                                            value="{{ $table->number }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="available" {{ $table->status == 'available' ? 'selected' : '' }}>Available</option>
                                                            <option value="busy" {{ $table->status == 'busy' ? 'selected' : '' }}>Busy
                                                            </option>
                                                            <option value="reserved" {{ $table->status == 'reserved' ? 'selected' : '' }}>
                                                                Reserved</option>
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
                            @empty
                                <div class="col-12 text-center py-4">
                                    <p class="text-muted italic mb-0">No tables found in this zone.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Edit Zone Modal --}}
                <div class="modal fade" id="editZoneModal{{ $zone->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('zones.update', $zone->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Zone</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Zone Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $zone->name }}" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Update Zone</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Add Table Modal --}}
                <div class="modal fade" id="addTableModal{{ $zone->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <form action="{{ route('tables.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="zone_id" value="{{ $zone->id }}">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Add Table to {{ $zone->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label class="form-label">Table Number</label>
                                        <input type="text" name="number" class="form-control" placeholder="e.g. 10" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Initial Status</label>
                                        <select name="status" class="form-select" required>
                                            <option value="available" selected>Available</option>
                                            <option value="busy">Busy</option>
                                            <option value="reserved">Reserved</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Create Table</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-5">
                    <i class="fa-solid fa-layer-group fa-4x text-light mb-3"></i>
                    <h4 class="text-muted">No zones created for this branch yet.</h4>
                    <p class="text-muted">Start by adding a zone to organize your tables.</p>
                    <button class="btn btn-primary rounded-pill mt-2 px-4" data-bs-toggle="modal" data-bs-target="#addZoneModal">
                        <i class="fa-solid fa-plus me-2"></i>Add First Zone
                    </button>
                </div>
            @endforelse
        @endif
    </div>

    {{-- Universal Add Zone Modal --}}
    @if($selectedBranch)
        <div class="modal fade" id="addZoneModal" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('zones.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Create New Zone</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Zone Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. VIP Lounge, Garden"
                                    required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Zone</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <style>
        .transition-up {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .transition-up:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
        }

        .table-card {
            transition: all 0.2s ease;
        }

        .table-card:hover {
            border-color: #0d6efd !important;
            background-color: #f8f9ff;
        }

        .branch-card {
            cursor: default;
        }
    </style>

    <script>
        function confirmDelete(formId, itemName) {
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete "${itemName}". This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6366f1',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                padding: '2rem',
                customClass: {
                    popup: 'rounded-4 border-0 shadow-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
    </script>
@endsection