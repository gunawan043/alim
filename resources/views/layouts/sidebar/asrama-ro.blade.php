<!-- Asrama (Read-Only) Sidebar -->
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

<li class="menu-title"><span>Menu</span></li>
<li class="nav-item">
    {{-- <a class="nav-link menu-link{{ $currentRoute === 'dashboard' ? ' active' : '' }}"
       href="{{ route('dashboard') }}">
        <i class="ri-home-6-line"></i>
        <span>Dashboard</span>
    </a> --}}
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.profile.my' ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i>
        <span>Profile</span>
    </a>
</li>

<li class="menu-title"><span>Monitoring Asrama</span></li>

{{-- Profil Asrama --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.asrama.my-profile' || $currentRoute === 'user.asrama.show' ? ' active' : '' }}"
       href="{{ route('user.asrama.my-profile', ['userId' => $userId]) }}">
        <i class="ri-hotel-line"></i>
        <span>Profil Asrama</span>
    </a>
</li>

{{-- Kamar --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.rooms.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-door-open-line"></i>
        <span>Kamar</span>
    </a>
</li>

{{-- Wing (Gedung) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.wings.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-stack-line"></i>
        <span>Gedung</span>
    </a>
</li>

{{-- Penghuni --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.residents.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-user-follow-line"></i>
        <span>Penghuni</span>
    </a>
</li>

{{-- Absensi --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.attendance.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-calendar-check-line"></i>
        <span>Absensi</span>
    </a>
</li>

{{-- Perizinan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.permits.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-pass-valid-line"></i>
        <span>Perizinan</span>
    </a>
</li>

{{-- Konfigurasi Izin --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.leave-policies') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.leave-policies.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-settings-4-line"></i>
        <span>Konfigurasi Izin</span>
    </a>
</li>

{{-- Pelanggaran --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.violations.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-error-warning-line"></i>
        <span>Pelanggaran</span>
    </a>
</li>

{{-- Penghargaan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.rewards.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.rewards.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-medal-line"></i>
        <span>Penghargaan</span>
    </a>
</li>

{{-- Kunjungan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.visits.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-footprint-line"></i>
        <span>Kunjungan</span>
    </a>
</li>

{{-- Mutasi Kamar --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.room-moves.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-arrow-left-right-line"></i>
        <span>Mutasi Kamar</span>
    </a>
</li>

{{-- Wali Kamar --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.room-supervisors.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.room-supervisors.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-shield-user-line"></i>
        <span>Wali Kamar</span>
    </a>
</li>

{{-- Inventaris --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.inventories.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-archive-line"></i>
        <span>Inventaris</span>
    </a>
</li>

{{-- Laporan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.reports.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.reports.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-file-text-line"></i>
        <span>Laporan</span>
    </a>
</li>

{{-- Approval Center (view only) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.approval-center') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.approval-center', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-inbox-line"></i>
        <span>Approval Center</span>
    </a>
</li>

{{-- Kegiatan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.activities.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.activities.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-file-list-3-line"></i>
        <span>Kegiatan</span>
    </a>
</li>

{{-- Template --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.templates.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.templates.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-git-branch-line"></i>
        <span>Template Kegiatan</span>
    </a>
</li>

{{-- Informasi & Broadcast --}}
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

<li class="menu-title"><span>Setting</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.boarding-policies.') ? ' active' : '' }}"
       href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}">
        <i class="ri-file-shield-2-line"></i>
        <span>Kebijakan Asrama</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.calendar.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.calendar.return.index', ['userId' => $userId]) : $asramaProfileFallback }}">
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
    <a class="nav-link menu-link{{ $currentRoute === 'user.dashboard.pengasuh' ? ' active' : '' }}"
       href="{{ route('user.dashboard.pengasuh', ['userId' => $userId]) }}">
        <i class="ri-dashboard-3-line"></i>
        <span>Dashboard Pengasuh</span>
    </a>
</li>
