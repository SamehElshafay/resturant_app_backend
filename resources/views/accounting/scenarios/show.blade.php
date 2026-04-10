@php
    $commonTags = [
        '{{total_amount}}' => 'Total Amount (Invoice/Voucher)',
        '{{total_cost}}' => 'Total Production Cost',
        '{{total_value}}' => 'Inventory Value',
        '{{supplier_account}}' => 'Supplier Account (Linked)',
        '{{branch_account}}' => 'Branch Account (Linked)',
        '{{customer_account}}' => 'Customer Account (Linked)',
        '{{treasury_account}}' => 'Branch Treasury (Default)',
        '{{bank_account}}' => 'Default Bank Account',
    ];
@endphp

@extends('layouts.app')
@section('title', 'Configure: ' . $scenario->name)

@section('content')
<div class="container-fluid px-4">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('accounting.scenarios.index') }}">Scenarios</a></li>
                <li class="breadcrumb-item active">{{ $scenario->name }}</li>
            </ol>
        </nav>
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="fw-bold mb-0 text-gradient">{{ $scenario->name }}</h3>
                <code class="px-2 py-1 rounded-pill bg-light text-primary border shadow-sm">{{ $scenario->event_key }}</code>
            </div>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal"
                data-bs-target="#addStepModal">
                <i class="fa-solid fa-plus-circle me-2"></i>Add Step
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 mt-2">
                        <thead class="border-bottom" style="background-color: var(--sidebar-bg); color: var(--text-main);">
                            <tr>
                                <th class="ps-4" style="width: 60px;">#</th>
                                <th>Description / الوصف</th>
                                <th class="text-danger"><i class="fa-solid fa-minus-circle me-2"></i>Debit / مدين</th>
                                <th class="text-success"><i class="fa-solid fa-plus-circle me-2"></i>Credit / دائن</th>
                                <th>Formula / المعادلة</th>
                                <th>Condition / الشرط</th>
                                <th class="pe-4 text-end">Actions / الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($scenario->steps as $step)
                                <tr>
                                    <td class="ps-4">
                                        <span class="badge rounded-pill bg-dark py-1 px-3">{{ $step->priority }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold" style="color: var(--text-main);">{{ $step->description }}</span>
                                    </td>
                                    <td>
                                        @if(str_contains($step->debit_account_pattern, '{'))
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-tag me-1"></i> {{ $step->debit_account_pattern }}</span>
                                        @else
                                            <code class="text-danger fw-bold px-2 py-1 rounded border" style="background-color: var(--sidebar-bg); border-color: var(--border-color)!important;">{{ $step->debit_account_pattern }}</code>
                                        @endif
                                    </td>
                                    <td>
                                        @if(str_contains($step->credit_account_pattern, '{'))
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-tag me-1"></i> {{ $step->credit_account_pattern }}</span>
                                        @else
                                            <code class="text-success fw-bold px-2 py-1 rounded border" style="background-color: var(--sidebar-bg); border-color: var(--border-color)!important;">{{ $step->credit_account_pattern }}</code>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="small fw-bold" style="color: var(--text-secondary);"><i class="fa-solid fa-calculator me-1"></i> {{ $step->debit_amount_formula ?? $step->credit_amount_formula ?? $step->amount_formula }}</span>
                                    </td>
                                    <td>
                                        @if($step->condition_expression)
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle small font-monospace">
                                                <i class="fa-solid fa-filter me-1"></i> {{ $step->condition_expression }}
                                            </span>
                                        @else
                                            <span class="text-muted small text-uppercase">Always</span>
                                        @endif
                                    </td>
                                    <td class="pe-4 text-end">
                                        <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                            <button class="btn btn-sm btn-white border-end"
                                                data-bs-toggle="modal" data-bs-target="#editStepModal{{ $step->id }}">
                                                <i class="fa-solid fa-pen-to-square text-primary"></i>
                                            </button>
                                            <form action="{{ route('accounting.scenarios.steps.destroy', $step->id) }}"
                                                method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-white"
                                                    onclick="return confirm('Delete this step?')">
                                                    <i class="fa-solid fa-trash-can text-danger"></i>
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Edit Step Modal -->
                                        @include('accounting.scenarios._step_modal', ['step' => $step, 'accounts' => $accounts, 'commonTags' => $commonTags])
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-muted mb-3">
                                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                <i class="fa-solid fa-puzzle-piece fs-1 opacity-25"></i>
                                            </div>
                                            <h5 class="fw-bold">No Rules Defined</h5>
                                            <p>Start by adding the first accounting step for this scenario.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Step Modal -->
@include('accounting.scenarios._step_modal', ['step' => null, 'scenario' => $scenario, 'accounts' => $accounts, 'commonTags' => $commonTags])

<style>
    .text-gradient {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .modal-content { border: none; border-radius: 1.2rem; }
    .nav-pills-custom .nav-link {
        border-radius: 50rem;
        padding: 0.3rem 1rem;
        font-size: 0.8rem;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        margin-right: 0.5rem;
    }
    .nav-pills-custom .nav-link.active {
        background: #0d6efd;
        color: white;
    }
</style>
@endsection
