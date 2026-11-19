{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: SATUAN PENDIDIKAN
     ───────────────────────────────────────────────────────────────────────────
     Urutan menu mengikuti prioritas peran pengguna:

       1. Menu                 → Dashboard, Todo, Profil, Sekolah, Notifikasi, Approval
       2. MENU GURU            → dipisah per topik: Absensi, Kurikulum, Nilai, Jurnal
       3. WALI KELAS           → hanya bila benar-benar wali kelas
       4. MANAJEMEN SEKOLAH    → rombel, kesiswaan, nilai, akademik, GTK, dst.

     Setiap topik hanya dirender SEKALI walau user memegang beberapa jabatan,
     dan menu guru selalu berada di atas menu jabatan/koordinator.

     Menu lintas divisi (Bahasa, Tahfidz) hanya untuk Koordinator Bahasa,
     Koordinator Tahfidz, Waka, dan Kepala Satuan Pendidikan — bukan TU.
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Context ─────────────────────────────────────────────────────── */
    $user = auth()->user();
    $currentRoute = $currentRoute ?? (request()->route() ? request()->route()->getName() : '');
    $userId = $userId ?? $user?->id;

    /* ── Sekolah user (WAJIB — semua query difilter ke sini) ─────────── */
    $userWorkUnits = \App\Models\GtkWorkUnit::with('workUnit.school')
        ->where('user_id', $userId)
        ->whereHas('workUnit', fn ($q) => $q->where('is_active', true))
        ->get();
    $primaryWorkUnit   = $userWorkUnits->where('is_primary', true)->first() ?? $userWorkUnits->first();
    $primaryWorkUnitId = $primaryWorkUnit?->workUnit?->id;
    $sekolah           = $primaryWorkUnit?->workUnit?->school;

    // Rangkap jabatan: jika user adalah kepala sekolah tetapi satuan kerja
    // primernya bukan unit sekolah (mis. Kepala Asrama), pakai sekolah yang
    // dipimpinnya sebagai konteks sidebar.
    $headedSchool = \App\Models\School::query()
        ->where('principal_user_id', $userId)
        ->where('is_active', true)
        ->first();

    if ($headedSchool) {
        $sekolah = $headedSchool;
    }

    $sekolahId = $sekolah?->id;

    /* ── Tahun ajaran aktif (satu sumber — bukan perkiraan kalender) ──── */
    $activeAcademicYearId = \App\Models\AcademicYear::where('is_active', true)->value('id');

    /* ── Pengingat kandidat Mumtaz Murtafi' dari rapor terbit ─────────── */
    $mumtazPendingCount = $sekolahId
        ? \App\Models\MumtazMurtafiCandidate::query()
            ->where('school_id', $sekolahId)
            ->where('status', \App\Models\MumtazMurtafiCandidate::STATUS_PENDING)
            ->count()
        : \App\Models\MumtazMurtafiCandidate::query()
            ->where('status', \App\Models\MumtazMurtafiCandidate::STATUS_PENDING)
            ->count();

    /* ── Kelas yang diwalikan user (untuk Wali Kelas) — TA aktif saja ─── */
    $waliKelasGroups = $sekolahId
        ? \App\Models\StudyGroup::withoutGlobalScope('school_context')
            ->where('school_id', $sekolahId)
            ->where('homeroom_teacher_id', $userId)
            ->where('is_active', true)
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->with(['gradeLevel'])
            ->orderBy('name')
            ->get()
        : collect();
    $isActuallyWaliKelas = $waliKelasGroups->isNotEmpty();

    /* ── Semua rombel di sekolah (untuk TU/Kepala/Wakil) — TA AKTIF saja ─ */
    $sidebarStudyGroups = $sekolahId
        ? \App\Models\StudyGroup::withoutGlobalScope('school_context')
            ->where('school_id', $sekolahId)
            ->where('is_active', true)
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->with(['gradeLevel', 'homeroomTeacher'])
            ->orderedByGrade()
            ->get()
        : collect();

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepala       = $hasJabatan('Kepala Satuan Pendidikan')
        || $hasTugas('Kepala Satuan Pendidikan')
        || $headedSchool !== null;
    // "Waka Kurikulum", "Waka Kesiswaan", "Waka Sarpras" adalah wakil kepala;
    // jabatan ini sering tersimpan sebagai tugas tambahan, bukan jabatan utama.
    $isWakil        = $hasJabatan('Wakil Kepala Satuan Pendidikan')
        || $hasTugas('Wakil Kepala Satuan Pendidikan')
        || $hasTugas('Waka');
    $isGuruUmum     = $hasJabatan('Guru Umum');
    $isGuruAgama    = $hasJabatan('Guru Agama');
    $isGuruHadits   = $hasJabatan('Guru Hadits');
    $isGuruArab     = $hasJabatan('Guru Bahasa Arab');
    $isGuruInggris  = $hasJabatan('Guru Bahasa Inggris');
    $isGuruTahfidz  = $hasJabatan('Guru Tahfidz');
    $isKepalaTU     = $hasJabatan('Kepala Tata Usaha')
        || $hasJabatan('Kepala Staf Tata Usaha')
        || $hasTugas('Kepala Tata Usaha')
        || $hasTugas('Kepala Staf Tata Usaha');
    $isTU           = $hasJabatan('Tata Usaha')
        || $hasJabatan('Staf Tata Usaha')
        || (! $isKepalaTU && ($hasTugas('Staf Tata Usaha') || $hasTugas('Tata Usaha')));
    // Bendahara Sekolah dapat berupa jabatan struktural maupun tugas tambahan.
    $isBendahara    = $hasJabatan('Bendahara Sekolah') || $hasTugas('Bendahara Sekolah');
    $isKoorKurik    = $hasJabatan('Koordinator Kurikulum') || $hasTugas('Koordinator Kurikulum');
    $isKoorKesiswa  = $hasJabatan('Koordinator Kesiswaan') || $hasTugas('Koordinator Kesiswaan');
    // Koordinator Alumni mengurus data kelulusan/alumni, bukan kesiswaan harian.
    $isKoorAlumni   = $hasJabatan('Koordinator Alumni') || $hasTugas('Koordinator Alumni');
    $isKoorEkskul   = $hasJabatan('Koordinator Ekstrakurikuler')
        || $hasTugas('Koordinator Ekstrakurikuler');

    // Guru TIDAK hanya ditentukan dari teks jabatan: banyak GTK menjabat
    // struktural (mis. Wakil Kepala) tetapi tetap mengajar. Karena itu data
    // penugasan mengajar (SK aktif) juga dipakai sebagai bukti.
    $hasTeachingAssignments = $userId
        ? \App\Models\TeachingAssignment::query()
            ->where('teacher_id', $userId)
            ->where('status', 'active')
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->exists()
        : false;

    // Buku nilai juga bukti mengajar — terutama untuk wali kelas/guru yang
    // bukunya dibuat dari struktur tingkat tanpa SK mengajar terpisah.
    $hasTeacherBooks = $userId
        ? \App\Models\TeacherAdminBook::query()
            ->forTeacherWorkspace($userId)
            ->where('is_active', true)
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->exists()
        : false;

    // Semua varian jabatan guru (GURU KELAS, GURU PAUD, GURU MAPEL, ...) dihitung guru.
    $isGuru         = $isGuruUmum || $isGuruAgama || $isGuruHadits || $isGuruArab || $isGuruInggris || $isGuruTahfidz
        || $hasJabatan('Guru')
        || $hasTeachingAssignments
        || $hasTeacherBooks;
    $isAdminTU      = $isKepalaTU || $isTU;
    $isStruktural   = $isKepala || $isWakil;

    /* ── Pencocokan tugas tambahan (nama bebas, mis. "Wali Kelas 7A") ── */
    $tugasList = $userTugas ?? [];
    $tugasMatches = static function (string $needle) use ($tugasList): bool {
        foreach ($tugasList as $tugas) {
            if (str_contains(strtolower((string) $tugas), strtolower($needle))) {
                return true;
            }
        }

        return false;
    };

    /* ── Tugas Tambahan ──────────────────────────────────────────────── */
    // Wali kelas ditentukan oleh penugasan rombel (sumber utama), bukan teks tugas.
    $isWaliKelas       = $isActuallyWaliKelas;
    $isTimKurikulum    = $hasTugas('Tim Kurikulum');
    $isTimKesiswaan    = $hasTugas('Tim Kesiswaan');
    $isGuruBK          = $hasJabatan('Guru BK') || $hasJabatan('Guru Bimbingan Konseling')
        || $hasTugas('Guru BK') || $hasTugas('Guru Bimbingan Konseling')
        || $hasTugas('Koordinator BK') || $hasTugas('Konselor');
    $isPembinaEkskul   = $hasTugas('Pembina Ekstrakurikuler');
    $isKoorLab         = $hasTugas('Koordinator Laboratorium');
    $isKoorSarpras     = $hasTugas('Koordinator Sarpras Satuan Pendidikan');
    $isKoorGuruUmum    = $hasTugas('Koordinator Guru Umum');
    $isKoorGuruAgama   = $hasTugas('Koordinator Guru Agama');
    $isKoorGuruHadits  = $hasTugas('Koordinator Guru Hadits');
    $isKoorGuruArab    = $hasTugas('Koordinator Guru Bahasa Arab');
    $isKoorGuruTahfidz = $hasTugas('Koordinator Guru Tahfidz');

    $isKoorMapel       = $isKoorGuruUmum || $isKoorGuruAgama || $isKoorGuruHadits || $isKoorGuruArab || $isKoorGuruTahfidz;

    // Koordinator/tim bukan "guru murni": menu manajerial mereka tidak boleh
    // tertutup oleh section Menu Guru. Sebelumnya hanya tiga tugas utama yang
    // dihitung sehingga koordinator mapel/lab/sarpras diperlakukan sebagai guru.
    $isKoordinator  = $isKoorKurik || $isKoorKesiswa || $isKoorEkskul
        || $isKoorMapel || $isKoorLab || $isKoorSarpras
        || $isTimKurikulum || $isTimKesiswaan;

    /* ── Koordinator lintas divisi ───────────────────────────────────── */
    $isKoorPerpus = $hasJabatan('Koordinator Perpustakaan') || $hasJabatan('Kepala Perpustakaan')
        || $tugasMatches('koordinator perpustakaan') || $tugasMatches('kepala perpustakaan');

    $isKoorBahasa = $hasJabatan('Koordinator Bahasa') || $hasJabatan('Koordinator Bahasa Arab')
        || $hasJabatan('Koordinator Bahasa Inggris')
        || $tugasMatches('koordinator bahasa') || $tugasMatches('coordinator bahasa');

    $isKoorTahfidz = $isKoorGuruTahfidz
        || $hasJabatan('Koordinator Tahfidz') || $hasJabatan('Koordinator Tahfidz Al-Qur\'an')
        || $tugasMatches('koordinator tahfidz');

    /* ── Bedakan jabatan struktural vs tugas tambahan (untuk label menu) ─
       PENTING: `$hasJabatan()` membaca juga tugas tambahan dari SK/penugasan
       operasional. Untuk memisahkan topi "Jabatan" dan "Tugas Tambahan",
       struktur jabatan harus dibaca dari kepegawaian/penempatan/penugasan
       struktural saja — bukan dari daftar tugas. */
    $structuralJabatanRows = collect();

    if ($user) {
        $structuralJabatanRows->push($user->employment?->jabatan);

        try {
            foreach ($user->employments as $row) {
                $structuralJabatanRows->push($row?->jabatan);
                $structuralJabatanRows->push($row?->jabatanRel?->nama);
            }
        } catch (\Throwable) {
            // Relasi kepegawaian tidak tersedia.
        }

        try {
            foreach (\App\Models\GtkWorkUnit::with('position')->where('user_id', $userId)->get() as $placement) {
                $structuralJabatanRows->push($placement->jabatan);
                $structuralJabatanRows->push($placement->position?->name);
            }
        } catch (\Throwable) {
            // Tabel penempatan belum tersedia.
        }

        try {
            foreach (\App\Models\StructuralAssignment::with('position')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->get() as $assignment) {
                $structuralJabatanRows->push($assignment->position?->name);
            }
        } catch (\Throwable) {
            // Tabel penugasan struktural belum tersedia.
        }
    }

    $structuralJabatanText = mb_strtolower($structuralJabatanRows->filter()->implode(' '));
    $hasStructuralJabatan = static function (string $name) use ($structuralJabatanText): bool {
        $needle = mb_strtolower(trim($name));

        return $needle !== '' && str_contains($structuralJabatanText, $needle);
    };

    $jabKoorKurik   = $hasStructuralJabatan('koordinator kurikulum');
    $jabKoorKesiswa = $hasStructuralJabatan('koordinator kesiswaan');
    $jabKoorEkskul  = $hasStructuralJabatan('koordinator ekstrakurikuler');
    $jabKoorPerpus  = $hasStructuralJabatan('koordinator perpustakaan') || $hasStructuralJabatan('kepala perpustakaan');
    $jabKoorBahasa  = $hasStructuralJabatan('koordinator bahasa') || $hasStructuralJabatan('koordinator bahasa arab')
        || $hasStructuralJabatan('koordinator bahasa inggris');
    $jabKoorTahfidz = $hasStructuralJabatan('koordinator tahfidz') || $hasStructuralJabatan('koordinator tahfidz al-qur\'an');
    $jabBendahara   = $hasStructuralJabatan('bendahara sekolah');

    /*
     * ── Topi peran (Mengajar / Walkes / Jabatan / Tugas Tambahan) ─────
     * Dipakai untuk memisahkan menu agar tidak bertumpuk saat GTK merangkap.
     * Waka/KTU/koordinator yang juga mengajar hanya melihat daftar mapelnya
     * di bagian Mengajar; menu luasnya berada di Jabatan/Tugas Tambahan.
     */
    $hasStructuralPosition = $isKepala || $isWakil || $isKepalaTU || $isTU
        || $jabKoorKurik || $jabKoorKesiswa || $jabKoorEkskul
        || $jabKoorPerpus || $jabKoorBahasa || $jabKoorTahfidz
        || $jabBendahara;

    $structuralLabels = collect();
    if ($isKepala) {
        $structuralLabels->push('Kepala Satuan Pendidikan');
    }
    if ($isWakil) {
        $structuralLabels->push('Wakil Kepala');
    }
    if ($isKepalaTU) {
        $structuralLabels->push('Kepala Tata Usaha');
    }
    if ($isTU) {
        $structuralLabels->push('Tata Usaha');
    }
    if ($jabBendahara) {
        $structuralLabels->push('Bendahara Sekolah');
    }
    foreach ([
        'Koordinator Kurikulum' => $jabKoorKurik,
        'Koordinator Kesiswaan' => $jabKoorKesiswa,
        'Koordinator Ekstrakurikuler' => $jabKoorEkskul,
        'Koordinator Perpustakaan' => $jabKoorPerpus,
        'Koordinator Bahasa' => $jabKoorBahasa,
        'Koordinator Tahfidz' => $jabKoorTahfidz,
    ] as $label => $active) {
        if ($active) {
            $structuralLabels->push($label);
        }
    }
    $structuralLabel = $structuralLabels->unique()->take(3)->join(', ');

    $additionalTaskLabels = collect();
    foreach ([
        'Koordinator Kurikulum' => $isKoorKurik && ! $jabKoorKurik,
        'Koordinator Kesiswaan' => $isKoorKesiswa && ! $jabKoorKesiswa,
        'Koordinator Ekstrakurikuler' => $isKoorEkskul && ! $jabKoorEkskul,
        'Koordinator Mapel' => $isKoorMapel,
        'Tim Kurikulum' => $isTimKurikulum,
        'Tim Kesiswaan' => $isTimKesiswaan,
        'Koordinator Laboratorium' => $isKoorLab,
        'Koordinator Sarpras' => $isKoorSarpras,
        'Guru BK' => $isGuruBK,
        'Pembina Ekstrakurikuler' => $isPembinaEkskul,
        'Koordinator Alumni' => $isKoorAlumni,
        'Koordinator Perpustakaan' => $isKoorPerpus && ! $jabKoorPerpus,
        'Koordinator Bahasa' => $isKoorBahasa && ! $jabKoorBahasa,
        'Koordinator Tahfidz' => $isKoorTahfidz && ! $jabKoorTahfidz,
        'Bendahara Sekolah' => $isBendahara && ! $jabBendahara,
    ] as $label => $active) {
        if ($active) {
            $additionalTaskLabels->push($label);
        }
    }
    $additionalTaskLabels = $additionalTaskLabels->unique()->values();
    $hasAdditionalTask = $additionalTaskLabels->isNotEmpty();
    $additionalTaskLabel = $additionalTaskLabels->take(3)->join(', ');


    $koorLabel = static function (string $nama, bool $isJabatan): string {
        return ($isJabatan ? 'Jabatan · ' : 'Tugas Tambahan · ').$nama;
    };

    /* ── Supervisor yang ditetapkan pada Pengaturan Supervisor ───────── */
    $supervisorProfileId = $sekolahId
        ? \App\Models\GtkProfile::where('user_id', $userId)->value('id')
        : null;
    $isConfiguredSupervisor = $supervisorProfileId && $sekolahId
        && \App\Models\SupervisiPengaturan::query()
            ->where('school_id', $sekolahId)
            ->where('supervisor_gtk_id', $supervisorProfileId)
            ->where('is_active', true)
            ->when($activeAcademicYearId, fn ($q) => $q->where('academic_year_id', $activeAcademicYearId))
            ->exists();

    /* ── Absen Manual — restricted ───────────────────────────────────── */
    $canManualAbsen = ($isAdminTU || $isStruktural || $isKoordinator)
        && (function_exists('canPermission')
            ? canPermission('teacher-attendance_manual')
            : (method_exists(auth()->user(), 'hasPermissionTo') && auth()->user()->hasPermissionTo('teacher-attendance_manual'))
        );

    /* ── Status buka/tutup section sidebar ───────────────────────────── */
    // Section dibuka otomatis bila route aktif berada di dalamnya, supaya
    // pengguna tidak perlu membuka manual saat berpindah halaman.
    $sectionOpen = static function (array $prefixes) use ($currentRoute): bool {
        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with((string) $currentRoute, $prefix)) {
                return true;
            }
        }

        return false;
    };

    /* ── Opsi dashboard sesuai jabatan/tugas ─────────────────────────── */
    // Dashboard per-jabatan sudah dikonsolidasi: nama route lama kini hanya
    // me-redirect ke satu halaman (lihat routes/web.php). Tanpa pemetaan ini,
    // user yang merangkap jabatan melihat beberapa entri "Dashboard" yang
    // semuanya mendarat di halaman yang sama.
    $unifiedDashboardRoute = 'user.dashboard.satuan-pendidikan';
    $legacyDashboardRoutes = [
        'user.dashboard.kepala-satuan-pendidikan',
        'user.dashboard.wakil-kepala',
        'user.dashboard.guru',
        'user.dashboard.ka-tata-usaha',
        'user.dashboard.staf-tata-usaha',
        'user.dashboard.bendahara',
        'user.dashboard.koordinator-kurikulum',
        'user.dashboard.koordinator-kesiswaan',
        'user.dashboard.koordinator-ekskul',
        'user.dashboard.wali-kelas',
        'user.dashboard.koordinator-guru',
        'user.dashboard.koordinator-sarpras',
    ];

    $dashboardOptions = [];
    $addDashboard = function (bool $condition, string $routeName, string $label, string $icon) use (&$dashboardOptions, $legacyDashboardRoutes, $unifiedDashboardRoute): void {
        if (! $condition) {
            return;
        }

        $resolved = in_array($routeName, $legacyDashboardRoutes, true) ? $unifiedDashboardRoute : $routeName;

        // Satu tujuan = satu entri. Label pertama dipertahankan karena urutan
        // pemanggilan sudah mengikuti prioritas jabatan.
        foreach ($dashboardOptions as $existing) {
            if ($existing['routeName'] === $resolved) {
                return;
            }
        }

        $dashboardOptions[] = ['routeName' => $resolved, 'label' => $label, 'icon' => $icon];
    };

    $addDashboard($isKepala, 'user.dashboard.kepala-satuan-pendidikan', 'Dashboard Kepala', 'ri-vip-crown-line');
    $addDashboard($isWakil, 'user.dashboard.wakil-kepala', 'Dashboard Wakil', 'ri-shield-star-line');
    $addDashboard($isGuru && ! $isGuruTahfidz, 'user.dashboard.guru', 'Dashboard Guru', 'ri-user-star-line');
    $addDashboard($isGuruTahfidz, 'user.dashboard.tahfidz', 'Dashboard Tahfidz', 'ri-book-read-line');
    $addDashboard($isKepalaTU, 'user.dashboard.ka-tata-usaha', 'Dashboard Kepala TU', 'ri-admin-line');
    $addDashboard($isTU, 'user.dashboard.staf-tata-usaha', 'Dashboard Tata Usaha', 'ri-file-user-line');
    $addDashboard($isBendahara, 'user.dashboard.bendahara', 'Dashboard Bendahara', 'ri-money-dollar-circle-line');
    $addDashboard($isKoorKurik, 'user.dashboard.koordinator-kurikulum', 'Dashboard Kurikulum', 'ri-book-mark-line');
    $addDashboard($isKoorKesiswa, 'user.dashboard.koordinator-kesiswaan', 'Dashboard Kesiswaan', 'ri-group-line');
    $addDashboard($isKoorEkskul, 'user.dashboard.koordinator-ekskul', 'Dashboard Ekstrakurikuler', 'ri-basketball-line');
    $addDashboard($isWaliKelas, 'user.dashboard.wali-kelas', 'Dashboard Wali Kelas', 'ri-parent-line');
    $addDashboard($isKoorMapel, 'user.dashboard.koordinator-guru', 'Dashboard Koordinator Mapel', 'ri-team-line');
    $addDashboard($isKoorLab, 'lab.index', 'Dashboard Laboratorium', 'ri-flask-line');
    $addDashboard($isKoorSarpras, 'user.dashboard.koordinator-sarpras', 'Dashboard Koordinator Sarpras', 'ri-tools-line');

    if ($isDualUnitHead ?? false) {
        $addDashboard(true, 'user.dashboard.choose', 'Pilih Dashboard', 'ri-arrow-left-right-line');
    }

    if ($dashboardOptions === []) {
        $dashboardOptions[] = ['routeName' => 'root', 'label' => 'Dashboard', 'icon' => 'ri-home-6-line'];
    }

    $dashboardOptions = collect($dashboardOptions)->unique('routeName')->values()->all();

    /* ── Akses modul Kurikulum ───────────────────────────────────────── */
    $kurikulumAccess       = app(\App\Services\KurikulumAccess::class);
    $canAccessKurikulum    = $showAll || $kurikulumAccess->canAccessKurikulumModule($user);
    $isCurriculumManager   = $showAll || $kurikulumAccess->isKurikulumTeam($user);
    $isKspSupervisor       = $showAll || $kurikulumAccess->isKurikulumSupervisor($user);
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     1. MENU
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Menu</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.', 'root']) ? ' active' : '' }}"
       href="#sidebar_dashboard" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.dashboard.', 'root']) ? 'true' : 'false' }}"
       aria-controls="sidebar_dashboard">
        <i class="ri-dashboard-3-line"></i><span>Dashboard</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.dashboard.', 'root']) ? ' show' : '' }}" id="sidebar_dashboard">
        <ul class="nav nav-sm flex-column">
            @foreach($dashboardOptions as $dashboard)
                <li class="nav-item">
                    <a class="nav-link{{ $currentRoute === $dashboard['routeName'] ? ' active' : '' }}"
                       href="{{ $dashboard['routeName'] === 'root' ? route('root') : route($dashboard['routeName'], ['userId' => $userId]) }}">
                        <i class="{{ $dashboard['icon'] }} me-1"></i>{{ $dashboard['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-task-line"></i><span>Todo List</span>
    @include('layouts.sidebar.partials.indicator-badge', ['key' => 'todo_overdue', 'title' => 'Tugas yang sudah lewat tenggat'])</a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>

<li class="nav-item">
    @if($sekolahId)
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.show']) ? ' active' : '' }}"
       href="{{ route('user.schools.show', ['userId' => $userId, 'schoolId' => $sekolahId]) }}">
        <i class="ri-government-line"></i><span>Satuan Pendidikan</span>
    </a>
    @else
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.index']) ? ' active' : '' }}"
       href="{{ route('user.schools.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i><span>Satuan Pendidikan</span>
    </a>
    @endif
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
       href="{{ route('user.notifications.page', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    @include('layouts.sidebar.partials.indicator-badge', ['key' => 'notifications', 'title' => 'Notifikasi belum dibaca'])</a>
</li>

@if($showAll || $isStruktural || $isAdminTU || $isKoordinator)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ route('user.approvals.index', ['userId' => $userId]) }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    @include('layouts.sidebar.partials.indicator-badge', ['key' => 'approvals', 'title' => 'Permintaan menunggu persetujuan Anda'])</a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     FLAG SECTION — dihitung sekali di atas supaya urutan section di bawah
     tinggal memakai variabel ini.
     ═══════════════════════════════════════════════════════════════════════════ --}}
