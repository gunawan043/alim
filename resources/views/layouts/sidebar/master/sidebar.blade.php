<!-- Master Sidebar - comprehensive route catalog -->
@php
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $user = auth()->user();

    if (!function_exists('canAccessMenu')) {
        function canAccessMenu($allowedRoles = [], $allowedJabatans = [], $allowedTugas = []) {
            global $user;
            $userRoles = method_exists($user, 'roles') ? $user->roles->pluck('name')->toArray() : [];
            $userJabatan = method_exists($user, 'jabatan') ? ($user->jabatan->nama ?? $user->jabatan ?? '') : '';
            $userTugas = method_exists($user, 'tugasTambahan') ? $user->tugasTambahan->pluck('nama')->toArray() : [];

            if (in_array('Super Admin', $userRoles)) return true;
            if (empty($allowedRoles) && empty($allowedJabatans) && empty($allowedTugas)) return true;

            $hasRole = !empty(array_intersect($allowedRoles, $userRoles));
            $hasJabatan = in_array($userJabatan, $allowedJabatans);
            $hasTugas = !empty(array_intersect($allowedTugas, $userTugas));

            return $hasRole || $hasJabatan || $hasTugas;
        }
    }

    if (!function_exists('isActiveMaster')) {
        function isActiveMaster($routeName, $pattern) {
            if (!$routeName) return false;
            return str_starts_with($routeName, $pattern);
        }
    }
@endphp

<ul class="navbar-nav">
@if(canAccessMenu([], [], []))
<li class="menu-title"><span>Dashboard & Umum</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{ isActiveMaster($currentRoute, 'auth.validator.') ? ' active' : '' }"
       href="#dashboard_umum" data-bs-toggle="collapse" role="button"
       aria-expanded="{ isActiveMaster($currentRoute, 'auth.validator.') ? 'true' : 'false' }"
       aria-controls="dashboard_umum">
        <i class="ri-home-sm-line"></i>
        <span>Dashboard & Umum</span>
    </a>
    <div class="collapse menu-dropdown{ isActiveMaster($currentRoute, 'auth.validator.') ? ' show' : '' }" id="dashboard_umum">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{ $currentRoute === 'access-denied' ? ' active' : '' }"
                   href="{ route('access-denied') }">
                    Access Denied
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'auth.validator.') ? ' active' : '' }"
                   href="{ route('auth.validator') }">
                    Auth.validator
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ $currentRoute === 'login' ? ' active' : '' }"
                   href="{ route('login') }">
                    Login
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'portal.dashboard.') ? ' active' : '' }"
                   href="{ route('portal.dashboard') }">
                    Portal.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'portal.notifications.') ? ' active' : '' }"
                   href="{ route('portal.notifications') }">
                    Portal.notifications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'portal.timeline.') ? ' active' : '' }"
                   href="{ route('portal.timeline') }">
                    Portal.timeline
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ $currentRoute === 'root' ? ' active' : '' }"
                   href="{ route('root') }">
                    Root
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sanctum.csrf-cookie.') ? ' active' : '' }"
                   href="{ route('sanctum.csrf-cookie') }">
                    Sanctum.csrf Cookie
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.approvals.') ? ' active' : '' }"
                   href="{ route('user.approvals.history', ['userId' => auth()->id()]) }">
                    Approvals
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.approvals.') ? ' active' : '' }"
                   href="{ route('user.approvals.index', ['userId' => auth()->id()]) }">
                    Approvals
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.approvals.') ? ' active' : '' }"
                   href="{ route('user.approvals.my-pending', ['userId' => auth()->id()]) }">
                    Approvals.my Pending
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.approvals.') ? ' active' : '' }"
                   href="{ route('user.approvals.show', ['userId' => auth()->id()]) }">
                    Approvals
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.my-profile', ['userId' => auth()->id()]) }">
                    Asrama.my Profil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.calendar.') ? ' active' : '' }"
                   href="{ route('user.calendar.return.index', ['userId' => auth()->id()]) }">
                    Kalender.return
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.calendar.') ? ' active' : '' }"
                   href="{ route('user.calendar.return.show', ['userId' => auth()->id()]) }">
                    Kalender.return
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.calendar.') ? ' active' : '' }"
                   href="{ route('user.calendar.visit.index', ['userId' => auth()->id()]) }">
                    Kalender.visit
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.calendar.') ? ' active' : '' }"
                   href="{ route('user.calendar.visit.show', ['userId' => auth()->id()]) }">
                    Kalender.visit
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dashboard.') ? ' active' : '' }"
                   href="{ route('user.dashboard', ['userId' => auth()->id()]) }">
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dashboard.') ? ' active' : '' }"
                   href="{ route('user.dashboard.admin-tu', ['userId' => auth()->id()]) }">
                    Dashboard.admin Tu
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dashboard.') ? ' active' : '' }"
                   href="{ route('user.dashboard.asrama', ['userId' => auth()->id()]) }">
                    Dashboard.asrama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dashboard.') ? ' active' : '' }"
                   href="{ route('user.dashboard.bendahara', ['userId' => auth()->id()]) }">
                    Dashboard.bendahara
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dashboard.') ? ' active' : '' }"
                   href="{ route('user.dashboard.gtk', ['userId' => auth()->id()]) }">
                    Dashboard.gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dashboard.') ? ' active' : '' }"
                   href="{ route('user.dashboard.guru', ['userId' => auth()->id()]) }">
                    Dashboard.guru
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dashboard.') ? ' active' : '' }"
                   href="{ route('user.dashboard.pengasuh', ['userId' => auth()->id()]) }">
                    Dashboard.pengasuh
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dashboard.') ? ' active' : '' }"
                   href="{ route('user.dashboard.wali-kelas', ['userId' => auth()->id()]) }">
                    Dashboard.wali Kelas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.profile.edit', ['userId' => auth()->id()]) }">
                    Gtk.profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.profile.show', ['userId' => auth()->id()]) }">
                    Gtk.profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kinerja.') ? ' active' : '' }"
                   href="{ route('user.kinerja.laporan', ['userId' => auth()->id()]) }">
                    Kinerja.laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.laporan.') ? ' active' : '' }"
                   href="{ route('user.laporan.asrama', ['userId' => auth()->id()]) }">
                    Laporan.asrama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.laporan.') ? ' active' : '' }"
                   href="{ route('user.laporan.gtk', ['userId' => auth()->id()]) }">
                    Laporan.gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.laporan.') ? ' active' : '' }"
                   href="{ route('user.laporan.index', ['userId' => auth()->id()]) }">
                    Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.laporan.') ? ' active' : '' }"
                   href="{ route('user.laporan.keuangan', ['userId' => auth()->id()]) }">
                    Laporan.keuangan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.laporan.') ? ' active' : '' }"
                   href="{ route('user.laporan.presensi', ['userId' => auth()->id()]) }">
                    Laporan.presensi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.laporan.') ? ' active' : '' }"
                   href="{ route('user.laporan.santri', ['userId' => auth()->id()]) }">
                    Laporan.santri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.notifications.') ? ' active' : '' }"
                   href="{ route('user.notifications.index', ['userId' => auth()->id()]) }">
                    Notifications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.operator.') ? ' active' : '' }"
                   href="{ route('user.operator.dashboard', ['userId' => auth()->id()]) }">
                    Operator.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.profile.') ? ' active' : '' }"
                   href="{ route('user.profile.cv', ['userId' => auth()->id()]) }">
                    Profil.cv
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.profile.') ? ' active' : '' }"
                   href="{ route('user.profile.my', ['userId' => auth()->id()]) }">
                    Profil.my
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.profile.') ? ' active' : '' }"
                   href="{ route('user.profile.my.edit', ['userId' => auth()->id()]) }">
                    Profil.my
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.qr.') ? ' active' : '' }"
                   href="{ route('user.qr.show', ['userId' => auth()->id()]) }">
                    Qr
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.notifications.index', ['userId' => auth()->id()]) }">
                    Sa.notifications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.todos.') ? ' active' : '' }"
                   href="{ route('user.todos.index', ['userId' => auth()->id()]) }">
                    Todos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.todos.') ? ' active' : '' }"
                   href="{ route('user.todos.lists.index', ['userId' => auth()->id()]) }">
                    Todos.lists
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.todos.') ? ' active' : '' }"
                   href="{ route('user.todos.show', ['userId' => auth()->id()]) }">
                    Todos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.dashboard', ['userId' => auth()->id()]) }">
                    Uks.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.profile', ['userId' => auth()->id()]) }">
                    Uks.profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.wali.') ? ' active' : '' }"
                   href="{ route('user.wali.dashboard', ['userId' => auth()->id()]) }">
                    Wali.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.work-units.') ? ' active' : '' }"
                   href="{ route('user.work-units.index', ['userId' => auth()->id()]) }">
                    Satuan Kerjas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.work-units.') ? ' active' : '' }"
                   href="{ route('user.work-units.show', ['userId' => auth()->id()]) }">
                    Satuan Kerjas
                </a>
            </li>
        </ul>
    </div>
</li>
@endif

@if(canAccessMenu(["Super Admin", "Humas Personalia", "Keuangan", "Unit Rumah Tangga"], [], []))
<li class="menu-title"><span>Referensi & Master Data</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{ isActiveMaster($currentRoute, 'api.academic-years.') ? ' active' : '' }"
       href="#referensi_master_data" data-bs-toggle="collapse" role="button"
       aria-expanded="{ isActiveMaster($currentRoute, 'api.academic-years.') ? 'true' : 'false' }"
       aria-controls="referensi_master_data">
        <i class="ri-database-2-line"></i>
        <span>Referensi & Master Data</span>
    </a>
    <div class="collapse menu-dropdown{ isActiveMaster($currentRoute, 'api.academic-years.') ? ' show' : '' }" id="referensi_master_data">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'api.academic-years.') ? ' active' : '' }"
                   href="{ route('api.academic-years') }">
                    Api.academic Years
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'api.dormitories.') ? ' active' : '' }"
                   href="{ route('api.dormitories') }">
                    Api.dormitories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'api.rooms-by-wing.') ? ' active' : '' }"
                   href="{ route('api.rooms-by-wing') }">
                    Api.rooms By Wing
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'api.schools.') ? ' active' : '' }"
                   href="{ route('api.schools') }">
                    Api.schools
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'mobile.v1.') ? ' active' : '' }"
                   href="{ route('mobile.v1.build') }">
                    Mobile.v1.build
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'mobile.v1.') ? ' active' : '' }"
                   href="{ route('mobile.v1.health') }">
                    Mobile.v1.health
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'mobile.v1.') ? ' active' : '' }"
                   href="{ route('mobile.v1.system.status') }">
                    Mobile.v1.system.status
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'mobile.v1.') ? ' active' : '' }"
                   href="{ route('mobile.v1.version') }">
                    Mobile.v1.version
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools-global.') ? ' active' : '' }"
                   href="{ route('user.schools-global.index', ['userId' => auth()->id()]) }">
                    Schools Global
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.create', ['userId' => auth()->id()]) }">
                    Schools
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.edit', ['userId' => auth()->id()]) }">
                    Schools
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.global.index', ['userId' => auth()->id()]) }">
                    Schools.global
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.index', ['userId' => auth()->id()]) }">
                    Schools
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.kktp.index', ['userId' => auth()->id()]) }">
                    Schools.kktp
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.nilai-kelas.rapor.cetak', ['userId' => auth()->id()]) }">
                    Schools.nilai Kelas.rapor
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.nilai.index', ['userId' => auth()->id()]) }">
                    Schools.nilai
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.satuan-kerja.edit', ['userId' => auth()->id()]) }">
                    Schools.satuan Kerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.satuan-kerja.show', ['userId' => auth()->id()]) }">
                    Schools.satuan Kerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.schools.') ? ' active' : '' }"
                   href="{ route('user.schools.show', ['userId' => auth()->id()]) }">
                    Schools
                </a>
            </li>
        </ul>
    </div>
</li>
@endif

@if(canAccessMenu(["Pimpinan"], ["Kepala Sekolah"], []))
<li class="menu-title"><span>Pimpinan & Eksekutif</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{ isActiveMaster($currentRoute, 'system.config.') ? ' active' : '' }"
       href="#pimpinan_eksekutif" data-bs-toggle="collapse" role="button"
       aria-expanded="{ isActiveMaster($currentRoute, 'system.config.') ? 'true' : 'false' }"
       aria-controls="pimpinan_eksekutif">
        <i class="ri-vip-crown-line"></i>
        <span>Pimpinan & Eksekutif</span>
    </a>
    <div class="collapse menu-dropdown{ isActiveMaster($currentRoute, 'system.config.') ? ' show' : '' }" id="pimpinan_eksekutif">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.config.') ? ' active' : '' }"
                   href="{ route('system.config') }">
                    System.config
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.dashboard.') ? ' active' : '' }"
                   href="{ route('system.dashboard') }">
                    System.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.devtools.') ? ' active' : '' }"
                   href="{ route('system.devtools') }">
                    System.devtools
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.features.') ? ' active' : '' }"
                   href="{ route('system.features') }">
                    System.features
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.maintenance.') ? ' active' : '' }"
                   href="{ route('system.maintenance') }">
                    System.maintenance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.monitoring.') ? ' active' : '' }"
                   href="{ route('system.monitoring') }">
                    System.monitoring
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.permits.') ? ' active' : '' }"
                   href="{ route('system.permits.index') }">
                    System.permits
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.view-as.') ? ' active' : '' }"
                   href="{ route('system.view-as.state') }">
                    System.view As.state
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.view-as.') ? ' active' : '' }"
                   href="{ route('system.view-as.users') }">
                    System.view As.users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'system.violations.') ? ' active' : '' }"
                   href="{ route('system.violations.index') }">
                    System.violations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.academic-years.') ? ' active' : '' }"
                   href="{ route('user.academic-years.index', ['userId' => auth()->id()]) }">
                    Academic Years
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.academic-years.') ? ' active' : '' }"
                   href="{ route('user.academic-years.show', ['userId' => auth()->id()]) }">
                    Academic Years
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.audit-logs.export', ['userId' => auth()->id()]) }">
                    Sa.audit Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.audit-logs.index', ['userId' => auth()->id()]) }">
                    Sa.audit Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.audit-logs.show', ['userId' => auth()->id()]) }">
                    Sa.audit Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.divisi.index', ['userId' => auth()->id()]) }">
                    Sa.divisi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.dormitories.index', ['userId' => auth()->id()]) }">
                    Sa.dormitories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.failed-jobs.index', ['userId' => auth()->id()]) }">
                    Sa.failed Jobs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.permissions.index', ['userId' => auth()->id()]) }">
                    Sa.permissions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.roles.index', ['userId' => auth()->id()]) }">
                    Sa.roles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.roles.show', ['userId' => auth()->id()]) }">
                    Sa.roles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.schools.create', ['userId' => auth()->id()]) }">
                    Sa.schools
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.schools.edit', ['userId' => auth()->id()]) }">
                    Sa.schools
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.schools.index', ['userId' => auth()->id()]) }">
                    Sa.schools
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.sidebar-access.index', ['userId' => auth()->id()]) }">
                    Sa.sidebar Access
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.tokens.index', ['userId' => auth()->id()]) }">
                    Sa.tokens
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.users.create', ['userId' => auth()->id()]) }">
                    Sa.users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.users.edit', ['userId' => auth()->id()]) }">
                    Sa.users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.sa.') ? ' active' : '' }"
                   href="{ route('user.sa.users.index', ['userId' => auth()->id()]) }">
                    Sa.users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'vendor.dashboard.') ? ' active' : '' }"
                   href="{ route('vendor.dashboard') }">
                    Vendor.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'vendor.invoices.') ? ' active' : '' }"
                   href="{ route('vendor.invoices.index') }">
                    Vendor.invoices
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'vendor.login.') ? ' active' : '' }"
                   href="{ route('vendor.login') }">
                    Vendor.login
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'vendor.orders.') ? ' active' : '' }"
                   href="{ route('vendor.orders.index') }">
                    Vendor.orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'vendor.orders.') ? ' active' : '' }"
                   href="{ route('vendor.orders.show', ['userId' => auth()->id()]) }">
                    Vendor.orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'vendor.performance.') ? ' active' : '' }"
                   href="{ route('vendor.performance') }">
                    Vendor.performance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'vendor.procurement.') ? ' active' : '' }"
                   href="{ route('vendor.procurement.create') }">
                    Vendor.procurement
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'vendor.procurement.') ? ' active' : '' }"
                   href="{ route('vendor.procurement.index') }">
                    Vendor.procurement
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'vendor.procurement.') ? ' active' : '' }"
                   href="{ route('vendor.procurement.show', ['userId' => auth()->id()]) }">
                    Vendor.procurement
                </a>
            </li>
        </ul>
    </div>
