{{-- Modul Akademik (pengaturan akademik satuan pendidikan) --}}
@php $hidePekanEfektif = $hidePekanEfektif ?? false; @endphp
@php $uid = $uid ?? '_'.substr(md5(uniqid('', true)), 0, 6); @endphp

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.academic-years.']) ? ' active' : '' }}"
       href="{{ route('user.academic-years.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-2-line"></i><span>Tahun Ajaran</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.subjects.']) ? ' active' : '' }}"
       href="{{ route('user.subjects.index', ['userId' => $userId]) }}">
        <i class="ri-book-2-line"></i><span>Mata Pelajaran</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.institution-decrees.']) ? ' active' : '' }}"
       href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">
        <i class="ri-file-paper-2-line"></i><span>SK &amp; Keputusan</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jadwal-kbm.index', 'user.jadwal-kbm.generate']) ? ' active' : '' }}"
       href="{{ route('user.jadwal-kbm.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-schedule-line"></i><span>Jadwal Pelajaran</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.jam-pelajaran.']) ? ' active' : '' }}"
       href="{{ route('user.jam-pelajaran.index', ['userId' => $userId]) }}">
        <i class="ri-timer-line"></i><span>Jam Pelajaran</span>
    </a>
</li>

@if(! $hidePekanEfektif)
    <li class="nav-item">
        <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.pekan-efektif.']) ? ' active' : '' }}"
           href="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
            <i class="ri-calendar-todo-line"></i><span>Pekan Efektif</span>
        </a>
    </li>
@endif

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? ' active' : '' }}"
       href="#sp{{ $uid }}_kaldik" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? 'true' : 'false' }}"
       aria-controls="sp{{ $uid }}_kaldik">
        <i class="ri-calendar-event-line"></i><span>Kalender Akademik</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.kaldik.', 'user.kalender-kegiatan.']) ? ' show' : '' }}"
         id="sp{{ $uid }}_kaldik">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
                   href="{{ route('user.kaldik.index', ['userId' => $userId]) }}">Kalender Pendidikan</a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.kalender-kegiatan.']) ? ' active' : '' }}"
                   href="{{ route('user.kalender-kegiatan.akademik', ['userId' => $userId]) }}">Agenda Kegiatan</a>
            </li>
        </ul>
    </div>
</li>
