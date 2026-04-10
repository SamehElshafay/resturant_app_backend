@extends('layouts.app')
@section('title', __('messages.accounting_reports'))

@section('content')
    <style>
        .cursor-pointer { cursor: pointer; transition: background 0.2s; }
        .cursor-pointer:hover { background-color: rgba(0,0,0,0.02) !important; }
    </style>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ __('messages.accounting_reports') }}</h4>
            <p class="text-muted small mb-0">{{ __('messages.reports_subtitle') }}</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-3" onclick="switchTab('statement')"><i
                    class="fa-solid fa-book-open me-2"></i>{{ __('messages.account_statement') }}</button>
        </div>
    </div>

    {{-- ── TAB 1: TRIAL BALANCE ─────────────────────────────────────────────────────────── --}}
    {{-- Trial Balance Hidden as requested --}}
    <div id="tabTrial" class="d-none"></div>

    {{-- ── TAB 2: ACCOUNT STATEMENT ─────────────────────────────────────────────────────── --}}
    <div id="tabStatement">
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h6 class="fw-bold mb-3"><i
                    class="fa-solid fa-book-open text-info me-2"></i>{{ __('messages.account_statement') }}</h6>
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">{{ __('messages.account_code') }} <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" id="asSearch" class="form-control rounded-end-3"
                            placeholder="{{ __('messages.search_placeholder') }}">
                    </div>
                    <div id="asDropdown" class="border rounded-3 shadow-sm mt-1 d-none"
                        style="max-height:200px;overflow-y:auto;background:var(--card-bg);position:absolute;z-index:999;min-width:300px">
                        <div id="asList"></div>
                    </div>
                    <input type="hidden" id="asCode">
                    <div id="asSelected" class="mt-2 d-none">
                        <span class="badge bg-info-subtle text-info p-2 rounded-3">
                            <i class="fa-solid fa-check me-1"></i><span id="asSelectedLabel"></span>
                            <button type="button" class="btn-close btn-close-sm ms-2" onclick="clearAsCode()"></button>
                        </span>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">{{ __('messages.reference_id_label') }}</label>
                    <input type="number" id="asRefId" class="form-control rounded-3" placeholder="{{ __('messages.reference') }} / ID">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">{{ __('messages.reference_type_label') }}</label>
                    <select id="asRefType" class="form-select rounded-3">
                        <option value="">{{ __('messages.any') }}</option>
                        <option value="voucher">{{ __('messages.vouchers') }}</option>
                        <option value="order">{{ __('messages.order') }}</option>
                        <option value="expense">{{ __('messages.expense') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button onclick="loadAccountStatement(1)" class="btn btn-info w-100 rounded-3 text-white">
                        <i class="fa-solid fa-book me-2"></i>{{ __('messages.load_statement') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Account Summary --}}
        <div id="asSummaryCard" class="d-none mb-4">
            <div class="card border-0 shadow-sm p-4"
                style="background: linear-gradient(135deg,#0ea5e9,#6366f1);color:white">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center border-end border-white border-opacity-25">
                        <small class="opacity-75 d-block">{{ __('messages.account') }}</small>
                        <strong class="fs-5" id="asAccName">—</strong>
                        <code class="d-block text-white opacity-75 small" id="asAccCode">—</code>
                    </div>
                    <div class="col-md-2 text-center border-end border-white border-opacity-25">
                        <small class="opacity-75 d-block">{{ __('messages.type') }}</small>
                        <strong id="asAccType">—</strong>
                    </div>
                    <div class="col-md-2 text-center border-end border-white border-opacity-25">
                        <small class="opacity-75 d-block">{{ __('messages.total_debit') }}</small>
                        <strong class="text-white" id="asDebit">—</strong>
                    </div>
                    <div class="col-md-2 text-center border-end border-white border-opacity-25">
                        <small class="opacity-75 d-block">{{ __('messages.total_credit') }}</small>
                        <strong class="text-white" id="asCredit">—</strong>
                    </div>
                    <div class="col-md-3 text-center">
                        <small class="opacity-75 d-block">{{ __('messages.net_balance') }}</small>
                        <strong class="fs-4" id="asNet">—</strong>
                        <span class="badge bg-white bg-opacity-25 d-block mt-1" id="asStatus">—</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- History Table --}}
        <div class="card border-0 shadow-sm overflow-hidden">
            <div id="asLoading" class="text-center py-5 d-none">
                <div class="spinner-border text-info mb-3"></div>
                <p class="text-muted">{{ __('messages.loading_statement_msg') }}</p>
            </div>
            <div id="asEmpty" class="text-center py-5">
                <i class="fa-solid fa-book fa-3x text-muted opacity-25 mb-3 d-block"></i>
                <p class="text-muted">{{ __('messages.load_statement_msg') }}</p>
            </div>
            <div id="asTableWrap" class="d-none">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>{{ __('messages.description') }}</th>
                                <th>{{ __('messages.debit_account') }}</th>
                                <th>{{ __('messages.credit_account') }}</th>
                                <th>{{ __('messages.reference') }}</th>
                                <th class="text-end text-success">{{ __('messages.debit') }}</th>
                                <th class="text-end text-danger">{{ __('messages.credit') }}</th>
                                <th>{{ __('messages.date') }}</th>
                            </tr>
                        </thead>
                        <tbody id="asBody"></tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center p-3">
                    <small class="text-muted" id="asPagMeta"></small>
                    <div id="asPagBtns" class="d-flex gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ── Tab Switching ─────────────────────────────────────────────────────────────
        function switchTab(tab) {
            document.getElementById('tabTrial').classList.toggle('d-none', tab !== 'trial');
            document.getElementById('tabStatement').classList.toggle('d-none', tab !== 'statement');
        }

        // ── Trial Balance ─────────────────────────────────────────────────────────────
        async function loadTrialBalance(page = 1) {
            const type = document.getElementById('tbType').value;
            const name = document.getElementById('tbName').value;
            const dateTo = document.getElementById('tbDateTo').value;

            document.getElementById('tbLoading').classList.remove('d-none');
            document.getElementById('tbTableWrap').classList.add('d-none');
            document.getElementById('tbEmpty').classList.add('d-none');
            document.getElementById('tbSummaryRow').style.display = 'none';

            const params = new URLSearchParams({ report_type: type, name, date_to: dateTo, page, per_page: 20 });
            const res = await fetch(`/accounting/reports/trial-balance?${params}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();

            document.getElementById('tbLoading').classList.add('d-none');

            if (!data.data?.length) {
                document.getElementById('tbEmpty').classList.remove('d-none');
                return;
            }

            // Summary tiles
            document.getElementById('tbTotalDebit').textContent = `$${data.summary.grand_total_debit.toFixed(2)}`;
            document.getElementById('tbTotalCredit').textContent = `$${data.summary.grand_total_credit.toFixed(2)}`;
            document.getElementById('tbTotalNet').textContent = `$${Math.abs(data.summary.grand_total_net).toFixed(2)}`;
            document.getElementById('tbSummaryRow').style.removeProperty('display');

            // Table rows
            const body = document.getElementById('tbBody');
            body.innerHTML = data.data.map(row => `
                        <tr>
                            <td class="ps-4"><code class="text-primary">${row.account_code}</code></td>
                            <td class="fw-semibold">${row.name}</td>
                            <td><span class="badge bg-secondary-subtle text-secondary">${row.entity_type}</span></td>
                            <td class="text-end text-success fw-bold">$${row.debit.toFixed(2)}</td>
                            <td class="text-end text-danger fw-bold">$${row.credit.toFixed(2)}</td>
                            <td class="text-end fw-bold">$${row.abs_balance.toFixed(2)}</td>
                            <td class="text-center">
                                <span class="badge ${row.balance_side === 'DEBIT' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                                    ${row.balance_side}
                                </span>
                            </td>
                            <td class="pe-4 text-center">
                                <button class="btn btn-sm btn-light rounded-3" onclick="viewStatement('${row.account_code}')">
                                    <i class="fa-solid fa-book-open"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');

            // Pagination
            buildPagination('tbPagBtns', 'tbPagMeta', data.meta, loadTrialBalance);
            document.getElementById('tbTableWrap').classList.remove('d-none');
        }

        // ── Account Statement ─────────────────────────────────────────────────────────
        let asSearchTimeout;
        document.getElementById('asSearch').addEventListener('input', function () {
            clearTimeout(asSearchTimeout);
            const q = this.value.trim();
            if (!q) { document.getElementById('asDropdown').classList.add('d-none'); return; }
            asSearchTimeout = setTimeout(() => fetchAsAccounts(q), 300);
        });
        document.addEventListener('click', e => {
            if (!document.getElementById('asSearch').contains(e.target)) document.getElementById('asDropdown').classList.add('d-none');
        });

        async function fetchAsAccounts(q) {
            const res = await fetch(`/api/accounts/tree?search=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            const list = document.getElementById('asList');
            list.innerHTML = '';
            if (!data.length) { list.innerHTML = '<div class="text-muted small p-3">No accounts found</div>'; }
            else data.forEach(a => {
                const btn = document.createElement('button');
                btn.type = 'button'; btn.className = 'list-group-item list-group-item-action py-2 px-3';
                btn.innerHTML = `<code class="text-primary me-2">${a.code}</code><small>${a.name_ar || a.name_en || ''}</small>`;
                btn.addEventListener('click', () => {
                    document.getElementById('asCode').value = a.code;
                    document.getElementById('asSelectedLabel').textContent = `${a.code} — ${a.name_ar || a.name_en || ''}`;
                    document.getElementById('asSelected').classList.remove('d-none');
                    document.getElementById('asSearch').value = '';
                    document.getElementById('asDropdown').classList.add('d-none');
                });
                list.appendChild(btn);
            });
            document.getElementById('asDropdown').classList.remove('d-none');
        }

        function clearAsCode() {
            document.getElementById('asCode').value = '';
            document.getElementById('asSelected').classList.add('d-none');
        }

        function viewStatement(code) {
            switchTab('statement');
            document.getElementById('asCode').value = code;
            document.getElementById('asSelectedLabel').textContent = code;
            document.getElementById('asSelected').classList.remove('d-none');
            loadAccountStatement(1);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        async function loadAccountStatement(page = 1) {
            const code = document.getElementById('asCode').value;
            const refId = document.getElementById('asRefId').value;
            const refType = document.getElementById('asRefType').value;

            if (!code) { alert('Please select an account code first.'); return; }

            document.getElementById('asLoading').classList.remove('d-none');
            document.getElementById('asTableWrap').classList.add('d-none');
            document.getElementById('asEmpty').classList.add('d-none');
            document.getElementById('asSummaryCard').classList.add('d-none');

            const params = new URLSearchParams({ account_code: code, page, per_page: 20 });
            if (refId) params.append('reference_id', refId);
            if (refType) params.append('reference_type', refType);

            const res = await fetch(`/accounting/reports/account-balance?${params}`, { headers: { Accept: 'application/json' } });
            const data = await res.json();

            document.getElementById('asLoading').classList.add('d-none');

            if (!data.success) { document.getElementById('asEmpty').classList.remove('d-none'); return; }

            const s = data.summary;
            document.getElementById('asAccName').textContent = s.account_name || code;
            document.getElementById('asAccCode').textContent = code;
            document.getElementById('asAccType').textContent = s.account_type;
            document.getElementById('asDebit').textContent = `$${s.debit.toFixed(2)}`;
            document.getElementById('asCredit').textContent = `$${s.credit.toFixed(2)}`;
            document.getElementById('asNet').textContent = `$${s.total.toFixed(2)}`;
            document.getElementById('asStatus').textContent = s.status.name;
            document.getElementById('asSummaryCard').classList.remove('d-none');

            if (!data.data?.length) { document.getElementById('asEmpty').classList.remove('d-none'); return; }

            const body = document.getElementById('asBody');
            body.innerHTML = data.data.map(e => {
                const url = getReferenceUrl(e.reference_type, e.reference_id, e.payload);
                const clickableClass = url ? 'cursor-pointer clickable-row' : '';
                const onclickAttr = url ? `onclick="window.location.href='${url}'"` : '';
                
                let refLabel = '—';
                if (e.reference_type && e.reference_id) {
                    refLabel = `<span class="badge bg-secondary-subtle text-secondary">${e.reference_type} #${e.reference_id}</span>`;
                } else if (e.payload) {
                    try {
                        const p = typeof e.payload === 'string' ? JSON.parse(e.payload) : e.payload;
                        if (p.id) refLabel = `<span class="badge bg-info-subtle text-info">Linked ID: #${p.id}</span>`;
                    } catch(err) {}
                }

                return `
                        <tr class="${clickableClass}" ${onclickAttr}>
                            <td class="ps-4 text-muted small">#${e.id}</td>
                            <td class="small" style="max-width:200px">${e.description || '—'}</td>
                            <td class="small"><code class="text-success">${e.debit_account_code}</code><br><span class="text-muted small">${e.debit_account_name}</span></td>
                            <td class="small"><code class="text-danger">${e.credit_account_code}</code><br><span class="text-muted small">${e.credit_account_name}</span></td>
                            <td>${refLabel}</td>
                            <td class="text-end text-success fw-bold">${parseFloat(e.debit) > 0 ? '$' + parseFloat(e.debit).toFixed(2) : '—'}</td>
                            <td class="text-end text-danger fw-bold">${parseFloat(e.credit) > 0 ? '$' + parseFloat(e.credit).toFixed(2) : '—'}</td>
                            <td class="text-muted small">${new Date(e.created_at).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' })}</td>
                        </tr>
                    `;
            }).join('');

            buildPagination('asPagBtns', 'asPagMeta', data.meta, loadAccountStatement);
            document.getElementById('asTableWrap').classList.remove('d-none');
        }

        function getReferenceUrl(type, id, payload = null) {
            let p = payload;
            if (p && typeof p === 'string') {
                try { p = JSON.parse(p); } catch(e) {}
            }

            // 1. Check payload first for explicit IDs
            if (p && p.id) {
                if (p.expense_type || (p.reference && p.reference.toString().startsWith('EXPENSE-'))) {
                    return `/expenses/${p.id}`;
                }
            }

            // 2. Standard types
            if (!type || !id) return null;
            type = type.toLowerCase().trim();
            if (type === 'expense') return `/expenses/${id}`;
            if (type === 'purchase_invoice') return `/purchase_invoices/${id}`;
            if (type === 'voucher') return `/vouchers/${id}`;
            if (type === 'order') return `/orders/${id}`;
            if (type === 'production') return `/productions`;
            if (type === 'production_transfer') return `/productions`;
            return null;
        }

        // ── Shared Pagination Builder ─────────────────────────────────────────────────
        function buildPagination(btnContainerId, metaId, meta, loadFn) {
            document.getElementById(metaId).textContent =
                `Showing page ${meta.current_page} of ${meta.last_page} (${meta.total} records)`;

            const container = document.getElementById(btnContainerId);
            container.innerHTML = '';

            if (meta.last_page <= 1) return;

            const addBtn = (label, page, disabled) => {
                const btn = document.createElement('button');
                btn.className = `btn btn-sm btn-light rounded-3 ${disabled ? 'disabled' : ''}`;
                btn.innerHTML = label;
                if (!disabled) btn.onclick = () => loadFn(page);
                container.appendChild(btn);
            };

            addBtn('<i class="fa-solid fa-angle-left"></i>', meta.current_page - 1, meta.current_page === 1);
            for (let p = Math.max(1, meta.current_page - 2); p <= Math.min(meta.last_page, meta.current_page + 2); p++) {
                const btn = document.createElement('button');
                btn.className = `btn btn-sm rounded-3 ${p === meta.current_page ? 'btn-primary' : 'btn-light'}`;
                btn.textContent = p;
                btn.onclick = () => loadFn(p);
                container.appendChild(btn);
            }
            addBtn('<i class="fa-solid fa-angle-right"></i>', meta.current_page + 1, meta.current_page === meta.last_page);
        }
    </script>
@endsection