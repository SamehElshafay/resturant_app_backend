@extends('layouts.app')

@section('title', 'Edit Recipe')

@section('content')
    <div class="card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Edit Recipe: <span class="text-primary">{{ $recipe->product->name_ar ?? $recipe->product->name_en ?? $recipe->product->name }}</span></h4>
            <a href="{{ route('recipes.index') }}" class="btn btn-outline-secondary btn-sm">Back to List</a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>@foreach($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            </div>
        @endif

        <form action="{{ route('recipes.update', $recipe->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-3">
                <label class="form-label">Product (Read-only)</label>
                <select class="form-select bg-light" disabled>
                    <option value="{{ $recipe->product_id }}" selected>
                        {{ $recipe->product->id }} - {{ $recipe->product->name_ar ?? $recipe->product->name_en ?? $recipe->product->name }}
                    </option>
                </select>
                <input type="hidden" name="product_id" value="{{ $recipe->product_id }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Instructions</label>
                <textarea name="instructions" class="form-control" rows="3">{{ old('instructions', $recipe->instructions) }}</textarea>
            </div>

            <h5>Ingredients & Sub-Products</h5>
            <div id="ingredients-container">
                @foreach($recipe->ingredients as $index => $item)
                    <div class="row mb-2 ingredient-row" data-index="{{ $index }}">
                        <div class="col-md-2">
                            <select name="ingredients[{{ $index }}][type]" class="form-select type-select" required>
                                <option value="ingredient" @if($item->ingredient_id) selected @endif>Raw Material</option>
                                <option value="sub_product" @if($item->child_product_id) selected @endif>Sub-Product</option>
                            </select>
                        </div>
                        <div class="col-md-4 component-wrapper">
                            {{-- Ingredient Select --}}
                            <select name="ingredients[{{ $index }}][ingredient_id]" 
                                class="form-select item-select ingredient-select select2-ajax-ingredient @if(!$item->ingredient_id) d-none @endif"
                                @if(!$item->ingredient_id) disabled @endif
                                required style="color: #000;">
                                @if($item->ingredient)
                                    <option value="{{ $item->ingredient_id }}" selected>{{ $item->ingredient->name_ar ?? $item->ingredient->name_en }}</option>
                                @else
                                    <option value="" class="text-muted">Select Ingredient...</option>
                                @endif
                            </select>

                            {{-- Sub-Product Select --}}
                            <select name="ingredients[{{ $index }}][child_product_id]"
                                class="form-select item-select product-select select2-ajax-product @if(!$item->child_product_id) d-none @endif"
                                @if(!$item->child_product_id) disabled @endif
                                style="color: #000;">
                                @if($item->childProduct)
                                    <option value="{{ $item->child_product_id }}" selected>{{ $item->childProduct->name_ar ?? $item->childProduct->name_en }}</option>
                                @else
                                    <option value="" class="text-muted">Select Product...</option>
                                @endif
                            </select>

                            <div class="component-preview mt-1 text-muted small fst-italic d-none ps-2 border-start border-3 border-info"></div>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="ingredients[{{ $index }}][quantity]" class="form-control" step="0.001"
                                placeholder="Qty" value="{{ $item->quantity }}" required>
                        </div>
                        <div class="col-md-2">
                            <select name="ingredients[{{ $index }}][unit]" class="form-select unit-select" required>
                                @foreach(['kg', 'g', 'ltr', 'ml', 'piece'] as $u)
                                    <option value="{{ $u }}" @if($item->unit == $u) selected @endif>{{ $u }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <input type="number" name="ingredients[{{ $index }}][cost_per_unit]" class="form-control cost-display"
                                step="0.01" placeholder="Cost" value="{{ $item->cost_per_unit }}" readonly>
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-secondary btn-sm mb-3" id="add-ingredient">Add Item</button>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Update Recipe</button>
            </div>
        </form>
    </div>

    <script>
        $(document).ready(function () {
            let i = {{ $recipe->ingredients->count() }};

            function initSelect2(row = null) {
                const scope = row ? $(row) : $(document);

                scope.find('.select2-ajax-product').select2({
                    theme: 'bootstrap-5',
                    ajax: {
                        url: '{{ route("products.search") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) { return { q: params.term }; },
                        processResults: function (data) { return { results: data }; },
                        cache: true
                    },
                    minimumInputLength: 0
                }).on('select2:select', function(e) {
                    const data = e.params.data;
                    const row = $(this).closest('.ingredient-row');
                    if (row.length) {
                        row.find('.cost-display').val(data.cost || 0);
                        row.find('.unit-select').val(data.unit || 'piece');
                        checkRecipeLink(row, data.id);
                    }
                });

                scope.find('.select2-ajax-ingredient').select2({
                    theme: 'bootstrap-5',
                    ajax: {
                        url: '{{ route("ingredients.search") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) { return { q: params.term }; },
                        processResults: function (data) { return { results: data }; },
                        cache: true
                    },
                    minimumInputLength: 0
                }).on('select2:select', function(e) {
                    const data = e.params.data;
                    const row = $(this).closest('.ingredient-row');
                    if (row.length) {
                        row.find('.cost-display').val(data.cost || 0);
                        if (data.unit) row.find('.unit-select').val(data.unit);
                    }
                });
            }

            function checkRecipeLink(row, productId) {
                const previewContainer = row.find('.component-preview');
                if (!previewContainer.length) return;
                previewContainer.removeClass('d-none').html('<i class="fa fa-spinner fa-spin"></i> Checking...');
                fetch("{{ url('products') }}/" + productId + "/recipe")
                    .then(res => res.json())
                    .then(data => {
                        if (data.has_recipe && data.ingredients && data.ingredients.length > 0) {
                            const items = data.ingredients.map(i => `${i.name} (${i.quantity}${i.unit})`).join(', ');
                            previewContainer.html(`<i class="fa fa-info-circle me-1"></i> <span class="text-dark">Includes:</span> ${items}`);
                        } else {
                            previewContainer.html('<small class="text-muted">No sub-components.</small>');
                        }
                    })
                    .catch(() => previewContainer.addClass('d-none'));
            }

            function attachEvents(row) {
                const $row = $(row);
                const typeSelect = $row.find('.type-select');
                const ingredientSelect = $row.find('.ingredient-select');
                const productSelect = $row.find('.product-select');
                const costInput = $row.find('.cost-display');

                typeSelect.on('change', function () {
                    if (this.value === 'ingredient') {
                        ingredientSelect.next('.select2-container').show();
                        ingredientSelect.prop('disabled', false);
                        productSelect.next('.select2-container').hide();
                        productSelect.prop('disabled', true);
                    } else {
                        ingredientSelect.next('.select2-container').hide();
                        ingredientSelect.prop('disabled', true);
                        productSelect.next('.select2-container').show();
                        productSelect.prop('disabled', false);
                        $row.find('.unit-select').val('piece');
                    }
                    ingredientSelect.val(null).trigger('change');
                    productSelect.val(null).trigger('change');
                    costInput.val('');
                });
            }

            initSelect2();
            $('.ingredient-row').each(function() {
                attachEvents(this);
                // Initial visibility based on type
                const type = $(this).find('.type-select').val();
                if (type === 'ingredient') {
                    $(this).find('.product-select').next('.select2-container').hide();
                } else {
                    $(this).find('.ingredient-select').next('.select2-container').hide();
                }
            });

            $('#add-ingredient').on('click', function () {
                const template = $('.ingredient-row').first();
                const $newRow = template.clone();
                $newRow.attr('data-index', i);
                $newRow.find('.select2-container').remove();
                $newRow.find('select').show().removeClass('select2-hidden-accessible').removeAttr('data-select2-id');

                $newRow.find('.type-select').attr('name', `ingredients[${i}][type]`).val('ingredient');
                $newRow.find('.ingredient-select').attr('name', `ingredients[${i}][ingredient_id]`).val(null).prop('disabled', false).show();
                $newRow.find('.product-select').attr('name', `ingredients[${i}][child_product_id]`).val(null).prop('disabled', true).hide();
                $newRow.find('input[placeholder="Qty"]').attr('name', `ingredients[${i}][quantity]`).val('');
                $newRow.find('.unit-select').attr('name', `ingredients[${i}][unit]`).val('kg');
                $newRow.find('.cost-display').attr('name', `ingredients[${i}][cost_per_unit]`).val('');
                $newRow.find('.component-preview').addClass('d-none').empty();

                $('#ingredients-container').append($newRow);
                initSelect2($newRow);
                attachEvents($newRow);
                $newRow.find('.product-select').next('.select2-container').hide();
                i++;
            });

            $('#ingredients-container').on('click', '.remove-row', function () {
                if ($('.ingredient-row').length > 1) { $(this).closest('.row').remove(); }
                else { alert('At least one item is required.'); }
            });
        });
    </script>
@endsection
