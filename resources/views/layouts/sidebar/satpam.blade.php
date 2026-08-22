<!-- Satpam Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isKepala = $jabatan === 'Kepala Satpam';
$isWaliJaga = $jabatan === 'Wali Jaga';

function isActiveSatpam($routeName, $pattern) {
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

<li class="menu-title"><span>Satuan Keamanan</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.students.') ? ' active' : '' }}"
       href="{{ route('user.students.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Daftar Santri</span>
    </a>
</li>

@if($isKepala || $isWaliJaga)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.uks.') ? ' active' : '' }}"
       href="#satpam_uks" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSatpam($currentRoute, 'user.uks.') ? 'true' : 'false' }}"
       aria-controls="satpam_uks">
        <i class="ri-heart-pulse-line"></i>
        <span>UKS</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSatpam($currentRoute, 'user.uks.') ? ' show' : '' }}" id="satpam_uks">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSatpam($currentRoute, 'user.uks.health-checkups') ? ' active' : '' }}" href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Medical Check-up</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSatpam($currentRoute, 'user.uks.medicine-inventory') ? ' active' : '' }}" href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Stok Obat</a></li>
        </ul>
    </div>
</li>
@endif

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.asrama.residents.') ? ' active' : '' }}"
       href="{{ route('user.asrama.residents.index', ['userId' => $userId]) }}">
        <i class="ri-hotel-line"></i>
        <span>Daftar Penghuni Asrama</span>
    </a>
</li>

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveSatpam'])
