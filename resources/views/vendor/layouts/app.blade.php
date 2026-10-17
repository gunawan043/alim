<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | Vendor Portal — {{ config('app.name', 'ALIM') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .vendor-sidebar {
            width: 250px;
            background: white;
            min-height: 100vh;
            border-right: 1px solid #e2e8f0;
            position: fixed;
            left: 0; top: 0; bottom: 0;
            z-index: 100;
        }
        .vendor-sidebar .brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            font-weight: 700;
            color: #005981;
        }
        .vendor-sidebar .nav-link {
            color: #475569;
            padding: 0.6rem 1.5rem;
            border-left: 3px solid transparent;
            font-size: 0.9rem;
        }
        .vendor-sidebar .nav-link:hover,
        .vendor-sidebar .nav-link.active {
            background: #f1f5f9;
            color: #005981;
            border-left-color: #005981;
        }
        .vendor-sidebar .nav-link i { margin-right: 0.5rem; }
        .vendor-main {
            margin-left: 250px;
            min-height: 100vh;
        }
        .vendor-topbar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .vendor-topbar .user-info { font-size: 0.85rem; }
        @media (max-width: 768px) {
            .vendor-sidebar { display: none; }
            .vendor-main { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <aside class="vendor-sidebar">
        <div class="brand"><i class="ri-building-4-line me-2"></i>Vendor Portal</div>
        <nav class="nav flex-column mt-3">
            <a class="nav-link {{ request()->routeIs('vendor.dashboard') ? 'active' : '' }}" href="{{ route('vendor.dashboard') }}">
                <i class="ri-dashboard-line"></i>Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('vendor.procurement.*') ? 'active' : '' }}" href="{{ route('vendor.procurement.index') }}">
                <i class="ri-shopping-cart-2-line"></i>Pengadaan
            </a>
            <a class="nav-link {{ request()->routeIs('vendor.orders.*') ? 'active' : '' }}" href="{{ route('vendor.orders.index') }}">
                <i class="ri-truck-line"></i>Pesanan & Pengiriman
            </a>
            <a class="nav-link {{ request()->routeIs('vendor.invoices.*') ? 'active' : '' }}" href="{{ route('vendor.invoices.index') }}">
                <i class="ri-file-text-line"></i>Faktur
            </a>
            <a class="nav-link {{ request()->routeIs('vendor.performance') ? 'active' : '' }}" href="{{ route('vendor.performance') }}">
                <i class="ri-line-chart-line"></i>Performa
            </a>
            <div class="mt-auto pt-4 px-3">
                <form method="POST" action="{{ route('vendor.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="ri-logout-box-line me-1"></i>Keluar</button>
                </form>
            </div>
        </nav>
    </aside>

    <div class="vendor-main">
        <div class="vendor-topbar">
            <h6 class="mb-0 text-muted">@yield('title')</h6>
            <div class="user-info">
                <i class="ri-building-line me-1"></i>{{ auth()->guard('vendor')->user()->name }}
            </div>
        </div>

        <main class="p-4">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
