@menuallowed('tugas-waka-pengasuhan')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: WAKA PENGASUHAN (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Varian tugas "Waka Pengasuhan" / "Jam Kajian & Waka Pengasuhan" yang
     tidak selalu terdeteksi sebagai jabatan Wakil Kepala. Arahkan ke modul
     kepengasuhan/asrama.
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $pengasuhanUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Waka Pengasuhan</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.asrama', 'user.dashboard.pengasuh']) ? ' active' : '' }}"
       href="{{ $pengasuhanUrl('user.dashboard.asrama') }}">
        <i class="ri-dashboard-3-line"></i><span>Dashboard Kepengasuhan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.index']) ? ' active' : '' }}"
       href="{{ $pengasuhanUrl('user.asrama.index') }}">
        <i class="ri-building-4-line"></i><span>Daftar Asrama</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-policies.']) ? ' active' : '' }}"
       href="{{ $pengasuhanUrl('user.boarding-policies.index') }}">
        <i class="ri-scales-3-line"></i><span>Tata Tertib Pengasuhan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
       href="{{ $pengasuhanUrl('user.kalender-kegiatan.akademik') }}">
        <i class="ri-calendar-event-line"></i><span>Agenda Kegiatan</span>
    </a>
</li>
@endmenuallowed
