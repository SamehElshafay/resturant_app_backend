@extends('layouts.app')
@section('title', 'Voucher #' . $voucher->id)

@section('content')
    @php
        $typeConfig = [
            'RECEIPT' => ['icon' => 'fa-arrow-down', 'color' => 'success', 'label' => 'Receipt', 'ar' => 'سند قبض'],
            'PAYMENT' => ['icon' => 'fa-arrow-up', 'color' => 'danger', 'label' => 'Payment', 'ar' => 'سند صرف'],
            'TRANSFER' => ['icon' => 'fa-right-left', 'color' => 'info', 'label' => 'Transfer', 'ar' => 'تحويل'],
        ];
        $tc = $typeConfig[$voucher->voucher_type] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => $voucher->voucher_type, 'ar' => ''];
        $statusBadge = ['DRAFT' => 'warning', 'POSTED' => 'success', 'CANCELLED' => 'danger'];
    @endphp

    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('vouchers.index') }}" class="btn btn-light rounded-3"><i class="fa-solid fa-arrow-left"></i></a>
        <div>
            <h4 class="fw-bold mb-0">
                Voucher #{{ $voucher->id }}
                <span class="badge bg-{{ $tc['color'] }}-subtle text-{{ $tc['color'] }} ms-2 fs-6">
                    <i class="fa-solid {{ $tc['icon'] }} me-1"></i>{{ $tc['label'] }} — {{ $tc['ar'] }}
                </span>
                <span
                    class="badge bg-{{ $statusBadge[$voucher->status] ?? 'secondary' }} ms-1">{{ $voucher->status }}</span>
            </h4>
            <small class="text-muted">Created: {{ $voucher->created_at?->format('d M Y  H:i') }}</small>
        </div>
        <div class="ms-auto d-flex gap-2">
            @if($voucher->isDraft())
                <button id="postBtn" onclick="postVoucher({{ $voucher->id }})" class="btn btn-success rounded-3 px-4">
                    <i class="fa-solid fa-stamp me-2"></i>Post Voucher (اعتماد)
                </button>
                <form action="{{ route('vouchers.cancel', $voucher->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger rounded-3" onclick="return confirm('Cancel?')">
                        <i class="fa-solid fa-ban me-1"></i>Cancel
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Alert --}}
    <div id="pageAlert" class="alert d-none mb-4"></div>

    <div class="row g-4">
        {{-- Left column: Voucher Details --}}
        <div class="col-lg-7">

            {{-- Account Flow Card --}}
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-arrows-left-right text-primary me-2"></i>Account Flow</h6>

                <div class="d-flex align-items-center gap-3 mb-4">
                    @php
                        [$debitCode, $creditCode] = match ($voucher->voucher_type) {
                            'RECEIPT' => [$voucher->treasury_account_code ?? $voucher->bank_account_code, $voucher->account_code],
                            'PAYMENT' => [$voucher->account_code, $voucher->treasury_account_code ?? $voucher->bank_account_code],
                            'TRANSFER' => [$voucher->recipient_account_code, $voucher->account_code],
                            default => ['?', '?']
                        };
                    @endphp
                    <div class="text-center flex-fill px-3 py-3 rounded-3" style="background: rgba(34,197,94,.1)">
                        <small class="text-success d-block fw-bold mb-1">DEBIT (مدين)</small>
                        <code class="fs-5 text-success d-block mb-1">{{ $debitCode ?? '—' }}</code>
                        @if($debitCode && isset($accountNames[$debitCode]))
                            <div class="small fw-semibold text-success opacity-75">
                                {{ $accountNames[$debitCode]->name_ar }} / {{ $accountNames[$debitCode]->name_en }}
                            </div>
                        @endif
                    </div>
                    <div class="text-muted fs-4"><i class="fa-solid fa-arrow-right"></i></div>
                    <div class="text-center flex-fill px-3 py-3 rounded-3" style="background: rgba(239,68,68,.1)">
                        <small class="text-danger d-block fw-bold mb-1">CREDIT (دائن)</small>
                        <code class="fs-5 text-danger d-block mb-1">{{ $creditCode ?? '—' }}</code>
                        @if($creditCode && isset($accountNames[$creditCode]))
                            <div class="small fw-semibold text-danger opacity-75">
                                {{ $accountNames[$creditCode]->name_ar }} / {{ $accountNames[$creditCode]->name_en }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Amount breakdown --}}
                <div class="row g-3">
                    <div class="col-4">
                        <div class="p-3 rounded-3 text-center" style="background:var(--bg-color)">
                            <small class="text-muted d-block">Total</small>
                            <strong class="fs-5">${{ number_format($voucher->amount, 2) }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-3 text-center" style="background:var(--bg-color)">
                            <small class="text-success d-block">Cash</small>
                            <strong>${{ number_format($voucher->cash_amount, 2) }}</strong>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 rounded-3 text-center" style="background:var(--bg-color)">
                            <small class="text-info d-block">Bank</small>
                            <strong>${{ number_format($voucher->bank_amount, 2) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Journal Entries --}}
            <div class="card border-0 shadow-sm p-4">
                <h6 class="fw-bold mb-3">
                    <i class="fa-solid fa-book text-primary me-2"></i>Journal Entries
                    @if($voucher->journalEntries->count())
                        <span class="badge bg-primary-subtle text-primary ms-2">{{ $voucher->journalEntries->count() }}
                            lines</span>
                    @endif
                </h6>
                @if($voucher->journalEntries->count())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Group</th>
                                    <th class="text-success">Debit Account</th>
                                    <th class="text-danger">Credit Account</th>
                                    <th class="text-success text-end">Debit</th>
                                    <th class="text-danger text-end">Credit</th>
                                    <th>Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($voucher->journalEntries as $je)
                                    @php
                                        $isParent = isset($je->payload['is_parent_propagation']) && $je->payload['is_parent_propagation'];
                                    @endphp
                                    <tr class="{{ $isParent ? 'bg-secondary bg-opacity-10 opacity-75' : '' }}">
                                        <td><small class="font-monospace text-truncate d-inline-block" style="max-width:80px"
                                                title="{{ $je->transaction_group_id }}">{{ substr($je->transaction_group_id, 0, 8) }}…</small>
                                        </td>
                                        <td><code class="{{ $isParent ? 'text-secondary' : 'text-success' }}">{{ $je->debit_account_code ?? '—' }}</code></td>
                                        <td><code class="{{ $isParent ? 'text-secondary' : 'text-danger' }}">{{ $je->credit_account_code ?? '—' }}</code></td>
                                        <td class="text-end fw-bold {{ $isParent ? 'text-secondary' : 'text-success' }}">
                                            {{ $je->debit ? number_format($je->debit, 2) : '—' }}</td>
                                        <td class="text-end fw-bold {{ $isParent ? 'text-secondary' : 'text-danger' }}">
                                            {{ $je->credit ? number_format($je->credit, 2) : '—' }}</td>
                                        <td><span
                                                class="badge {{ $isParent ? 'bg-secondary-subtle text-secondary' : 'bg-primary-subtle text-primary' }}">{{ $isParent ? 'Parent' : 'Primary' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="3" class="text-end">Total</td>
                                    <td class="text-end text-success">
                                        {{ number_format($voucher->journalEntries->sum('debit'), 2) }}</td>
                                    <td class="text-end text-danger">
                                        {{ number_format($voucher->journalEntries->sum('credit'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fa-solid fa-clock fa-2x text-muted mb-3 d-block opacity-25"></i>
                        <p class="text-muted">No journal entries yet. Post the voucher to generate them.</p>
                        @if($voucher->isDraft())
                            <button onclick="postVoucher({{ $voucher->id }})" class="btn btn-success rounded-3 px-4">
                                <i class="fa-solid fa-stamp me-2"></i>Post Now
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Right column: Meta --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h6 class="fw-bold mb-3"><i class="fa-solid fa-info-circle text-primary me-2"></i>Voucher Info</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted small fw-bold">ID</td>
                        <td>#{{ $voucher->id }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-bold">Type</td>
                        <td><span class="badge bg-{{ $tc['color'] }}">{{ $tc['label'] }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-bold">Status</td>
                        <td><span
                                class="badge bg-{{ $statusBadge[$voucher->status] ?? 'secondary' }}">{{ $voucher->status }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-bold">Account</td>
                        <td>
                            <code>{{ $voucher->account_code }}</code>
                            @if(isset($accountNames[$voucher->account_code]))
                                <div class="small text-muted">{{ $accountNames[$voucher->account_code]->name_ar }} / {{ $accountNames[$voucher->account_code]->name_en }}</div>
                            @endif
                        </td>
                    </tr>
                    @if($voucher->recipient_account_code)
                        <tr>
                            <td class="text-muted small fw-bold">Recipient</td>
                            <td>
                                <code>{{ $voucher->recipient_account_code }}</code>
                                @if(isset($accountNames[$voucher->recipient_account_code]))
                                    <div class="small text-muted">{{ $accountNames[$voucher->recipient_account_code]->name_ar }} / {{ $accountNames[$voucher->recipient_account_code]->name_en }}</div>
                                @endif
                            </td>
                    </tr>@endif
                    @if($voucher->treasury_account_code)
                        <tr>
                            <td class="text-muted small fw-bold">Treasury</td>
                            <td>
                                <code>{{ $voucher->treasury_account_code }}</code>
                                @if(isset($accountNames[$voucher->treasury_account_code]))
                                    <div class="small text-muted">{{ $accountNames[$voucher->treasury_account_code]->name_ar }} / {{ $accountNames[$voucher->treasury_account_code]->name_en }}</div>
                                @endif
                            </td>
                    </tr>@endif
                    @if($voucher->bank_account_code)
                        <tr>
                            <td class="text-muted small fw-bold">Bank</td>
                            <td>
                                <code>{{ $voucher->bank_account_code }}</code>
                                @if(isset($accountNames[$voucher->bank_account_code]))
                                    <div class="small text-muted">{{ $accountNames[$voucher->bank_account_code]->name_ar }} / {{ $accountNames[$voucher->bank_account_code]->name_en }}</div>
                                @endif
                            </td>
                    </tr>@endif
                    <tr>
                        <td class="text-muted small fw-bold">Expense Type</td>
                        <td>{{ $voucher->expense_type }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-bold">Ref. #</td>
                        <td>{{ $voucher->reference_number ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-bold">Entity</td>
                        <td>{{ $voucher->entity_type ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small fw-bold">Created By</td>
                        <td>{{ $voucher->createdBy?->name ?? '—' }}</td>
                    </tr>
                    @if($voucher->isPosted())
                        <tr>
                            <td class="text-muted small fw-bold">Posted By</td>
                            <td>{{ $voucher->postedBy?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small fw-bold">Posted At</td>
                            <td>{{ $voucher->posted_at?->format('d M Y H:i') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="text-muted small fw-bold">Settlement</td>
                        <td>{{ $voucher->is_sttel ? '✅ Yes' : '❌ No' }}</td>
                    </tr>
                    @if($voucher->note)
                        <tr>
                            <td class="text-muted small fw-bold">Note</td>
                            <td class="small">{{ $voucher->note }}</td>
                        </tr>
                    @endif
                </table>
            </div>

            {{-- Quick Account Statement link --}}
            <div class="card border-0 shadow-sm p-4 mb-4" style="background: var(--card-bg); border: 1px solid rgba(0,0,0,0.05) !important;">
                <h6 class="fw-bold mb-2"><i class="fa-solid fa-chart-line text-info me-2"></i>View Account Statement</h6>
                <p class="small text-muted mb-3">View full journal history for the account in this voucher.</p>
                @php
                    $mainAcc = \App\Models\Account::where('code', $voucher->account_code)->first();
                @endphp
                @if($mainAcc)
                    <a href="{{ route('accounting.statement', $mainAcc->id) }}"
                        class="btn btn-info rounded-3 btn-sm text-white w-100 py-2">
                        <i class="fa-solid fa-book-open me-2"></i>Statement for <code
                            class="text-white bg-white bg-opacity-25 px-2 rounded">{{ $voucher->account_code }}</code>
                    </a>
                @else
                    <button disabled class="btn btn-secondary btn-sm w-100 rounded-3">Account Not Found</button>
                @endif
            </div>
        </div>
    </div>

    {{-- Modern Confirmation Modal --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 pb-0 justify-content-center pt-4">
                    <div class="bg-success-subtle rounded-circle p-3 mb-2">
                        <i class="fa-solid fa-stamp fa-2x text-success"></i>
                    </div>
                </div>
                <div class="modal-body text-center px-4 pt-2 pb-4">
                    <h5 class="fw-bold mb-2">Confirm Posting</h5>
                    <p class="text-muted small">This will finalize the voucher, create journal entries, and update account balances. This action is irreversible.</p>
                    <div class="d-flex gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill flex-fill py-2 fw-bold" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmPostBtn" class="btn btn-success rounded-pill flex-fill py-2 fw-bold shadow-sm">Post Now</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let modalInstance = null;

        async function postVoucher(id) {
            if (typeof bootstrap === 'undefined') {
                console.error('Bootstrap is not loaded!');
                alert('System Error: Bootstrap library not found. Please refresh the page.');
                return;
            }
            const modalEl = document.getElementById('confirmModal');
            if(!modalInstance) {
                modalInstance = new bootstrap.Modal(modalEl);
            }
            
            document.getElementById('confirmPostBtn').onclick = async () => {
                modalInstance.hide();
                const btn = document.getElementById('postBtn');
                if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Posting…'; }

                try {
                    const res = await fetch(`/vouchers/${id}/post`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();

                    const alert = document.getElementById('pageAlert');
                    alert.className = `alert alert-${data.success ? 'success' : 'danger'} shadow-sm rounded-3 border-0 py-3`;
                    alert.innerHTML = `<i class="fa-solid ${data.success ? 'fa-circle-check' : 'fa-triangle-exclamation'} me-2"></i><strong>${data.message}</strong>`;
                    alert.classList.remove('d-none');

                    if (data.success) setTimeout(() => location.reload(), 1200);
                } catch (e) {
                    console.error(e);
                }
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-stamp me-2"></i>Post Voucher (اعتماد)'; }
            };

            modalInstance.show();
        }
    </script>
@endsection