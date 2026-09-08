<!-- Unit Rumah Tangga / Sarpras Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isKepala = in_array($jabatan, ['Kepala Unit Rumah Tangga', 'Kepala Sarpras']);

if (! function_exists('isActiveURT')) {
function isActiveURT($routeName, $pattern) {
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

<li class="menu-title"><span>Sarana Prasarana</span></li>

@include('layouts.sidebar.sarpras.sidebar', ['isActiveFn' => 'isActiveURT'])

{{-- GTK Section (Kepala) --}}
@if($isKepala)
<li class="menu-title"><span>GTK Terkait</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveURT($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="{{ route('user.gtk.index', ['userId' => $userId]) }}">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
</li>
@endif

@include('layouts.sidebar.uks.sidebar', ['isActiveFn' => 'isActiveURT'])
