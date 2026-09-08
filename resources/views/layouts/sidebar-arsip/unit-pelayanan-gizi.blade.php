<!-- Unit Pelayanan Gizi Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isKepala = in_array($jabatan, ['Kepala Unit Gizi', 'Kepala Seksi Gizi']);

function isActiveGizi($routeName, $pattern) {
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

<li class="menu-title"><span>Pelayanan Gizi</span></li>

{{-- Pengelolaan Makan — akan diaktifkan setelah controller dibuat --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false;" title="Fitur sedang dikembangkan">
        <i class="ri-food-line"></i>
        <span>Pengelolaan Makan</span>
        <small class="ms-1 text-warning" style="font-size:0.7rem">( Soon )</small>
    </a>
</li>

{{-- Santri Section --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGizi($currentRoute, 'user.students.') ? ' active' : '' }}"
       href="{{ route('user.students.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Data Santri</span>
    </a>
</li>

{{-- GTK Section (Kepala) --}}
@if($isKepala)
<li class="menu-title"><span>GTK</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGizi($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="{{ route('user.gtk.index', ['userId' => $userId]) }}">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
</li>
@endif

{{-- Laporan --}}
<li class="menu-title"><span>Laporan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGizi($currentRoute, 'user.laporan.') || isActiveGizi($currentRoute, 'user.reports.gizi') ? ' active' : '' }}"
       href="{{ route('user.laporan.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Laporan Gizi</span>
    </a>
</li>

@include('layouts.sidebar.uks.sidebar', ['isActiveFn' => 'isActiveGizi'])
