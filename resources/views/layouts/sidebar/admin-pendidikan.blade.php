<!-- Admin Pendidikan Asrama Sidebar -->
<style>
.navbar-nav .nav-item .nav-link.active,
.navbar-nav .nav-item .nav-link.active i {
    background-color: rgba(255,255,255,0.1) !important;
    border-radius: 0.25rem;
}
</style>
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

$routeInstance = request()->route();
$routeParams = $routeInstance ? $routeInstance->parameters() : [];
$asramaUuid = $routeParams['asramaUuid'] ?? null;
$hasAsramaContext = !empty($asramaUuid);

if (!$hasAsramaContext && $currentRoute === 'user.asrama.my-profile') {
    $firstAsrama = \App\Models\Dormitory::where('is_active', true)->first();
    if ($firstAsrama) {
        $asramaUuid = $firstAsrama->id;
        $hasAsramaContext = true;
    }
}

$asramaProfileFallback = route('user.asrama.my-profile', ['userId' => $userId]);

@endphp

<li class="menu-title"><span>Menu Utama</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.dashboard' ? ' active' : '' }}"
       href="{{ route('user.dashboard', ['userId' => $userId]) }}">
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

<li class="menu-title"><span>Akademik Asrama</span></li>

{{-- Perizinan (focus utama) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.permits.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-pass-valid-line"></i>
        <span>Perizinan</span>
    </a>
</li>

{{-- Kebijakan Asrama --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.boarding-policies.') ? ' active' : '' }}"
       href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}">
        <i class="ri-file-shield-2-line"></i>
        <span>Kebijakan Asrama</span>
    </a>
</li>

{{-- Kalender --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.calendar.return.') ? ' active' : '' }}"
       href="{{ route('user.calendar.return.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-event-line"></i>
        <span>Kalender Kepulangan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.dormitory-returns.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-login-box-line"></i>
        <span>Kedatangan Santri</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.calendar.visit.') ? ' active' : '' }}"
       href="{{ route('user.calendar.visit.index', ['userId' => $userId]) }}">
        <i class="ri-footprint-line"></i>
        <span>Kalender Kunjungan</span>
    </a>
</li>

<li class="menu-title"><span>Komunikasi</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.posts.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-megaphone-line"></i>
        <span>Informasi</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.broadcasts.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.broadcasts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-notification-3-line"></i>
        <span>Broadcast</span>
    </a>
</li>

<li class="menu-title"><span>Perencanaan Kegiatan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.activities.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.activities.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-file-list-3-line"></i>
        <span>Log Kegiatan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.templates.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.templates.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-git-branch-line"></i>
        <span>Template Kegiatan</span>
    </a>
</li>

<li class="menu-title"><span>Mahrom & Peserta Didik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.students.') && !str_contains($currentRoute, 'mahrom') ? ' active' : '' }}"
       href="{{ route('user.students.index', ['userId' => $userId]) }}">
        <i class="ri-user-star-line"></i>
        <span>Data Santri</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ str_contains($currentRoute, 'mahrom') ? ' active' : '' }}"
       href="{{ route('user.students.index', ['userId' => $userId]) }}">
        <i class="ri-parent-line"></i>
        <span>Data Mahrom</span>
    </a>
</li>

<li class="menu-title"><span>Reporting</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.reports.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.reports.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-file-text-line"></i>
        <span>Laporan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.asrama.my-profile' || $currentRoute === 'user.asrama.show' ? ' active' : '' }}"
       href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">
        <i class="ri-hotel-line"></i>
        <span>Daftar Asrama</span>
    </a>
</li>
