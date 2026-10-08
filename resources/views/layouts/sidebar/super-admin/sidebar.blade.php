{{-- ═══════════════════════════════════════════════════════════════════════════
     SIDEBAR: SUPER ADMIN
     ───────────────────────────────────────────────────────────────────────────
     Peran   : Data Bootstrapper + System Administrator
     Akses   : Unrestricted (bypass semua middleware role/jabatan)
     Urutan  : Mengikuti alur bootstrap sistem (fresh install)
     File    : resources/views/layouts/sidebar/super-admin/sidebar.blade.php
     ───────────────────────────────────────────────────────────────────────────
     Prasyarat (dari dispatcher):
       • $userId       — ID user yang login
       • $currentRoute — nama route yang sedang aktif
       • isActiveAny($route, [patterns]) — helper deteksi menu aktif
     ═══════════════════════════════════════════════════════════════════════════ --}}

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 1 — UMUM
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Umum</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.dashboard']) ? ' active' : '' }}"
       href="{{ route('system.dashboard') }}">
        <i class="ri-dashboard-3-line"></i><span>Dashboard Sistem</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.profile.']) ? ' active' : '' }}"
       href="{{ route('user.profile.my', ['userId' => $userId]) }}">
        <i class="ri-user-line"></i><span>Profil Saya</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.notifications.']) ? ' active' : '' }}"
       href="{{ route('user.notifications.index', ['userId' => $userId]) }}">
        <i class="ri-notification-3-line"></i><span>Notifikasi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.todos.']) ? ' active' : '' }}"
       href="{{ route('user.todos.index', ['userId' => $userId]) }}">
        <i class="ri-checkbox-multiple-line"></i><span>To-Do List</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 2 — INISIALISASI SISTEM (BOOTSTRAP)
     ───────────────────────────────────────────────────────────────────────────
     Alur wajib saat fresh install / awal tahun ajaran:
       1. Master Lembaga        → Sekolah & Asrama didaftarkan
       2. Struktur Organisasi   → Jabatan, Divisi, Satuan Kerja
       3. Data GTK Awal         → Guru & Tendik diinput
       4. Infrastruktur         → Gedung, Ruang, Aset
       5. Konfigurasi Akademik  → Tahun Ajaran, Mapel, Kaldik
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Inisialisasi Sistem</span></li>

