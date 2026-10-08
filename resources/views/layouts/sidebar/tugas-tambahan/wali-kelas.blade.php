@menuallowed('tugas-wali-kelas')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: WALI KELAS (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: Tugas tambahan yang dilekatkan ke Guru (role Satuan Pendidikan).
     Scope  : HANYA kelas yang dia walikan (homeroom_teacher_id = $userId).
     ───────────────────────────────────────────────────────────────────────────
     Fungsi :
       • Konsolidasi nilai seluruh guru mapel di kelasnya
       • Input catatan sikap / perkembangan santri
       • Rekap absensi kelas
       • Finalisasi & cetak Rapor
       • Komunikasi dengan wali santri
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $waliUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Kelas yang diwalikan ─────────────────────────────────────────── */
    $waliKelasGroups = \App\Models\StudyGroup::withoutGlobalScope('school_context')
        ->where('homeroom_teacher_id', auth()->id())
        ->where('is_active', true)
        ->with(['gradeLevel'])
        ->orderBy('name')
        ->get();
    $isActuallyWaliKelas = $waliKelasGroups->isNotEmpty();
@endphp

<li class="menu-title"><span>Wali Kelas</span></li>

@if($isActuallyWaliKelas)
<li class="nav-item">
    <span class="nav-link text-muted" style="font-size:0.8rem">
        <i class="ri-information-line me-1"></i>
        Kelas: <strong>{{ $waliKelasGroups->pluck('name')->join(', ') }}</strong>
    </span>
</li>
@endif

{{-- Konsolidasi Nilai Kelas --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai-kelas.']) ? ' active' : '' }}"
       href="{{ $waliUrl('user.schools.nilai-kelas.index') }}">
        <i class="ri-table-line"></i><span>Konsolidasi Nilai</span>
    </a>
</li>

{{-- Rapor & Leger --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai-kelas.rapor', 'user.schools.nilai-kelas.leger']) ? ' active' : '' }}"
       href="{{ $waliUrl('user.schools.nilai-kelas.index') }}">
        <i class="ri-file-text-line"></i><span>Rapor & Leger</span>
    </a>
</li>

{{-- Rekap Absensi Kelas --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.recap']) ? ' active' : '' }}"
       href="{{ $waliUrl('user.absensi.harian.recap') }}">
        <i class="ri-bar-chart-2-line"></i><span>Rekap Absensi Kelas</span>
    </a>
</li>

{{-- Pelanggaran Kelas --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.']) ? ' active' : '' }}"
       href="{{ $waliUrl('user.violation-points.index') }}">
        <i class="ri-error-warning-line"></i><span>Pelanggaran Kelas</span>
    </a>
</li>

{{-- Prestasi Kelas --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? ' active' : '' }}"
       href="{{ $waliUrl('user.student-achievement.index') }}">
        <i class="ri-trophy-line"></i><span>Prestasi Kelas</span>
    </a>
</li>

{{-- Santri Kelas Saya --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index']) ? ' active' : '' }}"
       href="{{ $isActuallyWaliKelas
            ? $waliUrl('user.students.index', ['study_group_id' => $waliKelasGroups->first()->id])
            : $waliUrl('user.students.index') }}">
        <i class="ri-user-heart-line"></i><span>Santri Kelas Saya</span>
    </a>
</li>
@endmenuallowed
