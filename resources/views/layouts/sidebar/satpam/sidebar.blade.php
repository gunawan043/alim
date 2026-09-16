<!-- Satpam Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;
$jabatan = $currentUser->gtkEmployment?->jabatan;
$isKepala = $jabatan === 'Kepala Satpam';
$isWaliJaga = $jabatan === 'Wali Jaga';

if (! function_exists('isActiveSatpam')) {
function isActiveSatpam($routeName, $pattern) {
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

<li class="menu-title"><span>Satuan Keamanan</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.students.') ? ' active' : '' }}"
       href="{{ route('user.students.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Daftar Santri</span>
    </a>
</li>

@if($isKepala || $isWaliJaga)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.uks.') ? ' active' : '' }}"
       href="#satpam_uks" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveSatpam($currentRoute, 'user.uks.') ? 'true' : 'false' }}"
       aria-controls="satpam_uks">
        <i class="ri-heart-pulse-line"></i>
        <span>UKS</span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveSatpam($currentRoute, 'user.uks.') ? ' show' : '' }}" id="satpam_uks">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActiveSatpam($currentRoute, 'user.uks.health-checkups') ? ' active' : '' }}" href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Medical Check-up</a></li>
            <li class="nav-item"><a class="nav-link{{ isActiveSatpam($currentRoute, 'user.uks.medicine-inventory') ? ' active' : '' }}" href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Stok Obat</a></li>
        </ul>
    </div>
</li>
@endif

<li class="nav-item">
    @php
        $__satpamAsramaUuid = request()->route()?->parameters()['asramaUuid'] ?? null;
        if (empty($__satpamAsramaUuid)) {
            $__firstAsrama = \App\Models\Dormitory::where('is_active', true)->first();
            $__satpamAsramaUuid = $__firstAsrama?->id;
        }
    @endphp
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.asrama.residents.') ? ' active' : '' }}"
       href="{{ $__satpamAsramaUuid ? route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $__satpamAsramaUuid]) : route('root') }}">
        <i class="ri-hotel-line"></i>
        <span>Daftar Penghuni Asrama</span>
    </a>
</li>

{{-- GTK Section (Kepala) --}}
@if($isKepala)
<li class="menu-title"><span>GTK</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.gtk-additional-tasks.') ? ' active' : '' }}"
       href="{{ route('user.gtk-additional-tasks.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i>
        <span>Tugas Tambahan GTK</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.gtk-positions.') ? ' active' : '' }}"
       href="{{ route('user.gtk-positions.index', ['userId' => $userId]) }}">
        <i class="ri-briefcase-line"></i>
        <span>Jabatan GTK</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.gtk-position-proposals.') ? ' active' : '' }}"
       href="{{ route('user.gtk-position-proposals.index', ['userId' => $userId]) }}">
        <i class="ri-arrow-up-line"></i>
        <span>Pengajuan Jabatan</span>
    </a>
</li>
@endif

@include('layouts.sidebar.uks.sidebar', ['isActiveFn' => 'isActiveSatpam'])
