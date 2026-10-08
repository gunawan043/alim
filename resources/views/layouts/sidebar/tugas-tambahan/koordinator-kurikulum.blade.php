@menuallowed('tugas-koordinator-kurikulum')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: KOORDINATOR KURIKULUM (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: Tugas tambahan yang dilekatkan ke Guru (role Satuan Pendidikan).
     Scope  : Lintas kelas & mapel di sekolah.
     ───────────────────────────────────────────────────────────────────────────
     Wewenang:
       • Rancang Struktur Kurikulum (CP/Alokasi Jam)
       • Kelola Plotting Guru Mengajar
       • Susun Jadwal Pelajaran
       • Tetapkan Kalender Akademik
       • Atur Bobot Penilaian
       • MONITORING KEHADIRAN GURU PER JAM PELAJARAN ⭐
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    /* ── Work Unit user ──────────────────────────────────────────────── */
    $kurikulumWorkUnits = \App\Models\GtkWorkUnit::with('workUnit')
        ->where('user_id', auth()->id())
        ->whereHas('workUnit', fn ($q) => $q->where('is_active', true))
        ->get();
    $kurikulumPrimary   = $kurikulumWorkUnits->where('is_primary', true)->first() ?? $kurikulumWorkUnits->first();
    $kurikulumWorkUnitId = $kurikulumPrimary?->workUnit?->id;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $koorKurUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
    $kurWorkUnitUrl = function (string $routeName, array $extra = []) use ($kurikulumWorkUnitId) {
        if (! $kurikulumWorkUnitId) return '#';
        try {
            return route($routeName, array_merge([
                'userId'     => auth()->id(),
                'workUnitId' => $kurikulumWorkUnitId,
            ], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 1 — KOORDINATOR KURIKULUM
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Koordinator Kurikulum</span></li>

{{-- 1. Dashboard --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-kurikulum']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.dashboard.koordinator-kurikulum') }}">
        <i class="ri-dashboard-3-line"></i><span>Dashboard Kurikulum</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — STRUKTUR & KURIKULUM
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Struktur & Kurikulum</span></li>

{{-- Tahun Ajaran --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.academic-years.']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.academic-years.index') }}">
        <i class="ri-calendar-2-line"></i><span>Tahun Ajaran</span>
    </a>
</li>

{{-- Tingkat & Kelas --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.grade-levels.']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.grade-levels.index') }}">
        <i class="ri-stack-line"></i><span>Tingkat & Kelas</span>
    </a>
</li>

{{-- Mata Pelajaran --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.subjects.']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.subjects.index') }}">
        <i class="ri-book-2-line"></i><span>Mata Pelajaran</span>
    </a>
</li>

{{-- Rombel --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.study-groups.']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.study-groups.index') }}">
        <i class="ri-group-2-line"></i><span>Rombel</span>
    </a>
</li>

{{-- KKTP --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.kktp.']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.schools.kktp.index') }}">
        <i class="ri-ruler-2-line"></i><span>KKTP</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — PLOTTING & JADWAL
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Plotting & Jadwal</span></li>

{{-- Plotting Guru Mengajar --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teaching-assignments.']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.teaching-assignments.index') }}">
        <i class="ri-user-star-line"></i><span>Plotting Guru Mengajar</span>
    </a>
</li>

{{-- Tugas Tambahan Guru --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.satuan-kerja.other-tasks', 'user.other-teacher-tasks.']) ? ' active' : '' }}"
       href="{{ $kurWorkUnitUrl('user.schools.satuan-kerja.other-tasks') }}">
        <i class="ri-user-settings-line"></i><span>Tugas Tambahan Guru</span>
    </a>
</li>

{{-- Jadwal Pelajaran --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jadwal-kbm.']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.jadwal-kbm.index') }}">
        <i class="ri-calendar-schedule-line"></i><span>Jadwal Pelajaran</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — KALENDER & AGENDA
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Kalender & Agenda</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? ' active' : '' }}"
       href="#koor_kur_kaldik" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? 'true' : 'false' }}"
       aria-controls="koor_kur_kaldik">
        <i class="ri-calendar-event-line"></i><span>Kalender Akademik</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? ' show' : '' }}"
         id="koor_kur_kaldik">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
                   href="{{ $koorKurUrl('user.kaldik.index') }}">
                    Kalender Pendidikan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
                   href="{{ $koorKurUrl('user.kalender-kegiatan.akademik') }}">
                    Agenda Kegiatan
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — PENILAIAN & EVALUASI
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Penilaian & Evaluasi</span></li>

{{-- Bank Soal & Kisi-Kisi --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bank-soal.', 'user.kisi-kisi-soal.', 'user.paket-soal.']) ? ' active' : '' }}"
       href="#koor_kur_soal" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.bank-soal.', 'user.kisi-kisi-soal.', 'user.paket-soal.']) ? 'true' : 'false' }}"
       aria-controls="koor_kur_soal">
        <i class="ri-file-list-3-line"></i><span>Bank Soal & Evaluasi</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.bank-soal.', 'user.kisi-kisi-soal.', 'user.paket-soal.']) ? ' show' : '' }}"
         id="koor_kur_soal">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.kisi-kisi-soal.']) ? ' active' : '' }}"
                   href="{{ $koorKurUrl('user.kisi-kisi-soal.index') }}">
                    Kisi-Kisi Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.bank-soal.']) ? ' active' : '' }}"
                   href="{{ $koorKurUrl('user.bank-soal.index') }}">
                    Bank Soal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.paket-soal.']) ? ' active' : '' }}"
                   href="{{ $koorKurUrl('user.paket-soal.index') }}">
                    Paket Soal Sumatif
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- Validasi Perangkat Ajar --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-checkbox-circle-line"></i>
        <span>Validasi Perangkat Ajar <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — MONITORING KEHADIRAN (WEWENANG PENTING)
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Monitoring Kehadiran</span></li>

{{-- Dashboard Kehadiran Guru --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.waka-dashboard']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.teacher-qr.waka-dashboard') }}">
        <i class="ri-dashboard-2-line"></i><span>Kehadiran Guru Per Jam</span>
    </a>
</li>

{{-- Riwayat Kehadiran Guru --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.history']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.teacher-qr.history') }}">
        <i class="ri-history-line"></i><span>Riwayat Kehadiran Guru</span>
    </a>
</li>

{{-- Rekap Pergantian Jam --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kehadiran.pergantian-jam']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.kehadiran.pergantian-jam') }}">
        <i class="ri-refresh-line"></i><span>Rekap Pergantian Jam</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — SUPERVISI & LAPORAN
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Supervisi & Laporan</span></li>

{{-- Supervisi Kinerja Guru --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kinerja.']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.kinerja.index') }}">
        <i class="ri-line-chart-line"></i><span>Supervisi Kinerja Guru</span>
    </a>
</li>

{{-- Laporan Kurikulum --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.gtk']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.laporan.gtk') }}">
        <i class="ri-file-chart-line"></i><span>Laporan Kurikulum</span>
    </a>
</li>

{{-- Dokumen ISO --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
       href="{{ $koorKurUrl('user.dokumen-iso.index') }}">
        <i class="ri-folder-shield-2-line"></i><span>Dokumen Kurikulum (ISO)</span>
    </a>
</li>
@endmenuallowed
