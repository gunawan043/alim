{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: HUMAS PERSONALIA
     ───────────────────────────────────────────────────────────────────────────
     Role    : Humas Personalia
     Jabatan : 5 —
       Kepala Humas & Personalia
       Kepala Humas
       Kepala Personalia
       Staf Humas
       Staf Personalia
     Tugas   : —
     ───────────────────────────────────────────────────────────────────────────
     DUA PILAR:
       1. PERSONALIA (HRD) → siklus hidup GTK: rekrutmen, kontrak, presensi,
                             kinerja, cuti, payroll, pensiun
       2. HUMAS           → komunikasi publik & layanan wali santri
     ───────────────────────────────────────────────────────────────────────────
     AUDIT ROUTE:
       ✅ AKTIF = route sudah ada di php.txt
       ⏳ SOON  = route belum dibuat (placeholder)
     ───────────────────────────────────────────────────────────────────────────
     BATASAN:
       • HANYA modul kepegawaian & kehumasan
       • TIDAK akses: Akademik, Asrama (kelola), UKS, Sarpras
       • Data santri & kebijakan hanya READ-ONLY untuk layanan wali
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Work Unit user (untuk route satuan-kerja.*) ─────────────────── */
    $userWorkUnits = \App\Models\GtkWorkUnit::with('workUnit')
        ->where('user_id', $userId)
        ->whereHas('workUnit', fn ($q) => $q->where('is_active', true))
        ->get();
    $primaryWorkUnit   = $userWorkUnits->where('is_primary', true)->first() ?? $userWorkUnits->first();
    $primaryWorkUnitId = $primaryWorkUnit?->workUnit?->id;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $humasUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
    $workUnitUrl = function (string $routeName, array $extra = []) use ($primaryWorkUnitId) {
        if (! $primaryWorkUnitId) return '#';
        try {
            return route($routeName, array_merge([
                'userId'     => auth()->id(),
                'workUnitId' => $primaryWorkUnitId,
            ], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepalaHumasPers = $hasJabatan('Kepala Humas & Personalia');
    $isKepalaHumas     = $hasJabatan('Kepala Humas');
    $isKepalaPersonal  = $hasJabatan('Kepala Personalia');
    $isStafHumas       = $hasJabatan('Staf Humas');
    $isStafPersonal    = $hasJabatan('Staf Personalia');

    $isStruktural      = $isKepalaHumasPers || $isKepalaHumas || $isKepalaPersonal;
    $isStaf            = $isStafHumas || $isStafPersonal;

    /* ── Pilar access ────────────────────────────────────────────────── */
    $canHumas         = $isKepalaHumasPers || $isKepalaHumas || $isStafHumas;
    $canPersonal      = $isKepalaHumasPers || $isKepalaPersonal || $isStafPersonal;

    /* ── Dashboard (pribadi personalia — sudah ada di php.txt) ───────── */
    $dashboardRoute = 'user.dashboard'; // PersonaliaDashboardController@dashboard
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 1 — UTAMA
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Utama</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i><span>Beranda</span>
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
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ route('user.approvals.index', ['userId' => $userId]) }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — DASHBOARD  [AKTIF]
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Dashboard</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard']) ? ' active' : '' }}"
       href="{{ route('user.dashboard', ['userId' => $userId]) }}">
        <i class="ri-user-settings-line"></i><span>Dashboard</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — DATA GTK / DIGITAL FILING  [AKTIF]
     Alur #1: Digital Filing GTK
     Akses: Kepala & Staf Personalia
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal) && menu_allowed('humas'))
    <li class="menu-title"><span>Data GTK</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.index']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.gtk.index') }}">
            <i class="ri-team-line"></i><span>Semua GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.indexguru']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.gtk.indexguru') }}">
            <i class="ri-user-3-line"></i><span>Data Guru</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.indextendik']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.gtk.indextendik') }}">
            <i class="ri-user-settings-line"></i><span>Data Tendik</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.create']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.gtk.create') }}">
            <i class="ri-user-add-line"></i><span>Tambah GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.import']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.gtk.import') }}">
            <i class="ri-upload-cloud-line"></i><span>Import GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.export']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.gtk.export') }}">
            <i class="ri-download-cloud-line"></i><span>Export GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.massal']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.gtk.massal') }}">
            <i class="ri-edit-box-line"></i><span>Manajemen Massal</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — STRUKTUR & JABATAN  [AKTIF]
     Akses: Kepala & Staf Personalia
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal) && menu_allowed('humas'))
    <li class="menu-title"><span>Struktur & Jabatan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.satuan-kerja.positions', 'user.gtk-positions.']) ? ' active' : '' }}"
           href="{{ $workUnitUrl('user.schools.satuan-kerja.positions') }}">
            <i class="ri-briefcase-line"></i><span>Jabatan GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.satuan-kerja.additional-tasks', 'user.gtk-additional-tasks.']) ? ' active' : '' }}"
           href="{{ $workUnitUrl('user.schools.satuan-kerja.additional-tasks') }}">
            <i class="ri-task-line"></i><span>Tugas Tambahan GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.satuan-kerja.other-tasks', 'user.other-teacher-tasks.']) ? ' active' : '' }}"
           href="{{ $workUnitUrl('user.schools.satuan-kerja.other-tasks') }}">
            <i class="ri-user-settings-line"></i><span>Tugas Tambahan Guru</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk-position-proposals.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.gtk-position-proposals.index') }}">
            <i class="ri-arrow-up-line"></i><span>Pengajuan Jabatan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — REKRUTMEN  [AKTIF]
     Akses: Kepala & Staf Personalia
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal) && menu_allowed('humas'))
    <li class="menu-title"><span>Rekrutmen</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.recruitment.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.recruitment.index') }}">
            <i class="ri-user-add-line"></i><span>Lowongan Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ats.applications.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.ats.applications.index') }}">
            <i class="ri-file-user-line"></i><span>Pelamar</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ats.candidates.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.ats.candidates.index') }}">
            <i class="ri-user-star-line"></i><span>Kandidat</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ats.interviews.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.ats.interviews.index') }}">
            <i class="ri-chat-check-line"></i><span>Wawancara</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ats.data-nilai.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.ats.data-nilai.index') }}">
            <i class="ri-survey-line"></i><span>Data Nilai Pelamar</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ats.reports.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.ats.reports.index') }}">
            <i class="ri-file-chart-line"></i><span>Laporan Rekrutmen</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ats.settings.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.ats.settings.index') }}">
            <i class="ri-settings-3-line"></i><span>Pengaturan ATS</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — KONTRAK & KEPEGAWAIAN  [AKTIF]
     Akses: Kepala & Staf Personalia
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal) && menu_allowed('humas'))
    <li class="menu-title"><span>Kontrak & Kepegawaian</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kontrak.index']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kontrak.index') }}">
            <i class="ri-file-text-line"></i><span>Kontrak Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kontrak.expiring']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kontrak.expiring') }}">
            <i class="ri-alarm-warning-line"></i><span>Kontrak Akan Habis</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kontrak.template']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kontrak.template') }}">
            <i class="ri-file-copy-line"></i><span>Template Kontrak</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.pension.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.pension.index') }}">
            <i class="ri-user-shared-line"></i><span>Pensiun</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk-requests.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.gtk-requests.index') }}">
            <i class="ri-mail-send-line"></i><span>Pengajuan GTK</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — PRESENSI & KEHADIRAN  [AKTIF]
     Alur #2: Manajemen Presensi & Izin/Cuti GTK
     Akses: Kepala & Staf Personalia
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal) && menu_allowed('humas'))
    <li class="menu-title"><span>Presensi & Kehadiran</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.index']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.absensi-gtk.index') }}">
            <i class="ri-calendar-check-line"></i><span>Absensi GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.harian']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.absensi-gtk.harian') }}">
            <i class="ri-calendar-line"></i><span>Absensi Harian</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.rekap-bulanan']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.absensi-gtk.rekap-bulanan') }}">
            <i class="ri-bar-chart-line"></i><span>Rekap Bulanan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.izin']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.absensi-gtk.izin') }}">
            <i class="ri-file-paper-2-line"></i><span>Izin GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.settings']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.absensi-gtk.settings') }}">
            <i class="ri-settings-3-line"></i><span>Pengaturan Absensi</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.waka-dashboard', 'user.teacher-qr.history']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.teacher-qr.waka-dashboard') }}">
            <i class="ri-qr-scan-line"></i><span>Kehadiran QR Guru</span>
        </a>
    </li>

    @if(canPermission('teacher-attendance_manual'))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.manual']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.teacher-qr.manual') }}">
            <i class="ri-keyboard-line"></i><span>Absen Manual Guru</span>
        </a>
    </li>
    @endif

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.qr.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.qr.index') }}">
            <i class="ri-qr-code-line"></i><span>QR Kelas</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kehadiran.rekap']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kehadiran.rekap') }}">
            <i class="ri-bar-chart-2-line"></i><span>Rekap Kehadiran</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kehadiran.pergantian-jam']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kehadiran.pergantian-jam') }}">
            <i class="ri-refresh-line"></i><span>Pergantian Jam</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — CUTI & JAM KERJA  [AKTIF]
     Akses: Kepala & Staf Personalia
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal) && menu_allowed('humas'))
    <li class="menu-title"><span>Cuti & Jam Kerja</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.cuti.index']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.cuti.index') }}">
            <i class="ri-calendar-close-line"></i><span>Daftar Cuti</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.cuti.approval']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.cuti.approval') }}">
            <i class="ri-checkbox-circle-line"></i><span>Approval Cuti</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.cuti.rekap']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.cuti.rekap') }}">
            <i class="ri-bar-chart-line"></i><span>Rekap Cuti</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.cuti.quota']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.cuti.quota') }}">
            <i class="ri-stack-line"></i><span>Kuota Cuti</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jam-kerja.index']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.jam-kerja.index') }}">
            <i class="ri-time-line"></i><span>Jam Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jam-kerja.shift']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.jam-kerja.shift') }}">
            <i class="ri-calendar-schedule-line"></i><span>Shift Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jam-kerja.kalender']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.jam-kerja.kalender') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kerja</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 9 — KINERJA & PENGEMBANGAN  [AKTIF]
     Alur #3: Evaluasi Kinerja GTK
     Akses: Kepala & Staf Personalia
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal) && menu_allowed('humas'))
    <li class="menu-title"><span>Kinerja & Pengembangan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kinerja.index']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kinerja.index') }}">
            <i class="ri-line-chart-line"></i><span>Penilaian Kinerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kinerja.periode']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kinerja.periode') }}">
            <i class="ri-calendar-2-line"></i><span>Periode Penilaian</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kinerja.indikator']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kinerja.indikator') }}">
            <i class="ri-list-check-2"></i><span>Indikator KPI</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kinerja.reward']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kinerja.reward') }}">
            <i class="ri-award-line"></i><span>Reward & Penghargaan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kinerja.laporan']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kinerja.laporan') }}">
            <i class="ri-file-chart-line"></i><span>Laporan Kinerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.pelatihan.index']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.pelatihan.index') }}">
            <i class="ri-presentation-line"></i><span>Pelatihan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.pelatihan.sertifikasi']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.pelatihan.sertifikasi') }}">
            <i class="ri-medal-2-line"></i><span>Sertifikasi</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.rapor-gtk.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.rapor-gtk.tahunan') }}">
            <i class="ri-file-user-line"></i><span>Rapor GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.analisis-gtk.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.analisis-gtk.beban-kerja') }}">
            <i class="ri-pie-chart-line"></i><span>Analisis Beban Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jenjang-karir.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.jenjang-karir.career-path.index') }}">
            <i class="ri-road-map-line"></i><span>Jenjang Karir</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 10 — KESEJAHTERAAN & PAYROLL  [AKTIF]
     Akses: Kepala & Staf Personalia
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal) && menu_allowed('humas'))
    <li class="menu-title"><span>Kesejahteraan & Payroll</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kesejahteraan.index', 'user.kesejahteraan.asuransi', 'user.kesejahteraan.benefit']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kesejahteraan.index') }}">
            <i class="ri-hand-heart-line"></i><span>Kesejahteraan GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kesejahteraan.klaim']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kesejahteraan.klaim') }}">
            <i class="ri-file-shield-2-line"></i><span>Klaim Asuransi</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll.index']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.payroll.index') }}">
            <i class="ri-wallet-3-line"></i><span>Payroll</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll.tunjangan']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.payroll.tunjangan') }}">
            <i class="ri-add-circle-line"></i><span>Tunjangan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll.potongan']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.payroll.potongan') }}">
            <i class="ri-subtract-line"></i><span>Potongan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll-slip.index', 'user.payroll-slip.show']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.payroll-slip.index') }}">
            <i class="ri-receipt-line"></i><span>Slip Gaji</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 11 — PERATURAN & DISIPLIN  [AKTIF]
     Akses: Kepala & Staf Personalia
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal) && menu_allowed('humas'))
    <li class="menu-title"><span>Peraturan & Disiplin</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.peraturan.index', 'user.peraturan.show']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.peraturan.index') }}">
            <i class="ri-book-read-line"></i><span>Peraturan Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.peraturan.kategori']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.peraturan.kategori') }}">
            <i class="ri-price-tag-3-line"></i><span>Kategori Peraturan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.peraturan.violation', 'user.peraturan.pelanggaran']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.peraturan.violation') }}">
            <i class="ri-error-warning-line"></i><span>Pelanggaran Kerja</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 12 — LAYANAN HUMAS  [SEBAGIAN AKTIF]
     Alur #4: Center Layanan Informasi & Aduan Wali Santri / Eksternal
     Akses: Kepala & Staf Humas
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canHumas) && menu_allowed('humas'))
    <li class="menu-title"><span>Layanan Humas</span></li>

    {{-- ✅ AKTIF: Data Santri (view — untuk kontak wali) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index', 'user.students.show']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.students.index') }}">
            <i class="ri-user-heart-line"></i><span>Data Santri</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Data Mahrom / Wali Santri --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.mahroms.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.students.mahroms.global') }}">
            <i class="ri-parent-line"></i><span>Data Wali Santri</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Kebijakan Asrama (view — untuk info ke wali) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-policies.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.boarding-policies.index') }}">
            <i class="ri-file-shield-2-line"></i><span>Kebijakan Asrama</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Peraturan Asrama (view) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-regulations.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.boarding-regulations.index') }}">
            <i class="ri-scales-3-line"></i><span>Peraturan Asrama</span>
        </a>
    </li>

    {{-- ⏳ SOON: Keluhan & Aduan Wali Santri --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-customer-service-line"></i>
            <span>Keluhan & Aduan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Pengumuman & Publikasi --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-megaphone-line"></i>
            <span>Pengumuman & Publikasi <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Buku Tamu Eksternal --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-user-received-line"></i>
            <span>Buku Tamu Eksternal <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Hubungan Media & Instansi --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-newspaper-line"></i>
            <span>Hubungan Media <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Survei Kepuasan Wali --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-survey-line"></i>
            <span>Survei Kepuasan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 13 — LAPORAN  [AKTIF]
     Akses: Kepala (semua) & Staf (terbatas)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('humas-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.index']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.laporan.index') }}">
            <i class="ri-file-list-2-line"></i><span>Semua Laporan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.gtk']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.laporan.gtk') }}">
            <i class="ri-team-line"></i><span>Laporan GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.santri']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.laporan.santri') }}">
            <i class="ri-user-star-line"></i><span>Laporan Santri</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 14 — DOKUMEN & KALENDER  [AKTIF]
     Akses: semua jabatan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $canPersonal || $canHumas) && menu_allowed('humas'))
    <li class="menu-title"><span>Dokumen & Kalender</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.institution-decrees.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.institution-decrees.index') }}">
            <i class="ri-file-paper-2-line"></i><span>SK & Keputusan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $humasUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>
@endif