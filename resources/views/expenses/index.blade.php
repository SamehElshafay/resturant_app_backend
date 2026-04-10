@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
<div class="container-fluid py-2">
    <!-- Floating Notifications -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        @if(session('success'))
            <div class="toast show align-items-center text-white bg-success border-0 shadow-lg rounded-3 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body p-3">
                        <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="toast show align-items-center text-white bg-danger border-0 shadow-lg rounded-3" role="alert">
                <div class="d-flex">
                    <div class="toast-body p-3">
                        <i class="fa-solid fa-circle-exclamation me-2"></i> <strong>Save Failed!</strong> Please check the form.
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        @endif
    </div>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-main">Expenses & Petty Cash</h4>
            <p class="text-muted small mb-0">Track and manage your business expenditures and petty cash flow.</p>
        </div>
        <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class="fa-solid fa-plus me-2"></i> Record New Expense
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative h-100">
                <div class="card-body p-4 text-main">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 text-primary">
                            <i class="fa-solid fa-money-bill-transfer fs-4"></i>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill">This Month</span>
                    </div>
                    <h6 class="text-muted small fw-bold mb-1">TOTAL EXPENSES</h6>
                    <h3 class="fw-bold mb-0">${{ number_format($totalThisMonth, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative h-100 text-main">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-3 text-warning">
                            <i class="fa-solid fa-coins fs-4"></i>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill">Petty Cash</span>
                    </div>
                    <h6 class="text-muted small fw-bold mb-1">TOTAL PETTY CASH</h6>
                    <h3 class="fw-bold mb-0">${{ number_format($pettyCashThisMonth, 2) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative h-100 text-main">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-indigo-subtle p-3 rounded-3 text-indigo">
                            <i class="fa-solid fa-file-invoice fs-4"></i>
                        </div>
                    </div>
                    <h6 class="text-muted small fw-bold mb-1">TOTAL RECORDS</h6>
                    <h3 class="fw-bold mb-0">{{ $expenseCount }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs & Filters Row -->
    <div class="row g-3 mb-4 align-items-center">
        <div class="col-xl-4 col-lg-5">
            <ul class="nav nav-pills gap-2 p-2 rounded-pill d-inline-flex border shadow-sm" id="expenseTabs" role="tablist" style="background-color: var(--card-bg, #f8f9fa);">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill px-4 fw-bold" data-filter="all">All Records</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-bold" data-filter="expense">Expenses</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-4 fw-bold" data-filter="petty_cash">Petty Cash</button>
                </li>
            </ul>
        </div>
        <div class="col-xl-8 col-lg-7">
            <form action="{{ route('expenses.index') }}" method="GET" id="filterForm">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end align-items-center">
                    <!-- Status Filter -->
                    <div class="input-group input-group-sm rounded-pill overflow-hidden border shadow-sm bg-white" style="width: auto;">
                        <span class="input-group-text bg-white border-0"><i class="fa-solid fa-circle-check text-muted"></i></span>
                        <select name="status_filter" onchange="document.getElementById('filterForm').submit()" class="form-select border-0 px-3 fw-bold text-main" style="width: 130px; cursor: pointer;">
                            <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>All Status</option>
                            <option value="pending" {{ $statusFilter == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ $statusFilter == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="cancelled" {{ $statusFilter == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div class="input-group input-group-sm rounded-pill overflow-hidden border shadow-sm bg-white" style="width: auto;">
                        <span class="input-group-text bg-white border-0"><i class="fa-solid fa-calendar-alt text-muted"></i></span>
                        <select name="date_filter" id="dateFilterSelect" class="form-select border-0 px-3 fw-bold text-main" style="width: 140px; cursor: pointer;">
                            <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Today</option>
                            <option value="yesterday" {{ $filter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                            <option value="this_week" {{ $filter == 'this_week' ? 'selected' : '' }}>This Week</option>
                            <option value="this_month" {{ $filter == 'this_month' ? 'selected' : '' }}>This Month</option>
                            <option value="custom" {{ $filter == 'custom' ? 'selected' : '' }}>Custom Range</option>
                        </select>
                    </div>

                    <div id="customDateInputs" class="d-flex gap-2 align-items-center {{ $filter == 'custom' ? '' : 'd-none' }}">
                        <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm rounded-pill border shadow-sm px-3" style="width: 150px;">
                        <span class="text-muted small fw-bold">to</span>
                        <input type="date" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm rounded-pill border shadow-sm px-3" style="width: 150px;">
                    </div>

                    <button type="submit" class="btn btn-dark btn-sm rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-filter me-1"></i> Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-0 py-4 px-4">
            <div class="row g-3 align-items-center text-main">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-0">Recent Expenditure</h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group rounded-pill overflow-hidden border">
                        <span class="input-group-text bg-white border-0"><i class="fa-solid fa-search text-muted"></i></span>
                        <input type="text" id="expenseSearch" class="form-control border-0 px-2" placeholder="Search by name, branch or account...">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="min-height: 350px;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light bg-opacity-50 small text-muted text-uppercase">
                        <tr>
                            <th class="ps-4 py-3">Date</th>
                            <th>Entry Details</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Branch</th>
                            <th>Account</th>
                            <th>Amount</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="expenseTableBody">
                        @forelse($expenses as $expense)
                            <tr class="expense-row" data-type="{{ $expense->type }}">
                                <td class="ps-4">
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-main">{{ $expense->expense_date->format('M d, Y') }}</span>
                                        <small class="text-muted">{{ $expense->expense_date->diffForHumans() }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light p-2 rounded-circle me-3">
                                            <i class="fa-solid fa-receipt text-secondary"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-main">{{ $expense->name_ar ?? $expense->name_en }}</div>
                                            <small class="text-muted d-block text-truncate" style="max-width: 200px;">{{ $expense->description ?? 'No description' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($expense->type == 'petty_cash')
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill small">Petty Cash</span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill small">Expense</span>
                                    @endif
                                </td>
                                <td>
                                    @if($expense->status == 'approved')
                                        <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill small"><i class="fa-solid fa-check-double me-1"></i> Approved</span>
                                    @elseif($expense->status == 'cancelled')
                                        <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill small"><i class="fa-solid fa-ban me-1"></i> Cancelled</span>
                                    @else
                                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill small"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-main">
                                        @if($expense->branch)
                                            <i class="fa-solid fa-location-dot me-1 text-primary small"></i> {{ $expense->branch->name }}
                                        @else
                                            <i class="fa-solid fa-earth-americas me-1 text-muted small"></i> Global / No Branch
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        {{ optional($expense->account)->name_ar ?? optional($expense->account)->name_en ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-danger">
                                        -${{ number_format($expense->amount, 2) }}
                                    </div>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light rounded-circle shadow-sm" data-bs-toggle="dropdown">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                            @if($expense->status == 'pending')
                                                <li>
                                                    <form action="{{ route('expenses.approve', $expense->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-success"><i class="fa-solid fa-check me-2"></i> Approve (اعتماد)</button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                            @endif
                                            
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0)" 
                                                   onclick="showExpenseDetails({{ json_encode([
                                                       'date' => $expense->expense_date->format('M d, Y'),
                                                       'name' => $expense->name_ar ?? $expense->name_en,
                                                       'type' => $expense->type == 'petty_cash' ? 'Petty Cash' : 'General Expense',
                                                       'status' => ucfirst($expense->status),
                                                       'amount' => number_format($expense->amount, 2),
                                                       'branch' => $expense->branch->name ?? 'Global',
                                                       'account_name' => optional($expense->account)->name_ar ?? 'N/A',
                                                       'account_code' => optional($expense->account)->code ?? '—',
                                                       'source_name' => optional($expense->sourceAccount)->name_ar ?? 'N/A',
                                                       'source_code' => optional($expense->sourceAccount)->code ?? '—',
                                                       'description' => $expense->description ?? 'No extra notes'
                                                   ]) }})">
                                                    <i class="fa-solid fa-eye me-2"></i> Details
                                                </a>
                                            </li>
                                            
                                            @if($expense->status == 'pending')
                                                <li>
                                                    <a class="dropdown-item" href="javascript:void(0)" onclick='editExpense(@json($expense))'>
                                                        <i class="fa-solid fa-pen me-2"></i> Edit (تعديل)
                                                    </a>
                                                </li>
                                                <li>
                                                    <form action="{{ route('expenses.cancel', $expense->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-warning" onclick="return confirm('Cancel this expense?')">
                                                            <i class="fa-solid fa-ban me-2"></i> Cancel (إلغاء)
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif

                                            @if($expense->status != 'approved')
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Permanent delete this entry?')">
                                                            <i class="fa-solid fa-trash me-2"></i> Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-file-invoice fa-3x opacity-25 mb-3"></i>
                                    <p class="mb-0">No expense records found. Try adding your first one!</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Expense Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 py-4 px-4 bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-receipt me-2"></i>Expense Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="bg-light p-4 text-center border-bottom">
                    <label class="small text-muted text-uppercase fw-bold d-block mb-1">Total Amount</label>
                    <h1 id="det-amount" class="fw-bold text-danger mb-2 display-5"></h1>
                    <span id="det-status" class="badge rounded-pill px-3 py-2 fs-6"></span>
                </div>
                
                <div class="p-4">
                    <div class="row g-4">
                        <div class="col-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1"><i class="fa-solid fa-calendar me-1"></i> Date</label>
                            <span id="det-date" class="fw-bold text-main"></span>
                        </div>
                        <div class="col-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1"><i class="fa-solid fa-tags me-1"></i> Type</label>
                            <span id="det-type" class="fw-bold text-main"></span>
                        </div>
                        
                        <div class="col-12">
                            <div class="bg-light rounded-3 p-3 text-end">
                                <label class="small text-muted text-uppercase fw-bold d-block mb-1">البيان (Description)</label>
                                <div id="det-name" class="fw-bold fs-5 text-primary"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="fw-bold mb-3 border-bottom pb-2 text-muted small text-uppercase">Accounting Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 h-100">
                                        <label class="small text-muted text-uppercase fw-bold d-block mb-1">Target Account</label>
                                        <div id="det-account-name" class="fw-bold small"></div>
                                        <code id="det-account-code" class="text-primary small"></code>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 h-100">
                                        <label class="small text-muted text-uppercase fw-bold d-block mb-1">Source Account</label>
                                        <div id="det-source-name" class="fw-bold small"></div>
                                        <code id="det-source-code" class="text-success small"></code>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1"><i class="fa-solid fa-location-dot me-1"></i> Branch</label>
                            <span id="det-branch" class="text-main fw-semibold"></span>
                        </div>

                        <div class="col-12">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1"><i class="fa-solid fa-note-sticky me-1"></i> Internal Notes</label>
                            <p id="det-desc" class="text-muted small mb-0 p-3 bg-light rounded-3 border-start border-primary border-4"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-dark rounded-pill px-4 w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 text-main">
            <div class="modal-header bg-primary text-white border-0 py-4 px-4">
                <h5 class="modal-title fw-bold" id="expenseModalTitle"><i class="fa-solid fa-file-invoice-dollar me-2"></i>Record New Expense</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('expenses.store') }}" method="POST" id="expenseForm">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-body p-4 p-md-5">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Transaction Type</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-tags"></i></span>
                                <select name="type" id="expenseTypeSelect" class="form-select @error('type') is-invalid @enderror" required>
                                    <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>General Expense</option>
                                    <option value="petty_cash" {{ old('type') == 'petty_cash' ? 'selected' : '' }}>Petty Cash</option>
                                </select>
                            </div>
                            @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Linked Account (Fixed)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-sitemap"></i></span>
                                <input type="text" id="displayAccountName" class="form-control fw-bold text-primary" readonly placeholder="Selecting account...">
                                <input type="hidden" name="source_account_id" id="hiddenSourceAccountId">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Date</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-calendar-day"></i></span>
                                <input type="date" name="expense_date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                            </div>
                            @error('expense_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Account (Searchable)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-search"></i></span>
                                <select name="account_id" class="form-select select2-modal @error('account_id') is-invalid @enderror" required>
                                    <option value="">Select Account...</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->name_ar ?? $acc->name_en }} ({{ $acc->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('account_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Amount</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-primary text-white border-0 fw-bold shadow-sm" style="border-radius: 12px 0 0 12px !important;">$</span>
                                <input type="number" name="amount" class="form-control fw-bold @error('amount') is-invalid @enderror" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                            </div>
                            @error('amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 text-end">
                            <label class="form-label fw-bold small text-muted text-uppercase d-block">الوصف بالعربي</label>
                            <input type="text" name="name_ar" class="form-control bg-light border-0 text-end" placeholder="مثال: فاتورة كهرباء">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">English Description</label>
                            <input type="text" name="name_en" class="form-control bg-light border-0" placeholder="e.g. Electricity Bill">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Notes</label>
                            <textarea name="description" class="form-control bg-light border-0" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveExpenseBtn" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm d-flex align-items-center">
                        <span class="btn-text">Save Expense</span>
                        <div class="spinner-border spinner-border-sm ms-2 d-none" role="status"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('extra_css')
<style>
    .rounded-4 { border-radius: 1rem !important; }
    .text-indigo { color: #6366f1; }
    .bg-indigo-subtle { background-color: rgba(99, 102, 241, 0.1); }
    .table-hover tbody tr:hover { background-color: rgba(0,0,0,.02); transition: all 0.2s; }
    .nav-pills .nav-link { color: #64748b; }
    .nav-pills .nav-link.active { background-color: var(--primary-color) !important; color: white !important; }
    
    /* Fix for dropdown menus being cut off in responsive tables */
    .table-responsive {
        overflow: visible !important;
    }
    .dropdown-menu {
        z-index: 1100 !important;
    }
    
    /* Modal Dark Mode Fixes */
    .modal-content {
        background-color: var(--card-bg, #ffffff) !important;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .modal-backdrop.show {
        opacity: 0.7;
    }
    .bg-light {
        background-color: rgba(0,0,0,0.05) !important;
    }
    [data-theme="dark"] .bg-light {
        background-color: rgba(255,255,255,0.05) !important;
    }
    .form-control, .form-select {
        opacity: 1 !important;
    }
    [data-theme="dark"] .form-control, [data-theme="dark"] .form-select {
        background-color: rgba(255,255,255,0.05) !important;
        color: #fff !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
    }
    [data-theme="dark"] .input-group-text {
        background-color: rgba(255,255,255,0.1) !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        color: var(--primary-color) !important;
    }

    /* Premium Select2 Styling Overrides */
    .select2-container--default .select2-selection--single {
        border: none !important;
        background-color: transparent !important;
        height: 45px !important;
        display: flex !important;
        align-items: center !important;
        padding-left: 12px !important;
        font-size: 0.95rem;
        transition: all 0.2s;
        width: 100% !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 43px !important;
        right: 12px !important;
    }
    
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text-main) !important;
        font-weight: 600 !important;
        padding-right: 35px !important;
        width: 100% !important;
        text-align: left !important;
    }

    /* Fix Select2 inside Input Group */
    .input-group > .select2-container.select2-container--default {
        flex: 1 !important;
        width: 0 !important;
        display: block !important;
    }

    .input-group > .select2-container--default .select2-selection--single {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        background-color: transparent !important;
    }

    [data-theme="dark"] .input-group > .select2-container--default .select2-selection--single {
        background-color: rgba(255,255,255,0.05) !important;
    }
    
    .select2-dropdown {
        background-color: var(--card-bg) !important;
        border-radius: 15px !important;
        border: 1px solid var(--border-color) !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important;
        padding: 5px;
        z-index: 1060; /* Above modal */
    }
    
    .select2-results__option {
        border-radius: 8px !important;
        margin: 2px 0;
        padding: 8px 12px !important;
        font-size: 0.9rem;
        color: var(--text-main) !important;
    }
    
    .select2-container--default    .select2-results__option--selected {
        background-color: rgba(99, 102, 241, 0.1) !important;
        color: var(--primary-color) !important;
        font-weight: bold !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--primary-color) !important;
        color: white !important;
    }
    
    /* Force text color visibility */
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text-main) !important;
    }
    
    [data-theme="light"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #111827 !important; /* Force dark text in light mode */
    }
    
    [data-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #f9fafb !important; /* Force light text in dark mode */
    }

    .select2-search--dropdown .select2-search__field {
        border-radius: 10px !important;
        padding: 8px 12px !important;
        background-color: var(--bg-color) !important;
        border: 1px solid var(--border-color) !important;
        color: var(--text-main) !important;
    }

    /* Modal Input Group Refinements */
    #addExpenseModal .input-group {
        flex-wrap: nowrap !important;
        border-radius: 12px !important;
        overflow: hidden;
        background-color: rgba(0,0,0,0.05);
    }

    [data-theme="dark"] #addExpenseModal .input-group {
        background-color: rgba(255,255,255,0.05);
    }

    #addExpenseModal .input-group-text {
        height: 45px !important;
        background-color: rgba(99, 102, 241, 0.1) !important;
        border: none !important;
        color: var(--primary-color) !important;
        min-width: 48px;
        justify-content: center;
        border-radius: 12px 0 0 12px !important;
    }

    #addExpenseModal .form-control, 
    #addExpenseModal .form-select,
    #addExpenseModal .input-group .select2-container--default .select2-selection--single {
        height: 45px !important;
        border-radius: 0 12px 12px 0 !important;
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab Filtering Logic
        const tabButtons = document.querySelectorAll('#expenseTabs .nav-link');
        const rows = document.querySelectorAll('.expense-row');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active state
                tabButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                rows.forEach(row => {
                    if (filter === 'all' || row.dataset.type === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Search Search Logic
        document.getElementById('expenseSearch').addEventListener('keyup', function() {
            let value = this.value.toLowerCase();
            const activeFilter = document.querySelector('#expenseTabs .nav-link.active').dataset.filter;
            
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                let matchesSearch = text.indexOf(value) > -1;
                let matchesTab = activeFilter === 'all' || row.dataset.type === activeFilter;
                
                row.style.display = (matchesSearch && matchesTab) ? '' : 'none';
            });
        });

        // Form Submission Loading State
        const expenseForm = document.querySelector('#addExpenseModal form');
        const saveBtn = document.getElementById('saveExpenseBtn');
        const btnText = saveBtn.querySelector('.btn-text');
        const spinner = saveBtn.querySelector('.spinner-border');

        expenseForm.addEventListener('submit', function() {
            saveBtn.disabled = true;
            btnText.textContent = 'Saving...';
            spinner.classList.remove('d-none');
        });

        // Auto-hide toasts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.toast').forEach(t => t.classList.remove('show'));
        }, 5000);

        // Date Filter Logic
        const dateFilterSelect = document.getElementById('dateFilterSelect');
        const customDateInputs = document.getElementById('customDateInputs');

        dateFilterSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateInputs.classList.remove('d-none');
            } else {
                customDateInputs.classList.add('d-none');
                // Automatically submit for predefined filters
                document.getElementById('filterForm').submit();
            }
        });
    });

    function showExpenseDetails(data) {
        document.getElementById('det-date').textContent = data.date;
        document.getElementById('det-amount').textContent = '-$' + data.amount;
        document.getElementById('det-name').textContent = data.name;
        document.getElementById('det-type').textContent = data.type;
        document.getElementById('det-branch').textContent = data.branch;
        document.getElementById('det-desc').textContent = data.description;
        
        // Status Badge Logic
        const statusEl = document.getElementById('det-status');
        statusEl.textContent = data.status;
        statusEl.className = 'badge rounded-pill px-3 py-2 fs-6 ';
        if (data.status.toLowerCase() === 'approved') statusEl.classList.add('bg-success');
        else if (data.status.toLowerCase() === 'cancelled') statusEl.classList.add('bg-danger');
        else statusEl.classList.add('bg-primary');

        // Accounting Details
        document.getElementById('det-account-name').textContent = data.account_name;
        document.getElementById('det-account-code').textContent = data.account_code;
        document.getElementById('det-source-name').textContent = data.source_name;
        document.getElementById('det-source-code').textContent = data.source_code;
        
        var detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
        detailsModal.show();
    }

    // Dynamic Account Selection Logic
    const accountMappings = @json($accountMappings);
    const expenseTypeSelect = document.getElementById('expenseTypeSelect');
    const displayAccountName = document.getElementById('displayAccountName');
    const hiddenSourceAccountId = document.getElementById('hiddenSourceAccountId');

    function updateLinkedAccount() {
        const type = expenseTypeSelect.value;
        const mapping = accountMappings[type];
        
        if (mapping) {
            displayAccountName.value = mapping.name + ' (' + mapping.code + ')';
            hiddenSourceAccountId.value = mapping.id;
        } else {
            displayAccountName.value = 'No mapping found (Contact Admin)';
            hiddenSourceAccountId.value = '';
        }
    }

    expenseTypeSelect.addEventListener('change', updateLinkedAccount);
    
    // Initial load & Select2 re-init for width
    document.addEventListener('DOMContentLoaded', function() {
        updateLinkedAccount();
        
        // Ensure Select2 takes full width when modal opens
        $('#addExpenseModal').on('shown.bs.modal', function () {
            $(this).find('.select2-modal').select2({
                dropdownParent: $('#addExpenseModal'),
                width: '100%'
            });
        });

        // Reset form when modal hidden
        $('#addExpenseModal').on('hidden.bs.modal', function () {
            const form = document.getElementById('expenseForm');
            form.action = "{{ route('expenses.store') }}";
            document.getElementById('formMethod').value = "POST";
            document.getElementById('expenseModalTitle').innerHTML = '<i class="fa-solid fa-file-invoice-dollar me-2"></i>Record New Expense';
            form.reset();
            updateLinkedAccount();
            // Trigger select2 reset
            $(form).find('.select2-modal').val('').trigger('change');
        });
    });

    function editExpense(data) {
        const modal = new bootstrap.Modal(document.getElementById('addExpenseModal'));
        const form = document.getElementById('expenseForm');
        
        // Change title and method
        document.getElementById('expenseModalTitle').innerHTML = '<i class="fa-solid fa-pen me-2"></i>Edit Expense';
        document.getElementById('formMethod').value = "PUT";
        form.action = `/expenses/${data.id}`;

        // Fill fields
        form.querySelector('[name="type"]').value = data.type;
        form.querySelector('[name="name_ar"]').value = data.name_ar || '';
        form.querySelector('[name="name_en"]').value = data.name_en || '';
        form.querySelector('[name="amount"]').value = data.amount;
        form.querySelector('[name="expense_date"]').value = data.expense_date.split('T')[0];
        form.querySelector('[name="description"]').value = data.description || '';
        
        if (data.branch_id) {
            form.querySelector('[name="branch_id"]').value = data.branch_id;
        }

        // Handle searchable account (Select2)
        if (data.account_id) {
            $(form).find('[name="account_id"]').val(data.account_id).trigger('change');
        }

        updateLinkedAccount();
        modal.show();
    }
</script>
@if(request('open_id'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @php 
            $openExpense = $expenses->firstWhere('id', request('open_id'));
        @endphp
        @if($openExpense)
            showExpenseDetails({
                date: '{{ $openExpense->expense_date->format('M d, Y') }}',
                name: '{{ $openExpense->name_ar ?? $openExpense->name_en }}',
                type: '{{ $openExpense->type == 'petty_cash' ? 'Petty Cash' : 'General Expense' }}',
                status: '{{ ucfirst($openExpense->status) }}',
                amount: '{{ number_format($openExpense->amount, 2) }}',
                branch: '{{ $openExpense->branch->name ?? 'Global' }}',
                account_name: '{{ optional($openExpense->account)->name_ar ?? 'N/A' }}',
                account_code: '{{ optional($openExpense->account)->code ?? '—' }}',
                source_name: '{{ optional($openExpense->sourceAccount)->name_ar ?? 'N/A' }}',
                source_code: '{{ optional($openExpense->sourceAccount)->code ?? '—' }}',
                description: '{{ $openExpense->description ?? "No extra notes" }}'
            });
        @endif
    });
</script>
@endif

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var addModal = new bootstrap.Modal(document.getElementById('addExpenseModal'));
        addModal.show();
    });
</script>
@endif
@endsection