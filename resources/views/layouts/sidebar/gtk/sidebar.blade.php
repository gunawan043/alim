<!-- GTK / Satuan Pendidikan Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

if (! function_exists('isActiveGTK')) {
function isActiveGTK($routeName, $pattern) {
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

{{-- ════════════════════════════════════════════════════════════���══
     SECTION: DATA GTK
     ═══════════════════════════════════════════════════════════════
--}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#menu_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="menu_gtk">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="menu_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk.indexguru' ? ' active' : '' }}"
                   href="{{ route('user.gtk.indexguru', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Guru</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk.indextendik' ? ' active' : '' }}"
                   href="{{ route('user.gtk.indextendik', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Tendik</a>
            </li>
            @if(canPermission('gtk-update'))
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk.massal' ? ' active' : '' }}"
                   href="{{ route('user.gtk.massal', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-edit-box-line me-1"></i>Manajemen Massal
                </a>
            </li>
            @endif
        </ul>
    </div>
</li>

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: PENGAJUAN GTK
     ═══════════════════════════════════════════════════════════════
--}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.gtk-requests.') ? ' active' : '' }}"
       href="#menu_gtk_requests" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.gtk-requests.') ? 'true' : 'false' }}"
       aria-controls="menu_gtk_requests">
        <i class="ri-file-add-line"></i>
        <span>Pengajuan GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.gtk-requests.') ? ' show' : '' }}" id="menu_gtk_requests">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk-requests.index' ? ' active' : '' }}"
                   href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Data Pengajuan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk-requests.create' ? ' active' : '' }}"
                   href="{{ route('user.gtk-requests.create', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Buat Pengajuan GTK</a>
            </li>
        </ul>
    </div>
</li>

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: TUGAS TAMBAHAN GTK
     ═══════════════════════════════════════════════════════════════
--}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.gtk-additional-tasks.') ? ' active' : '' }}"
       href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i>
        <span>Tugas Tambahan</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: REKRUTMEN GTK
     ═══════════════════════════════════════════════════════════════
--}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.recruitment.') ? ' active' : '' }}"
       href="{{ route('user.recruitment.index', ['userId' => $userId]) }}">
        <i class="ri-user-follow-line"></i>
        <span>Rekrutmen GTK</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: PENGAJUAN JABATAN GTK
     ═══════════════════════════════════════════════════════════════
--}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.gtk-position-proposals.') ? ' active' : '' }}"
       href="{{ route('user.gtk-position-proposals.index', ['userId' => $userId]) }}">
        <i class="ri-arrow-up-line"></i>
        <span>Pengajuan Jabatan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.gtk-positions.') ? ' active' : '' }}"
       href="{{ route('user.gtk-positions.index', ['userId' => $userId]) }}">
        <i class="ri-briefcase-line"></i>
        <span>Jabatan GTK</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: PESERTA DIDIK
     ═══════════════════════════════════════════════════════════════
