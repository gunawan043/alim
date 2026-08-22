<!-- Unified GTK Sidebar (Guru + Jabatan) -->
{{--
  Unified sidebar untuk semua role GTK/Guru.
  Section muncul/hilang dinamis berdasarkan assignment aktif:
    - Waka/Structural  → structural_assignment aktif
    - Wali Kelas       → homeroom_assignment aktif
    - Koordinator      → coordinator_assignment aktif
--}}
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

// Check workspace assignments
$ws = app(\App\Services\WorkspaceActivationService::class)->forUser($currentUser);

// Study groups for Waka/koordinator view
$userWorkUnits = \App\Models\GtkWorkUnit::with('workUnit.school')
    ->where('user_id', $currentUser->id)
    ->whereHas('workUnit', fn($q) => $q->where('is_active', true))
    ->get();
$primaryWorkUnit = $userWorkUnits->where('is_primary', true)->first() ?? $userWorkUnits->first();
$sekolah = $primaryWorkUnit?->workUnit?->school;

$sidebarStudyGroups = $sekolah
    ? \App\Models\StudyGroup::withoutGlobalScope('school_context')
        ->where('school_id', $sekolah->id)
        ->where('is_active', true)
        ->whereHas('academicYear', fn($q) => $q->where('semester', \App\Models\AcademicYear::where('is_active',true)->value('semester')))
        ->with(['gradeLevel', 'homeroomTeacher'])
        ->orderBy('name')
        ->get()
    : collect();
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

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: WAKA / STRUKTURAL (hanya jika ada assignment aktif)
     ═══════════════════════════════════════════════════════════════
--}}
@if($ws['waka_kurikulum'])
<li class="menu-title"><span>Wakil Kepala Sekolah</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#waka_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="waka_gtk">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="waka_gtk">
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
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.gtk-requests.') ? ' active' : '' }}"
       href="#waka_gtk_request" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.gtk-requests.') ? 'true' : 'false' }}"
       aria-controls="waka_gtk_request">
        <i class="ri-file-add-line"></i>
        <span>Pengajuan GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.gtk-requests.') ? ' show' : '' }}" id="waka_gtk_request">
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

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.grade-levels.') || isActiveGTK($currentRoute, 'user.study-groups.') || isActiveGTK($currentRoute, 'user.students.') || isActiveGTK($currentRoute, 'user.mutations-') ? ' active' : '' }}"
       href="#waka_peserta" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.grade-levels.') ? 'true' : 'false' }}"
       aria-controls="waka_peserta">
        <i class="ri-team-line"></i>
        <span>Peserta Didik</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.grade-levels.') || isActiveGTK($currentRoute, 'user.study-groups.') || isActiveGTK($currentRoute, 'user.students.') || isActiveGTK($currentRoute, 'user.mutations-') ? ' show' : '' }}" id="waka_peserta">
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
                <a class="nav-link" href="#waka_rombel" data-bs-toggle="collapse" role="button"
                   style="font-size:0.85rem">Rombel</a>
                <div class="collapse" id="waka_rombel">
                    <ul class="nav nav-sm flex-column ps-2">
                        @forelse($sidebarStudyGroups as $sg)
                        <li class="nav-item">
                            <a class="nav-link{{ $currentRoute === 'user.students.index' && request('study_group_id') == $sg->id ? ' active' : '' }}"
                               href="{{ route('user.students.index', ['userId' => $userId, 'study_group_id' => $sg->id]) }}"
                               style="font-size:0.80rem">
                                {{ $sg->full_name }}
                                @if($sg->homeroomTeacher)
                                <span class="text-muted ms-1">({{ $sg->homeroomTeacher->name }})</span>
                                @endif
                            </a>
                        </li>
                        @empty
                        <li class="nav-item"><span class="nav-link text-muted px-3" style="font-size:0.80rem">Belum ada rombel</span></li>
                        @endforelse
                    </ul>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#waka_mutasi" data-bs-toggle="collapse" role="button"
                   style="font-size:0.85rem">Mutasi PD</a>
                <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.mutations-') ? ' show' : '' }}" id="waka_mutasi">
                    <ul class="nav nav-sm flex-column ps-2">
                        <li class="nav-item">
                            <a class="nav-link{{ $currentRoute === 'user.mutations-in.index' ? ' active' : '' }}"
                               href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}"
                               style="font-size:0.80rem">Mutasi Masuk</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link{{ $currentRoute === 'user.mutations-out.index' ? ' active' : '' }}"
                               href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}"
                               style="font-size:0.80rem">Mutasi Keluar</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link{{ $currentRoute === 'user.mutations-do.index' ? ' active' : '' }}"
                               href="{{ route('user.mutations-do.index', ['userId' => $userId]) }}"
                               style="font-size:0.80rem">Drop Out</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link{{ $currentRoute === 'user.mutations-lulus.index' ? ' active' : '' }}"
                               href="{{ route('user.mutations-lulus.index', ['userId' => $userId]) }}"
                               style="font-size:0.80rem">Lulus</a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.violation-points.') ? ' active' : '' }}"
       href="#waka_pelanggaran" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveGTK($currentRoute, 'user.violation-points.') ? 'true' : 'false' }}"
       aria-controls="waka_pelanggaran">
        <i class="ri-spam-line"></i>
        <span>Data Pelanggaran</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveGTK($currentRoute, 'user.violation-points.') ? ' show' : '' }}" id="waka_pelanggaran">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.violation-points.index' ? ' active' : '' }}"
                   href="{{ route('user.violation-points.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Poin Pelanggaran</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.violation-points.dashboard' ? ' active' : '' }}"
                   href="{{ route('user.violation-points.dashboard', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Dashboard Pelanggaran</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.violation-points.recap' ? ' active' : '' }}"
                   href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Rekap Poin Siswa</a>
            </li>
        </ul>
    </div>
