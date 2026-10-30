{{-- Modul Akademik (pengaturan akademik satuan pendidikan) --}}
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

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.pekan-efektif.']) ? ' active' : '' }}"
       href="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
        <i class="ri-calendar-todo-line"></i><span>Pekan Efektif</span>
    </a>
</li>