</li>
@endif

@if(canAccessMenu(["Satuan Pendidikan"], [], ["Wali Kelas", "Tim Kurikulum", "Koordinator Kurikulum"]))
<li class="menu-title"><span>Satuan Pendidikan & Akademik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{ isActiveMaster($currentRoute, 'user.absensi.') ? ' active' : '' }"
       href="#satuan_pendidikan" data-bs-toggle="collapse" role="button"
       aria-expanded="{ isActiveMaster($currentRoute, 'user.absensi.') ? 'true' : 'false' }"
       aria-controls="satuan_pendidikan">
        <i class="ri-school-line"></i>
        <span>Satuan Pendidikan & Akademik</span>
    </a>
    <div class="collapse menu-dropdown{ isActiveMaster($currentRoute, 'user.absensi.') ? ' show' : '' }" id="satuan_pendidikan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.absensi.') ? ' active' : '' }"
                   href="{ route('user.absensi.harian.index', ['userId' => auth()->id()]) }">
                    Absensi.harian
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.absensi.') ? ' active' : '' }"
                   href="{ route('user.absensi.harian.recap', ['userId' => auth()->id()]) }">
                    Absensi.harian.recap
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.alumni.') ? ' active' : '' }"
                   href="{ route('user.alumni.edit', ['userId' => auth()->id()]) }">
                    Alumni
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.alumni.') ? ' active' : '' }"
                   href="{ route('user.alumni.export', ['userId' => auth()->id()]) }">
                    Alumni
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.alumni.') ? ' active' : '' }"
                   href="{ route('user.alumni.index', ['userId' => auth()->id()]) }">
                    Alumni
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.alumni.') ? ' active' : '' }"
                   href="{ route('user.alumni.show', ['userId' => auth()->id()]) }">
                    Alumni
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.alumni.') ? ' active' : '' }"
                   href="{ route('user.alumni.statistics', ['userId' => auth()->id()]) }">
                    Alumni.statistics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.bank-soal.') ? ' active' : '' }"
                   href="{ route('user.bank-soal.create', ['userId' => auth()->id()]) }">
                    Bank Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.bank-soal.') ? ' active' : '' }"
                   href="{ route('user.bank-soal.edit', ['userId' => auth()->id()]) }">
                    Bank Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.bank-soal.') ? ' active' : '' }"
                   href="{ route('user.bank-soal.index', ['userId' => auth()->id()]) }">
                    Bank Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.bank-soal.') ? ' active' : '' }"
                   href="{ route('user.bank-soal.show', ['userId' => auth()->id()]) }">
                    Bank Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dokumen-iso.') ? ' active' : '' }"
                   href="{ route('user.dokumen-iso.index', ['userId' => auth()->id()]) }">
                    Dokumen Iso
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.grade-levels.') ? ' active' : '' }"
                   href="{ route('user.grade-levels.create', ['userId' => auth()->id()]) }">
                    Grade Levels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.grade-levels.') ? ' active' : '' }"
                   href="{ route('user.grade-levels.edit', ['userId' => auth()->id()]) }">
                    Grade Levels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.grade-levels.') ? ' active' : '' }"
                   href="{ route('user.grade-levels.index', ['userId' => auth()->id()]) }">
                    Grade Levels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.grade-levels.') ? ' active' : '' }"
                   href="{ route('user.grade-levels.show', ['userId' => auth()->id()]) }">
                    Grade Levels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.jadwal-kbm.') ? ' active' : '' }"
                   href="{ route('user.jadwal-kbm.generate', ['userId' => auth()->id()]) }">
                    Jadwal KBM.generate
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.jadwal-kbm.') ? ' active' : '' }"
                   href="{ route('user.jadwal-kbm.index', ['userId' => auth()->id()]) }">
                    Jadwal KBM
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.jadwal-kbm.') ? ' active' : '' }"
                   href="{ route('user.jadwal-kbm.show', ['userId' => auth()->id()]) }">
                    Jadwal KBM
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kaldik.') ? ' active' : '' }"
                   href="{ route('user.kaldik.create', ['userId' => auth()->id()]) }">
                    Kaldik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kaldik.') ? ' active' : '' }"
                   href="{ route('user.kaldik.edit', ['userId' => auth()->id()]) }">
                    Kaldik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kaldik.') ? ' active' : '' }"
                   href="{ route('user.kaldik.index', ['userId' => auth()->id()]) }">
                    Kaldik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kaldik.') ? ' active' : '' }"
                   href="{ route('user.kaldik.show', ['userId' => auth()->id()]) }">
                    Kaldik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kisi-kisi-soal.') ? ' active' : '' }"
                   href="{ route('user.kisi-kisi-soal.index', ['userId' => auth()->id()]) }">
                    Kisi Kisi Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.mutations-do.') ? ' active' : '' }"
                   href="{ route('user.mutations-do.create', ['userId' => auth()->id()]) }">
                    Mutasis Do
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.mutations-do.') ? ' active' : '' }"
                   href="{ route('user.mutations-do.index', ['userId' => auth()->id()]) }">
                    Mutasis Do
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.mutations-in.') ? ' active' : '' }"
                   href="{ route('user.mutations-in.create', ['userId' => auth()->id()]) }">
                    Mutasis In
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.mutations-in.') ? ' active' : '' }"
                   href="{ route('user.mutations-in.index', ['userId' => auth()->id()]) }">
                    Mutasis In
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.mutations-lulus.') ? ' active' : '' }"
                   href="{ route('user.mutations-lulus.index', ['userId' => auth()->id()]) }">
                    Mutasis Lulus
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.mutations-out.') ? ' active' : '' }"
                   href="{ route('user.mutations-out.create', ['userId' => auth()->id()]) }">
                    Mutasis Out
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.mutations-out.') ? ' active' : '' }"
                   href="{ route('user.mutations-out.index', ['userId' => auth()->id()]) }">
                    Mutasis Out
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.paket-soal.') ? ' active' : '' }"
                   href="{ route('user.paket-soal.index', ['userId' => auth()->id()]) }">
                    Paket Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.paket-soal.') ? ' active' : '' }"
                   href="{ route('user.paket-soal.show', ['userId' => auth()->id()]) }">
                    Paket Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.rapor-gtk.') ? ' active' : '' }"
                   href="{ route('user.rapor-gtk.akademik', ['userId' => auth()->id()]) }">
                    Rapor GTK.akademik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.rapor-gtk.') ? ' active' : '' }"
                   href="{ route('user.rapor-gtk.disiplin', ['userId' => auth()->id()]) }">
                    Rapor GTK.disiplin
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.rapor-gtk.') ? ' active' : '' }"
                   href="{ route('user.rapor-gtk.tahunan', ['userId' => auth()->id()]) }">
                    Rapor GTK.tahunan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.student-move.') ? ' active' : '' }"
                   href="{ route('user.student-move.index', ['userId' => auth()->id()]) }">
                    Mutasi Santri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.students.') ? ' active' : '' }"
                   href="{ route('user.students.create', ['userId' => auth()->id()]) }">
                    Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.students.') ? ' active' : '' }"
                   href="{ route('user.students.edit', ['userId' => auth()->id()]) }">
                    Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.students.') ? ' active' : '' }"
                   href="{ route('user.students.import-form', ['userId' => auth()->id()]) }">
                    Students.import Form
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.students.') ? ' active' : '' }"
                   href="{ route('user.students.index', ['userId' => auth()->id()]) }">
                    Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.students.') ? ' active' : '' }"
                   href="{ route('user.students.mahroms.global', ['userId' => auth()->id()]) }">
                    Students.mahroms
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.students.') ? ' active' : '' }"
                   href="{ route('user.students.show', ['userId' => auth()->id()]) }">
                    Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.students.') ? ' active' : '' }"
                   href="{ route('user.students.template', ['userId' => auth()->id()]) }">
                    Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.study-groups.') ? ' active' : '' }"
                   href="{ route('user.study-groups.create', ['userId' => auth()->id()]) }">
                    Rombels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.study-groups.') ? ' active' : '' }"
                   href="{ route('user.study-groups.edit', ['userId' => auth()->id()]) }">
                    Rombels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.study-groups.') ? ' active' : '' }"
                   href="{ route('user.study-groups.index', ['userId' => auth()->id()]) }">
                    Rombels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.study-groups.') ? ' active' : '' }"
                   href="{ route('user.study-groups.show', ['userId' => auth()->id()]) }">
                    Rombels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.subjects.') ? ' active' : '' }"
                   href="{ route('user.subjects.create', ['userId' => auth()->id()]) }">
                    Subjects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.subjects.') ? ' active' : '' }"
                   href="{ route('user.subjects.edit', ['userId' => auth()->id()]) }">
                    Subjects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.subjects.') ? ' active' : '' }"
                   href="{ route('user.subjects.index', ['userId' => auth()->id()]) }">
                    Subjects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.subjects.') ? ' active' : '' }"
                   href="{ route('user.subjects.show', ['userId' => auth()->id()]) }">
                    Subjects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wadir1..') ? ' active' : '' }"
                   href="{ route('wadir1.') }">
                    Wadir1.
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wadir1.gtk.') ? ' active' : '' }"
                   href="{ route('wadir1.gtk.filter') }">
                    Wadir1.gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wadir1.gtk.') ? ' active' : '' }"
                   href="{ route('wadir1.gtk.index') }">
                    Wadir1.gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wadir1.gtk.') ? ' active' : '' }"
                   href="{ route('wadir1.gtk.show', ['userId' => auth()->id()]) }">
                    Wadir1.gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wadir1.reports.') ? ' active' : '' }"
                   href="{ route('wadir1.reports.index') }">
                    Wadir1.reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wadir1.statistics.') ? ' active' : '' }"
                   href="{ route('wadir1.statistics') }">
                    Wadir1.statistics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wadir1.work-units.') ? ' active' : '' }"
                   href="{ route('wadir1.work-units.index') }">
                    Wadir1.work Units
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.absensi-gtk.') ? ' active' : '' }"
                   href="{ route('waka.absensi-gtk') }">
                    Waka.absensi Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.absensi-pd.') ? ' active' : '' }"
                   href="{ route('waka.absensi-pd') }">
                    Waka.absensi Pd
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.bank-soal.') ? ' active' : '' }"
                   href="{ route('waka.bank-soal') }">
                    Waka.bank Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.dashboard.') ? ' active' : '' }"
                   href="{ route('waka.dashboard') }">
                    Waka.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.data-alumni.') ? ' active' : '' }"
                   href="{ route('waka.data-alumni') }">
                    Waka.data Alumni
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.dokumen-iso.') ? ' active' : '' }"
                   href="{ route('waka.dokumen-iso') }">
                    Waka.dokumen Iso
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.gtk-guru.') ? ' active' : '' }"
                   href="{ route('waka.gtk-guru') }">
                    Waka.gtk Guru
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.gtk-tendik.') ? ' active' : '' }"
                   href="{ route('waka.gtk-tendik') }">
                    Waka.gtk Tendik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.hafalan-hadits.') ? ' active' : '' }"
                   href="{ route('waka.hafalan-hadits') }">
                    Waka.hafalan Hadits
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.hafalan-quran.') ? ' active' : '' }"
                   href="{ route('waka.hafalan-quran') }">
                    Waka.hafalan Quran
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.jadwal-pelajaran.') ? ' active' : '' }"
                   href="{ route('waka.jadwal-pelajaran') }">
                    Waka.jadwal Pelajaran
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.jam-mengajar.') ? ' active' : '' }"
                   href="{ route('waka.jam-mengajar') }">
                    Waka.jam Mengajar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.kaldik.') ? ' active' : '' }"
                   href="{ route('waka.kaldik') }">
                    Waka.kaldik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.kisi-kisi-soal.') ? ' active' : '' }"
                   href="{ route('waka.kisi-kisi-soal') }">
                    Waka.kisi Kisi Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.nilai-sas.') ? ' active' : '' }"
                   href="{ route('waka.nilai-sas') }">
                    Waka.nilai Sas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.nilai-sts.') ? ' active' : '' }"
                   href="{ route('waka.nilai-sts') }">
                    Waka.nilai Sts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.pekan-efektif.') ? ' active' : '' }"
                   href="{ route('waka.pekan-efektif.index') }">
                    Waka.pekan Efektif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.poin-pelanggaran.') ? ' active' : '' }"
                   href="{ route('waka.poin-pelanggaran') }">
                    Waka.poin Pelanggaran
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.prestasi-akademik.') ? ' active' : '' }"
                   href="{ route('waka.prestasi-akademik') }">
                    Waka.prestasi Akademik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.sarana-prasarana.') ? ' active' : '' }"
                   href="{ route('waka.sarana-prasarana') }">
                    Waka.sarana Prasarana
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.sk-guru.') ? ' active' : '' }"
                   href="{ route('waka.sk-guru') }">
                    Waka.sk Guru
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.soal-sumatif.') ? ' active' : '' }"
                   href="{ route('waka.soal-sumatif') }">
                    Waka.soal Sumatif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.supervisi.') ? ' active' : '' }"
                   href="{ route('waka.supervisi.create') }">
                    Waka.supervisi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.supervisi.') ? ' active' : '' }"
                   href="{ route('waka.supervisi.index') }">
                    Waka.supervisi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.supervisi.') ? ' active' : '' }"
                   href="{ route('waka.supervisi.show') }">
                    Waka.supervisi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.surat-keluar.') ? ' active' : '' }"
                   href="{ route('waka.surat-keluar.index') }">
                    Waka.surat Keluar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'waka.surat-masuk.') ? ' active' : '' }"
                   href="{ route('waka.surat-masuk.index') }">
                    Waka.surat Masuk
                </a>
            </li>
        </ul>
    </div>
