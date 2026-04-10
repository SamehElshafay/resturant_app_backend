@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Roles & Permissions</h4>
            <a href="{{ route('roles.create') }}" class="btn btn-primary rounded-pill px-4">
                <i class="fa-solid fa-plus me-2"></i> New Role
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Role Name</th>
                        <th>Display Name</th>
                        <th>Users Count</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->display_name }}</td>
                            <td>{{ $role->users()->count() }}</td>
                            <td>
                                <span class="badge bg-info">{{ $role->permissions->count() }} Perms</span>
                            </td>
                            <td>
                                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-light"><i
                                        class="fa-solid fa-edit"></i></a>
                                @if($role->name !== 'admin')
                                    <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light text-danger"
                                            onclick="return confirm('Delete role?')"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection