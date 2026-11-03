{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: SATUAN KEAMANAN
     ───────────────────────────────────────────────────────────────────────────
     Role    : Satuan Keamanan
     Jabatan : Kepala, Koordinator, Anggota
     ───────────────────────────────────────────────────────────────────────────
     PRINSIP: PETUGAS POS GERBANG (baca & catat, BUKAN kelola izin)
       ✅ Boleh: verifikasi izin (baca), scan QR, catat tamu/kendaraan,
                 patroli, lapor insiden
       ❌ Tidak: approve/reject izin, buat izin, kelola stok/kamar
     ───────────────────────────────────────────────────────────────────────────
     Semua route modul ini ber-prefix /{userId}/keamanan.
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    $secUser = auth()->user();
    $isKepalaKeamanan = \App\Support\SecurityAccess::isKepala($secUser);
    $isKoorKeamanan   = \App\Support\SecurityAccess::isKoordinator($secUser);
    $isAnggota        = \App\Support\SecurityAccess::isAnggota($secUser);
    $isStruktural     = $isKepalaKeamanan || $isKoorKeamanan;
    $canManageSec     = $showAll || $isStruktural;

    /* Asrama aktif untuk halaman scan (izin & kunjungan) — dipilih petugas,
       bukan otomatis asrama pertama. */
    $scanDormitories = \App\Models\Dormitory::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get(['id', 'name']);

    $keamananUrl = function (string $routeName, array $extra = []) use ($userId) {
        try {
            return route($routeName, array_merge(['userId' => $userId], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    $asramaUrl = function (string $routeName, string $asramaId, array $extra = []) use ($userId) {
        try {
            return route($routeName, array_merge([
                'userId' => $userId,
                'asramaUuid' => $asramaId,
            ], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* Info regu petugas (pos & shift) dari penugasan aktif. */
    $secMembership = \App\Models\Security\SecurityTeamMember::query()
        ->active()
        ->with(['team.post', 'team.shift'])
        ->where('user_id', $userId)
        ->first();

    $posUser   = $secMembership?->team?->post?->name;
    $shiftUser = $secMembership?->team?->shift?->name;
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
       href="{{ route('user.notifications.page', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>

@if($showAll || $isStruktural)
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
@if(menu_allowed('keamanan'))
    <li class="menu-title"><span>Dashboard</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.dashboard']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.dashboard') }}">
            <i class="ri-shield-star-line"></i><span>Dashboard Keamanan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — POS GERBANG
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(menu_allowed('keamanan'))
    <li class="menu-title"><span>Pos Gerbang</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.guests.index', 'user.keamanan.guests.show']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.guests.index') }}">
            <i class="ri-user-received-line"></i><span>Log Tamu</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.guests.create']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.guests.index', ['register' => 1]) }}">
            <i class="ri-user-add-line"></i><span>Registrasi Tamu</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link"
           href="{{ $keamananUrl('user.keamanan.guests.index', ['status' => 'di_dalam']) }}">
            <i class="ri-id-card-line"></i><span>Cetak ID Card Tamu</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.vehicles.']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.vehicles.index') }}">
            <i class="ri-roadster-line"></i><span>Log Kendaraan</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — VERIFIKASI IZIN SANTRI (BACA SAJA)
     Prinsip: keamanan hanya SCAN & LIHAT — tidak approve/reject
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(menu_allowed('keamanan'))
    <li class="menu-title"><span>Verifikasi Izin Santri</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.permits.scan']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.asrama.permits.verify') }}">
            <i class="ri-shield-check-line"></i><span>Verifikasi Manual</span>
        </a>
    </li>

    @if($scanDormitories->isNotEmpty())
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.permits.scan']) ? ' active' : '' }}"
               href="#scan_izin_menu" data-bs-toggle="collapse" role="button"
               aria-expanded="{{ isActiveAny($currentRoute, ['user.asrama.permits.scan']) ? 'true' : 'false' }}"
               aria-controls="scan_izin_menu">
                <i class="ri-qr-scan-2-line"></i><span>Scan QR Izin</span>
            </a>
            <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.asrama.permits.scan']) ? ' show' : '' }}"
                 id="scan_izin_menu">
                <ul class="nav nav-sm flex-column">
                    @foreach($scanDormitories as $dorm)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ $asramaUrl('user.asrama.permits.scan', $dorm->id) }}">
                                {{ $dorm->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </li>
    @endif

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('tab') === 'log' && isActiveAny($currentRoute, ['user.keamanan.gate.index']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.gate.index', ['tab' => 'log']) }}">
            <i class="ri-file-list-2-line"></i><span>Log Keluar-Masuk</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('tab') === 'late' && isActiveAny($currentRoute, ['user.keamanan.gate.index']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.gate.index', ['tab' => 'late']) }}">
            <i class="ri-timer-line"></i><span>Santri Terlambat</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('tab') === 'outside' && isActiveAny($currentRoute, ['user.keamanan.gate.index']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.gate.index', ['tab' => 'outside']) }}">
            <i class="ri-user-location-line"></i><span>Status Santri</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — VERIFIKASI KUNJUNGAN (BACA SAJA)
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(menu_allowed('keamanan'))
    <li class="menu-title"><span>Verifikasi Kunjungan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.visits.verify']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.asrama.visits.verify') }}">
            <i class="ri-shield-check-line"></i><span>Verifikasi Kunjungan</span>
        </a>
    </li>

    @if($scanDormitories->isNotEmpty())
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.visits.scan']) ? ' active' : '' }}"
               href="#scan_kunjungan_menu" data-bs-toggle="collapse" role="button"
               aria-expanded="{{ isActiveAny($currentRoute, ['user.asrama.visits.scan']) ? 'true' : 'false' }}"
               aria-controls="scan_kunjungan_menu">
                <i class="ri-qr-scan-line"></i><span>Scan QR Kunjungan</span>
            </a>
            <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.asrama.visits.scan']) ? ' show' : '' }}"
                 id="scan_kunjungan_menu">
                <ul class="nav nav-sm flex-column">
                    @foreach($scanDormitories as $dorm)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ $asramaUrl('user.asrama.visits.scan', $dorm->id) }}">
                                {{ $dorm->name }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </li>
    @endif
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — PATROLI & RONDA
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(menu_allowed('keamanan'))
    <li class="menu-title"><span>Patroli & Ronda</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.patrol.index', 'user.keamanan.patrol.show']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.patrol.index') }}">
            <i class="ri-calendar-todo-line"></i><span>Jadwal Patroli</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.patrol.checkpoints']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.patrol.checkpoints.index') }}">
            <i class="ri-map-pin-line"></i><span>Checkpoint Patroli</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link"
           href="{{ $keamananUrl('user.keamanan.patrol.index', ['status' => 'selesai']) }}">
            <i class="ri-history-line"></i><span>Riwayat Ronda</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link"
           href="{{ $keamananUrl('sarpras.divisi.assets') }}">
            <i class="ri-tools-line"></i><span>Laporan Fasilitas</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — INSIDEN KEAMANAN
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(menu_allowed('keamanan'))
    <li class="menu-title"><span>Insiden Keamanan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.incidents.create']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.incidents.create') }}">
            <i class="ri-alert-line"></i><span>Laporan Insiden</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.incidents.index', 'user.keamanan.incidents.show']) && ! request()->query('status') ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.incidents.index') }}">
            <i class="ri-file-list-2-line"></i><span>Daftar Insiden</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('status') === 'investigasi' ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.incidents.index', ['status' => 'investigasi']) }}">
            <i class="ri-search-eye-line"></i><span>Investigasi</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 8 — MANAJEMEN REGU
     Akses: Kepala & Koordinator saja
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($canManageSec && menu_allowed('keamanan'))
    <li class="menu-title"><span>Manajemen Regu</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.teams.']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.teams.index') }}">
            <i class="ri-team-line"></i><span>Data Regu & Anggota</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('tab') === 'shift' ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.config.index', ['tab' => 'shift']) }}">
            <i class="ri-calendar-schedule-line"></i><span>Jadwal Shift</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('tab') === 'pos' ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.config.index', ['tab' => 'pos']) }}">
            <i class="ri-map-pin-2-line"></i><span>Pos Jaga</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 9 — SOP & KONFIGURASI
     Akses: Kepala saja
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(($showAll || $isKepalaKeamanan) && menu_allowed('keamanan'))
    <li class="menu-title"><span>SOP & Konfigurasi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('tab') === 'sop' ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.config.index', ['tab' => 'sop']) }}">
            <i class="ri-file-shield-2-line"></i><span>SOP Pengamanan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('tab') === 'jenis' ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.config.index', ['tab' => 'jenis']) }}">
            <i class="ri-list-settings-line"></i><span>Jenis Insiden</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('tab') === 'zona' ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.config.index', ['tab' => 'zona']) }}">
            <i class="ri-layout-masonry-line"></i><span>Zona Area</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->query('tab') === 'kontak' ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.config.index', ['tab' => 'kontak']) }}">
            <i class="ri-phone-line"></i><span>Kontak Darurat</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 10 — LAPORAN
     Akses: Kepala & Koordinator
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($canManageSec && menu_allowed('keamanan-pelengkap'))
    <li class="menu-title"><span>Laporan</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.keamanan.reports.index']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.keamanan.reports.index') }}">
            <i class="ri-bar-chart-2-line"></i><span>Statistik Kunjungan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link" href="{{ $keamananUrl('user.keamanan.reports.guests') }}">
            <i class="ri-user-received-2-line"></i><span>Laporan Tamu</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link" href="{{ $keamananUrl('user.keamanan.reports.incidents') }}">
            <i class="ri-file-warning-line"></i><span>Laporan Insiden</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link" href="{{ $keamananUrl('user.keamanan.reports.patrols') }}">
            <i class="ri-file-chart-line"></i><span>Laporan Patroli</span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 11 — REFERENSI
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if(menu_allowed('keamanan-pelengkap'))
    <li class="menu-title"><span>Referensi</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.kaldik.index') }}">
            <i class="ri-calendar-event-line"></i><span>Kalender Kegiatan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-policies.']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.boarding-policies.index') }}">
            <i class="ri-scales-3-line"></i><span>Kebijakan Izin Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-regulations.']) ? ' active' : '' }}"
           href="{{ $keamananUrl('user.boarding-regulations.index') }}">
            <i class="ri-file-shield-2-line"></i><span>Peraturan Asrama</span>
        </a>
    </li>
@endif
