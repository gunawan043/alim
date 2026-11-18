@menuallowed('tugas-bendahara-sekolah')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: BENDAHARA SEKOLAH (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Dipakai bila Bendahara Sekolah tidak memiliki role "Satuan Pendidikan"
     sehingga sidebar Satuan Pendidikan tidak dirender. Bila role tersebut
     ada, sidebar ini dianggap sudah terwakili (lihat dispatcher).
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $bendaharaUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Bendahara Sekolah</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.bendahara']) ? ' active' : '' }}"
       href="{{ $bendaharaUrl('user.dashboard.bendahara') }}">
        <i class="ri-dashboard-3-line"></i><span>Dashboard Bendahara</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.keuangan']) ? ' active' : '' }}"
       href="{{ $bendaharaUrl('user.laporan.keuangan') }}">
        <i class="ri-file-chart-2-line"></i><span>Laporan Keuangan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll-slip.']) ? ' active' : '' }}"
       href="{{ $bendaharaUrl('user.payroll-slip.index') }}">
        <i class="ri-receipt-line"></i><span>Slip Gaji</span>
    </a>
</li>
@endmenuallowed
