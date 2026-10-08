{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: PERPUSTAKAAN
     ───────────────────────────────────────────────────────────────────────────
     Role    : Perpustakaan
     Jabatan : 2 —
       Koordinator Perpustakaan
       Staf Perpustakaan
     Tugas   : —
     ───────────────────────────────────────────────────────────────────────────
     ALUR KERJA UTAMA:
       1. Registrasi Anggota (Santri & GTK)
       2. Transaksi Peminjaman (Scan barcode)
       3. Transaksi Pengembalian (Cek kondisi & keterlambatan)
       4. Manajemen Denda / Bebas Pustaka
       5. Stock Opname & Pengadaan Buku
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba tanpa tukar akun)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $pustakaUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKoordinator = $hasJabatan('Koordinator Perpustakaan');
    $isStaf        = $hasJabatan('Staf Perpustakaan');
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

@if($showAll || $isKoordinator)
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
        <i class="ri-book-2-line"></i>
        <span>Dashboard <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — MASTER KOLEKSI (Koordinator saja)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoordinator) && menu_allowed('perpustakaan'))
    <li class="menu-title"><span>Master Koleksi</span></li>

    {{-- PLACEHOLDER: Katalog buku --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-book-mark-line"></i>
            <span>Katalog Buku <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Kategori & Klasifikasi --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-price-tag-3-line"></i>
            <span>Kategori & Klasifikasi <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Manajemen Rak --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-layout-grid-line"></i>
            <span>Manajemen Rak <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Kitab & Referensi --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-book-3-line"></i>
            <span>Kitab & Referensi <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Aturan Peminjaman --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-settings-4-line"></i>
            <span>Aturan Peminjaman <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — SIRKULASI (peminjaman & pengembalian)
     Akses: Koordinator + Staf
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoordinator || $isStaf) && menu_allowed('perpustakaan'))
    <li class="menu-title"><span>Sirkulasi</span></li>

    {{-- PLACEHOLDER: Peminjaman --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-book-open-line"></i>
            <span>Transaksi Peminjaman <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Pengembalian --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-arrow-go-back-line"></i>
            <span>Transaksi Pengembalian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Perpanjangan --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-refresh-line"></i>
            <span>Perpanjangan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Buku Terlambat --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-timer-line"></i>
            <span>Buku Terlambat <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Riwayat Sirkulasi --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-history-line"></i>
            <span>Riwayat Sirkulasi <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — KEANGGOTAAN
     Akses: Koordinator + Staf
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoordinator || $isStaf) && menu_allowed('perpustakaan'))
    <li class="menu-title"><span>Keanggotaan</span></li>

    {{-- PLACEHOLDER: Anggota Santri --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-user-star-line"></i>
            <span>Anggota Santri <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Anggota GTK --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-team-line"></i>
            <span>Anggota GTK <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Kartu Anggota --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-id-card-line"></i>
            <span>Kartu Anggota <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Kunjungan Harian --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-footprint-line"></i>
            <span>Kunjungan Harian <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — DENDA & BEBAS PUSTAKA
     Akses: Koordinator + Staf
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoordinator || $isStaf) && menu_allowed('perpustakaan'))
    <li class="menu-title"><span>Denda & Bebas Pustaka</span></li>

    {{-- PLACEHOLDER: Daftar Denda --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-money-dollar-circle-line"></i>
            <span>Daftar Denda <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Pembayaran Denda --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-hand-coin-line"></i>
            <span>Pembayaran Denda <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Surat Bebas Pustaka --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-paper-2-line"></i>
            <span>Surat Bebas Pustaka <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Bebas Pustaka Massal (kenaikan kelas/kelulusan) --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-list-3-line"></i>
            <span>Bebas Pustaka Massal <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — INVENTARIS & PENGADAAN (Koordinator saja)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoordinator) && menu_allowed('perpustakaan'))
    <li class="menu-title"><span>Inventaris & Pengadaan</span></li>

    {{-- PLACEHOLDER: Stock Opname --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-list-2-line"></i>
            <span>Stock Opname <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Buku Rusak/Hilang --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-book-2-line"></i>
            <span>Buku Rusak / Hilang <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Pengadaan Buku Baru --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-shopping-bag-3-line"></i>
            <span>Pengadaan Buku <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Sumbangan Buku --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-gift-line"></i>
            <span>Sumbangan Buku <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Penyusutan / Pemusnahan --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-delete-bin-6-line"></i>
            <span>Penyusutan Koleksi <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — LITERASI
     Akses: Koordinator + Staf
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoordinator || $isStaf) && menu_allowed('perpustakaan'))
    <li class="menu-title"><span>Literasi</span></li>

    {{-- PLACEHOLDER: Pojok Baca --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-book-read-line"></i>
            <span>Pojok Baca <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Resensi Buku --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-edit-2-line"></i>
            <span>Resensi Buku <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Program Literasi --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-lightbulb-line"></i>
            <span>Program Literasi <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Lomba Literasi --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-trophy-line"></i>
            <span>Lomba Literasi <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 9 — LAPORAN
     Akses: Koordinator + Staf
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoordinator || $isStaf) && menu_allowed('perpustakaan-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    {{-- PLACEHOLDER: Statistik Peminjaman --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-bar-chart-2-line"></i>
            <span>Statistik Peminjaman <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Buku Terpopuler --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-fire-line"></i>
            <span>Buku Terpopuler <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Anggota Teraktif --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-user-star-line"></i>
            <span>Anggota Teraktif <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- PLACEHOLDER: Laporan Tahunan --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-chart-line"></i>
            <span>Laporan Tahunan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 10 — REFERENSI (yang sudah ada route-nya)
     Akses: semua
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKoordinator || $isStaf) && menu_allowed('perpustakaan-pelengkap'))
    <li class="menu-title"><span>Referensi</span></li>

    {{-- Kalender Kegiatan Perpustakaan --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $pustakaUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>

    {{-- Dokumen ISO (SOP Perpustakaan) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
           href="{{ $pustakaUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>
@endif