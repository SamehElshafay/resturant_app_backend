@extends('layouts.app')

@section('title', __('messages.recipes'))

@section('content')
    <div class="card p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <h4 class="fw-bold mb-0">{{ __('messages.recipes') }}</h4>
            
            <div class="d-flex flex-grow-1 justify-content-md-end gap-3">
                <form action="{{ route('recipes.index') }}" method="GET" class="flex-grow-1" style="max-width: 400px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" 
                            placeholder="{{ __('messages.search_placeholder') ?? 'Search recipes, products...' }}" 
                            value="{{ request('search') }}">
                        @if(request('search'))
                            <a href="{{ route('recipes.index') }}" class="btn btn-outline-secondary border-start-0">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </form>

                <a href="{{ route('recipes.create') }}" class="btn btn-primary rounded-pill px-4 flex-shrink-0">
                    <i class="fa-solid fa-plus me-2"></i> {{ __('messages.add_recipe') }}
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive" id="recipe-table-wrapper">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 border-0 rounded-start">{{ __('messages.product') }}</th>
                        <th class="border-0">{{ __('messages.current_stock') }}</th>
                        <th class="border-0">{{ __('messages.cost') }}</th>
                        <th class="border-0">{{ __('messages.ingredients_count') }}</th>
                        <th class="pe-3 border-0 rounded-end text-end">{{ __('messages.actions') }}</th>
                    </tr>
                    <tr class="bg-white border-top">
                        <th class="ps-3 py-2 border-0">
                            <input type="text" name="product_name" class="form-control form-control-sm column-filter" placeholder="Filter..." value="{{ request('product_name') }}">
                        </th>
                        <th class="py-2 border-0">
                            <div class="d-flex gap-1">
                                <input type="number" name="min_stock" class="form-control form-control-sm column-filter" placeholder="Min" value="{{ request('min_stock') }}">
                                <input type="number" name="max_stock" class="form-control form-control-sm column-filter" placeholder="Max" value="{{ request('max_stock') }}">
                            </div>
                        </th>
                        <th class="py-2 border-0">
                            <div class="d-flex gap-1">
                                <input type="number" name="min_cost" class="form-control form-control-sm column-filter" placeholder="Min" value="{{ request('min_cost') }}">
                                <input type="number" name="max_cost" class="form-control form-control-sm column-filter" placeholder="Max" value="{{ request('max_cost') }}">
                            </div>
                        </th>
                        <th class="py-2 border-0">
                            <div class="d-flex gap-1">
                                <input type="number" name="min_ingredients" class="form-control form-control-sm column-filter" placeholder="Min" value="{{ request('min_ingredients') }}">
                                <input type="number" name="max_ingredients" class="form-control form-control-sm column-filter" placeholder="Max" value="{{ request('max_ingredients') }}">
                            </div>
                        </th>
                        <th class="pe-3 py-2 border-0 text-end">
                            <a href="{{ route('recipes.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">Reset</a>
                        </th>
                    </tr>
                </thead>
                <tbody id="recipe-table-body">
                    @forelse($recipes as $recipe)
                        <tr>
                            <td class="fw-bold ps-3">
                                <div class="d-flex flex-column">
                                    <span>
                                        @if($recipe->product)
                                            {{ $recipe->product->name_ar ?: ($recipe->product->name_en ?: $recipe->product->name) }}
                                        @else
                                            <span class="text-danger">Product Not Found (ID: {{ $recipe->product_id }})</span>
                                        @endif
                                    </span>
                                    @if($recipe->name_ar || $recipe->name_en)
                                        <small class="text-muted fw-normal">
                                            {{ $recipe->name_ar ?: $recipe->name_en }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ number_format($recipe->product->stock_quantity ?? 0, 2) }}
                                </span>
                            </td>
                            <td class="text-success fw-bold">${{ number_format($recipe->cost, 2) }}</td>
                            <td>{{ $recipe->ingredients->count() }}</td>
                            <td class="text-end pe-3">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('recipes.show', $recipe->id) }}" class="btn btn-sm btn-info text-white rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <form id="delete-recipe-{{ $recipe->id }}" action="{{ route('recipes.destroy', $recipe->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-light text-danger rounded-circle" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;"
                                            onclick="confirmDelete('delete-recipe-{{ $recipe->id }}', '{{ $recipe->product->name_en ?? $recipe->product->name ?? 'this recipe' }}')">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted italic">
                                @if(request('search'))
                                    {{ __('messages.no_results_found') ?? 'No recipes found matching your search.' }}
                                @else
                                    {{ __('messages.no_recipes_yet') ?? 'No recipes added yet.' }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4" id="pagination-wrapper">
                {{ $recipes->links() }}
            </div>
        </div>
    </div>

    @section('scripts')
    <script>
        $(document).ready(function() {
            let searchTimer;
            const filters = $('input[name], .column-filter');
            const wrapper = $('#recipe-table-wrapper');

            // Listen to any input change
            $(document).on('input', 'input[name], .column-filter', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    fetchRecipes();
                }, 500); // 500ms debounce
            });

            function fetchRecipes() {
                // Gather all filter values
                let params = {};
                $('input[name], .column-filter').each(function() {
                    const name = $(this).attr('name');
                    const val = $(this).val();
                    if (val) params[name] = val;
                });

                // Update URL without reload
                const url = new URL(window.location.origin + window.location.pathname);
                Object.keys(params).forEach(key => url.searchParams.set(key, params[key]));
                window.history.pushState({}, '', url);

                // Show loading state
                wrapper.css('opacity', '0.5');

                $.ajax({
                    url: url.toString(),
                    success: function(data) {
                        const html = $(data).find('#recipe-table-wrapper').html();
                        wrapper.html(html);
                        wrapper.css('opacity', '1');
                    },
                    error: function() {
                        wrapper.css('opacity', '1');
                        showToast('Error refreshing data', 'error');
                    }
                });
            }

            // Handle pagination clicks via AJAX
            $(document).on('click', '#pagination-wrapper a', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                window.history.pushState({}, '', url);
                
                wrapper.css('opacity', '0.5');
                $.ajax({
                    url: url,
                    success: function(data) {
                        const html = $(data).find('#recipe-table-wrapper').html();
                        wrapper.html(html);
                        wrapper.css('opacity', '1');
                        $('html, body').animate({ scrollTop: 0 }, 'slow');
                    }
                });
            });
        });
    </script>
    @endsection
@endsection