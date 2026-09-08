<!-- Sarpras Sidebar — shared partial untuk Admin Sarpras, Admin Tata Usaha, dan Satuan Pendidikan -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$isSarprasAdmin = $currentUser->hasRole('Admin Sarpras') || $currentUser->hasPermissionTo('sarpras_all_access');
$isTU = $currentUser->hasRole('Admin Tata Usaha') || $currentUser->hasRole('Satuan Pendidikan');
$isSuperAdmin = $currentUser->isSystemAdmin() || $currentUser->isSuperAdmin();
$isAdmin = $isSarprasAdmin || $isTU || $isSuperAdmin;

if (! function_exists('isActiveSarpras')) {
function isActiveSarpras($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}
}

$isAdminFn = $isActiveFn ?? 'isActiveSarpras';
@endphp

<li class="menu-title"><span>Sarana Prasarana</span></li>

{{-- Dashboard --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.user.') ? ' active' : '' }}"
       href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">
        <i class="ri-dashboard-3-line"></i>
        <span>Dashboard Saya</span>
    </a>
</li>

{{-- Aset & Inventaris --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.aset') || $isAdminFn($currentRoute, 'sarpras.movements') || $isAdminFn($currentRoute, 'sarpras.perpindahan') ? ' active' : '' }}"
       href="#sarpras-aset" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.aset') || $isAdminFn($currentRoute, 'sarpras.movements') || $isAdminFn($currentRoute, 'sarpras.perpindahan') ? 'true' : 'false' }}"
       aria-controls="sarpras-aset">
        <i class="ri-archive-line"></i>
        <span>Aset & Inventaris</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.aset') || $isAdminFn($currentRoute, 'sarpras.movements') || $isAdminFn($currentRoute, 'sarpras.perpindahan') ? ' show' : '' }}" id="sarpras-aset">
        <ul class="nav nav-sm flex-column">
            @if($isAdmin)
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.aset.index') ? ' active' : '' }}" href="{{ route('sarpras.aset.index', ['userId' => $userId]) }}">Daftar Aset</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.movements') ? ' active' : '' }}" href="{{ route('sarpras.movements.index', ['userId' => $userId]) }}">Mutasi Aset</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.perpindahan') ? ' active' : '' }}" href="{{ route('sarpras.perpindahan.index', ['userId' => $userId]) }}">Perpindahan</a></li>
            @endif
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.user.aset') ? ' active' : '' }}" href="{{ route('sarpras.user.aset.index', ['userId' => $userId]) }}">Aset Saya</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.assets.passport') || $isAdminFn($currentRoute, 'sarpras.assets.scan') ? ' active' : '' }}" href="{{ route('sarpras.assets.scan', ['code' => '']) }}">Scan Passport</a></li>
        </ul>
    </div>
</li>

{{-- Gedung & Ruangan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.gedung') || $isAdminFn($currentRoute, 'sarpras.ruang') ? ' active' : '' }}"
       href="#sarpras-gedung" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.gedung') || $isAdminFn($currentRoute, 'sarpras.ruang') ? 'true' : 'false' }}"
       aria-controls="sarpras-gedung">
        <i class="ri-hotel-building-line"></i>
        <span>Gedung & Ruangan</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.gedung') || $isAdminFn($currentRoute, 'sarpras.ruang') ? ' show' : '' }}" id="sarpras-gedung">
        <ul class="nav nav-sm flex-column">
            @if($isAdmin)
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.gedung') ? ' active' : '' }}" href="{{ route('sarpras.gedung.index', ['userId' => $userId]) }}">Daftar Gedung</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.ruang') ? ' active' : '' }}" href="{{ route('sarpras.ruang.index', ['userId' => $userId]) }}">Daftar Ruangan</a></li>
            @endif
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.user.ruang') ? ' active' : '' }}" href="{{ route('sarpras.user.ruang.index', ['userId' => $userId]) }}">Ruangan Saya</a></li>
        </ul>
    </div>
</li>

{{-- Peminjaman --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.peminjaman') ? ' active' : '' }}"
       href="#sarpras-peminjaman" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.peminjaman') ? 'true' : 'false' }}"
       aria-controls="sarpras-peminjaman">
        <i class="ri-hand-heart-line"></i>
        <span>Peminjaman Aset</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.peminjaman') ? ' show' : '' }}" id="sarpras-peminjaman">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.peminjaman.index') ? ' active' : '' }}" href="{{ route('sarpras.peminjaman.index', ['userId' => $userId]) }}">Daftar Peminjaman</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.peminjaman.create') ? ' active' : '' }}" href="{{ route('sarpras.peminjaman.create', ['userId' => $userId]) }}">Pinjam Aset</a></li>
        </ul>
    </div>
</li>

{{-- Booking Ruangan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.booking') ? ' active' : '' }}"
       href="#sarpras-booking" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.booking') ? 'true' : 'false' }}"
       aria-controls="sarpras-booking">
        <i class="ri-calendar-todo-line"></i>
        <span>Booking Ruangan</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.booking') ? ' show' : '' }}" id="sarpras-booking">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.booking.index') ? ' active' : '' }}" href="{{ route('sarpras.booking.index', ['userId' => $userId]) }}">Daftar Booking</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.booking.create') ? ' active' : '' }}" href="{{ route('sarpras.booking.create', ['userId' => $userId]) }}">Buat Booking</a></li>
        </ul>
    </div>
</li>

{{-- Pemeliharaan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.pemeliharaan') || $isAdminFn($currentRoute, 'sarpras.user.kerusakan') ? ' active' : '' }}"
       href="#sarpras-pemeliharaan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.pemeliharaan') || $isAdminFn($currentRoute, 'sarpras.user.kerusakan') ? 'true' : 'false' }}"
       aria-controls="sarpras-pemeliharaan">
        <i class="ri-tools-line"></i>
        <span>Pemeliharaan</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.pemeliharaan') || $isAdminFn($currentRoute, 'sarpras.user.kerusakan') ? ' show' : '' }}" id="sarpras-pemeliharaan">
        <ul class="nav nav-sm flex-column">
            @if($isAdmin)
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.pemeliharaan.schedule') ? ' active' : '' }}" href="{{ route('sarpras.pemeliharaan.schedule.index', ['userId' => $userId]) }}">Jadwal Pemeliharaan</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.pemeliharaan.log') ? ' active' : '' }}" href="{{ route('sarpras.pemeliharaan.log.index', ['userId' => $userId]) }}">Riwayat Perawatan</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.teknisi') ? ' active' : '' }}" href="{{ route('sarpras.teknisi.dashboard', ['userId' => $userId]) }}">Workspace Teknisi</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.auditor') ? ' active' : '' }}" href="{{ route('sarpras.auditor.dashboard', ['userId' => $userId]) }}">Workspace Auditor</a></li>
            @endif
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.user.kerusakan') ? ' active' : '' }}" href="{{ route('sarpras.user.kerusakan.index', ['userId' => $userId]) }}">Laporan Kerusakan</a></li>
        </ul>
    </div>
</li>

{{-- Pengadaan (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.pengadaan') ? ' active' : '' }}"
       href="#sarpras-pengadaan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.pengadaan') ? 'true' : 'false' }}"
       aria-controls="sarpras-pengadaan">
        <i class="ri-shopping-cart-2-line"></i>
        <span>Pengadaan</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.pengadaan') ? ' show' : '' }}" id="sarpras-pengadaan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.pengadaan.index') ? ' active' : '' }}" href="{{ route('sarpras.pengadaan.index', ['userId' => $userId]) }}">Daftar Pengadaan</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.pengadaan.create') ? ' active' : '' }}" href="{{ route('sarpras.pengadaan.create', ['userId' => $userId]) }}">Buat Pengadaan</a></li>
        </ul>
    </div>
</li>
@endif

