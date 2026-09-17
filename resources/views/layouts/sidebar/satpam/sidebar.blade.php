<!-- Satpam Sidebar — Satuan Keamanan -->
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

<li class="nav-item">
    @php
        $__satpamAsramaUuid = request()->route()?->parameters()['asramaUuid'] ?? null;
        $hasSatpamAsramaContext = !empty($__satpamAsramaUuid);
        if (!$hasSatpamAsramaContext) {
            $viewAsDormitoryId = request()->attributes->get('viewAsDormitoryId');
            if ($viewAsDormitoryId) {
                $__satpamAsramaUuid = $viewAsDormitoryId;
                $hasSatpamAsramaContext = true;
            }
        }
        if (!$hasSatpamAsramaContext) {
            $__firstAsrama = \App\Models\Dormitory::where('is_active', true)->first();
            $__satpamAsramaUuid = $__firstAsrama?->id;
            $hasSatpamAsramaContext = (bool) $__satpamAsramaUuid;
        }
    @endphp
    <a class="nav-link menu-link{{ isActiveSatpam($currentRoute, 'user.asrama.residents.') ? ' active' : '' }}"
       href="{{ $hasSatpamAsramaContext ? route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $__satpamAsramaUuid]) : route('root') }}">
        <i class="ri-hotel-line"></i>
        <span>Daftar Penghuni Asrama</span>
    </a>
</li>
