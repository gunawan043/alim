{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: STAF PERIZINAN & STAF ASRAMA (Shared Template)
     ───────────────────────────────────────────────────────────────────────────
     Dipakai oleh 2 tugas tambahan:
       - Staf Perizinan  → mode 'perizinan' (fokus perizinan saja)
       - Staf Asrama     → mode 'asrama'    (perizinan + piket + operasional)
     ───────────────────────────────────────────────────────────────────────────
     Wajib passing parameter:
       $mode  — 'perizinan' atau 'asrama' (default: 'perizinan')
     ───────────────────────────────────────────────────────────────────────────
     Scope: LINTAS LEMBAGA (semua asrama), filter gender putra ATAU putri
     ───────────────────────────────────────────────────────────────────────────
     FILTER GENDER:
       • user.gender = 'L' / 'putra' / 'male'   → hanya data putra
       • user.gender = 'P' / 'putri' / 'female' → hanya data putri
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;
    $mode    = $mode ?? 'perizinan';

    /* ── Deteksi gender user ─────────────────────────────────────────── */
    $user = auth()->user();
    $userGender = $user->gender ?? $user->assigned_gender ?? null;
    $genderLabel = match(strtolower((string) $userGender)) {
        'l', 'putra', 'male'   => 'Putra',
        'p', 'putri', 'female' => 'Putri',
        default                => 'Semua',
    };

    /* ── Fallback asrama untuk scan ──────────────────────────────────── */
    $scanAsramaId = \App\Models\Dormitory::where('is_active', true)->value('id');

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $spUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
    $spAsramaUrl = function (string $routeName, array $extra = []) use ($scanAsramaId) {
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

    /* ── Judul section dinamis ───────────────────────────────────────── */
    $sectionTitle = match($mode) {
        'asrama'    => 'Staf Asrama',
        default     => 'Staf Perizinan',
    };
@endphp

<li class="menu-title"><span>{{ $sectionTitle }}</span></li>

{{-- Info sektor --}}
<li class="nav-item">
    <span class="nav-link text-muted" style="font-size:0.8rem">
        <i class="ri-information-line me-1"></i>
        Sektor: <strong>{{ $genderLabel }}</strong>
    </span>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     PERIZINAN (LINTAS ASRAMA)
     ═══════════════════════════════════════════════════════════════════════════ --}}

{{-- 1. Semua Perizinan (lintas asrama) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.permits.']) ? ' active' : '' }}"
       href="{{ route('system.permits.index') }}">
        <i class="ri-pass-valid-line"></i><span>Semua Perizinan</span>
    </a>
</li>

{{-- 2. Scan QR Izin --}}
@if($scanAsramaId)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.permits.scan']) ? ' active' : '' }}"
       href="{{ $spAsramaUrl('user.asrama.permits.scan') }}">
        <i class="ri-qr-scan-2-line"></i><span>Scan QR Izin</span>
    </a>
</li>
@endif

{{-- 3. Verifikasi Izin --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.permits.verify']) ? ' active' : '' }}"
       href="{{ $spUrl('user.asrama.permits.verify') }}">
        <i class="ri-shield-check-line"></i><span>Verifikasi Izin</span>
    </a>
</li>

{{-- 4. Cetak Kartu Izin --}}
@if($scanAsramaId)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.permits.bulk-card']) ? ' active' : '' }}"
       href="{{ $spAsramaUrl('user.asrama.permits.bulk-card') }}">
        <i class="ri-file-paper-2-line"></i><span>Cetak Kartu Izin</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     KUNJUNGAN
     ═══════════════════════════════════════════════════════════════════════════ --}}

{{-- 5. Daftar Kunjungan --}}
@if($scanAsramaId)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.visits.index']) ? ' active' : '' }}"
       href="{{ $spAsramaUrl('user.asrama.visits.index') }}">
        <i class="ri-footprint-line"></i><span>Daftar Kunjungan</span>
    </a>
</li>
@endif

{{-- 6. Scan QR Kunjungan --}}
@if($scanAsramaId)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.visits.scan']) ? ' active' : '' }}"
       href="{{ $spAsramaUrl('user.asrama.visits.scan') }}">
        <i class="ri-qr-scan-line"></i><span>Scan QR Kunjungan</span>
    </a>
</li>
@endif

{{-- 7. Verifikasi Kunjungan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.visits.verify']) ? ' active' : '' }}"
       href="{{ $spUrl('user.asrama.visits.verify') }}">
        <i class="ri-shield-check-line"></i><span>Verifikasi Kunjungan</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     KEDATANGAN SANTRI
     ═══════════════════════════════════════════════════════════════════════════ --}}

{{-- 8. Rekap Kedatangan --}}
@if($scanAsramaId)
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.dormitory-returns.index']) ? ' active' : '' }}"
       href="{{ $spAsramaUrl('user.asrama.dormitory-returns.index') }}">
        <i class="ri-login-box-line"></i><span>Kedatangan Santri</span>
    </a>
</li>

{{-- 9. Statistik Kepulangan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.dormitory-returns.statistics']) ? ' active' : '' }}"
       href="{{ $spAsramaUrl('user.asrama.dormitory-returns.statistics') }}">
        <i class="ri-bar-chart-line"></i><span>Statistik Kepulangan</span>
    </a>
</li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     KALENDER
     ═══════════════════════════════════════════════════════════════════════════ --}}

{{-- 10. Kalender Kepulangan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.calendar.return.']) ? ' active' : '' }}"
       href="{{ $spUrl('user.calendar.return.index') }}">
        <i class="ri-calendar-event-line"></i><span>Kalender Kepulangan</span>
    </a>
</li>

{{-- 11. Kalender Kunjungan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.calendar.visit.']) ? ' active' : '' }}"
       href="{{ $spUrl('user.calendar.visit.index') }}">
        <i class="ri-calendar-2-line"></i><span>Kalender Kunjungan</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION KHUSUS MODE 'ASRAMA' — PIKET & OPERASIONAL
     ═══════════════════════════════════════════════════════════════════════════ --}}
@if($mode === 'asrama')
    <li class="menu-title"><span>Piket & Operasional</span></li>

    {{-- 12. Log Piket Asrama (Soon) --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-file-list-2-line"></i>
            <span>Log Piket Asrama <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- 13. Konsumsi Asrama (Soon) --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-restaurant-line"></i>
            <span>Konsumsi Asrama <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>

    {{-- 14. Pendampingan Kegiatan Malam (Soon) --}}
    <li class="nav-item">
        <a class="nav-link menu-link text-muted" href="#" onclick="return false"
           style="cursor:not-allowed">
            <i class="ri-moon-line"></i>
            <span>Kegiatan Malam <small class="badge bg-warning text-dark ms-1">Soon</small></span>
        </a>
    </li>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     REFERENSI (semua mode)
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Referensi</span></li>

{{-- 15. Kebijakan Asrama --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-policies.']) ? ' active' : '' }}"
       href="{{ $spUrl('user.boarding-policies.index') }}">
        <i class="ri-file-shield-2-line"></i><span>Kebijakan Izin</span>
    </a>
</li>

{{-- 16. Peraturan Asrama --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-regulations.']) ? ' active' : '' }}"
       href="{{ $spUrl('user.boarding-regulations.index') }}">
        <i class="ri-scales-3-line"></i><span>Peraturan Asrama</span>
    </a>
</li>