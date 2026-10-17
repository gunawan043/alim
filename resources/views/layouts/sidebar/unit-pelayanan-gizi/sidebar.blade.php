{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: UNIT PELAYANAN GIZI
     ───────────────────────────────────────────────────────────────────────────
     Role    : Unit Pelayanan Gizi
     Jabatan : 4 —
       Kepala Unit Gizi & Logistik
       Koordinator Logistik
       Staf Gizi
       Staf Logistik
     Tugas   : —
     ───────────────────────────────────────────────────────────────────────────
     FOKUS: Penyediaan makanan santri & GTK
       • Data santri asrama (penghuni, hadir, pulang)
       • Data GTK (untuk porsi makan staf)
       • Data mutasi (santri masuk/keluar → penyesuaian porsi)
     ───────────────────────────────────────────────────────────────────────────
     CATATAN:
       • Menu makanan, bahan pangan, gudang → ditangani APLIKASI TERPISAH
       • Sidebar ini fokus pada DATA REFERENSI untuk kalkulasi porsi
     ───────────────────────────────────────────────────────────────────────────
     BATASAN (Gizi vs Unit Lain):
       ❌ BUKAN wewenang Gizi:
          - Bangunan dapur & perbaikan fisik   → Unit Rumah Tangga
          - Obat & alat medis                  → UKS
       ✅ Gizi fokus pada MENU & KONSUMSI
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Fallback asrama pertama aktif (untuk data penghuni) ─────────── */
    $giziAsramaId = \App\Models\Dormitory::where('is_active', true)->value('id');
    $asramaParams = ['userId' => auth()->id(), 'asramaUuid' => $giziAsramaId];

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $giziUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
    $asramaUrl = function (string $routeName, array $extra = []) use ($giziAsramaId) {
        if (! $giziAsramaId) return '#';
        try {
            return route($routeName, array_merge([
                'userId'     => auth()->id(),
                'asramaUuid' => $giziAsramaId,
            ], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepalaGizi     = $hasJabatan('Kepala Unit Gizi & Logistik');
    $isKoorLogistik   = $hasJabatan('Koordinator Logistik');
    $isStafGizi       = $hasJabatan('Staf Gizi');
    $isStafLogistik   = $hasJabatan('Staf Logistik');

    $isStruktural     = $isKepalaGizi || $isKoorLogistik;
    $isOperasional    = $isStafGizi || $isStafLogistik;
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
       href="{{ $giziUrl('user.profile.my') }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
       href="{{ $giziUrl('user.notifications.index') }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ $giziUrl('user.todos.index') }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>

@if($showAll || $isStruktural)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ $giziUrl('user.approvals.index') }}">
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
       href="{{ $giziUrl('user.dashboard') }}">
        <i class="ri-restaurant-line"></i><span>Dashboard</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — DATA PENGHUNI ASRAMA
     Santri asrama = penerima utama porsi makan
     Akses: semua jabatan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('gizi'))
    <li class="menu-title"><span>Penghuni Asrama</span></li>

    {{-- Daftar penghuni asrama (santri yang tinggal) --}}
    @if($giziAsramaId)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.residents.']) ? ' active' : '' }}"
           href="{{ $asramaUrl('user.asrama.residents.index') }}">
            <i class="ri-user-follow-line"></i><span>Daftar Penghuni</span>
        </a>
    </li>
    @endif

    {{-- Master santri (untuk lookup santri non-asrama/pulang) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index', 'user.students.show']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.students.index') }}">
            <i class="ri-user-star-line"></i><span>Master Santri</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — PRESENSI & KEPULANGAN
     Untuk kalkulasi porsi: siapa hadir, siapa pulang, siapa kembali
     Akses: semua jabatan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('gizi'))
    <li class="menu-title"><span>Presensi & Kepulangan</span></li>

    {{-- Rekap absensi asrama (siapa hadir) --}}
    @if($giziAsramaId)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.attendance.recap']) ? ' active' : '' }}"
           href="{{ $asramaUrl('user.asrama.attendance.recap') }}">
            <i class="ri-bar-chart-2-line"></i><span>Rekap Kehadiran</span>
        </a>
    </li>

    {{-- Perizinan (santri izin keluar = kurangi porsi) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.permits.']) ? ' active' : '' }}"
           href="{{ $asramaUrl('user.asrama.permits.index') }}">
            <i class="ri-pass-valid-line"></i><span>Santri Izin Keluar</span>
        </a>
    </li>

    {{-- Kedatangan santri (yang kembali dari pulang) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.dormitory-returns.index']) ? ' active' : '' }}"
           href="{{ $asramaUrl('user.asrama.dormitory-returns.index') }}">
            <i class="ri-login-box-line"></i><span>Kedatangan Santri</span>
        </a>
    </li>
    @endif

    {{-- Kalender kepulangan (rencana libur/pulang) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.calendar.return.']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.calendar.return.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kepulangan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — MUTASI SANTRI
     Untuk penyesuaian porsi (tambah/kurang)
     Akses: semua jabatan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('gizi'))
    <li class="menu-title"><span>Mutasi Santri</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-lulus.', 'user.mutations-do.']) ? ' active' : '' }}"
           href="#gizi_mutasi" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-lulus.', 'user.mutations-do.']) ? 'true' : 'false' }}"
           aria-controls="gizi_mutasi">
            <i class="ri-arrow-left-right-line"></i><span>Riwayat Mutasi</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-lulus.', 'user.mutations-do.']) ? ' show' : '' }}"
             id="gizi_mutasi">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-in.']) ? ' active' : '' }}"
                       href="{{ $giziUrl('user.mutations-in.index') }}">
                        Santri Masuk / Pindahan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-out.']) ? ' active' : '' }}"
                       href="{{ $giziUrl('user.mutations-out.index') }}">
                        Santri Keluar
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-lulus.']) ? ' active' : '' }}"
                       href="{{ $giziUrl('user.mutations-lulus.index') }}">
                        Santri Lulus
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-do.']) ? ' active' : '' }}"
                       href="{{ $giziUrl('user.mutations-do.index') }}">
                        Santri Drop Out
                    </a>
                </li>
            </ul>
        </div>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — DATA GTK
     Untuk porsi makan guru & staf
     Akses: semua jabatan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('gizi'))
    <li class="menu-title"><span>Data GTK</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.indexguru']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.gtk.indexguru') }}">
            <i class="ri-user-3-line"></i><span>Data Guru</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.indextendik']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.gtk.indextendik') }}">
            <i class="ri-user-settings-line"></i><span>Data Tendik</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.index']) && $currentRoute === 'user.gtk.index' ? ' active' : '' }}"
           href="{{ $giziUrl('user.gtk.index') }}">
            <i class="ri-team-line"></i><span>Semua GTK</span>
        </a>
    </li>

    {{-- Absensi GTK (yang hadir = yang butuh porsi) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.absensi-gtk.index') }}">
            <i class="ri-calendar-check-line"></i><span>Kehadiran GTK</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — LOGISTIK & PERLENGKAPAN
     Referensi ke modul Sarpras untuk peralatan dapur
     Akses: Kepala & Koordinator Logistik
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaGizi || $isKoorLogistik) && menu_allowed('gizi'))
    <li class="menu-title"><span>Logistik & Perlengkapan</span></li>

    {{-- Daftar aset (peralatan dapur, meja, dsb) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.aset.', 'sarpras.assets.']) ? ' active' : '' }}"
           href="{{ route('sarpras.aset.index') }}">
            <i class="ri-archive-2-line"></i><span>Aset Dapur & Logistik</span>
        </a>
    </li>

    {{-- Lapor kerusakan peralatan dapur --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.kerusakan.']) ? ' active' : '' }}"
           href="{{ route('sarpras.user.kerusakan.index') }}">
            <i class="ri-tools-line"></i><span>Lapor Kerusakan</span>
        </a>
    </li>

    {{-- Permintaan pengadaan --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.user.pengadaan.']) ? ' active' : '' }}"
           href="{{ route('sarpras.user.pengadaan.index') }}">
            <i class="ri-shopping-bag-3-line"></i><span>Permintaan Pengadaan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — LAPORAN
     Akses: Kepala & Koordinator
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('gizi-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.index']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.laporan.index') }}">
            <i class="ri-file-list-2-line"></i><span>Semua Laporan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.santri']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.laporan.santri') }}">
            <i class="ri-user-star-line"></i><span>Laporan Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.gtk']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.laporan.gtk') }}">
            <i class="ri-team-line"></i><span>Laporan GTK</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 9 — REFERENSI
     Akses: semua jabatan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural || $isOperasional) && menu_allowed('gizi-pelengkap'))
    <li class="menu-title"><span>Referensi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.dokumen-iso.index') }}">
            <i class="ri-folder-shield-2-line"></i><span>Dokumen Mutu (ISO)</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.institution-decrees.']) ? ' active' : '' }}"
           href="{{ $giziUrl('user.institution-decrees.index') }}">
            <i class="ri-file-paper-2-line"></i><span>SK & Keputusan</span>
        </a>
    </li>
@endif