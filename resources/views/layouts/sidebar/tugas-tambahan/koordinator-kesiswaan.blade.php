@menuallowed('tugas-koordinator-kesiswaan')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: KOORDINATOR KESISWAAN (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: Tugas tambahan yang dilekatkan ke Guru (role Satuan Pendidikan).
     Scope  : Lintas kelas & rombel di sekolah.
     ───────────────────────────────────────────────────────────────────────────
     Wewenang:
       • Tata tertib & poin kedisiplinan sekolah
       • Kegiatan OSIS / Santri
       • Penanganan kasus siswa
       • MONITORING KEHADIRAN & KETERLAMBATAN SANTRI ⭐
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    $koorKesUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Koordinator Kesiswaan</span></li>

{{-- 1. Dashboard --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-kesiswaan']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.dashboard.koordinator-kesiswaan') }}">
        <i class="ri-dashboard-3-line"></i><span>Dashboard Kesiswaan</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     KEDISIPLINAN
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Kedisiplinan</span></li>

{{-- 2. Dashboard Kedisiplinan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.dashboard']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.violation-points.dashboard') }}">
        <i class="ri-dashboard-2-line"></i><span>Dashboard Kedisiplinan</span>
    </a>
</li>

{{-- 3. Poin Pelanggaran --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.index', 'user.violation-points.show']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.violation-points.index') }}">
        <i class="ri-error-warning-line"></i><span>Poin Pelanggaran</span>
    </a>
</li>

{{-- 4. Rekap Pelanggaran --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.recap']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.violation-points.recap') }}">
        <i class="ri-bar-chart-2-line"></i><span>Rekap Pelanggaran</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     KEHADIRAN & KETERLAMBATAN
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Kehadiran & Keterlambatan</span></li>

{{-- 5. Rekap Absensi Santri --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.recap']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.absensi.harian.recap') }}">
        <i class="ri-calendar-check-line"></i><span>Rekap Absensi Santri</span>
    </a>
</li>

{{-- 6. Absensi Harian --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.index', 'user.absensi.harian.create']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.absensi.harian.index') }}">
        <i class="ri-calendar-line"></i><span>Absensi Harian</span>
    </a>
</li>

{{-- 7. Keterlambatan (Soon) --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-timer-line"></i>
        <span>Keterlambatan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     PRESTASI & KEGIATAN
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Prestasi & Kegiatan</span></li>

{{-- 8. Prestasi Santri --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.student-achievement.index') }}">
        <i class="ri-trophy-line"></i><span>Prestasi Santri</span>
    </a>
</li>

{{-- 9. Agenda Kesiswaan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.kalender-kegiatan.akademik') }}">
        <i class="ri-calendar-event-line"></i><span>Agenda Kesiswaan</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     REFERENSI & LAPORAN
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Referensi & Laporan</span></li>

{{-- 10. Tata Tertib --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-policies.']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.boarding-policies.index') }}">
        <i class="ri-scales-3-line"></i><span>Tata Tertib Sekolah</span>
    </a>
</li>

{{-- 11. Laporan Kesiswaan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.santri']) ? ' active' : '' }}"
       href="{{ $koorKesUrl('user.laporan.santri') }}">
        <i class="ri-file-chart-line"></i><span>Laporan Kesiswaan</span>
    </a>
</li>
@endmenuallowed
