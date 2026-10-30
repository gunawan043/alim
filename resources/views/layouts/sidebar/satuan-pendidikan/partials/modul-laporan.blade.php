{{-- Modul Laporan (KSP, Waka, Kepala TU) --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ $currentRoute === 'user.laporan.index' ? ' active' : '' }}"
       href="{{ route('user.laporan.index', ['userId' => $userId]) }}">
        <i class="ri-file-list-2-line"></i><span>Semua Laporan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.akademik']) ? ' active' : '' }}"
       href="{{ route('user.laporan.akademik', ['userId' => $userId]) }}">
        <i class="ri-line-chart-line"></i><span>Analisis Akademik</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.presensi']) ? ' active' : '' }}"
       href="{{ route('user.laporan.presensi', ['userId' => $userId]) }}">
        <i class="ri-calendar-check-line"></i><span>Laporan Presensi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.populasi.']) ? ' active' : '' }}"
       href="{{ route('user.laporan.populasi.mutasi', ['userId' => $userId]) }}">
        <i class="ri-swap-box-line"></i><span>Mutasi &amp; Populasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.santri']) ? ' active' : '' }}"
       href="{{ route('user.laporan.santri', ['userId' => $userId]) }}">
        <i class="ri-user-star-line"></i><span>Laporan Santri</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.gtk']) ? ' active' : '' }}"
       href="{{ route('user.laporan.gtk', ['userId' => $userId]) }}">
        <i class="ri-team-line"></i><span>Laporan GTK</span>
    </a>
</li>
