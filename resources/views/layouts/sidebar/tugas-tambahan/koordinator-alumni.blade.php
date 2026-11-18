@menuallowed('tugas-koordinator-alumni')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: KOORDINATOR ALUMNI (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: tugas tambahan yang melekat pada GTK satuan pendidikan.
     Wewenang: verifikasi data kelulusan, pendataan alumni, dan statistik
     kelulusan. Bukan kesiswaan harian (itu Koordinator Kesiswaan).
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $alumniUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Koordinator Alumni</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.alumni.index', 'user.alumni.show', 'user.alumni.edit']) ? ' active' : '' }}"
       href="{{ $alumniUrl('user.alumni.index') }}">
        <i class="ri-graduation-cap-line"></i><span>Data Alumni</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.alumni.statistics']) ? ' active' : '' }}"
       href="{{ $alumniUrl('user.alumni.statistics') }}">
        <i class="ri-bar-chart-box-line"></i><span>Statistik Kelulusan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.alumni.export']) ? ' active' : '' }}"
       href="{{ $alumniUrl('user.alumni.export') }}">
        <i class="ri-file-download-line"></i><span>Ekspor Data Alumni</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bulk-graduation.']) ? ' active' : '' }}"
       href="{{ $alumniUrl('user.bulk-graduation.index') }}">
        <i class="ri-user-shared-line"></i><span>Kelulusan Massal</span>
    </a>
</li>
@endmenuallowed
