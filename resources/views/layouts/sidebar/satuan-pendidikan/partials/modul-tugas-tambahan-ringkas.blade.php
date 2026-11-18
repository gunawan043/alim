@php
    $taskUrl = function (string $routeName, array $extra = []) use ($userId) {
        try {
            return route($routeName, array_merge(['userId' => $userId], $extra));
        } catch (\Throwable) {
            return '#';
        }
    };
@endphp

@if($isKoorKurik || $isTimKurikulum)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-kurikulum', 'user.dashboard.satuan-pendidikan']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.dashboard.koordinator-kurikulum') }}">
            <i class="ri-dashboard-3-line"></i><span>Dashboard Kurikulum</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kurikulum.realisasi.monitoring']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.kurikulum.realisasi.monitoring') }}">
            <i class="ri-line-chart-line"></i><span>Monitoring Jurnal</span>
        </a>
    </li>
@endif

@if($isKoorKesiswa || $isTimKesiswaan || $isGuruBK)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-kesiswaan']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.dashboard.koordinator-kesiswaan') }}">
            <i class="ri-dashboard-3-line"></i><span>Dashboard Kesiswaan</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.index']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.violation-points.index') }}">
            <i class="ri-error-warning-line"></i><span>Poin Pelanggaran</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kesiswaan.lateness.']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.kesiswaan.lateness.index') }}">
            <i class="ri-timer-line"></i><span>Keterlambatan</span>
        </a>
    </li>
@endif

@if($isKoorMapel)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-guru']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.dashboard.koordinator-guru') }}">
            <i class="ri-team-line"></i><span>Dashboard Koordinator Mapel</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asesmen.']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.asesmen.index') }}">
            <i class="ri-file-list-3-line"></i><span>Pusat Asesmen</span>
        </a>
    </li>
@endif

@if($isKoorLab)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.index']) ? ' active' : '' }}"
           href="{{ $taskUrl('lab.index') }}">
            <i class="ri-flask-line"></i><span>Dashboard Laboratorium</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.request.']) ? ' active' : '' }}"
           href="{{ $taskUrl('lab.request.index') }}">
            <i class="ri-file-shield-2-line"></i><span>Ajukan Izin Laboratorium</span>
        </a>
    </li>
@endif

@if($isKoorSarpras)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.koordinator-sarpras']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.dashboard.koordinator-sarpras') }}">
            <i class="ri-tools-line"></i><span>Dashboard Koordinator Sarpras</span>
        </a>
    </li>
@endif

@if($isKoorAlumni)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.alumni.index']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.alumni.index') }}">
            <i class="ri-graduation-cap-line"></i><span>Data Alumni</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.alumni.statistics']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.alumni.statistics') }}">
            <i class="ri-bar-chart-box-line"></i><span>Statistik Kelulusan</span>
        </a>
    </li>
@endif

@if($isPembinaEkskul && menu_allowed('ekstrakurikuler'))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['waka.ekstrakurikuler.']) ? ' active' : '' }}"
           href="{{ $taskUrl('waka.ekstrakurikuler.index') }}">
            <i class="ri-basketball-line"></i><span>Ekstrakurikuler</span>
        </a>
    </li>
@endif

@if($isBendahara && ! $jabBendahara)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.dashboard.bendahara']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.dashboard.bendahara') }}">
            <i class="ri-dashboard-3-line"></i><span>Dashboard Bendahara</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.laporan.keuangan']) ? ' active' : '' }}"
           href="{{ $taskUrl('user.laporan.keuangan') }}">
            <i class="ri-file-chart-2-line"></i><span>Laporan Keuangan</span>
        </a>
    </li>
@endif
