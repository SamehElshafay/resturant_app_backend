@extends('layouts.app')
@section('title', __('messages.vouchers'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ __('messages.vouchers') }}</h4>
            <p class="text-muted small mb-0">{{ __('messages.vouchers_subtitle') }}</p>
        </div>
        <a href="{{ route('vouchers.create') }}" class="btn btn-primary rounded-pill px-4">
            <i class="fa-solid fa-plus me-2"></i>{{ __('messages.new_voucher' ?? 'New Voucher') }}
        </a>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-pills gap-2 mb-4 p-2 bg-theme-card rounded-4 shadow-sm" style="width: fit-content;">
        <li class="nav-item">
            <a class="nav-link rounded-3 {{ !$type ? 'active' : '' }} btn-nav-tab" href="{{ route('vouchers.index') }}">
                <i class="fa-solid fa-list me-2"></i>{{ __('messages.vouchers') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-3 {{ $type == 'RECEIPT' ? 'active' : '' }} btn-nav-tab btn-nav-receipt"
                href="{{ route('vouchers.index', ['type' => 'RECEIPT']) }}">
                <i class="fa-solid fa-arrow-down me-2"></i>{{ __('messages.receipts') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-3 {{ $type == 'PAYMENT' ? 'active' : '' }} btn-nav-tab btn-nav-payment"
                href="{{ route('vouchers.index', ['type' => 'PAYMENT']) }}">
                <i class="fa-solid fa-arrow-up me-2"></i>{{ __('messages.payments') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link rounded-3 {{ $type == 'TRANSFER' ? 'active' : '' }} btn-nav-tab btn-nav-transfer"
                href="{{ route('vouchers.index', ['type' => 'TRANSFER']) }}">
                <i class="fa-solid fa-right-left me-2"></i>{{ __('messages.transfers') }}
            </a>
        </li>
    </ul>

    <style>
        .bg-theme-card {
            background-color: var(--card-bg) !important;
            border: 1px solid var(--border-color);
        }

        .btn-nav-tab {
            color: var(--text-secondary);
            font-weight: 600;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .btn-nav-tab:hover {
            color: var(--text-main);
            background: rgba(99, 102, 241, 0.05);
        }

        .btn-nav-tab.active {
            background-color: #6366f1 !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.24);
        }

        .btn-nav-receipt:not(.active) {
            color: #10b981;
        }

        .btn-nav-payment:not(.active) {
            color: #ef4444;
        }

        .btn-nav-transfer:not(.active) {
            color: #0ea5e9;
        }

        [data-theme="dark"] .bg-theme-card {
            background-color: rgba(30, 41, 59, 0.5) !important;
        }
    </style>

    {{-- Compact Status Filter --}}
    <div class="card p-2 mb-4 border-0 shadow-sm rounded-4" style="width: fit-content;">
        <div class="d-flex align-items-center gap-2">
            <span class="ms-3 small fw-bold text-muted">Status:</span>
            @foreach(['DRAFT' => 'warning', 'POSTED' => 'success', 'CANCELLED' => 'danger'] as $st => $clr)
                <a href="{{ route('vouchers.index', array_merge(request()->query(), ['status' => ($status == $st ? '' : $st)])) }}"
                    class="btn btn-sm rounded-3 py-1 px-3 {{ $status == $st ? 'btn-' . $clr : 'btn-outline-' . $clr }}">
                    {{ $st }}
                </a>
            @endforeach
            @if($status)
                <a href="{{ route('vouchers.index', request()->except('status')) }}" class="btn btn-sm btn-link text-muted"><i
                        class="fa-solid fa-xmark"></i></a>
            @endif
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">#</th>
                        <th>{{ __('messages.type') }}</th>
                        <th>{{ __('messages.debit_account') }}</th>
                        <th>{{ __('messages.credit_account') }}</th>
                        <th class="text-end">{{ __('messages.amount') }}</th>
                        <th>{{ __('messages.status') }}</th>
                        <th>{{ __('messages.created_by') }}</th>
                        <th>{{ __('messages.date') }}</th>
                        <th class="pe-4 text-end">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $v)
                        <tr>
                            <td class="ps-4 text-muted small">#{{ $v->id }}</td>
                            <td>
                                @php
                                    $typeConfig = [
                                        'RECEIPT' => ['icon' => 'fa-arrow-down', 'color' => 'success', 'label' => __('messages.receipts')],
                                        'PAYMENT' => ['icon' => 'fa-arrow-up', 'color' => 'danger', 'label' => __('messages.payments')],
                                        'TRANSFER' => ['icon' => 'fa-right-left', 'color' => 'info', 'label' => __('messages.transfers')],
                                    ];
                                    $tc = $typeConfig[$v->voucher_type] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => $v->voucher_type];
                                @endphp
                                <span class="badge bg-{{ $tc['color'] }}-subtle text-{{ $tc['color'] }} px-3 py-2 rounded-pill">
                                    <i class="fa-solid {{ $tc['icon'] }} me-1"></i>{{ $tc['label'] }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $debitAcc = '—';
                                    if ($v->voucher_type === 'RECEIPT') {
                                        $debitAcc = $v->treasury_account_code ?: ($v->bank_account_code ?: '—');
                                    } elseif ($v->voucher_type === 'PAYMENT') {
                                        $debitAcc = $v->account_code;
                                    } elseif ($v->voucher_type === 'TRANSFER') {
                                        $debitAcc = $v->recipient_account_code;
                                    }
                                @endphp
                                <code class="text-success">{{ $debitAcc }}</code>
                            </td>
                            <td>
                                @php
                                    $creditAcc = '—';
                                    if ($v->voucher_type === 'RECEIPT') {
                                        $creditAcc = $v->account_code;
                                    } elseif ($v->voucher_type === 'PAYMENT') {
                                        $creditAcc = $v->treasury_account_code ?: ($v->bank_account_code ?: '—');
                                    } elseif ($v->voucher_type === 'TRANSFER') {
                                        $creditAcc = $v->account_code;
                                    }
                                @endphp
                                <code class="text-danger">{{ $creditAcc }}</code>
                            </td>
                            <td class="text-end fw-bold">${{ number_format($v->amount, 2) }}</td>
                            <td>
                                @php
                                    $statusBadge = ['DRAFT' => 'warning', 'POSTED' => 'success', 'CANCELLED' => 'danger'];
                                @endphp
                                <span class="badge bg-{{ $statusBadge[$v->status] ?? 'secondary' }}">{{ $v->status }}</span>
                            </td>
                            <td>{{ $v->createdBy?->name ?? '—' }}</td>
                            <td class="text-muted small">{{ $v->created_at?->format('d M Y') }}</td>
                            <td class="pe-4 text-end">
                                <a href="{{ route('vouchers.show', $v->id) }}" class="btn btn-sm btn-light rounded-3">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                @if($v->status === 'DRAFT')
                                    <form action="{{ route('vouchers.post', $v->id) }}" method="POST" class="d-inline"
                                        id="post-form-{{ $v->id }}">
                                        @csrf
                                        <button type="button" class="btn btn-sm btn-success rounded-3"
                                            onclick="postVoucher({{ $v->id }})">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('vouchers.cancel', $v->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger rounded-3"
                                            onclick="return confirm('Cancel this voucher?')">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fa-solid fa-receipt fa-3x text-light mb-3 d-block"></i>
                                <p class="text-muted">No vouchers found. <a href="{{ route('vouchers.create') }}">Create
                                        one</a>.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vouchers->hasPages())
            <div class="d-flex justify-content-center p-4">
                {{ $vouchers->withQueryString()->links() }}
            </div>
        @endif
    </div>

    {{-- Modern Confirmation Modal --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 pb-0 justify-content-center pt-4">
                    <div class="bg-primary-subtle rounded-circle p-3 mb-2">
                        <i class="fa-solid fa-stamp fa-2x text-primary" id="modalIcon"></i>
                    </div>
                </div>
                <div class="modal-body text-center px-4 pt-2 pb-4">
                    <h5 class="fw-bold mb-2" id="modalTitle">Confirm Action</h5>
                    <p class="text-muted small" id="modalMessage">Are you sure you want to proceed?</p>
                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill flex-fill py-2 fw-bold"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmBtnAction"
                            class="btn btn-primary rounded-pill flex-fill py-2 fw-bold shadow-sm">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Toast Notification --}}
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="voucherToast" class="toast align-items-center border-0 shadow-lg" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-bold py-3" id="toastMsg"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <script>
        let modalInstance = null;

        function showConfirm({ title, message, icon, btnClass, onConfirm }) {
            const modalEl = document.getElementById('confirmModal');
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('modalIcon').className = `fa-solid ${icon || 'fa-question'} fa-2x text-${btnClass || 'primary'}`;

            const actionBtn = document.getElementById('confirmBtnAction');
            actionBtn.className = `btn btn-${btnClass || 'primary'} rounded-pill flex-fill py-2 fw-bold shadow-sm`;

            // Clear previous listeners
            const newBtn = actionBtn.cloneNode(true);
            actionBtn.parentNode.replaceChild(newBtn, actionBtn);

            newBtn.addEventListener('click', () => {
                modalInstance.hide();
                onConfirm();
            });

            if (!modalInstance) {
                modalInstance = new bootstrap.Modal(modalEl);
            }
            modalInstance.show();
        }

        async function postVoucher(id) {
            if (typeof bootstrap === 'undefined') {
                console.error('Bootstrap is not loaded!');
                alert('System Error: Bootstrap library not found. Please refresh the page.');
                return;
            }
            showConfirm({
                title: 'Post Voucher?',
                message: 'This will finalize the voucher, create journal entries, and update account balances. This action is irreversible.',
                icon: 'fa-stamp',
                btnClass: 'success',
                onConfirm: async () => {
                    const btn = document.querySelector(`#post-form-${id} button`);
                    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }

                    try {
                        const res = await fetch(`/vouchers/${id}/post`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        const data = await res.json();
                        showToast(data.message, data.success ? 'success' : 'danger');
                        if (data.success) setTimeout(() => location.reload(), 1200);
                    } catch (e) {
                        showToast('Request failed', 'danger');
                    }
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-check"></i>'; }
                }
            });
        }

        function showToast(msg, type = 'success') {
            const toast = document.getElementById('voucherToast');
            toast.className = `toast align-items-center text-bg-${type} border-0`;
            document.getElementById('toastMsg').textContent = msg;
            new bootstrap.Toast(toast, { delay: 3000 }).show();
        }
    </script>
@endsection