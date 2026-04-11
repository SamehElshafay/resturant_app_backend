@extends('layouts.app')

@section('title', 'Categories Management')

@section('content')
<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold text-dark mb-1">Product Categories</h2>
            <p class="text-secondary mb-0">Manage your menu categories and printing destinations</p>
        </div>
        <button class="btn btn-primary btn-lg rounded-pill shadow-sm px-4 d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fa-solid fa-plus-circle fs-5 me-2"></i>
            <span>Add New Category</span>
        </button>
    </div>

    <!-- Alert Section -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible border-0 shadow-sm fade show mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa-solid fa-check-circle fs-5 me-3"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Categories Grid -->
    @if($categories->count() > 0)
        <div class="row g-4">
            @foreach($categories as $category)
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="category-card shadow-sm h-100 border-0 rounded-4 overflow-hidden bg-white position-relative">
                        <!-- Action Menu -->
                        <div class="position-absolute top-0 end-0 p-3" style="z-index: 5;">
                            <div class="dropdown">
                                <button class="btn btn-white btn-sm rounded-circle shadow-sm action-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical text-dark"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2 rounded-3" style="min-width: 160px;">
                                    <li>
                                        <button type="button" class="dropdown-item rounded-2 py-2 mb-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $category->id }}">
                                            <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Settings
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item rounded-2 py-2 text-danger" 
                                            onclick="confirmDelete('delete-form-{{ $category->id }}', '{{ $category->name }}')">
                                            <i class="fa-solid fa-trash-can me-2"></i> Delete Category
                                        </button>
                                        <form id="delete-form-{{ $category->id }}" action="{{ route('categories.destroy', $category->id) }}" method="POST" class="d-none">
                                            @csrf @method('DELETE')
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <a href="{{ route('products.index') }}?category_id={{ $category->id }}" class="text-decoration-none">
                            <div class="image-wrapper position-relative overflow-hidden" style="height: 200px;">
                                @if($category->image)
                                    <img src="{{ asset('storage/' . $category->image) }}" class="w-100 h-100 object-fit-cover transition-transform" alt="{{ $category->name }}">
                                @else
                                    <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center">
                                        <i class="fa-solid fa-folder-open fa-3x text-secondary opacity-25"></i>
                                    </div>
                                @endif
                                <div class="image-overlay"></div>
                                <div class="badge bg-blur position-absolute bottom-0 start-0 m-3 px-3 py-2 rounded-pill shadow-sm">
                                    <span class="text-white small fw-semibold">{{ $category->products->count() }} Products</span>
                                </div>
                            </div>
                            
                            <div class="card-body p-4 text-center">
                                <h5 class="fw-bold text-dark mb-1 title-text">{{ $category->name }}</h5>
                                <p class="text-secondary small mb-0">{{ $category->products->count() }} Products</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Edit Modal (Stays inside loop but outside the visual card link) -->
                <div class="modal fade action-modal" id="editModal{{ $category->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <form action="{{ route('categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf @method('PUT')
                                <div class="modal-header border-0 p-4">
                                    <h5 class="fw-bold mb-0">Edit Category Settings</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4 pt-0">
                                    <div class="text-center mb-4">
                                        <div class="position-relative d-inline-block">
                                            @if($category->image)
                                                <img src="{{ asset('storage/' . $category->image) }}" class="rounded-4 shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">
                                            @else
                                                <div class="rounded-4 bg-light d-flex align-items-center justify-content-center" style="width: 120px; height: 120px;">
                                                    <i class="fa-solid fa-image fa-2x text-secondary opacity-50"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-floating mb-3">
                                                <input type="text" name="name" class="form-control rounded-3" id="name{{$category->id}}" value="{{ $category->name }}" placeholder="Category Name" required>
                                                <label for="name{{$category->id}}">Category Name</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-bold text-secondary">Update Image</label>
                                            <input type="file" name="image" class="form-control rounded-3">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 p-4 pt-0">
                                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Update Category</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-5 bg-white rounded-4 shadow-sm border mt-4">
            <div class="py-5">
                <i class="fa-solid fa-folder-plus fa-4x text-light-emphasis mb-3"></i>
                <h4 class="text-secondary">No categories yet</h4>
                <p class="text-secondary mb-4">Start by adding your first product category</p>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    Add Now
                </button>
            </div>
        </div>
    @endif
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header border-0 p-4">
                    <h5 class="fw-bold mb-0">Create New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <div class="row g-3">
                        <div class="col-12 text-center mb-3">
                            <div class="bg-primary bg-opacity-10 p-4 rounded-4 d-inline-block">
                                <i class="fa-solid fa-folder-plus fa-3x text-primary"></i>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <input type="text" name="name" class="form-control rounded-3" placeholder="Category Name" required>
                                <label>Category Name (e.g. Cold Drinks)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">Upload Icon/Image</label>
                            <input type="file" name="image" class="form-control rounded-3">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    body { background-color: #f8f9fa; }
    .category-card { transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.3s ease; }
    .category-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important; }
    .category-card:hover img { transform: scale(1.1); }
    .action-btn { width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.9); backdrop-filter: blur(4px); }
    .bg-blur { background: rgba(0,0,0,0.4); backdrop-filter: blur(8px); }
    .image-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, rgba(0,0,0,0) 60%, rgba(0,0,0,0.4) 100%); pointer-events: none; }
    .transition-transform { transition: transform 0.8s ease; }
    .form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1); }
    .title-text { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
</style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const actionModals = document.querySelectorAll('.action-modal');
            actionModals.forEach(modal => {
                modal.addEventListener('show.bs.modal', function (event) {
                    event.stopPropagation();
                });
            });
        });
    </script>
@endsection