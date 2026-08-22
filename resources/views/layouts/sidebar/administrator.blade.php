<!-- Administrator Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

function isActiveAdmin($routeName, $pattern) {
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

<li class="menu-title"><span>Administrasi Pondok</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAdmin($currentRoute, 'user.schools.') || isActiveAdmin($currentRoute, 'user.schools-global.') ? ' active' : '' }}"
       href="{{ route('user.schools.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i>
        <span>Daftar Sekolah</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAdmin($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#admin_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAdmin($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="admin_gtk">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAdmin($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="admin_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.index' ? ' active' : '' }}" href="{{ route('user.gtk.index', ['userId' => $userId]) }}">Semua GTK</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indexguru' ? ' active' : '' }}" href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}">Guru</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indextendik' ? ' active' : '' }}" href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}">Tendik</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAdmin($currentRoute, 'user.gtk-additional-tasks.') ? ' active' : '' }}"
       href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-add-circle-line"></i>
        <span>Tugas Tambahan</span>
    </a>
</li>

@if(auth()->user()->can('gtk_position_proposal_approve'))
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAdmin($currentRoute, 'user.gtk-position-proposals.') ? ' active' : '' }}"
       href="#admin_position_proposals" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAdmin($currentRoute, 'user.gtk-position-proposals.') ? 'true' : 'false' }}"
       aria-controls="admin_position_proposals">
        <i class="ri-file-transfer-line"></i>
        <span>Approval Pengajuan Jabatan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAdmin($currentRoute, 'user.gtk-position-proposals.') ? ' show' : '' }}" id="admin_position_proposals">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk-position-proposals.index' ? ' active' : '' }}" href="{{ route('user.gtk-position-proposals.index', ['userId' => $userId]) }}">Semua Pengajuan</a></li>
        </ul>
    </div>
</li>
@endif

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAdmin($currentRoute, 'user.jenjang-karir.') ? ' active' : '' }}"
       href="#admin_karir" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAdmin($currentRoute, 'user.jenjang-karir.') ? 'true' : 'false' }}"
       aria-controls="admin_karir">
        <i class="ri-rocket-line"></i>
        <span>Jenjang Karir</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAdmin($currentRoute, 'user.jenjang-karir.') ? ' show' : '' }}" id="admin_karir">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.jenjang-karir.mutasi.index' ? ' active' : '' }}" href="{{ route('user.jenjang-karir.mutasi.index', ['userId' => $userId]) }}">Mutasi & Rotasi</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.jenjang-karir.promosi.index' ? ' active' : '' }}" href="{{ route('user.jenjang-karir.promosi.index', ['userId' => $userId]) }}">Promosi & Demosi</a></li>
        </ul>
    </div>
</li>

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveAdmin'])
