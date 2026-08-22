<!-- Wakil Kepala Sekolah Sidebar -->
{{-- Sama dengan Admin TU, tapi dengan menu Supervisi --}}
@includeWhen(false, 'layouts.sidebar.admin-tu-tu')

<style>
.navbar-nav .nav-item .nav-link.active,
.navbar-nav .nav-item .nav-link.active i {
    color: #fff !important;
    background-color: rgba(255,255,255,0.1) !important;
    border-radius: 0.25rem;
}
.navbar-nav .nav-item .nav-link.active i {
    color: #fff !important;
}
</style>
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

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

if (!function_exists('isActiveWaka')) {
    function isActiveWaka($routeName, $pattern) {
        if (!$routeName) return false;
        return str_starts_with($routeName, $pattern);
    }
}

// Cek akses sarpras — Unit Rumah Tangga dapat dashboard lengkap, lainnya dashboard sederhana
$userSarprasAccess = \App\Models\GtkWorkUnit::where('user_id', $currentUser->id)
    ->whereHas('workUnit', fn($q) => $q->where('code', 'PAH-ADM-003'))
    ->exists()
    || $currentUser->hasPermissionTo('sarpras_all_access')
    || $currentUser->hasPermissionTo('inventory_view');

$sarprasDashboardRoute = route('sarpras.user.dashboard', ['userId' => $userId]);
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
    @if($sekolah)
    <a class="nav-link menu-link{{ $currentRoute === 'user.schools.show' ? ' active' : '' }}"
       href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $sekolah->id]) }}">
        <i class="ri-government-line"></i>
        <span>Satuan Pendidikan</span>
    </a>
    @else
    <a class="nav-link menu-link{{ $currentRoute === 'user.schools.index' ? ' active' : '' }}"
       href="{{ route('user.schools.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i>
        <span>Satuan Pendidikan</span>
    </a>
    @endif
</li>

<li class="menu-title"><span>GTK & Peserta Didik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveWaka($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="gtk">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveWaka($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="gtk">
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
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.gtk-requests.') ? ' active' : '' }}"
       href="#request_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveWaka($currentRoute, 'user.gtk-requests.') ? 'true' : 'false' }}"
       aria-controls="request_gtk">
        <i class="ri-file-add-line"></i>
        <span>Pengajuan GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveWaka($currentRoute, 'user.gtk-requests.') ? ' show' : '' }}" id="request_gtk">
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
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.grade-levels.') || isActiveWaka($currentRoute, 'user.study-groups.') || isActiveWaka($currentRoute, 'user.students.') || isActiveWaka($currentRoute, 'user.mutations-') ? ' active' : '' }}"
       href="#peserta_didik" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveWaka($currentRoute, 'user.grade-levels.') || isActiveWaka($currentRoute, 'user.study-groups.') || isActiveWaka($currentRoute, 'user.students.') || isActiveWaka($currentRoute, 'user.mutations-') ? 'true' : 'false' }}"
       aria-controls="peserta_didik">
        <i class="ri-team-line"></i>
        <span>Peserta Didik</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveWaka($currentRoute, 'user.grade-levels.') || isActiveWaka($currentRoute, 'user.study-groups.') || isActiveWaka($currentRoute, 'user.students.') || isActiveWaka($currentRoute, 'user.mutations-') ? ' show' : '' }}" id="peserta_didik">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveWaka($currentRoute, 'user.grade-levels.') ? ' active' : '' }}"
                   href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Data Kelas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveWaka($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
                   href="{{ route('user.study-groups.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Pengaturan Rombel</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#rombel" data-bs-toggle="collapse" role="button"
                   style="font-size:0.85rem">Rombel</a>
                <div class="collapse" id="rombel">
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
                <a class="nav-link" href="#mutasi_pd" data-bs-toggle="collapse" role="button"
                   style="font-size:0.85rem">Mutasi PD</a>
                <div class="collapse menu-dropdown{{ isActiveWaka($currentRoute, 'user.mutations-') ? ' show' : '' }}" id="mutasi_pd">
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
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.violation-points.') ? ' active' : '' }}"
       href="#pelanggaran" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveWaka($currentRoute, 'user.violation-points.') ? 'true' : 'false' }}"
       aria-controls="pelanggaran">
        <i class="ri-spam-line"></i>
        <span>Data Pelanggaran</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveWaka($currentRoute, 'user.violation-points.') ? ' show' : '' }}" id="pelanggaran">
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

@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveWaka'])

