{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: DEPARTEMEN TAHFIDZ
     ───────────────────────────────────────────────────────────────────────────
     Role    : Departemen Tahfidz
     Jabatan : 3 — Kepala, Wakil Kepala, Tata Usaha
     Tugas   : —
     ───────────────────────────────────────────────────────────────────────────
     ARSITEKTUR DATA (Format #18):
       • Guru Tahfidz (Satuan Pendidikan)  = INPUT setoran harian
       • Departemen Tahfidz (Mandiri)      = AGREGATOR + QC + UJIAN + SERTIFIKASI
       Data mengalir satu arah: Input → Agregator
     ───────────────────────────────────────────────────────────────────────────
     FOKUS SIDEBAR INI (BUKAN input harian):
       1. Master Kurikulum (target per marhalah/jilid)
       2. Rekapitulasi (agregat semua setoran dari Guru Tahfidz)
       3. Ujian Marhalah / Munaqosyah / Tasmi'
       4. Penerbitan Syahadah / Sertifikat
       5. Monitoring santri tertinggal
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $tahfidzUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepalaTahfidz = $hasJabatan('Kepala Departemen Tahfidz');
    $isWakilTahfidz  = $hasJabatan('Wakil Kepala Departemen Tahfidz');
    $isTUTahfidz     = $hasJabatan('Tata Usaha Departemen Tahfidz');

    $isStruktural    = $isKepalaTahfidz || $isWakilTahfidz;
    $isAdmin         = $isTUTahfidz;
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 1 — UTAMA
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Utama</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i><span>Beranda</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
       href="{{ route('user.notifications.index', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>

@if($showAll || $isStruktural || $isAdmin)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ route('user.approvals.index', ['userId' => $userId]) }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — DASHBOARD  [SOON]
     Fokus: QC agregat (bukan operasional halaqoh)
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Dashboard</span></li>

<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-book-mark-line"></i>
        <span>Dashboard QC Tahfidz <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — MASTER KURIKULUM TAHFIDZ  [SOON]
     Akses: Kepala saja (kebijakan & standar mutu)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaTahfidz) && menu_allowed('tahfidz'))
    <li class="menu-title"><span>Master Kurikulum</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-target-line"></i>
            <span>Target per Marhalah <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-list-settings-line"></i>
            <span>Marhalah & Jilid <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-ruler-2-line"></i>
            <span>Standar Penilaian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-map-2-line"></i>
            <span>Mapping Surah & Juz <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — REKAPITULASI & MONITORING (QC AGREGATOR)
     Akses: Kepala, Wakil, TU
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('tahfidz'))
    <li class="menu-title"><span>Rekapitulasi & Monitoring</span></li>

    {{-- ✅ AKTIF: Hafalan Qur'an (dari student-achievement type=quran) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) && request('type') === 'quran' ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.student-achievement.index', ['type' => 'quran']) }}">
            <i class="ri-book-2-line"></i><span>Rekap Hafalan Qur'an</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Hafalan Hadits --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) && request('type') === 'hadits' ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.student-achievement.index', ['type' => 'hadits']) }}">
            <i class="ri-book-mark-line"></i><span>Rekap Hafalan Hadits</span>
        </a>
    </li>

    {{-- ⏳ SOON: Leger Hafalan (rekap total juz per santri) --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-table-line"></i>
            <span>Leger Hafalan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Santri Tertinggal --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-alert-line"></i>
            <span>Santri Tertinggal <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Statistik per Kelas/Marhalah --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-bar-chart-2-line"></i>
            <span>Statistik Per Kelas <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Grafik Pencapaian --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-line-chart-line"></i>
            <span>Grafik Pencapaian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — UJIAN & SERTIFIKASI  [SOON]
     Akses: Kepala & Wakil (penguji utama)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('tahfidz'))
    <li class="menu-title"><span>Ujian & Sertifikasi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-calendar-todo-line"></i>
            <span>Jadwal Ujian Marhalah <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-add-line"></i>
            <span>Pendaftaran Ujian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-award-line"></i>
            <span>Penilaian Munaqosyah <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-medal-2-line"></i>
            <span>Rekap Kelulusan Juz <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-certificate-line"></i>
            <span>Penerbitan Syahadah <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — DATA PENDUKUNG (READ-ONLY)
     Akses: Kepala, Wakil, TU
     Referensi untuk QC & laporan — BUKAN untuk edit
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('tahfidz-pelengkap'))
    <li class="menu-title"><span>Data Pendukung</span></li>

    {{-- ✅ AKTIF: Data Santri (view) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index', 'user.students.show']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.students.index') }}">
            <i class="ri-user-star-line"></i><span>Data Santri</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Data Guru Tahfidz (view) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.indexguru']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.gtk.indexguru') }}">
            <i class="ri-user-3-line"></i><span>Data Guru Tahfidz</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Halaqoh (pakai study-groups sebagai referensi) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.study-groups.index']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.study-groups.index') }}">
            <i class="ri-group-2-line"></i><span>Halaqoh / Rombel</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Mata Pelajaran (untuk referensi Tahfidz sebagai mapel) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.subjects.index']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.subjects.index') }}">
            <i class="ri-book-2-line"></i><span>Mata Pelajaran</span>
        </a>
    </li>

    {{-- ✅ AKTIF: SK & Keputusan --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.institution-decrees.']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.institution-decrees.index') }}">
            <i class="ri-file-paper-2-line"></i><span>SK & Keputusan</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Dokumen Mutu (untuk SOP QC) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Kalender Kegiatan --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — ADMINISTRASI (TU Tahfidz)
     Akses: TU saja (bersama Kepala)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isAdmin) && menu_allowed('tahfidz-pelengkap'))
    <li class="menu-title"><span>Administrasi</span></li>

    {{-- ⏳ SOON: Rekam Setoran (view all setoran dari Guru Tahfidz) --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-list-2-line"></i>
            <span>Rekam Setoran <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Lembar Kontrol / Mutaba'ah --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-paper-line"></i>
            <span>Lembar Kontrol <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Kartu Hafalan Santri --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-id-card-line"></i>
            <span>Kartu Hafalan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Notifikasi Wali Santri --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-mail-send-line"></i>
            <span>Notifikasi Wali <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — LAPORAN
     Akses: Kepala, Wakil, TU
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('tahfidz-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.index']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.laporan.index') }}">
            <i class="ri-file-list-2-line"></i><span>Semua Laporan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.santri']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.laporan.santri') }}">
            <i class="ri-user-star-line"></i><span>Laporan Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.gtk']) ? ' active' : '' }}"
           href="{{ $tahfidzUrl('user.laporan.gtk') }}">
            <i class="ri-team-line"></i><span>Laporan Guru Tahfidz</span>
        </a>
    </li>

    {{-- ⏳ SOON: Laporan Capaian Hafalan --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-chart-line"></i>
            <span>Laporan Capaian Hafalan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif