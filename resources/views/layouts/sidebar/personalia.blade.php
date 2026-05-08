<!-- Personalia Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

function isActiveP($routeName, $pattern) {
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
    <a class="nav-link menu-link{{ $currentRoute === 'user.todos.index' ? ' active' : '' }}"
       href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i>
        <span>Todo List</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.profile.my' ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i>
        <span>Profile</span>
    </a>
</li>

<li class="menu-title"><span>Data GTK</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="#data_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.gtk.') ? 'true' : 'false' }}"
       aria-controls="data_gtk">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.gtk.') ? ' show' : '' }}" id="data_gtk">
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
            <li class="nav-item">
                <a class="nav-link{{ isActiveP($currentRoute, 'user.gtk.index') && $currentRoute !== 'user.gtk.indexguru' && $currentRoute !== 'user.gtk.indextendik' ? ' active' : '' }}"
                   href="{{ route('user.gtk.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Semua GTK</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk.import' ? ' active' : '' }}"
                   href="{{ route('user.gtk.import', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Import / Export</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.gtk-requests.') ? ' active' : '' }}"
       href="#gtk_requests" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.gtk-requests.') ? 'true' : 'false' }}"
       aria-controls="gtk_requests">
        <i class="ri-file-add-line"></i>
        <span>Pengajuan GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.gtk-requests.') ? ' show' : '' }}" id="gtk_requests">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk-requests.index' ? ' active' : '' }}"
                   href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Pengajuan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.gtk-requests.create' ? ' active' : '' }}"
                   href="{{ route('user.gtk-requests.create', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Buat Pengajuan</a>
            </li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>GTK Requests & Approval</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.approvals.') ? ' active' : '' }}"
       href="#approvals" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.approvals.') ? 'true' : 'false' }}"
       aria-controls="approvals">
        <i class="ri-git-pull-request-line"></i>
        <span>Approval</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.approvals.') ? ' show' : '' }}" id="approvals">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.approvals.index' ? ' active' : '' }}"
                   href="{{ route('user.approvals.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Daftar Approval</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.approvals.my-pending' ? ' active' : '' }}"
                   href="{{ route('user.approvals.my-pending', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Menunggu Saya</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.approvals.history' ? ' active' : '' }}"
                   href="{{ route('user.approvals.history', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Riwayat</a>
            </li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Jenjang Karir</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.jenjang-karir.') ? ' active' : '' }}"
       href="#jenjang_karir" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.jenjang-karir.') ? 'true' : 'false' }}"
       aria-controls="jenjang_karir">
        <i class="ri-rocket-line"></i>
        <span>Jenjang Karir</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.jenjang-karir.') ? ' show' : '' }}" id="jenjang_karir">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.jenjang-karir.career-path.index' ? ' active' : '' }}"
                   href="{{ route('user.jenjang-karir.career-path.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Career Path</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.jenjang-karir.mutasi.index' ? ' active' : '' }}"
                   href="{{ route('user.jenjang-karir.mutasi.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Mutasi & Rotasi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.jenjang-karir.promosi.index' ? ' active' : '' }}"
                   href="{{ route('user.jenjang-karir.promosi.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Promosi & Demosi</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.jenjang-karir.talent.index' ? ' active' : '' }}"
                   href="{{ route('user.jenjang-karir.talent.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Talent Pool</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.jenjang-karir.succession.index' ? ' active' : '' }}"
                   href="{{ route('user.jenjang-karir.succession.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Succession Plan</a>
            </li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Pensiun</span></li>
@php
$p_notif_months = (int) (\App\Models\PensionSetting::get('notification_months', '6'));
$p_bup_age = (int) (\App\Models\PensionSetting::get('bup_age', '58'));
// Count GTK approaching BUP using raw date math in query
$p_approaching = DB::table('users')
    ->join('gtk_profiles', 'users.id', '=', 'gtk_profiles.user_id')
    ->leftJoin('gtk_pensions', function($join) {
        $join->on('users.id', '=', 'gtk_pensions.user_id')
             ->whereIn('gtk_pensions.pension_status', ['completed', 'cancelled']);
    })
    ->where('users.is_active', true)
    ->whereNotNull('gtk_profiles.tanggal_lahir')
    ->whereNull('gtk_pensions.id')
    ->whereRaw("DATE_ADD(gtk_profiles.tanggal_lahir, INTERVAL ? YEAR) <= DATE_ADD(NOW(), INTERVAL ? MONTH)",
        [$p_bup_age, $p_notif_months])
    ->count();
@endphp
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.pension.index' || $currentRoute === 'user.pension.settings' ? ' active' : '' }}"
       href="{{ route('user.pension.index', ['userId' => $userId]) }}">
        <i class="ri-umbrella-line"></i>
        <span>Pensiun</span>
        @if($p_approaching > 0)
        <span class="badge bg-danger rounded-pill" style="font-size:0.65rem; padding: 0.2em 0.5em;">{{ $p_approaching }}</span>
        @endif
    </a>
</li>

<li class="menu-title"><span>Rekrutmen GTK (ATS)</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.ats.') ? ' active' : '' }}"
       href="#ats_recruitment" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.ats.') ? 'true' : 'false' }}"
       aria-controls="ats_recruitment">
        <i class="ri-user-add-line"></i>
        <span>Rekrutmen ATS</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.ats.') ? ' show' : '' }}" id="ats_recruitment">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.jobs.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Lowongan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.candidates.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Kandidat</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.applications.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Lamaran</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.interviews.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.interviews.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Jadwal Interview</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.reports.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.reports.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Reports</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ $currentRoute === 'user.ats.settings.index' ? ' active' : '' }}"
                   href="{{ route('user.ats.settings.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Pengaturan</a>
            </li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Master Data</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.master-data.') ? ' active' : '' }}"
       href="#master_data" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveP($currentRoute, 'user.master-data.') ? 'true' : 'false' }}"
       aria-controls="master_data">
        <i class="ri-database-2-line"></i>
        <span>Master Data</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveP($currentRoute, 'user.master-data.') ? ' show' : '' }}" id="master_data">
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
                <a class="nav-link{{ $currentRoute === 'user.master-data.mata-pelajaran.index' ? ' active' : '' }}"
                   href="{{ route('user.master-data.mata-pelajaran.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">Mata Pelajaran</a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveP($currentRoute, 'user.work-units.') ? ' active' : '' }}"
       href="{{ route('user.work-units.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i>
        <span>Satuan Kerja</span>
    </a>
</li>