@php
    // Kepala/Wakil (dan Super Admin) memimpin satuan pendidikan; Tata Usaha
    // hanya mengurus administrasi, jadi tidak otomatis mendapat menu
    // akademik/kurikulum/lintas divisi.
    $isSchoolLeader = $showAll || $isStruktural;
    $manageSchool   = $isSchoolLeader || $isAdminTU;
    // Menu Guru = topi pendidik. Pemegang jabatan struktural hanya melihatnya
    // bila benar-benar punya penugasan mengajar aktif (bukan sekadar teks
    // jabatan "Guru"), supaya tidak muncul menu kosong.
    $showMengajar   = $showAll || $hasTeachingAssignments || $hasTeacherBooks
        || ($isGuru && ! $isStruktural && ! $isAdminTU && ! $isKoordinator);

    // Label section mengikuti peran yang sedang dipakai, supaya GTK yang
    // merangkap Waka + Wali Kelas + Guru Mapel melihat batas yang jelas.
    $mengajarLabel  = ($hasTeachingAssignments || $hasTeacherBooks) ? 'Menu Guru Mapel' : 'Menu Guru';
    $managementLabel = $hasStructuralPosition
        ? 'Jabatan'.($structuralLabel !== '' ? ' · '.$structuralLabel : '')
        : 'Tugas Tambahan'.($additionalTaskLabel !== '' ? ' · '.$additionalTaskLabel : '');

    $showKesiswaan = $manageSchool || $isKoorKesiswa || $isTimKesiswaan || $isGuruBK
        || $isKoorEkskul || $isPembinaEkskul || $isKoorAlumni;
    $showRombel    = $manageSchool || $isKoorKesiswa || $isTimKesiswaan;
    $showNilai     = $manageSchool || $isKoorKurik || $isKoorMapel || $isKoorKesiswa
        || $isTimKurikulum;
    $showAkademik  = $manageSchool || $isKoorKurik || $isKoorMapel || $isTimKurikulum;
    // Data induk akademik (tahun ajaran, mapel, jadwal) hanya untuk pengelola
    // kurikulum — koordinator mapel cukup asesmen + modul kurikulum.
    $showAkademikInduk = $manageSchool || $isKoorKurik || $isTimKurikulum;
    $showGtk       = $manageSchool || $isKoorKurik || $isKoorMapel || $isConfiguredSupervisor;
    $showKurikulum = $isSchoolLeader || $isKoorKurik || $isKoorMapel || $isTimKurikulum || $isKspSupervisor;
    $showKehadiran = $manageSchool || $isKoorKurik || $isKoorKesiswa || $isTimKesiswaan;
    // Monitoring kehadiran guru per jam hanya untuk pengelola, bukan koordinator kesiswaan.
    $showMonitoring = $manageSchool || $isKoorKurik;
    $showSarpras   = $isSchoolLeader || $isKepalaTU || $isKoorSarpras || $isKoorLab;
    $showPerpus    = $isKoorPerpus;
    $showBahasa    = $isSchoolLeader || $isKoorBahasa;
    $showTahfidz   = $isSchoolLeader || $isKoorTahfidz;
    $showEkskul    = $manageSchool || $isKoorEkskul || $isKoorKesiswa || $isPembinaEkskul;
    $showLaporan   = $isSchoolLeader || $isKepalaTU;

    $kesiswaanMode = $manageSchool
        ? 'admin'
        : ($isGuruBK && ! $isKoorKesiswa && ! $isTimKesiswaan ? 'bk' : 'koordinator');
    // Guru BK saja (tanpa tugas kesiswaan lain) memakai label section sendiri.
    $kesiswaanLabel = $kesiswaanMode === 'bk' ? 'Bimbingan Konseling' : 'Kesiswaan';

    $kurikulumMode = ($manageSchool || $isKspSupervisor) ? 'supervisor' : 'manage';
    $kurikulumAlsoManage = $isCurriculumManager;

    $gtkMode = 'supervisi';
    if ($isAdminTU && ! $isStruktural && ! $isKoorKurik) {
        $gtkMode = 'admin';
    } elseif ($isKoorMapel && ! $manageSchool && ! $isKoorKurik && ! $isConfiguredSupervisor) {
        $gtkMode = 'mapel';
    }

    $absensiMode = ($isAdminTU && ! $isStruktural) ? 'admin' : 'koordinator';
    $sarprasMode = ($isAdminTU && ! $isStruktural) ? 'aset' : 'full';

    $hasManagementBlock = $showKesiswaan || $showRombel || $showNilai || $showAkademik
        || $showGtk || $showKehadiran || $showSarpras || $manageSchool || $showPerpus
        || $showBahasa || $showTahfidz || $isBendahara;
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     2. MENU GURU — dipisah per topik (Absensi, Kurikulum, Nilai, Jurnal) agar
     satu dropdown tidak menumpuk puluhan tautan.
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($showMengajar)
    <li class="menu-title"><span>{{ $mengajarLabel }}</span></li>

    @if($isGuruTahfidz && ! $isKoorGuruTahfidz)
        {{-- Guru Tahfidz: buku kerja halaqah + kehadiran --}}
        <x-sidebar-section id="sp_sec_guru_tahfidz" label="Tahfidz" icon="ri-book-read-line"
            :open="$sectionOpen(['user.tahfidz.workspace.', 'user.tahfidz.setoran.', 'user.tahfidz.monitoring.sheet.', 'user.tahfidz.gradebook.', 'user.tahfidz.reports.', 'user.tahfidz.attendance.', 'user.tahfidz.tasmi.', 'user.tahfidz.halaqah.', 'user.tahfidz.progress.', 'user.tahfidz.monitoring.', 'user.tahfidz.interventions.', 'user.tahfidz.targets.', 'user.tahfidz.muqorror.', 'user.tahfidz.rpp.', 'user.teacher-qr.'])">
            @include('layouts.sidebar.satuan-pendidikan.partials.modul-absensi', ['absensiMode' => 'self'])
            @include('layouts.sidebar.satuan-pendidikan.partials.modul-tahfidz', ['tahfidzMode' => 'teacher'])
        </x-sidebar-section>
    @endif

    {{-- Guru Tahfidz yang juga memegang SK mengajar mapel tetap mendapat menu
         pendidik lainnya; guru tahfidz murni tidak perlu section kosong. --}}
    @if(! ($isGuruTahfidz && ! $isKoorGuruTahfidz) || $hasTeachingAssignments)
        {{-- Absensi: jadwal mengajar, kehadiran guru, dan absensi kelas --}}
        <x-sidebar-section id="sp_sec_guru_absensi" label="Absensi" icon="ri-calendar-check-line"
            :open="$sectionOpen(['user.jadwal-kbm.for-teacher', 'user.teacher-qr.', 'user.absensi.harian.'])">
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jadwal-kbm.for-teacher']) ? ' active' : '' }}"
                   href="{{ route('user.jadwal-kbm.for-teacher', ['userId' => $userId, 'teacherId' => $userId]) }}">
                    <i class="ri-calendar-schedule-line"></i><span>Jadwal Mengajar Saya</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.scan']) ? ' active' : '' }}"
                   href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}">
                    <i class="ri-qr-scan-2-line"></i><span>Scan QR Kehadiran</span>
                </a>
            </li>

            @if(canPermission('teacher-attendance_view'))
                <li class="nav-item">
                    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.history']) ? ' active' : '' }}"
                       href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}">
                        <i class="ri-history-line"></i><span>Riwayat Kehadiran Saya</span>
                    </a>
                </li>
            @endif
        </x-sidebar-section>

        {{-- Kurikulum: perangkat ajar & perencanaan pekan --}}
        <x-sidebar-section id="sp_sec_guru_kurikulum" label="Kurikulum" icon="ri-book-open-line"
            :open="$sectionOpen(['user.pekan-efektif.', 'user.kurikulum.profil.', 'user.kurikulum.index', 'user.kurikulum.proyek.', 'user.kurikulum.cp.', 'user.kurikulum.tp.', 'user.kurikulum.atp.', 'user.kurikulum.prota.', 'user.kurikulum.prosem.', 'user.kurikulum.perangkat.', 'user.kurikulum.ai.', 'user.kurikulum.akses-edit.'])">
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.pekan-efektif.']) ? ' active' : '' }}"
                   href="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
                    <i class="ri-calendar-todo-line"></i><span>Pekan Efektif</span>
                </a>
            </li>

            @include('layouts.sidebar.satuan-pendidikan.partials.modul-kurikulum', [
                'kurikulumMode' => 'authoring',
                'kurikulumAlsoManage' => false,
            ])
        </x-sidebar-section>

        {{-- Nilai: buku nilai & asesmen --}}
        <x-sidebar-section id="sp_sec_guru_nilai" label="Nilai" icon="ri-survey-line"
            :open="$sectionOpen(['user.schools.guru-mapel.', 'user.asesmen.', 'user.kisi-kisi-soal.', 'user.bank-soal.', 'user.soal.', 'user.paket-soal.', 'user.tu-paket-soal.', 'user.bank-soal-terpusat.', 'user.review-soal.', 'user.ljk.'])">
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.guru-mapel.']) ? ' active' : '' }}"
                   href="{{ route('user.schools.guru-mapel.index', ['userId' => $userId]) }}">
                    <i class="ri-book-3-line"></i><span>Mapel Saya</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'nilai_guru', 'title' => 'Buku nilai yang belum lengkap'])</a>
            </li>

            @include('layouts.sidebar.satuan-pendidikan.partials.modul-asesmen')
        </x-sidebar-section>

        {{-- Jurnal: catatan pelaksanaan pembelajaran harian --}}
        <x-sidebar-section id="sp_sec_guru_jurnal" label="Jurnal" icon="ri-article-line"
            :open="$sectionOpen(['user.kurikulum.realisasi.'])">
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.realisasi.']) ? ' active' : '' }}"
                   href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId]) }}">
                    <i class="ri-article-line"></i><span>Jurnal Pembelajaran</span>
                </a>
            </li>
        </x-sidebar-section>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     3. MEN WALKES — hanya kelas yang diwalikan (bukan seluruh rombel sekolah)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($isWaliKelas)
    <li class="menu-title">
        <span>Men Walkes · {{ $waliKelasGroups->pluck('name')->join(', ') ?: '-' }}</span>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ $currentRoute === 'user.dashboard.wali-kelas' ? ' active' : '' }}"
           href="{{ route('user.dashboard.wali-kelas', ['userId' => $userId]) }}">
            <i class="ri-parent-line"></i><span>Dashboard Walkes</span>
        </a>
    </li>

    @foreach($waliKelasGroups as $walkesGroup)
        @php
            $walkesOpen = (string) request('study_group_id') === (string) $walkesGroup->id
                || (string) request()->route('studyGroupId') === (string) $walkesGroup->id;
            $walkesId = 'sp_walkes_'.$loop->index;
        @endphp
        <li class="nav-item">
            <a class="nav-link menu-link{{ $walkesOpen ? ' active' : '' }}"
               href="#{{ $walkesId }}" data-bs-toggle="collapse" role="button"
               aria-expanded="{{ $walkesOpen ? 'true' : 'false' }}"
               aria-controls="{{ $walkesId }}">
                <i class="ri-parent-line"></i>
                <span>{{ $walkesGroup->name }}</span>
                <span class="menu-arrow"></span>
            </a>
            <div class="collapse menu-dropdown{{ $walkesOpen ? ' show' : '' }}" id="{{ $walkesId }}">
                <ul class="nav nav-sm flex-column">
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.students.index', 'user.students.show']) && (string) request('study_group_id') === (string) $walkesGroup->id ? ' active' : '' }}"
                           href="{{ route('user.students.index', ['userId' => $userId, 'study_group_id' => $walkesGroup->id]) }}">
                            Data Santri Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.students.mahroms.']) && (string) request('study_group_id') === (string) $walkesGroup->id ? ' active' : '' }}"
                           href="{{ route('user.students.mahroms.global', ['userId' => $userId, 'study_group_id' => $walkesGroup->id]) }}">
                            Data Mahrom Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['user.schools.nilai-kelas.']) && (string) request()->route('studyGroupId') === (string) $walkesGroup->id ? ' active' : '' }}"
                           href="{{ route('user.schools.nilai-kelas.sts', ['userId' => $userId, 'studyGroupId' => $walkesGroup->id]) }}">
                            Leger &amp; Cetak Rapor
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="{{ route('user.absensi.harian.index', ['userId' => $userId, 'study_group_id' => $walkesGroup->id]) }}">
                            Absensi Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="{{ route('user.absensi.harian.recap', ['userId' => $userId, 'study_group_id' => $walkesGroup->id]) }}">
                            Rekap Absensi Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="{{ route('user.student-move.index', ['userId' => $userId, 'study_group_id' => $walkesGroup->id]) }}">
                            Pindahkan Santri
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['sarpras.user.aset.']) && (string) request('study_group_id') === (string) $walkesGroup->id ? ' active' : '' }}"
                           href="{{ route('sarpras.user.aset.index', ['userId' => $userId, 'study_group_id' => $walkesGroup->id]) }}">
                            Inventaris Kelas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link{{ isActiveAny($currentRoute, ['sarpras.user.ruang.']) && (string) request('study_group_id') === (string) $walkesGroup->id ? ' active' : '' }}"
                           href="{{ route('sarpras.user.ruang.index', ['userId' => $userId, 'study_group_id' => $walkesGroup->id]) }}">
                            Ruang Kelas
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    @endforeach

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-prestasi')
@endif

