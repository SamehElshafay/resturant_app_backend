@extends('layouts.app')

@section('title', 'Edit Ingredient')

@section('content')
    <div class="card p-4">
        <h4 class="fw-bold mb-4">Edit Ingredient</h4>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>@foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <form action="{{ route('ingredients.update', $ingredient->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name (Arabic) <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control" required value="{{ $ingredient->name_ar }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name (English)</label>
                    <input type="text" name="name_en" class="form-control" value="{{ $ingredient->name_en }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                    <select name="unit" class="form-select" required style="color: #000;">
                        <option value="kg" {{ $ingredient->unit == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                        <option value="g" {{ $ingredient->unit == 'g' ? 'selected' : '' }}>Gram (g)</option>
                        <option value="ltr" {{ $ingredient->unit == 'ltr' ? 'selected' : '' }}>Liter (ltr)</option>
                        <option value="ml" {{ $ingredient->unit == 'ml' ? 'selected' : '' }}>Milliliter (ml)</option>
                        <option value="piece" {{ $ingredient->unit == 'piece' ? 'selected' : '' }}>Piece / Box / Can</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Current Average Cost <small class="text-muted">(Calculated)</small></label>
                    <input type="number" class="form-control" value="{{ $ingredient->cost_price }}" disabled>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Current Stock (Global)</label>
                    <input type="number" name="stock_quantity" class="form-control" step="0.001" min="0"
                        value="{{ $ingredient->stock_quantity }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Minimum Stock Level (Alert)</label>
                    <input type="number" name="min_stock_level" class="form-control" step="0.001" min="0"
                        value="{{ $ingredient->min_stock_level }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Update Ingredient</button>
            <a href="{{ route('ingredients.index') }}" class="btn btn-secondary mt-3">Cancel</a>
        </form>
    </div>
@endsection