</li>
@endif

@if(canAccessMenu(["Asrama"], [], ["Wali Kamar", "Wali Asrama", "Musrif"]))
<li class="menu-title"><span>Kepesantrenaan & Asrama</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
       href="#kepesantrenaan_asrama" data-bs-toggle="collapse" role="button"
       aria-expanded="{ isActiveMaster($currentRoute, 'user.asrama.') ? 'true' : 'false' }"
       aria-controls="kepesantrenaan_asrama">
        <i class="ri-home-heart-line"></i>
        <span>Kepesantrenaan & Asrama</span>
    </a>
    <div class="collapse menu-dropdown{ isActiveMaster($currentRoute, 'user.asrama.') ? ' show' : '' }" id="kepesantrenaan_asrama">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.api.rooms', ['userId' => auth()->id()]) }">
                    Asrama.api.rooms
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.api.rooms.available-residents', ['userId' => auth()->id()]) }">
                    Asrama.api.rooms.available Residents
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.api.wings', ['userId' => auth()->id()]) }">
                    Asrama.api.wings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.create', ['userId' => auth()->id()]) }">
                    Asrama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.edit', ['userId' => auth()->id()]) }">
                    Asrama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.index', ['userId' => auth()->id()]) }">
                    Asrama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.permit-types.create', ['userId' => auth()->id()]) }">
                    Asrama.permit Types
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.permit-types.edit', ['userId' => auth()->id()]) }">
                    Asrama.permit Types
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.permit-types.show', ['userId' => auth()->id()]) }">
                    Asrama.permit Types
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.permits.verify', ['userId' => auth()->id()]) }">
                    Asrama.permits.verify
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.room-supervisors.edit', ['userId' => auth()->id()]) }">
                    Asrama.room Supervisors
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.asrama.') ? ' active' : '' }"
                   href="{ route('user.asrama.show', ['userId' => auth()->id()]) }">
                    Asrama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.applications.index', ['userId' => auth()->id()]) }">
                    Ats.applications
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.candidates.index', ['userId' => auth()->id()]) }">
                    Ats.candidates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.data-nilai.datatable', ['userId' => auth()->id()]) }">
                    Ats.data Nilai
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.data-nilai.index', ['userId' => auth()->id()]) }">
                    Ats.data Nilai
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.index', ['userId' => auth()->id()]) }">
                    Ats
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.interviews.index', ['userId' => auth()->id()]) }">
                    Ats.interviews
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.jobs.create', ['userId' => auth()->id()]) }">
                    Ats.jobs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.jobs.edit', ['userId' => auth()->id()]) }">
                    Ats.jobs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.jobs.index', ['userId' => auth()->id()]) }">
                    Ats.jobs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.jobs.show', ['userId' => auth()->id()]) }">
                    Ats.jobs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.reports.index', ['userId' => auth()->id()]) }">
                    Ats.reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.ats.') ? ' active' : '' }"
                   href="{ route('user.ats.settings.index', ['userId' => auth()->id()]) }">
                    Ats.settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.boarding-policies.') ? ' active' : '' }"
                   href="{ route('user.boarding-policies.index', ['userId' => auth()->id()]) }">
                    Boarding Policies
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.divisi.') ? ' active' : '' }"
                   href="{ route('user.divisi.index', ['userId' => auth()->id()]) }">
                    Divisi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.dormitory-master.') ? ' active' : '' }"
                   href="{ route('user.dormitory-master.index', ['userId' => auth()->id()]) }">
                    Master Asrama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.violation-points.') ? ' active' : '' }"
                   href="{ route('user.violation-points.index', ['userId' => auth()->id()]) }">
                    Poin Pelanggarans
                </a>
            </li>
        </ul>
    </div>
</li>
@endif

@if(canAccessMenu(["UKS"], [], []))
<li class="menu-title"><span>UKS & Kesehatan Santri</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
       href="#uks" data-bs-toggle="collapse" role="button"
       aria-expanded="{ isActiveMaster($currentRoute, 'user.uks.') ? 'true' : 'false' }"
       aria-controls="uks">
        <i class="ri-heart-pulse-line"></i>
        <span>UKS & Kesehatan Santri</span>
    </a>
    <div class="collapse menu-dropdown{ isActiveMaster($currentRoute, 'user.uks.') ? ' show' : '' }" id="uks">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.beds.create', ['userId' => auth()->id()]) }">
                    Uks.beds
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.beds.edit', ['userId' => auth()->id()]) }">
                    Uks.beds
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.beds.index', ['userId' => auth()->id()]) }">
                    Uks.beds
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.beds.show', ['userId' => auth()->id()]) }">
                    Uks.beds
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.gtk-health.index', ['userId' => auth()->id()]) }">
                    Uks.gtk Health
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.immunizations.index', ['userId' => auth()->id()]) }">
                    Uks.immunizations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.medicine-logs.index', ['userId' => auth()->id()]) }">
                    Uks.medicine Logs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.patients.create', ['userId' => auth()->id()]) }">
                    Uks.patients
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.patients.index', ['userId' => auth()->id()]) }">
                    Uks.patients
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.patients.show', ['userId' => auth()->id()]) }">
                    Uks.patients
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.reports.index', ['userId' => auth()->id()]) }">
                    Uks.reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.reports.occupancy', ['userId' => auth()->id()]) }">
                    Uks.reports.occupancy
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.reports.permits', ['userId' => auth()->id()]) }">
                    Uks.reports.permits
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.uks.') ? ' active' : '' }"
                   href="{ route('user.uks.scheduling.index', ['userId' => auth()->id()]) }">
                    Uks.scheduling
                </a>
            </li>
        </ul>
    </div>
</li>
@endif

@if(canAccessMenu(["Humas Personalia"], [], []))
<li class="menu-title"><span>Humas & Personalia / GTK</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{ isActiveMaster($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }"
       href="#humas_personalia" data-bs-toggle="collapse" role="button"
       aria-expanded="{ isActiveMaster($currentRoute, 'user.absensi-gtk.') ? 'true' : 'false' }"
       aria-controls="humas_personalia">
        <i class="ri-user-settings-line"></i>
        <span>Humas & Personalia / GTK</span>
    </a>
    <div class="collapse menu-dropdown{ isActiveMaster($currentRoute, 'user.absensi-gtk.') ? ' show' : '' }" id="humas_personalia">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }"
                   href="{ route('user.absensi-gtk.harian', ['userId' => auth()->id()]) }">
                    Absensi GTK.harian
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }"
                   href="{ route('user.absensi-gtk.index', ['userId' => auth()->id()]) }">
                    Absensi GTK
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }"
                   href="{ route('user.absensi-gtk.izin', ['userId' => auth()->id()]) }">
                    Absensi GTK.izin
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }"
                   href="{ route('user.absensi-gtk.settings', ['userId' => auth()->id()]) }">
                    Absensi GTK.settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.analisis-gtk.') ? ' active' : '' }"
                   href="{ route('user.analisis-gtk.gap', ['userId' => auth()->id()]) }">
                    Analisis GTK.gap
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.api.') ? ' active' : '' }"
                   href="{ route('user.api.grade-levels.by-academic-year', ['userId' => auth()->id()]) }">
                    Api.grade Levels
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.api.') ? ' active' : '' }"
                   href="{ route('user.api.study-groups.students.unassigned', ['userId' => auth()->id()]) }">
                    Api.study Groups.students.unassigned
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.cuti.') ? ' active' : '' }"
                   href="{ route('user.cuti.approval', ['userId' => auth()->id()]) }">
                    Cuti.approval
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.cuti.') ? ' active' : '' }"
                   href="{ route('user.cuti.create', ['userId' => auth()->id()]) }">
                    Cuti
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.cuti.') ? ' active' : '' }"
                   href="{ route('user.cuti.edit', ['userId' => auth()->id()]) }">
                    Cuti
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.cuti.') ? ' active' : '' }"
                   href="{ route('user.cuti.index', ['userId' => auth()->id()]) }">
                    Cuti
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.cuti.') ? ' active' : '' }"
                   href="{ route('user.cuti.quota', ['userId' => auth()->id()]) }">
                    Cuti.quota
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.cuti.') ? ' active' : '' }"
                   href="{ route('user.cuti.rekap', ['userId' => auth()->id()]) }">
                    Cuti.rekap
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.cuti.') ? ' active' : '' }"
                   href="{ route('user.cuti.settings', ['userId' => auth()->id()]) }">
                    Cuti.settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.cuti.') ? ' active' : '' }"
                   href="{ route('user.cuti.show', ['userId' => auth()->id()]) }">
                    Cuti
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk-positions.') ? ' active' : '' }"
                   href="{ route('user.gtk-positions.index', ['userId' => auth()->id()]) }">
                    Gtk Positions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk-requests.') ? ' active' : '' }"
                   href="{ route('user.gtk-requests.create', ['userId' => auth()->id()]) }">
                    Gtk Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk-requests.') ? ' active' : '' }"
                   href="{ route('user.gtk-requests.index', ['userId' => auth()->id()]) }">
                    Gtk Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.create', ['userId' => auth()->id()]) }">
                    Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.edit', ['userId' => auth()->id()]) }">
                    Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.educations.show', ['userId' => auth()->id()]) }">
                    Gtk.educations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.export', ['userId' => auth()->id()]) }">
                    Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.export.preview', ['userId' => auth()->id()]) }">
                    Gtk.export
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.import', ['userId' => auth()->id()]) }">
                    Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.index', ['userId' => auth()->id()]) }">
                    Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.indexguru', ['userId' => auth()->id()]) }">
                    Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.indextendik', ['userId' => auth()->id()]) }">
                    Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.massal', ['userId' => auth()->id()]) }">
                    Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.gtk.') ? ' active' : '' }"
                   href="{ route('user.gtk.show', ['userId' => auth()->id()]) }">
                    Gtk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.jam-kerja.') ? ' active' : '' }"
                   href="{ route('user.jam-kerja.create', ['userId' => auth()->id()]) }">
                    Jam Kerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.jam-kerja.') ? ' active' : '' }"
                   href="{ route('user.jam-kerja.edit', ['userId' => auth()->id()]) }">
                    Jam Kerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.jam-kerja.') ? ' active' : '' }"
                   href="{ route('user.jam-kerja.index', ['userId' => auth()->id()]) }">
                    Jam Kerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.jam-kerja.') ? ' active' : '' }"
                   href="{ route('user.jam-kerja.kalender', ['userId' => auth()->id()]) }">
                    Jam Kerja.kalender
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.jam-kerja.') ? ' active' : '' }"
                   href="{ route('user.jam-kerja.shift', ['userId' => auth()->id()]) }">
                    Jam Kerja.shift
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kehadiran.') ? ' active' : '' }"
                   href="{ route('user.kehadiran.cuti-izin', ['userId' => auth()->id()]) }">
                    Kehadiran.cuti Izin
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kehadiran.') ? ' active' : '' }"
                   href="{ route('user.kehadiran.rekap', ['userId' => auth()->id()]) }">
                    Kehadiran.rekap
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kesejahteraan.') ? ' active' : '' }"
                   href="{ route('user.kesejahteraan.create', ['userId' => auth()->id()]) }">
                    Kesejahteraan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kesejahteraan.') ? ' active' : '' }"
                   href="{ route('user.kesejahteraan.edit', ['userId' => auth()->id()]) }">
                    Kesejahteraan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kesejahteraan.') ? ' active' : '' }"
                   href="{ route('user.kesejahteraan.index', ['userId' => auth()->id()]) }">
                    Kesejahteraan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kesejahteraan.') ? ' active' : '' }"
                   href="{ route('user.kesejahteraan.klaim', ['userId' => auth()->id()]) }">
                    Kesejahteraan.klaim
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kesejahteraan.') ? ' active' : '' }"
                   href="{ route('user.kesejahteraan.show', ['userId' => auth()->id()]) }">
                    Kesejahteraan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kinerja.') ? ' active' : '' }"
                   href="{ route('user.kinerja.create', ['userId' => auth()->id()]) }">
                    Kinerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kinerja.') ? ' active' : '' }"
                   href="{ route('user.kinerja.edit', ['userId' => auth()->id()]) }">
                    Kinerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kinerja.') ? ' active' : '' }"
                   href="{ route('user.kinerja.index', ['userId' => auth()->id()]) }">
                    Kinerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kinerja.') ? ' active' : '' }"
                   href="{ route('user.kinerja.indikator', ['userId' => auth()->id()]) }">
                    Kinerja.indikator
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kinerja.') ? ' active' : '' }"
                   href="{ route('user.kinerja.periode', ['userId' => auth()->id()]) }">
                    Kinerja.periode
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kinerja.') ? ' active' : '' }"
                   href="{ route('user.kinerja.reward', ['userId' => auth()->id()]) }">
                    Kinerja.reward
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kinerja.') ? ' active' : '' }"
                   href="{ route('user.kinerja.show', ['userId' => auth()->id()]) }">
                    Kinerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kontrak.') ? ' active' : '' }"
                   href="{ route('user.kontrak.create', ['userId' => auth()->id()]) }">
                    Kontrak
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kontrak.') ? ' active' : '' }"
                   href="{ route('user.kontrak.edit', ['userId' => auth()->id()]) }">
                    Kontrak
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kontrak.') ? ' active' : '' }"
                   href="{ route('user.kontrak.expiring', ['userId' => auth()->id()]) }">
                    Kontrak.expiring
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kontrak.') ? ' active' : '' }"
                   href="{ route('user.kontrak.generate', ['userId' => auth()->id()]) }">
                    Kontrak.generate
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kontrak.') ? ' active' : '' }"
                   href="{ route('user.kontrak.index', ['userId' => auth()->id()]) }">
                    Kontrak
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kontrak.') ? ' active' : '' }"
                   href="{ route('user.kontrak.settings', ['userId' => auth()->id()]) }">
                    Kontrak.settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kontrak.') ? ' active' : '' }"
                   href="{ route('user.kontrak.show', ['userId' => auth()->id()]) }">
                    Kontrak
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.kontrak.') ? ' active' : '' }"
                   href="{ route('user.kontrak.template', ['userId' => auth()->id()]) }">
                    Kontrak
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll-slip.') ? ' active' : '' }"
                   href="{ route('user.payroll-slip.index', ['userId' => auth()->id()]) }">
                    Payroll Slip
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll-slip.') ? ' active' : '' }"
                   href="{ route('user.payroll-slip.pdf', ['userId' => auth()->id()]) }">
                    Payroll Slip
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll-slip.') ? ' active' : '' }"
                   href="{ route('user.payroll-slip.show', ['userId' => auth()->id()]) }">
                    Payroll Slip
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll.') ? ' active' : '' }"
                   href="{ route('user.payroll.bpjs-kes', ['userId' => auth()->id()]) }">
                    Payroll.bpjs Kes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll.') ? ' active' : '' }"
                   href="{ route('user.payroll.bpjstk', ['userId' => auth()->id()]) }">
                    Payroll.bpjstk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll.') ? ' active' : '' }"
                   href="{ route('user.payroll.create', ['userId' => auth()->id()]) }">
                    Payroll
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll.') ? ' active' : '' }"
                   href="{ route('user.payroll.edit', ['userId' => auth()->id()]) }">
                    Payroll
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll.') ? ' active' : '' }"
                   href="{ route('user.payroll.index', ['userId' => auth()->id()]) }">
                    Payroll
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll.') ? ' active' : '' }"
                   href="{ route('user.payroll.potongan', ['userId' => auth()->id()]) }">
                    Payroll.potongan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll.') ? ' active' : '' }"
                   href="{ route('user.payroll.settings', ['userId' => auth()->id()]) }">
                    Payroll.settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.payroll.') ? ' active' : '' }"
                   href="{ route('user.payroll.tunjangan', ['userId' => auth()->id()]) }">
                    Payroll.tunjangan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pelatihan.') ? ' active' : '' }"
                   href="{ route('user.pelatihan.create', ['userId' => auth()->id()]) }">
                    Pelatihan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pelatihan.') ? ' active' : '' }"
                   href="{ route('user.pelatihan.edit', ['userId' => auth()->id()]) }">
                    Pelatihan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pelatihan.') ? ' active' : '' }"
                   href="{ route('user.pelatihan.index', ['userId' => auth()->id()]) }">
                    Pelatihan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pelatihan.') ? ' active' : '' }"
                   href="{ route('user.pelatihan.peserta', ['userId' => auth()->id()]) }">
                    Pelatihan.peserta
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pelatihan.') ? ' active' : '' }"
                   href="{ route('user.pelatihan.rekap', ['userId' => auth()->id()]) }">
                    Pelatihan.rekap
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pelatihan.') ? ' active' : '' }"
                   href="{ route('user.pelatihan.show', ['userId' => auth()->id()]) }">
                    Pelatihan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pension.') ? ' active' : '' }"
                   href="{ route('user.pension.edit', ['userId' => auth()->id()]) }">
                    Pensiun
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pension.') ? ' active' : '' }"
                   href="{ route('user.pension.index', ['userId' => auth()->id()]) }">
                    Pensiun
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pension.') ? ' active' : '' }"
                   href="{ route('user.pension.settings', ['userId' => auth()->id()]) }">
                    Pensiun.settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.pension.') ? ' active' : '' }"
                   href="{ route('user.pension.show', ['userId' => auth()->id()]) }">
                    Pensiun
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.peraturan.') ? ' active' : '' }"
                   href="{ route('user.peraturan.create', ['userId' => auth()->id()]) }">
                    Peraturan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.peraturan.') ? ' active' : '' }"
                   href="{ route('user.peraturan.edit', ['userId' => auth()->id()]) }">
                    Peraturan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.peraturan.') ? ' active' : '' }"
                   href="{ route('user.peraturan.index', ['userId' => auth()->id()]) }">
                    Peraturan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.peraturan.') ? ' active' : '' }"
                   href="{ route('user.peraturan.kategori', ['userId' => auth()->id()]) }">
                    Peraturan.kategori
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.peraturan.') ? ' active' : '' }"
                   href="{ route('user.peraturan.show', ['userId' => auth()->id()]) }">
                    Peraturan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.peraturan.') ? ' active' : '' }"
                   href="{ route('user.peraturan.violation', ['userId' => auth()->id()]) }">
                    Peraturan.violation
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.recruitment.') ? ' active' : '' }"
                   href="{ route('user.recruitment.create', ['userId' => auth()->id()]) }">
                    Rekrutmen
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.recruitment.') ? ' active' : '' }"
                   href="{ route('user.recruitment.index', ['userId' => auth()->id()]) }">
                    Rekrutmen
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.teacher-qr.') ? ' active' : '' }"
                   href="{ route('user.teacher-qr.scan', ['userId' => auth()->id()]) }">
                    Teacher Qr.scan
                </a>
            </li>
        </ul>
    </div>
</li>
@endif

@if(canAccessMenu(["Unit Rumah Tangga"], [], []))
<li class="menu-title"><span>Unit Rumah Tangga & Sarpras</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{ isActiveMaster($currentRoute, 'api.sarpras.') ? ' active' : '' }"
       href="#unit_rumah_tangga" data-bs-toggle="collapse" role="button"
       aria-expanded="{ isActiveMaster($currentRoute, 'api.sarpras.') ? 'true' : 'false' }"
       aria-controls="unit_rumah_tangga">
        <i class="ri-building-line"></i>
        <span>Unit Rumah Tangga & Sarpras</span>
    </a>
    <div class="collapse menu-dropdown{ isActiveMaster($currentRoute, 'api.sarpras.') ? ' show' : '' }" id="unit_rumah_tangga">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'api.sarpras.') ? ' active' : '' }"
                   href="{ route('api.sarpras.passport') }">
                    Api.passport
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'api.sarpras.') ? ' active' : '' }"
                   href="{ route('api.sarpras.repairs.index') }">
                    Api.repairs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'api.sarpras.') ? ' active' : '' }"
                   href="{ route('api.sarpras.tco.show') }">
                    Api.tco
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.aset.') ? ' active' : '' }"
                   href="{ route('sarpras.aset.create') }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.aset.') ? ' active' : '' }"
                   href="{ route('sarpras.aset.edit', ['userId' => auth()->id()]) }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.aset.') ? ' active' : '' }"
                   href="{ route('sarpras.aset.import') }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.aset.') ? ' active' : '' }"
                   href="{ route('sarpras.aset.index') }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.aset.') ? ' active' : '' }"
                   href="{ route('sarpras.aset.show', ['userId' => auth()->id()]) }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.aset.') ? ' active' : '' }"
                   href="{ route('sarpras.aset.template') }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.assets.') ? ' active' : '' }"
                   href="{ route('sarpras.assets.passport', ['userId' => auth()->id()]) }">
                    Assets.passport
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.assets.') ? ' active' : '' }"
                   href="{ route('sarpras.assets.scan') }">
                    Assets.scan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.auditor.') ? ' active' : '' }"
                   href="{ route('sarpras.auditor.dashboard') }">
                    Auditor.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.booking.') ? ' active' : '' }"
                   href="{ route('sarpras.booking.create') }">
                    Booking
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.booking.') ? ' active' : '' }"
                   href="{ route('sarpras.booking.index') }">
                    Booking
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.booking.') ? ' active' : '' }"
                   href="{ route('sarpras.booking.show', ['userId' => auth()->id()]) }">
                    Booking
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.dashboard.') ? ' active' : '' }"
                   href="{ route('sarpras.dashboard') }">
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.disposal.') ? ' active' : '' }"
                   href="{ route('sarpras.disposal.pending') }">
                    Disposal.pending
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.divisi.') ? ' active' : '' }"
                   href="{ route('sarpras.divisi.asset_show') }">
                    Divisi.asset Detail
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.divisi.') ? ' active' : '' }"
                   href="{ route('sarpras.divisi.assets') }">
                    Divisi.assets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.divisi.') ? ' active' : '' }"
                   href="{ route('sarpras.divisi.dashboard') }">
                    Divisi.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.divisi.') ? ' active' : '' }"
                   href="{ route('sarpras.divisi.history') }">
                    Divisi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.division.') ? ' active' : '' }"
                   href="{ route('sarpras.division.assets') }">
                    Division.assets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.division.') ? ' active' : '' }"
                   href="{ route('sarpras.division.index') }">
                    Division
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.gedung.') ? ' active' : '' }"
                   href="{ route('sarpras.gedung.create') }">
                    Gedung
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.gedung.') ? ' active' : '' }"
                   href="{ route('sarpras.gedung.edit', ['userId' => auth()->id()]) }">
                    Gedung
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.gedung.') ? ' active' : '' }"
                   href="{ route('sarpras.gedung.index') }">
                    Gedung
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.gedung.') ? ' active' : '' }"
                   href="{ route('sarpras.gedung.show', ['userId' => auth()->id()]) }">
                    Gedung
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.kepala.') ? ' active' : '' }"
                   href="{ route('sarpras.kepala.index') }">
                    Kepala
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.kepala.') ? ' active' : '' }"
                   href="{ route('sarpras.kepala.show', ['userId' => auth()->id()]) }">
                    Kepala
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.laporan.') ? ' active' : '' }"
                   href="{ route('sarpras.laporan.export') }">
                    Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.laporan.') ? ' active' : '' }"
                   href="{ route('sarpras.laporan.index') }">
                    Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.laporan.') ? ' active' : '' }"
                   href="{ route('sarpras.laporan.nilai-aset') }">
                    Laporan.nilai Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.laporan.') ? ' active' : '' }"
                   href="{ route('sarpras.laporan.peminjaman') }">
                    Laporan.peminjaman
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.movements.') ? ' active' : '' }"
                   href="{ route('sarpras.movements.index') }">
                    Movements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.movements.') ? ' active' : '' }"
                   href="{ route('sarpras.movements.show', ['userId' => auth()->id()]) }">
                    Movements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.peminjaman.') ? ' active' : '' }"
                   href="{ route('sarpras.peminjaman.create') }">
                    Peminjaman
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.peminjaman.') ? ' active' : '' }"
                   href="{ route('sarpras.peminjaman.index') }">
                    Peminjaman
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.peminjaman.') ? ' active' : '' }"
                   href="{ route('sarpras.peminjaman.show', ['userId' => auth()->id()]) }">
                    Peminjaman
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.pengadaan.') ? ' active' : '' }"
                   href="{ route('sarpras.pengadaan.create') }">
                    Pengadaan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.pengadaan.') ? ' active' : '' }"
                   href="{ route('sarpras.pengadaan.edit', ['userId' => auth()->id()]) }">
                    Pengadaan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.pengadaan.') ? ' active' : '' }"
                   href="{ route('sarpras.pengadaan.index') }">
                    Pengadaan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.pengadaan.') ? ' active' : '' }"
                   href="{ route('sarpras.pengadaan.show', ['userId' => auth()->id()]) }">
                    Pengadaan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.perpindahan.') ? ' active' : '' }"
                   href="{ route('sarpras.perpindahan.create') }">
                    Perpindahan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.perpindahan.') ? ' active' : '' }"
                   href="{ route('sarpras.perpindahan.index') }">
                    Perpindahan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.perpindahan.') ? ' active' : '' }"
                   href="{ route('sarpras.perpindahan.show', ['userId' => auth()->id()]) }">
                    Perpindahan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.pic.') ? ' active' : '' }"
                   href="{ route('sarpras.pic.index') }">
                    Pic
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.pic.') ? ' active' : '' }"
                   href="{ route('sarpras.pic.show', ['userId' => auth()->id()]) }">
                    Pic
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.po.') ? ' active' : '' }"
                   href="{ route('sarpras.po.create') }">
                    Po
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.po.') ? ' active' : '' }"
                   href="{ route('sarpras.po.index') }">
                    Po
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.po.') ? ' active' : '' }"
                   href="{ route('sarpras.po.show', ['userId' => auth()->id()]) }">
                    Po
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.predictive.') ? ' active' : '' }"
                   href="{ route('sarpras.predictive.index') }">
                    Predictive
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.qr.') ? ' active' : '' }"
                   href="{ route('sarpras.qr.audit', ['userId' => auth()->id()]) }">
                    Qr.audit
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.qr.') ? ' active' : '' }"
                   href="{ route('sarpras.qr.bulk-audit') }">
                    Qr.bulk Audit
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.qr.') ? ' active' : '' }"
                   href="{ route('sarpras.qr.index') }">
                    Qr
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.qr.') ? ' active' : '' }"
                   href="{ route('sarpras.qr.lookup-page') }">
                    Qr.lookup Page
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.qr.') ? ' active' : '' }"
                   href="{ route('sarpras.qr.pdf') }">
                    Qr
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.qr.') ? ' active' : '' }"
                   href="{ route('sarpras.qr.print') }">
                    Qr.print
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.qr.') ? ' active' : '' }"
                   href="{ route('sarpras.qr.scanner') }">
                    Qr.scanner
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.ruang.') ? ' active' : '' }"
                   href="{ route('sarpras.ruang.create') }">
                    Ruang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.ruang.') ? ' active' : '' }"
                   href="{ route('sarpras.ruang.edit', ['userId' => auth()->id()]) }">
                    Ruang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.ruang.') ? ' active' : '' }"
                   href="{ route('sarpras.ruang.index') }">
                    Ruang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.ruang.') ? ' active' : '' }"
                   href="{ route('sarpras.ruang.show', ['userId' => auth()->id()]) }">
                    Ruang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.rvr.') ? ' active' : '' }"
                   href="{ route('sarpras.rvr.index') }">
                    Rvr
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.rvr.') ? ' active' : '' }"
                   href="{ route('sarpras.rvr.show') }">
                    Rvr
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.sparepart.') ? ' active' : '' }"
                   href="{ route('sarpras.sparepart.create') }">
                    Sparepart
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.sparepart.') ? ' active' : '' }"
                   href="{ route('sarpras.sparepart.edit', ['userId' => auth()->id()]) }">
                    Sparepart
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.sparepart.') ? ' active' : '' }"
                   href="{ route('sarpras.sparepart.index') }">
                    Sparepart
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.sparepart.') ? ' active' : '' }"
                   href="{ route('sarpras.sparepart.low-stock') }">
                    Sparepart.low Stock
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.sparepart.') ? ' active' : '' }"
                   href="{ route('sarpras.sparepart.show', ['userId' => auth()->id()]) }">
                    Sparepart
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.teknisi.') ? ' active' : '' }"
                   href="{ route('sarpras.teknisi.dashboard') }">
                    Teknisi.dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.teknisi.') ? ' active' : '' }"
                   href="{ route('sarpras.teknisi.show', ['userId' => auth()->id()]) }">
                    Teknisi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.teknisi.') ? ' active' : '' }"
                   href="{ route('sarpras.teknisi.snapshot', ['userId' => auth()->id()]) }">
                    Teknisi.snapshot
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.aset.create') }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.aset.edit', ['userId' => auth()->id()]) }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.aset.import') }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.aset.index') }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.aset.show', ['userId' => auth()->id()]) }">
                    Aset
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.dashboard') }">
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.kerusakan.index') }">
                    Kerusakan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.pengadaan.index') }">
                    Pengadaan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.ruang.create') }">
                    Ruang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.ruang.index') }">
                    Ruang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.user.') ? ' active' : '' }"
                   href="{ route('sarpras.user.ruang.show', ['userId' => auth()->id()]) }">
                    Ruang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.vendor.') ? ' active' : '' }"
                   href="{ route('sarpras.vendor.create') }">
                    Vendor
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.vendor.') ? ' active' : '' }"
                   href="{ route('sarpras.vendor.edit', ['userId' => auth()->id()]) }">
                    Vendor
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.vendor.') ? ' active' : '' }"
                   href="{ route('sarpras.vendor.index') }">
                    Vendor
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.vendor.') ? ' active' : '' }"
                   href="{ route('sarpras.vendor.rank') }">
                    Vendor.rank
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'sarpras.vendor.') ? ' active' : '' }"
                   href="{ route('sarpras.vendor.show', ['userId' => auth()->id()]) }">
                    Vendor
                </a>
            </li>
        </ul>
    </div>
</li>
@endif

@if(in_array('Super Admin', method_exists($user, 'roles') ? $user->roles->pluck('name')->toArray() : []))
<!-- CATATAN: Route di bawah ini belum terpetakan ke Role/Jabatan manapun. Hanya tampil untuk Super Admin untuk audit -->
<li class="menu-title"><span>Unmapped / Pending Assignment</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{ isActiveMaster($currentRoute, '.') ? ' active' : '' }"
       href="#unmapped" data-bs-toggle="collapse" role="button"
       aria-expanded="{ false }" aria-controls="unmapped">
        <i class="ri-question-line"></i>
        <span>Unmapped Routes (14)</span>
    </a>
    <div class="collapse menu-dropdown" id="unmapped">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'api.wings-by-dormitory.') ? ' active' : '' }"
                   href="{ route('api.wings-by-dormitory') }">
                    Api.wings By Dormitory
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'password.otp.') ? ' active' : '' }"
                   href="{ route('password.otp.form') }">
                    Password.otp.form
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'password.request.') ? ' active' : '' }"
                   href="{ route('password.request') }">
                    Password.request
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'password.reset.') ? ' active' : '' }"
                   href="{ route('password.reset.form') }">
                    Password.reset.form
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'secure.data.') ? ' active' : '' }"
                   href="{ route('secure.data.pegawai') }">
                    Secure.data.pegawai
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user..') ? ' active' : '' }"
                   href="{ route('user.', ['userId' => auth()->id()]) }">
                    user.
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.academic.') ? ' active' : '' }"
                   href="{ route('user.academic.index', ['userId' => auth()->id()]) }">
                    Academic
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.qr.') ? ' active' : '' }"
                   href="{ route('user.qr.image', ['userId' => auth()->id()]) }">
                    Qr.image
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.qr.') ? ' active' : '' }"
                   href="{ route('user.qr.print', ['userId' => auth()->id()]) }">
                    Qr.print
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'user.soal.') ? ' active' : '' }"
                   href="{ route('user.soal.create', ['userId' => auth()->id()]) }">
                    Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wilayah.cities.') ? ' active' : '' }"
                   href="{ route('wilayah.cities') }">
                    Wilayah.cities
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wilayah.districts.') ? ' active' : '' }"
                   href="{ route('wilayah.districts') }">
                    Wilayah.districts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wilayah.provinces.') ? ' active' : '' }"
                   href="{ route('wilayah.provinces') }">
                    Wilayah.provinces
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{ isActiveMaster($currentRoute, 'wilayah.villages.') ? ' active' : '' }"
                   href="{ route('wilayah.villages') }">
                    Wilayah.villages
                </a>
            </li>
        </ul>
    </div>
</li>
@endif
</ul>
