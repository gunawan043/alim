<!-- Kepala UKS Sidebar — Melihat Semua GTK UKS (Putra & Putri) -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$currentUser = auth()->user();
$userId = $currentUser->id;

function isActiveUks($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}
@endphp

<li class="menu-title"><span>Dashboard</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.dashboard') ? ' active' : '' }}"
       href="{{ route('user.uks.dashboard', ['userId' => $userId]) }}">
        <i class="ri-dashboard-3-line"></i>
        <span>Dashboard</span>
    </a>
</li>

{{-- Manajemen Data Kesehatan --}}
<li class="menu-title"><span>Data Kesehatan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.gtk-health') ? ' active' : '' }}"
       href="{{ route('user.uks.gtk-health.index', ['userId' => $userId]) }}">
        <i class="ri-heart-pulse-line"></i>
        <span>Data Kesehatan GTK</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.student-health') ? ' active' : '' }}"
       href="{{ route('user.uks.student-health.index', ['userId' => $userId]) }}">
        <i class="ri-user-heart-line"></i>
        <span>Data Kesehatan Santri</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.profile') ? ' active' : '' }}"
       href="{{ route('user.uks.profile', ['userId' => $userId]) }}">
        <i class="ri-profile-line"></i>
        <span>Profil Kesehatan Saya</span>
    </a>
</li>

{{-- Pelayanan Pasien --}}
<li class="menu-title"><span>Pelayanan Pasien</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.patients') ? ' active' : '' }}"
       href="{{ route('user.uks.patients.index', ['userId' => $userId]) }}">
        <i class="ri-hospital-line"></i>
        <span>Pendaftaran Pasien (RPM)</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.treatment-status') ? ' active' : '' }}"
       href="{{ route('user.uks.patients.index', ['userId' => $userId, 'status' => 'rawat_uks']) }}">
        <i class="ri-heart-pulse-fill"></i>
        <span>Status Perawatan</span>
    </a>
</li>

{{-- Pelayanan Kesehatan (existing) --}}
<li class="menu-title"><span>Pelayanan Kesehatan</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.health-checkups') ? ' active' : '' }}"
       href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}">
        <i class="ri-stethoscope-line"></i>
        <span>Medical Check-up</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.immunizations') ? ' active' : '' }}"
       href="{{ route('user.uks.immunizations.index', ['userId' => $userId]) }}">
        <i class="ri-syringe-line"></i>
        <span>Imunisasi</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.health-permits') ? ' active' : '' }}"
       href="{{ route('user.uks.health-permits.index', ['userId' => $userId]) }}">
        <i class="ri-file-text-line"></i>
        <span>Izin Sakit</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.counseling-records') ? ' active' : '' }}"
       href="{{ route('user.uks.counseling-records.index', ['userId' => $userId]) }}">
        <i class="ri-chat-heart-line"></i>
        <span>Konseling</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.health-metrics') ? ' active' : '' }}"
       href="{{ route('user.uks.health-metrics.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Antropometri</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.facility-referrals') ? ' active' : '' }}"
       href="{{ route('user.uks.facility-referrals.index', ['userId' => $userId]) }}">
        <i class="ri-hospital-fill"></i>
        <span>Faskes Rujukan</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.sanitation-inspections') ? ' active' : '' }}"
       href="{{ route('user.uks.sanitation-inspections.index', ['userId' => $userId]) }}">
        <i class="ri-shield-check-line"></i>
        <span>Inspeksi Sanitasi</span>
    </a>
</li>

{{-- Farmasi --}}
<li class="menu-title"><span>Farmasi & Logistik</span></li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.medicine-inventory') || isActiveUks($currentRoute, 'user.uks.medicine-logs') ? ' active' : '' }}"
       href="{{ route('user.uks.medicine-inventory.index', ['userId' => $userId]) }}">
        <i class="ri-capsule-line"></i>
        <span>Stok Obat</span>
    </a>
</li>
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveUks($currentRoute, 'user.uks.medicine-logs') ? ' active' : '' }}"
       href="{{ route('user.uks.medicine-logs.index', ['userId' => $userId]) }}">
        <i class="ri-history-line"></i>
        <span>Pemberian Obat</span>
    </a>
</li>
