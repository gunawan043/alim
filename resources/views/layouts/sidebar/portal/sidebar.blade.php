<!-- Portal Sidebar — Wali Santri, UKS, dan role portal lainnya -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

if (! function_exists('isActivePortal')) {
function isActivePortal($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}
}
@endphp

<li class="menu-title"><span>Menu Wali Santri</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i>
        <span>Dashboard</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePortal($currentRoute, 'user.profile.my') ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i>
        <span>Profile</span>
    </a>
</li>

<li class="menu-title"><span>Portal Wali</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePortal($currentRoute, 'portal.') ? ' active' : '' }}"
       href="{{ route('portal.dashboard', ['token' => $currentUser->waliAccessToken() ?? 'placeholder']) }}">
        <i class="ri-shield-user-line"></i>
        <span>Akses Portal</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePortal($currentRoute, 'user.notifications.') ? ' active' : '' }}"
       href="{{ route('user.notifications.index', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i>
        <span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePortal($currentRoute, 'user.jadwal-kbm.') ? ' active' : '' }}"
       href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-line"></i>
        <span>Jadwal Santri</span>
    </a>
</li>

@include('layouts.sidebar.uks.sidebar', ['isActiveFn' => 'isActivePortal'])
