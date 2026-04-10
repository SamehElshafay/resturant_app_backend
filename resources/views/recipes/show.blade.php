@extends('layouts.app')

@section('title', 'Recipe Details')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Recipe for
                {{ optional($recipe->product)->name_ar ?? optional($recipe->product)->name_en }}</h4>
            <a href="{{ route('recipes.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <strong>Product:</strong>
                {{ optional($recipe->product)->name_ar ?? optional($recipe->product)->name_en }}<br>
                <strong>Cost:</strong> ${{ number_format($recipe->cost, 2) }}
            </div>
            <div class="col-md-6">
                <strong>Instructions:</strong>
                <p>{{ $recipe->instructions ?? 'No instructions provided.' }}</p>
            </div>
        </div>

        <h5 class="mt-4">Ingredients</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Ingredient</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Cost/Unit</th>
                    <th>Total Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recipe->ingredients as $ing)
                    <tr>
                        <td>{{ optional($ing->product)->name_ar ?? optional($ing->product)->name_en }}</td>
                        <td>{{ $ing->quantity }}</td>
                        <td>{{ $ing->unit }}</td>
                        <td>${{ number_format($ing->cost_per_unit, 2) }}</td>
                        <td>${{ number_format($ing->quantity * $ing->cost_per_unit, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection