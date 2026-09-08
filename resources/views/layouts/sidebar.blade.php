<!-- ========== App Menu ========== -->
<style>
.app-menu .scrollbar_sidebar {
    height: calc(110vh - 70px - 80px) !important;
    overflow-y: auto !important;
}
.app-menu .scrollbar_sidebar .simplebar-content-wrapper,
.app-menu .scrollbar_sidebar .simplebar-content {
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

                    // SidebarAccess logic
                    $showSidebar = true;

                    if (!$isSystemAdmin && !$isViewingAs && !empty($sidebarAccesses)) {
                        $userRoleNames = method_exists($user, 'roles')
                            ? $user->roles->pluck('name')->toArray()
                            : [];
                        $roleHasAccess = false;
                        foreach ($sidebarAccesses as $access) {
                            if ($access->canAccessByRoles($userRoleNames)) {
                                $roleHasAccess = true;
                                break;
                            }
                        }
                        $showSidebar = $roleHasAccess;
                    }
                @endphp

                {{-- When viewing-as, render the sidebar for the impersonated role --}}
                @if($isViewingAs)
                    @php
                        $viewAsRoleModel = \App\Models\Role::where('name', $viewAsRole)->first();
                        $viewAsPerms = $viewAsRoleModel
                            ? $viewAsRoleModel->permissions->pluck('name')->toArray()
                            : [];
                        $has = fn ($p) => in_array($p, $viewAsPerms);
                    @endphp
                    @if($viewAsRole === 'Pimpinan' || $has('menu-wakil-kepala-sekolah-sidebar'))
                        @include('layouts.sidebar.pimpinan.sidebar')
                    @elseif($viewAsRole === 'Satuan Pendidikan' || $viewAsRole === 'Administrator' || $viewAsRole === 'Admin Tata Usaha' || $viewAsRole === 'Tata Usaha' || $has('menu-gtk-sidebar') || $has('menu-satuan-pendidikan-sidebar') || $has('menu-admin-tu-sidebar'))
                        @include('layouts.sidebar.satuan-pendidikan.sidebar')
                    @elseif($viewAsRole === 'Asrama' || $viewAsRole === 'Kepala Asrama' || $viewAsRole === 'Admin Asrama' || $viewAsRole === 'Wali Asrama' || $has('menu-asrama-sidebar'))
                        @include('layouts.sidebar.asrama.sidebar')
                    @elseif($viewAsRole === 'UKS' || $viewAsRole === 'Kepala UKS' || $viewAsRole === 'Admin UKS' || $viewAsRole === 'Admin Kesehatan' || $viewAsRole === 'Admin UKS Putra' || $viewAsRole === 'Admin UKS Putri' || $has('menu-uks-sidebar'))
                        @include('layouts.sidebar.portal.sidebar')
                    @elseif($viewAsRole === 'Wali Santri' || $has('menu-wali-asrama-sidebar'))
                        @include('layouts.sidebar.portal.sidebar')
                    @else
                        <li class="nav-item"><span class="nav-link text-muted px-3">Role '{{ $viewAsRole }}' belum punya menu sidebar.</span></li>
                    @endif
                @elseif($showSidebar && $isSystemAdmin)
                    @include('layouts.sidebar.super-admin.sidebar')
                {{-- ── ROLE-BASED SIDEBAR (14 role resmi dari RoleSeeder) ──────────────── --}}
                @elseif($showSidebar)
                    @if($user->hasRole('Super Admin'))
                        @include('layouts.sidebar.super-admin.sidebar')
                    @elseif($user->hasRole('Pimpinan'))
                        @include('layouts.sidebar.pimpinan.sidebar')
                    @elseif($user->hasRole('Satuan Pendidikan'))
                        @include('layouts.sidebar.satuan-pendidikan.sidebar')
                    @elseif($user->hasRole('Asrama'))
                        @include('layouts.sidebar.asrama.sidebar')
                    @elseif($user->hasRole('UKS'))
                        @include('layouts.sidebar.portal.sidebar')
                    @elseif($user->hasRole('Departemen Tahfidz'))
                        @include('layouts.sidebar.departemen-tahfidz.sidebar')
                    @elseif($user->hasRole('Departemen Bahasa'))
                        @include('layouts.sidebar.departemen-bahasa.sidebar')
                    @elseif($user->hasRole('Perpustakaan'))
                        @include('layouts.sidebar.perpustakaan.sidebar')
                    @elseif($user->hasRole('Satuan Keamanan'))
                        @include('layouts.sidebar.satpam.sidebar')
                    @elseif($user->hasRole('Humas Personalia'))
                        @include('layouts.sidebar.humas-personalia.sidebar')
                    @elseif($user->hasRole('Unit Rumah Tangga'))
                        @include('layouts.sidebar.unit-rumah-tangga.sidebar')
                    @elseif($user->hasRole('Keuangan'))
                        @include('layouts.sidebar.keuangan.sidebar')
                    @elseif($user->hasRole('Teknologi Informasi'))
                        @include('layouts.sidebar.teknologi-informasi.sidebar')
                    @elseif($user->hasRole('Unit Pelayanan Gizi'))
                        @include('layouts.sidebar.unit-pelayanan-gizi.sidebar')
                    @else
                        <li class="nav-item"><span class="nav-link text-muted px-3">Tidak ada menu untuk role ini</span></li>
                    @endif
                @endif
            </ul>
        </div>
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Overlay: close sidebar on mobile -->
<div class="vertical-overlay"></div>
