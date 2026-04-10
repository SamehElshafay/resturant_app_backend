@extends('layouts.app')

@section('title', 'POS Terminals - ' . $branch->name)

@section('content')
    <style>
        .clickable-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .clickable-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
    </style>
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('branches.index') }}">Branches</a></li>
                <li class="breadcrumb-item active">{{ $branch->name }}</li>
                <li class="breadcrumb-item active" aria-current="page">POS Terminals</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-0 text-primary">POS Terminals</h3>
                <p class="text-muted">Manage Point of Sale devices for <strong>{{ $branch->name }}</strong></p>
            </div>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addPosModal">
                <i class="fa-solid fa-plus me-2"></i> Register New POS
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm p-4 mb-4">
        <form action="{{ route('branches.pos', $branch->id) }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">From Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">To Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1 rounded-pill">
                        <i class="fa-solid fa-filter me-2"></i> Filter Stats
                    </button>
                    <a href="{{ route('branches.pos', $branch->id) }}" class="btn btn-light rounded-pill">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if($posDevices->count() > 0)
        <div class="row g-4">
            @foreach($posDevices as $pos)
                <div class="col-xl-4 col-md-6">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden clickable-card" 
                         onclick="window.location.href='{{ $pos->account_id ? route('accounting.statement', $pos->account_id) : '#' }}'">
                        <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-start">
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary-soft text-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                    style="width: 45px; height: 45px; background-color: rgba(13, 110, 253, 0.1);">
                                    <i class="fa-solid fa-desktop fs-4"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold">{{ $pos->name }}</h5>
                                    <code class="small text-primary">{{ $pos->account_code }}</code>
                                </div>
                            </div>
                            <span
                                class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 px-3 py-2 rounded-pill">
                                Online
                            </span>
                        </div>
                        <div class="card-body pt-4">
                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-3">
                                        <small class="text-muted d-block mb-1">Total Orders</small>
                                        <h4 class="mb-0 fw-bold">{{ $pos->stats['order_count'] ?? 0 }}</h4>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 bg-light rounded-3">
                                        <small class="text-muted d-block mb-1">Revenue</small>
                                        <h4 class="mb-0 fw-bold">${{ number_format($pos->stats['total_amount'] ?? 0, 2) }}</h4>
                                    </div>
                                </div>
                            </div>

                            <div class="list-group list-group-flush small">
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
                                    <span class="text-muted"><i class="fa-solid fa-user-clock me-2"></i> Active Cashier</span>
                                    <span class="fw-semibold">{{ $pos->stats['cashier_name'] ?? 'N/A' }}</span>
                                </div>
                                <div
                                    class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent border-0">
                                    <span class="text-muted"><i class="fa-solid fa-wallet me-2"></i> Cash in Hand</span>
                                    <span
                                        class="fw-bold text-success">${{ number_format($pos->stats['cash_in_hand'] ?? 0, 2) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light border-0 py-3 d-flex justify-content-around" onclick="event.stopPropagation()">
                            @if($pos->account_id)
                                <a href="{{ route('accounting.statement', $pos->account_id) }}"
                                    class="btn btn-sm btn-link text-decoration-none text-muted">
                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> Statement
                                </a>
                            @else
                                <button class="btn btn-sm btn-link text-decoration-none text-muted" disabled title="Account not linked">
                                    <i class="fa-solid fa-file-invoice-dollar me-1"></i> No Account
                                </button>
                            @endif
                            <button class="btn btn-sm btn-link text-decoration-none text-muted">
                                <i class="fa-solid fa-gear me-1"></i> Config
                            </button>
                            <button class="btn btn-sm btn-link text-decoration-none text-danger">
                                <i class="fa-solid fa-power-off me-1"></i> Logout
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card border-0 shadow-sm py-5 text-center">
            <div class="card-body">
                <i class="fa-solid fa-desktop fa-4x text-muted opacity-25 mb-4"></i>
                <h4>No POS Terminals Configured</h4>
                <p class="text-muted">Register your first POS device to start accepting orders in this branch.</p>
                <button class="btn btn-primary rounded-pill px-4 mt-2" data-bs-toggle="modal" data-bs-target="#addPosModal">
                    Register Terminal
                </button>
            </div>
        </div>
    @endif

    <!-- Add POS Modal -->
    <div class="modal fade" id="addPosModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="addPosForm" onsubmit="submitPos(event)">
                @csrf
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Register New POS</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Terminal Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Counter 01, Lobby POS"
                                required>
                        </div>
                        <div class="alert alert-info border-0 small mb-0">
                            <i class="fa-solid fa-circle-info me-2"></i> An accounting code will be automatically generated
                            inheriting from this branch (<strong>{{ $branch->account_code }}</strong>).
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="addPosSubmitBtn" class="btn btn-primary rounded-pill px-4">
                            <span id="posBtnText">Register POS</span>
                            <span id="posBtnSpinner" class="spinner-border spinner-border-sm d-none"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        async function submitPos(event) {
            event.preventDefault();
            const btn = document.getElementById('addPosSubmitBtn');
            const btnText = document.getElementById('posBtnText');
            const btnSpinner = document.getElementById('posBtnSpinner');
            btn.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');

            const formData = new FormData(event.target);
            try {
                const response = await axios.post('{{ route("branches.pos.store", $branch->id) }}', Object.fromEntries(formData.entries()));
                if (response.data.success) {
                    if (window.showToast) {
                        window.showToast(response.data.message, 'success');
                    }
                    setTimeout(() => window.location.reload(), 1500);
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