@if($hasManagementBlock)
    <li class="menu-title"><span>{{ $managementLabel }}</span></li>
@endif

@if($showRombel)
    <x-sidebar-section id="sp_sec_rombel" label="Rombel" icon="ri-layout-grid-line"
        :open="$sectionOpen(['user.study-groups.', 'user.grade-levels.', 'user.teaching-assignments.', 'user.student-move.'])">
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-rombel', [
            'rombelMode' => $manageSchool ? 'manage' : 'koordinator',
        ])
    </x-sidebar-section>
@endif

@if($showKesiswaan)
    <x-sidebar-section id="sp_sec_kesiswaan" :label="$kesiswaanLabel" icon="ri-group-line"
        :open="$sectionOpen(['user.kesiswaan.', 'user.violation-points.', 'user.students.', 'user.alumni.', 'user.mutations-'])">
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-kesiswaan', ['kesiswaanMode' => $kesiswaanMode])

        @if($showEkskul && menu_allowed('ekstrakurikuler'))
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.ekstrakurikuler.']) ? ' active' : '' }}"
                   href="{{ route('waka.ekstrakurikuler.index') }}">
                    <i class="ri-basketball-line"></i><span>Ekstrakurikuler</span>
                </a>
            </li>
        @endif
    </x-sidebar-section>
