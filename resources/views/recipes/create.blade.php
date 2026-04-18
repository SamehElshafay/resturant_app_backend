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
                <select name="product_id" class="form-select select2-ajax-product @error('product_id') is-invalid @enderror" required
                    style="color: #000;">
                    <option value="" class="text-muted">Select Finished Product...</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->id }} - {{ $product->name_ar ?? $product->name_en ?? $product->name }}
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
                        <select name="ingredients[0][ingredient_id]" class="form-select item-select ingredient-select select2-ajax-ingredient"
                            required style="color: #000;">
                            <option value="" class="text-muted">Select Ingredient...</option>
                        </select>
                        {{-- Sub-Product Select (Hidden by Default) --}}
                        <select name="ingredients[0][child_product_id]"
                            class="form-select item-select product-select select2-ajax-product d-none" disabled style="color: #000;">
                            <option value="" class="text-muted">Select Product...</option>
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
        $(document).ready(function () {
            let i = 1;

            function initSelect2(row = null) {
                const scope = row ? $(row) : $(document);

                // Initialize Product Selects
                scope.find('.select2-ajax-product').select2({
                    theme: 'bootstrap-5',
                    ajax: {
                        url: '{{ route("products.search") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return { results: data };
                        },
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

                // Initialize Ingredient Selects
                scope.find('.select2-ajax-ingredient').select2({
                    theme: 'bootstrap-5',
                    ajax: {
                        url: '{{ route("ingredients.search") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return { q: params.term };
                        },
                        processResults: function (data) {
                            return { results: data };
                        },
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

                previewContainer.removeClass('d-none').html('<i class="fa fa-spinner fa-spin"></i> Checking components...');
                
                fetch("{{ url('products') }}/" + productId + "/recipe")
                    .then(res => res.json())
                    .then(data => {
                        if (data.has_recipe && data.ingredients && data.ingredients.length > 0) {
                            const items = data.ingredients.map(i => `${i.name} (${i.quantity}${i.unit})`).join(', ');
                            previewContainer.html(`<i class="fa fa-info-circle me-1"></i> <span class="text-dark">Includes:</span> ${items}`);
                        } else {
                            previewContainer.html('<small class="text-muted"><i class="fa fa-exclamation-circle me-1"></i> No sub-components defined.</small>');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        previewContainer.addClass('d-none');
                    });
            }

            // Update cost and unit based on selection
            function attachEvents(row) {
                const $row = $(row);
                const typeSelect = $row.find('.type-select');
                const ingredientSelect = $row.find('.ingredient-select');
                const productSelect = $row.find('.product-select');
                const previewContainer = $row.find('.component-preview');
                const costInput = $row.find('.cost-display');

                typeSelect.on('change', function () {
                    if (this.value === 'ingredient') {
                        ingredientSelect.next('.select2-container').show();
                        ingredientSelect.prop('disabled', false);
                        productSelect.next('.select2-container').hide();
                        productSelect.prop('disabled', true);
                        previewContainer.addClass('d-none');
                    } else {
                        ingredientSelect.next('.select2-container').hide();
                        ingredientSelect.prop('disabled', true);
                        productSelect.next('.select2-container').show();
                        productSelect.prop('disabled', false);
                        $row.find('.unit-select').val('piece');
                    }
                    // Reset values
                    ingredientSelect.val(null).trigger('change');
                    productSelect.val(null).trigger('change');
                    costInput.val('');
                    previewContainer.empty();
                });
            }

            // Initial bind
            initSelect2();
            $('.ingredient-row').each(function() {
                attachEvents(this);
                // Hide product select by default for the first row
                $(this).find('.product-select').next('.select2-container').hide();
            });

            $('#add-ingredient').on('click', function () {
                const template = $('.ingredient-row').first();
                // Destroy select2 before cloning to avoid ID conflicts and cloned behaviors
                const $newRow = template.clone();
                
                $newRow.attr('data-index', i);
                
                // Cleanup the clone
                $newRow.find('.select2-container').remove();
                $newRow.find('select').show().removeClass('select2-hidden-accessible').removeAttr('data-select2-id');

                // Update names
                $newRow.find('.type-select').attr('name', `ingredients[${i}][type]`);
                $newRow.find('.ingredient-select').attr('name', `ingredients[${i}][ingredient_id]`).val(null);
                $newRow.find('.product-select').attr('name', `ingredients[${i}][child_product_id]`).val(null);
                $newRow.find('input[placeholder="Qty"]').attr('name', `ingredients[${i}][quantity]`).val('');
                $newRow.find('.unit-select').attr('name', `ingredients[${i}][unit]`).val('kg');
                $newRow.find('.cost-display').attr('name', `ingredients[${i}][cost_per_unit]`).val('');

                $newRow.find('.type-select').val('ingredient');
                $newRow.find('.ingredient-select').show().prop('disabled', false);
                $newRow.find('.product-select').hide().prop('disabled', true);
                $newRow.find('.component-preview').addClass('d-none').empty();

                $('#ingredients-container').append($newRow);
                
                initSelect2($newRow);
                attachEvents($newRow);
                
                // Ensure initial visibility
                $newRow.find('.product-select').next('.select2-container').hide();
                
                i++;
            });

            $('#ingredients-container').on('click', '.remove-row', function () {
                if ($('.ingredient-row').length > 1) {
                    $(this).closest('.row').remove();
                } else {
                    alert('At least one ingredient or sub-product is required.');
                }
            });
        });
    </script>
@endsection