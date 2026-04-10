@extends('layouts.app')

@section('title', 'System Settings')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="fw-bold"><i class="fa-solid fa-gears me-2 text-primary"></i>System & Security Settings</h4>
            <p class="text-muted">Manage authentication tokens and system configurations.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Token Expiration Settings -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0">Token & Session Expiration</h5>
                    <p class="small text-muted">Set expiration time (in minutes) for each admin role.</p>
                </div>
                <div class="card-body px-4 pb-4">
                    <form action="{{ route('system.settings.tokens') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light small text-muted">
                                    <tr>
                                        <th>Role</th>
                                        <th>Unlimited</th>
                                        <th>Expiration (Minutes)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($roles as $role)
                                        @php $isUnlimited = is_null($role->token_lifetime_minutes); @endphp
                                        <tr>
                                            <td class="fw-bold text-dark">
                                                <i class="fa-solid fa-user-shield me-2 text-indigo"></i>
                                                {{ ucfirst($role->name) }}
                                            </td>
                                            <td>
                                                <div class="form-check form-switch ps-5">
                                                    <input class="form-check-input unlimited-toggle" type="checkbox" 
                                                           name="unlimited[{{ $role->id }}]" value="1"
                                                           {{ $isUnlimited ? 'checked' : '' }}
                                                           data-role-id="{{ $role->id }}">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm lifetime-input-group-{{ $role->id }}" 
                                                     style="max-width: 150px; {{ $isUnlimited ? 'opacity: 0.5; pointer-events: none;' : '' }}">
                                                    <input type="number" name="role_lifetimes[{{ $role->id }}]" 
                                                           id="input-{{ $role->id }}"
                                                           class="form-control rounded-start-pill border-end-0" 
                                                           value="{{ $role->token_lifetime_minutes ?? 1440 }}" min="1"
                                                           {{ $isUnlimited ? 'disabled' : '' }}>
                                                    <span class="input-group-text bg-white border-start-0 rounded-end-pill small text-muted">min</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                                <i class="fa-solid fa-save me-2"></i> Save Token Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Future Settings Placeholder -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-light bg-opacity-50 border-2 border-dashed text-main">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center p-5">
                    <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                        <i class="fa-solid fa-lock text-muted fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-muted">More Security Settings</h6>
                    <p class="small text-muted mb-0">Coming soon: IP Whitelisting, 2FA Enforcement, and Audit Logs configuration.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.unlimited-toggle');
        toggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const roleId = this.dataset.roleId;
                const inputGroup = document.querySelector(`.lifetime-input-group-${roleId}`);
                const input = document.getElementById(`input-${roleId}`);
                
                if (this.checked) {
                    inputGroup.style.opacity = '0.5';
                    inputGroup.style.pointerEvents = 'none';
                    input.disabled = true;
                } else {
                    inputGroup.style.opacity = '1';
                    inputGroup.style.pointerEvents = 'auto';
                    input.disabled = false;
                }
            });
        });
    });
</script>
@endsection

@section('extra_css')
<style>
    .text-indigo { color: #6366f1; }
    .bg-indigo-subtle { background-color: rgba(99, 102, 241, 0.1); }
    .rounded-4 { border-radius: 1rem !important; }
</style>
@endsection
