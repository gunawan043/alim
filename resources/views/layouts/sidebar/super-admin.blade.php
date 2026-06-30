<!-- Super Admin Sidebar -->
<style>
.navbar-nav .nav-item .nav-link.active,
.navbar-nav .nav-item .nav-link.active i {
    background-color: rgba(255,255,255,0.1) !important;
    border-radius: 0.25rem;
}
.navbar-nav .nav-item .nav-link.active i {
}
/* Super Admin context bars */
.sa-context-section {
    margin-bottom: 4px;
    padding: 0 4px;
}
</style>
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$userId = auth()->id();
$routeParams = request()->route() ? request()->route()->parameters() : [];
$asramaUuid = $routeParams['asramaUuid'] ?? null;
$hasAsramaContext = !empty($asramaUuid);

// Auto-select first active asrama on dormitory-master index so sub-menu links work immediately
if (!$hasAsramaContext && in_array($currentRoute, ['user.dormitory-master.index', 'user.asrama.index'])) {
    $firstAsrama = \App\Models\Dormitory::where('is_active', true)->first();
    if ($firstAsrama) {
        $asramaUuid = $firstAsrama->id;
        $hasAsramaContext = true;
    }
}

// Auto-select first active sekolah so Operasi Sekolah sub-menu links work immediately
$sekolahUuid = $routeParams['sekolahUuid'] ?? $routeParams['schoolId'] ?? session('sa_school_id') ?? null;
$hasSekolahContext = !empty($sekolahUuid);
if (!$hasSekolahContext && in_array($currentRoute, ['user.schools-global.index', 'user.schools.index'])) {
    $firstSekolah = \App\Models\School::where('is_active', true)->first();
    if ($firstSekolah) {
        $sekolahUuid = $firstSekolah->id;
        $hasSekolahContext = true;
    }
}

$asramaFallback = route('user.dormitory-master.index', ['userId' => $userId]);

function isActiveSA($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}

function sekolahMenuActive($currentRoute, $hasSekolahContext) {
    $sekolahPatterns = [
        'user.students.',
        'user.absensi.harian.',
        'user.schools.nilai.',
        'user.study-groups.',
        'user.student-achievement.',
        'user.violation-points.',
    ];
    if ($hasSekolahContext) return true;
    foreach ($sekolahPatterns as $p) {
        if (str_starts_with($currentRoute, $p)) return true;
    }
    return false;
}

function saMenuActive($patterns = []) {
    global $currentRoute;
    foreach ($patterns as $p) {
        if (isActiveSA($currentRoute, $p)) return true;
    }
    return false;
}
@endphp

{{-- ============================================================
     CONTEXT SWITCHER BARS
     Always visible for Super Admin; shows active scope state.
     ============================================================ --}}

{{-- School Context Switcher (always shown for Super Admin) --}}
<div class="sa-context-section">
    @include('components.school-switcher', [
        'schools' => $schools ?? collect(),
        'saSchoolId' => $saSchoolId ?? null,
        'saSchoolName' => $saSchoolName ?? null,
        'saSchoolScoped' => $saSchoolScoped ?? false,
    ])
</div>

{{-- Asrama Context Bar (shown only when asramaUuid is in URL) --}}
@if($asramaContext)
    <div class="sa-context-section">
        @include('components.asrama-context-bar', [
            'asramaContext' => $asramaContext,
            'currentAsramaModule' => $currentAsramaModule ?? null,
        ])
    </div>
@endif

{{-- ============================================================
     1. UMUM
     ============================================================ --}}
<li class="menu-title"><span>Umum</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i>
        <span>Dashboard</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.todos.index' ? ' active' : '' }}"
       href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i>
        <span>Todo List</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.notifications.index' ? ' active' : '' }}"
       href="{{ route('user.notifications.index', ['userId' => $userId]) }}">
        <i class="bx bx-bell"></i>
        <span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.profile.my' ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i>
        <span>Profile</span>
    </a>
</li>

{{-- ============================================================
     2. PENGELOLAAN PONDOK
     ============================================================ --}}
<li class="menu-title"><span>Pengelolaan Pondok</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.schools-global.') ? ' active' : '' }}"
       href="{{ route('user.schools-global.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i>
        <span>Daftar Sekolah</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.dormitory-master.') ? ' active' : '' }}"
       href="{{ route('user.dormitory-master.index', ['userId' => $userId]) }}">
        <i class="ri-hotel-line"></i>
        <span>Daftar Asrama</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.asrama.') || $hasAsramaContext ? ' active' : '' }}"
       href="#manajemen_asrama" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.asrama.') || $hasAsramaContext ? 'true' : 'false' }}"
       aria-controls="manajemen_asrama">
        <i class="ri-hotel-fill"></i>
        <span>Operasi Asrama</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.asrama.') || $hasAsramaContext ? ' show' : '' }}"
         id="manajemen_asrama">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.residents.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-user-follow-line me-1"></i> Penghuni
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.attendance.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-calendar-check-line me-1"></i> Absensi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.permits.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-pass-valid-line me-1"></i> Perizinan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.violations.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-error-warning-line me-1"></i> Pelanggaran
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.visits.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-footprint-line me-1"></i> Kunjungan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.room-moves.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-arrow-left-right-line me-1"></i> Mutasi Kamar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.wings.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-stack-line me-1"></i> Gedung
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.rooms.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-door-open-line me-1"></i> Kamar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.inventories.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-archive-line me-1"></i> Inventaris
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.posts.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-megaphone-line me-1"></i> Informasi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.broadcasts.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.broadcasts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-notification-3-line me-1"></i> Broadcast
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.activities.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.activities.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-file-list-3-line me-1"></i> Log Kegiatan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.asrama.templates.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.templates.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-git-branch-line me-1"></i> Template Kegiatan
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ============================================================
     2B. OPERASI SEKOLAH
     ============================================================ --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ sekolahMenuActive($currentRoute, $hasSekolahContext) ? ' active' : '' }}"
       href="#manajemen_sekolah" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ sekolahMenuActive($currentRoute, $hasSekolahContext) ? 'true' : 'false' }}"
       aria-controls="manajemen_sekolah">
        <i class="ri-school-line"></i>
        <span>Operasi Sekolah</span>
        <span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ sekolahMenuActive($currentRoute, $hasSekolahContext) ? ' show' : '' }}"
         id="manajemen_sekolah">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.students.') || ($currentRoute === 'user.students.index' && request('sekolahUuid')) ? ' active' : '' }}"
                   href="{{ isset($sekolahUuid) ? route('user.students.index', ['userId' => $userId, 'sekolahUuid' => $sekolahUuid]) : route('user.schools-global.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-user-heart-line me-1"></i> Data Santri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.absensi.harian.') ? ' active' : '' }}"
                   href="{{ isset($sekolahUuid) ? route('user.absensi.harian.index', ['userId' => $userId, 'sekolahUuid' => $sekolahUuid]) : route('user.schools-global.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-calendar-check-line me-1"></i> Absensi Santri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.schools.nilai.') ? ' active' : '' }}"
                   href="{{ isset($sekolahUuid) ? route('user.schools.nilai.index', ['userId' => $userId]) : route('user.schools-global.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-survey-line me-1"></i> Nilai Santri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
                   href="{{ isset($sekolahUuid) ? route('user.study-groups.index', ['userId' => $userId, 'sekolahUuid' => $sekolahUuid]) : route('user.schools-global.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-stack-line me-1"></i> Kelas & Rombel
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.schools-global.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-time-line me-1"></i> Jadwal Pelajaran
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.schools-global.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-basketball-line me-1"></i> Ekstrakurikuler
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.student-achievement.') ? ' active' : '' }}"
                   href="{{ route('user.student-achievement.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-trophy-line me-1"></i> Prestasi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.violation-points.') ? ' active' : '' }}"
                   href="{{ route('user.violation-points.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-spam-line me-1"></i> Pelanggaran
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ============================================================
     3. DATA GTK
     ============================================================ --}}
