<!-- Satuan Pendidikan Sidebar — Single sidebar for KSP, Wakil KSP, TU, & Guru -->
<!-- Menu tampilan berbeda berdasarkan jabatan user -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

$jabatan = $currentUser->gtkEmployment?->jabatan;

function isActiveSP($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}

// Deteksi jabatan untuk variasi menu
$isKepala = in_array($jabatan, ['Kepala Satuan Pendidikan', 'Kepala Sekolah']);
$isWakil  = in_array($jabatan, ['Wakil Kepala Satuan Pendidikan', 'Wakil Kepala Sekolah']);
$isTU     = in_array($jabatan, ['Kepala Tata Usaha', 'Staf Tata Usaha']);
$isGuru   = in_array($jabatan, ['Guru Umum', 'Guru Agama', 'Guru Hadits', 'Guru Bahasa Arab', 'Guru Tahfidz']);
$isWaliKelas = in_array($jabatan, ['Wali Kelas']);
$isKoord   = in_array($jabatan, ['Koordinator Kurikulum', 'Koordinator Kesiswaan', 'Koordinator Sarpras Sekolah', 'Koordinator Guru', 'Koordinator Tahfidz', 'Koordinator Bahasa Arab']);
$isWaka = in_array($jabatan, ['Waka Kurikulum', 'Waka Kesiswaan', 'Waka Sarpras']);
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

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.schools.') || isActiveSP($currentRoute, 'user.schools-global.') ? ' active' : '' }}"
       href="{{ route('user.schools-global.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i>
        <span>Satuan Pendidikan</span>
    </a>
</li>

{{-- GTK Section (KSP, Waka, Waka Kurikulum, TU) --}}
@if($isKepala || $isWakil || $isTU || $isKoord)
<li class="menu-title"><span>GTK & Peserta Didik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSP($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="gtk">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSP($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSP($currentRoute, 'user.gtk.indexguru') ? ' active' : '' }}" href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}" style="font-size:0.85rem">Guru</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSP($currentRoute, 'user.gtk.indextendik') ? ' active' : '' }}" href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}" style="font-size:0.85rem">Tendik</a></li>
        </ul>
    </div>
</li>
@endif

{{-- Pengajuan Jabatan (KSP / Kepala Departemen) --}}
@if($isKepala && auth()->user()->can('gtk_position_proposal_create'))
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.gtk-position-proposals.') ? ' active' : '' }}"
       href="#gtk_position_proposals_kepala" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSP($currentRoute, 'user.gtk-position-proposals.') ? 'true' : 'false' }}"
       aria-controls="gtk_position_proposals_kepala">
        <i class="ri-file-transfer-line"></i>
        <span>Pengajuan Jabatan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSP($currentRoute, 'user.gtk-position-proposals.') ? ' show' : '' }}" id="gtk_position_proposals_kepala">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSP($currentRoute, 'user.gtk-position-proposals.index') ? ' active' : '' }}" href="{{ route('user.gtk-position-proposals.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Daftar Pengajuan</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSP($currentRoute, 'user.gtk-position-proposals.create') ? ' active' : '' }}" href="{{ route('user.gtk-position-proposals.create', ['userId' => $userId]) }}" style="font-size:0.85rem">Buat Pengajuan</a></li>
        </ul>
    </div>
</li>
@endif

{{-- Muatanajar Section (Guru) --}}
@if($isGuru || $isWaliKelas || $isWaka || $isKoord)
<li class="menu-title"><span>Akademik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.schools.guru-mapel.') ? ' active' : '' }}"
       href="{{ route('user.schools.guru-mapel.index', ['userId' => $userId]) }}">
        <i class="ri-book-2-line"></i>
        <span>Buku Admin Guru</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.schools.nilai.') ? ' active' : '' }}"
       href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
        <i class="ri-survey-line"></i>
        <span>Nilai</span>
    </a>
</li>
@endif

{{-- Wali Kelas Section --}}
@if($isWaliKelas)
<li class="menu-title"><span>Kelas Saya</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.absensi.harian.') ? ' active' : '' }}"
       href="#wali_absensi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSP($currentRoute, 'user.absensi.harian.') ? 'true' : 'false' }}"
       aria-controls="wali_absensi">
        <i class="ri-checkbox-multiple-fill"></i>
        <span>Absensi Harian</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSP($currentRoute, 'user.absensi.harian.') ? ' show' : '' }}" id="wali_absensi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSP($currentRoute, 'user.absensi.harian.index') ? ' active' : '' }}" href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Input Absensi</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSP($currentRoute, 'user.absensi.harian.recap') ? ' active' : '' }}" href="{{ route('user.absensi.harian.recap', ['userId' => $userId]) }}" style="font-size:0.85rem">Rekap Absensi</a></li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.teacher-qr.') ? ' active' : '' }}"
       href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}">
        <i class="ri-qr-scan-2-line"></i>
        <span>Riwayat Absensi QR</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.grade-levels.') || isActiveSP($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
       href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Data Kelas / Rombel</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.schools.nilai-kelas.') ? ' active' : '' }}"
       href="{{ route('user.schools.nilai-kelas.index', ['userId' => $userId]) }}">
        <i class="ri-file-list-3-line"></i>
        <span>Leger Nilai</span>
    </a>
</li>
@endif

{{-- TU: SK Pembagian Tugas --}}
@if($isTU)
<li class="menu-title"><span>Administrasi</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.institution-decrees.') || isActiveSP($currentRoute, 'user.teaching-assignments.') ? ' active' : '' }}"
       href="#administrasi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSP($currentRoute, 'user.institution-decrees.') || isActiveSP($currentRoute, 'user.teaching-assignments.') ? 'true' : 'false' }}"
       aria-controls="administrasi">
        <i class="ri-file-text-line"></i>
        <span>Administrasi</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSP($currentRoute, 'user.institution-decrees.') || isActiveSP($currentRoute, 'user.teaching-assignments.') ? ' show' : '' }}" id="administrasi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSP($currentRoute, 'user.institution-decrees.') ? ' active' : '' }}" href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}" style="font-size:0.85rem">SK Pembagian Tugas</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSP($currentRoute, 'user.teaching-assignments.') ? ' active' : '' }}" href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Jadwal Mengajar</a></li>
        </ul>
    </div>
</li>
@endif

{{-- Laporan (KSP, Waka) --}}
@if($isKepala || $isWakil || $isWaka)
<li class="menu-title"><span>Laporan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSP($currentRoute, 'user.laporan.') ? ' active' : '' }}"
       href="{{ route('user.laporan.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Laporan</span>
    </a>
</li>
@endif

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveSP'])
