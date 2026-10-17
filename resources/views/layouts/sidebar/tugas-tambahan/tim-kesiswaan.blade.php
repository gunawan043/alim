@menuallowed('tugas-tim-kesiswaan')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: TIM KESISWAAN (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: Tugas tambahan yang dilekatkan ke Guru (role Satuan Pendidikan).
     Scope  : Pendukung Koordinator Kesiswaan.
     ───────────────────────────────────────────────────────────────────────────
     Wewenang (PENDUKUNG):
       • Penegakan kedisiplinan harian (gerbang, KBM)
       • Razia kedisiplinan
       • Input pelanggaran
       • Catat keterlambatan ⭐
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    $timKesUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Tim Kesiswaan</span></li>

{{-- Info --}}
<li class="nav-item">
    <span class="nav-link text-muted" style="font-size:0.8rem">
        <i class="ri-information-line me-1"></i>
        Peran: <strong>Pendukung Kesiswaan</strong>
    </span>
</li>

{{-- 1. Input Pelanggaran --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.create']) ? ' active' : '' }}"
       href="{{ $timKesUrl('user.violation-points.create') }}">
        <i class="ri-add-circle-line"></i><span>Input Pelanggaran</span>
    </a>
</li>

{{-- 2. Daftar Pelanggaran Hari Ini --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.index']) ? ' active' : '' }}"
       href="{{ $timKesUrl('user.violation-points.index') }}">
        <i class="ri-list-check-2"></i><span>Daftar Pelanggaran</span>
    </a>
</li>

{{-- 3. Catat Keterlambatan (Soon) --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-timer-line"></i>
        <span>Catat Keterlambatan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>

{{-- 4. Absensi Harian (input) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.create']) ? ' active' : '' }}"
       href="{{ $timKesUrl('user.absensi.harian.create') }}">
        <i class="ri-calendar-check-line"></i><span>Input Absensi</span>
    </a>
</li>

{{-- 5. Rekap Absensi (view) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.recap']) ? ' active' : '' }}"
       href="{{ $timKesUrl('user.absensi.harian.recap') }}">
        <i class="ri-bar-chart-2-line"></i><span>Rekap Absensi</span>
    </a>
</li>

{{-- 6. Agenda Kegiatan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
       href="{{ $timKesUrl('user.kalender-kegiatan.akademik') }}">
        <i class="ri-calendar-event-line"></i><span>Agenda Kegiatan</span>
    </a>
</li>

{{-- 7. Piket Kesiswaan (Soon) --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-calendar-todo-line"></i>
        <span>Piket Kesiswaan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>
@endmenuallowed
