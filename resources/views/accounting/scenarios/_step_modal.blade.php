@php
    $modalId = $step ? 'editStepModal' . $step->id : 'addStepModal';
    $action = $step ? route('accounting.scenarios.steps.update', $step->id) : route('accounting.scenarios.steps.store', $scenario->id);
    $method = $step ? 'PUT' : 'POST';
    
    $debitIsTag = $step && str_contains($step->debit_account_pattern, '{');
    $creditIsTag = $step && str_contains($step->credit_account_pattern, '{');

    $commonConditions = [
        '' => 'Always Execute / تنفيذ دائم',
        "{{payment_method}} == 'bank'" => 'Payment is Bank / الدفع بنكي',
        "{{payment_method}} == 'cash'" => 'Payment is Cash / الدفع نقدي',
        "{{status}} == 'paid'" => 'Status is Paid / الحالة مدفوع',
        "{{status}} == 'approved'" => 'Status is Approved / الحالة معتمد',
    ];
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content shadow-lg border-0" style="background-color: var(--card-bg); color: var(--text-main);">
            <form action="{{ $action }}" method="POST" onsubmit="return validateFormulas('{{ $modalId }}')">
                @csrf
                @if($step) @method('PUT') @endif
                
                <div class="modal-header border-bottom py-3" style="border-color: var(--border-color)!important;">
                    <h5 class="modal-title fw-bold" style="color: var(--text-main);">
                        <i class="fa-solid fa-code-branch me-2 text-primary"></i>
                        {{ $step ? 'Edit Accounting Rule / تعديل القاعدة المحاسبية' : 'Add New Accounting Rule / إضافة قاعدة محاسبية جديدة' }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: var(--close-btn-filter);"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase tracking-wider">Step Description / وصف الخطوة</label>
                        <input type="text" name="description" class="form-control form-control-lg border-0 rounded-3 shadow-sm custom-input" 
                               value="{{ $step->description ?? '' }}" placeholder="e.g. Recording VAT output / ضريبة القيمة المضافة" required>
                    </div>

                    <div class="row g-4 mb-4">
                        <!-- Debit Section -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border h-100" style="background-color: rgba(220, 53, 69, 0.05); border-color: rgba(220, 53, 69, 0.2) !important;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label fw-bold text-danger mb-0 small text-uppercase">Debit Account / الحساب المدين (-)</label>
                                    <ul class="nav nav-pills nav-pills-custom" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link {{ !$debitIsTag ? 'active' : '' }}" type="button" 
                                                    onclick="toggleAccountInput('{{ $modalId }}', 'debit', 'manual')">Manual</button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link {{ $debitIsTag ? 'active' : '' }}" type="button" 
                                                    onclick="toggleAccountInput('{{ $modalId }}', 'debit', 'tag')">Tag</button>
                                        </li>
                                    </ul>
                                </div>

                                <div id="{{ $modalId }}-debit-manual" class="{{ $debitIsTag ? 'd-none' : '' }} mb-3">
                                    <select class="form-select select2-modal" data-dropdown-parent="#{{ $modalId }}" style="width: 100%;">
                                        <option value="">Search Account... / ابحث عن حساب...</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->code }}" {{ $step && $step->debit_account_pattern == $acc->code ? 'selected' : '' }}>
                                                {{ $acc->code }} - {{ $acc->name_ar }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="{{ $modalId }}-debit-tag" class="{{ !$debitIsTag ? 'd-none' : '' }} mb-3">
                                    <select class="form-select border-0 shadow-sm custom-input">
                                        <option value="">Select Tag... / اختر متغير...</option>
                                        @foreach($commonTags as $tag => $label)
                                            <option value="{{ $tag }}" {{ $step && $step->debit_account_pattern == $tag ? 'selected' : '' }}>
                                                {{ $tag }} ({{ $label }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <input type="hidden" name="debit_account_pattern" value="{{ $step->debit_account_pattern ?? '' }}" required>
                                
                                <hr style="border-color: rgba(220, 53, 69, 0.2) !important;">
                                
                                <label class="form-label fw-bold small text-uppercase">Debit Amount Formula / معادلة المبلغ المدين</label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text border-0 custom-input-addon"><i class="fa-solid fa-calculator text-danger"></i></span>
                                    <input type="text" name="debit_amount_formula" id="{{ $modalId }}-debit-formula" class="form-control border-0 custom-input" 
                                           value="{{ $step->debit_amount_formula ?? ($step->amount_formula ?? '') }}" 
                                           placeholder="e.g. @{{total_amount}} * 1.15" oninput="handleFormulaInput('{{ $modalId }}', 'debit')">
                                </div>
                            </div>
                        </div>

                        <!-- Credit Section -->
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border h-100" style="background-color: rgba(25, 135, 84, 0.05); border-color: rgba(25, 135, 84, 0.2) !important;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label fw-bold text-success mb-0 small text-uppercase">Credit Account / الحساب الدائن (+)</label>
                                    <ul class="nav nav-pills nav-pills-custom" role="tablist">
                                        <li class="nav-item">
                                            <button class="nav-link {{ !$creditIsTag ? 'active' : '' }}" type="button" 
                                                    onclick="toggleAccountInput('{{ $modalId }}', 'credit', 'manual')">Manual</button>
                                        </li>
                                        <li class="nav-item">
                                            <button class="nav-link {{ $creditIsTag ? 'active' : '' }}" type="button" 
                                                    onclick="toggleAccountInput('{{ $modalId }}', 'credit', 'tag')">Tag</button>
                                        </li>
                                    </ul>
                                </div>

                                <div id="{{ $modalId }}-credit-manual" class="{{ $creditIsTag ? 'd-none' : '' }} mb-3">
                                    <select class="form-select select2-modal" data-dropdown-parent="#{{ $modalId }}" style="width: 100%;">
                                        <option value="">Search Account... / ابحث عن حساب...</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->code }}" {{ $step && $step->credit_account_pattern == $acc->code ? 'selected' : '' }}>
                                                {{ $acc->code }} - {{ $acc->name_ar }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="{{ $modalId }}-credit-tag" class="{{ !$creditIsTag ? 'd-none' : '' }} mb-3">
                                    <select class="form-select border-0 shadow-sm custom-input">
                                        <option value="">Select Tag... / اختر متغير...</option>
                                        @foreach($commonTags as $tag => $label)
                                            <option value="{{ $tag }}" {{ $step && $step->credit_account_pattern == $tag ? 'selected' : '' }}>
                                                {{ $tag }} ({{ $label }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <input type="hidden" name="credit_account_pattern" value="{{ $step->credit_account_pattern ?? '' }}" required>

                                <hr style="border-color: rgba(25, 135, 84, 0.2) !important;">
                                
                                <label class="form-label fw-bold small text-uppercase">Credit Amount Formula / معادلة المبلغ الدائن</label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text border-0 custom-input-addon"><i class="fa-solid fa-calculator text-success"></i></span>
                                    <input type="text" name="credit_amount_formula" id="{{ $modalId }}-credit-formula" class="form-control border-0 custom-input" 
                                           value="{{ $step->credit_amount_formula ?? ($step->amount_formula ?? '') }}" 
                                           placeholder="e.g. @{{total_amount}} * 1.15" oninput="handleFormulaInput('{{ $modalId }}', 'credit')">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-9">
                            <label class="form-label fw-bold small text-uppercase">Condition / شرط التنفيذ</label>
                            <select name="condition_expression" class="form-select border-0 shadow-sm custom-input">
                                @foreach($commonConditions as $val => $label)
                                    <option value="{{ $val }}" {{ ($step->condition_expression ?? '') == $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted mt-1 d-block"><i class="fa-solid fa-info-circle me-1"></i> Choose when this rule applies.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-uppercase">Priority / الأولوية</label>
                            <input type="number" name="priority" class="form-control border-0 shadow-sm custom-input" 
                                   value="{{ $step->priority ?? '10' }}" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top p-4 pt-3" style="border-color: var(--border-color)!important;">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow">
                        {{ $step ? 'Update Rule / تحديث' : 'Create Rule / إنشاء' }}
                    </button>
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel / إلغاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    if (typeof handleFormulaInput === 'undefined') {
        window.handleFormulaInput = function(modalId, source) {
            const debitInput = document.getElementById(`${modalId}-debit-formula`);
            const creditInput = document.getElementById(`${modalId}-credit-formula`);
            
            if (source === 'debit' && debitInput.value.trim() !== '') {
                creditInput.value = '';
                creditInput.disabled = true;
                debitInput.disabled = false;
            } else if (source === 'credit' && creditInput.value.trim() !== '') {
                debitInput.value = '';
                debitInput.disabled = true;
                creditInput.disabled = false;
            } else {
                debitInput.disabled = false;
                creditInput.disabled = false;
            }
        };

        window.validateFormulas = function(modalId) {
            const debitInput = document.getElementById(`${modalId}-debit-formula`);
            const creditInput = document.getElementById(`${modalId}-credit-formula`);
            
            if (debitInput.value.trim() === '' && creditInput.value.trim() === '') {
                window.showToast('You must fill at least one amount formula (Debit or Credit) / يجب إدخال معادلة واحدة على الأقل', 'error');
                return false;
            }
            // Temporarily enable both to ensure they submit correctly
            debitInput.disabled = false;
            creditInput.disabled = false;
            return true;
        };

        window.toggleAccountInput = function (modalId, side, type) {
            const container = document.getElementById(modalId);
            const manualDiv = document.getElementById(`${modalId}-${side}-manual`);
            const tagDiv = document.getElementById(`${modalId}-${side}-tag`);
            const hiddenInput = container.querySelector(`input[name="${side}_account_pattern"]`);

            // Toggle visibility
            if (type === 'manual') {
                manualDiv.classList.remove('d-none');
                tagDiv.classList.add('d-none');
                const select = manualDiv.querySelector('select');
                hiddenInput.value = select.value;
            } else {
                manualDiv.classList.add('d-none');
                tagDiv.classList.remove('d-none');
                const select = tagDiv.querySelector('select');
                hiddenInput.value = select.value;
            }

            // Sync Tab Active States
            const buttons = manualDiv.parentElement.querySelectorAll('.nav-link');
            buttons.forEach(btn => {
                if (btn.innerText.toLowerCase() === type) btn.classList.add('active');
                else btn.classList.remove('active');
            });
        };

        // Initialize Listeners to update hidden inputs
        document.addEventListener('change', function (e) {
            if (e.target.matches('.modal select:not([name="condition_expression"])')) {
                const modal = e.target.closest('.modal');
                if (!modal) return;

                const side = e.target.parentElement.id.includes('debit') ? 'debit' : 'credit';
                const hiddenInput = modal.querySelector(`input[name="${side}_account_pattern"]`);
                if(hiddenInput) hiddenInput.value = e.target.value;
            }
        });

        // Initialize formula states on load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                ['{{ $modalId }}'].forEach(modalId => {
                    const debitInput = document.getElementById(`${modalId}-debit-formula`);
                    const creditInput = document.getElementById(`${modalId}-credit-formula`);
                    if(debitInput && creditInput) {
                        if (debitInput.value.trim() !== '') {
                            handleFormulaInput(modalId, 'debit');
                        } else if (creditInput.value.trim() !== '') {
                            handleFormulaInput(modalId, 'credit');
                        }
                    }
                });
            }, 500);
        });
    }
