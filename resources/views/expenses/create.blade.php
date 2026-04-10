@extends('layouts.app')

@section('title', 'Record Expense')

@section('content')
@section('content')
<div class="row justify-content-center py-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Form Header -->
            <div class="card-header bg-primary py-4 px-4 text-white border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-white bg-opacity-20 p-3 rounded-3 me-3">
                        <i class="fa-solid fa-file-invoice-dollar fs-3"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">Record New Expense</h4>
                        <p class="mb-0 opacity-75 small text-white">Fill in the details below to log a general expense or petty cash transaction.</p>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 p-md-5">
                @if($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                        <ul class="mb-0 small">@foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
                    </div>
                @endif

                <form action="{{ route('expenses.store') }}" method="POST">
                    @csrf
                    
                    <div class="row g-4 mb-4">
                        <!-- Type Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Transaction Type</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-tags text-primary"></i></span>
                                <select name="type" class="form-select bg-light border-0 @error('type') is-invalid @enderror" required>
                                    <option value="expense">General Expense (مصروف عام)</option>
                                    <option value="petty_cash">Petty Cash (نثريات)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Branch Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Branch / Location (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-building text-primary"></i></span>
                                <select name="branch_id" class="form-select bg-light border-0 @error('branch_id') is-invalid @enderror">
                                    <option value="">Global / All Branches</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Account Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Accounting Head (Optional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-sitemap text-primary"></i></span>
                                <select name="account_id" class="form-select bg-light border-0 @error('account_id') is-invalid @enderror">
                                    <option value="">Select Account...</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->name_ar ?? $account->name_en }} ({{ $account->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Date Selection -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Transaction Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0"><i class="fa-solid fa-calendar-day text-primary"></i></span>
                                <input type="date" name="expense_date" class="form-control bg-light border-0" value="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>

                        <!-- Amount Input -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Amount</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-primary text-white border-0 fw-bold">$</span>
                                <input type="number" name="amount" class="form-control bg-light border-0 fw-bold" step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 opacity-10">

                    <div class="row g-4 mb-4">
                        <!-- Arabic Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase text-end d-block">الوصف (بالعربي)</label>
                            <input type="text" name="name_ar" class="form-control bg-light border-0 text-end" placeholder="مثال: فاتورة كهرباء">
                        </div>

                        <!-- English Name -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted text-uppercase">Description (English)</label>
                            <input type="text" name="name_en" class="form-control bg-light border-0" placeholder="e.g. Electricity Bill">
                        </div>

                        <!-- Full Description -->
                        <div class="col-12">
                            <label class="form-label fw-bold small text-muted text-uppercase">Additional Notes / Remarks</label>
                            <textarea name="description" class="form-control bg-light border-0" rows="3" placeholder="Enter any extra details here..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-3 pt-2">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm py-3 flex-grow-1">
                           <i class="fa-solid fa-save me-2"></i> Save Expense Record
                        </button>
                        <a href="{{ route('expenses.index') }}" class="btn btn-light rounded-pill px-4 fw-bold py-3 shadow-none border">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@section('extra_css')
<style>
    .rounded-4 { border-radius: 1.25rem !important; }
    .form-control:focus, .form-select:focus {
        background-color: var(--card-bg) !important;
        box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.15);
        border: 1px solid var(--primary-color) !important;
    }
    .input-group-text { color: var(--primary-color); }
</style>
@endsection
@endsection