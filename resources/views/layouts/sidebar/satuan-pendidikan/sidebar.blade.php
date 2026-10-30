{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: SATUAN PENDIDIKAN
     ───────────────────────────────────────────────────────────────────────────
     Urutan menu mengikuti prioritas peran pengguna:

       1. Menu                 → Dashboard, Todo, Profil, Sekolah, Notifikasi, Approval
       2. WALI KELAS           → hanya bila benar-benar wali kelas (prioritas utama)
       3. ADMINISTRASI GURU    → guru mapel: Jadwal & Scan QR lebih dulu
       4. JABATAN              → jabatan struktural (Kepala, Waka, TU, Bendahara)
       5. TUGAS TAMBAHAN       → koordinator, tim, BK, pembina, supervisor
       6. Modul per peran      → di-include dari partials/modul-*.blade.php

     Menu lintas divisi (Bahasa, Tahfidz) hanya untuk Koordinator Bahasa,
     Koordinator Tahfidz, Waka, dan Kepala Satuan Pendidikan — bukan TU.
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Context ─────────────────────────────────────────────────────── */
    $user = auth()->user();
    $currentRoute = $currentRoute ?? (request()->route() ? request()->route()->getName() : '');
    $userId = $userId ?? $user?->id;

    /* ── Sekolah user (WAJIB — semua query difilter ke sini) ─────────── */
    $userWorkUnits = \App\Models\GtkWorkUnit::with('workUnit.school')
        ->where('user_id', $userId)
        ->whereHas('workUnit', fn ($q) => $q->where('is_active', true))
        ->get();
    $primaryWorkUnit   = $userWorkUnits->where('is_primary', true)->first() ?? $userWorkUnits->first();
    $primaryWorkUnitId = $primaryWorkUnit?->workUnit?->id;
    $sekolah           = $primaryWorkUnit?->workUnit?->school;
    $sekolahId         = $sekolah?->id;

    /* ── Tahun ajaran aktif (satu sumber — bukan perkiraan kalender) ──── */
    $activeAcademicYearId = \App\Models\AcademicYear::where('is_active', true)->value('id');

    /* ── Pengingat kandidat Mumtaz Murtafi' dari rapor terbit ─────────── */
    $mumtazPendingCount = $sekolahId
        ? \App\Models\MumtazMurtafiCandidate::query()
            ->where('school_id', $sekolahId)
            ->where('status', \App\Models\MumtazMurtafiCandidate::STATUS_PENDING)
            ->count()
        : \App\Models\MumtazMurtafiCandidate::query()
            ->where('status', \App\Models\MumtazMurtafiCandidate::STATUS_PENDING)
            ->count();

    /* ── Kelas yang diwalikan user (untuk Wali Kelas) — TA aktif saja ─── */
    $waliKelasGroups = $sekolahId
        ? \App\Models\StudyGroup::withoutGlobalScope('school_context')
            ->where('school_id', $sekolahId)
            ->where('homeroom_teacher_id', $userId)
            ->where('is_active', true)
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->with(['gradeLevel'])
            ->orderBy('name')
            ->get()
        : collect();
    $isActuallyWaliKelas = $waliKelasGroups->isNotEmpty();

    /* ── Semua rombel di sekolah (untuk TU/Kepala/Wakil) — TA AKTIF saja ─ */
    $sidebarStudyGroups = $sekolahId
        ? \App\Models\StudyGroup::withoutGlobalScope('school_context')
            ->where('school_id', $sekolahId)
            ->where('is_active', true)
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->with(['gradeLevel', 'homeroomTeacher'])
            ->orderedByGrade()
            ->get()
        : collect();

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepala       = $hasJabatan('Kepala Satuan Pendidikan');
    $isWakil        = $hasJabatan('Wakil Kepala Satuan Pendidikan');
    $isGuruUmum     = $hasJabatan('Guru Umum');
    $isGuruAgama    = $hasJabatan('Guru Agama');
    $isGuruHadits   = $hasJabatan('Guru Hadits');
    $isGuruArab     = $hasJabatan('Guru Bahasa Arab');
    $isGuruInggris  = $hasJabatan('Guru Bahasa Inggris');
    $isGuruTahfidz  = $hasJabatan('Guru Tahfidz');
    $isKepalaTU     = $hasJabatan('Kepala Tata Usaha') || $hasJabatan('Kepala Staf Tata Usaha');
    $isTU           = $hasJabatan('Tata Usaha') || $hasJabatan('Staf Tata Usaha');
    // Bendahara Sekolah dapat berupa jabatan struktural maupun tugas tambahan.
    $isBendahara    = $hasJabatan('Bendahara Sekolah') || $hasTugas('Bendahara Sekolah');
    $isKoorKurik    = $hasJabatan('Koordinator Kurikulum') || $hasTugas('Koordinator Kurikulum');
    $isKoorKesiswa  = $hasJabatan('Koordinator Kesiswaan') || $hasTugas('Koordinator Kesiswaan');
    $isKoorEkskul   = $hasJabatan('Koordinator Ekstrakurikuler')
        || $hasTugas('Koordinator Ekstrakurikuler');

    // Semua varian jabatan guru (GURU KELAS, GURU PAUD, GURU MAPEL, ...) dihitung guru.
    $isGuru         = $isGuruUmum || $isGuruAgama || $isGuruHadits || $isGuruArab || $isGuruInggris || $isGuruTahfidz
        || str_starts_with($jabatanNorm, 'guru');
    $isAdminTU      = $isKepalaTU || $isTU;
    $isStruktural   = $isKepala || $isWakil;
    $isKoordinator  = $isKoorKurik || $isKoorKesiswa || $isKoorEkskul;

    /* ── Pencocokan tugas tambahan (nama bebas, mis. "Wali Kelas 7A") ── */
    $tugasList = $userTugas ?? [];
    $tugasMatches = static function (string $needle) use ($tugasList): bool {
        foreach ($tugasList as $tugas) {
            if (str_contains(strtolower((string) $tugas), strtolower($needle))) {
                return true;
            }
        }

        return false;
    };

    /* ── Tugas Tambahan ──────────────────────────────────────────────── */
    // Wali kelas ditentukan oleh penugasan rombel (sumber utama), bukan teks tugas.
    $isWaliKelas       = $isActuallyWaliKelas;
    $isTimKurikulum    = $hasTugas('Tim Kurikulum');
    $isTimKesiswaan    = $hasTugas('Tim Kesiswaan');
    $isGuruBK          = $hasJabatan('Guru BK') || $hasJabatan('Guru Bimbingan Konseling')
        || $hasTugas('Guru BK') || $hasTugas('Guru Bimbingan Konseling')
        || $hasTugas('Koordinator BK') || $hasTugas('Konselor');
    $isPembinaEkskul   = $hasTugas('Pembina Ekstrakurikuler');
    $isKoorLab         = $hasTugas('Koordinator Laboratorium');
    $isKoorSarpras     = $hasTugas('Koordinator Sarpras Satuan Pendidikan');
    $isKoorGuruUmum    = $hasTugas('Koordinator Guru Umum');
    $isKoorGuruAgama   = $hasTugas('Koordinator Guru Agama');
    $isKoorGuruHadits  = $hasTugas('Koordinator Guru Hadits');
    $isKoorGuruArab    = $hasTugas('Koordinator Guru Bahasa Arab');
    $isKoorGuruTahfidz = $hasTugas('Koordinator Guru Tahfidz');

    $isKoorMapel       = $isKoorGuruUmum || $isKoorGuruAgama || $isKoorGuruHadits || $isKoorGuruArab || $isKoorGuruTahfidz;

    /* ── Koordinator lintas divisi ───────────────────────────────────── */
    $isKoorPerpus = $hasJabatan('Koordinator Perpustakaan') || $hasJabatan('Kepala Perpustakaan')
        || $tugasMatches('koordinator perpustakaan') || $tugasMatches('kepala perpustakaan');

    $isKoorBahasa = $hasJabatan('Koordinator Bahasa') || $hasJabatan('Koordinator Bahasa Arab')
        || $hasJabatan('Koordinator Bahasa Inggris')
        || $tugasMatches('koordinator bahasa') || $tugasMatches('coordinator bahasa');

    $isKoorTahfidz = $isKoorGuruTahfidz
        || $hasJabatan('Koordinator Tahfidz') || $hasJabatan('Koordinator Tahfidz Al-Qur\'an')
        || $tugasMatches('koordinator tahfidz');

    /* ── Bedakan jabatan struktural vs tugas tambahan (untuk label menu) ─ */
    $jabKoorKurik   = $hasJabatan('Koordinator Kurikulum');
    $jabKoorKesiswa = $hasJabatan('Koordinator Kesiswaan');
    $jabKoorEkskul  = $hasJabatan('Koordinator Ekstrakurikuler');
    $jabKoorPerpus  = $hasJabatan('Koordinator Perpustakaan') || $hasJabatan('Kepala Perpustakaan');
    $jabKoorBahasa  = $hasJabatan('Koordinator Bahasa') || $hasJabatan('Koordinator Bahasa Arab')
        || $hasJabatan('Koordinator Bahasa Inggris');
    $jabKoorTahfidz = $hasJabatan('Koordinator Tahfidz') || $hasJabatan('Koordinator Tahfidz Al-Qur\'an');
    $jabBendahara   = $hasJabatan('Bendahara Sekolah');

    $koorLabel = static function (string $nama, bool $isJabatan): string {
        return ($isJabatan ? 'Jabatan · ' : 'Tugas Tambahan · ').$nama;
    };

    /* ── Supervisor yang ditetapkan pada Pengaturan Supervisor ───────── */
    $supervisorProfileId = $sekolahId
        ? \App\Models\GtkProfile::where('user_id', $userId)->value('id')
        : null;
    $isConfiguredSupervisor = $supervisorProfileId && $sekolahId
        && \App\Models\SupervisiPengaturan::query()
            ->where('school_id', $sekolahId)
            ->where('supervisor_gtk_id', $supervisorProfileId)
            ->where('is_active', true)
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->exists();

    /* ── Absensi Kelas — wali kelas & kewenangan ─────────────────────── */
    $canAbsensiAuthority = $isKepala || $isWakil || $isAdminTU
        || $isKoorKurik || $isTimKurikulum
        || $isKoorKesiswa || $isTimKesiswaan
        || (function_exists('canPermission') && (
            canPermission('absensi-admin-tu') || canPermission('absensi-tata-usaha')
            || canPermission('absensi-wakil-kepala-sekolah') || canPermission('absensi-kepala-sekolah')
            || canPermission('absensi-kepala-sekolah-pondok')
        ));

    /* ── Absen Manual — restricted ───────────────────────────────────── */
    $canManualAbsen = ($isAdminTU || $isStruktural || $isKoordinator)
        && (function_exists('canPermission')
            ? canPermission('teacher-attendance_manual')
            : (method_exists(auth()->user(), 'hasPermissionTo') && auth()->user()->hasPermissionTo('teacher-attendance_manual'))
        );

    /* ── Opsi dashboard sesuai jabatan/tugas ─────────────────────────── */
    $dashboardOptions = [];
    $addDashboard = function (bool $condition, string $routeName, string $label, string $icon) use (&$dashboardOptions): void {
        if ($condition) {
            $dashboardOptions[] = compact('routeName', 'label', 'icon');
        }
    };

    $addDashboard($isKepala, 'user.dashboard.kepala-satuan-pendidikan', 'Dashboard Kepala', 'ri-vip-crown-line');
    $addDashboard($isWakil, 'user.dashboard.wakil-kepala', 'Dashboard Wakil', 'ri-shield-star-line');
    $addDashboard($isGuru && ! $isGuruTahfidz, 'user.dashboard.guru', 'Dashboard Guru', 'ri-user-star-line');
    $addDashboard($isGuruTahfidz, 'user.dashboard.tahfidz', 'Dashboard Tahfidz', 'ri-book-read-line');
    $addDashboard($isKepalaTU, 'user.dashboard.ka-tata-usaha', 'Dashboard Kepala TU', 'ri-admin-line');
    $addDashboard($isTU, 'user.dashboard.staf-tata-usaha', 'Dashboard Tata Usaha', 'ri-file-user-line');
    $addDashboard($isBendahara, 'user.dashboard.bendahara', 'Dashboard Bendahara', 'ri-money-dollar-circle-line');
    $addDashboard($isKoorKurik, 'user.dashboard.koordinator-kurikulum', 'Dashboard Kurikulum', 'ri-book-mark-line');
    $addDashboard($isKoorKesiswa, 'user.dashboard.koordinator-kesiswaan', 'Dashboard Kesiswaan', 'ri-group-line');
    $addDashboard($isKoorEkskul, 'user.dashboard.koordinator-ekskul', 'Dashboard Ekstrakurikuler', 'ri-basketball-line');
    $addDashboard($isWaliKelas, 'user.dashboard.wali-kelas', 'Dashboard Wali Kelas', 'ri-parent-line');
    $addDashboard($isKoorMapel, 'user.dashboard.koordinator-guru', 'Dashboard Koordinator Mapel', 'ri-team-line');
    $addDashboard($isKoorLab, 'lab.index', 'Dashboard Laboratorium', 'ri-flask-line');
    $addDashboard($isKoorSarpras, 'user.dashboard.koordinator-sarpras', 'Dashboard Koordinator Sarpras', 'ri-tools-line');

    if ($dashboardOptions === []) {
        $dashboardOptions[] = ['routeName' => 'root', 'label' => 'Dashboard', 'icon' => 'ri-home-6-line'];
    }

    $dashboardOptions = collect($dashboardOptions)->unique('routeName')->values()->all();

    /* ── Akses modul Kurikulum ───────────────────────────────────────── */
    $kurikulumAccess       = app(\App\Services\KurikulumAccess::class);
    $canAccessKurikulum    = $showAll || $kurikulumAccess->canAccessKurikulumModule($user);
    $isCurriculumManager   = $showAll || $kurikulumAccess->isKurikulumTeam($user);
    $isKspSupervisor       = $showAll || $kurikulumAccess->isKurikulumSupervisor($user);
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     1. MENU
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Menu</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.', 'root']) ? ' active' : '' }}"
       href="#sidebar_dashboard" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.dashboard.', 'root']) ? 'true' : 'false' }}"
       aria-controls="sidebar_dashboard">
        <i class="ri-dashboard-3-line"></i><span>Dashboard</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.dashboard.', 'root']) ? ' show' : '' }}" id="sidebar_dashboard">
        <ul class="nav nav-sm flex-column">
            @foreach($dashboardOptions as $dashboard)
                <li class="nav-item">
                    <a class="nav-link{{ $currentRoute === $dashboard['routeName'] ? ' active' : '' }}"
                       href="{{ $dashboard['routeName'] === 'root' ? route('root') : route($dashboard['routeName'], ['userId' => $userId]) }}">
                        <i class="{{ $dashboard['icon'] }} me-1"></i>{{ $dashboard['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i><span>Todo List</span>
    @include('layouts.sidebar.partials.indicator-badge', ['key' => 'todo_overdue', 'title' => 'Tugas yang sudah lewat tenggat'])</a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>

<li class="nav-item">
    @if($sekolahId)
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.show']) ? ' active' : '' }}"
       href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $sekolahId]) }}">
        <i class="ri-government-line"></i><span>Satuan Pendidikan</span>
    </a>
    @else
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.index']) ? ' active' : '' }}"
       href="{{ route('user.schools.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i><span>Satuan Pendidikan</span>
    </a>
    @endif
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
       href="{{ route('user.notifications.page', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    @include('layouts.sidebar.partials.indicator-badge', ['key' => 'notifications', 'title' => 'Notifikasi belum dibaca'])</a>
</li>

@if($showAll || $isStruktural || $isAdminTU || $isKoordinator)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ route('user.approvals.index', ['userId' => $userId]) }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    @include('layouts.sidebar.partials.indicator-badge', ['key' => 'approvals', 'title' => 'Permintaan menunggu persetujuan Anda'])</a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     2. WALI KELAS — prioritas utama bila benar-benar mengampu kelas
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($isWaliKelas)
    <li class="menu-title">
        <span>Wali Kelas · {{ $waliKelasGroups->pluck('name')->join(', ') ?: '-' }}</span>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ $currentRoute === 'user.dashboard.wali-kelas' ? ' active' : '' }}"
           href="{{ route('user.dashboard.wali-kelas', ['userId' => $userId]) }}">
            <i class="ri-parent-line"></i><span>Dashboard Wali Kelas</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index', 'user.students.show']) ? ' active' : '' }}"
           href="{{ route('user.students.index', ['userId' => $userId, 'study_group_id' => $waliKelasGroups->first()->id]) }}">
            <i class="ri-team-line"></i><span>Data Santri Kelas</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai-kelas.']) ? ' active' : '' }}"
           href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $waliKelasGroups->first()->id]) }}">
            <i class="ri-table-line"></i><span>Leger &amp; Cetak Rapor</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'nilai_kelas', 'title' => 'Kelas yang nilainya belum lengkap atau belum dipublikasikan'])</a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.']) ? ' active' : '' }}"
           href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-check-line"></i><span>Absensi Kelas Saya</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'absensi_wali', 'title' => 'Kelas Anda belum diabsen hari ini'])</a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.recap']) ? ' active' : '' }}"
           href="{{ route('user.absensi.harian.recap', ['userId' => $userId]) }}">
            <i class="ri-bar-chart-2-line"></i><span>Rekap Absensi Kelas</span>
        </a>
    </li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-prestasi')
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     3. ADMINISTRASI GURU — guru mapel: Jadwal & Scan QR didahulukan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isGuru)
    <li class="menu-title"><span>Administrasi Guru</span></li>

    @if($isGuruTahfidz && ! $isKoorGuruTahfidz)
        {{-- Guru Tahfidz: buku kerja halaqah + kehadiran --}}
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-absensi', ['absensiMode' => 'self'])
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-tahfidz', ['tahfidzMode' => 'teacher'])
    @else
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jadwal-kbm.for-teacher']) ? ' active' : '' }}"
               href="{{ route('user.jadwal-kbm.for-teacher', ['userId' => $userId, 'teacherId' => $userId]) }}">
                <i class="ri-calendar-schedule-line"></i><span>Jadwal Mengajar Saya</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.scan']) ? ' active' : '' }}"
               href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}">
                <i class="ri-qr-scan-2-line"></i><span>Scan QR Kehadiran</span>
            </a>
        </li>

        @if(canPermission('teacher-attendance_view'))
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.history']) ? ' active' : '' }}"
                   href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}">
                    <i class="ri-history-line"></i><span>Riwayat Kehadiran Saya</span>
                </a>
            </li>
        @endif

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.guru-mapel.']) ? ' active' : '' }}"
               href="{{ route('user.schools.guru-mapel.index', ['userId' => $userId]) }}">
                <i class="ri-book-3-line"></i><span>Buku Nilai Saya</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'nilai_guru', 'title' => 'Buku nilai yang belum lengkap'])</a>
        </li>

        @if(! $isWaliKelas && $canAbsensiAuthority)
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.']) ? ' active' : '' }}"
                   href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">
                    <i class="ri-calendar-check-line"></i><span>Absensi Kelas Saya</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'absensi_all', 'title' => 'Kelas yang belum diabsen hari ini'])</a>
            </li>
        @endif

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.realisasi.']) ? ' active' : '' }}"
               href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId]) }}">
                <i class="ri-article-line"></i><span>Jurnal Pembelajaran</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.pekan-efektif.']) ? ' active' : '' }}"
               href="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
                <i class="ri-calendar-todo-line"></i><span>Pekan Efektif</span>
            </a>
        </li>

        @include('layouts.sidebar.satuan-pendidikan.partials.modul-asesmen', ['showDataNilai' => false])
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-kurikulum', ['kurikulumMode' => 'authoring'])
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     4. JABATAN — menu sesuai jabatan struktural/fungsional
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isStruktural)
    <li class="menu-title"><span>Jabatan · {{ $isKepala ? 'Kepala Satuan Pendidikan' : 'Wakil Kepala Satuan Pendidikan' }}</span></li>

    <li class="menu-title"><span>Kesiswaan</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-kesiswaan', ['kesiswaanMode' => 'admin'])

    <li class="menu-title"><span>Akademik &amp; Asesmen</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-akademik')
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-asesmen')
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-kurikulum', ['kurikulumMode' => 'supervisor'])

    <li class="menu-title"><span>GTK &amp; Mutu</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-gtk', ['gtkMode' => 'supervisi'])

    <li class="menu-title"><span>Sarpras</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-sarpras', ['sarprasMode' => 'full'])

    <li class="menu-title"><span>Administrasi</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-administrasi')

    <li class="menu-title"><span>Kehadiran</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-monitoring')
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-absensi', ['absensiMode' => 'koordinator'])

    <li class="menu-title"><span>Informasi &amp; Broadcast</span></li>
    @if($showAll || $isKepala || $isWakil)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.announcements.']) ? ' active' : '' }}"
               href="{{ route('user.announcements.index', ['userId' => $userId]) }}">
                <i class="ri-newspaper-line"></i><span>Info Sekolah</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.humas.announcements.']) ? ' active' : '' }}"
               href="{{ route('user.humas.announcements.index', ['userId' => $userId]) }}">
                <i class="ri-megaphone-line"></i><span>Kelola Pengumuman</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.broadcasts.']) ? ' active' : '' }}"
               href="{{ route('user.broadcasts.index', ['userId' => $userId]) }}">
                <i class="ri-send-plane-2-line"></i><span>Broadcast</span>
            </a>
        </li>
    @endif

    @if(menu_allowed('laporan'))
        <li class="menu-title"><span>Laporan</span></li>
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-laporan')
    @endif

    {{-- Lintas divisi: boleh untuk KSP & Waka --}}
    <li class="menu-title"><span>Lintas Divisi</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-bahasa')
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-tahfidz', ['tahfidzMode' => 'manage'])
@endif

