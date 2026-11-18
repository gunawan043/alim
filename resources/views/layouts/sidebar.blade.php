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
/* Submenu: text-only, tanpa icon. Icon hanya dipakai pada menu utama. */
.app-menu .menu-dropdown .nav-sm .nav-link i {
    display: none !important;
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
        || (method_exists($user, 'hasPermissionTo')
            && \App\Models\Permission::where('name', 'impersonate_role')->where('guard_name', 'web')->exists()
            && $user->hasPermissionTo('impersonate_role'));
    if ($canUseViewAs) {
        $viewAsRole = app(\App\Services\ViewAsService::class)->getCurrentViewRole();
    }
    $isViewingAs = $viewAsRole !== null;

    // ── Data user: role / jabatan / tugas tambahan ────────────────────────
    $userRoles = method_exists($user, 'roles')
        ? $user->roles->pluck('name')->toArray()
        : [];

    /*
     * Rangkap jabatan: satu user dapat menjadi Kepala Asrama sekaligus
     * Kepala Satuan Pendidikan. Data kepala pada master asrama/sekolah
     * menjadi sumber kebenaran, jadi kedua sidebar tetap ditampilkan
     * meskipun role Spatie hanya menyimpan salah satunya.
     */
    $isDormHead = false;
    $isSchoolHead = false;

    if ($user && ! $isSystemAdmin) {
        try {
            $isDormHead = \App\Models\Dormitory::query()
                ->where('head_id', $user->id)
                ->where('is_active', true)
                ->exists();

            $isSchoolHead = \App\Models\School::query()
                ->where('principal_user_id', $user->id)
                ->where('is_active', true)
                ->exists();
        } catch (\Throwable) {
            $isDormHead = false;
            $isSchoolHead = false;
        }
    }

    $isDualUnitHead = $isDormHead && $isSchoolHead;

    // Kepala unit adalah bukti peran yang lebih kuat daripada role Spatie:
    // akun lama bisa belum punya role yang sesuai, atau role-nya tertimpa
    // saat penetapan kepala yang lain. Tambahkan (jangan ganti) agar sidebar
    // kedua area tetap muncul untuk peran rangkap.
    $impliedRoles = [];

    if ($isDormHead) {
        $impliedRoles[] = 'Asrama';
    }

    if ($isSchoolHead) {
        $impliedRoles[] = 'Satuan Pendidikan';
    }

    if ($impliedRoles !== []) {
        $userRoles = array_values(array_unique(array_merge($userRoles, $impliedRoles)));
    }

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

    // Tugas tambahan dapat berasal dari GtkAdditionalTask (SK) maupun
    // OtherTeacherTask (penugasan operasional). Gabungkan keduanya.
    $userTugas = collect();

    if ($user && method_exists($user, 'tugasTambahan')) {
        $userTugas = $userTugas->merge($user->tugasTambahan->pluck('nama_tugas'));
    }

    if ($user && class_exists(\App\Models\OtherTeacherTask::class)) {
        $userTugas = $userTugas->merge(
            \App\Models\OtherTeacherTask::query()
                ->where('teacher_id', $user->id)
                ->where('is_active', 1)
                ->pluck('task_name')
        );
    }

    $userTugas = $userTugas
        ->filter()
        ->map(fn ($tugas) => trim((string) $tugas))
        ->unique()
        ->values()
        ->toArray();

    // ── Helper: cek role / jabatan / tugas ────────────────────────────────
    $hasRole = fn ($role) => in_array($role, $userRoles, true);

    /**
     * Normalisasi nama jabatan agar varian data hasil import tetap terbaca:
     *   "STAFF HUMAS DAN PERSONALIA (SUB OFFICE ASISTAND)" → "staf humas dan personalia"
     *   "STAF STAF TATA USAHA PENGASUHAN PUTRA"            → "staf tata usaha pengasuhan putra"
     *   "KEPALA UNIT GIZI & LOGISTIK (UGL)"                → "kepala unit gizi dan logistik"
     */
    $normalizeJabatan = static function ($value): string {
        $v = strtolower(trim((string) $value));
        if ($v === '') {
            return '';
        }
        $v = preg_replace('/\([^)]*\)/', ' ', $v);          // buang keterangan dalam kurung
        $v = str_replace('&', ' dan ', $v);
        $v = str_replace('staff', 'staf', $v);
        $v = preg_replace('/[^a-z0-9]+/', ' ', $v);          // tanda baca → spasi
        $v = preg_replace('/\s+/', ' ', $v);
        $v = preg_replace('/\b(\w+)( \1\b)+/', '$1', $v);  // kata ganda: "staf staf" → "staf"

        return trim($v);
    };

    /*
     * Satu GTK dapat merangkap: Waka (struktural), Wali Kelas (tugas), dan
     * Guru Mapel (SK mengajar). Jabatan bisa tersimpan di kepegawaian,
     * penempatan satuan kerja, penugasan struktural, atau tugas tambahan —
     * jadi semua sumber dibaca lewat UserJabatan, bukan hanya satu kolom.
     */
    $userJabatanCandidates = \App\Support\UserJabatan::candidates($user);

    $jabatanNorms = collect($userJabatanCandidates)
        ->map(fn ($j) => $normalizeJabatan($j))
        ->filter()
        ->unique()
        ->values()
        ->all();

    // Teks jabatan utama tetap dipakai untuk label dashboard.
    $jabatanNorm = $normalizeJabatan($userJabatan);

    /**
     * Cocokkan jabatan user dengan nama jabatan baku.
     * Cocok bila salah satu sumber sama persis ATAU diawali nama baku + spasi
     * (mis. "staf tata usaha pengasuhan putra" cocok dengan "Staf Tata Usaha").
     * Aturan "diawali" mencegah "Wakil Kepala Satuan Pendidikan" dianggap
     * sebagai "Kepala Satuan Pendidikan".
     */
    $hasJabatan = static function ($jab) use ($jabatanNorms, $normalizeJabatan): bool {
        $target = $normalizeJabatan($jab);

        if ($target === '' || $jabatanNorms === []) {
            return false;
        }

        foreach ($jabatanNorms as $candidate) {
            if ($candidate === $target || str_starts_with($candidate, $target.' ')) {
                return true;
            }
        }

        return false;
    };

    /**
     * Tugas tambahan sering ditulis dengan tambahan nama rombel/unit, mis.
     * "Wali Kelas 7A" atau "Waka Kurikulum". Cocokkan sebagai frasa, bukan
     * kesamaan persis, supaya varian itu tetap dikenali.
     */
    $hasTugas = static function ($tugas) use ($userTugas, $normalizeJabatan): bool {
        $target = $normalizeJabatan($tugas);

        if ($target === '') {
            return false;
        }

        foreach ($userTugas as $owned) {
            $ownedNorm = $normalizeJabatan($owned);

            if ($ownedNorm === $target
                || str_starts_with($ownedNorm, $target.' ')
                || str_contains($ownedNorm, $target)) {
                return true;
            }
        }

        return false;
    };

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
        'Operator Perpustakaan Santri' => 'perpustakaan',
        'Operator Asrama Santri' => 'asrama-operator',
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
        'Guru Koordinator OSIPP'                => 'koordinator-osipp',
        'Koordinator OSIPP'                     => 'koordinator-osipp',
        'Pembina OSIPP'                         => 'koordinator-osipp',
        'Koordinator Ekstrakurikuler'           => 'koordinator-ekskul',
        'Koordinator Laboratorium'              => 'koordinator-lab',
        'Koordinator Sarpras Satuan Pendidikan' => 'koordinator-sarpras',
        'Koordinator Sarpras'                   => 'koordinator-sarpras',    // alias
        'Koordinator Guru Umum'                 => 'koordinator-guru-umum',
        'Koordinator Guru Agama'                => 'koordinator-guru-agama',
        'Koordinator Guru Hadits'               => 'koordinator-guru-hadits',
        'Koordinator Guru Bahasa Arab'          => 'koordinator-guru-bahasa-arab',
        'Koordinator Guru Tahfidz'              => 'koordinator-guru-tahfidz',
        // Tim Kurikulum memakai modul Kurikulum yang sama dengan koordinator.
        // Sebelumnya dipetakan ke file yang tidak ada sehingga menu hilang.
        'Tim Kurikulum'                         => 'koordinator-kurikulum',
        'Tim Kesiswaan'                         => 'tim-kesiswaan',
        'Pembina Ekstrakurikuler'               => 'pembina-ekskul',
        'Admin UKS Putra'                       => 'admin-uks-putra',
        'Admin UKS Putri'                       => 'admin-uks-putri',

        // ── Tugas tambahan lain di luar tiga tugas utama ─────────────
        'Koordinator Alumni'                    => 'koordinator-alumni',
        'Staf Penitipan Barang'                 => 'staf-penitipan-barang',
        'Bendahara Sekolah'                     => 'bendahara-sekolah',

        // Varian "Waka <bidang>" yang sering tersimpan sebagai tugas tambahan.
        'Waka Kurikulum'                        => 'koordinator-kurikulum',
        'Wakil Kurikulum'                       => 'koordinator-kurikulum',
        'Waka Kesiswaan'                        => 'koordinator-kesiswaan',
        'Wakil Kesiswaan'                       => 'koordinator-kesiswaan',
        'Waka Sarpras'                          => 'koordinator-sarpras',
        'Waka Sarana Prasarana'                 => 'koordinator-sarpras',
        'Waka Pengasuhan'                       => 'waka-pengasuhan',
        'Jam Kajian & Waka Pengasuhan'          => 'waka-pengasuhan',
        'Kepala Sekolah'                        => 'kepala-satuan-pendidikan',
        'Kepala Satuan Pendidikan'              => 'kepala-satuan-pendidikan',
    ];

    /*
     * Nama tugas tambahan di data nyata tidak selalu sama persis dengan
     * katalog. Contoh: "Wali Kelas 7A", "Waka Kurikulum (SK 2026)".
     * Resolver ini mencocokkan nama terpanjang yang menjadi awalan/terkandung
     * dalam nama tugas, sehingga seluruh varian tetap mendapat menu.
     */
    $resolveTugasSlug = static function (?string $tugasName) use ($tugasFileMap): ?string {
        $name = strtolower(trim((string) $tugasName));

        if ($name === '') {
            return null;
        }

        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $name);
        $normalized = trim(preg_replace('/\s+/', ' ', (string) $normalized));

        $candidates = $tugasFileMap;
        uksort($candidates, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($candidates as $key => $slug) {
            $needle = strtolower(trim((string) $key));
            $needleNorm = trim(preg_replace('/\s+/', ' ', (string) preg_replace('/[^a-z0-9]+/', ' ', $needle)));

            if ($normalized === $needleNorm
                || str_starts_with($normalized, $needleNorm.' ')
                || str_contains($normalized, $needleNorm)) {
                return $slug;
            }
        }

        return null;
    };

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
        'Guru Koordinator OSIPP'                => 'osipp.dashboard',
        'Koordinator OSIPP'                     => 'osipp.dashboard',
        'Pembina OSIPP'                         => 'osipp.dashboard',
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

            // Varian nama tugas (mis. "Wali Kelas 7A") tetap dipetakan
            // ke dashboard tugas yang sama.
            $normalized = strtolower(trim((string) $tugas));
            foreach ($tugasDashboardMap as $key => $routeName) {
                $needle = strtolower(trim((string) $key));
                if ($normalized === $needle
                    || str_starts_with($normalized, $needle.' ')
                    || str_contains($normalized, $needle)) {
                    $myDashboardRoute = $routeName;
                    break 2;
                }
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
                {{-- ALIM AI — tersedia untuk semua pegawai --}}
                <li class="menu-title"><span>ALIM AI</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ai.']) ? ' active' : '' }}"
                       href="{{ route('user.ai.index', ['userId' => $userId]) }}">
                        <i class="ri-sparkling-2-line"></i><span>ALIM AI</span>
                    </a>
                </li>

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
                     Render role + tugas tambahan yang belum terwakili
                     di sidebar role (hindari menu ganda saat rangkap).
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

                    {{-- 3a-bis. Menu Guru untuk role non-Satuan Pendidikan.
                         Guru yang berperan sebagai Pimpinan/Asrama/Departemen
                         tetap mendapat menu mengajar lengkap sampai daftar
                         mapel/kelas yang diampu. Sidebar Satuan Pendidikan
                         sudah memuat menu ini, jadi tidak diulang. --}}
                    @php
                        $hasSatuanRoleForGuruMenu = in_array('Satuan Pendidikan', $userRoles, true);
                        $hasTeacherWorkspace = false;

                        if (! $hasSatuanRoleForGuruMenu && $user && $userId) {
                            $activeAyForGuruMenu = \App\Models\AcademicYear::query()
                                ->where('is_active', true)
                                ->first();

                            if ($activeAyForGuruMenu) {
                                $hasTeacherWorkspace = \App\Models\TeachingAssignment::query()
                                    ->where('teacher_id', $userId)
                                    ->where('status', 'active')
                                    ->where('academic_year_id', $activeAyForGuruMenu->id)
                                    ->exists()
                                    || \App\Models\TeacherAdminBook::query()
                                        ->forTeacherWorkspace($userId)
                                        ->where('is_active', true)
                                        ->where('academic_year_id', $activeAyForGuruMenu->id)
                                        ->exists();
                            }
                        }
                    @endphp

                    @if($hasTeacherWorkspace)
                        @include('layouts.sidebar.partials.menu-guru')
                        @php $renderedCount++; @endphp
                    @endif

                    {{-- 3b. Tugas tambahan.
                         Sidebar Satuan Pendidikan sudah memuat menu tugas
                         berikut secara terpadu. Jangan meng-include sidebar
                         tugas terpisah untuk tugas-tugas tersebut agar menu
                         tidak dobel saat user merangkap jabatan. Tugas yang
                         belum terwakili (asrama, perizinan, UKS, OSIPP)
                         tetap ditampilkan dari sidebar tugasnya. --}}
                    @php
                        $satuanIntegratedTugasSlugs = [
                            'wali-kelas',
                            'koordinator-kurikulum',
                            'koordinator-kesiswaan',
                            'koordinator-ekskul',
                            'koordinator-lab',
                            'koordinator-sarpras',
                            'koordinator-guru-umum',
                            'koordinator-guru-agama',
                            'koordinator-guru-hadits',
                            'koordinator-guru-bahasa-arab',
                            'koordinator-guru-tahfidz',
                            'tim-kesiswaan',
                            'pembina-ekskul',
                            // Modul berikut sudah menyatu di sidebar Satuan
                            // Pendidikan (Kesiswaan → Alumni, Keuangan →
                            // Bendahara). Jangan di-include ulang.
                            'koordinator-alumni',
                            'bendahara-sekolah',
                            'kepala-satuan-pendidikan',
                            'waka-pengasuhan',
                        ];
                        $hasSatuanRole = in_array('Satuan Pendidikan', $userRoles, true);
                        $renderedTugasSlugs = [];
                    @endphp
                    @foreach($userTugas as $tugasName)
                        @php
                            // Resolusi toleran terhadap varian nama ("Wali Kelas 7A").
                            $slug = $resolveTugasSlug($tugasName);
                            $viewPath = $slug ? 'layouts.sidebar.tugas-tambahan.' . $slug : null;
                            $alreadyIntegrated = $hasSatuanRole
                                && in_array($slug, $satuanIntegratedTugasSlugs, true);
                            // Satu file sidebar hanya dirender sekali walau user
                            // memegang beberapa tugas yang memakai modul sama.
                            $alreadyRendered = $slug && in_array($slug, $renderedTugasSlugs, true);
                        @endphp
                        @if($viewPath && ! $alreadyIntegrated && ! $alreadyRendered && view()->exists($viewPath))
                            @include($viewPath)
                            @php
                                $renderedTugasSlugs[] = $slug;
                                $renderedCount++;
                            @endphp
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
                               href="{{ route('user.notifications.page', ['userId' => $userId]) }}">
                                <i class="ri-notification-3-line"></i><span>Notifikasi</span>
                            @include('layouts.sidebar.partials.indicator-badge', ['key' => 'notifications', 'title' => 'Notifikasi belum dibaca'])</a>
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

                <li class="menu-title"><span>Bantuan &amp; Laporan</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.feedback.']) ? ' active' : '' }}"
                       href="{{ route('user.feedback.index', ['userId' => $userId]) }}">
                        <i class="ri-feedback-line"></i><span>Kritik &amp; Saran / Bug</span>
                    @include('layouts.sidebar.partials.indicator-badge', ['key' => 'feedback_baru', 'title' => 'Laporan baru yang belum ditindaklanjuti'])</a>
                </li>

            </ul>
        </div>
    </div>
    <div class="sidebar-background"></div>
</div>

<!-- Overlay: close sidebar on mobile -->
<div class="vertical-overlay"></div>

<script>
    (function () {
        function scrollSidebarToActive() {
            const candidates = Array.from(document.querySelectorAll('#navbar-nav .nav-link.active'));
            const active = candidates.find(function (link) {
                const rect = link.getBoundingClientRect();
                return link.offsetParent !== null && rect.width > 0 && rect.height > 0;
            }) || candidates[candidates.length - 1];

            const sidebar = document.querySelector('.scrollbar_sidebar');
            if (!active || !sidebar) return;

            const scroller = sidebar.querySelector('.simplebar-content-wrapper') || sidebar;
            const activeRect = active.getBoundingClientRect();
            const scrollRect = scroller.getBoundingClientRect();

            if (activeRect.top >= scrollRect.top && activeRect.bottom <= scrollRect.bottom) {
                return;
            }

            const target = scroller.scrollTop
                + (activeRect.top - scrollRect.top)
                - (scrollRect.height / 2)
                + (activeRect.height / 2);

            scroller.scrollTo({ top: Math.max(0, target), behavior: 'auto' });
        }

        function initSidebarActiveScroll() {
            setTimeout(scrollSidebarToActive, 200);
            setTimeout(scrollSidebarToActive, 650);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initSidebarActiveScroll);
        } else {
            initSidebarActiveScroll();
        }
    })();
</script>