<li class="menu-title"><span>Data GTK</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.gtk.index' ? ' active' : '' }}"
       href="{{ route('user.gtk.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Semua GTK</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.gtk-requests.') ? ' active' : '' }}"
       href="#gtk_requests" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.gtk-requests.') ? 'true' : 'false' }}"
       aria-controls="gtk_requests">
        <i class="ri-git-pull-request-line"></i>
        <span>GTK Requests</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.gtk-requests.') ? ' show' : '' }}" id="gtk_requests">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk-requests.index' ? ' active' : '' }}"
                   href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Request GTK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.gtk-requests.create', ['userId' => $userId]) }}?type=procurement"
                   style="font-size:0.85rem">Pengadaan GTK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.gtk-requests.create', ['userId' => $userId]) }}?type=trial"
                   style="font-size:0.85rem">Pengangkatan Percobaan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.gtk-requests.create', ['userId' => $userId]) }}?type=status_increase"
                   style="font-size:0.85rem">Kenaikan Status GTK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.approvals.index' ? ' active' : '' }}"
                   href="{{ route('user.approvals.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Approval</a>
            </li>
        </ul>
    </div>
</li>

{{-- ============================================================
     PENSION / PENSIUN
     ============================================================ --}}
@php
$sa_pension_settings = \App\Models\PensionSetting::allSettings();
$sa_pension_months = (int) ($sa_pension_settings['notification_months'] ?? 6);
$sa_pension_bup = (int) ($sa_pension_settings['bup_age'] ?? 58);
$sa_pension_approaching = DB::table('users')
    ->join('gtk_profiles', 'users.id', '=', 'gtk_profiles.user_id')
    ->leftJoin('gtk_pensions', function($join) {
        $join->on('users.id', '=', 'gtk_pensions.user_id')
             ->whereIn('gtk_pensions.pension_status', ['completed', 'cancelled']);
    })
    ->where('users.is_active', true)
    ->whereNotNull('gtk_profiles.tanggal_lahir')
    ->whereNull('gtk_pensions.id')
    ->whereRaw("DATE_ADD(gtk_profiles.tanggal_lahir, INTERVAL ? YEAR) <= DATE_ADD(NOW(), INTERVAL ? MONTH)",
        [$sa_pension_bup, $sa_pension_months])
    ->count();
@endphp
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.pension.index' || $currentRoute === 'user.pension.settings' ? ' active' : '' }}"
       href="{{ route('user.pension.index', ['userId' => $userId]) }}">
        <i class="ri-umbrella-line"></i>
        <span>Pensiun</span>
        @if($sa_pension_approaching > 0)
        <span class="badge bg-danger rounded-pill" style="font-size:0.65rem; padding: 0.2em 0.5em;">{{ $sa_pension_approaching }}</span>
        @endif
    </a>
</li>

{{-- ============================================================
     4. DATA SANTRI
     ============================================================ --}}
<li class="menu-title"><span>Data Santri</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.students.') ? ' active' : '' }}"
       href="{{ route('user.students.index', ['userId' => $userId]) }}">
        <i class="ri-user-heart-line"></i>
        <span>Santri</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.mutations-') ? ' active' : '' }}"
       href="#mutasi_santri" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.mutations-') ? 'true' : 'false' }}"
       aria-controls="mutasi_santri">
        <i class="ri-exchange-line"></i>
        <span>Mutasi Santri</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.mutations-') ? ' show' : '' }}" id="mutasi_santri">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.mutations-in.index' ? ' active' : '' }}"
                   href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Mutasi Masuk</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.mutations-out.index' ? ' active' : '' }}"
                   href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Mutasi Keluar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.mutations-do.index' ? ' active' : '' }}"
                   href="{{ route('user.mutations-do.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Drop Out</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.mutations-lulus.index' ? ' active' : '' }}"
                   href="{{ route('user.mutations-lulus.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Lulus</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.student-move.') ? ' active' : '' }}"
       href="{{ route('user.student-move.index', ['userId' => $userId]) }}">
        <i class="ri-arrow-left-right-line"></i>
        <span>Pindahkan Santri</span>
    </a>
</li>

{{-- ============================================================
     5. AKADEMIK
     ============================================================ --}}
<li class="menu-title"><span>Akademik</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.academic-years.') ? ' active' : '' }}"
       href="{{ route('user.academic-years.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-event-line"></i>
        <span>Tahun Ajaran</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.grade-levels.') || isActiveSA($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
       href="#akademik_kelas" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.grade-levels.') || isActiveSA($currentRoute, 'user.study-groups.') ? 'true' : 'false' }}"
       aria-controls="akademik_kelas">
        <i class="ri-stack-line"></i>
        <span>Kelas & Rombel</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.grade-levels.') || isActiveSA($currentRoute, 'user.study-groups.') ? ' show' : '' }}" id="akademik_kelas">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.grade-levels.') ? ' active' : '' }}"
                   href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Tingkat Kelas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
                   href="{{ route('user.study-groups.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Rombongan Belajar</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.subjects.') ? ' active' : '' }}"
       href="{{ route('user.subjects.index', ['userId' => $userId]) }}">
        <i class="ri-book-open-line"></i>
        <span>Mata Pelajaran</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.teaching-assignments.') ? ' active' : '' }}"
       href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">
        <i class="ri-user-star-line"></i>
        <span>Penugasan Mengajar</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.other-teacher-tasks.') ? ' active' : '' }}"
       href="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-user-settings-line"></i>
        <span>Tugas Tambahan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.schools.nilai.') ? ' active' : '' }}"
       href="{{ route('user.schools.nilai.index', ['userId' => $userId]) }}">
        <i class="ri-survey-line"></i>
        <span>Data Nilai</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link"
       href="#sumatif" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="sumatif">
        <i class="ri-file-edit-line"></i>
        <span>Pelaksanaan Sumatif</span>
    </a>
    <div class="collapse menu-dropdown" id="sumatif">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'user.kisi-kisi-soal.') ? ' active' : '' }}" href="{{ route('user.kisi-kisi-soal.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Kisi-Kisi Soal</a></li>
            <li class="nav-item"><a class="nav-link" href="#" style="font-size:0.85rem"><span class="badge bg-secondary me-1">Soon</span>Soal Sumatif</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link"
       href="#absensi" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="absensi">
        <i class="ri-contacts-book-line"></i>
        <span>Absensi</span>
    </a>
    <div class="collapse menu-dropdown" id="absensi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'user.absensi-gtk.') ? ' active' : '' }}" href="{{ route('user.absensi-gtk.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Absensi GTK</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'user.absensi.harian.') ? ' active' : '' }}" href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Absensi Santri</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link"
       href="#ekskul" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="ekskul">
        <i class="ri-basketball-line"></i>
        <span>Ekstrakurikuler</span>
    </a>
    <div class="collapse menu-dropdown" id="ekskul">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'waka.ekstrakurikuler.') ? ' active' : '' }}" href="{{ route('waka.ekstrakurikuler.index') }}" style="font-size:0.85rem">Daftar Ekskul</a></li>
            <li class="nav-item"><a class="nav-link" href="#" style="font-size:0.85rem"><span class="badge bg-secondary me-1">Soon</span>Penugasan Ekskul</a></li>
        </ul>
    </div>
</li>

{{-- ============================================================
     6. KESEHATAN (UKS)
     ============================================================ --}}
@include('layouts.sidebar.uks', ['isActiveFn' => 'isActiveSA'])

{{-- ============================================================
     7. PELANGGARAN & PRESTASI
     ============================================================ --}}
<li class="menu-title"><span>Pelanggaran & Prestasi</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.violation-points.') ? ' active' : '' }}"
       href="#pelanggaran" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.violation-points.') ? 'true' : 'false' }}"
       aria-controls="pelanggaran">
        <i class="ri-spam-line"></i>
        <span>Pelanggaran</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.violation-points.') ? ' show' : '' }}" id="pelanggaran">
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
                   style="font-size:0.85rem">Rekap Poin Santri</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link"
       href="#prestasi" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="prestasi">
        <i class="ri-trophy-line"></i>
        <span>Prestasi</span>
    </a>
    <div class="collapse menu-dropdown" id="prestasi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'user.student-achievement.') ? ' active' : '' }}" href="{{ route('user.student-achievement.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Prestasi Akademik</a></li>
            <li class="nav-item"><a class="nav-link{{ request('type') === 'quran' ? ' active' : '' }}" href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'quran']) }}" style="font-size:0.85rem">Hafalan Qur'an</a></li>
            <li class="nav-item"><a class="nav-link{{ request('type') === 'hadits' ? ' active' : '' }}" href="{{ route('user.student-achievement.index', ['userId' => $userId, 'type' => 'hadits']) }}" style="font-size:0.85rem">Hafalan Hadits</a></li>
        </ul>
    </div>
