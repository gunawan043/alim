<!-- Pimpinan Sidebar — Unsur Pimpinan Pondok (Wakil Mudir I & II) -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$userId = auth()->user()->id;

if (! function_exists('isActivePimpinan')) {
function isActivePimpinan($routeName, $pattern) {
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

<li class="menu-title"><span>Pengawasan GTK & Santri</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="{{ route('user.gtk.index', ['userId' => $userId]) }}">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.students.') ? ' active' : '' }}"
       href="{{ route('user.students.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Data Santri</span>
    </a>
</li>

<li class="menu-title"><span>Akademik</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.schools.') ? ' active' : '' }}"
       href="{{ route('user.schools-global.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i>
        <span>Satuan Pendidikan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.grade-levels.') || isActivePimpinan($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
       href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Data Kelas</span>
    </a>
</li>

<li class="menu-title"><span>Laporan Pimpinan</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.') ? ' active' : '' }}"
       href="#rapor_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.') ? 'true' : 'false' }}"
       aria-controls="rapor_gtk">
        <i class="ri-newspaper-line"></i>
        <span>Rapor GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.') ? ' show' : '' }}" id="rapor_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.akademik') ? ' active' : '' }}" href="{{ route('user.rapor-gtk.akademik', ['userId' => $userId]) }}" style="font-size:0.85rem">Penilaian Akademik</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.disiplin') ? ' active' : '' }}" href="{{ route('user.rapor-gtk.disiplin', ['userId' => $userId]) }}" style="font-size:0.85rem">Penilaian Disiplin</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.tahunan') ? ' active' : '' }}" href="{{ route('user.rapor-gtk.tahunan', ['userId' => $userId]) }}" style="font-size:0.85rem">Rekap Tahunan</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.laporan.') ? ' active' : '' }}"
       href="{{ route('user.laporan.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Laporan Umum</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.') ? ' active' : '' }}"
       href="#analisis" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.') ? 'true' : 'false' }}"
       aria-controls="analisis">
        <i class="ri-pencil-ruler-2-line"></i>
        <span>Analisis GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.') ? ' show' : '' }}" id="analisis">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.beban-kerja') ? ' active' : '' }}" href="{{ route('user.analisis-gtk.beban-kerja', ['userId' => $userId]) }}" style="font-size:0.85rem">Beban Kerja</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.rasio-ideal') ? ' active' : '' }}" href="{{ route('user.analisis-gtk.rasio-ideal', ['userId' => $userId]) }}" style="font-size:0.85rem">Rasio Ideal</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.proyeksi') ? ' active' : '' }}" href="{{ route('user.analisis-gtk.proyeksi', ['userId' => $userId]) }}" style="font-size:0.85rem">Proyeksi SDM</a></li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Pengasuhan</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.asrama.') ? ' active' : '' }}"
       href="{{ route('user.asrama.residents.index', ['userId' => $userId]) }}">
        <i class="ri-hotel-line"></i>
        <span>Daftar Penghuni Asrama</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.uks.') ? ' active' : '' }}"
       href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}">
        <i class="ri-heart-pulse-line"></i>
        <span>UKS & Kesehatan</span>
    </a>
</li>

@include('layouts.sidebar.uks.sidebar', ['isActiveFn' => 'isActivePimpinan'])
