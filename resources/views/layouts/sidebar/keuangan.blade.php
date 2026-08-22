<!-- Keuangan Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isKepala = in_array($jabatan, ['Kepala Keuangan', 'Kepala Seksi Keuangan']);
$isStaf = $jabatan === 'Staf Keuangan';

function isActiveKeuangan($routeName, $pattern) {
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

{{-- GTK Section --}}
@if($isKepala || $isStaf)
<li class="menu-title"><span>GTK</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveKeuangan($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#gtk_keuangan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveKeuangan($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="gtk_keuangan">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveKeuangan($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="gtk_keuangan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.index' ? ' active' : '' }}" href="{{ route('user.gtk.index', ['userId' => $userId]) }}">Semua GTK</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indexguru' ? ' active' : '' }}" href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}">Guru</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indextendik' ? ' active' : '' }}" href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}">Tendik</a></li>
        </ul>
    </div>
</li>
@endif

<li class="menu-title"><span>Keuangan</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveKeuangan($currentRoute, 'user.payroll.') ? ' active' : '' }}"
       href="{{ route('user.payroll.index', ['userId' => $userId]) }}">
        <i class="ri-money-dollar-circle-line"></i>
        <span>Penggajian GTK</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveKeuangan($currentRoute, 'user.laporan.') ? ' active' : '' }}"
       href="{{ route('user.laporan.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Laporan Keuangan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveKeuangan($currentRoute, 'user.gtk-additional-tasks.') ? ' active' : '' }}"
       href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-add-circle-line"></i>
        <span>Tugas Tambahan</span>
    </a>
</li>

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveKeuangan'])