@if($showAll || $isAdminTU)
    <li class="menu-title"><span>Jabatan · {{ $isKepalaTU ? 'Kepala Tata Usaha' : 'Tata Usaha' }}</span></li>

    <li class="menu-title"><span>Kesiswaan</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-kesiswaan', ['kesiswaanMode' => 'admin'])

    <li class="menu-title"><span>Akademik &amp; Asesmen</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-akademik')
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-asesmen')

    <li class="menu-title"><span>GTK</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-gtk', ['gtkMode' => 'admin'])

    @if($showAll || $isKepalaTU)
        <li class="menu-title"><span>Sarpras</span></li>
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-sarpras', ['sarprasMode' => 'aset'])
    @endif

    <li class="menu-title"><span>Administrasi</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-administrasi')

    <li class="menu-title"><span>Kehadiran</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-monitoring')
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-absensi', ['absensiMode' => 'admin'])

    <li class="menu-title"><span>Informasi &amp; Broadcast</span></li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.announcements.']) ? ' active' : '' }}"
           href="{{ route('user.announcements.index', ['userId' => $userId]) }}">
            <i class="ri-newspaper-line"></i><span>Info Sekolah</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.humas.announcements.']) ? ' active' : '' }}"
           href="{{ route('user.humas.announcements.index', ['userId' => $userId]) }}">
            <i class="ri-megaphone-line"></i><span>Kelola Pengumuman</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.broadcasts.']) ? ' active' : '' }}"
           href="{{ route('user.broadcasts.index', ['userId' => $userId]) }}">
            <i class="ri-send-plane-2-line"></i><span>Broadcast</span>
        </a>
    </li>

    @if(($showAll || $isKepalaTU) && menu_allowed('laporan'))
        <li class="menu-title"><span>Laporan</span></li>
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-laporan')
    @endif
@endif

@if($showAll || $isBendahara)
    <li class="menu-title"><span>{{ $koorLabel('Bendahara Sekolah', $showAll || $jabBendahara) }}</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.keuangan']) ? ' active' : '' }}"
           href="{{ route('user.laporan.keuangan', ['userId' => $userId]) }}">
            <i class="ri-file-chart-2-line"></i><span>Laporan Keuangan</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll-slip.']) ? ' active' : '' }}"
           href="{{ route('user.payroll-slip.index', ['userId' => $userId]) }}">
            <i class="ri-receipt-line"></i><span>Slip Gaji</span>
        </a>
    </li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-sarpras', ['sarprasMode' => 'full'])
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     5. TUGAS TAMBAHAN — koordinator & penugasan di luar jabatan
     (Wali Kelas sudah ditempatkan paling atas)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isKoorKurik)
    <li class="menu-title"><span>{{ $koorLabel('Koordinator Kurikulum', $showAll || $jabKoorKurik) }}</span></li>

    <li class="menu-title"><span>Kurikulum</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-kurikulum', ['kurikulumMode' => 'manage'])

    <li class="menu-title"><span>Akademik &amp; Asesmen</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-akademik')
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-asesmen')

    <li class="menu-title"><span>GTK &amp; Mutu</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-gtk', ['gtkMode' => 'supervisi'])

    <li class="menu-title"><span>Kehadiran</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-monitoring')
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-absensi', ['absensiMode' => 'koordinator'])
@endif

@if($showAll || $isKoorKesiswa)
    <li class="menu-title"><span>{{ $koorLabel('Koordinator Kesiswaan', $showAll || $jabKoorKesiswa) }}</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-kesiswaan', ['kesiswaanMode' => 'koordinator'])
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-absensi', ['absensiMode' => 'koordinator'])

    @if(menu_allowed('ekstrakurikuler'))
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.ekstrakurikuler.']) ? ' active' : '' }}"
               href="{{ route('waka.ekstrakurikuler.index') }}">
                <i class="ri-basketball-line"></i><span>Daftar Ekstrakurikuler</span>
            </a>
        </li>
    @endif
@endif

@if($showAll || $isKoorEkskul)
    <li class="menu-title"><span>{{ $koorLabel('Koordinator Ekstrakurikuler', $showAll || $jabKoorEkskul) }}</span></li>

    @if(menu_allowed('ekstrakurikuler'))
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.ekstrakurikuler.']) ? ' active' : '' }}"
               href="{{ route('waka.ekstrakurikuler.index') }}">
                <i class="ri-basketball-line"></i><span>Daftar Ekstrakurikuler</span>
            </a>
        </li>
    @endif
@endif

@if($showAll || $isKoorMapel)
    <li class="menu-title"><span>{{ $koorLabel('Koordinator Mata Pelajaran', false) }}</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-kurikulum', ['kurikulumMode' => 'manage'])
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-asesmen')
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-gtk', ['gtkMode' => 'mapel'])
@endif

@if($showAll || $isKoorLab)
    <li class="menu-title"><span>{{ $koorLabel('Koordinator Laboratorium', false) }}</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.index']) ? ' active' : '' }}"
           href="{{ route('lab.index') }}">
            <i class="ri-flask-line"></i><span>Dashboard Laboratorium</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.request.']) ? ' active' : '' }}"
           href="{{ route('lab.request.index', ['userId' => $userId]) }}">
            <i class="ri-file-shield-2-line"></i><span>Ajukan Izin Laboratorium</span>
        </a>
    </li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-sarpras', ['sarprasMode' => 'full'])
@endif

@if($showAll || $isKoorSarpras)
    <li class="menu-title"><span>{{ $koorLabel('Koordinator Sarpras', false) }}</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-sarpras', ['sarprasMode' => 'full'])
@endif

@if(($showAll || $isKoorPerpus) && menu_allowed('perpustakaan'))
    <li class="menu-title"><span>{{ $koorLabel('Koordinator Perpustakaan', $showAll || $jabKoorPerpus) }}</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-perpustakaan')
@endif

@if($showAll || $isKoorBahasa)
    <li class="menu-title"><span>{{ $koorLabel('Koordinator Bahasa', $showAll || $jabKoorBahasa) }}</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-bahasa')
