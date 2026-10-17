{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: DEPARTEMEN BAHASA
     ───────────────────────────────────────────────────────────────────────────
     Role    : Departemen Bahasa
     Jabatan : 3 —
       Kepala Departemen Bahasa
       Wakil Kepala Departemen Bahasa
       Tata Usaha Departemen Bahasa
     Tugas   : —
     ───────────────────────────────────────────────────────────────────────────
     ARSITEKTUR DATA (Format #18):
       • Guru Bahasa Arab / Guru Bahasa Inggris (Satuan Pendidikan) = INPUT nilai KBM
       • Departemen Bahasa = AGREGATOR + QC + Ujian + Sertifikasi
       Data mengalir satu arah: Input → Agregator
     ───────────────────────────────────────────────────────────────────────────
     BATASAN AKSES KETAT:
       • HANYA modul kebahasaan
       • TIDAK ada akses ke: Keuangan, Sarpras, Asrama, UKS, GTK full, dll
       • Data santri & GTK hanya READ-ONLY untuk referensi
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba tanpa tukar akun)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $bahasaUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepalaBahasa = $hasJabatan('Kepala Departemen Bahasa');
    $isWakilBahasa  = $hasJabatan('Wakil Kepala Departemen Bahasa');
    $isTUBahasa     = $hasJabatan('Tata Usaha Departemen Bahasa');

    $isStruktural   = $isKepalaBahasa || $isWakilBahasa;
    $isAdmin        = $isTUBahasa;
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
     SECTION 2 — DASHBOARD
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Dashboard</span></li>

<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-translate-2"></i>
        <span>Dashboard <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — MASTER KURIKULUM BAHASA (Kepala saja)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaBahasa) && menu_allowed('bahasa'))
    <li class="menu-title"><span>Master Kurikulum Bahasa</span></li>

    {{-- PLACEHOLDER: silabus mufrodat --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-book-2-line"></i>
            <span>Silabus Mufrodat <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: standar penilaian bahasa --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-ruler-2-line"></i>
            <span>Standar Penilaian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: mapping kurikulum --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-node-tree"></i>
            <span>Mapping Kurikulum <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- Yang sudah ada: Mapel Bahasa (read-only referensi) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.subjects.']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.subjects.index') }}">
            <i class="ri-book-mark-line"></i><span>Mata Pelajaran Bahasa</span>
        </a>
    </li>

    {{-- Yang sudah ada: Dokumen Mutu --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu Bahasa</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — OPERASIONAL HARIAN
     Akses: Kepala, Wakil, TU
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('bahasa'))
    <li class="menu-title"><span>Operasional Harian</span></li>

    {{-- PLACEHOLDER: mufrodat harian --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-calendar-todo-line"></i>
            <span>Mufrodat Harian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: percakapan harian --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-chat-3-line"></i>
            <span>Percakapan Harian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: monitoring Jasus / spionase bahasa --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-spy-line"></i>
            <span>Monitoring Jasus <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- Data guru bahasa — read-only, filter di controller --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.index', 'user.gtk.indexguru']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.gtk.indexguru') }}">
            <i class="ri-user-star-line"></i><span>Data Guru Bahasa</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — PELANGGARAN BAHASA
     Akses: Kepala, Wakil, TU
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('bahasa'))
    <li class="menu-title"><span>Pelanggaran Bahasa</span></li>

    {{-- PLACEHOLDER: pencatatan pelanggaran --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-error-warning-line"></i>
            <span>Pencatatan Pelanggaran <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: rekap poin pelanggaran --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-bar-chart-2-line"></i>
            <span>Rekap Poin Bahasa <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: santri sering melanggar --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-user-unfollow-line"></i>
            <span>Santri Bermasalah <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — MAHKAMAH BAHASA
     Akses: Kepala, Wakil
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('bahasa'))
    <li class="menu-title"><span>Mahkamah Bahasa</span></li>

    {{-- PLACEHOLDER: jadwal sidang --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-calendar-event-line"></i>
            <span>Jadwal Sidang <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: pencatatan sidang --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-text-line"></i>
            <span>Pencatatan Sidang <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: penindakan pelanggaran --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-scales-3-line"></i>
            <span>Penindakan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — NILAI & PERKEMBANGAN BAHASA
     Akses: Kepala, Wakil, TU
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('bahasa'))
    <li class="menu-title"><span>Nilai & Perkembangan</span></li>

    {{-- PLACEHOLDER: nilai Bahasa Arab --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-translate"></i>
            <span>Nilai Bahasa Arab <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: nilai Bahasa Inggris --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-english-input"></i>
            <span>Nilai Bahasa Inggris <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: grafik perkembangan --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-line-chart-line"></i>
            <span>Grafik Perkembangan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: santri tertinggal --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-alert-line"></i>
            <span>Santri Tertinggal <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — UJIAN & SERTIFIKASI BAHASA
     Akses: Kepala, Wakil, TU
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('bahasa'))
    <li class="menu-title"><span>Ujian & Sertifikasi</span></li>

    {{-- PLACEHOLDER: ujian lisan per semester --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-mic-line"></i>
            <span>Ujian Lisan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: penilaian ujian bahasa --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-edit-2-line"></i>
            <span>Penilaian Ujian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: leger nilai bahasa --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-table-line"></i>
            <span>Leger Nilai Bahasa <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: sertifikasi bahasa --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-award-line"></i>
            <span>Sertifikasi Bahasa <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 9 — ADMINISTRASI (khusus TU Bahasa)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isAdmin) && menu_allowed('bahasa-pelengkap'))
    <li class="menu-title"><span>Administrasi Bahasa</span></li>

    {{-- PLACEHOLDER: rekam pelanggaran --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-list-2-line"></i>
            <span>Rekam Pelanggaran <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: kartu bahasa santri --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-id-card-line"></i>
            <span>Kartu Bahasa Santri <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: notifikasi wali --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-mail-send-line"></i>
            <span>Notifikasi Wali Santri <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- Yang sudah ada: kalender kegiatan --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 10 — LAPORAN
     Akses: Kepala, Wakil, TU
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('bahasa-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.index']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.laporan.index') }}">
            <i class="ri-file-list-2-line"></i><span>Semua Laporan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.santri']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.laporan.santri') }}">
            <i class="ri-user-star-line"></i><span>Laporan Santri</span>
        </a>
    </li>

    {{-- PLACEHOLDER: laporan capaian bahasa --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-chart-line"></i>
            <span>Laporan Capaian Bahasa <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: laporan pelanggaran bahasa --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-warning-line"></i>
            <span>Laporan Pelanggaran <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif