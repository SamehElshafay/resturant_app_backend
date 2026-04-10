@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card p-4 border-0 shadow-sm rounded-4">
                <form action="{{ route('accounting.chart') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Search Account</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="fa-solid fa-search text-primary"></i></span>
                            <input type="text" name="search" class="form-control border-0 rounded-end-3" 
                                placeholder="Name or Code..." value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Account Type</label>
                        <select name="type" class="form-select border-0">
                            <option value="">All Types</option>
                            <option value="1" {{ ($filters['type'] ?? '') == '1' ? 'selected' : '' }}>Assets</option>
                            <option value="2" {{ ($filters['type'] ?? '') == '2' ? 'selected' : '' }}>Liabilities</option>
                            <option value="3" {{ ($filters['type'] ?? '') == '3' ? 'selected' : '' }}>Equity</option>
                            <option value="4" {{ ($filters['type'] ?? '') == '4' ? 'selected' : '' }}>Income</option>
                            <option value="5" {{ ($filters['type'] ?? '') == '5' ? 'selected' : '' }}>Expenses</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Filter by Branch</label>
                        <select name="branch_id" class="form-select border-0">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" {{ ($filters['branch_id'] ?? '') == $b->id ? 'selected' : '' }}>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">Balance Status</label>
                        <select name="balance_status" class="form-select border-0">
                            <option value="">All Statuses</option>
                            <option value="nonzero" {{ ($filters['balance_status'] ?? '') == 'nonzero' ? 'selected' : '' }}>Non-Zero Balance</option>
                            <option value="debit" {{ ($filters['balance_status'] ?? '') == 'debit' ? 'selected' : '' }}>Debit (+) Only</option>
                            <option value="credit" {{ ($filters['balance_status'] ?? '') == 'credit' ? 'selected' : '' }}>Credit (-) Only</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1 rounded-pill">
                            <i class="fa-solid fa-filter me-2"></i> Filter
                        </button>
                        <a href="{{ route('accounting.chart') }}" class="btn btn-light rounded-pill">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card p-4 border-0 shadow-sm rounded-4">
                <div class="d-flex justify-content-between align-items-center mb-4 ps-2">
                    <div>
                        <h4 class="fw-bold mb-1">Accounting Hierarchy</h4>
                        <p class="text-muted small mb-0">Manage your chart of accounts and sub-accounts with full depth visibility.</p>
                    </div>
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                        data-bs-target="#addAccountModal">
                        <i class="fa-solid fa-plus me-2"></i> Add New Account
                    </button>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 shadow-sm" role="alert">
                        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover align-middle border-top-0">
                        <thead>
                            <tr class="bg-light bg-opacity-50">
                                <th class="ps-4 py-3 text-uppercase small ls-1 fw-bold">Account Name</th>
                                <th class="text-center py-3 text-uppercase small ls-1 fw-bold" style="width: 120px;">Code</th>
                                <th class="text-center py-3 text-uppercase small ls-1 fw-bold" style="width: 140px;">Type</th>
                                <th class="text-end pe-4 py-3 text-uppercase small ls-1 fw-bold" style="width: 250px;">Aggregated Balance</th>
                                <th class="text-center py-3 text-uppercase small ls-1 fw-bold" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($accounts as $account)
                                @include('accounting.partials.account_row', ['account' => $account, 'level' => 0])
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa-solid fa-sitemap fa-3x mb-3 opacity-25"></i>
                                        <p>No accounts found matching your filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <style>
                    .ls-1 { letter-spacing: 0.5px; }
                    .table-parent-row {
                        background-color: rgba(99, 102, 241, 0.04) !important;
                        font-weight: 700;
                    }

                    [data-theme="dark"] .table-parent-row {
                        background-color: rgba(255, 255, 255, 0.03) !important;
                    }

                    .table-hover tbody tr:hover {
                        background-color: rgba(99, 102, 241, 0.07) !important;
                    }
                    
                    .badge { font-weight: 600; font-size: 0.75rem; }
                    code { font-weight: 700; color: #6366f1; }
                    [data-theme="dark"] code { color: #818cf8; }
                </style>
            </div>
        </div>
    </div>

    <!-- Recursive Account Modals -->
    @foreach($accounts as $account)
        @include('accounting.partials.account_modals', ['account' => $account])
    @endforeach

    <!-- Add Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="{{ route('accounting.accounts.store') }}" method="POST" class="account-form">
                @csrf
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Add New Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Parent Account (Optional)</label>
                            <select name="parent_id" id="accountParentSelect" class="form-select bg-light border-0 rounded-3">
                                <option value="">--- Root Account ---</option>
                                @foreach(\App\Models\Account::orderBy('code')->get() as $acc)
                                    <option value="{{ $acc->id }}">[{{ $acc->code }}] {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Account Name (Arabic)</label>
                            <input type="text" name="name_ar" class="form-control bg-light border-0 rounded-3" placeholder="الاسم بالعربي">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Account Name (English)</label>
                            <input type="text" name="name_en" class="form-control bg-light border-0 rounded-3" placeholder="Name in English">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted">Account Code</label>
                                <input type="text" name="code" id="accountCodeInput" class="form-control bg-light border-0 rounded-3" placeholder="Click parent to generate..." readonly required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-bold text-muted">Account Type</label>
                                <select name="type" class="form-select bg-light border-0 rounded-3" required>
                                    <option value="1">Asset</option>
                                    <option value="2">Liability</option>
                                    <option value="3">Equity</option>
                                    <option value="4">Income</option>
                                    <option value="5">Expense</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Produce Account</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const parentSelect = document.getElementById('accountParentSelect');
            const codeInput = document.getElementById('accountCodeInput');

            async function fetchNextCode() {
                const parentId = parentSelect.value;
                try {
                    const response = await fetch(`{{ route('api.accounts.next-code') }}?parent_id=${parentId}`);
                    const data = await response.json();
                    if (data.code) {
                        codeInput.value = data.code;
                    }
                } catch (error) {
                    console.error('Error fetching next code:', error);
                }
            }

            if (parentSelect) {
                parentSelect.addEventListener('change', fetchNextCode);
                // Initial fetch for root
                fetchNextCode();
            }

            document.addEventListener('submit', async function (e) {
                const form = e.target;

                if (form.classList.contains('account-form')) {
                    e.preventDefault();
                    const btn = form.querySelector('button[type="submit"]');
                    if (!btn) return;

                    const originalHtml = btn.innerHTML;
                    btn.disabled = true;
                    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Processing...`;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                        });

                        const data = await response.json();
                        if (response.ok) {
                            if (window.showToast) window.showToast(data.message, 'success');
                            bootstrap.Modal.getInstance(form.closest('.modal')).hide();
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            if (window.showToast) window.showToast(data.message || 'Error', 'error');
                        }
                    } catch (error) {
                        if (window.showToast) window.showToast('Server error', 'error');
                    } finally {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                }

                if (form.classList.contains('account-delete-form')) {
                    e.preventDefault();
                    if (!confirm('Are you sure you want to delete this account?')) return;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                        });

                        const data = await response.json();
                        if (response.ok) {
                            if (window.showToast) window.showToast(data.message, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            if (window.showToast) window.showToast(data.message || 'Error deleting', 'error');
                        }
                    } catch (error) {
                        console.error('Delete AJAX Error:', error);
                    }
                }
            });
        });
    </script>
@endsection