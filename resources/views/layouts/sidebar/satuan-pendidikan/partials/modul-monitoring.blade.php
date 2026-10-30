{{-- Modul Monitoring Kehadiran (KSP, Waka, Koor Kurikulum, TU) --}}
@if(canPermission('teacher-attendance_view'))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teacher-qr.waka-dashboard']) ? ' active' : '' }}"
           href="{{ route('user.teacher-qr.waka-dashboard', ['userId' => $userId]) }}">
            <i class="ri-dashboard-2-line"></i><span>Kehadiran Guru Per Jam</span>
        </a>
    </li>
@endif

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kehadiran.pergantian-jam']) ? ' active' : '' }}"
       href="{{ route('user.kehadiran.pergantian-jam', ['userId' => $userId]) }}">
        <i class="ri-refresh-line"></i><span>Rekap Pergantian Jam</span>
    </a>
</li>