@endif

@if($showNilai)
    <x-sidebar-section id="sp_sec_nilai" label="Nilai & Rapor" icon="ri-survey-line"
        :open="$sectionOpen(['user.schools.nilai.', 'user.schools.nilai-kelas.'])">
        {{-- Guru memakai Asesmen & Buku Nilai Saya di section Mengajar, jadi
             menu Asesmen di sini hanya untuk pengelola/koordinator. --}}
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-nilai', [
            'nilaiMode' => $manageSchool ? 'manage' : 'read',
        ])
        @if(! $showMengajar)
            @include('layouts.sidebar.satuan-pendidikan.partials.modul-asesmen')
        @endif
    </x-sidebar-section>
@endif

@if($showAkademik)
    <x-sidebar-section id="sp_sec_akademik" label="Akademik" icon="ri-book-open-line"
        :open="$sectionOpen(['user.academic-years.', 'user.subjects.', 'user.jadwal-kbm.', 'user.jam-pelajaran.', 'user.pekan-efektif.', 'user.institution-decrees.', 'user.kaldik.', 'user.kalender-kegiatan.', 'user.kurikulum.'])">
        @if($showAkademikInduk)
            @include('layouts.sidebar.satuan-pendidikan.partials.modul-akademik', [
                'hidePekanEfektif' => $showMengajar,
            ])
        @endif
        @if($showKurikulum)
            @include('layouts.sidebar.satuan-pendidikan.partials.modul-kurikulum', [
                'kurikulumMode' => $kurikulumMode,
                'kurikulumAlsoManage' => $kurikulumAlsoManage,
                'kurikulumSkipCommon' => $showMengajar,
                'kurikulumBroad' => true,
                'perangkatLabel' => $showMengajar
                    ? 'Dokumen Kurikulum (Semua Mapel)'
                    : 'Perangkat & Bahan Ajar',
            ])
        @endif
        @if($showAll || $isStruktural || $isKoorKurik || $isTimKurikulum || $isKspSupervisor)
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.realisasi.']) ? ' active' : '' }}"
                   href="{{ route('user.kurikulum.realisasi.monitoring', ['userId' => $userId]) }}">
                    <i class="ri-line-chart-line"></i><span>Monitoring Jurnal</span>
                </a>
            </li>
        @endif
    </x-sidebar-section>
