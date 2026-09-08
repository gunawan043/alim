<!-- Humas Personalia Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

if (! function_exists('isActiveHumas')) {
function isActiveHumas($routeName, $pattern) {
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

{{-- GTK Section --}}
<li class="menu-title"><span>GTK & Personalia</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveHumas($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#data_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveHumas($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="data_gtk">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveHumas($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="data_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.index' ? ' active' : '' }}" href="{{ route('user.gtk.index', ['userId' => $userId]) }}">Semua GTK</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indexguru' ? ' active' : '' }}" href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}">Guru</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indextendik' ? ' active' : '' }}" href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}">Tendik</a></li>
        </ul>
    </div>
</li>

{{-- Absensi GTK --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveHumas($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }}"
       href="#absensi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveHumas($currentRoute, 'user.absensi-gtk.') ? 'true' : 'false' }}"
       aria-controls="absensi">
        <i class="ri-time-line"></i>
        <span>Absensi GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveHumas($currentRoute, 'user.absensi-gtk.') ? ' show' : '' }}" id="absensi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.absensi-gtk.index' ? ' active' : '' }}" href="{{ route('user.absensi-gtk.index', ['userId' => $userId]) }}">Rekap Absensi</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.absensi-gtk.harian' ? ' active' : '' }}" href="{{ route('user.absensi-gtk.harian', ['userId' => $userId]) }}">Absensi Harian</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.absensi-gtk.rekap-bulanan' ? ' active' : '' }}" href="{{ route('user.absensi-gtk.rekap-bulanan', ['userId' => $userId]) }}">Rekap Bulanan</a></li>
        </ul>
    </div>
</li>

{{-- Cuti & Izin --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveHumas($currentRoute, 'user.cuti.') ? ' active' : '' }}"
       href="#cuti_izin" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveHumas($currentRoute, 'user.cuti.') ? 'true' : 'false' }}"
       aria-controls="cuti_izin">
        <i class="ri-calendar-check-line"></i>
        <span>Cuti & Izin</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveHumas($currentRoute, 'user.cuti.') ? ' show' : '' }}" id="cuti_izin">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.cuti.index' ? ' active' : '' }}" href="{{ route('user.cuti.index', ['userId' => $userId]) }}">Daftar Cuti</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.cuti.rekap' ? ' active' : '' }}" href="{{ route('user.cuti.rekap', ['userId' => $userId]) }}">Rekap Cuti</a></li>
        </ul>
    </div>
</li>

{{-- Rekrutmen --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveHumas($currentRoute, 'user.ats.') ? ' active' : '' }}"
       href="#ats_recruitment" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveHumas($currentRoute, 'user.ats.') ? 'true' : 'false' }}"
       aria-controls="ats_recruitment">
        <i class="ri-user-add-line"></i>
        <span>Rekrutmen GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveHumas($currentRoute, 'user.ats.') ? ' show' : '' }}" id="ats_recruitment">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.ats.jobs.index' ? ' active' : '' }}" href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}">Lowongan</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.ats.candidates.index' ? ' active' : '' }}" href="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}">Kandidat</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.ats.applications.index' ? ' active' : '' }}" href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}">Lamaran</a></li>
        </ul>
    </div>
</li>

{{-- Jenjang Karir --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveHumas($currentRoute, 'user.jenjang-karir.') ? ' active' : '' }}"
       href="#jenjang_karir" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveHumas($currentRoute, 'user.jenjang-karir.') ? 'true' : 'false' }}"
       aria-controls="jenjang_karir">
        <i class="ri-rocket-line"></i>
        <span>Jenjang Karir</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveHumas($currentRoute, 'user.jenjang-karir.') ? ' show' : '' }}" id="jenjang_karir">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.jenjang-karir.mutasi.index' ? ' active' : '' }}" href="{{ route('user.jenjang-karir.mutasi.index', ['userId' => $userId]) }}">Mutasi & Rotasi</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.jenjang-karir.promosi.index' ? ' active' : '' }}" href="{{ route('user.jenjang-karir.promosi.index', ['userId' => $userId]) }}">Promosi & Demosi</a></li>
        </ul>
    </div>
</li>

{{-- Tugas Tambahan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveHumas($currentRoute, 'user.gtk-additional-tasks.') ? ' active' : '' }}"
       href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-add-circle-line"></i>
        <span>Tugas Tambahan GTK</span>
    </a>
</li>

{{-- Master Data --}}
@include('layouts.sidebar.master.sidebar')

@include('layouts.sidebar.uks.sidebar', ['isActiveFn' => 'isActiveHumas'])
