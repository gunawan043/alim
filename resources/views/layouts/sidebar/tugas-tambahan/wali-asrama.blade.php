@menuallowed('tugas-wali-asrama')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: WALI ASRAMA (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: Tugas tambahan yang dilekatkan ke Musrif/Kepala Asrama (role Asrama).
     Scope  : HANYA blok/gedung yang dia awasi.
     ───────────────────────────────────────────────────────────────────────────
     Wewenang:
       • Pengawasan ketertiban blok
       • Koordinasi antar Wali Kamar (bawahannya)
       • Laporan agregat per blok
       • Piket blok
     ───────────────────────────────────────────────────────────────────────────
     AKSES SUPER ADMIN: Unrestricted (untuk ujicoba)
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    /* ── Blok/gedung yang diawasi user ───────────────────────────────── */
    $waliAsramaWings = \App\Models\DormitoryWing::where('supervisor_user_id', auth()->id())
        ->with('dormitory')
        ->get();
    $isActuallyWaliAsrama = $waliAsramaWings->isNotEmpty();
    $firstWingId = $waliAsramaWings->first()?->id;
    $firstAsramaId = $waliAsramaWings->first()?->dormitory_id;

    /* ── Helper URL ──────────────────────────────────────────────────── */
    $waUrl = function (string $routeName, array $extra = []) use ($firstAsramaId) {
        if (! $firstAsramaId) return '#';
        try {
            return route($routeName, array_merge([
                'userId'     => auth()->id(),
                'asramaUuid' => $firstAsramaId,
            ], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Wali Asrama</span></li>

{{-- Info blok --}}
@if($isActuallyWaliAsrama)
<li class="nav-item">
    <span class="nav-link text-muted" style="font-size:0.8rem">
        <i class="ri-information-line me-1"></i>
        Blok: <strong>{{ $waliAsramaWings->pluck('name')->join(', ') }}</strong>
    </span>
</li>
@endif

{{-- 1. Rekap Absensi Blok --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.attendance.recap']) ? ' active' : '' }}"
       href="{{ $waUrl('user.asrama.attendance.recap', $firstWingId ? ['wing_id' => $firstWingId] : []) }}">
        <i class="ri-bar-chart-2-line"></i><span>Rekap Absensi Blok</span>
    </a>
</li>

{{-- 2. Pelanggaran Blok --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.violations.index']) ? ' active' : '' }}"
       href="{{ $waUrl('user.asrama.violations.index', $firstWingId ? ['wing_id' => $firstWingId] : []) }}">
        <i class="ri-error-warning-line"></i><span>Pelanggaran Blok</span>
    </a>
</li>

{{-- 3. Penghargaan Blok --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.rewards.index']) ? ' active' : '' }}"
       href="{{ $waUrl('user.asrama.rewards.index', $firstWingId ? ['wing_id' => $firstWingId] : []) }}">
        <i class="ri-medal-line"></i><span>Penghargaan Blok</span>
    </a>
</li>

{{-- 4. Santri Blok --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.residents.index']) ? ' active' : '' }}"
       href="{{ $waUrl('user.asrama.residents.index', $firstWingId ? ['wing_id' => $firstWingId] : []) }}">
        <i class="ri-user-heart-line"></i><span>Santri Blok Saya</span>
    </a>
</li>

{{-- 5. Daftar Kamar di Blok --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.rooms.index']) ? ' active' : '' }}"
       href="{{ $waUrl('user.asrama.rooms.index', $firstWingId ? ['wing_id' => $firstWingId] : []) }}">
        <i class="ri-door-open-line"></i><span>Daftar Kamar Blok</span>
    </a>
</li>

{{-- 6. Daftar Wali Kamar (bawahan) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.room-supervisors.']) ? ' active' : '' }}"
       href="{{ $waUrl('user.asrama.room-supervisors.index', $firstWingId ? ['wing_id' => $firstWingId] : []) }}">
        <i class="ri-shield-user-line"></i><span>Wali Kamar Bawahan</span>
    </a>
</li>

{{-- 7. Piket Blok --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-calendar-todo-line"></i>
        <span>Piket Blok <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>
@endmenuallowed