{{-- ─── Step 1: Master Lembaga ─────────────────────────────────────────── --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.schools.', 'user.schools-global.', 'user.schools.', 'user.sa.dormitories.', 'user.dormitory-master.']) ? ' active' : '' }}"
       href="#boot_lembaga" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.sa.schools.', 'user.schools-global.', 'user.schools.', 'user.sa.dormitories.', 'user.dormitory-master.']) ? 'true' : 'false' }}"
       aria-controls="boot_lembaga">
        <i class="ri-government-line"></i><span>1. Master Lembaga</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.sa.schools.', 'user.schools-global.', 'user.schools.', 'user.sa.dormitories.', 'user.dormitory-master.']) ? ' show' : '' }}"
         id="boot_lembaga">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.sa.schools.']) ? ' active' : '' }}"
                   href="{{ route('user.sa.schools.index', ['userId' => $userId]) }}">
                    <i class="ri-building-2-line me-1"></i> Kelola Sekolah
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.schools-global.']) ? ' active' : '' }}"
                   href="{{ route('user.schools-global.index', ['userId' => $userId]) }}">
                    <i class="ri-list-check me-1"></i> Daftar Sekolah
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.sa.dormitories.']) ? ' active' : '' }}"
                   href="{{ route('user.sa.dormitories.index', ['userId' => $userId]) }}">
                    <i class="ri-hotel-line me-1"></i> Kelola Asrama Global
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.dormitory-master.']) ? ' active' : '' }}"
                   href="{{ route('user.dormitory-master.index', ['userId' => $userId]) }}">
                    <i class="ri-hotel-fill me-1"></i> Master Asrama
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ─── Step 2: Struktur Organisasi ────────────────────────────────────── --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.master-data.jenis-gtk', 'user.master-data.jabatan', 'user.master-data.satuan-kerja', 'user.work-units.', 'user.sa.divisi.', 'user.divisi.']) ? ' active' : '' }}"
       href="#boot_struktur" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.master-data.jenis-gtk', 'user.master-data.jabatan', 'user.master-data.satuan-kerja', 'user.work-units.', 'user.sa.divisi.', 'user.divisi.']) ? 'true' : 'false' }}"
       aria-controls="boot_struktur">
        <i class="ri-node-tree"></i><span>2. Struktur Organisasi</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.master-data.jenis-gtk', 'user.master-data.jabatan', 'user.master-data.satuan-kerja', 'user.work-units.', 'user.sa.divisi.', 'user.divisi.']) ? ' show' : '' }}"
         id="boot_struktur">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.master-data.jenis-gtk']) ? ' active' : '' }}"
                   href="{{ route('user.master-data.jenis-gtk.index', ['userId' => $userId]) }}">
                    <i class="ri-price-tag-3-line me-1"></i> Jenis GTK
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.master-data.jabatan']) ? ' active' : '' }}"
                   href="{{ route('user.master-data.jabatan.index', ['userId' => $userId]) }}">
                    <i class="ri-briefcase-line me-1"></i> Jabatan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.master-data.satuan-kerja.', 'user.work-units.']) ? ' active' : '' }}"
                   href="{{ route('user.master-data.satuan-kerja.index', ['userId' => $userId]) }}">
                    <i class="ri-community-line me-1"></i> Satuan Kerja
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.sa.divisi.', 'user.divisi.']) ? ' active' : '' }}"
                   href="{{ route('user.sa.divisi.index', ['userId' => $userId]) }}">
                    <i class="ri-folder-2-line me-1"></i> Divisi
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ─── Step 3: Data GTK Awal ──────────────────────────────────────────── --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.gtk.', 'user.gtk-requests.', 'user.gtk-positions.', 'user.gtk-position-proposals.', 'user.pension.']) ? ' active' : '' }}"
       href="#boot_gtk" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.gtk.', 'user.gtk-requests.', 'user.gtk-positions.', 'user.gtk-position-proposals.', 'user.pension.']) ? 'true' : 'false' }}"
       aria-controls="boot_gtk">
        <i class="ri-team-line"></i><span>3. Data GTK Awal</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.gtk.', 'user.gtk-requests.', 'user.gtk-positions.', 'user.gtk-position-proposals.', 'user.pension.']) ? ' show' : '' }}"
         id="boot_gtk">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.index', 'user.gtk.indexguru', 'user.gtk.indextendik']) ? ' active' : '' }}"
                   href="{{ route('user.gtk.index', ['userId' => $userId]) }}">
                    <i class="ri-list-check-2 me-1"></i> Semua GTK
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.create']) ? ' active' : '' }}"
                   href="{{ route('user.gtk.create', ['userId' => $userId]) }}">
                    <i class="ri-user-add-line me-1"></i> Tambah GTK
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.import']) ? ' active' : '' }}"
                   href="{{ route('user.gtk.import', ['userId' => $userId]) }}">
                    <i class="ri-upload-cloud-line me-1"></i> Import GTK
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk.massal']) ? ' active' : '' }}"
                   href="{{ route('user.gtk.massal', ['userId' => $userId]) }}">
                    <i class="ri-edit-box-line me-1"></i> Manajemen Massal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk-positions.', 'user.gtk-position-proposals.']) ? ' active' : '' }}"
                   href="{{ route('user.gtk-positions.index', ['userId' => $userId]) }}">
                    <i class="ri-award-line me-1"></i> Jabatan GTK
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.gtk-requests.']) ? ' active' : '' }}"
                   href="{{ route('user.gtk-requests.index', ['userId' => $userId]) }}">
                    <i class="ri-git-pull-request-line me-1"></i> GTK Requests
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.pension.']) ? ' active' : '' }}"
                   href="{{ route('user.pension.index', ['userId' => $userId]) }}">
                    <i class="ri-umbrella-line me-1"></i> Pensiun
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ─── Step 4: Infrastruktur ──────────────────────────────────────────── --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.gedung.', 'sarpras.ruang.', 'sarpras.aset.']) ? ' active' : '' }}"
       href="#boot_infra" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['sarpras.gedung.', 'sarpras.ruang.', 'sarpras.aset.']) ? 'true' : 'false' }}"
       aria-controls="boot_infra">
        <i class="ri-building-3-line"></i><span>4. Infrastruktur</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['sarpras.gedung.', 'sarpras.ruang.', 'sarpras.aset.']) ? ' show' : '' }}"
         id="boot_infra">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['sarpras.gedung.']) ? ' active' : '' }}"
                   href="{{ route('sarpras.gedung.index') }}">
                    <i class="ri-building-line me-1"></i> Gedung
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['sarpras.ruang.']) ? ' active' : '' }}"
                   href="{{ route('sarpras.ruang.index') }}">
                    <i class="ri-door-open-line me-1"></i> Ruang
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['sarpras.aset.']) ? ' active' : '' }}"
                   href="{{ route('sarpras.aset.index') }}">
                    <i class="ri-archive-line me-1"></i> Aset
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ─── Step 5: Konfigurasi Akademik ───────────────────────────────────── --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.academic-years.', 'user.grade-levels.', 'user.subjects.', 'user.kaldik.', 'user.pekan-efektif.', 'user.dokumen-iso.']) ? ' active' : '' }}"
       href="#boot_akademik" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.academic-years.', 'user.grade-levels.', 'user.subjects.', 'user.kaldik.', 'user.pekan-efektif.', 'user.dokumen-iso.']) ? 'true' : 'false' }}"
       aria-controls="boot_akademik">
        <i class="ri-book-2-line"></i><span>5. Konfigurasi Akademik</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.academic-years.', 'user.grade-levels.', 'user.subjects.', 'user.kaldik.', 'user.pekan-efektif.', 'user.dokumen-iso.']) ? ' show' : '' }}"
         id="boot_akademik">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.academic-years.']) ? ' active' : '' }}"
                   href="{{ route('user.academic-years.index', ['userId' => $userId]) }}">
                    <i class="ri-calendar-event-line me-1"></i> Tahun Ajaran
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.grade-levels.']) ? ' active' : '' }}"
                   href="{{ route('user.grade-levels.index', ['userId' => $userId]) }}">
                    <i class="ri-stack-line me-1"></i> Tingkat
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.subjects.']) ? ' active' : '' }}"
                   href="{{ route('user.subjects.index', ['userId' => $userId]) }}">
                    <i class="ri-book-open-line me-1"></i> Mata Pelajaran
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.kaldik.']) ? ' active' : '' }}"
                   href="{{ route('user.kaldik.index', ['userId' => $userId]) }}">
                    <i class="ri-task-line me-1"></i> Kalender Pendidikan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.pekan-efektif.']) ? ' active' : '' }}"
                   href="{{ route('user.pekan-efektif.index', ['userId' => $userId]) }}">
                    <i class="ri-calendar-todo-line me-1"></i> Pekan Efektif
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.dokumen-iso.']) ? ' active' : '' }}"
                   href="{{ route('user.dokumen-iso.index', ['userId' => $userId]) }}">
                    <i class="ri-folder-shield-2-line me-1"></i> Dokumen ISO
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 3 — DATA OPERASIONAL (Super Admin: full access)
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Data Operasional</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.students.', 'user.mutations-', 'user.student-move.', 'user.alumni.']) ? ' active' : '' }}"
       href="#op_santri" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.students.', 'user.mutations-', 'user.student-move.', 'user.alumni.']) ? 'true' : 'false' }}"
       aria-controls="op_santri">
        <i class="ri-user-heart-line"></i><span>Santri</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.students.', 'user.mutations-', 'user.student-move.', 'user.alumni.']) ? ' show' : '' }}"
         id="op_santri">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.students.index']) ? ' active' : '' }}"
                   href="{{ route('user.students.index', ['userId' => $userId]) }}">
                    <i class="ri-list-check me-1"></i> Data Santri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.students.mahroms.']) ? ' active' : '' }}"
                   href="{{ route('user.students.mahroms.global', ['userId' => $userId]) }}">
                    <i class="ri-parent-line me-1"></i> Data Mahrom
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-in.']) ? ' active' : '' }}"
                   href="{{ route('user.mutations-in.index', ['userId' => $userId]) }}">
                    <i class="ri-login-box-line me-1"></i> Mutasi Masuk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-out.']) ? ' active' : '' }}"
                   href="{{ route('user.mutations-out.index', ['userId' => $userId]) }}">
                    <i class="ri-logout-box-line me-1"></i> Mutasi Keluar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-lulus.']) ? ' active' : '' }}"
                   href="{{ route('user.mutations-lulus.index', ['userId' => $userId]) }}">
                    <i class="ri-graduation-cap-line me-1"></i> Mutasi Lulus
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.mutations-do.']) ? ' active' : '' }}"
                   href="{{ route('user.mutations-do.index', ['userId' => $userId]) }}">
                    <i class="ri-user-unfollow-line me-1"></i> Mutasi DO
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.student-move.']) ? ' active' : '' }}"
                   href="{{ route('user.student-move.index', ['userId' => $userId]) }}">
                    <i class="ri-arrow-left-right-line me-1"></i> Pindahkan Santri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.alumni.']) ? ' active' : '' }}"
                   href="{{ route('user.alumni.index', ['userId' => $userId]) }}">
                    <i class="ri-graduation-cap-2-line me-1"></i> Alumni
                </a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.study-groups.', 'user.teaching-assignments.', 'user.other-teacher-tasks.', 'user.institution-decrees.']) ? ' active' : '' }}"
       href="#op_akademik" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.study-groups.', 'user.teaching-assignments.', 'user.other-teacher-tasks.', 'user.institution-decrees.']) ? 'true' : 'false' }}"
       aria-controls="op_akademik">
        <i class="ri-school-line"></i><span>Akademik Lanjutan</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.study-groups.', 'user.teaching-assignments.', 'user.other-teacher-tasks.', 'user.institution-decrees.']) ? ' show' : '' }}"
         id="op_akademik">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.study-groups.']) ? ' active' : '' }}"
                   href="{{ route('user.study-groups.index', ['userId' => $userId]) }}">
                    <i class="ri-group-line me-1"></i> Rombel
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.teaching-assignments.']) ? ' active' : '' }}"
                   href="{{ route('user.teaching-assignments.index', ['userId' => $userId]) }}">
                    <i class="ri-user-star-line me-1"></i> Penugasan Mengajar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.other-teacher-tasks.']) ? ' active' : '' }}"
                   href="{{ route('user.other-teacher-tasks.index', ['userId' => $userId]) }}">
                    <i class="ri-user-settings-line me-1"></i> Tugas Tambahan Guru
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.institution-decrees.']) ? ' active' : '' }}"
                   href="{{ route('user.institution-decrees.index', ['userId' => $userId]) }}">
                    <i class="ri-file-paper-line me-1"></i> SK Lembaga
                </a>
            </li>
        </ul>
    </div>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.', 'user.absensi.', 'user.violation-points.', 'user.student-achievements.']) ? ' active' : '' }}"
       href="#op_absensi" data-bs-toggle="collapse" role="button"
       aria-expanded="{{ isActiveAny($currentRoute, ['user.absensi-gtk.', 'user.absensi.', 'user.violation-points.', 'user.student-achievements.']) ? 'true' : 'false' }}"
       aria-controls="op_absensi">
        <i class="ri-calendar-check-line"></i><span>Absensi & Kedisiplinan</span><span class="menu-arrow"></span>
    </a>
    <div class="collapse menu-dropdown{{ isActiveAny($currentRoute, ['user.absensi-gtk.', 'user.absensi.', 'user.violation-points.', 'user.student-achievements.']) ? ' show' : '' }}"
         id="op_absensi">
        <ul class="nav nav-sm flex-column">
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.absensi-gtk.']) ? ' active' : '' }}"
                   href="{{ route('user.absensi-gtk.index', ['userId' => $userId]) }}">
                    <i class="ri-contacts-book-line me-1"></i> Absensi GTK
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.absensi.harian.']) ? ' active' : '' }}"
                   href="{{ route('user.absensi.harian.index', ['userId' => $userId]) }}">
                    <i class="ri-calendar-line me-1"></i> Absensi Santri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.violation-points.']) ? ' active' : '' }}"
                   href="{{ route('user.violation-points.index', ['userId' => $userId]) }}">
                    <i class="ri-error-warning-line me-1"></i> Poin Pelanggaran
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link{{ isActiveAny($currentRoute, ['user.student-achievements.']) ? ' active' : '' }}"
                   href="{{ route('user.student-achievement.index', ['userId' => $userId]) }}">
                    <i class="ri-trophy-line me-1"></i> Prestasi Santri
                </a>
            </li>
        </ul>
    </div>
