{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: SATUAN PENDIDIKAN (STRUKTUR BARU — hasil restrukturisasi)
     ───────────────────────────────────────────────────────────────────────────
     Struktur:
       1. Menu                → semua (Dashboard root, Profil, Sekolah, Notifikasi, Approval)
       2. Dashboard           → sesuai jabatan & tugas (multi dashboard)
       3. KBM Saya            → Guru: Buku Nilai, Absensi, Jadwal, Jurnal, Modul Ajar
       4. Kesiswaan           → Santri, Rombel, Wali Kelas, Mutasi, Kedisiplinan, Prestasi
       5. Akademik            → Kurikulum, Plotting, SK, Jadwal, Jam, Kaldik, Asesmen
       6. GTK & Tugas         → Data GTK, Pengajuan, Tugas Tambahan, Kinerja, Koor Mapel
       7. Ekstrakurikuler
       8. Laboratorium & Sarpras
       9. Keuangan Sekolah
      10. Administrasi        → Surat Menyurat, Dokumen ISO
      11. Monitoring Kehadiran
      12. Absensi Kehadiran   → QR & manual + rekap
      13. Referensi           → Kalender Pendidikan (semua)
      14. Laporan
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

    /* ── Semua rombel di sekolah (untuk TU/Kepala/Wakil) — TA AKTIF saja;
          rombel TA lama tetap tersimpan & diakses lewat Data Rombel. ───── */
    $sidebarStudyGroups = $sekolahId
        ? \App\Models\StudyGroup::withoutGlobalScope('school_context')
            ->where('school_id', $sekolahId)
            ->where('is_active', true)
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->with(['gradeLevel', 'homeroomTeacher'])
            ->orderBy('name')
            ->get()
        : collect();

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepala       = $hasJabatan('Kepala Satuan Pendidikan');
    $isWakil        = $hasJabatan('Wakil Kepala Satuan Pendidikan');
    $isGuruUmum     = $hasJabatan('Guru Umum');
    $isGuruAgama    = $hasJabatan('Guru Agama');
    $isGuruHadits   = $hasJabatan('Guru Hadits');
    $isGuruArab     = $hasJabatan('Guru Bahasa Arab');
    $isGuruTahfidz  = $hasJabatan('Guru Tahfidz');
    $isKepalaTU     = $hasJabatan('Kepala Tata Usaha');
    $isTU           = $hasJabatan('Tata Usaha') || $hasJabatan('Staf Tata Usaha');
    $isBendahara    = $hasJabatan('Bendahara Sekolah');
    $isKoorKurik    = $hasJabatan('Koordinator Kurikulum');
    $isKoorKesiswa  = $hasJabatan('Koordinator Kesiswaan');
    $isKoorEkskul   = $hasJabatan('Koordinator Ekstrakurikuler');

    $isGuru         = $isGuruUmum || $isGuruAgama || $isGuruHadits || $isGuruArab || $isGuruTahfidz;
    $isAdminTU      = $isKepalaTU || $isTU;
    $isStruktural   = $isKepala || $isWakil;
    $isKoordinator  = $isKoorKurik || $isKoorKesiswa || $isKoorEkskul;

    /* ── Tugas Tambahan ──────────────────────────────────────────────── */
    $isWaliKelas       = $hasTugas('Wali Kelas') && $isActuallyWaliKelas;
    $isTimKurikulum    = $hasTugas('Tim Kurikulum');
    $isTimKesiswaan    = $hasTugas('Tim Kesiswaan');
    $isPembinaEkskul   = $hasTugas('Pembina Ekstrakurikuler');
    $isKoorLab         = $hasTugas('Koordinator Laboratorium');
    $isKoorSarpras     = $hasTugas('Koordinator Sarpras Satuan Pendidikan');
    $isKoorGuruUmum    = $hasTugas('Koordinator Guru Umum');
    $isKoorGuruAgama   = $hasTugas('Koordinator Guru Agama');
    $isKoorGuruHadits  = $hasTugas('Koordinator Guru Hadits');
    $isKoorGuruArab    = $hasTugas('Koordinator Guru Bahasa Arab');
    $isKoorGuruTahfidz = $hasTugas('Koordinator Guru Tahfidz');

    $isKoorMapel       = $isKoorGuruUmum || $isKoorGuruAgama || $isKoorGuruHadits || $isKoorGuruArab || $isKoorGuruTahfidz;
    $isTimKurikulumG   = $isKoorKurik || $isTimKurikulum || $isKepala || $isWakil;

    /* ── Absensi Kelas — wali kelas & kewenangan (KSP/Waka/Kurikulum/Kesiswaan/TU) ── */
    $canAbsensiAuthority = $isKepala || $isWakil || $isAdminTU
        || $isKoorKurik || $isTimKurikulum
        || $isKoorKesiswa || $isTimKesiswaan
        || (function_exists('canPermission') && (
            canPermission('absensi-admin-tu') || canPermission('absensi-tata-usaha')
            || canPermission('absensi-wakil-kepala-sekolah') || canPermission('absensi-kepala-sekolah')
            || canPermission('absensi-kepala-sekolah-pondok')
        ));

    /* ── Absen Manual — restricted (aturan #4) ───────────────────────── */
    $canManualAbsen = ($isAdminTU || $isStruktural || $isKoordinator)
        && (function_exists('canPermission')
            ? canPermission('teacher-attendance_manual')
            : (method_exists(auth()->user(), 'hasPermissionTo') && auth()->user()->hasPermissionTo('teacher-attendance_manual'))
        );

    /* ── Sarpras Dashboard Route ─────────────────────────────────────── */
    $sarprasDashboardRoute = route('sarpras.user.dashboard', ['userId' => $userId]);
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 1 — MENU (Utama)
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Menu</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i><span>Dashboard</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i><span>Todo List</span>
    </a>
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
       href="{{ route('user.notifications.index', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>

@if($showAll || $isStruktural || $isAdminTU || $isKoordinator)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ route('user.approvals.index', ['userId' => $userId]) }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — DASHBOARD (multi per jabatan & tugas)
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Dashboard</span></li>

@if($showAll || $isKepala)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.kepala-satuan-pendidikan']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.kepala-satuan-pendidikan', ['userId' => $userId]) }}">
        <i class="ri-vip-crown-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isWakil)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.wakil-kepala']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.wakil-kepala', ['userId' => $userId]) }}">
        <i class="ri-shield-star-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isGuru)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.guru']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.guru', ['userId' => $userId]) }}">
        <i class="ri-user-star-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isKepalaTU)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.ka-tata-usaha']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.ka-tata-usaha', ['userId' => $userId]) }}">
        <i class="ri-admin-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isTU)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.staf-tata-usaha']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.staf-tata-usaha', ['userId' => $userId]) }}">
        <i class="ri-file-user-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if(($showAll || $isBendahara) && menu_allowed('keuangan'))
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.bendahara']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.bendahara', ['userId' => $userId]) }}">
        <i class="ri-money-dollar-circle-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isKoorKurik)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-kurikulum']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.koordinator-kurikulum', ['userId' => $userId]) }}">
        <i class="ri-book-mark-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isKoorKesiswa)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-kesiswaan']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.koordinator-kesiswaan', ['userId' => $userId]) }}">
        <i class="ri-group-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isKoorEkskul)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-ekskul']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.koordinator-ekskul', ['userId' => $userId]) }}">
        <i class="ri-basketball-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isWaliKelas)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.wali-kelas']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.wali-kelas', ['userId' => $userId]) }}">
        <i class="ri-parent-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isKoorMapel)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-guru']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.koordinator-guru', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isKoorLab)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-lab']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.koordinator-lab', ['userId' => $userId]) }}">
        <i class="ri-flask-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

@if($showAll || $isKoorSarpras)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-sarpras']) ? ' active' : '' }}"
       href="{{ route('user.dashboard.koordinator-sarpras', ['userId' => $userId]) }}">
        <i class="ri-tools-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — KBM SAYA (Guru)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isGuru)
    <li class="menu-title"><span>KBM Saya</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.guru-mapel.']) ? ' active' : '' }}"
           href="{{ route('user.schools.guru-mapel.index', ['userId' => $userId]) }}">
            <i class="ri-book-3-line"></i><span>Buku Nilai Saya</span>
        </a>
    </li>

    @if($showAll || ($isGuru && ! $isWaliKelas && $canAbsensiAuthority))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.']) ? ' active' : '' }}"
           href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-check-line"></i><span>Absensi Kelas Saya</span>
        </a>
    </li>
    @endif

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jadwal-kbm.for-teacher']) ? ' active' : '' }}"
           href="{{ route('user.jadwal-kbm.for-teacher', ['userId' => $userId, 'teacherId' => $userId]) }}">
            <i class="ri-time-line"></i><span>Jadwal Mengajar Saya</span>
        </a>
    </li>

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

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.']) ? ' active' : '' }}"
           href="#guru_kurikulum" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.kurikulum.']) ? 'true' : 'false' }}"
           aria-controls="guru_kurikulum">
            <i class="ri-book-open-line"></i><span>Kurikulum Saya</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.kurikulum.']) ? ' show' : '' }}"
             id="guru_kurikulum">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.tp.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.tp.index', ['userId' => $userId]) }}">
                        Tujuan Pembelajaran
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.atp.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.atp.index', ['userId' => $userId]) }}">
                        ATP
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.prota.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.prota.index', ['userId' => $userId]) }}">
                        PROTA
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.prosem.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId]) }}">
                        PROSEM
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.perangkat.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}">
                        Modul Ajar (RPM)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.akses-edit.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.akses-edit.index', ['userId' => $userId]) }}">
                        Permintaan Akses Edit
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.index']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">
                        Peta Kurikulum
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.ai.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.ai.index', ['userId' => $userId]) }}">
                        AI Assistant (RPM &amp; Soal)
                    </a>
                </li>
            </ul>
        </div>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — KESISWAAN
     Santri, Rombel, Wali Kelas, Mutasi, Kedisiplinan, Prestasi
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isAdminTU || $isStruktural || $isKoorKesiswa || $isTimKesiswaan || $isWaliKelas)
    <li class="menu-title"><span>Kesiswaan</span></li>

    {{-- Santri (TU/Struktural/Tim Kesiswaan) --}}
    @if($showAll || $isAdminTU || $isStruktural || $isKoorKesiswa || $isTimKesiswaan)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index', 'user.students.show']) ? ' active' : '' }}"
           href="{{ route('user.students.index', ['userId' => $userId]) }}">
            <i class="ri-team-line"></i><span>Data Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.mahroms.']) ? ' active' : '' }}"
           href="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}">
            <i class="ri-parent-line"></i><span>Data Mahrom</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.import-form']) ? ' active' : '' }}"
           href="{{ route('user.students.import-form', ['userId' => $userId]) }}">
            <i class="ri-upload-2-line"></i><span>Import Data Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.alumni.']) ? ' active' : '' }}"
           href="{{ route('user.alumni.index', ['userId' => $userId]) }}">
            <i class="ri-group-2-line"></i><span>Data Alumni</span>
        </a>
    </li>
    @endif

    {{-- Rombel: TU/Struktural/Tim Kesiswaan + Wali Kelas (daftar kelasnya) --}}
    @if($showAll || $isAdminTU || $isStruktural || $isKoorKesiswa || $isTimKesiswaan)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.study-groups.']) ? ' active' : '' }}"
           href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">
            <i class="ri-layout-grid-line"></i><span>Pengaturan Rombel</span>
        </a>
    </li>
    @endif

    @if($showAll || $isAdminTU || $isStruktural || $isKoorKesiswa || $isTimKesiswaan)
    <li class="nav-item">
        <a class="nav-link" href="#tu_rombel_list" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $currentRoute === 'user.students.index' && request('study_group_id') ? 'true' : 'false' }}"
           aria-controls="tu_rombel_list">
            <i class="ri-list-check-2"></i><span>Santri per Rombel</span>
        </a>
        <div class="collapse{{ $currentRoute === 'user.students.index' && request('study_group_id') ? ' show' : '' }}"
             id="tu_rombel_list">
            <ul class="nav nav-sm flex-column ps-2">
                @forelse($sidebarStudyGroups as $sg)
                    <li class="nav-item">
                        <a class="nav-link{{ $currentRoute === 'user.students.index' && request('study_group_id') == $sg->id ? ' active' : '' }}"
                           href="{{ route('user.students.index', ['userId' => $userId, 'study_group_id' => $sg->id]) }}"
                           style="font-size:0.82rem">
                            {{ $sg->full_name }}
                            @if($sg->homeroomTeacher)
                                <span class="text-muted ms-1" style="font-size:0.72rem">({{ $sg->homeroomTeacher->name }})</span>
                            @endif
                        </a>
                    </li>
                @empty
                    <li class="nav-item">
                        <span class="nav-link text-muted" style="font-size:0.8rem">Belum ada rombel</span>
                    </li>
                @endforelse
            </ul>
        </div>
    </li>
    @endif

    {{-- Mutasi (TU & struktural) --}}
    @if($showAll || $isAdminTU || $isStruktural)
        <li class="nav-item">
            <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-do.', 'user.mutations-lulus.', 'user.student-move.']) ? ' active' : '' }}"
               href="#tu_mutasi_pd" data-bs-toggle="collapse" role="button"
               aria-expanded="{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-do.', 'user.mutations-lulus.', 'user.student-move.']) ? 'true' : 'false' }}"
               aria-controls="tu_mutasi_pd">
                <i class="ri-swap-box-line"></i><span>Mutasi PD</span><span class="menu-arrow"></span>
            </a>
            <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-do.', 'user.mutations-lulus.', 'user.student-move.']) ? ' show' : '' }}"
                 id="tu_mutasi_pd">
                <ul class="nav nav-sm flex-column">
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-in.']) ? ' active' : '' }}"
                           href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}">
                            Mutasi Masuk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-out.']) ? ' active' : '' }}"
                           href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}">
                            Mutasi Keluar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-do.']) ? ' active' : '' }}"
                           href="{{ route('user.mutations-do.index', ['userId' => $userId]) }}">
                            Drop Out
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-lulus.']) ? ' active' : '' }}"
                           href="{{ route('user.mutations-lulus.index', ['userId' => $userId]) }}">
                            Lulus
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.student-move.']) ? ' active' : '' }}"
                           href="{{ route('user.student-move.index', ['userId' => $userId]) }}">
                            Pindahkan Santri
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    @endif

    {{-- Wali Kelas --}}
    @if($showAll || $isWaliKelas)
        <li class="nav-item">
            <span class="nav-link text-muted" style="font-size:0.8rem">
                <i class="ri-information-line me-1"></i>
                Kelas: <strong>{{ $waliKelasGroups->pluck('name')->join(', ') ?: '-' }}</strong>
            </span>
        </li>

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai-kelas.']) ? ' active' : '' }}"
               href="{{ $waliKelasGroups->isNotEmpty()
                    ? route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $waliKelasGroups->first()->id])
                    : route('user.schools.nilai-kelas.index', ['userId' => $userId]) }}">
                <i class="ri-table-line"></i><span>Leger &amp; Cetak Rapor</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.']) ? ' active' : '' }}"
               href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">
                <i class="ri-calendar-check-line"></i><span>Absensi Kelas Saya</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.recap']) ? ' active' : '' }}"
               href="{{ route('user.absensi.harian.recap', ['userId' => $userId]) }}">
                <i class="ri-bar-chart-2-line"></i><span>Rekap Absensi Kelas</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index']) ? ' active' : '' }}"
               href="{{ $waliKelasGroups->isNotEmpty()
                    ? route('user.students.index', ['userId' => $userId, 'study_group_id' => $waliKelasGroups->first()->id])
                    : route('user.students.index', ['userId' => $userId]) }}">
                <i class="ri-user-heart-line"></i><span>Santri Kelas Saya</span>
            </a>
        </li>
    @endif

    @if(($showAll || $isAdminTU || $isStruktural || $isKoorKurik || $isTimKurikulum) && ! $isWaliKelas)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai-kelas.']) ? ' active' : '' }}"
               href="{{ route('user.schools.nilai-kelas.index', ['userId' => $userId]) }}">
                <i class="ri-table-line"></i><span>Leger &amp; Cetak Rapor</span>
            </a>
        </li>
    @endif

    {{-- Kedisiplinan --}}
    @if($showAll || $isKoorKesiswa || $isTimKesiswaan || $isAdminTU || $isStruktural)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.dashboard']) ? ' active' : '' }}"
               href="{{ route('user.violation-points.dashboard', ['userId' => $userId]) }}">
                <i class="ri-dashboard-2-line"></i><span>Dashboard Kedisiplinan</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.index', 'user.violation-points.create', 'user.violation-points.show']) ? ' active' : '' }}"
               href="{{ route('user.violation-points.index', ['userId' => $userId]) }}">
                <i class="ri-error-warning-line"></i><span>Poin Pelanggaran</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.recap']) ? ' active' : '' }}"
               href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}">
                <i class="ri-bar-chart-2-line"></i><span>Rekap Pelanggaran</span>
            </a>
        </li>
    @endif

    {{-- Prestasi --}}
    @if($showAll || $isKoorKesiswa || $isTimKesiswaan || $isAdminTU || $isWaliKelas || $isStruktural)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? ' active' : '' }}"
               href="#tu_prestasi" data-bs-toggle="collapse" role="button"
               aria-expanded="{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? 'true' : 'false' }}"
               aria-controls="tu_prestasi">
                <i class="ri-trophy-line"></i><span>Prestasi Santri</span><span class="menu-arrow"></span>
            </a>
            <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? ' show' : '' }}"
                 id="tu_prestasi">
                <ul class="nav nav-sm flex-column">
                    <li class="nav-item">
                        <a class="nav-link{{ request('type') !== 'quran' && request('type') !== 'hadits' ? ' active' : '' }}"
                           href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'akademik']) }}">
                            Prestasi Akademik
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ request('type') === 'quran' ? ' active' : '' }}"
                           href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'quran']) }}">
                            Hafalan Qur'an
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ request('type') === 'hadits' ? ' active' : '' }}"
                           href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'hadits']) }}">
                            Hafalan Hadits
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — AKADEMIK
     Kurikulum, Plotting, SK, Jadwal, Jam, Kaldik, Asesmen
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isTimKurikulumG || $isAdminTU)
    <li class="menu-title"><span>Akademik</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.academic-years.']) ? ' active' : '' }}"
           href="{{ route('user.academic-years.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-2-line"></i><span>Tahun Ajaran</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.subjects.']) ? ' active' : '' }}"
           href="{{ route('user.subjects.index', ['userId' => $userId]) }}">
            <i class="ri-book-2-line"></i><span>Mata Pelajaran</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.grade-levels.']) ? ' active' : '' }}"
           href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">
            <i class="ri-layout-grid-fill"></i><span>Data Kelas &amp; Mapel</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teaching-assignments.']) ? ' active' : '' }}"
           href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">
            <i class="ri-user-star-line"></i><span>Plotting Guru Mengajar</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.institution-decrees.']) ? ' active' : '' }}"
           href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">
            <i class="ri-file-paper-2-line"></i><span>SK &amp; Keputusan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jadwal-kbm.index', 'user.jadwal-kbm.generate']) ? ' active' : '' }}"
           href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-schedule-line"></i><span>Jadwal Pelajaran</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jam-pelajaran.']) ? ' active' : '' }}"
           href="{{ route('user.jam-pelajaran.index', ['userId' => $userId]) }}">
            <i class="ri-timer-line"></i><span>Jam Pelajaran</span>
        </a>
    </li>

    @if(!$isGuru)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.pekan-efektif.']) ? ' active' : '' }}"
           href="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-todo-line"></i><span>Pekan Efektif</span>
        </a>
    </li>
    @endif

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.']) ? ' active' : '' }}"
           href="#sp_kurikulum" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.kurikulum.']) ? 'true' : 'false' }}"
           aria-controls="sp_kurikulum">
            <i class="ri-book-open-line"></i><span>Kurikulum &amp; Perangkat</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.kurikulum.']) ? ' show' : '' }}"
             id="sp_kurikulum">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.index']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}">
                        Peta Kurikulum
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.cp.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.cp.index', ['userId' => $userId]) }}">
                        Capaian Pembelajaran
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.tp.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.tp.index', ['userId' => $userId]) }}">
                        Tujuan Pembelajaran
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.atp.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.atp.index', ['userId' => $userId]) }}">
                        ATP
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.prota.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.prota.index', ['userId' => $userId]) }}">
                        PROTA
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.prosem.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId]) }}">
                        PROSEM
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.perangkat.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}">
                        Modul Ajar (RPM)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.akses-edit.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.akses-edit.index', ['userId' => $userId]) }}">
                        Permintaan Akses Edit
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kurikulum.rumpun.']) ? ' active' : '' }}"
                       href="{{ route('user.kurikulum.rumpun.index', ['userId' => $userId]) }}">
                        Master Rumpun
                    </a>
                </li>
            </ul>
        </div>
    </li>

@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5b — BANK SOAL & ASESMEN (Guru, Koor Mapel, Kurikulum, TU)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isGuru || $isKoorMapel || $isTimKurikulumG || $isAdminTU)
    <li class="menu-title"><span>Bank Soal &amp; Asesmen</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kisi-kisi-soal.', 'user.bank-soal.', 'user.paket-soal.', 'user.tu-paket-soal.', 'user.bank-soal-terpusat.', 'user.review-soal.']) ? ' active' : '' }}"
           href="#sp_asesmen" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.kisi-kisi-soal.', 'user.bank-soal.', 'user.paket-soal.', 'user.tu-paket-soal.', 'user.bank-soal-terpusat.', 'user.review-soal.']) ? 'true' : 'false' }}"
           aria-controls="sp_asesmen">
            <i class="ri-file-edit-line"></i><span>Bank Soal &amp; Asesmen</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.kisi-kisi-soal.', 'user.bank-soal.', 'user.paket-soal.', 'user.tu-paket-soal.', 'user.bank-soal-terpusat.', 'user.review-soal.']) ? ' show' : '' }}"
             id="sp_asesmen">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kisi-kisi-soal.']) ? ' active' : '' }}"
                       href="{{ route('user.kisi-kisi-soal.index', ['userId' => $userId]) }}">
                        Kisi-Kisi Soal
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.bank-soal.']) ? ' active' : '' }}"
                       href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}">
                        Bank Soal &amp; Input Soal
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.bank-soal-terpusat.']) ? ' active' : '' }}"
                       href="{{ route('user.bank-soal-terpusat.index', ['userId' => $userId]) }}">
                        Repository Soal
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.review-soal.']) ? ' active' : '' }}"
                       href="{{ route('user.review-soal.index', ['userId' => $userId]) }}">
                        Review Soal Serumpun
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.paket-soal.']) ? ' active' : '' }}"
                       href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}">
                        Paket Soal Sumatif
                    </a>
                </li>
                @if($showAll || $isAdminTU)
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.tu-paket-soal.']) ? ' active' : '' }}"
                           href="{{ route('user.tu-paket-soal.index', ['userId' => $userId]) }}">
                            TU — Cetak Paket Final
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.ljk.']) ? ' active' : '' }}"
                       href="{{ route('user.ljk.index', ['userId' => $userId]) }}">
                        <i class="ri-scan-2-line me-1"></i>LJK Smart Scanner
                    </a>
                </li>
            </ul>
        </div>
    </li>

    {{-- KKTP kini diatur langsung di Peta Kurikulum (kolom N-KKTP) & Struktur Kurikulum. --}}

    @if($showAll || $isAdminTU || $isStruktural || $isKoorKurik || $isTimKurikulum)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai.']) ? ' active' : '' }}"
           href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
            <i class="ri-survey-line"></i><span>Data Nilai</span>
        </a>
    </li>
    @endif

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? ' active' : '' }}"
           href="#sp_kaldik" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? 'true' : 'false' }}"
           aria-controls="sp_kaldik">
            <i class="ri-calendar-event-line"></i><span>Kalender Akademik</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? ' show' : '' }}"
             id="sp_kaldik">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
                       href="{{ route('user.kaldik.index', ['userId' => $userId]) }}">
                        Kalender Pendidikan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
                       href="{{ route('user.kalender-kegiatan.akademik', ['userId' => $userId]) }}">
                        Agenda Kegiatan
                    </a>
                </li>
            </ul>
        </div>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — GTK & TUGAS
     Data GTK, Pengajuan, Tugas Tambahan, Kinerja, Koordinasi Mapel
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isAdminTU || $isStruktural || $isKoordinator || $isTimKurikulumG || $isKoorMapel)
    <li class="menu-title"><span>GTK &amp; Tugas</span></li>

    @if($showAll || $isAdminTU || $isStruktural || $isTimKurikulumG || $isKoorMapel)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.indexguru', 'user.gtk.indextendik']) ? ' active' : '' }}"
           href="#tu_gtk" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.gtk.indexguru', 'user.gtk.indextendik']) ? 'true' : 'false' }}"
           aria-controls="tu_gtk">
            <i class="ri-contacts-book-2-line"></i><span>Data GTK</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.gtk.indexguru', 'user.gtk.indextendik']) ? ' show' : '' }}"
             id="tu_gtk">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.indexguru']) ? ' active' : '' }}"
                       href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}">
                        Guru
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.indextendik']) ? ' active' : '' }}"
                       href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}">
                        Tendik
                    </a>
                </li>
            </ul>
        </div>
    </li>
    @endif

    @if($showAll || $isAdminTU)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk-requests.']) ? ' active' : '' }}"
               href="#tu_pengajuan_gtk" data-bs-toggle="collapse" role="button"
               aria-expanded="{{ isActiveAny($currentRoute, ['user.gtk-requests.']) ? 'true' : 'false' }}"
               aria-controls="tu_pengajuan_gtk">
                <i class="ri-file-add-line"></i><span>Pengajuan GTK</span><span class="menu-arrow"></span>
            </a>
            <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.gtk-requests.']) ? ' show' : '' }}"
                 id="tu_pengajuan_gtk">
                <ul class="nav nav-sm flex-column">
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk-requests.index']) ? ' active' : '' }}"
                           href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}">
                            Data Pengajuan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk-requests.create']) ? ' active' : '' }}"
                           href="{{ route('user.gtk-requests.create', ['userId' => $userId]) }}">
                            Buat Pengajuan GTK
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    @endif

    @if($showAll || $isAdminTU || $isStruktural || $isTimKurikulumG)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.other-teacher-tasks.']) ? ' active' : '' }}"
           href="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}">
            <i class="ri-user-settings-line"></i><span>Tugas Tambahan</span>
        </a>
    </li>
    @endif

    @if($showAll || $isStruktural)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.rapor-gtk.', 'user.kinerja.']) ? ' active' : '' }}"
               href="{{ route('user.rapor-gtk.tahunan', ['userId' => $userId]) }}">
                <i class="ri-file-chart-line"></i><span>Supervisi &amp; Kinerja GTK</span>
            </a>
        </li>
    @endif

    @if($showAll || $isStruktural || $isKoorKurik || $isTimKurikulum || $isAdminTU)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.supervisi.']) ? ' active' : '' }}"
               href="{{ route('waka.supervisi.index') }}">
                <i class="ri-file-excel-2-line"></i><span>Supervisi Pembelajaran</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.supervisi-instrumen.']) ? ' active' : '' }}"
               href="{{ route('waka.supervisi-instrumen.index') }}">
                <i class="ri-list-settings-line"></i><span>Instrumen Supervisi</span>
            </a>
        </li>
    @endif

    @if($showAll || $isKoorMapel)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kinerja.']) ? ' active' : '' }}"
               href="{{ route('user.kinerja.index', ['userId' => $userId]) }}">
                <i class="ri-line-chart-line"></i><span>Evaluasi Guru Mapel</span>
            </a>
        </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — EKSTRAKURIKULER
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoorEkskul || $isPembinaEkskul || $isAdminTU) && menu_allowed('ekstrakurikuler'))
    <li class="menu-title"><span>Ekstrakurikuler</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.ekstrakurikuler.']) ? ' active' : '' }}"
           href="{{ route('waka.ekstrakurikuler.index') }}">
            <i class="ri-basketball-line"></i><span>Daftar Ekstrakurikuler</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
           href="{{ route('user.kalender-kegiatan.akademik', ['userId' => $userId]) }}">
            <i class="ri-calendar-event-line"></i><span>Agenda Kegiatan Ekskul</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — LABORATORIUM & SARPRAS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isKepala || (($isKoorLab || $isKoorSarpras) && menu_allowed('sarpras')) || $isAdminTU)
    <li class="menu-title"><span>Laboratorium &amp; Sarpras</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.dashboard']) ? ' active' : '' }}"
           href="{{ $sarprasDashboardRoute }}">
            <i class="ri-dashboard-2-line"></i><span>Dashboard Sarpras</span>
        </a>
    </li>

    @if($showAll || $isKepala || $isKoorSarpras || $isAdminTU)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.agenda']) ? ' active' : '' }}"
           href="{{ route('sarpras.agenda.index') }}">
            <i class="ri-calendar-task-line"></i><span>Agenda Kerja Sarpras</span>
        </a>
    </li>
    @endif

    @if($showAll || (($isKoorLab || $isKoorSarpras) && menu_allowed('sarpras')))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.ruang.']) ? ' active' : '' }}"
           href="{{ route('sarpras.user.ruang.index') }}">
            <i class="ri-door-open-line"></i><span>Ruang &amp; Laboratorium</span>
        </a>
    </li>

    @if($showAll || $isKepala)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.petugas.']) ? ' active' : '' }}"
           href="{{ route('sarpras.petugas.index') }}">
            <i class="ri-user-star-line"></i><span>Petugas Sarpras Unit</span>
        </a>
    </li>
    @endif

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.aset.']) ? ' active' : '' }}"
           href="{{ route('sarpras.user.aset.index') }}">
            <i class="ri-archive-2-line"></i><span>Inventaris Aset</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.kerusakan.']) ? ' active' : '' }}"
           href="{{ route('sarpras.user.kerusakan.index') }}">
            <i class="ri-tools-line"></i><span>Lapor Kerusakan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.pengadaan.']) ? ' active' : '' }}"
           href="{{ route('sarpras.user.pengadaan.index') }}">
            <i class="ri-shopping-bag-3-line"></i><span>Permintaan Pengadaan</span>
        </a>
    </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 9 — KEUANGAN SEKOLAH (Bendahara)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isBendahara)
    <li class="menu-title"><span>Keuangan Sekolah</span></li>

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
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 10 — ADMINISTRASI (TU & Struktural)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isAdminTU || $isStruktural)
    <li class="menu-title"><span>Administrasi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.surat-masuk.', 'waka.surat-keluar.']) ? ' active' : '' }}"
           href="#tu_surat" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['waka.surat-masuk.', 'waka.surat-keluar.']) ? 'true' : 'false' }}"
           aria-controls="tu_surat">
            <i class="ri-mail-send-line"></i><span>Surat Menyurat</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['waka.surat-masuk.', 'waka.surat-keluar.']) ? ' show' : '' }}"
             id="tu_surat">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['waka.surat-keluar.']) ? ' active' : '' }}"
                       href="{{ route('waka.surat-keluar.index') }}">
                        Surat Keluar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['waka.surat-masuk.']) ? ' active' : '' }}"
                       href="{{ route('waka.surat-masuk.index') }}">
                        Surat Masuk
                    </a>
                </li>
            </ul>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
           href="{{ route('user.dokumen-iso.index', ['userId' => $userId]) }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>

    @if($showAll || $isKepala || $isWakil || $isAdminTU || $isKoorKurik || $isTimKurikulum)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.arsip.']) ? ' active' : '' }}"
           href="{{ route('user.arsip.index', ['userId' => $userId]) }}">
            <i class="ri-archive-2-line"></i><span>Arsip &amp; Penutupan Periode</span>
        </a>
    </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 11 — MONITORING KEHADIRAN (Wakil, Koor Kurikulum, Kepala, TU)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isWakil || $isKoorKurik || $isKepala || $isAdminTU)
    <li class="menu-title"><span>Monitoring Kehadiran</span></li>

    @if(canPermission('teacher-attendance_view'))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.waka-dashboard']) ? ' active' : '' }}"
           href="{{ route('user.teacher-qr.waka-dashboard', ['userId' => $userId]) }}">
            <i class="ri-dashboard-2-line"></i><span>Kehadiran Guru Per Jam</span>
        </a>
    </li>
    @endif

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kehadiran.pergantian-jam']) ? ' active' : '' }}"
           href="{{ route('user.kehadiran.pergantian-jam', ['userId' => $userId]) }}">
            <i class="ri-refresh-line"></i><span>Rekap Pergantian Jam</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 12 — ABSENSI KEHADIRAN (QR & manual + rekap)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showAll || $isGuru || $isStruktural || $isAdminTU || $isKoordinator)
    <li class="menu-title"><span>Absensi Kehadiran</span></li>

    @if($showAll || $isGuru || $isStruktural)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.scan']) ? ' active' : '' }}"
           href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}">
            <i class="ri-qr-scan-2-line"></i><span>Scan QR Kehadiran</span>
        </a>
    </li>
    @endif

    @if(canPermission('teacher-attendance_view'))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.history']) ? ' active' : '' }}"
           href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}">
            <i class="ri-history-line"></i><span>Riwayat Kehadiran Saya</span>
        </a>
    </li>
    @endif

    @if($showAll || $isStruktural || $isAdminTU || $isKoorKurik)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.qr.']) ? ' active' : '' }}"
           href="{{ route('user.qr.index', ['userId' => $userId]) }}">
            <i class="ri-qr-code-line"></i><span>QR Kelas</span>
        </a>
    </li>
    @endif

    @if($showAll || $isAdminTU)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.']) ? ' active' : '' }}"
           href="{{ route('user.absensi-gtk.index', ['userId' => $userId]) }}">
            <i class="ri-contacts-book-line"></i><span>Absensi GTK</span>
        </a>
    </li>
    @endif

    @if($showAll || $isAdminTU || $isKepala || $isWakil || $isKoorKurik || $isTimKurikulum || $isKoorKesiswa || $isTimKesiswaan)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.']) ? ' active' : '' }}"
           href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-check-line"></i><span>Absensi Peserta Didik</span>
        </a>
    </li>
    @endif

    @if($showAll || $isAdminTU || $isStruktural || $isKoorKurik || $isTimKurikulum)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.settings']) ? ' active' : '' }}"
           href="{{ route('user.absensi-gtk.settings', ['userId' => $userId]) }}">
            <i class="ri-settings-3-line"></i><span>Pengaturan Toleransi &amp; Lokasi</span>
        </a>
    </li>
    @endif

    @if($showAll || $canManualAbsen)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.manual']) ? ' active' : '' }}"
           href="{{ route('user.teacher-qr.manual', ['userId' => $userId]) }}">
            <i class="ri-keyboard-line"></i><span>Absen Manual Guru</span>
        </a>
    </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 13 — REFERENSI (semua user)
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Referensi</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
       href="{{ route('user.kaldik.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-event-line"></i><span>Kalender Pendidikan</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 14 — LAPORAN (Kepala, Wakil, Kepala TU)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepala || $isWakil || $isKepalaTU) && menu_allowed('laporan'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ $currentRoute === 'user.laporan.index' ? ' active' : '' }}"
           href="{{ route('user.laporan.index', ['userId' => $userId]) }}">
            <i class="ri-file-list-2-line"></i><span>Semua Laporan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.presensi']) ? ' active' : '' }}"
           href="{{ route('user.laporan.presensi', ['userId' => $userId]) }}">
            <i class="ri-calendar-check-line"></i><span>Laporan Presensi</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.santri']) ? ' active' : '' }}"
           href="{{ route('user.laporan.santri', ['userId' => $userId]) }}">
            <i class="ri-user-star-line"></i><span>Laporan Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.gtk']) ? ' active' : '' }}"
           href="{{ route('user.laporan.gtk', ['userId' => $userId]) }}">
            <i class="ri-team-line"></i><span>Laporan GTK</span>
        </a>
    </li>
@endif
