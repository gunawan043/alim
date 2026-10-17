@menuallowed('tugas-pembina-ekskul')
{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: PEMBINA EKSTRAKURIKULER (Tugas Tambahan)
     ───────────────────────────────────────────────────────────────────────────
     Konteks: Tugas tambahan yang dilekatkan ke Guru (role Satuan Pendidikan).
     Scope  : 1 ekskul spesifik (Pramuka, Panahan, Silat, Olahraga, KTI, dll).
     ───────────────────────────────────────────────────────────────────────────
     Wewenang:
       • Bimbing & absen anggota ekskul
       • Jadwal latihan
       • Penilaian anggota
       • Laporan kegiatan
     ───────────────────────────────────────────────────────────────────────────
     CATATAN: Field ekskul spesifik diambil dari relasi user (ekskul_pembina_id
              atau sejenisnya). Sesuaikan dengan struktur DB.
     ═══════════════════════════════════════════════════════════════════════════ --}}

@php
    $showAll = $isSystemAdmin ?? false;

    /* ── Ekskul yang dibina user ─────────────────────────────────────── */
    $pembinaEkskul = \App\Models\Ekstrakurikuler::where('pembina_id', auth()->id())
        ->where('is_active', true)
        ->get();
    $isActuallyPembina = $pembinaEkskul->isNotEmpty();

    $pembinaEksUrl = function (string $routeName, array $extra = []) {
        try {
            return route($routeName, array_merge(['userId' => auth()->id()], $extra));
        } catch (\Throwable $e) {
            return '#';
        }
    };
@endphp

<li class="menu-title"><span>Pembina Ekstrakurikuler</span></li>

{{-- Info ekskul --}}
@if($isActuallyPembina)
<li class="nav-item">
    <span class="nav-link text-muted" style="font-size:0.8rem">
        <i class="ri-information-line me-1"></i>
        Ekskul: <strong>{{ $pembinaEkskul->pluck('nama')->join(', ') }}</strong>
    </span>
</li>
@endif

{{-- 1. Ekskul Saya --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.ekstrakurikuler.index']) ? ' active' : '' }}"
       href="{{ route('waka.ekstrakurikuler.index') }}">
        <i class="ri-basketball-line"></i><span>Ekskul Saya</span>
    </a>
</li>

{{-- 2. Absensi Anggota --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.index', 'user.absensi.harian.create']) ? ' active' : '' }}"
       href="{{ $pembinaEksUrl('user.absensi.harian.index') }}">
        <i class="ri-calendar-check-line"></i><span>Absensi Anggota</span>
    </a>
</li>

{{-- 3. Prestasi Anggota --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.student-achievements.', 'user.student-achievement.']) ? ' active' : '' }}"
       href="{{ $pembinaEksUrl('user.student-achievement.index') }}">
        <i class="ri-trophy-line"></i><span>Prestasi Anggota</span>
    </a>
</li>

{{-- 4. Agenda Kegiatan --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
       href="{{ $pembinaEksUrl('user.kalender-kegiatan.akademik') }}">
        <i class="ri-calendar-event-line"></i><span>Agenda Kegiatan</span>
    </a>
</li>

{{-- 5. Jadwal Latihan (Soon) --}}
<li class="nav-item">
    <a class="nav-link menu-link text-muted" href="#" onclick="return false"
       style="cursor:not-allowed">
        <i class="ri-calendar-schedule-line"></i>
        <span>Jadwal Latihan <small class="badge bg-warning text-dark ms-1">Soon</small></span>
    </a>
</li>
@endmenuallowed