--}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.grade-levels.') || isActiveGTK($currentRoute, 'user.study-groups.') || isActiveGTK($currentRoute, 'user.students.') || isActiveGTK($currentRoute, 'user.mutations-') ? ' active' : '' }}"
       href="#menu_peserta_didik" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.grade-levels.') || isActiveGTK($currentRoute, 'user.study-groups.') || isActiveGTK($currentRoute, 'user.students.') || isActiveGTK($currentRoute, 'user.mutations-') ? 'true' : 'false' }}"
       aria-controls="menu_peserta_didik">
        <i class="ri-team-line"></i>
        <span>Peserta Didik</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.grade-levels.') || isActiveGTK($currentRoute, 'user.study-groups.') || isActiveGTK($currentRoute, 'user.students.') || isActiveGTK($currentRoute, 'user.mutations-') ? ' show' : '' }}" id="menu_peserta_didik">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveGTK($currentRoute, 'user.grade-levels.') ? ' active' : '' }}"
                   href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Data Kelas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveGTK($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
                   href="{{ route('user.study-groups.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Pengaturan Rombel</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveGTK($currentRoute, 'user.mutations-') ? ' active' : '' }}"
                   href="#menu_mutasi_pd" data-bs-toggle="collapse" role="button"
                   style="font-size:0.85rem">Mutasi PD</a>
                <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.mutations-') ? ' show' : '' }}" id="menu_mutasi_pd">
                    <ul class="nav nav-sm flex-column ps-2">
                        <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.mutations-in.index' ? ' active' : '' }}" href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}" style="font-size:0.80rem">Mutasi Masuk</a></li>
                        <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.mutations-out.index' ? ' active' : '' }}" href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}" style="font-size:0.80rem">Mutasi Keluar</a></li>
                        <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.mutations-do.index' ? ' active' : '' }}" href="{{ route('user.mutations-do.index', ['userId' => $userId]) }}" style="font-size:0.80rem">Drop Out</a></li>
                        <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.mutations-lulus.index' ? ' active' : '' }}" href="{{ route('user.mutations-lulus.index', ['userId' => $userId]) }}" style="font-size:0.80rem">Lulus</a></li>
                        <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.student-move.') ? ' active' : '' }}" href="{{ route('user.student-move.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Pindahkan Santri</a></li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</li>

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: AKADEMIK
     ═══════════════════════════════════════════════════════════════
--}}
<li class="menu-title"><span>Akademik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.subjects.') ? ' active' : '' }}"
       href="{{ route('user.subjects.index', ['userId' => $userId]) }}">
        <i class="ri-book-open-line"></i>
        <span>Mata Pelajaran</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.teaching-assignments.') ? ' active' : '' }}"
       href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">
        <i class="ri-user-star-line"></i>
        <span>Penugasan Mengajar</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.other-teacher-tasks.') ? ' active' : '' }}"
       href="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-user-settings-line"></i>
        <span>Tugas Tambahan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link" href="#menu_sumatif" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="menu_sumatif">
        <i class="ri-file-edit-line"></i>
        <span>Pelaksanaan Sumatif</span>
    </a>
    <div class="collapse menu-dropdown" id="menu_sumatif">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.kisi-kisi-soal.') ? ' active' : '' }}" href="{{ route('user.kisi-kisi-soal.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Kisi-Kisi Soal</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.bank-soal.') ? ' active' : '' }}" href="{{ route('user.bank-soal.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Bank Soal</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.paket-soal.') ? ' active' : '' }}" href="{{ route('user.paket-soal.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Soal Sumatif</a></li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.schools.nilai.') ? ' active' : '' }}"
       href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
        <i class="ri-survey-line"></i>
        <span>Data Nilai</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.absensi.') ? ' active' : '' }}"
       href="#menu_absensi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.absensi.') ? 'true' : 'false' }}"
       aria-controls="menu_absensi">
        <i class="ri-contacts-book-line"></i>
        <span>Absensi</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.absensi.') ? ' show' : '' }}" id="menu_absensi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }}" href="{{ route('user.absensi-gtk.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Absensi GTK</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.absensi.') ? ' active' : '' }}" href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Absensi Peserta Didik</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.teacher-qr.waka-dashboard' ? ' active' : '' }}" href="{{ route('user.teacher-qr.waka-dashboard', ['userId' => $userId]) }}" style="font-size:0.85rem">Dashboard Absensi QR</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.teacher-qr.history' ? ' active' : '' }}" href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" style="font-size:0.85rem">Riwayat Absensi QR</a></li>
            @if(canPermission('teacher-attendance_manual'))
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.teacher-qr.manual' ? ' active' : '' }}" href="{{ route('user.teacher-qr.manual', ['userId' => $userId]) }}" style="font-size:0.85rem"><i class="ri-keyboard-line me-1"></i>Absen Manual</a></li>
            @endif
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link" href="#menu_prestasi" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="menu_prestasi">
        <i class="ri-trophy-line"></i>
        <span>Data Prestasi</span>
    </a>
    <div class="collapse menu-dropdown" id="menu_prestasi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ request('type') !== 'quran' && request('type') !== 'hadits' ? ' active' : '' }}"
                   href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'akademik']) }}"
                   style="font-size:0.85rem">Prestasi Akademik</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ request('type') === 'quran' ? ' active' : '' }}"
                   href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'quran']) }}"
                   style="font-size:0.85rem">Hafalan Qur'an</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ request('type') === 'hadits' ? ' active' : '' }}"
                   href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'hadits']) }}"
                   style="font-size:0.85rem">Hafalan Hadits</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.ekstrakurikuler.') || isActiveGTK($currentRoute, 'ekstrakurikuler.') ? ' active' : '' }}" href="{{ route('user.ekstrakurikuler.index', ['userId' => $userId]) }}">
        <i class="ri-basketball-line"></i>
        <span>Ekstrakurikuler</span>
    </a>
</li>

@include('layouts.sidebar.uks.sidebar', ['isActiveFn' => 'isActiveGTK'])

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: ADMINISTRASI
     ═══════════════════════════════════════════════════════════════
--}}
<li class="menu-title"><span>Administrasi</span></li>
<li class="nav-item">
    <a class="nav-link menu-link" href="#menu_surat_menyurat" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="menu_surat_menyurat">
        <i class="ri-mail-send-line"></i>
        <span>Surat Menyurat</span>
    </a>
    <div class="collapse menu-dropdown" id="menu_surat_menyurat">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.surat-keluar.') ? ' active' : '' }}" href="{{ route('user.surat-keluar.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Surat Keluar</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.surat-masuk.') ? ' active' : '' }}" href="{{ route('user.surat-masuk.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Surat Masuk</a></li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.dokumen-iso.') ? ' active' : '' }}"
       href="{{ route('user.dokumen-iso.index', ['userId' => $userId]) }}">
        <i class="ri-file-text-line"></i>
        <span>Dokumen ISO</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: PENDUKUNG
     ═══════════════════════════════════════════════════════════════
--}}
<li class="menu-title"><span>Pendukung</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.kaldik.') ? ' active' : '' }}"
       href="#menu_agenda_kegiatan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.kaldik.') ? 'true' : 'false' }}"
       aria-controls="menu_agenda_kegiatan">
        <i class="ri-task-line"></i>
        <span>Agenda Kegiatan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.kaldik.') ? ' show' : '' }}" id="menu_agenda_kegiatan">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kaldik.index' && !request('category') ? ' active' : '' }}"
                   href="{{ route('user.kaldik.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Semua</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kaldik.index' && request('category') === 'kaldik' ? ' active' : '' }}"
                   href="{{ route('user.kaldik.index', ['userId' => $userId, 'category' => 'kaldik']) }}"
                   style="font-size:0.85rem">Kaldik</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.kaldik.index' && request('category') === 'agenda' ? ' active' : '' }}"
                   href="{{ route('user.kaldik.index', ['userId' => $userId, 'category' => 'agenda']) }}"
                   style="font-size:0.85rem">Agenda Kegiatan</a>
            </li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.alumni.') ? ' active' : '' }}"
       href="{{ route('user.alumni.index', ['userId' => $userId]) }}">
        <i class="ri-group-2-line"></i>
        <span>Data Alumni</span>
    </a>
</li>