@endif

@if($showAll || $isKoorTahfidz)
    <li class="menu-title"><span>{{ $koorLabel('Koordinator Tahfidz', $showAll || $jabKoorTahfidz) }}</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-tahfidz', ['tahfidzMode' => 'manage'])
@endif

@if($showAll || $isTimKurikulum)
    <li class="menu-title"><span>Tugas Tambahan · Tim Kurikulum</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-kurikulum', ['kurikulumMode' => 'manage'])
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-asesmen')
@endif

@if($showAll || $isTimKesiswaan)
    <li class="menu-title"><span>Tugas Tambahan · Tim Kesiswaan</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-kesiswaan', ['kesiswaanMode' => 'koordinator'])
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-absensi', ['absensiMode' => 'koordinator'])
@endif

@if($showAll || $isGuruBK)
    <li class="menu-title"><span>Tugas Tambahan · Bimbingan Konseling</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-kesiswaan', ['kesiswaanMode' => 'bk'])
@endif

@if($showAll || $isPembinaEkskul)
    <li class="menu-title"><span>Tugas Tambahan · Pembina Ekstrakurikuler</span></li>

    @if(menu_allowed('ekstrakurikuler'))
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.ekstrakurikuler.']) ? ' active' : '' }}"
               href="{{ route('waka.ekstrakurikuler.index') }}">
                <i class="ri-basketball-line"></i><span>Daftar Ekstrakurikuler</span>
            </a>
        </li>
    @endif
@endif

@if($showAll || $isConfiguredSupervisor)
    <li class="menu-title"><span>Tugas Tambahan · Supervisor Pembelajaran</span></li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-gtk', ['gtkMode' => 'supervisor'])
@endif
