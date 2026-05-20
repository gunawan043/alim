<!-- Sidebar GTK — Unit Rumah Tangga / Sarana Prasarana (PAH-URT-005) -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

function isActiveURT($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}
@endphp

<li class="menu-title"><span>Menu</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i>
        <span>Dashboard</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'sarpras.dashboard' ? ' active' : '' }}"
       href="{{ route('sarpras.dashboard') }}">
        <i class="ri-dashboard-3-line"></i>
        <span>Dashboard Sarpras</span>
    </a>
</li>

<li class="menu-title"><span>Manajemen Aset</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.gedung.') ? ' active' : '' }}"
       href="#gedung" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.gedung.') ? 'true' : 'false' }}"
       aria-controls="gedung">
        <i class="ri-hotel-building-line"></i>
        <span>Gedung</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.gedung.') ? ' show' : '' }}" id="gedung">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.gedung.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.gedung.index') }}"
                   style="font-size:0.85rem">Daftar Gedung</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.gedung.create' ? ' active' : '' }}"
                   href="{{ route('sarpras.gedung.create') }}"
                   style="font-size:0.85rem">Tambah Gedung</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.ruang.') ? ' active' : '' }}"
       href="#ruang" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.ruang.') ? 'true' : 'false' }}"
       aria-controls="ruang">
        <i class="ri-door-open-line"></i>
        <span>Ruang</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.ruang.') ? ' show' : '' }}" id="ruang">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.ruang.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.ruang.index') }}"
                   style="font-size:0.85rem">Daftar Ruang</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.ruang.create' ? ' active' : '' }}"
                   href="{{ route('sarpras.ruang.create') }}"
                   style="font-size:0.85rem">Tambah Ruang</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.aset.') ? ' active' : '' }}"
       href="#aset" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.aset.') ? 'true' : 'false' }}"
       aria-controls="aset">
        <i class="ri-archive-line"></i>
        <span>Aset / Inventaris</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.aset.') ? ' show' : '' }}" id="aset">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.aset.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.aset.index') }}"
                   style="font-size:0.85rem">Daftar Aset</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.aset.create' ? ' active' : '' }}"
                   href="{{ route('sarpras.aset.create') }}"
                   style="font-size:0.85rem">Tambah Aset</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.aset.import' ? ' active' : '' }}"
                   href="{{ route('sarpras.aset.import') }}"
                   style="font-size:0.85rem">Import Aset</a>
            </li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Operasi Aset</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.peminjaman.') ? ' active' : '' }}"
       href="#peminjaman" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.peminjaman.') ? 'true' : 'false' }}"
       aria-controls="peminjaman">
        <i class="ri-swap-box-line"></i>
        <span>Peminjaman Aset</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.peminjaman.') ? ' show' : '' }}" id="peminjaman">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.peminjaman.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.peminjaman.index') }}"
                   style="font-size:0.85rem">Daftar Peminjaman</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.peminjaman.create' ? ' active' : '' }}"
                   href="{{ route('sarpras.peminjaman.create') }}"
                   style="font-size:0.85rem">Buat Peminjaman</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.perpindahan.') ? ' active' : '' }}"
       href="#perpindahan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.perpindahan.') ? 'true' : 'false' }}"
       aria-controls="perpindahan">
        <i class="ri-arrow-left-right-line"></i>
        <span>Perpindahan Aset</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.perpindahan.') ? ' show' : '' }}" id="perpindahan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.perpindahan.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.perpindahan.index') }}"
                   style="font-size:0.85rem">Riwayat Perpindahan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.perpindahan.create' ? ' active' : '' }}"
                   href="{{ route('sarpras.perpindahan.create') }}"
                   style="font-size:0.85rem">Catat Perpindahan</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.pemeliharaan.') ? ' active' : '' }}"
       href="#pemeliharaan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.pemeliharaan.') ? 'true' : 'false' }}"
       aria-controls="pemeliharaan">
        <i class="ri-tools-line"></i>
        <span>Pemeliharaan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.pemeliharaan.') ? ' show' : '' }}" id="pemeliharaan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.pemeliharaan.schedule.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.pemeliharaan.schedule.index') }}"
                   style="font-size:0.85rem">Jadwal Perawatan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.pemeliharaan.schedule.create' ? ' active' : '' }}"
                   href="{{ route('sarpras.pemeliharaan.schedule.create') }}"
                   style="font-size:0.85rem">Tambah Jadwal</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.pemeliharaan.log.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.pemeliharaan.log.index') }}"
                   style="font-size:0.85rem">Riwayat Perawatan</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.booking.') ? ' active' : '' }}"
       href="#booking" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.booking.') ? 'true' : 'false' }}"
       aria-controls="booking">
        <i class="ri-calendar-check-line"></i>
        <span>Booking Ruangan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.booking.') ? ' show' : '' }}" id="booking">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.booking.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.booking.index') }}"
                   style="font-size:0.85rem">Daftar Booking</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.booking.create' ? ' active' : '' }}"
                   href="{{ route('sarpras.booking.create') }}"
                   style="font-size:0.85rem">Buat Booking</a>
            </li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Pengadaan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.pengadaan.') ? ' active' : '' }}"
       href="#pengadaan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.pengadaan.') ? 'true' : 'false' }}"
       aria-controls="pengadaan">
        <i class="ri-shopping-cart-line"></i>
        <span>Pengadaan Barang</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.pengadaan.') ? ' show' : '' }}" id="pengadaan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.pengadaan.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.pengadaan.index') }}"
                   style="font-size:0.85rem">Daftar Pengadaan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.pengadaan.create' ? ' active' : '' }}"
                   href="{{ route('sarpras.pengadaan.create') }}"
                   style="font-size:0.85rem">Buat Pengadaan</a>
            </li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>QR Code & Audit</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.qr.') ? ' active' : '' }}"
       href="#qr" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.qr.') ? 'true' : 'false' }}"
       aria-controls="qr">
        <i class="ri-qr-code-line"></i>
        <span>QR Code & Audit</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.qr.') ? ' show' : '' }}" id="qr">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.qr.index' ? ' active' : '' }}"
                   href="{{ route('sarpras.qr.index') }}"
                   style="font-size:0.85rem">Generate QR</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.qr.scanner' ? ' active' : '' }}"
                   href="{{ route('sarpras.qr.scanner') }}"
                   style="font-size:0.85rem">Scanner</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.qr.lookup-page' ? ' active' : '' }}"
                   href="{{ route('sarpras.qr.lookup-page') }}"
                   style="font-size:0.85rem">Cari Aset</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.qr.bulk-audit' ? ' active' : '' }}"
                   href="{{ route('sarpras.qr.bulk-audit') }}"
                   style="font-size:0.85rem">Audit Massal</a>
            </li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Laporan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.laporan.') ? ' active' : '' }}"
       href="#laporan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.laporan.') ? 'true' : 'false' }}"
       aria-controls="laporan">
        <i class="ri-file-chart-line"></i>
        <span>Laporan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.laporan.') ? ' show' : '' }}" id="laporan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.laporan.inventaris-per-ruang' ? ' active' : '' }}"
                   href="{{ route('sarpras.laporan.inventaris-per-ruang') }}"
                   style="font-size:0.85rem">Inventaris per Ruang</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.laporan.kondisi-aset' ? ' active' : '' }}"
                   href="{{ route('sarpras.laporan.kondisi-aset') }}"
                   style="font-size:0.85rem">Kondisi Aset</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.laporan.pemeliharaan' ? ' active' : '' }}"
                   href="{{ route('sarpras.laporan.pemeliharaan') }}"
                   style="font-size:0.85rem">Pemeliharaan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.laporan.peminjaman' ? ' active' : '' }}"
                   href="{{ route('sarpras.laporan.peminjaman') }}"
                   style="font-size:0.85rem">Peminjaman</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.laporan.nilai-aset' ? ' active' : '' }}"
                   href="{{ route('sarpras.laporan.nilai-aset') }}"
                   style="font-size:0.85rem">Nilai Aset</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'sarpras.laporan.export' ? ' active' : '' }}"
                   href="{{ route('sarpras.laporan.export') }}"
                   style="font-size:0.85rem">Export Excel</a>
            </li>
        </ul>
    </div>
</li>