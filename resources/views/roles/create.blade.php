@extends('layouts.app')

@section('title', 'New Role')

@section('content')
    <div class="card p-4">
        <h4 class="fw-bold mb-4">Create New Role</h4>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>@foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <form action="{{ route('roles.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Role Name (Unique Identifier)</label>
                <input type="text" name="name" class="form-control" placeholder="e.g. branch_manager" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Display Name</label>
                <input type="text" name="display_name" class="form-control" placeholder="e.g. Branch Manager" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2"></textarea>
            </div>

            <h5 class="mt-4 mb-3">Permissions</h5>

            <div class="row">
                @foreach($permissions as $group => $groupPermissions)
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-header bg-light fw-bold text-uppercase">{{ $group }}</div>
                            <div class="card-body">
                                @foreach($groupPermissions as $permission)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]"
                                            value="{{ $permission->name }}" id="perm_{{ $permission->id }}">
                                        <label class="form-check-label" for="perm_{{ $permission->id }}">
                                            {{ $permission->display_name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary mt-3">Create Role</button>
            <a href="{{ route('roles.index') }}" class="btn btn-secondary mt-3">Cancel</a>
        </form>
    </div>
@endsection