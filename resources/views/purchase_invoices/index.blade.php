@extends('layouts.app')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Purchase Invoices</h4>
            <a href="{{ route('purchase_invoices.create') }}" class="btn btn-primary rounded-pill px-4">
                <i class="fa-solid fa-plus me-2"></i> New Bill
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Bill No.</th>
                        <th>Supplier</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($invoices as $invoice)
                        <tr>
                            <td class="ps-4 fw-medium text-dark">{{ $invoice->invoice_number }}</td>
                            <td>{{ optional($invoice->supplier)->name_ar ?? optional($invoice->supplier)->name_en }}</td>
                            <td><span class="badge rounded-pill bg-light text-dark border px-3 py-2"><i class="fa-solid fa-warehouse me-1 opacity-50"></i> Central Warehouse</span></td>
                            <td class="text-muted small">{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                            <td class="fw-bold text-dark">${{ number_format($invoice->total_amount, 2) }}</td>
                            <td>
                                @if($invoice->status == 'approved')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-check-circle me-1"></i> {{ app()->getLocale() == 'ar' ? 'معتمدة' : 'Approved' }}
                                    </span>
                                @elseif($invoice->status == 'cancelled')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-xmark-circle me-1"></i> {{ app()->getLocale() == 'ar' ? 'ملغاة' : 'Cancelled' }}
                                    </span>
                                @else
                                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-clock me-1"></i> {{ app()->getLocale() == 'ar' ? 'مسودة' : 'Draft' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($invoice->payment_status == 'paid')
                                    <span class="badge bg-success text-white px-3 py-2 rounded-pill">Paid</span>
                                @elseif($invoice->payment_status == 'partial')
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Partial</span>
                                @else
                                    <span class="badge bg-danger text-white px-3 py-2 rounded-pill">Unpaid</span>
                                @endif
                            </td>
                            <td class="pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm rounded-circle p-2 border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-ellipsis-vertical text-muted"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 p-2 rounded-3 text-start">
                                        <li>
                                            <a class="dropdown-item py-2 rounded-2" href="{{ route('purchase_invoices.show', $invoice->id) }}">
                                                <i class="fa-solid fa-eye me-2 text-info opacity-75"></i> 
                                                {{ app()->getLocale() == 'ar' ? 'عرض التفاصيل' : 'View Details' }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item py-2 rounded-2" href="{{ route('purchase_invoices.duplicate', $invoice->id) }}">
                                                <i class="fa-solid fa-copy me-2 text-primary opacity-75"></i> 
                                                {{ app()->getLocale() == 'ar' ? 'تكرار الفاتورة' : 'Duplicate Bill' }}
                                            </a>
                                        </li>
                                        
                                        @if($invoice->status == 'draft')
                                            <li><hr class="dropdown-divider opacity-50"></li>
                                            <li>
                                                <a class="dropdown-item py-2 rounded-2 text-success confirm-action" 
                                                   data-title="{{ app()->getLocale() == 'ar' ? 'اعتماد الفاتورة' : 'Approve Invoice' }}"
                                                   data-message="{{ app()->getLocale() == 'ar' ? 'هل أنت متأكد من اعتماد هذه الفاتورة؟ سيتم تحديث المخزون والتكلفة.' : 'Are you sure you want to approve this invoice? Inventory and costs will be updated.' }}"
                                                   data-href="{{ route('purchase_invoices.approve', $invoice->id) }}">
                                                    <i class="fa-solid fa-check me-2 opacity-75"></i> 
                                                    {{ app()->getLocale() == 'ar' ? 'اعتماد' : 'Approve' }}
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item py-2 rounded-2 text-warning" href="{{ route('purchase_invoices.edit', $invoice->id) }}">
                                                    <i class="fa-solid fa-edit me-2 opacity-75"></i> 
                                                    {{ app()->getLocale() == 'ar' ? 'تعديل' : 'Edit' }}
                                                </a>
                                            </li>
                                            <li>
                                                <form id="delete-form-{{ $invoice->id }}" action="{{ route('purchase_invoices.destroy', $invoice->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                                <button type="button" class="dropdown-item py-2 rounded-2 text-danger confirm-action"
                                                        data-title="{{ app()->getLocale() == 'ar' ? 'حذف الفاتورة' : 'Delete Invoice' }}"
                                                        data-message="{{ app()->getLocale() == 'ar' ? 'هل أنت متأكد من حذف هذه الفاتورة؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this invoice? This action cannot be undone.' }}"
                                                        data-form-id="delete-form-{{ $invoice->id }}"
                                                        data-btn-class="btn-danger">
                                                    <i class="fa-solid fa-trash me-2 opacity-75"></i> 
                                                    {{ app()->getLocale() == 'ar' ? 'حذف' : 'Delete' }}
                                                </button>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fa-solid fa-file-invoice fa-3x mb-3 opacity-25"></i><br>
                                No invoices found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Profession Confirmation Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-light py-3">
                    <h5 class="modal-title fw-bold text-dark fs-5" id="confirmModalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4 px-4 text-center">
                    <div class="icon-box mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-light" style="width: 70px; height: 70px;">
                        <i class="fa-solid fa-circle-question fa-2x text-primary" id="confirmModalIcon"></i>
                    </div>
                    <p class="mb-0 text-muted px-3 fs-6" id="confirmModalBody" style="line-height: 1.6;">Are you sure?</p>
                </div>
                <div class="modal-footer border-0 bg-light py-3 d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-outline-secondary border-0 fw-bold rounded-pill px-4" data-bs-dismiss="modal">
                        {{ app()->getLocale() == 'ar' ? 'إلغاء' : 'Cancel' }}
                    </button>
                    <button type="button" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm" id="confirmModalBtn">
                        {{ app()->getLocale() == 'ar' ? 'تأكيد' : 'Confirm' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
            const modalTitle = document.getElementById('confirmModalTitle');
            const modalBody = document.getElementById('confirmModalBody');
            const modalBtn = document.getElementById('confirmModalBtn');
            const modalIcon = document.getElementById('confirmModalIcon');

            let currentAction = null;
            let currentFormId = null;

            document.querySelectorAll('.confirm-action').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const title = this.dataset.title;
                    const message = this.dataset.message;
                    const btnClass = this.dataset.btnClass || 'btn-primary';
                    const iconClass = this.dataset.icon || (btnClass === 'btn-danger' ? 'fa-triangle-exclamation text-danger' : 'fa-circle-question text-primary');
                    
                    currentAction = this.dataset.href;
                    currentFormId = this.dataset.formId;

                    modalTitle.innerText = title;
                    modalBody.innerText = message;
                    
                    // Reset button classes
                    modalBtn.className = 'btn fw-bold rounded-pill px-4 shadow-sm ' + btnClass;
                    modalIcon.className = 'fa-solid fa-2x ' + iconClass;

                    confirmModal.show();
                });
            });

            modalBtn.addEventListener('click', function() {
                if (currentFormId) {
                    document.getElementById(currentFormId).submit();
                } else if (currentAction) {
                    window.location.href = currentAction;
                }
                confirmModal.hide();
            });
        });
    </script>
@endsection