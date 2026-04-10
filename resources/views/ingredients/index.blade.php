@extends('layouts.app')

@section('title', 'Ingredients')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Ingredients / Raw Materials</h4>
            <a href="{{ route('ingredients.create') }}" class="btn btn-primary rounded-pill px-4">
                <i class="fa-solid fa-plus me-2"></i> New Item
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name (AR)</th>
                        <th>Name (EN)</th>
                        <th>Unit</th>
                        <th>Current Cost</th>
                        <th>Current Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ingredients as $ingredient)
                        <tr>
                            <td>#{{ $ingredient->id }}</td>
                            <td>{{ $ingredient->name_ar }}</td>
                            <td>{{ $ingredient->name_en ?? '-' }}</td>
                            <td><span class="badge bg-info">{{ $ingredient->unit }}</span></td>
                            <td class="fw-bold text-success">${{ number_format($ingredient->cost_price ?? 0, 2) }}</td>
                            <td class="fw-bold">{{ number_format($ingredient->stock_quantity ?? 0, 2) }}</td>
                            <td>
                                <a href="{{ route('ingredients.edit', $ingredient->id) }}" class="btn btn-sm btn-light"><i
                                        class="fa-solid fa-edit"></i></a>
                                <form action="{{ route('ingredients.destroy', $ingredient->id) }}" method="POST"
                                    class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger"
                                        onclick="return confirm('Delete ingredient?')"><i
                                            class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection