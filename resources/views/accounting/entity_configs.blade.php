@extends('layouts.app')
@section('title', __('messages.entity_accounting_configs'))

@section('content')
    <!-- ─── TOP SECTION: GLOBAL SETTINGS ─── -->
    <div class="row g-4 mb-4">
        <!-- 1. Merchant Bill Config -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm overflow-hidden h-100" 
                 style="background: linear-gradient(135deg, #4f46e5, #6366f1); color: white;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-receipt me-2"></i> {{ __('messages.merchant_bill_debit') }}</h5>
                    <p class="small mb-3 opacity-75">
                        {{ app()->getLocale() == 'ar' ? 'حدد الحساب المدين الافتراضي عند اعتماد فواتير الموردين.' : 'Default debit account for supplier invoices.' }}
                    </p>
                    
                    <form action="{{ route('accounting.entity-configs.store') }}" method="POST" class="d-flex gap-2">
                        @csrf
                        <input type="hidden" name="entity_type" value="MERCHANT_BILL_DEBIT">
                        <select name="parent_account_code" class="form-select border-0 shadow-sm rounded-pill py-2 text-dark" required>
                            <option value="">{{ __('messages.select_account') }}</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->code }}" {{ ($merchantBillConfig->parent_account_code ?? '') == $acc->code ? 'selected' : '' }}>
                                    {{ $acc->code }} — {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-white rounded-pill px-3 fw-bold">
                            <i class="fa-solid fa-save"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Production Config -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm overflow-hidden h-100" 
                 style="background: linear-gradient(135deg, #059669, #10b981); color: white;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-industry me-2"></i> {{ __('messages.production_accounting') }}</h5>
                    <p class="small mb-3 opacity-75">
                        {{ app()->getLocale() == 'ar' ? 'توجيه التكاليف والأرباح بين المواد الخام وحساب فرع المنتجات.' : 'Transfer costs and profits between Raw Materials and Branch Product accounts.' }}
                    </p>
                    
                    <div class="row g-2">
                        <!-- Raw Materials -->
                        <div class="col-4">
                            <label class="small opacity-75 mb-1 d-block">{{ __('messages.raw_materials_account') }}</label>
                            <form action="{{ route('accounting.entity-configs.store') }}" method="POST" id="form-raw">
                                @csrf
                                <input type="hidden" name="entity_type" value="PRODUCTION_RAW_MATERIALS">
                                <select name="parent_account_code" class="form-select border-0 shadow-sm rounded-pill py-2 small" required onchange="this.form.submit()">
                                    <option value="">{{ __('messages.select_account') }}</option>
                                    @php $rawCode = $prodRawConfig->parent_account_code ?? ($merchantBillConfig->parent_account_code ?? ''); @endphp
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->code }}" {{ $rawCode == $acc->code ? 'selected' : '' }}>
                                            {{ $acc->code }} — {{ $acc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <!-- Finished Goods Template -->
                        <div class="col-4">
                            <label class="small opacity-75 mb-1 d-block">{{ __('messages.finished_goods_account') }}</label>
                            <form action="{{ route('accounting.entity-configs.store') }}" method="POST" id="form-finished">
                                @csrf
                                <input type="hidden" name="entity_type" value="PRODUCTION_FINISHED_GOODS">
                                <select name="parent_account_code" class="form-select border-0 shadow-sm rounded-pill py-2 small" required onchange="this.form.submit()">
                                    <option value="">{{ __('messages.select_account') }}</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->code }}" {{ ($prodFinishedConfig->parent_account_code ?? '') == $acc->code ? 'selected' : '' }}>
                                            {{ $acc->code }} — {{ $acc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                        <!-- Profit Account -->
                        <div class="col-4">
                            <label class="small opacity-75 mb-1 d-block">{{ __('messages.profit_account') }}</label>
                            <form action="{{ route('accounting.entity-configs.store') }}" method="POST" id="form-profit">
                                @csrf
                                <input type="hidden" name="entity_type" value="PRODUCTION_PROFIT_ACCOUNT">
                                <select name="parent_account_code" class="form-select border-0 shadow-sm rounded-pill py-2 small" required onchange="this.form.submit()">
                                    <option value="">{{ __('messages.select_account') }}</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->code }}" {{ ($prodProfitConfig->parent_account_code ?? '') == $acc->code ? 'selected' : '' }}>
                                            {{ $acc->code }} — {{ $acc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── MAIN CONTENT: MAPPING ─── -->
    <div class="row g-4">
        <!-- LEFT COL: FORMS & LIBRARY -->
        <div class="col-md-4">
            <!-- 1. Add New Mapping -->
            <div class="card border-0 shadow-sm p-4 mb-4">
                <div class="d-flex align-items-center mb-4">
                    <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <h5 class="fw-bold mb-0">{{ __('messages.add_new_config') }}</h5>
                </div>

                <form action="{{ route('accounting.entity-configs.store') }}" method="POST" id="config-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">{{ __('messages.entity_type_role') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-tag text-muted"></i></span>
                            <input type="text" name="entity_type" id="entity_type_input" class="form-control bg-light border-start-0 ps-0" placeholder="e.g. driver, supplier..." required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">{{ __('messages.parent_account_code') }}</label>
                        <select name="parent_account_code" class="form-control select2" required>
                            <option value="">{{ __('messages.select_parent_account') }}</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->code }}">{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2.5 fw-bold shadow-sm">
                        {{ __('messages.save_config') }}
                    </button>
                </form>
            </div>

            <!-- 2. Library -->
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header border-0 py-3">
                    <h6 class="fw-bold mb-0 px-2 text-primary"><i class="fa-solid fa-book-bookmark me-2"></i>Entity Library</h6>
                </div>
                <div class="list-group list-group-flush border-top">
                    @forelse($types as $type)
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 bg-transparent">
                            <div>
                                <span class="fw-semibold text-main">{{ $type->name }}</span>
                                <small class="d-block text-muted">Ready to map</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-indigo-subtle rounded-pill px-3 copy-type-btn" data-type="{{ $type->name }}">
                                <i class="fa-solid fa-plus me-1"></i> Add
                            </button>
                        </div>
                    @empty
                        <div class="p-4 text-center">
                            <i class="fa-solid fa-check-double text-success mb-2 fs-3"></i>
                            <p class="text-muted small mb-0">All entity types have been configured.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RIGHT COL: EXISTING TABLE -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Existing Tag Mappings</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 px-3 rounded-pill">{{ $configs->count() }} Configured</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="text-muted small">
                            <tr>
                                <th class="ps-4 border-0 py-3" style="width: 25%">{{ __('messages.entity_type') }}</th>
                                <th class="border-0 py-3">{{ __('messages.parent_account_code') }}</th>
                                <th class="border-0 py-3">{{ __('messages.parent_name') }}</th>
                                <th class="pe-4 text-end border-0 py-3">{{ __('messages.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($configs as $c)
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs bg-indigo-subtle text-indigo rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                                <i class="fa-solid fa-tag small"></i>
                                            </div>
                                            <span class="fw-bold">{{ $c->entity_type }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace px-2 py-1">
                                            {{ $c->parent_account_code }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-muted">
                                        @php
                                            $parent = $accounts->where('code', $c->parent_account_code)->first();
                                        @endphp
                                        @if($parent)
                                            <span class="d-block">{{ $parent->name }}</span>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="pe-4 text-end py-3">
                                        <form action="{{ route('accounting.entity-configs.destroy', $c->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" 
                                                    style="width: 32px; height: 32px;"
                                                    onclick="return confirm('Delete this mapping?')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="fa-solid fa-folder-open text-muted fs-1 mb-3"></i>
                                            <p class="text-muted">No mappings found. Start by adding one from the left panel.</p>
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

    <style>
        .btn-white { background: white; color: #4f46e5; }
        .btn-white:hover { background: #f8fafc; color: #4338ca; }
        .text-indigo { color: #4f46e5; }
        .bg-indigo-subtle { background-color: rgba(79, 70, 229, 0.1); }
        .btn-indigo-subtle { background-color: rgba(79, 70, 229, 0.1); color: #4f46e5; border: none; }
        .btn-indigo-subtle:hover { background-color: #4f46e5; color: white; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.copy-type-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const type = this.getAttribute('data-type');
                    const input = document.getElementById('entity_type_input');
                    input.value = type;
                    input.focus();
                    
                    // Highlight the input
                    input.style.boxShadow = '0 0 0 0.25rem rgba(79, 70, 229, 0.25)';
                    setTimeout(() => { input.style.boxShadow = ''; }, 1000);
                });
            });
        });
    </script>
@endsection
