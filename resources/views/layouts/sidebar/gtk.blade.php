<!-- GTK (Guru Mapel) Sidebar -->
<style>
.navbar-nav .nav-item .nav-link.active,
.navbar-nav .nav-item .nav-link.active i {
    background-color: rgba(255,255,255,0.1) !important;
    border-radius: 0.25rem;
}
.navbar-nav .nav-item .nav-link.active i {
}
</style>
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

function isActiveGTK($routeName, $pattern) {
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

<li class="menu-title"><span>Akademik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.schools.guru-mapel.') ? ' active' : '' }}"
       href="{{ route('user.schools.guru-mapel.index', ['userId' => $userId]) }}">
        <i class="ri-book-2-line"></i>
        <span>Buku Admin Guru</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.subjects.') ? ' active' : '' }}"
       href="{{ route('user.subjects.index', ['userId' => $userId]) }}">
        <i class="ri-book-open-line"></i>
        <span>Mata Pelajaran</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.bank-soal.') || isActiveGTK($currentRoute, 'user.kisi-kisi-soal.') || isActiveGTK($currentRoute, 'user.paket-soal.') || isActiveGTK($currentRoute, 'user.soal.') ? ' active' : '' }}"
       href="#evaluasi_guru" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.bank-soal.') || isActiveGTK($currentRoute, 'user.kisi-kisi-soal.') || isActiveGTK($currentRoute, 'user.paket-soal.') || isActiveGTK($currentRoute, 'user.soal.') ? 'true' : 'false' }}"
       aria-controls="evaluasi_guru">
        <i class="ri-file-edit-line"></i>
        <span>Evaluasi</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.bank-soal.') || isActiveGTK($currentRoute, 'user.kisi-kisi-soal.') || isActiveGTK($currentRoute, 'user.paket-soal.') || isActiveGTK($currentRoute, 'user.soal.') ? ' show' : '' }}" id="evaluasi_guru">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.kisi-kisi-soal.') ? ' active' : '' }}" href="{{ route('user.kisi-kisi-soal.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Kisi-Kisi Soal</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.bank-soal.') ? ' active' : '' }}" href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Bank Soal</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.paket-soal.') ? ' active' : '' }}" href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Paket Soal</a></li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Peserta Didik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.grade-levels.') ? ' active' : '' }}"
       href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Data Kelas</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
       href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">
        <i class="ri-file-list-3-line"></i>
        <span>Rombongan Belajar</span>
    </a>
</li>

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveGTK'])

<li class="menu-title"><span>Data Nilai</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.schools.nilai.') ? ' active' : '' }}"
       href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
        <i class="ri-survey-line"></i>
        <span>Leger Nilai</span>
    </a>
</li>