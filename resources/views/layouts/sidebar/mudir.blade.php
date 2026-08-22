<!-- Mudir & Wakil Mudir Sidebar — Single sidebar for all leadership levels -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

function isActiveMudir($routeName, $pattern) {
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

<li class="menu-title"><span>Pimpinan Pondok</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveMudir($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#mudir_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveMudir($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="mudir_gtk">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveMudir($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="mudir_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.index' ? ' active' : '' }}" href="{{ route('user.gtk.index', ['userId' => $userId]) }}">Semua GTK</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indexguru' ? ' active' : '' }}" href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}">Guru</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indextendik' ? ' active' : '' }}" href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}">Tendik</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveMudir($currentRoute, 'user.gtk-additional-tasks.') ? ' active' : '' }}"
       href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-add-circle-line"></i>
        <span>Tugas Tambahan</span>
    </a>
</li>

@if(auth()->user()->can('gtk_position_proposal_approve'))
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveMudir($currentRoute, 'user.gtk-position-proposals.') ? ' active' : '' }}"
       href="#mudir_position_proposals" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveMudir($currentRoute, 'user.gtk-position-proposals.') ? 'true' : 'false' }}"
       aria-controls="mudir_position_proposals">
        <i class="ri-file-transfer-line"></i>
        <span>Approval Pengajuan Jabatan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveMudir($currentRoute, 'user.gtk-position-proposals.') ? ' show' : '' }}" id="mudir_position_proposals">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk-position-proposals.index' ? ' active' : '' }}" href="{{ route('user.gtk-position-proposals.index', ['userId' => $userId]) }}">Semua Pengajuan</a></li>
        </ul>
    </div>
</li>
@endif

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveMudir($currentRoute, 'user.schools.') || isActiveMudir($currentRoute, 'user.schools-global.') ? ' active' : '' }}"
       href="{{ route('user.schools-global.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i>
        <span>Satuan Pendidikan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveMudir($currentRoute, 'user.laporan.') ? ' active' : '' }}"
       href="{{ route('user.laporan.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Laporan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveMudir($currentRoute, 'user.kalkulasi-nilai.') ? ' active' : '' }}"
       href="{{ route('user.kalkulasi-nilai.index', ['userId' => $userId]) }}">
        <i class="ri-calculator-line"></i>
        <span>Kalkulasi Nilai</span>
    </a>
</li>

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveMudir'])
