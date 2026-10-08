{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: KOORDINATOR GURU (Shared Template)
     ───────────────────────────────────────────────────────────────────────────
     Dipakai oleh 5 tugas tambahan:
       - Koordinator Guru Umum
       - Koordinator Guru Agama
       - Koordinator Guru Hadits
       - Koordinator Guru Bahasa Arab
       - Koordinator Guru Tahfidz
     ───────────────────────────────────────────────────────────────────────────
     Wajib passing parameter:
       $rumpun      — nama rumpun (mis. 'Umum', 'Agama', 'Hadits', 'Bahasa Arab', 'Tahfidz')
       $rumpunSlug  — slug (mis. 'umum', 'agama', 'hadits', 'bahasa-arab', 'tahfidz')
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;
    $rumpun      = $rumpun      ?? 'Umum';
    $rumpunSlug  = $rumpunSlug  ?? 'umum';

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $koorUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };

    /* ── Dashboard dinamis per rumpun (semua pakai route yang sama) ──── */
    $dashboardRoute = 'user.dashboard.koordinator-guru';
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION — KOORDINATOR GURU {RUMPUN}
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Koor Guru {{ $rumpun }}</span></li>

<li class="nav-item">
    <span class="nav-link text-muted" style="font-size:0.8rem">
        <i class="ri-information-line me-1"></i>
        Rumpun: <strong>{{ $rumpun }}</strong>
    </span>
</li>

{{-- 1. Dashboard --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, [$dashboardRoute]) ? ' active' : '' }}"
       href="{{ $koorUrl($dashboardRoute) }}">
        <i class="ri-dashboard-3-line"></i><span>Dashboard Rumpun</span>
    </a>
</li>

{{-- 2. Data Guru Rumpun --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.indexguru']) ? ' active' : '' }}"
       href="{{ $koorUrl('user.gtk.indexguru') }}">
        <i class="ri-team-line"></i><span>Data Guru {{ $rumpun }}</span>
    </a>
</li>

{{-- 3. Supervisi & Kinerja --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kinerja.']) ? ' active' : '' }}"
       href="{{ $koorUrl('user.kinerja.index') }}">
        <i class="ri-line-chart-line"></i><span>Supervisi & Kinerja Guru</span>
    </a>
</li>

{{-- 4. Monitoring Kehadiran --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.waka-dashboard', 'user.teacher-qr.history']) ? ' active' : '' }}"
       href="{{ $koorUrl('user.teacher-qr.waka-dashboard') }}">
        <i class="ri-calendar-check-line"></i><span>Monitoring Kehadiran</span>
    </a>
</li>

{{-- 5-6. Bank Soal & Kisi-Kisi --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.bank-soal.', 'user.kisi-kisi-soal.', 'user.paket-soal.']) ? ' active' : '' }}"
       href="#koor_{{$rumpunSlug}}_bank_soal" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.bank-soal.', 'user.kisi-kisi-soal.', 'user.paket-soal.']) ? 'true' : 'false' }}"
       aria-controls="koor_{{$rumpunSlug}}_bank_soal">
        <i class="ri-file-list-3-line"></i><span>Bank Soal & Evaluasi</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.bank-soal.', 'user.kisi-kisi-soal.', 'user.paket-soal.']) ? ' show' : '' }}"
         id="koor_{{$rumpunSlug}}_bank_soal">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.bank-soal.']) ? ' active' : '' }}"
                   href="{{ $koorUrl('user.bank-soal.index') }}">
                    Bank Soal {{ $rumpun }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.kisi-kisi-soal.']) ? ' active' : '' }}"
                   href="{{ $koorUrl('user.kisi-kisi-soal.index') }}">
                    Kisi-Kisi Bersama
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.paket-soal.']) ? ' active' : '' }}"
                   href="{{ $koorUrl('user.paket-soal.index') }}">
                    Paket Soal Sumatif
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- 7. Standar Penilaian --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.kktp.']) ? ' active' : '' }}"
       href="{{ $koorUrl('user.schools.kktp.index') }}">
        <i class="ri-ruler-2-line"></i><span>Standar Penilaian</span>
    </a>
</li>

{{-- 8. Laporan Rumpun --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.gtk']) ? ' active' : '' }}"
       href="{{ $koorUrl('user.laporan.gtk') }}">
        <i class="ri-file-chart-line"></i><span>Laporan Rumpun</span>
    </a>
</li>