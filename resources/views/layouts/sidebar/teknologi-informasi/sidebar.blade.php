{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: TEKNOLOGI INFORMASI
     ───────────────────────────────────────────────────────────────────────────
     Role    : Teknologi Informasi
     Jabatan : 3 —
       Kepala Unit Teknologi Informasi
       Staf Teknologi Informasi
       Teknisi Jaringan
     Tugas   : —
     ───────────────────────────────────────────────────────────────────────────
     FOKUS:
       • Penyediaan fasilitas internet & sarana TI
       • Data referensi pengguna (santri & GTK) untuk inventarisasi akses
       • Referensi (dokumen, SK, kalender)
     ───────────────────────────────────────────────────────────────────────────
     CATATAN:
       • Manajemen jaringan/perangkat (router, switch, AP) ditangani
         TOOLS TERPISAH (Mikrotik, NMS, dsb)
       • Modul yang tidak ada di aplikasi ini tidak ditampilkan
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $tiUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepalaTI    = $hasJabatan('Kepala Unit Teknologi Informasi');
    $isStafTI      = $hasJabatan('Staf Teknologi Informasi');
    $isTeknisiJar  = $hasJabatan('Teknisi Jaringan');

    $isStruktural  = $isKepalaTI;
    $isOperasional = $isStafTI || $isTeknisiJar;
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
       href="{{ $tiUrl('user.profile.my') }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
       href="{{ $tiUrl('user.notifications.index') }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ $tiUrl('user.todos.index') }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>

@if($showAll || $isStruktural)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ $tiUrl('user.approvals.index') }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — DASHBOARD
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Dashboard</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard']) ? ' active' : '' }}"
       href="{{ $tiUrl('user.dashboard') }}">
        <i class="ri-computer-line"></i><span>Dashboard</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — PENGGUNA FASILITAS TI
     Data santri & GTK untuk inventarisasi akses internet/akun
     Akses: semua jabatan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('teknologi-informasi'))
    <li class="menu-title"><span>Pengguna Fasilitas</span></li>

    {{-- Data Santri (pengguna fasilitas internet santri) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index', 'user.students.show']) ? ' active' : '' }}"
           href="{{ $tiUrl('user.students.index') }}">
            <i class="ri-user-star-line"></i><span>Data Santri</span>
        </a>
    </li>

    {{-- Data GTK (pengguna fasilitas internet GTK) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.index', 'user.gtk.show', 'user.gtk.indexguru', 'user.gtk.indextendik']) ? ' active' : '' }}"
           href="#ti_gtk" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.gtk.index', 'user.gtk.show', 'user.gtk.indexguru', 'user.gtk.indextendik']) ? 'true' : 'false' }}"
           aria-controls="ti_gtk">
            <i class="ri-team-line"></i><span>Data GTK</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.gtk.index', 'user.gtk.show', 'user.gtk.indexguru', 'user.gtk.indextendik']) ? ' show' : '' }}"
             id="ti_gtk">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.indexguru']) ? ' active' : '' }}"
                       href="{{ $tiUrl('user.gtk.indexguru') }}">
                        Data Guru
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.indextendik']) ? ' active' : '' }}"
                       href="{{ $tiUrl('user.gtk.indextendik') }}">
                        Data Tendik
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.index']) && $currentRoute === 'user.gtk.index' ? ' active' : '' }}"
                       href="{{ $tiUrl('user.gtk.index') }}">
                        Semua GTK
                    </a>
                </li>
            </ul>
        </div>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — INVENTARIS PERANGKAT TI
     Perangkat TI sebagai bagian dari aset (via modul Sarpras)
     Akses: Kepala & Staf
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaTI || $isStafTI) && menu_allowed('teknologi-informasi'))
    <li class="menu-title"><span>Inventaris Perangkat</span></li>

    {{-- Lihat daftar aset Sarpras (untuk inventaris perangkat TI) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.aset.', 'sarpras.assets.']) ? ' active' : '' }}"
           href="{{ route('sarpras.aset.index') }}">
            <i class="ri-archive-2-line"></i><span>Daftar Aset TI</span>
        </a>
    </li>

    {{-- Lihat ruang (untuk lokasi perangkat) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.ruang.', 'sarpras.user.ruang.']) ? ' active' : '' }}"
           href="{{ route('sarpras.ruang.index') }}">
            <i class="ri-door-open-line"></i><span>Ruang & Lokasi</span>
        </a>
    </li>

    {{-- Scan QR aset --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.qr.scanner', 'sarpras.qr.lookup', 'sarpras.assets.scan']) ? ' active' : '' }}"
           href="{{ route('sarpras.qr.scanner') }}">
            <i class="ri-qr-scan-2-line"></i><span>Scan QR Aset</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — LAPORAN KERUSAKAN
     Teknisi Jaringan bisa lapor kerusakan perangkat (via modul Sarpras)
     Akses: Kepala, Staf, Teknisi
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaTI || $isStafTI || $isTeknisiJar) && menu_allowed('teknologi-informasi-pelengkap'))
    <li class="menu-title"><span>Laporan Kerusakan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.kerusakan.']) ? ' active' : '' }}"
           href="{{ route('sarpras.user.kerusakan.index') }}">
            <i class="ri-tools-line"></i><span>Lapor Kerusakan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — SATUAN KERJA & UNIT
     Untuk inventarisasi perangkat per unit
     Akses: Kepala saja
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaTI) && menu_allowed('teknologi-informasi'))
    <li class="menu-title"><span>Satuan Kerja & Unit</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.work-units.']) ? ' active' : '' }}"
           href="{{ $tiUrl('user.work-units.index') }}">
            <i class="ri-community-line"></i><span>Satuan Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.divisi.']) ? ' active' : '' }}"
           href="{{ $tiUrl('user.divisi.index') }}">
            <i class="ri-node-tree"></i><span>Divisi</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — REFERENSI
     Akses: semua jabatan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('teknologi-informasi-pelengkap'))
    <li class="menu-title"><span>Referensi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $tiUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
           href="{{ $tiUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.institution-decrees.']) ? ' active' : '' }}"
           href="{{ $tiUrl('user.institution-decrees.index') }}">
            <i class="ri-file-paper-2-line"></i><span>SK & Keputusan</span>
        </a>
    </li>
@endif