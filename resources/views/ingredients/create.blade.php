@extends('layouts.app')

@section('title', 'New Ingredient')

@section('content')
    <div class="card p-4">
        <h4 class="fw-bold mb-4">Add New Ingredient / Raw Material</h4>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>@foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <form action="{{ route('ingredients.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name (Arabic) <span class="text-danger">*</span></label>
                    <input type="text" name="name_ar" class="form-control" required placeholder="مثال: أرز">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Name (English)</label>
                    <input type="text" name="name_en" class="form-control" placeholder="e.g. Rice">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Unit <span class="text-danger">*</span></label>
                    <select name="unit" class="form-select" required style="color: #000;">
                        <option value="kg">Kilogram (kg)</option>
                        <option value="g">Gram (g)</option>
                        <option value="ltr">Liter (ltr)</option>
                        <option value="ml">Milliliter (ml)</option>
                        <option value="piece">Piece / Box / Can</option>
                    </select>
                </div>
                <!-- Cost Removed: Calculated from Purchase Invoices -->
                <div class="col-md-6 mb-3">
                    <label class="form-label">Calculated Average Cost</label>
                    <input type="text" class="form-control" value="Calculated from Invoices" disabled>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Opening Stock (Optional)</label>
                    <input type="number" name="stock_quantity" class="form-control" step="0.001" min="0" value="0">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Minimum Stock Level (Alert)</label>
                    <input type="number" name="min_stock_level" class="form-control" step="0.001" min="0" value="10">
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Initial Save</button>
            <a href="{{ route('ingredients.index') }}" class="btn btn-secondary mt-3">Cancel</a>
        </form>
    </div>
@endsection