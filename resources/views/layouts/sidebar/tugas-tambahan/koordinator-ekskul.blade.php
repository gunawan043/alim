@menuallowed('tugas-koordinator-ekskul')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: KOORDINATOR EKSTRAKURIKULER (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: Tugas tambahan yang dilekatkan ke Guru (role Satuan Pendidikan).
     Scope  : Semua ekskul di sekolah.
     ───────────────────────────────────────────────────────────────────────────
     Wewenang:
       • Perencanaan & evaluasi seluruh ekskul
       • Alokasi anggaran ekskul
       • Plotting pembina ekskul
       • Master ekskul
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    $koorEksUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Koordinator Ekstrakurikuler</span></li>

{{-- 1. Daftar Ekstrakurikuler --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.ekstrakurikuler.index', 'waka.ekstrakurikuler.show']) ? ' active' : '' }}"
       href="{{ route('waka.ekstrakurikuler.index') }}">
        <i class="ri-basketball-line"></i><span>Daftar Ekstrakurikuler</span>
    </a>
</li>

{{-- 2. Tambah Ekskul --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.ekstrakurikuler.create']) ? ' active' : '' }}"
       href="{{ route('waka.ekstrakurikuler.create') }}">
        <i class="ri-add-circle-line"></i><span>Tambah Ekstrakurikuler</span>
    </a>
</li>

{{-- 3. Prestasi Ekskul --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? ' active' : '' }}"
       href="{{ $koorEksUrl('user.student-achievement.index') }}">
        <i class="ri-trophy-line"></i><span>Prestasi Ekskul</span>
    </a>
</li>

{{-- 4. Agenda Kegiatan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
       href="{{ $koorEksUrl('user.kalender-kegiatan.akademik') }}">
        <i class="ri-calendar-event-line"></i><span>Agenda Kegiatan</span>
    </a>
</li>

{{-- 5. Laporan Ekskul --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.santri']) ? ' active' : '' }}"
       href="{{ $koorEksUrl('user.laporan.santri') }}">
        <i class="ri-file-chart-line"></i><span>Laporan Ekskul</span>
    </a>
</li>
@endmenuallowed