</li>

{{-- ============================================================
     8. DATA PENDUKUNG
     ============================================================ --}}
<li class="menu-title"><span>Data Pendukung</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'sarpras.') || isActiveSA($currentRoute, 'user.sarpras.') || isActiveSA($currentRoute, 'sarpras.user.') ? ' active' : '' }}"
       href="{{ route('sarpras.user.dashboard', ['userId' => $userId]) }}">
        <i class="ri-community-line"></i>
        <span>Sarana Prasarana</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.alumni.') ? ' active' : '' }}"
       href="{{ route('user.alumni.index', ['userId' => $userId]) }}">
        <i class="ri-group-2-line"></i>
        <span>Alumni</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.kaldik.') ? ' active' : '' }}"
       href="#agenda" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.kaldik.') ? 'true' : 'false' }}"
       aria-controls="agenda">
        <i class="ri-task-line"></i>
        <span>Agenda Kegiatan</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.kaldik.') ? ' show' : '' }}" id="agenda">
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

{{-- ============================================================
     9. OPERASIONAL
     ============================================================ --}}
<li class="menu-title"><span>Operasional</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.ats.') ? ' active' : '' }}"
       href="#recruitment" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.ats.') ? 'true' : 'false' }}"
       aria-controls="recruitment">
        <i class="ri-user-add-line"></i>
        <span>Recruitment (ATS)</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.ats.') ? ' show' : '' }}" id="recruitment">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem"><i class="ri-dashboard-line me-1"></i>Dashboard</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.jobs.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem"><i class="ri-briefcase-line me-1"></i>Lowongan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.candidates.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem"><i class="ri-user-line me-1"></i>Kandidat</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.applications.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem"><i class="ri-file-list-3-line me-1"></i>Lamaran</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.ats.data-nilai') ? ' active' : '' }}"
                   href="{{ route('user.ats.data-nilai.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem"><i class="ri-file-chart-line me-1"></i>Data Nilai</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.reports.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.reports.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem"><i class="ri-bar-chart-box-line me-1"></i>Laporan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.settings.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.settings.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem"><i class="ri-settings-3-line me-1"></i>Pengaturan</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.jenjang-karir.') ? ' active' : '' }}"
       href="#jenjang_karir" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.jenjang-karir.') ? 'true' : 'false' }}"
       aria-controls="jenjang_karir">
        <i class="bx bx-rocket"></i>
        <span>Jenjang Karir</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.jenjang-karir.') ? ' show' : '' }}" id="jenjang_karir">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.jenjang-karir.career-path.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Career Path</a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.jenjang-karir.mutasi.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Mutasi & Rotasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.jenjang-karir.promosi.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Promosi & Demosi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.jenjang-karir.talent.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Talent Pool</a>
            </li>
            <li class="nav-item">
                <a class="nav-link"
                   href="{{ route('user.jenjang-karir.succession.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Succession Plan</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.master-data.') ? ' active' : '' }}"
       href="#master_data" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.master-data.') ? 'true' : 'false' }}"
       aria-controls="master_data">
        <i class="bx bx-slider"></i>
        <span>Master Data</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.master-data.') ? ' show' : '' }}" id="master_data">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.jenis-gtk.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.jenis-gtk.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Jenis GTK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.jabatan.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.jabatan.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Jabatan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.master-data.satuan-kerja.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.satuan-kerja.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Satuan Kerja</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveSA($currentRoute, 'user.divisi.') ? ' active' : '' }}"
                   href="{{ route('user.divisi.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-team-line me-1"></i> Divisi
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ============================================================
     10. ADMINISTRASI
     ============================================================ --}}
