<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'POS Master') }}</title>
    <link href="https://fonts.bunny.net/css?family=Outfit:400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-secondary: #64748b;
            --sidebar-bg: #ffffff;
            --border-color: #e2e8f0;
        }

        [data-theme="dark"] {
            --bg-color: #0f172a;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #2e1065 100%);
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-secondary: #cbd5e1;
            --sidebar-bg: #1e293b;
            --border-color: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient, var(--bg-color));
            color: var(--text-main);
            transition: all 0.3s ease;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Particle Overlay */
        .bg-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            overflow: hidden;
        }

        [data-theme="dark"] .bg-particles {
            display: block;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            animation: float-particle var(--duration) infinite ease-in-out;
            pointer-events: none;
        }

        .particle-dots {
            background-image: radial-gradient(rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 10px 10px;
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        @keyframes float-particle {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(30px, -50px) rotate(10deg);
            }

            66% {
                transform: translate(-20px, 20px) rotate(-10deg);
            }
        }

        /* Premium Animated Background - Enhanced */
        .bg-animated-layers {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
            background: var(--bg-color);
        }

        .bg-blob {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0) 70%);
            filter: blur(80px);
            border-radius: 50%;
            animation: move 25s infinite alternate ease-in-out;
            opacity: 0.8;
        }

        .bg-blob-2 {
            top: -100px;
            right: -100px;
            background: radial-gradient(circle, rgba(236, 72, 153, 0.15) 0%, rgba(236, 72, 153, 0) 70%);
            animation-duration: 30s;
            animation-delay: -5s;
        }

        .bg-blob-3 {
            bottom: -150px;
            left: 10%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.18) 0%, rgba(59, 130, 246, 0) 70%);
            animation-duration: 35s;
            animation-delay: -10s;
        }

        @keyframes move {
            0% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(15vw, 20vh) scale(1.2);
            }

            66% {
                transform: translate(-10vw, 10vh) scale(0.8);
            }

            100% {
                transform: translate(0, 0) scale(1);
            }
        }

        .text-main {
            color: var(--text-main) !important;
        }

        /* Dark Mode specific enhancements for blobs */
        [data-theme="dark"] .bg-blob {
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, rgba(99, 102, 241, 0) 70%);
            opacity: 0.6;
        }

        [data-theme="dark"] .bg-blob-2 {
            background: radial-gradient(circle, rgba(236, 72, 153, 0.1) 0%, rgba(236, 72, 153, 0) 70%);
        }

        [data-theme="dark"] .bg-blob-3 {
            background: radial-gradient(circle, rgba(59, 130, 246, 0.12) 0%, rgba(59, 130, 246, 0) 70%);
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            background: var(--sidebar-bg);
            border-inline-end: 1px solid var(--border-color);
            padding-top: 20px;
            z-index: 1000;
            overflow-y: auto;
            /* Enable scrolling */
            scrollbar-width: thin;
            /* Firefox scrollbar styling */
        }

        .sidebar-brand {
            padding: 0 25px 30px;
            font-size: 22px;
            font-weight: 700;
            color: #6366f1;
        }

        .nav-link {
            padding: 12px 25px;
            color: var(--text-secondary);
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }

        .nav-link i {
            width: 30px;
            font-size: 18px;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
            border-inline-start: 4px solid #6366f1;
        }

        .main-content {
            margin-inline-start: 260px;
            padding: 30px;
        }

        .card {
            border: none;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            color: var(--text-main);
        }

        .table {
            color: var(--text-main);
        }

        .dropdown-menu {
            background: var(--card-bg);
            border-color: var(--border-color);
        }

        .dropdown-item {
            color: var(--text-main);
        }

        .dropdown-item:hover {
            background: rgba(99, 102, 241, 0.1);
        }

        .btn-header {
            background: var(--card-bg);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            transition: all 0.3s;
        }

        .btn-header:hover {
            background: var(--bg-color);
            color: #6366f1;
            border-color: #6366f1;
        }

        /* Global Dark Mode Overrides */
        [data-theme="dark"] .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-main);
            --bs-table-border-color: var(--border-color);
            --bs-table-striped-bg: rgba(255, 255, 255, 0.02);
            --bs-table-hover-bg: rgba(255, 255, 255, 0.04);
        }

        [data-theme="dark"] .table-light {
            --bs-table-bg: rgba(255, 255, 255, 0.05);
            --bs-table-color: var(--text-main);
            border-bottom-color: var(--border-color);
        }

        [data-theme="dark"] .modal-content {
            background-color: var(--card-bg);
            color: var(--text-main);
            border-color: var(--border-color);
        }

        [data-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-footer {
            border-color: var(--border-color);
        }

        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: var(--bg-color) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .form-control::placeholder {
            color: var(--text-secondary) !important;
            opacity: 0.7;
        }

        [data-theme="dark"] .form-control:focus {
            background-color: var(--bg-color);
            border-color: #6366f1;
            color: var(--text-main);
        }

        [data-theme="dark"] .text-dark {
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .text-muted {
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] .text-success {
            color: #4ade80 !important;
            /* Vibrant green for dark mode */
        }

        [data-theme="dark"] .text-danger {
            color: #f87171 !important;
            /* Vibrant red for dark mode */
        }

        [data-theme="dark"] .btn-light {
            background-color: var(--border-color);
            color: var(--text-main);
            border: none;
        }

        [data-theme="dark"] .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        [data-theme="dark"] .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        [data-theme="dark"] code {
            background-color: rgba(236, 72, 153, 0.1);
            color: #ec4899;
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* Improved Badges for Dark Mode */
        [data-theme="dark"] .bg-success-subtle {
            background-color: rgba(16, 185, 129, 0.15) !important;
            color: #4ade80 !important;
        }

        [data-theme="dark"] .bg-danger-subtle {
            background-color: rgba(239, 68, 68, 0.15) !important;
            color: #f87171 !important;
        }

        [data-theme="dark"] .bg-info-subtle {
            background-color: rgba(14, 165, 233, 0.15) !important;
            color: #38bdf8 !important;
        }

        [data-theme="dark"] .bg-warning-subtle {
            background-color: rgba(245, 158, 11, 0.15) !important;
            color: #fbbf24 !important;
        }

        [data-theme="dark"] .bg-light {
            background-color: rgba(255, 255, 255, 0.1) !important;
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .bg-secondary-subtle {
            background-color: rgba(107, 114, 128, 0.15) !important;
            color: #9ca3af !important;
        }

        [data-theme="dark"] .bg-white,
        [data-theme="dark"] .card-header.bg-white {
            background-color: var(--card-bg) !important;
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .input-group-text.bg-light {
            background-color: rgba(255, 255, 255, 0.05) !important;
            border-color: var(--border-color) !important;
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] .breadcrumb-item.active,
        [data-theme="dark"] .breadcrumb-item {
            color: var(--text-secondary) !important;
        }

        [data-theme="dark"] .breadcrumb-item::before {
            color: var(--border-color) !important;
        }

        [data-theme="dark"] .list-group-item {
            background-color: transparent !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .text-dark {
            color: var(--text-main) !important;
        }
        
        [data-theme="dark"] .text-main {
            color: var(--text-main) !important;
        }

        /* Global Select Dark Mode Fix */
        [data-theme="dark"] select.form-select option {
            background-color: var(--sidebar-bg);
            color: var(--text-main);
        }

        /* Select2 Dark Mode Fixes */
        [data-theme="dark"] .select2-container--default .select2-selection--single {
            background-color: var(--bg-color) !important;
            border-color: var(--border-color) !important;
        }

        [data-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .select2-dropdown {
            background-color: var(--sidebar-bg) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .select2-results__option {
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #6366f1 !important;
            color: white !important;
        }

        [data-theme="dark"] .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: rgba(99, 102, 241, 0.2) !important;
        }

        [data-theme="dark"] .select2-search--dropdown .select2-search__field {
            background-color: var(--bg-color) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }
    </style>
    @yield('extra_css')
</head>

<body data-theme="{{ session('theme', 'light') }}">
    <!-- Animated background elements -->
    <div class="bg-animated-layers">
        <div class="bg-particles">
            {{-- Bokeh Circles --}}
            <div class="particle"
                style="width: 300px; height: 300px; top: -50px; left: 10%; --duration: 25s; opacity: 0.1;"></div>
            <div class="particle"
                style="width: 200px; height: 200px; top: 15%; left: 85%; --duration: 30s; opacity: 0.08;"></div>
            <div class="particle"
                style="width: 400px; height: 400px; top: 40%; left: 30%; --duration: 45s; opacity: 0.05;"></div>
            <div class="particle"
                style="width: 150px; height: 150px; top: 70%; left: 60%; --duration: 20s; opacity: 0.12;"></div>
            <div class="particle"
                style="width: 250px; height: 250px; bottom: 5%; right: 10%; --duration: 35s; opacity: 0.07;"></div>
            <div class="particle"
                style="width: 350px; height: 350px; top: 60%; left: -5%; --duration: 50s; opacity: 0.04;"></div>

            {{-- Dotted Mesh Circles --}}
            <div class="particle particle-dots"
                style="width: 180px; height: 180px; top: 10%; left: 70%; --duration: 32s;"></div>
            <div class="particle particle-dots"
                style="width: 140px; height: 140px; top: 55%; left: 15%; --duration: 28s;"></div>
            <div class="particle particle-dots"
                style="width: 220px; height: 220px; bottom: 20%; left: 45%; --duration: 40s;"></div>
            <div class="particle particle-dots"
                style="width: 100px; height: 100px; top: 30%; left: 5%; --duration: 25s;"></div>
            <div class="particle particle-dots"
                style="width: 160px; height: 160px; top: 80%; left: 80%; --duration: 35s;"></div>
            <div class="particle particle-dots"
                style="width: 130px; height: 130px; top: 5%; left: 35%; --duration: 30s;"></div>
        </div>
    </div>

    <div id="app">
        @auth
            <aside class="sidebar">
                <div class="sidebar-brand">
                    <i class="fa-solid fa-utensils me-2"></i> POS MASTER
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link {{ request()->is('home') ? 'active' : '' }}" href="/home">
                        <i class="fa-solid fa-house"></i> {{ __('messages.overview') }}
                    </a>
                    <a class="nav-link {{ request()->routeIs('branches.*') ? 'active' : '' }}"
                        href="{{ route('branches.index') }}"><i class="fa-solid fa-code-branch"></i>
                        {{ __('messages.branches') }}</a>
                    <a class="nav-link {{ request()->routeIs('tables.*') ? 'active' : '' }}"
                        href="{{ route('tables.index') }}"><i class="fa-solid fa-chair"></i>
                        {{ __('messages.tables_zones') }}</a>
                    <a class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}"
                        href="{{ route('categories.index') }}"><i class="fa-solid fa-folder"></i> Categories</a>
                    <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}"
                        href="{{ route('products.index') }}"><i class="fa-solid fa-layer-group"></i>
                        {{ __('messages.menu_products') }}</a>
                    <a class="nav-link {{ request()->routeIs('employees.*') ? 'active' : '' }}"
                        href="{{ route('employees.index') }}"><i class="fa-solid fa-users"></i>
                        {{ __('messages.employees') }}</a>
                    <a class="nav-link {{ request()->routeIs('drivers.*') ? 'active' : '' }}"
                        href="{{ route('drivers.index') }}"><i class="fa-solid fa-truck"></i>
                        {{ __('messages.drivers') }}</a>
                    <a class="nav-link {{ request()->routeIs('accounting.*') ? 'active' : '' }}"
                        href="{{ route('accounting.chart') }}"><i class="fa-solid fa-sitemap"></i>
                        {{ __('messages.accounts') }}</a>
                    <a class="nav-link {{ request()->routeIs('vouchers.*') ? 'active' : '' }}"
                        href="{{ route('vouchers.index') }}">
                        <i class="fa-solid fa-receipt"></i> Vouchers</a>
                    <a class="nav-link {{ request()->is('accounting/accounting-reports') ? 'active' : '' }}"
                        href="{{ route('accounting.reports') }}">
                        <i class="fa-solid fa-scale-balanced"></i> Accounting Reports</a>
                    <a class="nav-link {{ request()->routeIs('accounting.entity-configs') ? 'active' : '' }}"
                        href="{{ route('accounting.entity-configs') }}">
                        <i class="fa-solid fa-gears"></i> Entity Configs</a>
                    {{-- 
                    <a class="nav-link {{ request()->routeIs('accounting.scenarios.*') ? 'active' : '' }}"
                        href="{{ route('accounting.scenarios.index') }}">
                        <i class="fa-solid fa-bolt"></i> Scenarios Dashboard</a>
                    --}}
                    <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}"
                        href="{{ route('suppliers.index') }}"><i class="fa-solid fa-truck-field"></i>
                        {{ __('messages.suppliers') }}</a>
                    <a class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}"
                        href="{{ route('inventory.index') }}"><i class="fa-solid fa-box-open"></i>
                        {{ __('messages.inventory') }}</a>

                    <a class="nav-link {{ request()->routeIs('ingredients.*') ? 'active' : '' }}"
                        href="{{ route('ingredients.index') }}">
                        <i class="fa-solid fa-wheat-awn"></i> Raw Materials / Ingredients
                    </a>

                    <a class="nav-link {{ request()->routeIs('purchase_invoices.*') ? 'active' : '' }}"
                        href="{{ route('purchase_invoices.index') }}">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Purchase Bills
                    </a>

                    <a class="nav-link {{ request()->routeIs('recipes.*') ? 'active' : '' }}"
                        href="{{ route('recipes.index') }}">
                        <i class="fa-solid fa-scroll"></i> Recipes
                    </a>

                    <a class="nav-link {{ request()->routeIs('productions.*') ? 'active' : '' }}"
                        href="{{ route('productions.index') }}">
                        <i class="fa-solid fa-industry"></i> Production
                    </a>

                    <a class="nav-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}"
                        href="{{ route('expenses.index') }}">
                        <i class="fa-solid fa-money-bill-wave"></i> Expenses
                    </a>

                    <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}"
                        href="{{ route('roles.index') }}">
                        <i class="fa-solid fa-user-shield"></i> Roles &amp; Permissions
                    </a>
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}"
                        href="{{ route('reports.index') }}"><i class="fa-solid fa-chart-line"></i>
                        {{ __('messages.reports') }}</a>
                    <a class="nav-link {{ request()->routeIs('system.settings') ? 'active' : '' }}"
                        href="{{ route('system.settings') }}">
                        <i class="fa-solid fa-screwdriver-wrench"></i> System Settings
                    </a>
                </nav>
            </aside>
        @endauth

        <main class="main-content" style="{{ Auth::check() ? '' : 'margin-inline-start: 0;' }}">
            <header class="d-flex justify-content-between align-items-center mb-5">
                <h2 class="fw-bold">@yield('title', __('messages.dashboard'))</h2>

                <div class="d-flex align-items-center gap-3">
                    <!-- Language Switcher -->
                    <div class="dropdown">
                        <button class="btn btn-header shadow-sm dropdown-toggle rounded-pill px-3" type="button"
                            data-bs-toggle="dropdown">
                            <i class="fa-solid fa-globe me-1 text-primary"></i> {{ strtoupper(app()->getLocale()) }}
                        </button>
                        <ul class="dropdown-menu shadow">
                            <li><a class="dropdown-item" href="/lang/en">English</a></li>
                            <li><a class="dropdown-item" href="/lang/ar">العربية</a></li>
                        </ul>
                    </div>

                    <!-- Theme Toggle -->
                    <a href="/theme-toggle" class="btn-theme-toggle shadow-sm">
                        <i class="fa-solid fa-{{ session('theme') == 'dark' ? 'sun' : 'moon' }}"></i>
                    </a>

                    <!-- Profile -->
                    @auth
                        <div class="dropdown">
                            <button class="btn btn-header shadow-sm dropdown-toggle rounded-pill px-4" type="button"
                                data-bs-toggle="dropdown">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=6366f1&color=fff"
                                    class="rounded-circle me-2" width="30">
                                <span class="fw-semibold">{{ Auth::user()->name }}</span>
                            </button>
                            <ul class="dropdown-menu border-0 shadow">
                                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user me-2"></i> Profile</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fa-solid fa-right-from-bracket me-2"></i> {{ __('messages.logout') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary rounded-pill px-4">Login</a>
                    @endauth
                </div>
            </header>

            @yield('content')

            <!-- Toast Container -->
            <div class="toast-container position-fixed bottom-0 end-0 p-3">
                <div id="liveToast" class="toast align-items-center border-0 shadow-lg" role="alert"
                    aria-live="assertive" aria-atomic="true" style="border-radius: 12px;">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2">
                            <i id="toastIcon" class="fa-solid fa-circle-info"></i>
                            <span id="toastMessage"></span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                            aria-label="Close"></button>
                    </div>
                </div>
            </div>
        </main>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
        <script>
            window.confirmDelete = function(formId, itemName = 'this item') {
                Swal.fire({
                    title: '{{ app()->getLocale() == "ar" ? "هل أنت متأكد؟" : "Are you sure?" }}',
                    text: '{{ app()->getLocale() == "ar" ? "سيتم حذف" : "You are about to delete" }}: ' + itemName,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '{{ app()->getLocale() == "ar" ? "نعم، احذف!" : "Yes, delete it!" }}',
                    cancelButtonText: '{{ app()->getLocale() == "ar" ? "إلغاء" : "Cancel" }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById(formId).submit();
                    }
                })
            }

            window.showToast = function (message, type = 'success') {
                const toastEl = document.getElementById('liveToast');
                const toastMessage = document.getElementById('toastMessage');
                const toastIcon = document.getElementById('toastIcon');

                toastMessage.textContent = message;

                // Reset classes
                toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white');
                toastIcon.className = 'fa-solid me-2 ';

                if (type === 'success') {
                    toastEl.classList.add('bg-success', 'text-white');
                    toastIcon.classList.add('fa-circle-check');
                } else if (type === 'error') {
                    toastEl.classList.add('bg-danger', 'text-white');
                    toastIcon.classList.add('fa-circle-exclamation');
                } else {
                    toastEl.classList.add('bg-primary', 'text-white');
                    toastIcon.classList.add('fa-circle-info');
                }

                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            }

            $(document).ready(function () {
                // Initialize Select2 for modals
                $('.modal').on('shown.bs.modal', function () {
                    $(this).find('.select2-modal').select2({
                        dropdownParent: $(this)
                    });
                });
            });
        </script>
        @yield('scripts')
    </div>
</body>
</html>