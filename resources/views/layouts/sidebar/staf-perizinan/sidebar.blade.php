<!-- Staf Perizinan Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

$routeInstance = request()->route();
$routeParams = $routeInstance ? $routeInstance->parameters() : [];
$asramaUuid = $routeParams['asramaUuid'] ?? null;

// Get user's accessible dormitories
$accessibleDormitories = $currentUser->accessibleDormitoryIds();
$hasStafPerizinanContext = !empty($asramaUuid) && in_array($asramaUuid, $accessibleDormitories);

if (!$hasStafPerizinanContext && !empty($accessibleDormitories)) {
    $asramaUuid = $accessibleDormitories[0];
    $hasStafPerizinanContext = true;
}

if (! function_exists('isActiveStafPerizinan')) {
    function isActiveStafPerizinan($routeName, $pattern) {
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

<li class="menu-title"><span>Perizinan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveStafPerizinan($currentRoute, 'user.asrama.permits.') ? ' active' : '' }}"
       href="{{ $hasStafPerizinanContext ? route('user.asrama.permits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : '#' }}"
       {{ !$hasStafPerizinanContext ? 'disabled' : '' }}>
        <i class="ri-pass-valid-line"></i>
        <span>Daftar Perizinan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveStafPerizinan($currentRoute, 'user.asrama.permits.scan') ? ' active' : '' }}"
       href="{{ $hasStafPerizinanContext ? route('user.asrama.permits.scan', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : '#' }}"
       {{ !$hasStafPerizinanContext ? 'disabled' : '' }}>
        <i class="ri-qr-scan-line"></i>
        <span>Scan QR</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveStafPerizinan($currentRoute, 'user.asrama.visits.') ? ' active' : '' }}"
       href="{{ $hasStafPerizinanContext ? route('user.asrama.visits.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : '#' }}"
       {{ !$hasStafPerizinanContext ? 'disabled' : '' }}>
        <i class="ri-footprint-line"></i>
        <span>Kunjungan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveStafPerizinan($currentRoute, 'user.asrama.dormitory-returns.') ? ' active' : '' }}"
       href="{{ $hasStafPerizinanContext ? route('user.asrama.dormitory-returns.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : '#' }}"
       {{ !$hasStafPerizinanContext ? 'disabled' : '' }}>
        <i class="ri-login-box-line"></i>
        <span>Kepulangan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveStafPerizinan($currentRoute, 'user.asrama.reports.') ? ' active' : '' }}"
       href="{{ $hasStafPerizinanContext ? route('user.asrama.reports.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid]) : '#' }}"
       {{ !$hasStafPerizinanContext ? 'disabled' : '' }}>
        <i class="ri-file-text-line"></i>
        <span>Laporan</span>
    </a>
</li>

@if($hasStafPerizinanContext)
<li class="menu-title"><span>Asrama: {{ \App\Models\Dormitory::find($asramaUuid)?->name ?? 'Unknown' }}</span></li>
@endif
