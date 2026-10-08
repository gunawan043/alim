@menuallowed('tugas-koordinator-lab')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: KOORDINATOR LABORATORIUM (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: Tugas tambahan yang dilekatkan ke Guru (role Satuan Pendidikan).
     Scope  : Laboratorium sekolah (IPA, Komputer, Bahasa).
     ───────────────────────────────────────────────────────────────────────────
     Wewenang:
       • Jadwal pemakaian lab
       • Inventaris bahan/alat lab
       • Keselamatan kerja (K3) lab
       • Pengajuan pengadaan alat/bahan
     ───────────────────────────────────────────────────────────────────────────
     CATATAN: Semua route pakai sarpras.user.* (workspace user satuan kerja).
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    $koorLabUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, $extra);
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Koordinator Laboratorium</span></li>

{{-- 1. Dashboard Sarpras --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.dashboard']) ? ' active' : '' }}"
       href="{{ $koorLabUrl('sarpras.user.dashboard') }}">
        <i class="ri-dashboard-2-line"></i><span>Dashboard Lab</span>
    </a>
</li>

{{-- 2. Ruang & Laboratorium --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.ruang.']) ? ' active' : '' }}"
       href="{{ $koorLabUrl('sarpras.user.ruang.index') }}">
        <i class="ri-door-open-line"></i><span>Ruang & Laboratorium</span>
    </a>
</li>

{{-- 3. Inventaris Alat & Bahan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.aset.']) ? ' active' : '' }}"
       href="{{ $koorLabUrl('sarpras.user.aset.index') }}">
        <i class="ri-archive-2-line"></i><span>Inventaris Alat & Bahan</span>
    </a>
</li>

{{-- 4. Lapor Kerusakan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.kerusakan.']) ? ' active' : '' }}"
       href="{{ $koorLabUrl('sarpras.user.kerusakan.index') }}">
        <i class="ri-tools-line"></i><span>Lapor Kerusakan</span>
    </a>
</li>

{{-- 5. Permintaan Pengadaan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.pengadaan.']) ? ' active' : '' }}"
       href="{{ $koorLabUrl('sarpras.user.pengadaan.index') }}">
        <i class="ri-shopping-bag-3-line"></i><span>Permintaan Pengadaan</span>
    </a>
</li>

{{-- 6. Jadwal Praktikum (Soon) --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-calendar-schedule-line"></i>
        <span>Jadwal Praktikum <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>

{{-- 7. SOP Keselamatan (Soon) --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-shield-check-line"></i>
        <span>SOP Keselamatan K3 <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>
@endmenuallowed