@endif

@if($showGtk)
    <x-sidebar-section id="sp_sec_gtk" label="GTK & Mutu" icon="ri-team-line"
        :open="$sectionOpen(['user.gtk.', 'user.gtk-requests.', 'user.kinerja.', 'user.other-teacher-tasks.', 'user.rapor-gtk.', 'user.wali-links.', 'waka.supervisi'])">
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-gtk', ['gtkMode' => $gtkMode])
    </x-sidebar-section>
@endif

@if($showKehadiran)
    <x-sidebar-section id="sp_sec_kehadiran" label="Kehadiran" icon="ri-calendar-check-line"
        :open="$sectionOpen(['user.kehadiran.', 'user.teacher-qr.', 'user.absensi', 'user.qr.'])">
        @if($showMonitoring)
            @include('layouts.sidebar.satuan-pendidikan.partials.modul-monitoring')
        @endif
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-absensi', ['absensiMode' => $absensiMode])
    </x-sidebar-section>
@endif

@if($showSarpras)
    <x-sidebar-section id="sp_sec_sarpras" label="Sarpras" icon="ri-tools-line"
        :open="$sectionOpen(['sarpras.', 'user.bump-orders.', 'user.institution-debts.', 'user.siplah.', 'lab.'])">
        {{-- Koordinator Laboratorium hanya mengelola lab; modul Sarpras penuh
             bukan kewenangannya. Tampilkan modul Sarpras hanya untuk
             pengelola sekolah atau Koordinator Sarpras. --}}
        @if($manageSchool || $isKoorSarpras || $isKepalaTU)
            @include('layouts.sidebar.satuan-pendidikan.partials.modul-sarpras', ['sarprasMode' => $sarprasMode])
        @endif

        @if($showAll || $isKoorLab)
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.index']) ? ' active' : '' }}"
                   href="{{ route('lab.index') }}">
                    <i class="ri-flask-line"></i><span>Dashboard Laboratorium</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.request.']) ? ' active' : '' }}"
                   href="{{ route('lab.request.index', ['userId' => $userId]) }}">
                    <i class="ri-file-shield-2-line"></i><span>Ajukan Izin Laboratorium</span>
                </a>
            </li>
        @endif
    </x-sidebar-section>
