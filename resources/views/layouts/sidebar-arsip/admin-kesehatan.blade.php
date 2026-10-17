<!-- Admin Kesehatan — UKS Sidebar -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

function isActiveUKS($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}
@endphp

<li class="menu-title"><span>Menu</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-dashboard-3-line"></i>
        <span>Dashboard</span>
    </a>
</li>

{{-- Pelayanan Kesehatan --}}
<li class="menu-title"><span>Pelayanan Kesehatan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUKS($currentRoute, 'user.uks.health-checkups') ? ' active' : '' }}"
       href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}">
        <i class="ri-stethoscope-line"></i>
        <span>Medical Check-up</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUKS($currentRoute, 'user.uks.immunizations') ? ' active' : '' }}"
       href="{{ route('user.uks.immunizations.index', ['userId' => $userId]) }}">
        <i class="ri-syringe-line"></i>
        <span>Imunisasi</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUKS($currentRoute, 'user.uks.health-permits') ? ' active' : '' }}"
       href="{{ route('user.uks.health-permits.index', ['userId' => $userId]) }}">
        <i class="ri-file-text-line"></i>
        <span>Izin Sakit</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUKS($currentRoute, 'user.uks.counseling-records') ? ' active' : '' }}"
       href="{{ route('user.uks.counseling-records.index', ['userId' => $userId]) }}">
        <i class="ri-chat-heart-line"></i>
        <span>Konseling</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUKS($currentRoute, 'user.uks.health-metrics') ? ' active' : '' }}"
       href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Antropometri</span>
    </a>
</li>

{{-- Farmasi & Logistik --}}
<li class="menu-title"><span>Farmasi & Logistik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUKS($currentRoute, 'user.uks.medicine-inventory') || isActiveUKS($currentRoute, 'user.uks.medicine-logs') ? ' active' : '' }}"
       href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}">
        <i class="ri-capsule-line"></i>
        <span>Stok Obat</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUKS($currentRoute, 'user.uks.medicine-logs') ? ' active' : '' }}"
       href="{{ route('user.uks.medicine-logs.index', ['userId' => $userId]) }}">
        <i class="ri-history-line"></i>
        <span>Pemberian Obat</span>
    </a>
</li>

{{-- Rujukan & Lingkungan --}}
<li class="menu-title"><span>Rujukan & Lingkungan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUKS($currentRoute, 'user.uks.facility-referrals') ? ' active' : '' }}"
       href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}">
        <i class="ri-hospital-line"></i>
        <span>Faskes Rujukan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUKS($currentRoute, 'user.uks.sanitation-inspections') ? ' active' : '' }}"
       href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}">
        <i class="ri-shield-check-line"></i>
        <span>Inspeksi Sanitasi</span>
    </a>
</li>
