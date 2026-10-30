{{-- Modul Absensi Kehadiran — mode: self (guru) | admin (TU) | koordinator --}}
@php $absensiMode = $absensiMode ?? 'self'; @endphp

@if($absensiMode === 'self')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.scan']) ? ' active' : '' }}"
           href="{{ route('user.teacher-qr.scan', ['userId' => $userId]) }}">
            <i class="ri-qr-scan-2-line"></i><span>Scan QR Kehadiran</span>
        </a>
    </li>

    @if(canPermission('teacher-attendance_view'))
        <li class="nav-item">
            <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.history']) ? ' active' : '' }}"
               href="{{ route('user.teacher-qr.history', ['userId' => $userId]) }}">
                <i class="ri-history-line"></i><span>Riwayat Kehadiran Saya</span>
            </a>
        </li>
    @endif
@endif

@if(in_array($absensiMode, ['admin', 'koordinator'], true))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.qr.']) ? ' active' : '' }}"
           href="{{ route('user.qr.index', ['userId' => $userId]) }}">
            <i class="ri-qr-code-line"></i><span>QR Kelas</span>
        </a>
    </li>
@endif

@if($absensiMode === 'admin')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.']) && ! isActiveAny($currentRoute, ['user.absensi-gtk.settings']) ? ' active' : '' }}"
           href="{{ route('user.absensi-gtk.index', ['userId' => $userId]) }}">
            <i class="ri-contacts-book-line"></i><span>Absensi GTK</span>
        </a>
    </li>
@endif

@if(in_array($absensiMode, ['admin', 'koordinator'], true))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi.harian.']) ? ' active' : '' }}"
           href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-check-line"></i><span>Absensi Peserta Didik</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'absensi_all', 'title' => 'Kelas yang belum diabsen hari ini'])</a>
    </li>
@endif

@if(in_array($absensiMode, ['admin', 'koordinator'], true))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.settings']) ? ' active' : '' }}"
           href="{{ route('user.absensi-gtk.settings', ['userId' => $userId]) }}">
            <i class="ri-settings-3-line"></i><span>Pengaturan Toleransi &amp; Lokasi</span>
        </a>
    </li>
@endif

@if($absensiMode === 'admin' && ($showAll || $canManualAbsen))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.manual']) ? ' active' : '' }}"
           href="{{ route('user.teacher-qr.manual', ['userId' => $userId]) }}">
            <i class="ri-keyboard-line"></i><span>Absen Manual Guru</span>
        </a>
    </li>
@endif
