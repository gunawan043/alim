@menuallowed('tugas-koordinator-lab')
@php
    $showAll = $isSystemAdmin ?? false;
    $koorLabUrl = function (string $routeName, array $extra = []) {
        try { return route($routeName, $extra); } catch (\Throwable $e) { return '#'; }
    };
    $labMenuUserId = $userId ?? auth()->id();
@endphp

<li class="menu-title"><span>Koordinator Laboratorium</span></li>

<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.index', 'lab.inventory']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.index', ['userId' => $labMenuUserId]) }}"><i class="ri-flask-line"></i><span>Dashboard Laboratorium</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.inspection.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.inspection.index', ['userId' => $labMenuUserId]) }}"><i class="ri-search-eye-line"></i><span>Pemeriksaan Berkala</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.calibration.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.calibration.index', ['userId' => $labMenuUserId]) }}"><i class="ri-timer-line"></i><span>Kalibrasi Alat</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.loan.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.loan.index', ['userId' => $labMenuUserId]) }}"><i class="ri-arrow-right-up-line"></i><span>Peminjaman & Pengembalian</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.activity.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.activity.index', ['userId' => $labMenuUserId]) }}"><i class="ri-book-open-line"></i><span>Kegiatan & Praktikum</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.usage.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.usage.index', ['userId' => $labMenuUserId]) }}"><i class="ri-file-shield-2-line"></i><span>Izin Penggunaan</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.schedule.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.schedule.index', ['userId' => $labMenuUserId]) }}"><i class="ri-calendar-schedule-line"></i><span>Jadwal Laboratorium</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.safety.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.safety.index', ['userId' => $labMenuUserId]) }}"><i class="ri-shield-check-line"></i><span>Inspeksi Keselamatan K3</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.incident.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.incident.index', ['userId' => $labMenuUserId]) }}"><i class="ri-alert-line"></i><span>Insiden & Kecelakaan</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.disposal.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.disposal.index', ['userId' => $labMenuUserId]) }}"><i class="ri-delete-bin-6-line"></i><span>Penghapusan Inventaris</span></a></li>
<li class="nav-item"><a class="nav-link menu-link{{ isActiveAny($currentRoute, ['lab.evaluation.']) ? ' active' : '' }}" href="{{ $koorLabUrl('lab.evaluation.index', ['userId' => $labMenuUserId]) }}"><i class="ri-file-chart-line"></i><span>Evaluasi Akhir Semester</span></a></li>
<li class="menu-title"><span>Referensi Sarpras</span></li>
<li class="nav-item"><a class="nav-link menu-link" href="{{ $koorLabUrl('sarpras.user.ruang.index', ['userId' => $labMenuUserId]) }}"><i class="ri-door-open-line"></i><span>Ruang & Laboratorium</span></a></li>
<li class="nav-item"><a class="nav-link menu-link" href="{{ $koorLabUrl('sarpras.user.aset.index', ['userId' => $labMenuUserId]) }}"><i class="ri-archive-2-line"></i><span>Master Aset Sarpras</span></a></li>
<li class="nav-item"><a class="nav-link menu-link" href="{{ $koorLabUrl('sarpras.user.kerusakan.index', ['userId' => $labMenuUserId]) }}"><i class="ri-tools-line"></i><span>Lapor Kerusakan</span></a></li>
<li class="nav-item"><a class="nav-link menu-link" href="{{ $koorLabUrl('sarpras.user.pengadaan.index', ['userId' => $labMenuUserId]) }}"><i class="ri-shopping-bag-3-line"></i><span>Permintaan Pengadaan</span></a></li>
@endmenuallowed
