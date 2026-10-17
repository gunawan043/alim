{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: UKS (Unit Kesehatan Santri)
     ───────────────────────────────────────────────────────────────────────────
     Role    : UKS
     Jabatan : 3 —
       Kepala UKS, Staf UKS Putra, Staf UKS Putri
     Tugas   : 2 —
       Admin UKS Putra, Admin UKS Putri
     ───────────────────────────────────────────────────────────────────────────
     FILTER DATA: gender santri & GTK otomatis difilter di controller
       • Staf UKS Putra / Admin UKS Putra → santri & GTK putra
       • Staf UKS Putri / Admin UKS Putri → santri & GTK putri
       • Kepala UKS → semua
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $uksUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepalaUKS     = $hasJabatan('Kepala UKS');
    $isStafPutra     = $hasJabatan('Staf UKS Putra');
    $isStafPutri     = $hasJabatan('Staf UKS Putri');

    $isStaf          = $isStafPutra || $isStafPutri;
    $isStruktural    = $isKepalaUKS;

    /* ── Tugas Tambahan ──────────────────────────────────────────────── */
    $isAdminPutra    = $hasTugas('Admin UKS Putra');
    $isAdminPutri    = $hasTugas('Admin UKS Putri');
    $isAdminUKS      = $isAdminPutra || $isAdminPutri;

    /* ── Sektor (info banner) ────────────────────────────────────────── */
    $sektor = match (true) {
        $isStafPutra, $isAdminPutra => 'Putra',
        $isStafPutri, $isAdminPutri => 'Putri',
        default                     => null,
    };
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 1 — UTAMA
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Utama</span></li>

@if($sektor)
    <li class="nav-item">
        <span class="nav-link text-muted" style="font-size:0.8rem">
            <i class="ri-information-line me-1"></i>
            Sektor: <strong>UKS {{ $sektor }}</strong>
        </span>
    </li>
@endif

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i><span>Beranda</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}"
       href="{{ $uksUrl('user.profile.my') }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
       href="{{ $uksUrl('user.notifications.index') }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ $uksUrl('user.todos.index') }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>

@if($showAll || $isStruktural)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ $uksUrl('user.approvals.index') }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — DASHBOARD
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Dashboard</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.dashboard']) ? ' active' : '' }}"
       href="{{ $uksUrl('user.uks.dashboard') }}">
        <i class="ri-heart-pulse-line"></i><span>Dashboard</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — LAYANAN HARIAN
     Alur: Pendaftaran → Pemeriksaan → Penanganan → Pencatatan
     Akses: Kepala + Staf UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf) && menu_allowed('uks'))
    <li class="menu-title"><span>Layanan Harian</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.patients.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.patients.index') }}">
            <i class="ri-hospital-line"></i><span>Pasien & Antrean</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.health-checkups.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.health-checkups.index') }}">
            <i class="ri-stethoscope-line"></i><span>Pemeriksaan Kesehatan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.medication-administrations.', 'user.uks.medicine-logs.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.medicine-logs.index') }}">
            <i class="ri-first-aid-kit-line"></i><span>Tindakan & Pemberian Obat</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — RAWAT INAP
     Akses: Kepala + Staf UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf) && menu_allowed('uks'))
    <li class="menu-title"><span>Rawat Inap</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.beds.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.beds.index') }}">
            <i class="ri-hotel-bed-line"></i><span>Bed & Ruang Rawat</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.treatment-status.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.patients.index', ['status' => 'rawat_uks']) }}">
            <i class="ri-heart-add-line"></i><span>Status Perawatan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — REKAM MEDIS SANTRI
     Akses: Kepala + Staf UKS + Admin UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf || $isAdminUKS) && menu_allowed('uks'))
    <li class="menu-title"><span>Rekam Medis Santri</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.student-health.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.student-health.index') }}">
            <i class="ri-user-heart-line"></i><span>Profil Kesehatan Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.health-metrics.dashboard', 'user.uks.health-metrics.student']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.health-metrics.dashboard') }}">
            <i class="ri-line-chart-line"></i><span>Dashboard Antropometri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.health-metrics.index', 'user.uks.health-metrics.create', 'user.uks.health-metrics.show', 'user.uks.health-metrics.edit']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.health-metrics.index') }}">
            <i class="ri-scales-3-line"></i><span>Data Antropometri</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — IZIN SAKIT
     Akses: Kepala + Staf UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf) && menu_allowed('uks'))
    <li class="menu-title"><span>Izin Sakit</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.health-permits.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.health-permits.index') }}">
            <i class="ri-file-shield-2-line"></i><span>Daftar Izin Sakit</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — IMUNISASI
     Akses: Kepala + Staf UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf) && menu_allowed('uks'))
    <li class="menu-title"><span>Imunisasi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.immunizations.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.immunizations.index') }}">
            <i class="ri-syringe-line"></i><span>Catatan Imunisasi</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — OBAT & LOGISTIK
     Akses: Kepala + Staf UKS + Admin UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf || $isAdminUKS) && menu_allowed('uks'))
    <li class="menu-title"><span>Obat & Logistik</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.medicine-inventory.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.medicine-inventory.index') }}">
            <i class="ri-capsule-line"></i><span>Stok Obat</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.medicine-logs.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.medicine-logs.index') }}">
            <i class="ri-file-list-2-line"></i><span>Log Pemberian Obat</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 9 — KONSELING
     Akses: Kepala + Staf UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf) && menu_allowed('uks'))
    <li class="menu-title"><span>Konseling</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.counseling-records.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.counseling-records.index') }}">
            <i class="ri-chat-heart-line"></i><span>Catatan Konseling</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.student-counseling.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.student-counseling.index') }}">
            <i class="ri-mental-health-line"></i><span>Konseling Santri</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 10 — RUJUKAN FASKES
     Akses: Kepala + Staf UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf) && menu_allowed('uks'))
    <li class="menu-title"><span>Rujukan Faskes</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.facility-referrals.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.facility-referrals.index') }}">
            <i class="ri-hospital-fill"></i><span>Rujukan RS / Klinik</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 11 — SANITASI & KEBERSIHAN
     Akses: Kepala + Staf UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf) && menu_allowed('uks'))
    <li class="menu-title"><span>Sanitasi & Kebersihan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.sanitation-inspections.dashboard']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.sanitation-inspections.dashboard') }}">
            <i class="ri-dashboard-2-line"></i><span>Dashboard Sanitasi</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.sanitation-inspections.index', 'user.uks.sanitation-inspections.create', 'user.uks.sanitation-inspections.show', 'user.uks.sanitation-inspections.edit']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.sanitation-inspections.index') }}">
            <i class="ri-brush-line"></i><span>Inspeksi Kebersihan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 12 — KESEHATAN GTK
     Akses: Kepala + Staf UKS + Admin UKS
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS || $isStaf || $isAdminUKS) && menu_allowed('uks'))
    <li class="menu-title"><span>Kesehatan GTK</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.gtk-health.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.gtk-health.index') }}">
            <i class="ri-user-heart-line"></i><span>Profil Kesehatan GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.profile']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.profile') }}">
            <i class="ri-account-circle-line"></i><span>Kesehatan Saya</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 13 — JADWAL SHIFT UKS (24 JAM)
     Akses: Kepala saja
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS) && menu_allowed('uks'))
    <li class="menu-title"><span>Jadwal Shift</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.scheduling.']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.scheduling.index') }}">
            <i class="ri-calendar-todo-line"></i><span>Penjadwalan Shift</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 14 — LAPORAN
     Akses: Kepala saja
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaUKS) && menu_allowed('uks-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.reports.index']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.reports.index') }}">
            <i class="ri-file-chart-line"></i><span>Laporan UKS</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.reports.occupancy']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.reports.occupancy') }}">
            <i class="ri-bar-chart-2-line"></i><span>Okupansi Rawat Inap</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.reports.permits']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.reports.permits') }}">
            <i class="ri-pass-valid-line"></i><span>Laporan Izin Sakit</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.reports.sanitation']) ? ' active' : '' }}"
           href="{{ $uksUrl('user.uks.reports.sanitation') }}">
            <i class="ri-file-shield-line"></i><span>Laporan Sanitasi</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     REFERENSI — Kalender Pendidikan untuk semua user
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Referensi</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
       href="{{ $uksUrl('user.kaldik.index') }}">
        <i class="ri-calendar-event-line"></i><span>Kalender Pendidikan</span>
    </a>
</li>