</script>

<style>
    .tracking-wider { letter-spacing: 0.05em; }
    
    /* Dark Mode Theme Support for Modal */
    html[data-theme="dark"] .custom-input {
        background-color: var(--sidebar-bg);
        color: var(--text-main);
    }
    html[data-theme="light"] .custom-input {
        background-color: #f8fafc;
        color: #1e293b;
    }
    
    html[data-theme="dark"] .custom-input-addon {
        background-color: var(--sidebar-bg);
    }
    html[data-theme="light"] .custom-input-addon {
        background-color: #f8fafc;
    }
    
    html[data-theme="dark"] .nav-pills-custom .nav-link {
        background-color: var(--sidebar-bg);
        color: var(--text-secondary);
    }
    
    html[data-theme="dark"] .close, 
    html[data-theme="dark"] .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    .select2-container--default .select2-selection--single {
        border-radius: 0.375rem;
        height: 38px;
        border: 1px solid var(--border-color, #e2e8f0);
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }
    
    html[data-theme="dark"] .select2-container--default .select2-selection--single {
        background-color: var(--sidebar-bg);
        border-color: rgba(255,255,255,0.1);
    }
    html[data-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text-main);
    }
    html[data-theme="dark"] .select2-dropdown {
        background-color: var(--sidebar-bg);
        border-color: rgba(255,255,255,0.1);
    }
    html[data-theme="dark"] .select2-container--default .select2-results__option[aria-selected=true] {
        background-color: rgba(255,255,255,0.1);
    }
</style>