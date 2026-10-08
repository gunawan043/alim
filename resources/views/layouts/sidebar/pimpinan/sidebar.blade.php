<!-- Pimpinan Sidebar — Unsur Pimpinan Pondok (Wakil Mudir I & II) -->
@php
$currentRoute = request()->route() ? request()->route()->getName() : '';
$userId = auth()->user()->id;

if (! function_exists('isActivePimpinan')) {
function isActivePimpinan($routeName, $pattern) {
    if (!$routeName) return false;
    return str_starts_with($routeName, $pattern);
}
}
@endphp

<li class="menu-title"><span>Menu</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'root' ? ' active' : '' }}"
       href="{{ route('root') }}">
        <i class="ri-home-6-line"></i>
        <span>Dashboard</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.profile.my' ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i>
        <span>Profile</span>
    </a>
</li>

<li class="menu-title"><span>Pengawasan GTK & Santri</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.gtk.') ? ' active' : '' }}"
       href="{{ route('user.gtk.index', ['userId' => $userId]) }}">
        <i class="ri-contacts-book-2-line"></i>
        <span>Data GTK</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.students.') ? ' active' : '' }}"
       href="{{ route('user.students.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Data Santri</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.teacher-qr.waka-dashboard') ? ' active' : '' }}"
       href="{{ route('user.teacher-qr.waka-dashboard', ['userId' => $userId]) }}">
        <i class="ri-dashboard-2-line"></i>
        <span>Kehadiran Guru Per Jam</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.teacher-qr.history') ? ' active' : '' }}"
       href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}">
        <i class="ri-history-line"></i>
        <span>Riwayat Kehadiran Guru</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.kehadiran.pergantian-jam') ? ' active' : '' }}"
       href="{{ route('user.kehadiran.pergantian-jam', ['userId' => $userId]) }}">
        <i class="ri-refresh-line"></i>
        <span>Rekap Pergantian Jam</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.qr.') ? ' active' : '' }}"
       href="{{ route('user.qr.index', ['userId' => $userId]) }}">
        <i class="ri-qr-code-line"></i>
        <span>QR Kelas</span>
    </a>
</li>

<li class="menu-title"><span>Akademik</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.jadwal-kbm.') ? ' active' : '' }}"
       href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-schedule-line"></i>
        <span>Jadwal Pelajaran</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.jam-pelajaran.') ? ' active' : '' }}"
       href="{{ route('user.jam-pelajaran.index', ['userId' => $userId]) }}">
        <i class="ri-timer-line"></i>
        <span>Jam Pelajaran</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.kaldik.') ? ' active' : '' }}"
       href="{{ route('user.kaldik.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-event-line"></i>
        <span>Kalender Pendidikan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.pekan-efektif.') ? ' active' : '' }}"
       href="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-todo-line"></i>
        <span>Pekan Efektif</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.kurikulum.') ? ' active' : '' }}"
       href="#pimpinan_kurikulum" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActivePimpinan($currentRoute, 'user.kurikulum.') ? 'true' : 'false' }}"
       aria-controls="pimpinan_kurikulum">
        <i class="ri-book-open-line"></i>
        <span>Kurikulum &amp; Perangkat</span>
    </a>
    <div class="collapse menu-dropdown{{ isActivePimpinan($currentRoute, 'user.kurikulum.') ? ' show' : '' }}" id="pimpinan_kurikulum">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.kurikulum.index') ? ' active' : '' }}" href="{{ route('user.kurikulum.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Peta Kurikulum</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.kurikulum.cp.') ? ' active' : '' }}" href="{{ route('user.kurikulum.cp.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Capaian Pembelajaran</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.kurikulum.tp.') ? ' active' : '' }}" href="{{ route('user.kurikulum.tp.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Tujuan Pembelajaran</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.kurikulum.atp.') ? ' active' : '' }}" href="{{ route('user.kurikulum.atp.index', ['userId' => $userId]) }}" style="font-size:0.85rem">ATP</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.kurikulum.prota.') ? ' active' : '' }}" href="{{ route('user.kurikulum.prota.index', ['userId' => $userId]) }}" style="font-size:0.85rem">PROTA</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.kurikulum.prosem.') ? ' active' : '' }}" href="{{ route('user.kurikulum.prosem.index', ['userId' => $userId]) }}" style="font-size:0.85rem">PROSEM</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.kurikulum.realisasi.') ? ' active' : '' }}" href="{{ route('user.kurikulum.realisasi.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Realisasi Pembelajaran</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.kurikulum.perangkat.') ? ' active' : '' }}" href="{{ route('user.kurikulum.perangkat.index', ['userId' => $userId]) }}" style="font-size:0.85rem">Perangkat Pembelajaran</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.schools.') ? ' active' : '' }}"
       href="{{ route('user.schools.index', ['userId' => $userId]) }}">
        <i class="ri-government-line"></i>
        <span>Satuan Pendidikan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.grade-levels.') || isActivePimpinan($currentRoute, 'user.study-groups.') ? ' active' : '' }}"
       href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i>
        <span>Data Kelas</span>
    </a>
