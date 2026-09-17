<!-- Teknologi Informasi Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isKepala = $jabatan === 'Kepala Teknologi Informasi';

if (! function_exists('isActiveTI')) {
function isActiveTI($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}
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
    @if(canPermission('super-admin-only'))
    <a class="nav-link menu-link{{ $currentRoute === 'system.monitoring' ? ' active' : '' }}"
       href="{{ route('system.monitoring') }}">
        <i class="ri-server-line"></i>
        <span>Monitoring Sistem</span>
    </a>
    @endif
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
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTI($currentRoute, 'user.gtk-additional-tasks.') ? ' active' : '' }}"
       href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i>
        <span>Tugas Tambahan GTK</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTI($currentRoute, 'user.gtk-positions.') ? ' active' : '' }}"
       href="{{ route('user.gtk-positions.index', ['userId' => $userId]) }}">
        <i class="ri-briefcase-line"></i>
        <span>Jabatan GTK</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTI($currentRoute, 'user.gtk-position-proposals.') ? ' active' : '' }}"
       href="{{ route('user.gtk-position-proposals.index', ['userId' => $userId]) }}">
        <i class="ri-arrow-up-line"></i>
        <span>Pengajuan Jabatan</span>
    </a>
</li>
@endif

{{-- Master Data --}}
@include('layouts.sidebar.master.sidebar')

