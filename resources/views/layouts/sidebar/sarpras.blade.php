<!-- Sarpras Sidebar — single role, variasi by jabatan -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

// Pembedaan jabatan di dalam role Sarpras (single role)
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isAdmin = in_array($jabatan, ['Kepala Unit Sarana dan Prasarana', 'Koordinator Sarana dan Prasarana']);
$isStaf = $jabatan === 'Staf Sarana dan Prasarana';

function isActiveSarpras($routeName, $pattern) {
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

<li class="menu-title"><span>Sarpras</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSarpras($currentRoute, 'sarpras.inventory.') || isActiveSarpras($currentRoute, 'sarpras.dashboard') ? ' active' : '' }}"
       href="{{ route('sarpras.dashboard', ['userId' => $userId]) }}">
        <i class="ri-archive-line"></i>
        <span>Inventaris</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSarpras($currentRoute, 'sarpras.laporan.') ? ' active' : '' }}"
       href="{{ route('sarpras.laporan.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Laporan Sarpras</span>
    </a>
</li>

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveSarpras'])

