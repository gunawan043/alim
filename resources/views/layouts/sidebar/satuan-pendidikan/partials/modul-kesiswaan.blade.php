{{-- Modul Kesiswaan — mode: admin (TU/KSP/Waka) | koordinator | bk --}}
@php $kesiswaanMode = $kesiswaanMode ?? 'admin'; @endphp
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp
@if(in_array($kesiswaanMode, ['admin', 'koordinator'], true))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.index', 'user.students.show']) ? ' active' : '' }}"
           href="{{ route('user.students.index', ['userId' => $userId]) }}">
            <i class="ri-team-line"></i><span>Data Santri</span>
        </a>
    </li>
@endif

@if($kesiswaanMode === 'admin')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.mahroms.']) ? ' active' : '' }}"
           href="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}">
            <i class="ri-parent-line"></i><span>Data Mahrom</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.import-form']) ? ' active' : '' }}"
           href="{{ route('user.students.import-form', ['userId' => $userId]) }}">
            <i class="ri-upload-2-line"></i><span>Import Data Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.alumni.']) ? ' active' : '' }}"
           href="{{ route('user.alumni.index', ['userId' => $userId]) }}">
            <i class="ri-group-2-line"></i><span>Data Alumni</span>
        </a>
    </li>
@endif

@if(in_array($kesiswaanMode, ['admin', 'koordinator'], true))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.study-groups.']) ? ' active' : '' }}"
           href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">
            <i class="ri-layout-grid-line"></i><span>Pengaturan Rombel</span>@include('layouts.sidebar.partials.indicator-badge', ['key' => 'rombel_tanpa_wali', 'title' => 'Rombel belum memiliki wali kelas'])</a>
    </li>

    @php
        $rombelByGrade = $sidebarStudyGroups->groupBy(
            fn ($sg) => $sg->gradeLevel?->name ?? 'Tanpa Tingkat'
        );
    @endphp
    <li class="nav-item">
        <a class="nav-link{{ $currentRoute === 'user.students.index' && request('study_group_id') ? ' active' : '' }}"
           href="#sp{{ $uid }}_rombel_list" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ $currentRoute === 'user.students.index' && request('study_group_id') ? 'true' : 'false' }}"
           aria-controls="sp{{ $uid }}_rombel_list">
            <i class="ri-group-line"></i><span>Santri per Rombel</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ $currentRoute === 'user.students.index' && request('study_group_id') ? ' show' : '' }}"
             id="sp{{ $uid }}_rombel_list">
            <ul class="nav nav-sm flex-column">
                @forelse($rombelByGrade as $gradeName => $gradeGroups)
                    <li class="nav-item mt-1">
                        <span class="nav-link text-uppercase text-muted small fw-semibold">{{ $gradeName }}</span>
                    </li>
                    @foreach($gradeGroups as $sg)
                        <li class="nav-item">
                            <a class="nav-link{{ $currentRoute === 'user.students.index' && request('study_group_id') == $sg->id ? ' active' : '' }}"
                               href="{{ route('user.students.index', ['userId' => $userId, 'study_group_id' => $sg->id]) }}"
                               title="{{ $sg->full_name }}{{ $sg->homeroomTeacher ? ' — Wali: '.$sg->homeroomTeacher->name : '' }}">
                                {{ $sg->full_name }}
                            </a>
                        </li>
                    @endforeach
                @empty
                    <li class="nav-item">
                        <span class="nav-link text-muted">Belum ada rombel</span>
                    </li>
                @endforelse
            </ul>
        </div>
    </li>
@endif

@if($kesiswaanMode === 'admin')
    <li class="nav-item">
        <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-do.', 'user.mutations-lulus.', 'user.student-move.']) ? ' active' : '' }}"
           href="#sp{{ $uid }}_mutasi_pd" data-bs-toggle="collapse" role="button"
           aria-expanded="{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-do.', 'user.mutations-lulus.', 'user.student-move.']) ? 'true' : 'false' }}"
           aria-controls="sp{{ $uid }}_mutasi_pd">
            <i class="ri-swap-box-line"></i><span>Mutasi PD</span><span class="menu-arrow"></span>
        </a>
        <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.mutations-in.', 'user.mutations-out.', 'user.mutations-do.', 'user.mutations-lulus.', 'user.student-move.']) ? ' show' : '' }}"
             id="sp{{ $uid }}_mutasi_pd">
            <ul class="nav nav-sm flex-column">
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-in.']) ? ' active' : '' }}"
                       href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}">Mutasi Masuk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-out.']) ? ' active' : '' }}"
                       href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}">
                        Mutasi Keluar
                        @include('layouts.sidebar.partials.indicator-badge', ['key' => 'mutasi_pending', 'title' => 'Pengajuan mutasi menunggu persetujuan'])
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-do.']) ? ' active' : '' }}"
                       href="{{ route('user.mutations-do.index', ['userId' => $userId]) }}">Drop Out</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-lulus.']) ? ' active' : '' }}"
                       href="{{ route('user.mutations-lulus.index', ['userId' => $userId]) }}">Lulus</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link{{ isActiveAny($currentRoute, ['user.student-move.']) ? ' active' : '' }}"
                       href="{{ route('user.student-move.index', ['userId' => $userId]) }}">Pindahkan Santri</a>
                </li>
            </ul>
        </div>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.schools.nilai-kelas.']) ? ' active' : '' }}"
           href="{{ route('user.schools.nilai-kelas.index', ['userId' => $userId]) }}">
            <i class="ri-table-line"></i><span>Leger &amp; Cetak Rapor</span>
        </a>
    </li>
@endif

@if(in_array($kesiswaanMode, ['admin', 'koordinator'], true))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.dashboard']) ? ' active' : '' }}"
           href="{{ route('user.violation-points.dashboard', ['userId' => $userId]) }}">
            <i class="ri-dashboard-2-line"></i><span>Dashboard Kedisiplinan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.index', 'user.violation-points.create', 'user.violation-points.show']) ? ' active' : '' }}"
           href="{{ route('user.violation-points.index', ['userId' => $userId]) }}">
            <i class="ri-error-warning-line"></i><span>Poin Pelanggaran</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.violation-points.recap']) ? ' active' : '' }}"
           href="{{ route('user.violation-points.recap', ['userId' => $userId]) }}">
            <i class="ri-bar-chart-2-line"></i><span>Rekap Pelanggaran</span>
        </a>
    </li>

    @include('layouts.sidebar.satuan-pendidikan.partials.modul-prestasi', ['uid' => $uid.'_prestasi'])
@endif

@if(in_array($kesiswaanMode, ['admin', 'koordinator', 'bk'], true))
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kesiswaan.counseling-records.', 'user.uks.counseling-records.']) ? ' active' : '' }}"
           href="{{ route('user.kesiswaan.counseling-records.index', ['userId' => $userId]) }}">
            <i class="ri-chat-heart-line"></i><span>Konseling Santri</span>
        </a>
    </li>
@endif
