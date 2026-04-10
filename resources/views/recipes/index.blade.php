@extends('layouts.app')

@section('title', __('messages.recipes'))

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">{{ __('messages.recipes') }}</h4>
            <a href="{{ route('recipes.create') }}" class="btn btn-primary rounded-pill px-4">
                <i class="fa-solid fa-plus me-2"></i> {{ __('messages.add_recipe') }}
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 border-0 rounded-start">{{ __('messages.product') }}</th>
                        <th class="border-0">{{ __('messages.current_stock') }}</th>
                        <th class="border-0">{{ __('messages.cost') }}</th>
                        <th class="border-0">{{ __('messages.ingredients_count') }}</th>
                        <th class="pe-3 border-0 rounded-end text-end">{{ __('messages.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recipes as $recipe)
                        <tr>
                            <td class="fw-bold ps-3">
                                @if($recipe->product)
                                    {{ $recipe->product->name_ar ?: ($recipe->product->name_en ?: $recipe->product->name) }}
                                @else
                                    <span class="text-danger">Product Not Found (ID: {{ $recipe->product_id }})</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ number_format($recipe->product->stock_quantity ?? 0, 2) }}
                                </span>
                            </td>
                            <td class="text-success fw-bold">${{ number_format($recipe->cost, 2) }}</td>
                            <td>{{ $recipe->ingredients->count() }}</td>
                            <td class="text-end pe-3">
                                <a href="{{ route('recipes.show', $recipe->id) }}" class="btn btn-sm btn-info text-white"><i
                                        class="fa-solid fa-eye"></i></a>
                                <form action="{{ route('recipes.destroy', $recipe->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light text-danger"
                                        onclick="return confirm('Delete recipe?')"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection