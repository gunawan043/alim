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

@php
    /* ═══════════════════════════════════════════════════════════════════════
       APP MENU — DISPATCHER
       ───────────────────────────────────────────────────────────────────────
       Tugas   : Menyiapkan konteks user (role, jabatan, tugas tambahan) dan
                 memilih sidebar role mana yang akan di-render.
       Prinsip : 1 folder = 1 role → layouts/sidebar/{folder}/sidebar.blade.php
                 Tugas tambahan → layouts/sidebar/tugas-tambahan/{file}.blade.php
       ───────────────────────────────────────────────────────────────────────
       Helper yang tersedia untuk semua sidebar yang di-include:
         $userId         — ID user yang login
         $currentRoute   — nama route aktif (mis. 'user.sa.users.index')
         $hasRole()      — fn(string)   → cek user punya role tertentu
         $hasJabatan()   — fn(string)   → cek user punya jabatan tertentu
         $hasTugas()     — fn(string)   → cek user punya tugas tambahan tertentu
         isActiveAny()   — fn(route, [patterns]) → cek route aktif diawali salah satu pattern
         $userRoles      — array nama role user
         $userJabatan    — string nama jabatan user
         $userTugas      — array nama tugas tambahan user
         $myDashboardRoute — nama route dashboard sesuai jabatan/tugas user
       ═══════════════════════════════════════════════════════════════════════ */

    $user = auth()->user();

    // ── Deteksi Super Admin / System Admin ────────────────────────────────
    $isSystemAdmin = method_exists($user, 'isSystemAdmin') && $user->isSystemAdmin();

    // ── Deteksi View-As (Super Admin / pemegang permission impersonate) ───
    $viewAsRole   = null;
    $canUseViewAs = $isSystemAdmin
        || (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo('impersonate_role'));
    if ($canUseViewAs) {
        $viewAsRole = app(\App\Services\ViewAsService::class)->getCurrentViewRole();
    }
    $isViewingAs = $viewAsRole !== null;

    // ── Data user: role / jabatan / tugas tambahan ────────────────────────
    $userRoles = method_exists($user, 'roles')
        ? $user->roles->pluck('name')->toArray()
        : [];

    // ── Jabatan: PRIORITAS dari employment.jabatan ────────────────────────
    // Setelah import GTK, jabatan disimpan di `gtk_employments.jabatan` (string),
    // BUKAN di relasi `$user->jabatan` (yang mungkin UUID/objek/null).
    $userJabatan = null;

    if ($user && $user->employment) {
        $userJabatan = $user->employment->jabatan;
    }

    if (! $userJabatan && $user && method_exists($user, 'jabatan') && $user->jabatan) {
        // Fallback: relasi jabatan (kalau ada)
        if (is_object($user->jabatan)) {
            $userJabatan = $user->jabatan->nama
                ?? $user->jabatan->name
                ?? null;
        } elseif (is_string($user->jabatan)) {
            $userJabatan = $user->jabatan;
        }
    }

    $userJabatan = trim((string) ($userJabatan ?? ''));

    $userTugas = method_exists($user, 'tugasTambahan')
        ? $user->tugasTambahan->pluck('nama')->toArray()
        : [];

    // ── Helper: cek role / jabatan / tugas (case-insensitive untuk jabatan) ─
    $hasRole    = fn ($role)  => in_array($role, $userRoles, true);
    $hasJabatan = fn ($jab)   => strtolower(trim($userJabatan)) === strtolower(trim($jab));
    $hasTugas   = fn ($tugas) => in_array($tugas, $userTugas, true);

    // ── Helper: context route ─────────────────────────────────────────────
    $currentRoute = request()->route() ? request()->route()->getName() : '';
    $userId       = $user?->id;

    // Helper: cek route aktif diawali salah satu pattern
    if (! function_exists('isActiveAny')) {
        function isActiveAny($routeName, $patterns = []) {
            if (! is_string($routeName) || $routeName === '') return false;

            if (is_string($patterns)) {
                $patterns = [$patterns];
            }
            if (! is_array($patterns)) return false;

            $flat = [];
            array_walk_recursive($patterns, function ($value) use (&$flat) {
                if (is_string($value) && $value !== '') {
                    $flat[] = $value;
                }
            });

            foreach ($flat as $p) {
                if (str_starts_with($routeName, $p)) return true;
            }
            return false;
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       PEMETAAN ROLE → FOLDER SIDEBAR
       ═══════════════════════════════════════════════════════════════════════ */
    $roleFolderMap = [
        'Pimpinan'              => 'pimpinan',
        'Satuan Pendidikan'     => 'satuan-pendidikan',
        'Asrama'                => 'asrama',
        'UKS'                   => 'uks',
        'Departemen Tahfidz'    => 'departemen-tahfidz',
        'Departemen Bahasa'     => 'departemen-bahasa',
        'Perpustakaan'          => 'perpustakaan',
        'Satuan Keamanan'       => 'satpam',
        'Humas Personalia'      => 'humas-personalia',
        'Unit Rumah Tangga'     => 'unit-rumah-tangga',
        'Keuangan'              => 'keuangan',
        'Teknologi Informasi'   => 'teknologi-informasi',
        'Unit Pelayanan Gizi'   => 'unit-pelayanan-gizi',
    ];

    /* ═══════════════════════════════════════════════════════════════════════
       PEMETAAN TUGAS TAMBAHAN → FILE
       ═══════════════════════════════════════════════════════════════════════ */
        $tugasFileMap = [
        'Wali Kelas'                            => 'wali-kelas',
        'Wali Kamar'                            => 'wali-kamar',
        'Wali Asrama'                           => 'wali-asrama',
        'Staf Asrama'                           => 'staf-asrama',            // ← BARU
        'Staf Perizinan'                        => 'staf-perizinan',         // ← BARU
        'Koordinator Kurikulum'                 => 'koordinator-kurikulum',
        'Koordinator Kesiswaan'                 => 'koordinator-kesiswaan',
        'Koordinator Ekstrakurikuler'           => 'koordinator-ekskul',
        'Koordinator Laboratorium'              => 'koordinator-lab',
        'Koordinator Sarpras Satuan Pendidikan' => 'koordinator-sarpras',
        'Koordinator Sarpras'                   => 'koordinator-sarpras',    // alias
        'Koordinator Guru Umum'                 => 'koordinator-guru-umum',
        'Koordinator Guru Agama'                => 'koordinator-guru-agama',
        'Koordinator Guru Hadits'               => 'koordinator-guru-hadits',
        'Koordinator Guru Bahasa Arab'          => 'koordinator-guru-bahasa-arab',
        'Koordinator Guru Tahfidz'              => 'koordinator-guru-tahfidz',
        'Tim Kurikulum'                         => 'tim-kurikulum',
        'Tim Kesiswaan'                         => 'tim-kesiswaan',
        'Pembina Ekstrakurikuler'               => 'pembina-ekskul',
        'Admin UKS Putra'                       => 'admin-uks-putra',
        'Admin UKS Putri'                       => 'admin-uks-putri',
    ];

    /* ═══════════════════════════════════════════════════════════════════════
       PEMETAAN DASHBOARD PER JABATAN
       Dipakai untuk menentukan dashboard utama user (fallback Mode 3).
       ═══════════════════════════════════════════════════════════════════════ */
    $jabatanDashboardMap = [
        // ── Satuan Pendidikan ────────────────────────────────────────
        'Kepala Satuan Pendidikan'       => 'user.dashboard.kepala-satuan-pendidikan',
        'Wakil Kepala Satuan Pendidikan' => 'user.dashboard.wakil-kepala',
        'Guru Umum'                      => 'user.dashboard.guru',
        'Guru Agama'                     => 'user.dashboard.guru',
        'Guru Hadits'                    => 'user.dashboard.guru',
        'Guru Bahasa Arab'               => 'user.dashboard.guru',
        'Guru Tahfidz'                   => 'user.dashboard.guru',
        'Kepala Tata Usaha'              => 'user.dashboard.ka-tata-usaha',
        'Tata Usaha'                     => 'user.dashboard.staf-tata-usaha',
        'Bendahara Sekolah'              => 'user.dashboard.bendahara',

        // ── Asrama / Kepengasuhan ─────────────────────────────────────
        'Kepala Asrama'                  => 'user.dashboard.asrama',
        'Wakil Kepala Asrama'            => 'user.dashboard.asrama',
        'Tata Usaha Asrama'              => 'user.dashboard.asrama',
        'Staf Perizinan'                 => 'user.dashboard.asrama',
        'Musrif'                         => 'user.dashboard.pengasuh',
        'Musrifah'                       => 'user.dashboard.pengasuh',

        // ── UKS ──────────────────────────────────────────────────────
        'Kepala UKS'                     => 'user.dashboard.boarding-health',
        'Staf UKS Putra'                 => 'user.dashboard.boarding-health',
        'Staf UKS Putri'                 => 'user.dashboard.boarding-health',

        // ── Pimpinan ─────────────────────────────────────────────────
        'Mudir'                          => 'root',
        'Wadir 1'                        => 'root',
        'Wadir 2'                        => 'root',

        // ── Kepala Unit (non-sekolah) ─────────────────────────────────
        'Kepala Unit Rumah Tangga'       => 'sarpras.dashboard',
        'Koordinator Sarana Prasarana'   => 'sarpras.dashboard',
        'Kepala Humas & Personalia'      => 'user.dashboard',
        'Kepala Humas'                   => 'user.dashboard',
        'Kepala Personalia'              => 'user.dashboard',
        'Kepala Departemen Tahfidz'      => 'root',
        'Kepala Departemen Bahasa'       => 'root',
        'Koordinator Perpustakaan'       => 'root',
        'Kepala Satuan Keamanan'         => 'root',
        'Kepala Unit Teknologi Informasi'=> 'root',
        'Kepala Unit Gizi & Logistik'    => 'root',
    ];

    /* ═══════════════════════════════════════════════════════════════════════
       PEMETAAN DASHBOARD PER TUGAS TAMBAHAN
       ═══════════════════════════════════════════════════════════════════════ */
    $tugasDashboardMap = [
        'Wali Kelas'                            => 'user.dashboard.wali-kelas',
        'Koordinator Kurikulum'                 => 'user.dashboard.koordinator-kurikulum',
        'Koordinator Kesiswaan'                 => 'user.dashboard.koordinator-kesiswaan',
        'Koordinator Ekstrakurikuler'           => 'user.dashboard.koordinator-ekskul',
        'Koordinator Laboratorium'              => 'user.dashboard.koordinator-lab',
        'Koordinator Sarpras Satuan Pendidikan' => 'user.dashboard.koordinator-sarpras',
        'Koordinator Guru Umum'                 => 'user.dashboard.koordinator-guru',
        'Koordinator Guru Agama'                => 'user.dashboard.koordinator-guru',
        'Koordinator Guru Hadits'               => 'user.dashboard.koordinator-guru',
        'Koordinator Guru Bahasa Arab'          => 'user.dashboard.koordinator-guru',
        'Koordinator Guru Tahfidz'              => 'user.dashboard.koordinator-guru',
    ];

    /* ═══════════════════════════════════════════════════════════════════════
       RESOLUSI DASHBOARD USER SAAT INI
       Prioritas: jabatan → tugas tambahan → fallback root
       ═══════════════════════════════════════════════════════════════════════ */
    $myDashboardRoute = null;

    if ($userJabatan && isset($jabatanDashboardMap[trim($userJabatan)])) {
        $myDashboardRoute = $jabatanDashboardMap[trim($userJabatan)];
    }

    if (! $myDashboardRoute && ! empty($userTugas)) {
        foreach ($userTugas as $tugas) {
            if (isset($tugasDashboardMap[$tugas])) {
                $myDashboardRoute = $tugasDashboardMap[$tugas];
                break;
            }
        }
    }

    $myDashboardRoute = $myDashboardRoute ?: 'root';
@endphp

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
    <div data-simplebar class="scrollbar_sidebar">
        <div class="container-fluid mt-3">
            @include('components.user-sidebar-profile')
            <div id="two-column-menu"></div>

            <ul class="navbar-nav" id="navbar-nav" style="padding-bottom: 50px">

                {{-- ═══════════════════════════════════════════════════════════
                     MODE 1 — VIEW-AS (Super Admin / impersonate)
                     Hanya render role yang sedang di-View-As, tanpa tugas tambahan.
                     ═══════════════════════════════════════════════════════════ --}}
                @if($isViewingAs)

                    @if(isset($roleFolderMap[$viewAsRole]))
                        @includeIf('layouts.sidebar.' . $roleFolderMap[$viewAsRole] . '.sidebar')
                    @else
                        <li class="nav-item">
                            <span class="nav-link text-muted px-3">
                                <i class="ri-error-warning-line me-1"></i>
                                Role '{{ $viewAsRole }}' belum punya menu sidebar.
                            </span>
                        </li>
                    @endif

                {{-- ═══════════════════════════════════════════════════════════
                     MODE 2 — SUPER ADMIN (login langsung)
                     Selalu mendapat sidebar super-admin penuh.
                     ═══════════════════════════════════════════════════════════ --}}
                @elseif($isSystemAdmin)

                    @includeIf('layouts.sidebar.super-admin.sidebar')

                {{-- ═══════════════════════════════════════════════════════════
                     MODE 3 — USER NORMAL
                     Render semua role + semua tugas tambahan.
                     ═══════════════════════════════════════════════════════════ --}}
                @else

                    @php
                        $renderedCount = 0;
                    @endphp

                    {{-- 3a. Semua role yang dimiliki user --}}
                    @foreach($userRoles as $roleName)
                        @php
                            $folder = $roleFolderMap[$roleName] ?? null;
                            $viewPath = $folder ? 'layouts.sidebar.' . $folder . '.sidebar' : null;
                        @endphp
                        @if($viewPath && view()->exists($viewPath))
                            @include($viewPath)
                            @php $renderedCount++; @endphp
                        @endif
                    @endforeach

                    {{-- 3b. Semua tugas tambahan yang dimiliki user --}}
                    @foreach($userTugas as $tugasName)
                        @php
                            $slug = $tugasFileMap[$tugasName] ?? null;
                            $viewPath = $slug ? 'layouts.sidebar.tugas-tambahan.' . $slug : null;
                        @endphp
                        @if($viewPath && view()->exists($viewPath))
                            @include($viewPath)
                            @php $renderedCount++; @endphp
                        @endif
                    @endforeach

                    {{-- 3c. Fallback: user tanpa role / tugas tambahan yang dikenali --}}
                    @if($renderedCount === 0)
                        <li class="menu-title"><span>Menu</span></li>

                        {{-- Dashboard dinamis sesuai jabatan / tugas tambahan --}}
                        <li class="nav-item">
                            <a class="nav-link menu-link{{ isActiveAny($currentRoute, [$myDashboardRoute, 'root']) ? ' active' : '' }}"
                               href="{{ $myDashboardRoute === 'root'
                                    ? route('root')
                                    : route($myDashboardRoute, ['userId' => $userId]) }}">
                                <i class="ri-dashboard-3-line"></i><span>Dashboard</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}"
                               href="{{ route('user.profile.my', ['userId' => $userId]) }}">
                                <i class="ri-user-line"></i><span>Profil Saya</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
                               href="{{ route('user.notifications.index', ['userId' => $userId]) }}">
                                <i class="ri-notification-3-line"></i><span>Notifikasi</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
                               href="{{ route('user.todos.index', ['userId' => $userId]) }}">
                                <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
                               href="{{ route('user.kaldik.index', ['userId' => $userId]) }}">
                                <i class="ri-calendar-event-line"></i><span>Kalender Pendidikan</span>
                            </a>
                        </li>

                        @if($userJabatan)
                            <li class="menu-title"><span>Info</span></li>
                            <li class="nav-item">
                                <span class="nav-link text-muted px-3" style="font-size:0.8rem">
                                    <i class="ri-information-line me-1"></i>
                                    Jabatan: <strong>{{ $userJabatan }}</strong>
                                </span>
                            </li>
                        @endif
                    @endif

                @endif

            </ul>
        </div>
    </div>
    <div class="sidebar-background"></div>
</div>

<!-- Overlay: close sidebar on mobile -->
<div class="vertical-overlay"></div>