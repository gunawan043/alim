{{-- Modul Tahfidz — mode: teacher (guru tahfidz) | manage (koordinator/KSP/Waka) --}}
@php $tahfidzMode = $tahfidzMode ?? 'manage'; @endphp
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp
{{-- Menyusun RPP tahfizh hanya untuk pengelola kurikulum tahfidz / koordinator
     tahfidz sekolah. KSP & Waka boleh melihat daftar, tetapi tidak menyusun. --}}
@php
    $tahfidzSidebarUser = auth()->user();
    $canManageTahfidzRpp = ($showAll ?? false) || ($tahfidzSidebarUser && (
        app(\App\Services\TahfidzAccess::class)->canManageCurriculum($tahfidzSidebarUser)
        || app(\App\Services\TahfidzAccess::class)->isSchoolCoordinator($tahfidzSidebarUser, $sekolahId ?? null)
    ));
@endphp
@if($tahfidzMode === 'teacher')
    {{-- Menu guru tahfidz disusun menurut urutan kerja harian:
         absensi halaqah → setoran → mutaba'ah → catatan → ujian → nilai.
         Menu umum seperti jadwal mengajar, mapel, dan perangkat kurikulum
         tidak diulang di sini. --}}
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.workspace.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.workspace.index', ['userId' => $userId]) }}">
            <i class="ri-book-2-line"></i><span>Buku Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.attendance.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.attendance.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-check-line"></i><span>Absensi Halaqah</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.setoran-bulk.', 'user.tahfidz.setoran.create', 'user.tahfidz.setoran.store', 'user.tahfidz.input.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.setoran-bulk.index', ['userId' => $userId]) }}">
            <i class="ri-file-add-line"></i><span>Input Setoran Harian</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.monitoring.sheet']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.monitoring.sheet', ['userId' => $userId]) }}">
            <i class="ri-file-chart-line"></i><span>Lembar Monitoring</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.penilaian.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.penilaian.index', ['userId' => $userId]) }}">
            <i class="ri-file-list-3-line"></i><span>Buku Nilai Tahfidz</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.format.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.format.index', ['userId' => $userId]) }}">
            <i class="ri-price-tag-3-line"></i><span>Format Penilaian Mapel</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.tasmi.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.tasmi.index', ['userId' => $userId]) }}">
            <i class="ri-award-line"></i><span>Tasmi’ &amp; Syahadah</span>
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
            <i class="ri-bar-chart-box-line"></i><span>Laporan Grafik</span>
        </a>
    </li>

    @php
        $tahfidzKelasActive = isActiveAny($currentRoute, [
            'user.tahfidz.halaqah.', 'user.tahfidz.progress.', 'user.tahfidz.monitoring.',
            'user.tahfidz.targets.', 'user.tahfidz.setoran.',
        ]);
    @endphp
    <li class="nav-item">
        <a class="nav-link menu-link{{ $tahfidzKelasActive ? ' active' : '' }}"
           href="#sp{{ $uid }}_tahfidz_santri" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $tahfidzKelasActive ? 'true' : 'false' }}" aria-controls="sp{{ $uid }}_tahfidz_santri">
            <i class="ri-team-line"></i><span>Halaqah &amp; Santri</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ $tahfidzKelasActive ? ' show' : '' }}" id="sp{{ $uid }}_tahfidz_santri">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.halaqah.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.halaqah.index', ['userId' => $userId]) }}">Daftar Halaqah</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.progress.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.progress.index', ['userId' => $userId]) }}">Progres Hafalan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.targets.']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.targets.index', ['userId' => $userId]) }}">Target Hafalan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.setoran.index', 'user.tahfidz.setoran.edit', 'user.tahfidz.setoran.update']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.setoran.index', ['userId' => $userId]) }}">Riwayat Setoran</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.tahfidz.monitoring.index', 'user.tahfidz.monitoring.notify']) ? ' active' : '' }}"
                       href="{{ route('user.tahfidz.monitoring.index', ['userId' => $userId]) }}">Santri Perlu Perhatian</a>
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
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.tahfidz.setoran-bulk.']) ? ' active' : '' }}"
           href="{{ route('user.tahfidz.setoran-bulk.index', ['userId' => $userId]) }}">
            <i class="ri-file-add-line"></i><span>Input Setoran Harian</span>
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
            'user.tahfidz.rpp.', 'user.tahfidz.muqorror.',
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
                @if($canManageTahfidzRpp)
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('user.tahfidz.rpp.create', ['userId' => $userId]) }}">Susun RPP Tahfizh</a>
                    </li>
                @endif
            </ul>
        </div>
    </li>
@endif