{{-- PO / Purchase Order (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.po') ? ' active' : '' }}"
       href="#sarpras-po" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.po') ? 'true' : 'false' }}"
       aria-controls="sarpras-po">
        <i class="ri-file-text-line"></i>
        <span>Purchase Order</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.po') ? ' show' : '' }}" id="sarpras-po">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.po.index') ? ' active' : '' }}" href="{{ route('sarpras.po.index', ['userId' => $userId]) }}">Daftar PO</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.po.create') ? ' active' : '' }}" href="{{ route('sarpras.po.create', ['userId' => $userId]) }}">Buat PO</a></li>
        </ul>
    </div>
</li>
@endif

{{-- QR Code & Audit (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.qr') ? ' active' : '' }}"
       href="#sarpras-qr" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.qr') ? 'true' : 'false' }}"
       aria-controls="sarpras-qr">
        <i class="ri-qr-code-line"></i>
        <span>QR Code & Audit</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.qr') ? ' show' : '' }}" id="sarpras-qr">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.qr.index') ? ' active' : '' }}" href="{{ route('sarpras.qr.index', ['userId' => $userId]) }}">Daftar QR</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.qr.scanner') ? ' active' : '' }}" href="{{ route('sarpras.qr.scanner', ['userId' => $userId]) }}">Scanner</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.qr.lookup') ? ' active' : '' }}" href="{{ route('sarpras.qr.lookup-page', ['userId' => $userId]) }}">Cari Aset</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.qr.bulk-audit') ? ' active' : '' }}" href="{{ route('sarpras.qr.bulk-audit', ['userId' => $userId]) }}">Bulk Audit</a></li>
        </ul>
    </div>
</li>
@endif

{{-- Divisi Portal (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.divisi') || $isAdminFn($currentRoute, 'sarpras.division') ? ' active' : '' }}"
       href="#sarpras-divisi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.divisi') || $isAdminFn($currentRoute, 'sarpras.division') ? 'true' : 'false' }}"
       aria-controls="sarpras-divisi">
        <i class="ri-building-line"></i>
        <span>Portal Divisi</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.divisi') || $isAdminFn($currentRoute, 'sarpras.division') ? ' show' : '' }}" id="sarpras-divisi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.divisi.dashboard') ? ' active' : '' }}" href="{{ route('sarpras.divisi.dashboard', ['userId' => $userId]) }}">Dashboard Divisi</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.divisi.assets') ? ' active' : '' }}" href="{{ route('sarpras.divisi.assets', ['userId' => $userId]) }}">Aset Divisi</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.divisi.history') ? ' active' : '' }}" href="{{ route('sarpras.divisi.history', ['userId' => $userId]) }}">Riwayat</a></li>
        </ul>
    </div>
</li>
@endif

{{-- Sparepart (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.sparepart') ? ' active' : '' }}"
       href="#sarpras-sparepart" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.sparepart') ? 'true' : 'false' }}"
       aria-controls="sarpras-sparepart">
        <i class="ri-tools-fill"></i>
        <span>Sparepart</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.sparepart') ? ' show' : '' }}" id="sarpras-sparepart">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.sparepart.index') ? ' active' : '' }}" href="{{ route('sarpras.sparepart.index', ['userId' => $userId]) }}">Daftar Sparepart</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.sparepart.low-stock') ? ' active' : '' }}" href="{{ route('sarpras.sparepart.low-stock', ['userId' => $userId]) }}">Stok Rendah</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.sparepart.dead-stock') ? ' active' : '' }}" href="{{ route('sarpras.sparepart.dead-stock', ['userId' => $userId]) }}">Stok Mati</a></li>
        </ul>
    </div>
</li>
@endif

{{-- Vendor (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.vendor') ? ' active' : '' }}"
       href="#sarpras-vendor" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.vendor') ? 'true' : 'false' }}"
       aria-controls="sarpras-vendor">
        <i class="ri-shop-line"></i>
        <span>Vendor</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.vendor') ? ' show' : '' }}" id="sarpras-vendor">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.vendor.index') ? ' active' : '' }}" href="{{ route('sarpras.vendor.index', ['userId' => $userId]) }}">Daftar Vendor</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.vendor.rank') ? ' active' : '' }}" href="{{ route('sarpras.vendor.rank', ['userId' => $userId]) }}">Ranking Vendor</a></li>
        </ul>
    </div>
</li>
@endif

{{-- Kepala Approval (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.kepala') ? ' active' : '' }}"
       href="{{ route('sarpras.kepala.index', ['userId' => $userId]) }}">
        <i class="ri-admin-line"></i>
        <span>Approval Kepala</span>
    </a>
</li>
@endif

{{-- PIC Approval (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.pic') ? ' active' : '' }}"
       href="{{ route('sarpras.pic.index', ['userId' => $userId]) }}">
        <i class="ri-user-check-line"></i>
        <span>Approval PIC</span>
    </a>
</li>
@endif

{{-- Disposal (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.disposal') ? ' active' : '' }}"
       href="{{ route('sarpras.disposal.pending', ['userId' => $userId]) }}">
        <i class="ri-delete-bin-line"></i>
        <span>Disposal Aset</span>
    </a>
</li>
@endif

{{-- RvR & Predictive (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.rvr') || $isAdminFn($currentRoute, 'sarpras.predictive') ? ' active' : '' }}"
       href="#sarpras-analytics" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.rvr') || $isAdminFn($currentRoute, 'sarpras.predictive') ? 'true' : 'false' }}"
       aria-controls="sarpras-analytics">
        <i class="ri-line-chart-line"></i>
        <span>RvR & Predictive</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.rvr') || $isAdminFn($currentRoute, 'sarpras.predictive') ? ' show' : '' }}" id="sarpras-analytics">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.rvr') ? ' active' : '' }}" href="{{ route('sarpras.rvr.index', ['userId' => $userId]) }}">Repair vs Replace</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.predictive') ? ' active' : '' }}" href="{{ route('sarpras.predictive.index', ['userId' => $userId]) }}">Predictive Maintenance</a></li>
        </ul>
    </div>
</li>
@endif

{{-- Intelligence Dashboard (Admin only) --}}
@if($isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.dashboard.intelligence') ? ' active' : '' }}"
       href="{{ route('sarpras.dashboard.intelligence', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-box-line"></i>
        <span>Intelligence Dashboard</span>
    </a>
</li>
@endif

{{-- Laporan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $isAdminFn($currentRoute, 'sarpras.laporan') ? ' active' : '' }}"
       href="#sarpras-laporan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $isAdminFn($currentRoute, 'sarpras.laporan') ? 'true' : 'false' }}"
       aria-controls="sarpras-laporan">
        <i class="ri-bar-chart-2-line"></i>
        <span>Laporan</span>
    </a>
    <div class="collapse menu-dropdown{{ $isAdminFn($currentRoute, 'sarpras.laporan') ? ' show' : '' }}" id="sarpras-laporan">
        <ul class="nav nav-sm flex-column">
            @if($isAdmin)
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.laporan.index') ? ' active' : '' }}" href="{{ route('sarpras.laporan.index', ['userId' => $userId]) }}">Semua Laporan</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.laporan.inventaris-per-ruang') ? ' active' : '' }}" href="{{ route('sarpras.laporan.inventaris-per-ruang', ['userId' => $userId]) }}">Inventaris per Ruang</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.laporan.kondisi-aset') ? ' active' : '' }}" href="{{ route('sarpras.laporan.kondisi-aset', ['userId' => $userId]) }}">Kondisi Aset</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.laporan.nilai-aset') ? ' active' : '' }}" href="{{ route('sarpras.laporan.nilai-aset', ['userId' => $userId]) }}">Nilai Aset</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.laporan.peminjaman') ? ' active' : '' }}" href="{{ route('sarpras.laporan.peminjaman', ['userId' => $userId]) }}">Laporan Peminjaman</a></li>
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'sarpras.laporan.pemeliharaan') ? ' active' : '' }}" href="{{ route('sarpras.laporan.pemeliharaan', ['userId' => $userId]) }}">Laporan Pemeliharaan</a></li>
            @endif
            <li class="nav-item"><a class="nav-link{{ $isAdminFn($currentRoute, 'user.laporan') ? ' active' : '' }}" href="{{ route('user.laporan.index', ['userId' => $userId]) }}">Laporan Umum</a></li>
        </ul>
    </div>
</li>