<li class="menu-title"><span>Administrasi</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.institution-decrees.') ? ' active' : '' }}"
       href="#kbm" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.institution-decrees.') ? 'true' : 'false' }}"
       aria-controls="kbm">
        <i class="ri-git-repository-line"></i>
        <span>Jadwal KBM</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.institution-decrees.') ? ' show' : '' }}" id="kbm">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.institution-decrees.index' ? ' active' : '' }}"
                   href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">SK Pembagian Tugas</a>
            </li>
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'user.jadwal-kbm.') ? ' active' : '' }}" href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Jadwal Pelajaran</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'waka.jam-mengajar') ? ' active' : '' }}" href="{{ route('waka.jam-mengajar') }}" style="font-size:0.85rem">Jam Mengajar</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'waka.rekap-pergantian-jam') ? ' active' : '' }}" href="{{ route('waka.rekap-pergantian-jam') }}" style="font-size:0.85rem">Rekap Pergantian Jam</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link"
       href="#surat" data-bs-toggle="collapse" role="button"
       aria-expanded="false" aria-controls="surat">
        <i class="ri-mail-send-line"></i>
        <span>Surat Menyurat</span>
    </a>
    <div class="collapse menu-dropdown" id="surat">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'waka.surat-keluar.') ? ' active' : '' }}" href="{{ route('waka.surat-keluar.index') }}" style="font-size:0.85rem">Surat Keluar</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSA($currentRoute, 'waka.surat-masuk.') ? ' active' : '' }}" href="{{ route('waka.surat-masuk.index') }}" style="font-size:0.85rem">Surat Masuk</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.dokumen-iso.') ? ' active' : '' }}"
       href="{{ route('user.dokumen-iso.index', ['userId' => $userId]) }}">
        <i class="ri-file-text-line"></i>
        <span>Dokumen ISO</span>
    </a>
</li>

{{-- ============================================================
     11. LAPORAN
     ============================================================ --}}
<li class="menu-title"><span>Laporan</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.sa.audit-logs.index' ? ' active' : '' }}"
       href="{{ route('user.sa.audit-logs.index', ['userId' => $userId]) }}">
        <i class="ri-file-chart-line"></i>
        <span>Log Audit</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.sa.password-reset-logs.index' ? ' active' : '' }}"
       href="{{ route('user.sa.password-reset-logs.index', ['userId' => $userId]) }}">
        <i class="ri-key-line"></i>
        <span>Password Reset Logs</span>
    </a>
</li>

{{-- ============================================================
     12. PENGATURAN SISTEM
     ============================================================ --}}
<li class="menu-title"><span>Pengaturan Sistem</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.sa.users') ? ' active' : '' }}"
       href="{{ route('user.sa.users.index', ['userId' => $userId]) }}">
        <i class="ri-shield-user-line"></i>
        <span>Manajemen User</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSA($currentRoute, 'user.sa.roles') || isActiveSA($currentRoute, 'user.sa.permissions') ? ' active' : '' }}"
       href="#roles_perms" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSA($currentRoute, 'user.sa.roles') || isActiveSA($currentRoute, 'user.sa.permissions') ? 'true' : 'false' }}"
       aria-controls="roles_perms">
        <i class="ri-shield-star-line"></i>
        <span>Roles & Permissions</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSA($currentRoute, 'user.sa.roles') || isActiveSA($currentRoute, 'user.sa.permissions') ? ' show' : '' }}" id="roles_perms">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.sa.roles.index' ? ' active' : '' }}"
                   href="{{ route('user.sa.roles.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Roles</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.sa.permissions.index' ? ' active' : '' }}"
                   href="{{ route('user.sa.permissions.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Permissions</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.sa.tokens.index' ? ' active' : '' }}"
       href="{{ route('user.sa.tokens.index', ['userId' => $userId]) }}">
        <i class="ri-key-2-line"></i>
        <span>Token & Sesi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.sa.failed-jobs.index' ? ' active' : '' }}"
       href="{{ route('user.sa.failed-jobs.index', ['userId' => $userId]) }}">
        <i class="ri-error-warning-fill"></i>
        <span>Failed Jobs</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.sa.notifications.index' ? ' active' : '' }}"
       href="{{ route('user.sa.notifications.index', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i>
        <span>Notifikasi Sistem</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.sa.sidebar-menus.index' ? ' active' : '' }}"
       href="{{ route('user.sa.sidebar-menus.index', ['userId' => $userId]) }}">
        <i class="ri-menu-add-line"></i>
        <span>Kelola Menu Sidebar</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.sa.system-settings.index' ? ' active' : '' }}"
       href="{{ route('user.sa.system-settings.index', ['userId' => $userId]) }}">
        <i class="ri-settings-3-line"></i>
        <span>Pengaturan Sistem</span>
    </a>
</li>
