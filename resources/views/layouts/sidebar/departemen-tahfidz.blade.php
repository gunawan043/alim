<!-- Departemen Tahfidz Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isGuru = in_array($jabatan, ['Guru Tahfidz', 'Guru Hadits', 'Guru Bahasa Arab']);
$isWaliKelas = $jabatan === 'Wali Kelas';
$isWaka = in_array($jabatan, ['Waka Tahfidz', 'Koordinator Tahfidz']);

function isActiveTahfidz($routeName, $pattern) {
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

{{-- GTK & Admin Section --}}
@if($isWaka || $isGuru || $isWaliKelas)
<li class="menu-title"><span>GTK</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#gtk_tahfidz" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveTahfidz($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="gtk_tahfidz">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveTahfidz($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="gtk_tahfidz">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.index' ? ' active' : '' }}" href="{{ route('user.gtk.index', ['userId' => $userId]) }}">Semua GTK</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indexguru' ? ' active' : '' }}" href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}">Guru</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.gtk.indextendik' ? ' active' : '' }}" href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}">Tendik</a></li>
        </ul>
    </div>
</li>
@endif

{{-- Tugas Tambahan --}}
@if($isWaka || $isGuru || $isWaliKelas)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.gtk-additional-tasks.') ? ' active' : '' }}"
       href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-add-circle-line"></i>
        <span>Tugas Tambahan</span>
    </a>
</li>
@endif

{{-- Mutasi & Rotasi --}}
@if($isWaka)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.jenjang-karir.mutasi.') ? ' active' : '' }}"
       href="{{ route('user.jenjang-karir.mutasi.index', ['userId' => $userId]) }}">
        <i class="ri-arrow-left-right-line"></i>
        <span>Mutasi & Rotasi</span>
    </a>
</li>
@endif

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.schools.guru-mapel.') ? ' active' : '' }}"
       href="{{ route('user.schools.guru-mapel.index', ['userId' => $userId]) }}">
        <i class="ri-book-2-line"></i>
        <span>Buku Admin Guru</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.grade-levels.') || isActiveTahfidz($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
       href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Data Kelas</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.schools.nilai.') ? ' active' : '' }}"
       href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
        <i class="ri-survey-line"></i>
        <span>Nilai Tahfidz</span>
    </a>
</li>

{{-- Wali Kelas Section --}}
@if($isWaliKelas)
<li class="menu-title"><span>Kelas Saya</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.absensi.harian.') ? ' active' : '' }}"
       href="#wali_absensi_tahfidz" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveTahfidz($currentRoute, 'user.absensi.harian.') ? 'true' : 'false' }}"
       aria-controls="wali_absensi_tahfidz">
        <i class="ri-checkbox-multiple-fill"></i>
        <span>Absensi Harian</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveTahfidz($currentRoute, 'user.absensi.harian.') ? ' show' : '' }}" id="wali_absensi_tahfidz">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveTahfidz($currentRoute, 'user.absensi.harian.index') ? ' active' : '' }}" href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Input Absensi</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveTahfidz($currentRoute, 'user.absensi.harian.recap') ? ' active' : '' }}" href="{{ route('user.absensi.harian.recap', ['userId' => $userId]) }}" style="font-size:0.85rem">Rekap Absensi</a></li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.schools.nilai-kelas.') ? ' active' : '' }}"
       href="{{ route('user.schools.nilai-kelas.index', ['userId' => $userId]) }}">
        <i class="ri-file-list-3-line"></i>
        <span>Leger Nilai</span>
    </a>
</li>
@endif

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.teaching-assignments.') ? ' active' : '' }}"
       href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-line"></i>
        <span>Jadwal Mengajar</span>
    </a>
</li>

{{-- Pengajuan Jabatan (Kepala Departemen) --}}
@if(in_array($jabatan, ['Kepala Departemen Tahfidz', 'Kepala Departemen Bahasa', 'Kepala Departemen Kesiswaan']) && auth()->user()->can('gtk_position_proposal_create'))
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveTahfidz($currentRoute, 'user.gtk-position-proposals.') ? ' active' : '' }}"
       href="{{ route('user.gtk-position-proposals.index', ['userId' => $userId]) }}">
        <i class="ri-file-transfer-line"></i>
        <span>Pengajuan Jabatan</span>
    </a>
</li>
@endif

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveTahfidz'])
