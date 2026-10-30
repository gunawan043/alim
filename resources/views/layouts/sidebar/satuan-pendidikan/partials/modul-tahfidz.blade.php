{{-- Modul Tahfidz — mode: teacher (guru tahfidz) | manage (koordinator/KSP/Waka) --}}
@php $tahfidzMode = $tahfidzMode ?? 'manage'; @endphp
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp
@if($tahfidzMode === 'teacher')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.workspace.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.workspace.index', ['userId' => $userId]) }}">
            <i class="ri-book-2-line"></i><span>Buku Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.setoran.create', 'user.tahfidz.setoran.store']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.setoran.create', ['userId' => $userId]) }}">
            <i class="ri-add-box-line"></i><span>Input Setoran</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.monitoring.sheet']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.monitoring.sheet', ['userId' => $userId]) }}">
            <i class="ri-file-chart-line"></i><span>Lembar Monitoring</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.gradebook.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.gradebook.index', ['userId' => $userId]) }}">
            <i class="ri-book-3-line"></i><span>Buku Nilai</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.reports.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.reports.index', ['userId' => $userId]) }}">
            <i class="ri-bar-chart-box-line"></i><span>Laporan &amp; Grafik</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.attendance.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.attendance.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-check-line"></i><span>Absensi Halaqah</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.tasmi.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.tasmi.index', ['userId' => $userId]) }}">
            <i class="ri-award-line"></i><span>Tasmi’ &amp; Syahadah</span>
        </a>
    </li>

    @php
        $tahfidzEvalActive = isActiveAny($currentRoute, [
            'user.tahfidz.halaqah.', 'user.tahfidz.progress.', 'user.tahfidz.monitoring.',
            'user.tahfidz.interventions.', 'user.tahfidz.targets.', 'user.tahfidz.setoran.',
            'user.tahfidz.monitoring.sheet.',
            'user.tahfidz.muqorror.', 'user.tahfidz.rpp.',
        ]);
    @endphp
    <li class="nav-item">
        <a class="nav-link menu-link{{ $tahfidzEvalActive ? ' active' : '' }}"
           href="#sp{{ $uid }}_tahfidz_evaluasi" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $tahfidzEvalActive ? 'true' : 'false' }}" aria-controls="sp{{ $uid }}_tahfidz_evaluasi">
            <i class="ri-line-chart-line"></i><span>Evaluasi &amp; Perangkat</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ $tahfidzEvalActive ? ' show' : '' }}" id="sp{{ $uid }}_tahfidz_evaluasi">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.halaqah.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Halaqah &amp; Jadwal</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.setoran.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.setoran.index', ['userId' => $userId]) }}">Riwayat Setoran</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.progress.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.progress.index', ['userId' => $userId]) }}">Progres &amp; Rekap</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.monitoring.index', 'user.tahfidz.monitoring.notify']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.monitoring.index', ['userId' => $userId]) }}">Monitoring Santri</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.interventions.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.interventions.index', ['userId' => $userId]) }}">Tindak Lanjut</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.targets.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.targets.index', ['userId' => $userId]) }}">Target Hafalan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.muqorror.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.muqorror.index', ['userId' => $userId]) }}">Muqorror Tahfizh</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.rpp.']) && request('type') !== 'iqro' ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.rpp.index', ['userId' => $userId]) }}">RPP Tahfizh</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.rpp.']) && request('type') === 'iqro' ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.rpp.index', ['userId' => $userId, 'type' => 'iqro']) }}">RPP Iqro’</a>
                </li>
            </ul>
        </div>
    </li>
@endif

@if($tahfidzMode === 'manage')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.monitoring.index', 'user.tahfidz.monitoring.notify']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.monitoring.index', ['userId' => $userId]) }}">
            <i class="ri-line-chart-line"></i><span>Ringkasan Tahfidz</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.halaqah.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">
            <i class="ri-team-line"></i><span>Halaqah</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.monitoring.sheet']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.monitoring.sheet', ['userId' => $userId]) }}">
            <i class="ri-file-chart-line"></i><span>Lembar Monitoring</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.gradebook.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.gradebook.index', ['userId' => $userId]) }}">
            <i class="ri-book-3-line"></i><span>Buku Nilai</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.setoran.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.setoran.index', ['userId' => $userId]) }}">
            <i class="ri-file-add-line"></i><span>Setoran Hafalan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.tasmi.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.tasmi.index', ['userId' => $userId]) }}">
            <i class="ri-award-line"></i><span>Tasmi’ &amp; Syahadah</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.muqorror.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.muqorror.index', ['userId' => $userId]) }}">
            <i class="ri-book-open-line"></i><span>Muqorror Tahfizh</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.executive.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.executive.index', ['userId' => $userId]) }}">
            <i class="ri-bar-chart-box-line"></i><span>Laporan Tahfidz</span>
        </a>
    </li>

    @php
        $tahfidzAdvancedActive = isActiveAny($currentRoute, [
            'user.tahfidz.audit.', 'user.tahfidz.consistency.', 'user.tahfidz.interventions.',
            'user.tahfidz.rpp.',
        ]);
    @endphp
    <li class="nav-item">
        <a class="nav-link menu-link{{ $tahfidzAdvancedActive ? ' active' : '' }}"
           href="#sp{{ $uid }}_tahfidz_perangkat" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $tahfidzAdvancedActive ? 'true' : 'false' }}" aria-controls="sp{{ $uid }}_tahfidz_perangkat">
            <i class="ri-settings-4-line"></i><span>Perangkat &amp; Audit</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ $tahfidzAdvancedActive ? ' show' : '' }}" id="sp{{ $uid }}_tahfidz_perangkat">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.audit.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.audit.index', ['userId' => $userId]) }}">Audit Akhir</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.consistency.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.consistency.index', ['userId' => $userId]) }}">Konsistensi Rapor–Leger</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.interventions.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.interventions.index', ['userId' => $userId]) }}">Tindak Lanjut</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.rpp.']) && request('type') !== 'iqro' ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.rpp.index', ['userId' => $userId]) }}">RPP Tahfizh</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.rpp.']) && request('type') === 'iqro' ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.rpp.index', ['userId' => $userId, 'type' => 'iqro']) }}">RPP Iqro’</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('user.tahfidz.rpp.create', ['userId' => $userId]) }}">Susun RPP Tahfizh</a>
                </li>
            </ul>
        </div>
    </li>
@endif
