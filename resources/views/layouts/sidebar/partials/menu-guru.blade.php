@php
    /*
     * Menu Guru lintas-role.
     *
     * Dipakai untuk GTK yang mengajar tetapi role utamanya bukan "Satuan
     * Pendidikan" (mis. Pimpinan, Asrama, Departemen Tahfidz). Sidebar role
     * mereka tetap tampil, dan menu mengajar tetap tersedia lengkap sampai
     * daftar mapel/kelas yang diampu.
     */
    $guruMenuUserId = $userId ?? auth()->id();
    $guruMenuAy = \App\Models\AcademicYear::query()->where('is_active', true)->first();

    // Sekolah tempat guru ini mengajar. Untuk GTK yang ditempatkan di luar
    // satuan pendidikan, konteksnya diambil dari SK mengajar — bukan asrama.
    $guruMenuSchoolId = \App\Support\TeacherWorkspace::preferredSchoolId($guruMenuUserId);

    $guruMenuBooks = collect();

    if ($guruMenuUserId && $guruMenuAy) {
        $guruMenuBooks = \App\Models\TeacherAdminBook::query()
            ->with(['subject:id,name', 'studyGroup:id,name'])
            ->forTeacherWorkspace($guruMenuUserId)
            ->when($guruMenuSchoolId, fn ($q, $sid) => $q->where('school_id', $sid))
            ->where('is_active', true)
            ->where('academic_year_id', $guruMenuAy->id)
            ->where('semester', $guruMenuAy->semester)
            ->get()
            ->unique(fn ($book) => $book->subject_id.'|'.$book->study_group_id.'|'.$book->academic_year_id.'|'.$book->semester)
            ->sortBy(fn ($book) => ($book->subject?->name ?? '').'|'.($book->studyGroup?->name ?? ''))
            ->values();
    }

    $guruMenuUrl = function (string $routeName, array $extra = []) use ($guruMenuUserId) {
        try {
            return route($routeName, array_merge(['userId' => $guruMenuUserId], $extra));
        } catch (\Throwable) {
            return '#';
        }
    };

    // Guru halaqah tahfidz: buku nilainya diikat ke halaqah, bukan mapel × rombel.
    $guruMenuHalaqah = $guruMenuUserId && \App\Models\TahfidzGroup::query()
        ->where('is_active', true)
        ->where(fn ($q) => $q
            ->where('teacher_id', $guruMenuUserId)
            ->orWhere('coordinator_id', $guruMenuUserId)
            ->orWhereHas('teachers', fn ($t) => $t->where('user_id', $guruMenuUserId)->where('is_active', true)))
        ->exists();

    $guruSectionOpen = static function (array $prefixes) use ($currentRoute): bool {
        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with((string) $currentRoute, $prefix)) {
                return true;
            }
        }

        return false;
    };
@endphp

<li class="menu-title"><span>Mengajar · Guru Mapel</span></li>

@php
    $guruMenuSchoolName = $guruMenuSchoolId
        ? \App\Models\School::query()->whereKey($guruMenuSchoolId)->value('name')
        : null;
@endphp

@if($guruMenuSchoolName)
    <li class="nav-item">
        <span class="nav-link px-3 py-1 text-muted small">
            <i class="ri-school-line me-1"></i>{{ $guruMenuSchoolName }}
        </span>
    </li>
@endif

<x-sidebar-section id="guru_menu_absensi" label="Absensi" icon="ri-calendar-check-line"
    :open="$guruSectionOpen(['user.jadwal-kbm.for-teacher', 'user.teacher-qr.', 'user.absensi.harian.'])">
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jadwal-kbm.for-teacher']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.jadwal-kbm.for-teacher', ['teacherId' => $guruMenuUserId]) }}">
            <i class="ri-calendar-schedule-line"></i><span>Jadwal Mengajar Saya</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.scan']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.teacher-qr.scan') }}">
            <i class="ri-qr-scan-2-line"></i><span>Scan QR Kehadiran</span>
        </a>
    </li>
    @if(canPermission('teacher-attendance_view'))
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.history']) ? ' active' : '' }}"
               href="{{ $guruMenuUrl('user.teacher-qr.history') }}">
                <i class="ri-history-line"></i><span>Riwayat Kehadiran Saya</span>
            </a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.absensi.harian.index') }}">
            <i class="ri-calendar-check-line"></i><span>Absensi Peserta Didik</span>
        </a>
    </li>
</x-sidebar-section>

<x-sidebar-section id="guru_menu_kurikulum" label="Kurikulum" icon="ri-book-open-line"
    :open="$guruSectionOpen(['user.pekan-efektif.', 'user.kurikulum.cp.', 'user.kurikulum.tp.', 'user.kurikulum.atp.', 'user.kurikulum.perangkat.', 'user.kurikulum.prota.', 'user.kurikulum.prosem.', 'user.kurikulum.ai.', 'user.kurikulum.realisasi.'])">
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.pekan-efektif.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.pekan-efektif.index') }}">
            <i class="ri-calendar-todo-line"></i><span>Pekan Efektif</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.cp.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.kurikulum.cp.index') }}">
            <i class="ri-file-list-line"></i><span>Capaian Pembelajaran</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.tp.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.kurikulum.tp.index') }}">
            <i class="ri-list-check-2"></i><span>Tujuan Pembelajaran</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.atp.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.kurikulum.atp.index') }}">
            <i class="ri-flow-chart"></i><span>ATP</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.perangkat.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.kurikulum.perangkat.index') }}">
            <i class="ri-file-text-line"></i><span>Perangkat &amp; Bahan Ajar</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.prota.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.kurikulum.prota.index') }}">
            <i class="ri-calendar-2-line"></i><span>Program Tahunan</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.prosem.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.kurikulum.prosem.index') }}">
            <i class="ri-calendar-todo-line"></i><span>Program Semester</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.ai.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.kurikulum.ai.index') }}">
            <i class="ri-sparkling-2-line"></i><span>AI Assistant (RPM &amp; Soal)</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.realisasi.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.kurikulum.realisasi.index') }}">
            <i class="ri-article-line"></i><span>Jurnal Pembelajaran</span>
        </a>
    </li>
</x-sidebar-section>

<x-sidebar-section id="guru_menu_nilai" label="Nilai &amp; Asesmen" icon="ri-survey-line"
    :open="$guruSectionOpen(['user.schools.guru-mapel.', 'user.tahfidz.penilaian.', 'user.asesmen.', 'user.bank-soal.', 'user.kisi-kisi-soal.', 'user.paket-soal.', 'user.ljk.'])">
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.guru-mapel.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.schools.guru-mapel.index') }}">
            <i class="ri-book-3-line"></i><span>Buku Nilai Saya</span>
        </a>
    </li>
    @if($guruMenuHalaqah)
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.penilaian.']) ? ' active' : '' }}"
               href="{{ $guruMenuUrl('user.tahfidz.penilaian.index') }}">
                <i class="ri-file-list-3-line"></i><span>Buku Nilai Halaqah</span>
            </a>
        </li>
    @endif
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asesmen.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.asesmen.index') }}">
            <i class="ri-file-list-3-line"></i><span>Pusat Asesmen</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bank-soal.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.bank-soal.index') }}">
            <i class="ri-archive-2-line"></i><span>Bank Soal</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.paket-soal.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.paket-soal.index') }}">
            <i class="ri-stack-line"></i><span>Paket Soal</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.ljk.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.ljk.index') }}">
            <i class="ri-scan-2-line"></i><span>LJK</span>
        </a>
    </li>
</x-sidebar-section>
