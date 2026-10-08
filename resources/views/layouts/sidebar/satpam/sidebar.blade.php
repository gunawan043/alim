{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: SATUAN KEAMANAN
     ───────────────────────────────────────────────────────────────────────────
     Role    : Satuan Keamanan
     Jabatan : 3 — Kepala, Koordinator, Anggota
     Tugas   : —
     ───────────────────────────────────────────────────────────────────────────
     PRINSIP: PETUGAS POS GERBANG (baca & catat, BUKAN kelola)
       ✅ Boleh: verifikasi izin (baca), scan QR, catat log keluar-masuk,
                 catat tamu, verifikasi kunjungan (baca)
       ❌ Tidak: approve/reject izin, buat izin, kelola stok/kamar/dll
     ───────────────────────────────────────────────────────────────────────────
     ALUR KERJA:
       1. Digital Log Tamu              [SOON]
       2. Verifikasi Perizinan (scan)   [AKTIF]
       3. Patroli Rutin                 [SOON]
       4. Pencatatan Insiden            [SOON]
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Super Admin bypass ──────────────────────────────────────────── */
    $showAll = $isSystemAdmin ?? false;

    /* ── Fallback asrama pertama aktif (untuk scan yang butuh asramaUuid) ─ */
    $scanAsramaId = \App\Models\Dormitory::where('is_active', true)->value('id');

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $keamananUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
    $asramaUrl = function (string $routeName, array $extra = []) use ($scanAsramaId) {
        if (! $scanAsramaId) return '#';
        try {
            return route($routeName, array_merge([
                'userId'     => auth()->id(),
                'asramaUuid' => $scanAsramaId,
            ], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Jabatan ─────────────────────────────────────────────────────── */
    $isKepalaKeamanan = $hasJabatan('Kepala Satuan Keamanan');
    $isKoorKeamanan   = $hasJabatan('Koordinator Satuan Keamanan');
    $isAnggota        = $hasJabatan('Anggota Satuan Keamanan');

    $isStruktural     = $isKepalaKeamanan || $isKoorKeamanan;

    /* ── Info pos/shift (opsional) ───────────────────────────────────── */
    $posUser   = $user->security_post  ?? null;
    $shiftUser = $user->security_shift ?? null;
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 1 — UTAMA
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Utama</span></li>

@if($posUser || $shiftUser)
    <li class="nav-item">
        <span class="nav-link text-muted" style="font-size:0.8rem">
            <i class="ri-information-line me-1"></i>
            @if($posUser) Pos: <strong>{{ $posUser }}</strong> @endif
            @if($shiftUser) · Shift: <strong>{{ $shiftUser }}</strong> @endif
        </span>
    </li>
@endif

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

{{-- Approval Center: HANYA Kepala & Koordinator (bukan anggota) --}}
@if($showAll || $isStruktural)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ route('user.approvals.index', ['userId' => $userId]) }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — DASHBOARD  [SOON]
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Dashboard</span></li>

<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-shield-star-line"></i>
        <span>Dashboard <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — POS GERBANG  [SOON]
     Alur #1: Digital Log Tamu
     Akses: semua jabatan
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeamanan || $isKoorKeamanan || $isAnggota) && menu_allowed('keamanan'))
    <li class="menu-title"><span>Pos Gerbang</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-user-received-line"></i>
            <span>Log Tamu <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-user-add-line"></i>
            <span>Registrasi Tamu <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-id-card-line"></i>
            <span>Cetak ID Card Tamu <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-roadster-line"></i>
            <span>Log Kendaraan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — VERIFIKASI IZIN SANTRI (BACA SAJA)
     Alur #2: Verifikasi Perizinan
     Prinsip: keamanan hanya SCAN & LIHAT — tidak approve/reject
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeamanan || $isKoorKeamanan || $isAnggota) && menu_allowed('keamanan'))
    <li class="menu-title"><span>Verifikasi Izin Santri</span></li>

    {{-- ✅ AKTIF: Scan QR Izin (validasi izin santri keluar) --}}
    @if($scanAsramaId)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.permits.scan']) ? ' active' : '' }}"
           href="{{ $asramaUrl('user.asrama.permits.scan') }}">
            <i class="ri-qr-scan-2-line"></i><span>Scan QR Izin</span>
        </a>
    </li>
    @endif

    {{-- ✅ AKTIF: Verifikasi Manual (input kode izin) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.permits.verify']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.asrama.permits.verify') }}">
            <i class="ri-shield-check-line"></i><span>Verifikasi Manual</span>
        </a>
    </li>

    {{-- ✅ AKTIF: Public Verify (link publik untuk scan dari HP) --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-link"></i>
            <span>Scan via HP (Public) <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Log Keluar-Masuk Santri (belum ada route) --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-list-2-line"></i>
            <span>Log Keluar-Masuk <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Santri Terlambat Kembali --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-timer-line"></i>
            <span>Santri Terlambat <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- ⏳ SOON: Status Santri (di dalam/luar) --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-user-location-line"></i>
            <span>Status Santri <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — VERIFIKASI KUNJUNGAN (BACA SAJA)
     Prinsip: keamanan hanya cek apakah kunjungan valid
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeamanan || $isKoorKeamanan || $isAnggota) && menu_allowed('keamanan'))
    <li class="menu-title"><span>Verifikasi Kunjungan</span></li>

    {{-- ✅ AKTIF: Scan QR Kunjungan --}}
    @if($scanAsramaId)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.visits.scan']) ? ' active' : '' }}"
           href="{{ $asramaUrl('user.asrama.visits.scan') }}">
            <i class="ri-qr-scan-line"></i><span>Scan QR Kunjungan</span>
        </a>
    </li>
    @endif

    {{-- ✅ AKTIF: Verifikasi Kunjungan Manual --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.visits.verify']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.asrama.visits.verify') }}">
            <i class="ri-shield-check-line"></i><span>Verifikasi Kunjungan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — PATROLI & RONDA  [SOON]
     Alur #3: Patroli Rutin
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeamanan || $isKoorKeamanan || $isAnggota) && menu_allowed('keamanan'))
    <li class="menu-title"><span>Patroli & Ronda</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-calendar-todo-line"></i>
            <span>Jadwal Patroli <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-map-pin-line"></i>
            <span>Checkpoint Patroli <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-history-line"></i>
            <span>Riwayat Ronda <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-tools-line"></i>
            <span>Laporan Fasilitas <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — INSIDEN KEAMANAN  [SOON]
     Alur #4: Pencatatan Insiden
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeamanan || $isKoorKeamanan || $isAnggota) && menu_allowed('keamanan'))
    <li class="menu-title"><span>Insiden Keamanan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-alert-line"></i>
            <span>Laporan Insiden <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-list-2-line"></i>
            <span>Daftar Insiden <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-search-eye-line"></i>
            <span>Investigasi <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — MANAJEMEN REGU  [SOON]
     Akses: Kepala & Koordinator saja
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('keamanan'))
    <li class="menu-title"><span>Manajemen Regu</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-calendar-schedule-line"></i>
            <span>Jadwal Regu <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-team-line"></i>
            <span>Data Anggota <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-map-pin-2-line"></i>
            <span>Pos Jaga <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 9 — SOP & KONFIGURASI  [SOON]
     Akses: Kepala saja
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeamanan) && menu_allowed('keamanan'))
    <li class="menu-title"><span>SOP & Konfigurasi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-shield-2-line"></i>
            <span>SOP Pengamanan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-list-settings-line"></i>
            <span>Jenis Insiden <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-layout-masonry-line"></i>
            <span>Zona Area <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-phone-line"></i>
            <span>Kontak Darurat <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 10 — LAPORAN  [SOON]
     Akses: Kepala & Koordinator
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isStruktural) && menu_allowed('keamanan-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-user-received-2-line"></i>
            <span>Laporan Tamu <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-warning-line"></i>
            <span>Laporan Insiden <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-chart-line"></i>
            <span>Laporan Patroli <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-bar-chart-2-line"></i>
            <span>Statistik Kunjungan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 11 — REFERENSI
     Semua route sudah ada — untuk cek aturan izin & agenda
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeamanan || $isKoorKeamanan || $isAnggota) && menu_allowed('keamanan-pelengkap'))
    <li class="menu-title"><span>Referensi</span></li>

    {{-- Kalender Kegiatan (untuk antisipasi event besar) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>

    {{-- Kebijakan Asrama (untuk cek aturan izin santri) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-policies.']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.boarding-policies.index') }}">
            <i class="ri-scales-3-line"></i><span>Kebijakan Izin Santri</span>
        </a>
    </li>

    {{-- Peraturan Asrama (untuk referensi tata tertib) --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-regulations.']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.boarding-regulations.index') }}">
            <i class="ri-file-shield-2-line"></i><span>Peraturan Asrama</span>
        </a>
    </li>
@endif