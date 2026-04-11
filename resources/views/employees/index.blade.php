@extends('layouts.app')

@section('title', __('messages.employees'))

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ __('messages.employees') }}</h4>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="fa-solid fa-plus me-2"></i> Add Employee
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($employees->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Branch</th>
                            <th>PIN</th>
                            <th>Acc. Code</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $employee)
                            <tr>
                                <td>#{{ $employee->id }}</td>
                                <td class="fw-semibold">{{ $employee->name }}</td>
                                <td>{{ $employee->email }}</td>
                                <td><span class="badge bg-primary">{{ ucfirst($employee->role ?? 'N/A') }}</span></td>
                                <td>{{ $employee->branch->name ?? 'N/A' }}</td>
                                <td>
                                    @if($employee->pin)
                                        <div class="d-flex align-items-center">
                                            <code id="pin-text-{{ $employee->id }}" class="me-2" style="display: none;">{{ $employee->pin }}</code>
                                            <code id="pin-stars-{{ $employee->id }}" class="me-2">****</code>
                                            <button class="btn btn-sm btn-link p-0 text-secondary" onclick="togglePinList({{ $employee->id }})">
                                                <i class="fa-solid fa-eye" id="pin-icon-{{ $employee->id }}"></i>
                                            </button>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><code>{{ $employee->account_code ?? 'N/A' }}</code></td>
                                <td>
                                    <button class="btn btn-sm btn-light" data-bs-toggle="modal"
                                        data-bs-target="#editEmployeeModal{{ $employee->id }}">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light text-danger"
                                        onclick="confirmDelete('delete-form-{{ $employee->id }}', '{{ $employee->name }}')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $employee->id }}" action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="d-none">
                                        @csrf @method('DELETE')
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editEmployeeModal{{ $employee->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form action="{{ route('employees.update', $employee->id) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Employee</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" name="name" class="form-control"
                                                        value="{{ $employee->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control"
                                                        value="{{ $employee->email }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Password (leave empty to keep current)</label>
                                                    <input type="password" name="password" class="form-control"
                                                        placeholder="••••••••">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Role</label>
                                                    <select name="role" class="form-select" required>
                                                        <option value="admin" {{ $employee->role == 'admin' ? 'selected' : '' }}>Admin
                                                        </option>
                                                        <option value="manager" {{ $employee->role == 'manager' ? 'selected' : '' }}>
                                                            Manager</option>
                                                        <option value="cashier" {{ $employee->role == 'cashier' ? 'selected' : '' }}>
                                                            Cashier</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3 pin-field-edit" style="{{ $employee->role == 'cashier' ? '' : 'display:none;' }}">
                                                    <label class="form-label">PIN (4 Digits)</label>
                                                    <div class="input-group">
                                                        <input type="password" name="pin" id="pin_edit_{{ $employee->id }}" class="form-control"
                                                            value="{{ $employee->pin }}" maxlength="4" pattern="\d{4}" readonly>
                                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePinVisibility('pin_edit_{{ $employee->id }}', this)">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Branch</label>
                                                    <select name="branch_id" class="form-select">
                                                        <option value="">No Branch</option>
                                                        @foreach($branches as $branch)
                                                            <option value="{{ $branch->id }}" {{ $employee->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary"
                                                    data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fa-solid fa-users fa-4x text-secondary opacity-50 mb-3"></i>
                <p class="text-secondary">No employees found.</p>
            </div>
        @endif
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="addEmployeeForm" action="{{ route('employees.store') }}" method="POST">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i
                                class="fa-solid fa-user-plus me-2"></i>{{ app()->getLocale() == 'ar' ? 'إضافة موظف جديد' : 'Add New Employee' }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <div id="addEmployeeAlert" class="alert d-none"></div>

                        <div class="mb-3">
                            <label
                                class="form-label fw-bold small">{{ app()->getLocale() == 'ar' ? 'الاسم الكامل' : 'Full Name' }}</label>
                            <input type="text" name="name" class="form-control rounded-3"
                                placeholder="{{ app()->getLocale() == 'ar' ? 'مثال: محمد علي' : 'e.g. John Doe' }}"
                                required>
                        </div>
                        <div class="mb-3">
                            <label
                                class="form-label fw-bold small">{{ app()->getLocale() == 'ar' ? 'البريد الإلكتروني' : 'Email Address' }}</label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="john@example.com"
                                required>
                        </div>
                        <div class="mb-3">
                            <label
                                class="form-label fw-bold small">{{ app()->getLocale() == 'ar' ? 'كلمة المرور' : 'Password' }}</label>
                            <input type="password" name="password" class="form-control rounded-3" placeholder="••••••••"
                                required>
                        </div>
                        <div class="mb-3">
                            <label
                                class="form-label fw-bold small">{{ app()->getLocale() == 'ar' ? 'الوظيفة' : 'Role' }}</label>
                            <select name="role" class="form-select rounded-3" required>
                                <option value="">{{ app()->getLocale() == 'ar' ? 'اختر الوظيفة' : 'Select Role' }}</option>
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                                <option value="cashier">Cashier</option>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="pin-field-add">
                            <label class="form-label fw-bold small">{{ app()->getLocale() == 'ar' ? 'الرمز السري (4 أرقام)' : 'PIN (4 Digits)' }}</label>
                            <div class="input-group">
                                <input type="password" name="pin" id="pin_input_add" class="form-control rounded-start-3" placeholder="1234" maxlength="4" pattern="\d{4}" readonly>
                                <button class="btn btn-outline-secondary rounded-end-3" type="button" onclick="togglePinVisibility('pin_input_add', this)">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text text-muted small mt-1">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                {{ app()->getLocale() == 'ar' ? 'يتم إنشاء الرمز السري تلقائياً لضمان عدم تكراره.' : 'PIN is generated automatically to ensure uniqueness.' }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label
                                class="form-label fw-bold small">{{ app()->getLocale() == 'ar' ? 'الفرع' : 'Branch' }}</label>
                            <select name="branch_id" class="form-select rounded-3">
                                <option value="">{{ app()->getLocale() == 'ar' ? 'بدون فرع' : 'No Branch' }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-4"
                            data-bs-dismiss="modal">{{ app()->getLocale() == 'ar' ? 'إغلاق' : 'Close' }}</button>
                        <button type="submit" id="submitAddEmployee" class="btn btn-primary rounded-pill px-4">
                            <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                            <span class="btn-text">{{ app()->getLocale() == 'ar' ? 'إضافة موظف' : 'Add Employee' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Error Details Modal -->
    <div class="modal fade" id="errorDetailsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">{{ app()->getLocale() == 'ar' ? 'تفاصيل الخطأ' : 'Error Details' }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">
                        {{ app()->getLocale() == 'ar' ? 'الرسالة التقنية:' : 'Technical Message:' }}
                    </p>
                    <div class="p-3 bg-light rounded border text-danger font-monospace small" id="errorDetailsContent">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        document.getElementById('addEmployeeForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const form = this;
            const btn = document.getElementById('submitAddEmployee');
            const alert = document.getElementById('addEmployeeAlert');
            const spinner = btn.querySelector('.spinner-border');
            const btnText = btn.querySelector('.btn-text');

            // UI States: Loading
            btn.disabled = true;
            spinner.classList.remove('d-none');
            alert.classList.add('d-none');
            alert.classList.remove('alert-success', 'alert-danger');

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(async response => {
                    const data = await response.json();

                    if (response.ok && data.success) {
                        // Success UI
                        alert.textContent = data.message;
                        alert.className = 'alert alert-success d-block';

                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Business Logic or Validation Error
                        handleError(data.message || 'Something went wrong', data.details || data.errors);
                    }
                })
                .catch(error => {
                    handleError('{{ app()->getLocale() == "ar" ? "حدث خطأ غير متوقع" : "An unexpected error occurred" }}', error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    spinner.classList.add('d-none');
                });

            function handleError(msg, details) {
                alert.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>${msg}</span>
                                    ${details ? `<button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="showErrorDetails('${details.toString().replace(/'/g, "\\'")}')">
                                        {{ app()->getLocale() == 'ar' ? 'التفاصيل' : 'Details' }}
                                    </button>` : ''}
                                </div>
                            `;
                alert.className = 'alert alert-danger d-block';
            }
        });

        function showErrorDetails(details) {
            document.getElementById('errorDetailsContent').textContent = details;
            new bootstrap.Modal(document.getElementById('errorDetailsModal')).show();
        }

        // Dynamic PIN field visibility and generation
        document.querySelectorAll('select[name="role"]').forEach(select => {
            select.addEventListener('change', function() {
                const isAddForm = !this.closest('form').querySelector('input[name="_method"]');
                const pinField = this.closest('form').querySelector(this.closest('.modal-body') ? '.pin-field-edit' : '#pin-field-add');
                
                if (this.value === 'cashier') {
                    if (pinField) {
                        pinField.classList.remove('d-none');
                        pinField.style.display = 'block';
                        
                        const pinInput = pinField.querySelector('input[name="pin"]');
                        if (!pinInput.value) {
                            generatePinForForm(pinInput);
                        }
                    }
                } else {
                    if (pinField) {
                        pinField.classList.add('d-none');
                        pinField.style.display = 'none';
                    }
                }
            });
        });

        function generatePinForForm(input) {
            fetch('{{ route('employees.generate-pin') }}')
                .then(res => res.json())
                .then(data => {
                    input.value = data.pin;
                });
        }

        function togglePinVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        function togglePinList(id) {
            const text = document.getElementById(`pin-text-${id}`);
            const stars = document.getElementById(`pin-stars-${id}`);
            const icon = document.getElementById(`pin-icon-${id}`);
            
            if (text.style.display === 'none') {
                text.style.display = 'inline';
                stars.style.display = 'none';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                text.style.display = 'none';
                stars.style.display = 'inline';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
@endsection