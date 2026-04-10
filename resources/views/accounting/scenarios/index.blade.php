@extends('layouts.app')
@section('title', 'Accounting Scenarios')

@section('content')
    <div class="container-fluid px-4">
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h3 class="fw-bold mb-0">Accounting Scenarios Dashboard</h3>
                <p class="text-muted small">Manage the dynamic rules that generate journal entries for every system event.
                </p>
            </div>
        </div>

        <div class="row g-4">
            @foreach($scenarios as $scenario)
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 overflow-hidden position-relative">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 48px; height: 48px;">
                                    <i class="fa-solid fa-bolt fs-5"></i>
                                </div>
                                <div class="form-check form-switch mt-1">
                                    <form action="{{ route('accounting.scenarios.toggle', $scenario->id) }}" method="POST">
                                        @csrf
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            onchange="this.form.submit()" {{ $scenario->is_active ? 'checked' : '' }}>
                                    </form>
                                </div>
                            </div>

                            <h5 class="fw-bold mb-1">{{ $scenario->name }}</h5>
                            <code class="small text-primary mb-3 d-block">{{ $scenario->event_key }}</code>

                            <div class="d-flex align-items-center mb-4">
                                <span class="badge bg-light text-dark border me-2">
                                    <i class="fa-solid fa-list-ol small me-1"></i> {{ $scenario->steps_count }} Steps
                                </span>
                                @if($scenario->is_active)
                                    <span class="badge bg-success-subtle text-success border-success-subtle">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border-secondary-subtle">Disabled</span>
                                @endif
                            </div>

                            <a href="{{ route('accounting.scenarios.show', $scenario->id) }}"
                                class="btn btn-outline-primary w-100 rounded-pill fw-bold">
                                <i class="fa-solid fa-cog me-2"></i>Configure Rules
                            </a>
                        </div>

                        <div class="position-absolute" style="bottom: -20px; right: -20px; opacity: 0.05; font-size: 100px;">
                            <i class="fa-solid fa-file-invoice"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection