<!-- ATS Recruitment Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

function isActiveAts($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}
@endphp

<li class="menu-title"><span>Rekrutmen</span></li>

{{-- Dashboard --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.ats.index' ? ' active' : '' }}"
       href="{{ route('user.ats.index', ['userId' => $userId]) }}">
        <i class="ri-dashboard-line"></i>
        <span>Dashboard</span>
    </a>
</li>

{{-- Lowongan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAts($currentRoute, 'user.ats.jobs') ? ' active' : '' }}"
       href="{{ route('user.ats.jobs.index', ['userId' => $userId]) }}">
        <i class="ri-briefcase-line"></i>
        <span>Lowongan</span>
    </a>
</li>

{{-- Pelamar --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAts($currentRoute, 'user.ats.candidates') ? ' active' : '' }}"
       href="{{ route('user.ats.candidates.index', ['userId' => $userId]) }}">
        <i class="ri-user-search-line"></i>
        <span>Pelamar</span>
    </a>
</li>

{{-- Lamaran --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAts($currentRoute, 'user.ats.applications') ? ' active' : '' }}"
       href="{{ route('user.ats.applications.index', ['userId' => $userId]) }}">
        <i class="ri-file-list-3-line"></i>
        <span>Lamaran</span>
    </a>
</li>

{{-- Data Nilai --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAts($currentRoute, 'user.ats.data-nilai') ? ' active' : '' }}"
       href="{{ route('user.ats.data-nilai.index', ['userId' => $userId]) }}">
        <i class="ri-file-chart-line"></i>
        <span>Data Nilai</span>
    </a>
</li>

{{-- Pipeline --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAts($currentRoute, 'user.ats.pipeline') ? ' active' : '' }}"
       href="{{ route('user.ats.pipeline.board', ['userId' => $userId, 'jobId' => optional(\App\Models\RecruitmentJob::where('status','aktif')->first())->id ?? '0']) }}">
        <i class="ri-git-branch-line"></i>
        <span>Pipeline</span>
    </a>
</li>

<li class="menu-title mt-2"><span>Laporan</span></li>

{{-- Laporan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAts($currentRoute, 'user.ats.reports') ? ' active' : '' }}"
       href="{{ route('user.ats.reports.dashboard', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-box-line"></i>
        <span>Laporan</span>
    </a>
</li>

<li class="menu-title mt-2"><span>Pengaturan</span></li>

{{-- Pengaturan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.ats.settings.index' ? ' active' : '' }}"
       href="{{ route('user.ats.settings.index', ['userId' => $userId]) }}">
        <i class="ri-settings-3-line"></i>
        <span>Pengaturan</span>
    </a>
</li>