@endif

@if($manageSchool)
    <x-sidebar-section id="sp_sec_administrasi" label="Administrasi" icon="ri-folder-3-line"
        :open="$sectionOpen(['user.arsip.', 'user.dokumen-iso.', 'waka.surat-'])">
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-administrasi')
    </x-sidebar-section>
@endif

@if($showPerpus && menu_allowed('perpustakaan'))
    <x-sidebar-section id="sp_sec_perpustakaan" label="Perpustakaan" icon="ri-book-2-line"
        :open="$sectionOpen(['user.perpustakaan.', 'perpustakaan.'])">
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-perpustakaan')
    </x-sidebar-section>
@endif

@if($showBahasa || $showTahfidz)
    <x-sidebar-section id="sp_sec_lintas" label="Bahasa & Tahfidz" icon="ri-share-forward-line"
        :open="$sectionOpen(['user.bahasa.', 'user.tahfidz.'])">
        @if($showBahasa)
            @include('layouts.sidebar.satuan-pendidikan.partials.modul-bahasa')
        @endif

        @if($showTahfidz)
            @include('layouts.sidebar.satuan-pendidikan.partials.modul-tahfidz', ['tahfidzMode' => 'manage'])
        @endif
    </x-sidebar-section>
@endif

@if($showAll || $isBendahara)
    <x-sidebar-section id="sp_sec_keuangan" label="Keuangan" icon="ri-money-dollar-circle-line"
        :open="$sectionOpen(['user.laporan.keuangan', 'user.payroll-slip.'])">
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.keuangan']) ? ' active' : '' }}"
               href="{{ route('user.laporan.keuangan', ['userId' => $userId]) }}">
                <i class="ri-file-chart-2-line"></i><span>Laporan Keuangan</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.payroll-slip.']) ? ' active' : '' }}"
               href="{{ route('user.payroll-slip.index', ['userId' => $userId]) }}">
                <i class="ri-receipt-line"></i><span>Slip Gaji</span>
            </a>
        </li>
    </x-sidebar-section>
