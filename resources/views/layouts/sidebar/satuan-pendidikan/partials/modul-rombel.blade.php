{{-- Modul Rombel — mode: manage (KSP/Waka/TU) | koordinator --}}
@php $rombelMode = $rombelMode ?? 'manage'; @endphp
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp

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

@if($rombelMode === 'manage')
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.grade-levels.']) ? ' active' : '' }}"
           href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">
            <i class="ri-layout-grid-fill"></i><span>Data Kelas &amp; Mapel</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.teaching-assignments.']) ? ' active' : '' }}"
           href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">
            <i class="ri-user-star-line"></i><span>Plotting Guru Mengajar</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.student-move.']) ? ' active' : '' }}"
           href="{{ route('user.student-move.index', ['userId' => $userId]) }}">
            <i class="ri-arrow-left-right-line"></i><span>Pindahkan Santri</span>
        </a>
    </li>
@endif
