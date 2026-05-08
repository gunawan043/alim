<!-- ========== App Menu ========== -->
<style>
.app-menu .scrollbar-sidebar {
    height: calc(110vh - 70px - 80px) !important;
    overflow-y: auto !important;
}
.app-menu .scrollbar-sidebar .simplebar-content-wrapper,
.app-menu .scrollbar-sidebar .simplebar-content {
    overflow: unset !important;
    overflow-y: auto !important;
}
/* Active sidebar item — light mode */
.navbar-nav .nav-item .nav-link.active,
.navbar-nav .nav-item .nav-link.active i {
    color: #405189 !important;
    border-radius: 0.25rem;
    font-weight: 420 !important;
}
.navbar-nav .nav-item .nav-link.active{
    background: #40518923;
}
.navbar-menu .menu-dropdown .nav-item .nav-link.active,
.navbar-menu .menu-dropdown .nav-link.active {
    background: transparent !important;
}
.navbar-nav .nav-item .nav-link.active i {
    color: #405189 !important;
}
/* Active sidebar item — dark mode */
[data-bs-theme="dark"] .navbar-nav .nav-item .nav-link.active,
[data-bs-theme="dark"] .navbar-nav .nav-item .nav-link.active i {
    color: #fff !important;
    font-weight: 420 !important;
}
[data-bs-theme="dark"] .navbar-nav .nav-item .nav-link.active{
    background: #ffffff23;
}
[data-bs-theme="dark"] .navbar-nav .nav-item .nav-link.active i {
    color: #fff !important;
}
[data-bs-theme="dark"] .navbar-menu .menu-dropdown .nav-item .nav-link.active,
[data-bs-theme="dark"] .navbar-menu .menu-dropdown .nav-link.active {
    background: transparent !important;
}
</style>
<div class="app-menu navbar-menu">
    <!-- Logo area -->
    <div class="navbar-brand-box mt-2">
        <a href="{{ route('root') }}" class="mb-2 logo logo-dark">
            <span class="logo-sm"><img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="50"></span>
            <span class="logo-lg"><img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="70"></span>
        </a>
        <a href="{{ route('root') }}" class="mb-2 logo logo-light">
            <span class="logo-sm"><img src="{{ URL::asset('build/images/logo-sm.png') }}" alt="" height="50"></span>
            <span class="logo-lg"><img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="70"></span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <!-- Menu content -->
    <div data-simplebar class="scrollbar-sidebar">
        <div class="container-fluid mt-3">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav" style="padding-bottom: 50px">
                @php
                    $user = auth()->user();
                @endphp

                @if($user->hasRole('Super Admin'))
                    @include('layouts.sidebar.super-admin')
                @elseif($user->hasRole('GTK'))
                    @include('layouts.sidebar.gtk')
                @elseif($user->hasRole('Admin Tata Usaha'))
                    @include('layouts.sidebar.admin-tu')
                @elseif($user->hasRole('Personalia'))
                    @include('layouts.sidebar.personalia')
                @elseif($user->hasRole('Wakil Kepala Sekolah'))
                    @include('layouts.sidebar.waka')
                @elseif($user->hasRole('Asrama') || $user->hasRole('Admin Asrama'))
                    @include('layouts.sidebar.asrama')
                @else
                    <li class="nav-item"><span class="nav-link text-muted px-3">Tidak ada menu untuk role ini</span></li>
                @endif
            </ul>
        </div>
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Overlay: close sidebar on mobile -->
<div class="vertical-overlay"></div>