@endif

@if($showLaporan && menu_allowed('laporan'))
    <x-sidebar-section id="sp_sec_laporan" label="Laporan" icon="ri-file-chart-line"
        :open="$sectionOpen(['user.laporan.'])">
        @include('layouts.sidebar.satuan-pendidikan.partials.modul-laporan')
    </x-sidebar-section>
@endif

@if($manageSchool)
    <x-sidebar-section id="sp_sec_informasi" label="Informasi & Broadcast" icon="ri-megaphone-line"
        :open="$sectionOpen(['user.announcements.', 'user.humas.announcements.', 'user.broadcasts.'])">
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.announcements.']) ? ' active' : '' }}"
               href="{{ route('user.announcements.index', ['userId' => $userId]) }}">
                <i class="ri-newspaper-line"></i><span>Info Sekolah</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.humas.announcements.']) ? ' active' : '' }}"
               href="{{ route('user.humas.announcements.index', ['userId' => $userId]) }}">
                <i class="ri-megaphone-line"></i><span>Kelola Pengumuman</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.broadcasts.']) ? ' active' : '' }}"
               href="{{ route('user.broadcasts.index', ['userId' => $userId]) }}">
                <i class="ri-send-plane-2-line"></i><span>Broadcast</span>
            </a>
        </li>
    </x-sidebar-section>
@endif

{{-- Tugas tambahan tetap punya “topi” sendiri saat GTK juga memegang
     jabatan struktural, agar menu tidak bercampur dengan menu jabatan. --}}
@if($hasStructuralPosition && $hasAdditionalTask)
    <li class="menu-title"><span>Tugas Tambahan · {{ $additionalTaskLabel }}</span></li>
    @include('layouts.sidebar.satuan-pendidikan.partials.modul-tugas-tambahan-ringkas')
@endif