<li class="menu-title"><span>Akademik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.subjects.') ? ' active' : '' }}"
       href="{{ route('user.subjects.index', ['userId' => $userId]) }}">
        <i class="ri-book-open-line"></i>
        <span>Mata Pelajaran</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.teaching-assignments.') ? ' active' : '' }}"
       href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">
        <i class="ri-user-star-line"></i>
        <span>Penugasan Mengajar</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.other-teacher-tasks.') ? ' active' : '' }}"
       href="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-user-settings-line"></i>
        <span>Tugas Tambahan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link" href="#sumatif" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="sumatif">
        <i class="ri-file-edit-line"></i>
        <span>Pelaksanaan Sumatif</span>
    </a>
    <div class="collapse menu-dropdown" id="sumatif">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.kisi-kisi-soal' ? ' active' : '' }}" href="{{ route('waka.kisi-kisi-soal') }}" style="font-size:0.85rem">Kisi-Kisi Soal</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.bank-soal' ? ' active' : '' }}" href="{{ route('waka.bank-soal') }}" style="font-size:0.85rem">Bank Soal</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.soal-sumatif' ? ' active' : '' }}" href="{{ route('waka.soal-sumatif') }}" style="font-size:0.85rem">Soal Sumatif</a></li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.schools.nilai.') ? ' active' : '' }}"
       href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
        <i class="ri-survey-line"></i>
        <span>Data Nilai</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.absensi.') ? ' active' : '' }}"
       href="#absensi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveWaka($currentRoute, 'user.absensi.') ? 'true' : 'false' }}"
       aria-controls="absensi">
        <i class="ri-contacts-book-line"></i>
        <span>Absensi</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveWaka($currentRoute, 'user.absensi.') ? ' show' : '' }}" id="absensi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.absensi-gtk' ? ' active' : '' }}" href="{{ route('waka.absensi-gtk') }}" style="font-size:0.85rem">Absensi GTK</a></li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveWaka($currentRoute, 'user.absensi.') ? ' active' : '' }}"
                   href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Absensi Peserta Didik</a>
            </li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.teacher-qr.waka-dashboard' ? ' active' : '' }}" href="{{ route('user.teacher-qr.waka-dashboard', ['userId' => $userId]) }}" style="font-size:0.85rem">Dashboard Absensi QR</a></li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link" href="#prestasi" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="prestasi">
        <i class="ri-trophy-line"></i>
        <span>Data Prestasi</span>
    </a>
    <div class="collapse menu-dropdown" id="prestasi">
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

{{-- Menu Supervisi hanya untuk Waka --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'waka.supervisi.index' ? ' active' : '' }}" href="{{ route('waka.supervisi.index') }}">
        <i class="ri-file-excel-2-line"></i>
        <span>Supervisi</span>
    </a>
</li>

{{-- Menu Pekan Efektif untuk Waka --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'waka.pekan-efektif.index' ? ' active' : '' }}" href="{{ route('waka.pekan-efektif.index') }}">
        <i class="ri-bank-2-line"></i>
        <span>Pekan Efektif</span>
    </a>
</li>

<li class="menu-title"><span>Administrasi</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.institution-decrees.') ? ' active' : '' }}"
       href="#jadwal_kbm" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveWaka($currentRoute, 'user.institution-decrees.') ? 'true' : 'false' }}"
       aria-controls="jadwal_kbm">
        <i class="ri-git-repository-line"></i>
        <span>Jadwal KBM</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveWaka($currentRoute, 'user.institution-decrees.') ? ' show' : '' }}" id="jadwal_kbm">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.institution-decrees.index' ? ' active' : '' }}"
                   href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">SK Pembagian Tugas</a>
            </li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'user.jadwal-kbm.index' ? ' active' : '' }}" href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Jadwal Pelajaran</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.jam-mengajar' ? ' active' : '' }}" href="{{ route('waka.jam-mengajar') }}" style="font-size:0.85rem">Jam Mengajar</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.rekap-pergantian-jam' ? ' active' : '' }}" href="{{ route('waka.rekap-pergantian-jam') }}" style="font-size:0.85rem">Rekap Pergantian Jam</a></li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link" href="#surat_menyurat" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="surat_menyurat">
        <i class="ri-mail-send-line"></i>
        <span>Surat Menyurat</span>
    </a>
    <div class="collapse menu-dropdown" id="surat_menyurat">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.surat-keluar.index' ? ' active' : '' }}" href="{{ route('waka.surat-keluar.index') }}" style="font-size:0.85rem">Surat Keluar</a></li>
            <li class="nav-item"><a class="nav-link{{ $currentRoute === 'waka.surat-masuk.index' ? ' active' : '' }}" href="{{ route('waka.surat-masuk.index') }}" style="font-size:0.85rem">Surat Masuk</a></li>
        </ul>
    </div>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.dokumen-iso.') ? ' active' : '' }}"
       href="{{ route('user.dokumen-iso.index', ['userId' => $userId]) }}">
        <i class="ri-file-text-line"></i>
        <span>Dokumen ISO</span>
    </a>
</li>

<li class="menu-title"><span>Pendukung</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.kaldik.') ? ' active' : '' }}"
       href="#agenda_kegiatan" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveWaka($currentRoute, 'user.kaldik.') ? 'true' : 'false' }}"
       aria-controls="agenda_kegiatan">
        <i class="ri-task-line"></i>
        <span>Agenda Kegiatan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveWaka($currentRoute, 'user.kaldik.') ? ' show' : '' }}" id="agenda_kegiatan">
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
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'sarpras.') || isActiveWaka($currentRoute, 'user.sarpras.') || isActiveWaka($currentRoute, 'sarpras.user.') ? ' active' : '' }}"
       href="{{ $sarprasDashboardRoute }}">
        <i class="ri-community-line"></i>
        <span>Sarana Prasarana</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveWaka($currentRoute, 'user.alumni.') ? ' active' : '' }}"
       href="{{ route('user.alumni.index', ['userId' => $userId]) }}">
        <i class="ri-group-2-line"></i>
        <span>Data Alumni</span>
    </a>
</li>