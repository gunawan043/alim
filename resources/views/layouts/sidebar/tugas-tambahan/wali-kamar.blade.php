@menuallowed('tugas-wali-kamar')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: WALI KAMAR (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: GTK yang ditetapkan sebagai penanggung jawab satu kamar asrama
     (room_supervisors). Bisa juga dipegang GTK satuan pendidikan.
     Scope  : HANYA kamar yang dia awasi.
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    /* ── Kamar yang diawasi user (penugasan aktif) ───────────────────── */
    $waliKamarAssignments = \App\Models\RoomSupervisor::query()
        ->active()
        ->where('user_id', auth()->id())
        ->with(['room.wing', 'dormitory'])
        ->get();

    $waliKamar = $waliKamarAssignments->first();
    $waliKamarRoomId = $waliKamar?->room_id;
    $waliKamarDormId = $waliKamar?->dormitory_id;

    /* ── Helper URL asrama + kamar ───────────────────────────────────── */
    $wkUrl = function (string $routeName, array $extra = []) use ($waliKamarDormId): string {
        if (! $waliKamarDormId) {
            return '#';
        }

        try {
            return route($routeName, array_merge([
                'userId' => auth()->id(),
                'asramaUuid' => $waliKamarDormId,
            ], $extra));
        } catch (\Throwable) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Wali Kamar</span></li>

@if($waliKamar)
<li class="nav-item">
    <span class="nav-link text-muted" style="font-size:0.8rem">
        <i class="ri-information-line me-1"></i>
        Kamar: <strong>{{ $waliKamar->room?->name ?? $waliKamar->room?->code ?? '-' }}</strong>
        @if($waliKamar->room?->wing?->name)
            · {{ $waliKamar->room->wing->name }}
        @endif
    </span>
</li>
@endif

{{-- 1. Kamar Saya — detail kamar & penghuninya --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.rooms.show']) ? ' active' : '' }}"
       href="{{ $waliKamarRoomId
            ? $wkUrl('user.asrama.rooms.show', ['roomUuid' => $waliKamarRoomId])
            : $wkUrl('user.asrama.rooms.index') }}">
        <i class="ri-door-open-line"></i><span>Kamar Saya</span>
    </a>
</li>

{{-- 2. Santri Kamar Saya --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.residents.index']) ? ' active' : '' }}"
       href="{{ $wkUrl('user.asrama.residents.index', $waliKamarRoomId ? ['room_id' => $waliKamarRoomId] : []) }}">
        <i class="ri-user-heart-line"></i><span>Santri Kamar Saya</span>
    </a>
</li>

{{-- 3. Rekap Absensi Kamar --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.attendance.recap']) ? ' active' : '' }}"
       href="{{ $wkUrl('user.asrama.attendance.recap', $waliKamarRoomId ? ['room_id' => $waliKamarRoomId] : []) }}">
        <i class="ri-bar-chart-2-line"></i><span>Rekap Absensi Kamar</span>
    </a>
</li>

{{-- 4. Pelanggaran Kamar --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.violations.index']) ? ' active' : '' }}"
       href="{{ $wkUrl('user.asrama.violations.index', $waliKamarRoomId ? ['room_id' => $waliKamarRoomId] : []) }}">
        <i class="ri-error-warning-line"></i><span>Pelanggaran Kamar</span>
    </a>
</li>

{{-- 5. Penghargaan Kamar --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.rewards.index']) ? ' active' : '' }}"
       href="{{ $wkUrl('user.asrama.rewards.index', $waliKamarRoomId ? ['room_id' => $waliKamarRoomId] : []) }}">
        <i class="ri-medal-line"></i><span>Penghargaan Kamar</span>
    </a>
</li>

{{-- 6. Piket Kamar — tugas harian asrama --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.daily-tasks.']) ? ' active' : '' }}"
       href="{{ $wkUrl('user.asrama.daily-tasks.index') }}">
        <i class="ri-calendar-todo-line"></i><span>Piket Kamar</span>
    </a>
</li>
@endmenuallowed
