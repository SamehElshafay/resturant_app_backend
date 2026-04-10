@extends('layouts.app')

@section('title', __('messages.statement_for') . ' ' . $account->name)

@section('content')
    <style>
        .clickable:hover {
            background-color: rgba(var(--bs-primary-rgb), 0.05) !important;
            transition: background-color 0.2s ease;
        }
    </style>
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('accounting.chart') }}">{{ __('messages.accounts') }}</a></li>
                    <li class="breadcrumb-item active">{{ $account->name }}</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0">{{ __('messages.account_statement') }}</h3>
            <p class="text-muted mb-0">{{ __('messages.account_code') }}: <code class="text-primary">{{ $account->code }}</code> | {{ __('messages.type') }}:
                <strong>{{ __('messages.' . strtolower($account->type_name)) }}</strong></p>
        </div>
        <button class="btn btn-outline-primary rounded-pill px-4" onclick="window.print()">
            <i class="fa-solid fa-print me-2"></i> {{ __('messages.print_statement') }}
        </button>
    </div>

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm p-4 mb-4">
        <form action="{{ route('accounting.statement', $account->id) }}" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">{{ __('messages.from_date') }}</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">{{ __('messages.to_date') }}</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1 rounded-pill">
                        <i class="fa-solid fa-filter me-2"></i> {{ __('messages.update_range') }}
                    </button>
                    <a href="{{ route('accounting.statement', $account->id) }}" class="btn btn-light rounded-pill">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Ledger Table -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="bg-light bg-opacity-10">
                        <th class="ps-4">{{ __('messages.date') }}</th>
                        <th>{{ __('messages.description') }}</th>
                        <th class="text-end">{{ __('messages.debit') }}</th>
                        <th class="text-end">{{ __('messages.credit') }}</th>
                        <th class="text-end pe-4">{{ __('messages.net_balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance -->
                    <tr class="bg-info bg-opacity-10">
                        <td class="ps-4">---</td>
                        <td class="fw-bold">{{ __('messages.opening_balance') }} ({{ __('messages.date_to') }} {{ $startDate }})</td>
                        <td class="text-end">---</td>
                        <td class="text-end">---</td>
                        <td class="text-end pe-4 fw-bold {{ $openingBalance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($openingBalance, 2) }}
                        </td>
                    </tr>

                    @php $runningBalance = $openingBalance; @endphp
                    @forelse($entries as $entry)
                        @php 
                            $runningBalance += ($entry->debit - $entry->credit);
                            $url = null;
                            if($entry->reference_type === 'production') $url = route('productions.show', $entry->reference_id);
                            elseif($entry->reference_type === 'voucher') $url = route('vouchers.show', $entry->reference_id);
                            elseif($entry->reference_type === 'purchase_invoice') $url = route('purchase_invoices.show', $entry->reference_id);
                            elseif($entry->reference_type === 'expense') $url = route('expenses.show', $entry->reference_id);
                        @endphp
                        <tr class="entry-row {{ $url ? 'clickable' : '' }}" {!! $url ? 'onclick="window.location=\''.$url.'\'"' : '' !!} style="{{ $url ? 'cursor: pointer;' : '' }}">
                            <td class="ps-4 small">{{ $entry->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <div>{{ $entry->description ?? 'Transaction Record' }}</div>
                                @if($entry->reference_type === 'production')
                                    <small class="text-muted d-block">
                                        <i class="fa-solid fa-industry me-1"></i>
                                        {{ __('messages.production_accounting') }}: #{{ $entry->reference_id }}
                                    </small>
                                @elseif($entry->reference_type === 'voucher')
                                    <small class="text-muted">
                                        <i class="fa-solid fa-file-invoice me-1"></i>
                                        {{ __('messages.vouchers') }}: #{{ $entry->reference_id }}
                                    </small>
                                @elseif($entry->reference_type === 'purchase_invoice')
                                    <small class="text-muted d-block">
                                        <i class="fa-solid fa-file-invoice me-1"></i>
                                        {{ __('messages.merchant_bill_debit') }}: #{{ $entry->reference_id }}
                                    </small>
                                @elseif($entry->reference_type === 'expense')
                                    <small class="text-muted d-block">
                                        <i class="fa-solid fa-file-invoice-dollar me-1"></i>
                                        {{ __('messages.expense_type') }}: #{{ $entry->reference_id }}
                                    </small>
                                @endif
                            </td>
                            <td class="text-end text-primary">
                                {{ $entry->debit > 0 ? '+' . number_format($entry->debit, 2) : '-' }}</td>
                            <td class="text-end text-danger">
                                {{ $entry->credit > 0 ? '-' . number_format($entry->credit, 2) : '-' }}</td>
                            <td class="text-end pe-4 fw-bold">{{ number_format($runningBalance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-inbox fa-3x mb-3 opacity-25"></i>
                                <p>{{ __('messages.no_transactions') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-light bg-opacity-10">
                        <th colspan="2" class="ps-4">{{ __('messages.net_change_final_balance') }}</th>
                        <th class="text-end text-primary">+{{ number_format($entries->sum('debit'), 2) }}</th>
                        <th class="text-end text-danger">-{{ number_format($entries->sum('credit'), 2) }}</th>
                        <th class="text-end pe-4 fw-bold fs-5">{{ number_format($runningBalance, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection