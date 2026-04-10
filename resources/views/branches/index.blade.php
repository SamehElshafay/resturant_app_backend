@extends('layouts.app')

@section('title', __('messages.branches'))

@section('content')
    <style>
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .clickable-row:hover {
            background-color: rgba(0, 123, 255, 0.05) !important;
        }
        .dropdown-toggle::after {
            display: none;
        }
        .action-btn {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
            cursor: pointer;
            border: none;
            background: transparent;
        }
        .action-btn:hover {
            background-color: #f8f9fa;
        }
        .bg-soft-success {
            background-color: rgba(40, 167, 69, 0.1);
        }
    </style>

    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ __('messages.branches') }}</h4>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                <i class="fa-solid fa-plus me-2"></i> Add New Branch
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($branches->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Acc. Code</th>
                            <th>Status</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branches as $branch)
                            <tr class="clickable-row" onclick="handleRowClick(event, '{{ route('branches.show', $branch->id) }}')">
                                <td>#{{ $branch->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="fa-solid fa-building text-primary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $branch->name }}</div>
                                            <small class="text-muted">{{ $branch->name_ar }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $branch->address ?? 'N/A' }}</td>
                                <td>{{ $branch->phone ?? 'N/A' }}</td>
                                <td><code>{{ $branch->account_code ?? 'N/A' }}</code></td>
                                <td><span class="badge bg-soft-success text-success border border-success border-opacity-10">Active</span></td>
                                <td class="text-end px-4">
                                    <div class="dropdown" onclick="event.stopPropagation()">
                                        <button class="action-btn text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('branches.show', $branch->id) }}">
                                                    <i class="fa-solid fa-eye me-2 text-primary"></i> View Details
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('tables.index', ['branch_id' => $branch->id]) }}">
                                                    <i class="fa-solid fa-table-cells me-2 text-success"></i> Branch Tables
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2" href="{{ route('branches.pos', $branch->id) }}">
                                                    <i class="fa-solid fa-desktop me-2 text-info"></i> POS Terminals
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item py-2" data-bs-toggle="modal" data-bs-target="#editBranchModal{{ $branch->id }}">
                                                    <i class="fa-solid fa-edit me-2 text-warning"></i> Edit Branch
                                                </button>
                                            </li>
                                            <li>
                                                <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" id="delete-branch-{{ $branch->id }}">
                                                    @csrf @method('DELETE')
                                                    <button type="button" class="dropdown-item py-2 text-danger" onclick="confirmDelete({{ $branch->id }})">
                                                        <i class="fa-solid fa-trash me-2"></i> Delete Branch
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editBranchModal{{ $branch->id }}" tabindex="-1" onclick="event.stopPropagation()">
                                <div class="modal-dialog">
                                    <form action="{{ route('branches.update', $branch->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold">Edit Branch</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Branch Name (Arabic)</label>
                                                    <input type="text" name="name_ar" class="form-control" value="{{ $branch->name_ar }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Branch Name (English)</label>
                                                    <input type="text" name="name_en" class="form-control" value="{{ $branch->name_en }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Display Name (Internal)</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $branch->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Address</label>
                                                    <input type="text" name="address" class="form-control" value="{{ $branch->address }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Phone</label>
                                                    <input type="text" name="phone" class="form-control" value="{{ $branch->phone }}">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
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
                <i class="fa-solid fa-code-branch fa-4x text-secondary opacity-50 mb-3"></i>
                <p class="text-secondary">No branches found. Click "Add New Branch" to create one.</p>
            </div>
        @endif
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addBranchModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="addBranchForm" onsubmit="submitBranch(event)">
                @csrf
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Add New Branch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Branch Name (Arabic)</label>
                            <input type="text" name="name_ar" class="form-control" placeholder="مثال: فرع القاهرة">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Branch Name (English)</label>
                            <input type="text" name="name_en" class="form-control" placeholder="e.g. Cairo Branch">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Display Name (Internal)</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Main Branch" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="123 Street, City">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="+20 123 456 7890">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="addBranchSubmitBtn" class="btn btn-primary rounded-pill px-4">
                            <span id="btnText">Add Branch</span>
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function handleRowClick(event, url) {
            // Check if the click was on the dropdown button or its descendants
            if (!event.target.closest('.dropdown') && !event.target.closest('.modal')) {
                window.location.href = url;
            }
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this branch? All associated data will be removed.')) {
                document.getElementById('delete-branch-' + id).submit();
            }
        }

        async function submitBranch(event) {
            event.preventDefault();
            const btn = document.getElementById('addBranchSubmitBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            btn.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');
            
            const formData = new FormData(event.target);
            try {
                const response = await axios.post('{{ route("branches.store") }}', Object.fromEntries(formData.entries()));
                if (response.data.success) {
                    if (window.showToast) {
                        window.showToast(response.data.message, 'success');
                    }
                    setTimeout(() => window.location.reload(), 1000);
                }
            } catch (error) {
                const msg = error.response ? (error.response.data.message || 'Error') : 'Network error';
                if (window.showToast) {
                    window.showToast(msg, 'error');
                } else {
                    alert(msg);
                }
            } finally {
                btn.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');
            }
        }
    </script>
@endsection