</li>

<li class="menu-title"><span>Laporan Pimpinan</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.') ? ' active' : '' }}"
       href="#rapor_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.') ? 'true' : 'false' }}"
       aria-controls="rapor_gtk">
        <i class="ri-newspaper-line"></i>
        <span>Rapor GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.') ? ' show' : '' }}" id="rapor_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.akademik') ? ' active' : '' }}" href="{{ route('user.rapor-gtk.akademik', ['userId' => $userId]) }}" style="font-size:0.85rem">Penilaian Akademik</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.disiplin') ? ' active' : '' }}" href="{{ route('user.rapor-gtk.disiplin', ['userId' => $userId]) }}" style="font-size:0.85rem">Penilaian Disiplin</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.rapor-gtk.tahunan') ? ' active' : '' }}" href="{{ route('user.rapor-gtk.tahunan', ['userId' => $userId]) }}" style="font-size:0.85rem">Rekap Tahunan</a></li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.laporan.') ? ' active' : '' }}"
       href="{{ route('user.laporan.index', ['userId' => $userId]) }}">
        <i class="ri-bar-chart-2-line"></i>
        <span>Laporan Umum</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.') ? ' active' : '' }}"
       href="#analisis" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.') ? 'true' : 'false' }}"
       aria-controls="analisis">
        <i class="ri-pencil-ruler-2-line"></i>
        <span>Analisis GTK</span>
    </a>
    <div class="collapse menu-dropdown{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.') ? ' show' : '' }}" id="analisis">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.beban-kerja') ? ' active' : '' }}" href="{{ route('user.analisis-gtk.beban-kerja', ['userId' => $userId]) }}" style="font-size:0.85rem">Beban Kerja</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.rasio-ideal') ? ' active' : '' }}" href="{{ route('user.analisis-gtk.rasio-ideal', ['userId' => $userId]) }}" style="font-size:0.85rem">Rasio Ideal</a></li>
            <li class="nav-item"><a class="nav-link{{ isActivePimpinan($currentRoute, 'user.analisis-gtk.proyeksi') ? ' active' : '' }}" href="{{ route('user.analisis-gtk.proyeksi', ['userId' => $userId]) }}" style="font-size:0.85rem">Proyeksi SDM</a></li>
        </ul>
    </div>
</li>

<li class="menu-title"><span>Pengasuhan</span></li>

<li class="nav-item">
    @php
        $__pimpinanAsramaUuid = request()->route()?->parameters()['asramaUuid'] ?? null;
        if (empty($__pimpinanAsramaUuid)) {
            $__firstAsrama = \App\Models\Dormitory::where('is_active', true)->first();
            $__pimpinanAsramaUuid = $__firstAsrama?->id;
        }
    @endphp
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.asrama.') ? ' active' : '' }}"
       href="{{ $__pimpinanAsramaUuid ? route('user.asrama.residents.index', ['userId' => $userId, 'asramaUuid' => $__pimpinanAsramaUuid]) : route('root') }}">
        <i class="ri-hotel-line"></i>
        <span>Daftar Penghuni Asrama</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActivePimpinan($currentRoute, 'user.uks.') ? ' active' : '' }}"
       href="{{ route('user.uks.health-checkups.index', ['userId' => $userId]) }}">
        <i class="ri-heart-pulse-line"></i>
        <span>UKS & Kesehatan</span>
    </a>
</li>

