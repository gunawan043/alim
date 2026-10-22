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
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.dashboard']) ? ' active' : '' }}"
       href="{{ $bahasaUrl('user.bahasa.dashboard') }}">
        <i class="ri-translate-2"></i><span>Dashboard Bahasa</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — MASTER KURIKULUM BAHASA (Kepala saja)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaBahasa) && menu_allowed('bahasa'))
    <li class="menu-title"><span>Master Kurikulum Bahasa</span></li>

    {{-- Mufradat: silabus/periode pekanan --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.mufradat.']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.mufradat.index') }}">
            <i class="ri-book-2-line"></i><span>Silabus Mufradat</span>
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
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.reference.subjects']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.reference.subjects') }}">
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

    {{-- Mufradat harian: daftar pekan aktif + setoran --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.mufradat.']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.mufradat.index') }}">
            <i class="ri-calendar-todo-line"></i><span>Hafalan Mufradat</span>
        </a>
    </li>

    {{-- AM Nasyath Syahri --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.activities.']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.activities.index') }}">
            <i class="ri-calendar-event-line"></i><span>AM Nasyath Syahri</span>
        </a>
    </li>

    {{-- Percakapan harian --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.daily.index']) && request('type', 'percakapan') === 'percakapan' ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.daily.index', ['type' => 'percakapan']) }}">
            <i class="ri-chat-3-line"></i><span>Percakapan Harian</span>
        </a>
    </li>

    {{-- Monitoring Jasus --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.daily.index']) && request('type') === 'jasus' ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.daily.index', ['type' => 'jasus']) }}">
            <i class="ri-spy-line"></i><span>Monitoring Jasus</span>
        </a>
    </li>

    {{-- Data guru bahasa — read-only, filter di controller --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.reference.teachers']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.reference.teachers') }}">
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

    {{-- Pencatatan pelanggaran --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.violations.']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.violations.index') }}">
            <i class="ri-error-warning-line"></i><span>Pencatatan Pelanggaran</span>
        </a>
    </li>

    {{-- Rekap poin pelanggaran --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.violations.report']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.violations.report') }}">
            <i class="ri-bar-chart-2-line"></i><span>Rekap Poin Bahasa</span>
        </a>
    </li>

    {{-- Santri bermasalah --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.violations.report']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.violations.report', ['threshold' => 100]) }}">
            <i class="ri-user-unfollow-line"></i><span>Santri Bermasalah</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — NILAI & PERKEMBANGAN BAHASA
     Akses: Kepala, Wakil, TU
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('bahasa'))
    <li class="menu-title"><span>Nilai & Perkembangan</span></li>

    {{-- Nilai Bahasa Arab --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.academic.nilai']) && request('language', 'arab') === 'arab' ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.academic.nilai', ['language' => 'arab']) }}">
            <i class="ri-translate"></i><span>Nilai Bahasa Arab</span>
        </a>
    </li>

    {{-- Nilai Bahasa Inggris --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.academic.nilai']) && request('language') === 'inggris' ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.academic.nilai', ['language' => 'inggris']) }}">
            <i class="ri-english-input"></i><span>Nilai Bahasa Inggris</span>
        </a>
    </li>

    {{-- Grafik perkembangan --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.academic.progress']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.academic.progress', ['language' => 'arab']) }}">
            <i class="ri-line-chart-line"></i><span>Grafik Perkembangan</span>
        </a>
    </li>

    {{-- Santri tertinggal --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.academic.at-risk']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.academic.at-risk', ['language' => 'arab']) }}">
            <i class="ri-alert-line"></i><span>Santri Tertinggal</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — UJIAN & SERTIFIKASI BAHASA
     Akses: Kepala, Wakil, TU
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isAdmin) && menu_allowed('bahasa'))
    <li class="menu-title"><span>Ujian & Sertifikasi</span></li>

    {{-- Ujian lisan / penilaian dari nilai resmi Satuan Pendidikan --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.academic.exams']) && request('mode') === 'ujian' ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.academic.exams', ['mode' => 'ujian', 'language' => 'arab']) }}">
            <i class="ri-mic-line"></i><span>Ujian Lisan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.academic.exams']) && request('mode') === 'penilaian' ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.academic.exams', ['mode' => 'penilaian', 'language' => 'arab']) }}">
            <i class="ri-edit-2-line"></i><span>Penilaian Ujian</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.academic.leger']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.academic.leger', ['language' => 'arab']) }}">
            <i class="ri-table-line"></i><span>Leger Nilai Bahasa</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.certifications.']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.certifications.index') }}">
            <i class="ri-award-line"></i><span>Sertifikasi Bahasa</span>
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

    {{-- Laporan capaian bahasa --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.academic.report']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.academic.report', ['language' => 'arab']) }}">
            <i class="ri-file-chart-line"></i><span>Laporan Capaian Bahasa</span>
        </a>
    </li>

    {{-- Laporan pelanggaran bahasa --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bahasa.violations.report']) ? ' active' : '' }}"
           href="{{ $bahasaUrl('user.bahasa.violations.report') }}">
            <i class="ri-file-warning-line"></i><span>Laporan Pelanggaran</span>
        </a>
    </li>
@endif