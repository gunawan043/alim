{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: UNIT RUMAH TANGGA (URT)
     ───────────────────────────────────────────────────────────────────────────
     Role    : Unit Rumah Tangga
     Jabatan : 5 —
       Kepala Unit Rumah Tangga
       Koordinator Sarana Prasarana
       Teknisi Maintenance
       Petugas Kebersihan (Janitor)
       Driver / Pengemudi
     Tugas   : —
     ───────────────────────────────────────────────────────────────────────────
     BIDANG URT (murni fisik & fasilitas):
       • Aset fisik & bangunan (gedung, ruang, mebel)
       • Kebersihan lingkungan
       • Pemeliharaan & perbaikan
       • Transportasi operasional
     ───────────────────────────────────────────────────────────────────────────
     BATASAN (URT vs UNIT LAIN):
       ❌ BUKAN wewenang URT:
          - Menu makanan & gizi        → Unit Pelayanan Gizi
          - Obat & alat medis          → UKS
          - Jaringan, server, komputer → Teknologi Informasi
       ✅ URT hanya memelihara FISIK bangunan & inventaris mebel
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    /* Semua route sarpras.* TIDAK butuh userId (di luar prefix) */
    $sarprasUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, $extra);
        } catch (\Throwable $e) {
            return '#';
        }
    };
    /* Route user.* butuh userId */
    $userUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepalaURT     = $hasJabatan('Kepala Unit Rumah Tangga');
    $isKoorSarpras   = $hasJabatan('Koordinator Sarana Prasarana');
    $isTeknisi       = $hasJabatan('Teknisi Maintenance');
    $isKebersihan    = $hasJabatan('Petugas Kebersihan');
    $isDriver        = $hasJabatan('Driver') || $hasJabatan('Pengemudi');

    $isStruktural    = $isKepalaURT || $isKoorSarpras;
    $isOperasional   = $isTeknisi || $isKebersihan || $isDriver;
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
       href="{{ $userUrl('user.profile.my') }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
       href="{{ $userUrl('user.notifications.index') }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ $userUrl('user.todos.index') }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>

@if($showAll || $isStruktural)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ $userUrl('user.approvals.index') }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — DASHBOARD
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Dashboard</span></li>

{{-- Dashboard Kepala/Koordinator: sarpras.dashboard (full) --}}
@if($showAll || $isStruktural)
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'sarpras.dashboard' ? ' active' : '' }}"
       href="{{ $sarprasUrl('sarpras.dashboard') }}">
        <i class="ri-dashboard-2-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

{{-- Dashboard Operasional (Teknisi/Kebersihan/Driver): user.dashboard --}}
@if($showAll || $isOperasional)
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'sarpras.user.dashboard' ? ' active' : '' }}"
       href="{{ $sarprasUrl('sarpras.user.dashboard') }}">
        <i class="ri-dashboard-3-line"></i><span>Dashboard</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — ASET & BANGUNAN
     Akses: Kepala, Koordinator (full) | Operasional (view)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>Aset & Bangunan</span></li>

    {{-- Aset: full untuk struktural, view untuk operasional --}}
    @if($showAll || $isStruktural)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.aset.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.aset.index') }}">
            <i class="ri-archive-2-line"></i><span>Data Aset</span>
        </a>
    </li>
    @endif

    @if($showAll || $isOperasional)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.aset.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.user.aset.index') }}">
            <i class="ri-archive-line"></i><span>Aset Saya</span>
        </a>
    </li>
    @endif

    {{-- Gedung: Kepala & Koordinator --}}
    @if($showAll || $isStruktural)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.gedung.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.gedung.index') }}">
            <i class="ri-building-3-line"></i><span>Gedung</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.ruang.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.ruang.index') }}">
            <i class="ri-door-open-line"></i><span>Ruang</span>
        </a>
    </li>
    @endif

    {{-- Ruang Saya: operasional --}}
    @if($showAll || $isOperasional)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.ruang.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.user.ruang.index') }}">
            <i class="ri-layout-grid-line"></i><span>Ruang Saya</span>
        </a>
    </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — QR & AUDIT ASET
     Akses: Kepala, Koordinator
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>QR & Audit Aset</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.qr.index', 'sarpras.qr.generate', 'sarpras.qr.pdf', 'sarpras.qr.print']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.qr.index') }}">
            <i class="ri-qr-code-line"></i><span>Generate QR Aset</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.qr.scanner', 'sarpras.qr.lookup']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.qr.scanner') }}">
            <i class="ri-qr-scan-2-line"></i><span>Scan QR Aset</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.qr.audit.', 'sarpras.qr.audit']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.qr.index') }}">
            <i class="ri-file-shield-2-line"></i><span>Audit Aset</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.qr.bulk-audit']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.qr.bulk-audit') }}">
            <i class="ri-list-check-3"></i><span>Bulk Audit</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.auditor.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.auditor.dashboard') }}">
            <i class="ri-shield-user-line"></i><span>Auditor Workspace</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — PEMELIHARAAN & TEKNISI
     Akses: Kepala, Koordinator (jadwal) | Teknisi (workspace)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isTeknisi) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>Pemeliharaan</span></li>

    @if($showAll || $isStruktural)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.pemeliharaan.schedule']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.pemeliharaan.schedule.index') }}">
            <i class="ri-calendar-todo-line"></i><span>Jadwal Pemeliharaan</span>
        </a>
    </li>
    @endif

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.pemeliharaan.log']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.pemeliharaan.log.index') }}">
            <i class="ri-history-line"></i><span>Riwayat Perawatan</span>
        </a>
    </li>

    {{-- Teknisi Workspace: hanya teknisi & kepala --}}
    @if($showAll || $isTeknisi || $isKepalaURT)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.teknisi.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.teknisi.dashboard') }}">
            <i class="ri-tools-line"></i><span>Workspace Teknisi</span>
        </a>
    </li>
    @endif

    @if($showAll || $isStruktural)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.sparepart.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.sparepart.index') }}">
            <i class="ri-cpu-line"></i><span>Sparepart</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.predictive.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.predictive.index') }}">
            <i class="ri-line-chart-line"></i><span>Predictive Maintenance</span>
        </a>
    </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — PERBAIKAN & KERUSAKAN
     Akses: Kepala, Koordinator | Operasional (input laporan)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>Perbaikan & Kerusakan</span></li>

    {{-- Laporan Kerusakan: input untuk operasional, lihat semua untuk struktural --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.kerusakan.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.user.kerusakan.index') }}">
            <i class="ri-error-warning-line"></i><span>Laporan Kerusakan</span>
        </a>
    </li>

    @if($showAll || $isStruktural)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.rvr.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.rvr.index') }}">
            <i class="ri-scales-3-line"></i><span>Repair vs Replace</span>
        </a>
    </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — PEMINJAMAN & PINDAH ASET
     Akses: Kepala, Koordinator
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>Peminjaman & Pindah</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.peminjaman.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.peminjaman.index') }}">
            <i class="ri-hand-coin-line"></i><span>Peminjaman Aset</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.booking.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.booking.index') }}">
            <i class="ri-calendar-check-line"></i><span>Booking Ruangan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.perpindahan.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.perpindahan.index') }}">
            <i class="ri-truck-line"></i><span>Perpindahan Aset</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.movements.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.movements.index') }}">
            <i class="ri-arrow-left-right-line"></i><span>Multi-Stage Movement</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — PENGADAAN & VENDOR
     Akses: Kepala, Koordinator
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>Pengadaan & Vendor</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.pengadaan.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.pengadaan.index') }}">
            <i class="ri-shopping-cart-2-line"></i><span>Pengadaan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.po.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.po.index') }}">
            <i class="ri-file-list-2-line"></i><span>Purchase Order</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.vendor.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.vendor.index') }}">
            <i class="ri-store-2-line"></i><span>Vendor</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.disposal.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.disposal.pending') }}">
            <i class="ri-delete-bin-6-line"></i><span>Disposal Aset</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 9 — APPROVAL (PIC & KEPALA URT)
     Akses: Kepala (Kepala approval) | Koordinator (PIC approval)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaURT || $isKoorSarpras) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>Approval</span></li>

    @if($showAll || $isKoorSarpras)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.pic.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.pic.index') }}">
            <i class="ri-user-received-line"></i><span>PIC Approval</span>
        </a>
    </li>
    @endif

    @if($showAll || $isKepalaURT)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.kepala.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.kepala.index') }}">
            <i class="ri-shield-check-line"></i><span>Kepala Approval</span>
        </a>
    </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 10 — KEBERSIHAN & LINGKUNGAN  [SEBAGIAN SOON]
     Akses: Koordinator + Petugas Kebersihan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoorSarpras || $isKebersihan) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>Kebersihan & Lingkungan</span></li>

    {{-- ✅ AKTIF: Laporan Kerusakan (petugas bisa lapor kerusakan lingkungan) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.kerusakan.']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.user.kerusakan.create') }}">
            <i class="ri-tools-line"></i><span>Lapor Kerusakan</span>
        </a>
    </li>

    {{-- ⏳ SOON: Jadwal Kebersihan --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-calendar-todo-line"></i>
            <span>Jadwal Kebersihan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Checklist Harian --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-checkbox-multiple-line"></i>
            <span>Checklist Harian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Laporan Kebersihan --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-list-2-line"></i>
            <span>Laporan Kebersihan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 11 — TRANSPORTASI  [SOON]
     Akses: Driver + Kepala/Koordinator
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isDriver || $isStruktural) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>Transportasi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-roadster-line"></i>
            <span>Daftar Kendaraan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-calendar-schedule-line"></i>
            <span>Jadwal Perjalanan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-gas-station-line"></i>
            <span>Log BBM <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-tools-line"></i>
            <span>Servis Rutin <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 12 — ANALYTICS & INTELLIGENCE
     Akses: Kepala saja
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaURT) && menu_allowed('rumah-tangga'))
    <li class="menu-title"><span>Analytics</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.dashboard.intelligence']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.dashboard.intelligence') }}">
            <i class="ri-brain-line"></i><span>Intelligence Dashboard</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 13 — LAPORAN
     Akses: Kepala, Koordinator
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('rumah-tangga-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ $currentRoute === 'sarpras.laporan.index' ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.laporan.index') }}">
            <i class="ri-file-chart-line"></i><span>Semua Laporan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.laporan.inventaris-per-ruang']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.laporan.inventaris-per-ruang') }}">
            <i class="ri-list-check-2"></i><span>Inventaris Per Ruang</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.laporan.kondisi-aset']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.laporan.kondisi-aset') }}">
            <i class="ri-heart-pulse-line"></i><span>Kondisi Aset</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.laporan.peminjaman']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.laporan.peminjaman') }}">
            <i class="ri-hand-coin-line"></i><span>Laporan Peminjaman</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.laporan.pemeliharaan']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.laporan.pemeliharaan') }}">
            <i class="ri-tools-line"></i><span>Laporan Pemeliharaan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.laporan.nilai-aset']) ? ' active' : '' }}"
           href="{{ $sarprasUrl('sarpras.laporan.nilai-aset') }}">
            <i class="ri-money-dollar-circle-line"></i><span>Nilai Aset</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 14 — REFERENSI
     Akses: semua
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('rumah-tangga-pelengkap'))
    <li class="menu-title"><span>Referensi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $userUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
           href="{{ $userUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.work-units.']) ? ' active' : '' }}"
           href="{{ $userUrl('user.work-units.index') }}">
            <i class="ri-community-line"></i><span>Satuan Kerja</span>
        </a>
    </li>
@endif