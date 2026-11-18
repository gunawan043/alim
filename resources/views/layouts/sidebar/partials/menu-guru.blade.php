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

    $guruMenuBooks = collect();

    if ($guruMenuUserId && $guruMenuAy) {
        $guruMenuBooks = \App\Models\TeacherAdminBook::query()
            ->with(['subject:id,name', 'studyGroup:id,name'])
            ->forTeacherWorkspace($guruMenuUserId)
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

    $guruSectionOpen = static function (array $prefixes) use ($currentRoute): bool {
        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with((string) $currentRoute, $prefix)) {
                return true;
            }
        }

        return false;
    };
@endphp

<li class="menu-title"><span>Menu Guru</span></li>

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
    :open="$guruSectionOpen(['user.kurikulum.perangkat.', 'user.kurikulum.prota.', 'user.kurikulum.prosem.', 'user.kurikulum.realisasi.'])">
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
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.realisasi.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.kurikulum.realisasi.index') }}">
            <i class="ri-article-line"></i><span>Jurnal Pembelajaran</span>
        </a>
    </li>
</x-sidebar-section>

<x-sidebar-section id="guru_menu_nilai" label="Nilai" icon="ri-survey-line"
    :open="$guruSectionOpen(['user.schools.guru-mapel.', 'user.asesmen.', 'user.bank-soal.', 'user.kisi-kisi-soal.'])">
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.guru-mapel.']) ? ' active' : '' }}"
           href="{{ $guruMenuUrl('user.schools.guru-mapel.index') }}">
            <i class="ri-book-3-line"></i><span>Buku Nilai Saya</span>
        </a>
    </li>

    @if($guruMenuBooks->isNotEmpty())
        @php
            $guruMenuBookActive = isActiveAny($currentRoute, [
                'user.schools.guru-mapel.wizard',
                'user.schools.guru-mapel.w1',
                'user.schools.guru-mapel.w2',
                'user.schools.guru-mapel.w3',
                'user.schools.guru-mapel.w4',
                'user.schools.guru-mapel.w5',
                'user.schools.guru-mapel.w6',
            ]);
        @endphp
        <li class="nav-item">
            <a class="nav-link menu-link{{ $guruMenuBookActive ? ' active' : '' }}"
               href="#guru_menu_books" data-bs-toggle="collapse" role="button"
               aria-expanded="{{ $guruMenuBookActive ? 'true' : 'false' }}"
               aria-controls="guru_menu_books">
                <i class="ri-booklet-line"></i>
                <span>Mapel Saya <span class="badge bg-primary-subtle text-primary ms-1">{{ $guruMenuBooks->count() }}</span></span>
                <span class="menu-arrow"></span>
            </a>
            <div class="collapse menu-dropdown{{ $guruMenuBookActive ? ' show' : '' }}" id="guru_menu_books">
                <ul class="nav nav-sm flex-column">
                    @foreach($guruMenuBooks as $book)
                        <li class="nav-item">
                            <div class="d-flex align-items-center">
                                <a class="nav-link flex-grow-1 text-truncate{{ (string) request()->route('adminBookId') === (string) $book->id ? ' active' : '' }}"
                                   href="{{ $guruMenuUrl('user.schools.guru-mapel.w3', ['adminBookId' => $book->id]) }}"
                                   title="Input nilai {{ $book->subject?->name }} — {{ $book->studyGroup?->name }}"
                                   style="max-width: 190px;">
                                    {{ $book->subject?->name ?? 'Mapel' }} · {{ $book->studyGroup?->name ?? '-' }}
                                </a>
                                <a class="nav-link px-2 flex-shrink-0"
                                   href="{{ $guruMenuUrl('user.kurikulum.perangkat.index', ['subject_id' => $book->subject_id, 'study_group_id' => $book->study_group_id]) }}"
                                   title="Perangkat ajar {{ $book->subject?->name }} — {{ $book->studyGroup?->name }}"
                                   aria-label="Perangkat ajar {{ $book->subject?->name }}">
                                    <i class="ri-file-text-line"></i>
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
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
</x-sidebar-section>
