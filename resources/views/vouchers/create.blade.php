@extends('layouts.app')
@section('title', __('messages.create_new_voucher'))

@section('content')
    <div class="mb-4">
        <a href="{{ route('vouchers.index') }}" class="text-muted text-decoration-none small">
            <i class="fa-solid fa-arrow-left me-1"></i>{{ __('messages.back_to_vouchers') }}
        </a>
        <h4 class="fw-bold mt-2 mb-0">{{ __('messages.create_new_voucher') }}</h4>
    </div>

    <form id="voucherForm" action="{{ route('vouchers.store') }}" method="POST">
        @csrf
        <div class="row g-4">

            {{-- Left Column --}}
            <div class="col-lg-8">
                {{-- Voucher Type Card --}}
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h6 class="fw-bold mb-3"><i
                            class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>{{ __('messages.voucher_type_label') }}
                    </h6>
                    <div class="row g-3">
                        @foreach(['RECEIPT' => ['📥', __('messages.receipts'), 'success'], 'PAYMENT' => ['📤', __('messages.payments'), 'danger'], 'TRANSFER' => ['🔄', __('messages.transfers'), 'info']] as $val => [$ico, $label, $clr])
                            <div class="col-4">
                                <input type="radio" name="voucher_type" id="type_{{ $val }}" value="{{ $val }}"
                                    class="btn-check" {{ $val == 'RECEIPT' ? 'checked' : '' }} onchange="updateSummary()">
                                <label for="type_{{ $val }}" class="btn btn-outline-{{ $clr }} w-100 py-3 rounded-3">
                                    <span class="fs-4 d-block">{{ $ico }}</span>
                                    <strong>{{ $label }}</strong>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Accounts Card --}}
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h6 class="fw-bold mb-3"><i
                            class="fa-solid fa-sitemap text-primary me-2"></i>{{ __('messages.account_details') }}</h6>

                    <div class="mb-4">
                        <label class="form-label fw-bold small">
                            {{ __('messages.main_account_code') }}
                            <span class="badge bg-secondary-subtle text-secondary ms-1" id="mainAccountRole"></span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i
                                    class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="accountSearch" class="form-control rounded-end-3"
                                placeholder="{{ __('messages.search_placeholder') }}">
                        </div>
                        <div id="accountDropdown" class="border rounded-3 mt-1 shadow-sm d-none"
                            style="max-height:220px;overflow-y:auto;background:var(--card-bg);z-index: 1000;position: absolute;width: 90%;">
                            <div id="accountList" class="list-group list-group-flush"></div>
                        </div>
                        <input type="hidden" name="account_code" id="account_code" required>
                        <div id="selectedAccount" class="mt-2 d-none">
                            <span class="badge bg-primary-subtle text-primary p-2 rounded-3">
                                <i class="fa-solid fa-circle-check me-1"></i>
                                <span id="selectedAccountLabel"></span>
                                <button type="button" class="btn-close btn-close-sm ms-2" onclick="clearAccount()"></button>
                            </span>
                        </div>
                    </div>

                    <div id="recipientBlock" class="d-none mb-4">
                        <label class="form-label fw-bold small">{{ __('messages.recipient_account_code_label') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i
                                    class="fa-solid fa-magnifying-glass text-muted"></i></span>
                            <input type="text" id="recipientSearch" class="form-control rounded-end-3"
                                placeholder="{{ __('messages.search_recipient_placeholder') }}">
                        </div>
                        <div id="recipientDropdown" class="border rounded-3 mt-1 shadow-sm d-none"
                            style="max-height:180px;overflow-y:auto;background:var(--card-bg);z-index: 1000;position: absolute;width: 90%;">
                            <div id="recipientList" class="list-group list-group-flush"></div>
                        </div>
                        <input type="hidden" name="recipient_account_code" id="recipient_account_code">
                        <div id="selectedRecipient" class="mt-2 d-none">
                            <span class="badge bg-info-subtle text-info p-2 rounded-3">
                                <i class="fa-solid fa-circle-check me-1"></i>
                                <span id="selectedRecipientLabel"></span>
                                <button type="button" class="btn-close btn-close-sm ms-2"
                                    onclick="clearRecipient()"></button>
                            </span>
                        </div>
                    </div>

                    {{-- Source Selector --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('messages.payment_source') }}</label>
                        <div class="d-flex gap-2">
                            <input type="radio" name="pay_mode" id="mode_cash" value="CASH" class="btn-check" checked
                                onchange="toggleSources()">
                            <label for="mode_cash" class="btn btn-outline-success flex-fill p-2 rounded-3"><i
                                    class="fa-solid fa-wallet me-1"></i> {{ __('messages.cash_only') }}</label>
                            <input type="radio" name="pay_mode" id="mode_bank" value="BANK" class="btn-check"
                                onchange="toggleSources()">
                            <label for="mode_bank" class="btn btn-outline-info flex-fill p-2 rounded-3"><i
                                    class="fa-solid fa-building-columns me-1"></i> {{ __('messages.bank_only') }}</label>
                            <input type="radio" name="pay_mode" id="mode_both" value="BOTH" class="btn-check"
                                onchange="toggleSources()">
                            <label for="mode_both" class="btn btn-outline-primary flex-fill p-2 rounded-3"><i
                                    class="fa-solid fa-layer-group me-1"></i> {{ __('messages.cash_and_bank') }}</label>
                        </div>
                        <div id="modeNote" class="small text-muted mt-2 d-none"><i class="fa-solid fa-circle-info me-1"></i>
                            {{ __('messages.both_note') }}</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6" id="treasurySource">
                            <label class="form-label fw-bold small">{{ __('messages.treasury_account') }}</label>
                            <select name="treasury_account_code" class="form-select rounded-3">
                                <option value="">{{ __('messages.select_treasury') }}</option>
                                @foreach($accounts as $acc)
                                    @if(str_contains(strtolower($acc->name_ar . $acc->name_en), 'صندوق') || str_contains(strtolower($acc->name_ar . $acc->name_en), 'cash') || str_contains(strtolower($acc->name_ar . $acc->name_en), 'خزينة'))
                                        <option value="{{ $acc->code }}">{{ $acc->code }} — {{ $acc->name_ar ?: $acc->name_en }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6" id="bankSource">
                            <label class="form-label fw-bold small">{{ __('messages.bank_account') }}</label>
                            <select name="bank_account_code" class="form-select rounded-3">
                                <option value="">{{ __('messages.select_bank_account') }}</option>
                                @foreach($accounts as $acc)
                                    @if(str_contains(strtolower($acc->name_ar . $acc->name_en), 'bank') || str_contains(strtolower($acc->name_ar . $acc->name_en), 'بنك'))
                                        <option value="{{ $acc->code }}">{{ $acc->code }} — {{ $acc->name_ar ?: $acc->name_en }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Amounts Card --}}
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h6 class="fw-bold mb-3"><i
                            class="fa-solid fa-coins text-primary me-2"></i>{{ __('messages.amount_details') }}</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">{{ __('messages.total_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="amount" id="totalAmount" class="form-control bg-light fw-bold"
                                    step="0.01" value="0.00" readonly>
                            </div>
                        </div>
                        <div class="col-md-4" id="cashAmountInputWrap">
                            <label class="form-label fw-bold small">{{ __('messages.cash_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-money-bill text-success"></i></span>
                                <input type="number" name="cash_amount" id="cashAmount" class="form-control" step="0.01"
                                    min="0" value="0" oninput="calcTotal()">
                            </div>
                        </div>
                        <div class="col-md-4" id="bankAmountInputWrap">
                            <label class="form-label fw-bold small">{{ __('messages.bank_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fa-solid fa-building-columns text-info"></i></span>
                                <input type="number" name="bank_amount" id="bankAmount" class="form-control" step="0.01"
                                    min="0" value="0" oninput="calcTotal()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-lg-4">
                {{-- Meta Card --}}
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h6 class="fw-bold mb-3"><i
                            class="fa-solid fa-tag text-primary me-2"></i>{{ __('messages.additional_info') }}</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('messages.expense_type') }}</label>
                        <select name="expense_type" class="form-select rounded-3">
                            <option value="NONE">{{ __('messages.expense_none') }}</option>
                            <option value="ADMINISTRATIVE">{{ __('messages.expense_admin') }}</option>
                            <option value="OPERATIONAL">{{ __('messages.expense_operational') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('messages.reference_number') }}</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="INV-2024-001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('messages.note_description') }}</label>
                        <textarea name="note" class="form-control" rows="4"
                            placeholder="{{ __('messages.note_placeholder') }}"></textarea>
                    </div>
                </div>

                {{-- Summary Card --}}
                <div class="card border-0 shadow-sm p-4 mb-4"
                    style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color:white;">
                    <h6 class="fw-bold mb-3 text-white"><i
                            class="fa-solid fa-receipt me-2"></i>{{ __('messages.voucher_summary') }}</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="opacity-75 small">{{ __('messages.type') }}:</span>
                        <strong id="summaryType">—</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="opacity-75 small">{{ __('messages.account') }}:</span>
                        <strong id="summaryAccount" class="small text-truncate ms-2">—</strong>
                    </div>
                    <hr class="border-white opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="opacity-75">{{ __('messages.payable_total') }}:</span>
                        <strong class="fs-4" id="summaryTotal">$0.00</strong>
                    </div>
                </div>

                {{-- Alert Box --}}
                <div id="formAlert" class="alert d-none"></div>

                {{-- Submit --}}
                <div class="d-grid gap-2">
                    <button type="submit" id="submitBtn" class="btn btn-primary btn-lg rounded-3 py-3 shadow-sm">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="submitSpinner"></span>
                        <i class="fa-solid fa-floppy-disk me-2"></i>{{ __('messages.create_voucher_btn') }}
                    </button>
                    <a href="{{ route('vouchers.index') }}" class="btn btn-light rounded-3">{{ __('messages.cancel') }}</a>
                </div>
            </div>

        </div>
    </form>

    <script>
        // ── Search & Dropdown ────────────────────────────────────────────────────────
        let searchTimeout = null;

        function setupSearch(inputId, dropdownId, listId, hiddenId, selectedId, labelId) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(dropdownId);
            const list = document.getElementById(listId);

            input.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                const q = this.value.trim();
                if (!q) { dropdown.classList.add('d-none'); return; }

                searchTimeout = setTimeout(async () => {
                    const res = await fetch(`/api/accounts/tree?search=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    list.innerHTML = '';
                    if (!data.length) {
                        list.innerHTML = '<div class="text-muted small p-3">No matching accounts or users found</div>';
                    } else {
                        data.forEach(a => {
                            const item = document.createElement('button');
                            item.type = 'button';
                            item.className = 'list-group-item list-group-item-action py-2 px-3';
                            const name = a.name_ar || a.name_en || a.name || 'No Name';
                            item.innerHTML = `<code class="text-primary me-2">${a.code}</code><small class="fw-semibold">${name}</small>`;
                            item.addEventListener('click', () => {
                                document.getElementById(hiddenId).value = a.code;
                                document.getElementById(labelId).textContent = `${a.code} — ${name}`;
                                document.getElementById(selectedId).classList.remove('d-none');
                                input.value = '';
                                dropdown.classList.add('d-none');
                                updateSummary();
                            });
                            list.appendChild(item);
                        });
                    }
                    dropdown.classList.remove('d-none');
                }, 300);
            });

            document.addEventListener('click', (e) => {
                if (!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.add('d-none');
            });
        }

        setupSearch('accountSearch', 'accountDropdown', 'accountList', 'account_code', 'selectedAccount', 'selectedAccountLabel');
        setupSearch('recipientSearch', 'recipientDropdown', 'recipientList', 'recipient_account_code', 'selectedRecipient', 'selectedRecipientLabel');

        function clearAccount() { document.getElementById('account_code').value = ''; document.getElementById('selectedAccount').classList.add('d-none'); updateSummary(); }
        function clearRecipient() { document.getElementById('recipient_account_code').value = ''; document.getElementById('selectedRecipient').classList.add('d-none'); updateSummary(); }

        // ── UI Interactions ──────────────────────────────────────────────────────────
        function toggleSources() {
            const mode = document.querySelector('[name="pay_mode"]:checked').value;
            const treasuryWrap = document.getElementById('treasurySource');
            const bankWrap = document.getElementById('bankSource');
            const cashInput = document.getElementById('cashAmount');
            const bankInput = document.getElementById('bankAmount');
            const modeNote = document.getElementById('modeNote');

            if (mode === 'CASH') {
                treasuryWrap.classList.remove('d-none');
                bankWrap.classList.add('d-none');
                cashInput.readOnly = false;
                bankInput.readOnly = true;
                bankInput.value = 0;
                modeNote.classList.add('d-none');
            } else if (mode === 'BANK') {
                treasuryWrap.classList.add('d-none');
                bankWrap.classList.remove('d-none');
                cashInput.readOnly = true;
                bankInput.readOnly = false;
                cashInput.value = 0;
                modeNote.classList.add('d-none');
            } else {
                treasuryWrap.classList.remove('d-none');
                bankWrap.classList.remove('d-none');
                cashInput.readOnly = false;
                bankInput.readOnly = false;
                modeNote.classList.remove('d-none');
            }
            calcTotal();
        }

        function calcTotal() {
            const cash = parseFloat(document.getElementById('cashAmount').value) || 0;
            const bank = parseFloat(document.getElementById('bankAmount').value) || 0;
            const total = cash + bank;
            document.getElementById('totalAmount').value = total.toFixed(2);
            document.getElementById('summaryTotal').textContent = `$${total.toFixed(2)}`;
        }

        function updateSummary() {
            const type = document.querySelector('[name="voucher_type"]:checked')?.value || '—';
            const acc = document.getElementById('account_code').value || '—';
            const roles = {
                RECEIPT: '{{ __("messages.role_paying_party") }}',
                PAYMENT: '{{ __("messages.role_recipient_party") }}',
                TRANSFER: '{{ __("messages.role_source_account") }}'
            };

            document.getElementById('summaryType').textContent = type;
            document.getElementById('summaryAccount').textContent = acc;
            document.getElementById('mainAccountRole').textContent = roles[type] || '{{ __("messages.main_account_code") }}';
            document.getElementById('recipientBlock').classList.toggle('d-none', type !== 'TRANSFER');
        }

        // ── Ajax Submit ─────────────────────────────────────────────────────────────
        document.getElementById('voucherForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const alert = document.getElementById('formAlert');
            const btn = document.getElementById('submitBtn');
            const spinner = document.getElementById('submitSpinner');

            alert.classList.add('d-none');
            btn.disabled = true;
            spinner.classList.remove('d-none');

            try {
                const res = await fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = (data.message || 'Saved successfully') + '. Redirecting...';
                    alert.classList.remove('d-none');
                    setTimeout(() => window.location.href = data.id ? `/vouchers/${data.id}` : '/vouchers', 1500);
                } else {
                    alert.className = 'alert alert-danger';
                    alert.innerHTML = data.errors ? Object.values(data.errors).flat().join('<br>') : (data.message || 'Validation error');
                    alert.classList.remove('d-none');
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                }
            } catch (err) {
                alert.className = 'alert alert-danger';
                alert.textContent = 'Server Error: ' + err.message;
                alert.classList.remove('d-none');
                btn.disabled = false;
                spinner.classList.add('d-none');
            }
        });

        // Init
        toggleSources();
        updateSummary();
    </script>
@endsection