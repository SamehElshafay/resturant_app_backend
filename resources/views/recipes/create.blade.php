@extends('layouts.app')

@section('title', 'New Recipe')

@section('content')
    <div class="card p-4">
        <h4 class="fw-bold mb-4">New Recipe</h4>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>@foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <form action="{{ route('recipes.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select @error('product_id') is-invalid @enderror" required
                    style="color: #000;">
                    <option value="" class="text-muted">Select Finished Product...</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" style="color: #000;">
                            {{ $product->name_ar ?? $product->name_en ?? $product->name ?? 'Product #' . $product->id }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Instructions</label>
                <textarea name="instructions" class="form-control" rows="3"></textarea>
            </div>

            <h5>Ingredients & Sub-Products</h5>
            <div id="ingredients-container">
                {{-- Initial Row --}}
                <div class="row mb-2 ingredient-row" data-index="0">
                    <div class="col-md-2">
                        <select name="ingredients[0][type]" class="form-select type-select" required>
                            <option value="ingredient">Raw Material</option>
                            <option value="sub_product">Sub-Product</option>
                        </select>
                    </div>
                    <div class="col-md-4 component-wrapper">
                        {{-- Ingredient Select --}}
                        <select name="ingredients[0][ingredient_id]" class="form-select item-select ingredient-select"
                            required style="color: #000;">
                            <option value="" class="text-muted">Select Ingredient...</option>
                            @foreach($ingredients as $ing)
                                <option value="{{ $ing->id }}" data-cost="{{ $ing->cost_price }}" data-unit="{{ $ing->unit }}"
                                    style="color: #000;">
                                    {{ $ing->name_ar ?? $ing->name_en ?? $ing->name }}
                                </option>
                            @endforeach
                        </select>
                        {{-- Sub-Product Select (Hidden by Default) --}}
                        <select name="ingredients[0][child_product_id]"
                            class="form-select item-select product-select d-none" disabled style="color: #000;">
                            <option value="" class="text-muted">Select Product...</option>
                            @foreach($subProducts as $sub)
                                <option value="{{ $sub->id }}" data-cost="{{ $sub->base_purchase_price ?? 0 }}"
                                    data-unit="piece" style="color: #000;">
                                    {{ $sub->name_ar ?? $sub->name_en ?? $sub->name }}
                                </option>
                            @endforeach
                        </select>
                        {{-- Recipe Preview --}}
                        <div class="component-preview mt-1 text-muted small fst-italic d-none ps-2 border-start border-3 border-info"></div>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="ingredients[0][quantity]" class="form-control" step="0.001"
                            placeholder="Qty" required>
                    </div>
                    <div class="col-md-2">
                        <select name="ingredients[0][unit]" class="form-select unit-select" required>
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="ltr">ltr</option>
                            <option value="ml">ml</option>
                            <option value="piece">piece</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <input type="number" name="ingredients[0][cost_per_unit]" class="form-control cost-display"
                            step="0.01" placeholder="Cost" readonly>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm mb-3" id="add-ingredient">Add Item</button>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Save Recipe</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let i = 1;

            // Update cost and unit based on selection
            function attachEvents(row) {
                const typeSelect = row.querySelector('.type-select');
                const ingredientSelect = row.querySelector('.ingredient-select');
                const productSelect = row.querySelector('.product-select');
                const previewContainer = row.querySelector('.component-preview');
                const costInput = row.querySelector('.cost-display');
                const unitSelect = row.querySelector('.unit-select');

                // Toggle between Ingredient and Product list
                typeSelect.addEventListener('change', function () {
                    if (this.value === 'ingredient') {
                        ingredientSelect.classList.remove('d-none');
                        ingredientSelect.disabled = false;
                        productSelect.classList.add('d-none');
                        productSelect.disabled = true;
                        if(previewContainer) previewContainer.classList.add('d-none');
                    } else {
                        ingredientSelect.classList.add('d-none');
                        ingredientSelect.disabled = true;
                        productSelect.classList.remove('d-none');
                        productSelect.disabled = false;
                        unitSelect.value = 'piece'; // Default for products
                    }
                    // Reset values
                    ingredientSelect.value = '';
                    productSelect.value = '';
                    costInput.value = '';
                    if(previewContainer) previewContainer.innerHTML = '';
                });

                // Update cost when item selected
                [ingredientSelect, productSelect].forEach(sel => {
                    sel.addEventListener('change', function () {
                        const opt = this.options[this.selectedIndex];
                        if (opt.value) {
                            costInput.value = opt.getAttribute('data-cost') || 0;
                            const unit = opt.getAttribute('data-unit');
                            if (unit) unitSelect.value = unit;

                            // Fetch recipe if Sub-Product
                            if (this.classList.contains('product-select') && previewContainer) {
                                previewContainer.classList.remove('d-none');
                                previewContainer.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Checking components...';
                                
                                fetch("{{ url('products') }}/" + this.value + "/recipe")
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.has_recipe && data.ingredients && data.ingredients.length > 0) {
                                            const items = data.ingredients.map(i => `${i.name} (${i.quantity}${i.unit})`).join(', ');
                                            previewContainer.innerHTML = `<i class="fa fa-info-circle me-1"></i> <span class="text-dark">Includes:</span> ${items}`;
                                        } else {
                                            previewContainer.innerHTML = '<small class="text-muted"><i class="fa fa-exclamation-circle me-1"></i> No sub-components defined.</small>';
                                        }
                                    })
                                    .catch(err => {
                                        console.error(err);
                                        previewContainer.classList.add('d-none');
                                    });
                            }
                        } else {
                            if (this.classList.contains('product-select') && previewContainer) {
                                previewContainer.classList.add('d-none');
                            }
                        }
                    });
                });
            }

            // Initial bind
            document.querySelectorAll('.ingredient-row').forEach(row => attachEvents(row));

            document.getElementById('add-ingredient').addEventListener('click', function () {
                // Clone the first row as a template (simpler than string template)
                const template = document.querySelector('.ingredient-row');
                const newRow = template.cloneNode(true);

                newRow.setAttribute('data-index', i);

                // Update names
                newRow.querySelector('.type-select').name = `ingredients[${i}][type]`;
                newRow.querySelector('.ingredient-select').name = `ingredients[${i}][ingredient_id]`;
                newRow.querySelector('.product-select').name = `ingredients[${i}][child_product_id]`;
                newRow.querySelector('input[name^="ingredients"][placeholder="Qty"]').name = `ingredients[${i}][quantity]`;
                newRow.querySelector('.unit-select').name = `ingredients[${i}][unit]`;
                newRow.querySelector('.cost-display').name = `ingredients[${i}][cost_per_unit]`;

                // Reset values
                newRow.querySelector('.type-select').value = 'ingredient';
                newRow.querySelector('.ingredient-select').value = '';
                newRow.querySelector('.ingredient-select').classList.remove('d-none');
                newRow.querySelector('.ingredient-select').disabled = false;

                newRow.querySelector('.product-select').value = '';
                newRow.querySelector('.product-select').classList.add('d-none');
                newRow.querySelector('.product-select').disabled = true;

                newRow.querySelector('input[placeholder="Qty"]').value = '';
                newRow.querySelector('.cost-display').value = '';

                document.getElementById('ingredients-container').appendChild(newRow);
                attachEvents(newRow);
                i++;
            });

            document.getElementById('ingredients-container').addEventListener('click', function (e) {
                if (e.target.closest('.remove-row')) {
                    if (document.querySelectorAll('.ingredient-row').length > 1) {
                        e.target.closest('.row').remove();
                    } else {
                        alert('At least one ingredient or sub-product is required.');
                    }
                }
            });
        });
    </script>
@endsection