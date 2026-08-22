<ul class="navbar-nav">
    {{-- 1. UMUM --}}
    <li class="menu-title"><span>Umum</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('system.dashboard') ? ' active' : '' }}"
           href="{{ route('system.dashboard') }}">
            <i class="ri-home-6-line"></i>
            <span>Dashboard Sistem</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.profile.my') ? ' active' : '' }}"
           href="{{ route('user.profile.my', ['userId' => auth()->id()]) }}">
            <i class="ri-user-line"></i>
            <span>Profile</span>
        </a>
    </li>

    {{-- 2. PENGELOLAAN PONDOK & SEKOLAH --}}
    <li class="menu-title"><span>Pengelolaan Pondok & Sekolah</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.schools-global.*') ? ' active' : '' }}"
           href="{{ route('user.schools-global.index', ['userId' => auth()->id()]) }}">
            <i class="ri-government-line"></i>
            <span>Daftar Sekolah</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.dormitory-master.*') ? ' active' : '' }}"
           href="{{ route('user.dormitory-master.index', ['userId' => auth()->id()]) }}">
            <i class="ri-hotel-line"></i>
            <span>Daftar Asrama</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.boarding-policies.*') ? ' active' : '' }}"
           href="{{ route('user.boarding-policies.index', ['userId' => auth()->id()]) }}">
            <i class="ri-file-shield-2-line"></i>
            <span>Kebijakan Asrama</span>
        </a>
    </li>

    {{-- 3. GTK --}}
    <li class="menu-title"><span>GTK</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.gtk.index*') || request()->routeIs('user.gtk.indexguru') || request()->routeIs('user.gtk.indextendik') ? ' active' : '' }}"
           href="{{ route('user.gtk.index', ['userId' => auth()->id()]) }}">
            <i class="ri-team-line"></i>
            <span>Semua GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.gtk.create') ? ' active' : '' }}"
           href="{{ route('user.gtk.create', ['userId' => auth()->id()]) }}">
            <i class="ri-user-add-line"></i>
            <span>Tambah GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.gtk-requests.*') || request()->routeIs('user.approvals.*') ? ' active' : '' }}"
           href="{{ route('user.gtk-requests.index', ['userId' => auth()->id()]) }}">
            <i class="ri-git-pull-request-line"></i>
            <span>GTK Requests & Approval</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.pension.*') ? ' active' : '' }}"
           href="{{ route('user.pension.index', ['userId' => auth()->id()]) }}">
            <i class="ri-umbrella-line"></i>
            <span>Pensiun</span>
        </a>
    </li>

    {{-- 4. MASTER DATA --}}
    <li class="menu-title"><span>Master Data</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.master-data.satuan-kerja.*') ? ' active' : '' }}"
           href="{{ route('user.master-data.satuan-kerja.index', ['userId' => auth()->id()]) }}">
            <i class="ri-building-line"></i>
            <span>Satuan Kerja</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.master-data.jenis-gtk.*') ? ' active' : '' }}"
           href="{{ route('user.master-data.jenis-gtk.index', ['userId' => auth()->id()]) }}">
            <i class="ri-database-2-line"></i>
            <span>Jenis GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.master-data.jabatan.*') ? ' active' : '' }}"
           href="{{ route('user.master-data.jabatan.index', ['userId' => auth()->id()]) }}">
            <i class="ri-briefcase-line"></i>
            <span>Jabatan</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.dokumen-iso.*') ? ' active' : '' }}"
           href="{{ route('user.dokumen-iso.index', ['userId' => auth()->id()]) }}">
            <i class="ri-file-paper-2-line"></i>
            <span>Dokumen ISO</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.master-data.mata-pelajaran.*') ? ' active' : '' }}"
           href="{{ route('user.master-data.mata-pelajaran.index', ['userId' => auth()->id()]) }}">
            <i class="ri-book-line"></i>
            <span>Mata Pelajaran</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.divisi.*') ? ' active' : '' }}"
           href="{{ route('user.divisi.index', ['userId' => auth()->id()]) }}">
            <i class="ri-folder-line"></i>
            <span>Divisi</span>
        </a>
    </li>

    {{-- 5. SANTRI --}}
    <li class="menu-title"><span>Santri</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.students.*') ? ' active' : '' }}"
           href="{{ route('user.students.index', ['userId' => auth()->id()]) }}">
            <i class="ri-user-heart-line"></i>
            <span>Data Santri</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.mutations-in.*') ? ' active' : '' }}"
           href="{{ route('user.mutations-in.index', ['userId' => auth()->id()]) }}">
            <i class="ri-login-box-line"></i>
            <span>Mutasi Masuk</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.mutations-out.*') ? ' active' : '' }}"
           href="{{ route('user.mutations-out.index', ['userId' => auth()->id()]) }}">
            <i class="ri-logout-box-line"></i>
            <span>Mutasi Keluar</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.mutations-lulus.*') ? ' active' : '' }}"
           href="{{ route('user.mutations-lulus.index', ['userId' => auth()->id()]) }}">
            <i class="ri-graduation-cap-line"></i>
            <span>Mutasi Lulus</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.mutations-do.*') ? ' active' : '' }}"
           href="{{ route('user.mutations-do.index', ['userId' => auth()->id()]) }}">
            <i class="ri-heart-pulse-line"></i>
            <span>Mutasi DO</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.student-move.*') ? ' active' : '' }}"
           href="{{ route('user.student-move.index', ['userId' => auth()->id()]) }}">
            <i class="ri-arrow-left-right-line"></i>
            <span>Pindahkan Santri</span>
        </a>
    </li>

    {{-- 6. AKADEMIK --}}
    <li class="menu-title"><span>Akademik</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.academic-years.*') ? ' active' : '' }}"
           href="{{ route('user.academic-years.index', ['userId' => auth()->id()]) }}">
            <i class="ri-calendar-event-line"></i>
            <span>Tahun Ajaran</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.grade-levels.*') || request()->routeIs('user.study-groups.*') ? ' active' : '' }}"
           href="{{ route('user.grade-levels.index', ['userId' => auth()->id()]) }}">
            <i class="ri-stack-line"></i>
            <span>Kelas & Rombel</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.subjects.*') ? ' active' : '' }}"
           href="{{ route('user.subjects.index', ['userId' => auth()->id()]) }}">
            <i class="ri-book-open-line"></i>
            <span>Mata Pelajaran</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.teaching-assignments.*') ? ' active' : '' }}"
           href="{{ route('user.teaching-assignments.index', ['userId' => auth()->id()]) }}">
            <i class="ri-user-star-line"></i>
            <span>Penugasan Mengajar</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.other-teacher-tasks.*') ? ' active' : '' }}"
           href="{{ route('user.other-teacher-tasks.index', ['userId' => auth()->id()]) }}">
            <i class="ri-user-settings-line"></i>
            <span>Tugas Tambahan</span>
        </a>
    </li>

    {{-- 7. ABSENSI & KALDIK --}}
    <li class="menu-title"><span>Absensi & Agenda</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.absensi-gtk.*') ? ' active' : '' }}"
           href="{{ route('user.absensi-gtk.index', ['userId' => auth()->id()]) }}">
            <i class="ri-contacts-book-line"></i>
            <span>Absensi GTK</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.kaldik.*') ? ' active' : '' }}"
           href="{{ route('user.kaldik.index', ['userId' => auth()->id()]) }}">
            <i class="ri-task-line"></i>
            <span>Agenda Kegiatan / Kaldik</span>
        </a>
    </li>

    {{-- 8. SARPRAS --}}
    <li class="menu-title"><span>Sarana Prasarana</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('sarpras.*') ? ' active' : '' }}"
           href="{{ route('sarpras.user.dashboard', ['userId' => auth()->id()]) }}">
            <i class="ri-community-line"></i>
            <span>Sarpras</span>
        </a>
    </li>

    {{-- 9. ADMINISTRASI SISTEM --}}
    <li class="menu-title"><span>Administrasi Sistem</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.users.*') ? ' active' : '' }}"
           href="{{ route('user.sa.users.index', ['userId' => auth()->id()]) }}">
            <i class="ri-user-line"></i>
            <span>Manajemen User</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.roles.*') ? ' active' : '' }}"
           href="{{ route('user.sa.roles.index', ['userId' => auth()->id()]) }}">
            <i class="ri-group-line"></i>
            <span>Roles</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.permissions.*') ? ' active' : '' }}"
           href="{{ route('user.sa.permissions.index', ['userId' => auth()->id()]) }}">
            <i class="ri-key-line"></i>
            <span>Permissions</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.schools.*') ? ' active' : '' }}"
           href="{{ route('user.sa.schools.index', ['userId' => auth()->id()]) }}">
            <i class="ri-government-line"></i>
            <span>Manajemen Sekolah</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.dormitories.*') ? ' active' : '' }}"
           href="{{ route('user.sa.dormitories.index', ['userId' => auth()->id()]) }}">
            <i class="ri-hotel-line"></i>
            <span>Manajemen Asrama</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('system.features') ? ' active' : '' }}"
           href="{{ route('system.features') }}">
            <i class="ri-toggle-line"></i>
            <span>Feature Activation</span>
        </a>
    </li>

    {{-- 10. LOG & MONITORING --}}
    <li class="menu-title"><span>Log & Monitoring</span></li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.audit-logs.*') ? ' active' : '' }}"
           href="{{ route('user.sa.audit-logs.index', ['userId' => auth()->id()]) }}">
            <i class="ri-file-chart-line"></i>
            <span>Audit Log</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.password-reset-logs.*') ? ' active' : '' }}"
           href="{{ route('user.sa.password-reset-logs.index', ['userId' => auth()->id()]) }}">
            <i class="ri-key-2-line"></i>
            <span>Password Reset Logs</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.tokens.*') ? ' active' : '' }}"
           href="{{ route('user.sa.tokens.index', ['userId' => auth()->id()]) }}">
            <i class="ri-key-2-line"></i>
            <span>Token & Sesi</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.failed-jobs.*') ? ' active' : '' }}"
           href="{{ route('user.sa.failed-jobs.index', ['userId' => auth()->id()]) }}">
            <i class="ri-error-warning-line"></i>
            <span>Failed Jobs</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link menu-link{{ request()->routeIs('user.sa.notifications.*') ? ' active' : '' }}"
           href="{{ route('user.sa.notifications.index', ['userId' => auth()->id()]) }}">
            <i class="ri-notification-3-line"></i>
            <span>Notifikasi Sistem</span>
        </a>
    </li>
</ul>