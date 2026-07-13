<!-- Asrama Sidebar -->
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

// Get asramaUuid from route parameters (works on all asrama pages)
$routeInstance = request()->route();
$routeParams = $routeInstance ? $routeInstance->parameters() : [];
$asramaUuid = $routeParams['asramaUuid'] ?? null;
$hasAsramaContext = !empty($asramaUuid);

// If on the asrama list page (user.asrama.index) and no asramaUuid in route,
// auto-select the first asrama so sub-menu links work immediately
if (!$hasAsramaContext && $currentRoute === 'user.asrama.index') {
    $firstAsrama = \App\Models\Dormitory::where('is_active', true)->first();
    if ($firstAsrama) {
        $asramaUuid = $firstAsrama->id;
        $hasAsramaContext = true;
    }
}

// Fallback link when no asrama is selected
$asramaIndexFallback = route('user.asrama.index', ['userId' => $userId]);

function isActiveAsr($routeName, $pattern) {
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

<li class="menu-title"><span>Asrama</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.asrama.index' ? ' active' : '' }}"
       href="{{ route('user.asrama.index', ['userId' => $userId]) }}">
        <i class="ri-hotel-line"></i>
        <span>Daftar Asrama</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $hasAsramaContext ? ' active' : '' }}"
       href="#asrama-detail" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ $hasAsramaContext ? 'true' : 'false' }}"
       aria-controls="asrama-detail">
        <i class="ri-hotel-fill"></i>
        <span>Manajemen Asrama</span>
        <span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ $hasAsramaContext ? ' show' : '' }}"
         id="asrama-detail">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.residents.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-user-follow-line me-1"></i> Penghuni
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.attendance.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.attendance.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-calendar-check-line me-1"></i> Absensi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.permits.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-pass-valid-line me-1"></i> Perizinan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.violations.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.violations.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-error-warning-line me-1"></i> Pelanggaran
                </a>
            </li>

            {{-- Approval Center --}}
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.approval-center') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.approval-center', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-inbox-line me-1"></i> Approval Center
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.visits.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-footprint-line me-1"></i> Kunjungan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.room-moves.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.room-moves.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-arrow-left-right-line me-1"></i> Mutasi Kamar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.rooms.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.rooms.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}">
                    <i class="ri-door-open-line"></i>
                    <span>Kamar</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.wings.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.wings.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}">
                    <i class="ri-stack-line"></i>
                    <span>Gedung</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.asrama.inventories.') ? ' active' : '' }}"
                   href="{{ $hasAsramaContext ? route('user.asrama.inventories.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}"
                   style="font-size:0.85rem">
                    <i class="ri-archive-line me-1"></i> Inventaris
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.boarding-policies.') ? ' active' : '' }}"
                   href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-file-shield-2-line me-1"></i> Kebijakan Asrama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.calendar.return.') ? ' active' : '' }}"
                   href="{{ route('user.calendar.return.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-calendar-event-line me-1"></i> Kalender Kepulangan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.calendar.visit.') ? ' active' : '' }}"
                   href="{{ route('user.calendar.visit.index', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-footprint-line me-1"></i> Kalender Kunjungan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAsr($currentRoute, 'user.dashboard.pengasuh') ? ' active' : '' }}"
                   href="{{ route('user.dashboard.pengasuh', ['userId' => $userId]) }}"
                   style="font-size:0.85rem">
                    <i class="ri-dashboard-3-line me-1"></i> Dashboard Pengasuh
                </a>
            </li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Informasi & Kegiatan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.posts.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.posts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}">
        <i class="ri-megaphone-line"></i>
        <span>Informasi</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.broadcasts.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.broadcasts.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}">
        <i class="ri-notification-3-line"></i>
        <span>Broadcast</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.activities.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.activities.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}">
        <i class="ri-file-list-3-line"></i>
        <span>Log Kegiatan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAsr($currentRoute, 'user.asrama.templates.') ? ' active' : '' }}"
       href="{{ $hasAsramaContext ? route('user.asrama.templates.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : $asramaIndexFallback }}">
        <i class="ri-git-branch-line"></i>
        <span>Template Kegiatan</span>
    </a>
</li>

<li class="menu-title"><span>Peserta Didik</span></li>
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