</li>

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveGTK'])

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
    <a class="nav-link menu-link" href="#waka_evaluasi" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="waka_evaluasi">
        <i class="ri-file-edit-line"></i>
        <span>Pelaksanaan Sumatif</span>
    </a>
    <div class="collapse menu-dropdown" id="waka_evaluasi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.kisi-kisi-soal' ? ' active' : '' }}" href="{{ route('waka.kisi-kisi-soal') }}" style="font-size:0.85rem">Kisi-Kisi Soal</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.bank-soal' ? ' active' : '' }}" href="{{ route('waka.bank-soal') }}" style="font-size:0.85rem">Bank Soal</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.soal-sumatif' ? ' active' : '' }}" href="{{ route('waka.soal-sumatif') }}" style="font-size:0.85rem">Soal Sumatif</a></li>
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
    <a class="nav-link menu-link" href="#waka_absensi" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="waka_absensi">
        <i class="ri-contacts-book-line"></i>
        <span>Absensi</span>
    </a>
    <div class="collapse menu-dropdown" id="waka_absensi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.absensi-gtk' ? ' active' : '' }}" href="{{ route('waka.absensi-gtk') }}" style="font-size:0.85rem">Absensi GTK</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.absensi.') ? ' active' : '' }}" href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Absensi Peserta Didik</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.teacher-qr') ? ' active' : '' }}" href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}" style="font-size:0.85rem">Scan QR Guru</a></li>
            @if(canPermission('teacher-attendance_view'))
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.teacher-qr.waka-dashboard' ? ' active' : '' }}" href="{{ route('user.teacher-qr.waka-dashboard', ['userId' => $userId]) }}" style="font-size:0.85rem">Dashboard Absensi QR</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveGTK($currentRoute, 'user.teacher-qr.history') ? ' active' : '' }}" href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}" style="font-size:0.85rem">Riwayat Absensi QR</a></li>
            @if(canPermission('teacher-attendance_manual'))
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.teacher-qr.manual' ? ' active' : '' }}" href="{{ route('user.teacher-qr.manual', ['userId' => $userId]) }}" style="font-size:0.85rem"><i class="ri-keyboard-line me-1"></i>Absen Manual</a></li>
            @endif
            @endif
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link" href="#waka_prestasi" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="waka_prestasi">
        <i class="ri-trophy-line"></i>
        <span>Data Prestasi</span>
    </a>
    <div class="collapse menu-dropdown" id="waka_prestasi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.prestasi-akademik' ? ' active' : '' }}" href="{{ route('waka.prestasi-akademik') }}" style="font-size:0.85rem">Prestasi Akademik</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.hafalan-quran' ? ' active' : '' }}" href="{{ route('waka.hafalan-quran') }}" style="font-size:0.85rem">Hafalan Qur'an</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.hafalan-hadits' ? ' active' : '' }}" href="{{ route('waka.hafalan-hadits') }}" style="font-size:0.85rem">Hafalan Hadits</a></li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'waka.ekstrakurikuler.index' ? ' active' : '' }}" href="{{ route('waka.ekstrakurikuler.index') }}">
        <i class="ri-basketball-line"></i>
        <span>Ekstrakurikuler</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'waka.supervisi.index' ? ' active' : '' }}" href="{{ route('waka.supervisi.index') }}">
        <i class="ri-file-excel-2-line"></i>
        <span>Supervisi</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'waka.pekan-efektif.index' ? ' active' : '' }}" href="{{ route('waka.pekan-efektif.index') }}">
        <i class="ri-bank-2-line"></i>
        <span>Pekan Efektif</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: BASE GTK (semua guru)
     ═══════════════════════════════════════════════════════════════
--}}
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

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: WALI KELAS (hanya jika assignment aktif)
     ═══════════════════════════════════════════════════════════════
--}}
@if($ws['wali_kelas'])
<li class="menu-title"><span>Wali Kelas</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.absensi.harian.') ? ' active' : '' }}"
       href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">
        <i class="ri-contacts-book-line"></i>
        <span>Presensi Harian</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.schools.nilai.') ? ' active' : '' }}"
       href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
        <i class="ri-survey-line"></i>
        <span>Nilai Kelas</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.uks.student-counseling.') ? ' active' : '' }}"
       href="{{ route('user.uks.student-counseling.index', ['userId' => $userId]) }}">
        <i class="ri-user-follow-line"></i>
        <span>Bimbingan Siswa</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════
     SECTION: KOORDINATOR RUMPUN (hanya jika assignment aktif)
     ═══════════════════════════════════════════════════════════════
--}}
@if($ws['koordinator_rumpun'])
<li class="menu-title"><span>Koordinator Rumpun</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveGTK($currentRoute, 'user.jadwal-kbm.') ? ' active' : '' }}"
       href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-todo-line"></i>
        <span>Jadwal KBM</span>
    </a>
</li>
@endif
