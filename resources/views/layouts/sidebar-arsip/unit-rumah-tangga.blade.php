<!-- Unit Rumah Tangga / Sarpras Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isKepala = in_array($jabatan, ['Kepala Unit Rumah Tangga', 'Kepala Sarpras']);

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
    <a class="nav-link menu-link{{ $currentRoute === 'user.profile.my' ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i>
        <span>Profile</span>
    </a>
</li>

<li class="menu-title"><span>Sarana Prasarana</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.') ? ' active' : '' }}"
       href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">
        <i class="ri-dashboard-3-line"></i>
        <span>Dashboard Sarpras</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.gedung.') ? ' active' : '' }}"
       href="#gedung" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.gedung.') ? 'true' : 'false' }}"
       aria-controls="gedung">
        <i class="ri-hotel-building-line"></i>
        <span>Gedung & Ruangan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.gedung.') ? ' show' : '' }}" id="gedung">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveURT($currentRoute, 'sarpras.gedung.index') ? ' active' : '' }}" href="{{ route('sarpras.gedung.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Daftar Gedung</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveURT($currentRoute, 'sarpras.ruangan.index') ? ' active' : '' }}" href="{{ route('sarpras.ruangan.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Daftar Ruangan</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.inventory.') || isActiveURT($currentRoute, 'sarpras.assets.') ? ' active' : '' }}"
       href="#inventory" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.inventory.') || isActiveURT($currentRoute, 'sarpras.assets.') ? 'true' : 'false' }}"
       aria-controls="inventory">
        <i class="ri-archive-line"></i>
        <span>Inventaris Aset</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.inventory.') || isActiveURT($currentRoute, 'sarpras.assets.') ? ' show' : '' }}" id="inventory">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveURT($currentRoute, 'sarpras.inventory.index') ? ' active' : '' }}" href="{{ route('sarpras.inventory.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Daftar Aset</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveURT($currentRoute, 'sarpras.inventory.move') ? ' active' : '' }}" href="{{ route('sarpras.inventory.move.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Mutasi Aset</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.maintenance.') ? ' active' : '' }}"
       href="#maintenance" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveURT($currentRoute, 'sarpras.maintenance.') ? 'true' : 'false' }}"
       aria-controls="maintenance">
        <i class="ri-tools-line"></i>
        <span>Pemeliharaan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveURT($currentRoute, 'sarpras.maintenance.') ? ' show' : '' }}" id="maintenance">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveURT($currentRoute, 'sarpras.maintenance.index') ? ' active' : '' }}" href="{{ route('sarpras.maintenance.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Daftar Permohonan</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveURT($currentRoute, 'sarpras.maintenance.schedule') ? ' active' : '' }}" href="{{ route('sarpras.maintenance.schedule.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Jadwal Pemeliharaan</a></li>
        </ul>
    </div>
</li>

{{-- GTK Section (Kepala) --}}
@if($isKepala)
<li class="menu-title"><span>GTK Terkait</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="{{ route('user.gtk.index', ['userId' => $userId]) }}">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
</li>
@endif

<li class="menu-title"><span>Laporan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'sarpras.reports.') || isActiveURT($currentRoute, 'user.laporan.') ? ' active' : '' }}"
       href="{{ route('user.laporan.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Laporan Sarpras</span>
    </a>
</li>

@include('layouts.sidebar.uks.sidebar', ['isActiveFn' => 'isActiveURT'])
