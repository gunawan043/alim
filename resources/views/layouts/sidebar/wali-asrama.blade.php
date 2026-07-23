<!-- Wali Asrama Sidebar -->
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
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.dashboard.pengasuh' ? ' active' : '' }}"
       href="{{ route('user.dashboard.pengasuh', ['userId' => $userId]) }}">
        <i class="ri-dashboard-3-line"></i>
        <span>Dashboard Pengasuh</span>
    </a>
</li>

<li class="menu-title"><span>Penghuni</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.residents.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-hotel-line"></i>
        <span>Data Penghuni</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $hasAsramaContext && isActiveAsr($currentRoute, 'user.asrama.rooms.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-door-open-line"></i>
        <span>Kamar</span>
    </a>
</li>

<li class="menu-title"><span>Keamanan Harian</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.attendance.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}"
       data-toggle="collapse">
        <i class="ri-calendar-check-line"></i>
        <span>Absensi <span class="menu-arrow"></span></span>
    </a>
    <ul class="nav nav-sm flex-column collapse menu-dropdown"
        id="attendance-detail">
        <li class="nav-item">
            <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.attendance.') ? ' active' : '' }}"
               href="{{ $hasAsramaContext ? route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
                Riwayat Absensi
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link{{ $currentRoute === 'user.asrama.attendance.create' ? ' active' : '' }}"
               href="{{ $hasAsramaContext ? route('user.asrama.attendance.create', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
                Input Hari Ini
            </a>
        </li>
    </ul>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.violations.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-error-warning-line"></i>
        <span>Pelanggaran</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.rewards.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.rewards.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-medal-line"></i>
        <span>Penghargaan</span>
    </a>
</li>

<li class="menu-title"><span>Mobilitas Santri</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.permits.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-pass-valid-line"></i>
        <span>Perizinan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.visits.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-footprint-line"></i>
        <span>Visite / Kunjungan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.room-moves.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-arrow-left-right-line"></i>
        <span>Mutasi Kamar</span>
    </a>
</li>

<li class="menu-title"><span>Informasi & Kegiatan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.activities.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.activities.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-file-list-3-line"></i>
        <span>Log Kegiatan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.posts.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaProfileFallback }}">
        <i class="ri-megaphone-line"></i>
        <span>Informasi</span>
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