</li>

{{-- ─── Modul dengan link langsung (preview singkat) ───────────────────── --}}
<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.asrama.']) ? ' active' : '' }}"
       href="{{ route('user.asrama.index', ['userId' => $userId]) }}">
        <i class="ri-hotel-fill"></i><span>Modul Asrama</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.uks.']) ? ' active' : '' }}"
       href="{{ route('user.uks.dashboard', ['userId' => $userId]) }}">
        <i class="ri-heart-pulse-line"></i><span>Modul UKS</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['sarpras.']) ? ' active' : '' }}"
       href="{{ route('sarpras.dashboard') }}">
        <i class="ri-tools-line"></i><span>Modul Sarpras</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.boarding-policies.']) ? ' active' : '' }}"
       href="{{ route('user.boarding-policies.index', ['userId' => $userId]) }}">
        <i class="ri-file-shield-2-line"></i><span>Kebijakan Asrama</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 4 — ADMINISTRASI SISTEM
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Administrasi Sistem</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.users.']) ? ' active' : '' }}"
       href="{{ route('user.sa.users.index', ['userId' => $userId]) }}">
        <i class="ri-user-settings-line"></i><span>Manajemen User</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.roles.']) ? ' active' : '' }}"
       href="{{ route('user.sa.roles.index', ['userId' => $userId]) }}">
        <i class="ri-admin-line"></i><span>Roles</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.permissions.']) ? ' active' : '' }}"
       href="{{ route('user.sa.permissions.index', ['userId' => $userId]) }}">
        <i class="ri-key-2-line"></i><span>Permissions</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.sidebar-access.']) ? ' active' : '' }}"
       href="{{ route('user.sa.sidebar-access.index', ['userId' => $userId]) }}">
        <i class="ri-layout-left-line"></i><span>Sidebar Access</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.features']) ? ' active' : '' }}"
       href="{{ route('system.features') }}">
        <i class="ri-toggle-line"></i><span>Feature Activation</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.system-settings.']) ? ' active' : '' }}"
       href="{{ route('user.sa.system-settings.index', ['userId' => $userId]) }}">
        <i class="ri-settings-4-line"></i><span>Pengaturan Sistem</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 5 — LOG & MONITORING
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Log & Monitoring</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.audit-logs.']) ? ' active' : '' }}"
       href="{{ route('user.sa.audit-logs.index', ['userId' => $userId]) }}">
        <i class="ri-file-history-line"></i><span>Audit Log</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.password-reset-logs.']) ? ' active' : '' }}"
       href="{{ route('user.sa.password-reset-logs.index', ['userId' => $userId]) }}">
        <i class="ri-lock-password-line"></i><span>Password Reset Log</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.tokens.']) ? ' active' : '' }}"
       href="{{ route('user.sa.tokens.index', ['userId' => $userId]) }}">
        <i class="ri-key-line"></i><span>Token & Sesi</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.failed-jobs.']) ? ' active' : '' }}"
       href="{{ route('user.sa.failed-jobs.index', ['userId' => $userId]) }}">
        <i class="ri-error-warning-line"></i><span>Failed Jobs</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.sa.notifications.']) ? ' active' : '' }}"
       href="{{ route('user.sa.notifications.index', ['userId' => $userId]) }}">
        <i class="ri-notification-badge-line"></i><span>Notifikasi Sistem</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 6 — SYSTEM TOOLS
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>System Tools</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.config']) ? ' active' : '' }}"
       href="{{ route('system.config') }}">
        <i class="ri-equalizer-line"></i><span>Config</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.monitoring']) ? ' active' : '' }}"
       href="{{ route('system.monitoring') }}">
        <i class="ri-pulse-line"></i><span>Monitoring</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.maintenance']) ? ' active' : '' }}"
       href="{{ route('system.maintenance') }}">
        <i class="ri-tools-line"></i><span>Maintenance</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.devtools']) ? ' active' : '' }}"
       href="{{ route('system.devtools') }}">
        <i class="ri-code-s-slash-line"></i><span>Dev Tools</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.view-as.']) ? ' active' : '' }}"
       href="{{ route('system.view-as.users') }}">
        <i class="ri-eye-line"></i><span>View As User</span>
    </a>
</li>

{{-- ═══════════════════════════════════════════════════════════════════════════
     SECTION 7 — DATA GLOBAL (monitoring lintas modul)
     ═══════════════════════════════════════════════════════════════════════════ --}}
<li class="menu-title"><span>Data Global</span></li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['user.approvals.']) ? ' active' : '' }}"
       href="{{ route('user.approvals.index', ['userId' => $userId]) }}">
        <i class="ri-check-double-line"></i><span>Approval Center</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.violations.']) ? ' active' : '' }}"
       href="{{ route('system.violations.index') }}">
        <i class="ri-alert-line"></i><span>Semua Pelanggaran</span>
    </a>
</li>

<li class="nav-item">
    <a class="nav-link menu-link{{ isActiveAny($currentRoute, ['system.permits.']) ? ' active' : '' }}"
       href="{{ route('system.permits.index') }}">
        <i class="ri-pass-valid-line"></i><span>Semua Perizinan</span>
    </a>
</li>