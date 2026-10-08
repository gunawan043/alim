@menuallowed('tugas-koordinator-sarpras')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: KOORDINATOR SARPRAS SATUAN PENDIDIKAN (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: Tugas tambahan yang melekat di SETIAP satuan kerja.
              Membantu Unit Rumah Tangga (URT) dalam pendataan sarpras unit.
     Scope  : 1 satuan kerja (unit sekolah/madrasah tempat bertugas).
     ───────────────────────────────────────────────────────────────────────────
     Wewenang:
       • Pendataan sarpras unit
       • Pelaporan kerusakan fasilitas
       • Pengajuan pengadaan
       • Pengawasan aset unit
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    $koorSarUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, $extra);
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Koordinator Sarpras Unit</span></li>

{{-- 1. Dashboard Sarpras Unit --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.dashboard']) ? ' active' : '' }}"
       href="{{ $koorSarUrl('sarpras.user.dashboard') }}">
        <i class="ri-dashboard-2-line"></i><span>Dashboard Sarpras Unit</span>
    </a>
</li>

{{-- 2. Inventaris Aset Unit --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.aset.']) ? ' active' : '' }}"
       href="{{ $koorSarUrl('sarpras.user.aset.index') }}">
        <i class="ri-archive-2-line"></i><span>Inventaris Aset Unit</span>
    </a>
</li>

{{-- 3. Ruang & Fasilitas --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.ruang.']) ? ' active' : '' }}"
       href="{{ $koorSarUrl('sarpras.user.ruang.index') }}">
        <i class="ri-door-open-line"></i><span>Ruang & Fasilitas</span>
    </a>
</li>

{{-- 4. Lapor Kerusakan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.kerusakan.']) ? ' active' : '' }}"
       href="{{ $koorSarUrl('sarpras.user.kerusakan.index') }}">
        <i class="ri-tools-line"></i><span>Lapor Kerusakan</span>
    </a>
</li>

{{-- 5. Permintaan Pengadaan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.pengadaan.']) ? ' active' : '' }}"
       href="{{ $koorSarUrl('sarpras.user.pengadaan.index') }}">
        <i class="ri-shopping-bag-3-line"></i><span>Permintaan Pengadaan</span>
    </a>
</li>

{{-- 6. Riwayat Laporan Unit --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-history-line"></i>
        <span>Riwayat Laporan Unit <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>
@endmenuallowed
