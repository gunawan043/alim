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
            <span class="logo-sm"><img src="{{ URL::asset('build/images/alim-sm-light.png') }}" alt="" height="50"></span>
            <span class="logo-lg"><img src="{{ URL::asset('build/images/alim-dark-name.png') }}" alt="" height="70"></span>
        </a>
        <a href="{{ route('root') }}" class="mb-2 logo logo-light">
            <span class="logo-sm"><img src="{{ URL::asset('build/images/alim-sm-light.png') }}" alt="" height="50"></span>
            <span class="logo-lg"><img src="{{ URL::asset('build/images/alim-light-name.png') }}" alt="" height="70"></span>
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
                    $isSystemAdmin = method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();
                    $viewAsRole = null;
                    $canUseViewAs = $isSystemAdmin
                        || (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('impersonate_role'));
                    if ($canUseViewAs) {
                        $viewAsRole = app(\App\Services\ViewAsService::class)->getCurrentViewRole();
                    }
                    $isViewingAs = $viewAsRole !== null;
                @endphp

                {{-- When viewing-as, render the sidebar for the impersonated role so SA/SuperAdmin can preview that role's nav --}}
                @if($isViewingAs)
                    @php
                        $viewAsRoleModel = \App\Models\Role::where('name', $viewAsRole)->first();
                        $viewAsPerms = $viewAsRoleModel
                            ? $viewAsRoleModel->permissions->pluck('name')->toArray()
                            : [];
                        $has = fn ($p) => in_array($p, $viewAsPerms);
                    @endphp
                    {{-- Asrama: role-name disambiguation first (Kepala Asrama & Admin Asrama
                         share most permissions) --}}
                    @if($viewAsRole === 'Kepala Asrama')
                        @include('layouts.sidebar.kepala-asrama')
                    @elseif($viewAsRole === 'Admin Asrama')
                        @include('layouts.sidebar.admin-asrama')
                    @elseif($viewAsRole === 'Admin Pendidikan')
                        @include('layouts.sidebar.admin-pendidikan')
                    {{-- UKS Roles --}}
                    @elseif($viewAsRole === 'Kepala UKS')
                        @include('layouts.sidebar.kepala-uks')
                    @elseif($viewAsRole === 'Admin UKS')
                        @include('layouts.sidebar.admin-uks')
                    @elseif($viewAsRole === 'Admin UKS Putra')
                        @include('layouts.sidebar.admin-uks-putra')
                    @elseif($viewAsRole === 'Admin UKS Putri')
                        @include('layouts.sidebar.admin-uks-putri')
                    @elseif($viewAsRole === 'UKS')
                        @include('layouts.sidebar.uks')
                    {{-- Existing Admin Kesehatan retains priority --}}
                    @elseif($viewAsRole === 'Admin Kesehatan')
                        @include('layouts.sidebar.admin-kesehatan')
                    @elseif($viewAsRole === 'Wali Asrama')
                        @include('layouts.sidebar.wali-asrama')
                    @elseif($has('menu-super-admin-sidebar'))
                        @include('layouts.sidebar.super-admin')
                    @elseif($has('menu-admin-tu-sidebar'))
                        @include('layouts.sidebar.admin-tu')
                    @elseif($has('menu-gtk-sidebar'))
                        @include('layouts.sidebar.gtk')
                    @elseif($has('menu-admin-sarpras-sidebar') || $has('menu-sarpras-sidebar'))
                        @include('layouts.sidebar.gtk-sarpras')
                    @elseif($has('menu-personalia-sidebar'))
                        @include('layouts.sidebar.personalia')
                    @elseif($has('menu-wakil-kepala-sekolah-sidebar'))
                        @include('layouts.sidebar.waka')
                    @elseif($has('menu-asrama-sidebar'))
                        @include('layouts.sidebar.asrama-ro')
                    @else
                        <li class="nav-item"><span class="nav-link text-muted px-3">Role '{{ $viewAsRole }}' belum punya menu sidebar.</span></li>
                    @endif
                @elseif($isSystemAdmin || $user->hasPermissionTo('menu-super-admin-sidebar'))
                    @include('layouts.sidebar.super-admin')
                @elseif($user->hasRole('Kepala Asrama'))
                    @include('layouts.sidebar.kepala-asrama')
                @elseif($user->hasRole('Admin Asrama'))
                    @include('layouts.sidebar.admin-asrama')
                @elseif($user->hasRole('Admin Pendidikan'))
                    @include('layouts.sidebar.admin-pendidikan')
                {{-- UKS Roles --}}
                @elseif($user->hasRole('Kepala UKS'))
                    @include('layouts.sidebar.kepala-uks')
                @elseif($user->hasRole('Admin UKS'))
                    @include('layouts.sidebar.admin-uks')
                @elseif($user->hasRole('Admin UKS Putra'))
                    @include('layouts.sidebar.admin-uks-putra')
                @elseif($user->hasRole('Admin UKS Putri'))
                    @include('layouts.sidebar.admin-uks-putri')
                @elseif($user->hasRole('UKS'))
                    @include('layouts.sidebar.uks')
                {{-- Existing Admin Kesehatan retains priority --}}
                @elseif($user->hasRole('Admin Kesehatan'))
                    @include('layouts.sidebar.admin-kesehatan')
                @elseif($user->hasRole('Wali Asrama'))
                    @include('layouts.sidebar.wali-asrama')
                @elseif($user->hasPermissionTo('menu-admin-tu-sidebar'))
                    @include('layouts.sidebar.admin-tu')
                {{-- GTK: unified sidebar for all teacher roles (Guru, Guru Tahfidz, Coordinator, Waka) --}}
                @elseif($user->hasPermissionTo('menu-gtk-sidebar'))
                    @include('layouts.sidebar.unified-gtk')
                @elseif($user->hasPermissionTo('menu-admin-sarpras-sidebar') || $user->hasPermissionTo('menu-sarpras-sidebar'))
                    @include('layouts.sidebar.gtk-sarpras')
                @elseif($user->hasPermissionTo('menu-personalia-sidebar'))
                    @include('layouts.sidebar.personalia')
                @elseif($user->hasPermissionTo('menu-wakil-kepala-sekolah-sidebar'))
                    @include('layouts.sidebar.waka')
                @elseif($user->hasPermissionTo('menu-asrama-sidebar'))
                    @include('layouts.sidebar.asrama-ro')
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
