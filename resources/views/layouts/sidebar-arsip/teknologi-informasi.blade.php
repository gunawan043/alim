<!-- Teknologi Informasi Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isKepala = $jabatan === 'Kepala Teknologi Informasi';

function isActiveTI($routeName, $pattern) {
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

<li class="menu-title"><span>Sistem & Jaringan</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTI($currentRoute, 'user.systems.') || isActiveTI($currentRoute, 'user.network.') ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-server-line"></i>
        <span>Monitoring Sistem</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTI($currentRoute, 'user.users.') || isActiveTI($currentRoute, 'user.accounts.') ? ' active' : '' }}"
       href="{{ route('user.gtk.index', ['userId' => $userId]) }}">
        <i class="ri-user-settings-line"></i>
        <span>Manajemen Akun User</span>
    </a>
</li>

{{-- GTK Section (Kepala TI) --}}
@if($isKepala)
<li class="menu-title"><span>GTK</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTI($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="{{ route('user.gtk.index', ['userId' => $userId]) }}">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTI($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }}"
       href="{{ route('user.absensi-gtk.index', ['userId' => $userId]) }}">
        <i class="ri-time-line"></i>
        <span>Absensi GTK</span>
    </a>
</li>
@endif

{{-- Referensi --}}
<li class="menu-title"><span>Referensi</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTI($currentRoute, 'user.master-data.') ? ' active' : '' }}"
       href="#master_data" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveTI($currentRoute, 'user.master-data.') ? 'true' : 'false' }}"
       aria-controls="master_data">
        <i class="ri-database-2-line"></i>
        <span>Master Data</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveTI($currentRoute, 'user.master-data.') ? ' show' : '' }}" id="master_data">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.master-data.jenis-gtk.index' ? ' active' : '' }}" href="{{ route('user.master-data.jenis-gtk.index', ['userId' => $userId]) }}">Jenis GTK</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.master-data.jabatan.index' ? ' active' : '' }}" href="{{ route('user.master-data.jabatan.index', ['userId' => $userId]) }}">Jabatan</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.master-data.satuan-kerja.index' ? ' active' : '' }}" href="{{ route('user.master-data.satuan-kerja.index', ['userId' => $userId]) }}">Satuan Kerja</a></li>
        </ul>
    </div>
</li>

@include('layouts.sidebar.uks.sidebar', ['isActiveFn' => 'isActiveTI'])
