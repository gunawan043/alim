<?php

namespace Database\Seeders;

use App\Models\SidebarMenu;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SidebarMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        SidebarMenu::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Role IDs ─────────────────────────────────────────────────
        $roleSuperAdmin  = Role::where('name', 'Super Admin')->first();
        $roleMudir       = Role::where('name', 'Mudir')->first();
        $roleAdminTU     = Role::where('name', 'Admin Tata Usaha')->first();
        $roleTU          = Role::where('name', 'Tata Usaha')->first();
        $roleKepsek      = Role::where('name', 'Kepala Sekolah')->first();
        $roleWadir1      = Role::where('name', 'Wadir 1')->first();
        $roleWadir2      = Role::where('name', 'Wadir 2')->first();
        $rolePersonalia  = Role::where('name', 'Personalia')->first();
        $roleGTK         = Role::where('name', 'GTK')->first();

        $superAdminOnly = array_filter([$roleSuperAdmin?->id]);
        $allRoleIds     = Role::pluck('id')->toArray();

        // Academic roles (global + scoped)
        $academicRoles = array_filter([
            $roleSuperAdmin?->id,
            $roleMudir?->id,
            $roleAdminTU?->id,
            $roleTU?->id,
            $roleKepsek?->id,
            $roleWadir1?->id,
            $roleWadir2?->id,
        ]);

        // GTK edit roles (Personalia + AdminTU can create/edit GTK)
        $gtkEditRoles = array_filter([
            $roleSuperAdmin?->id,
            $roleMudir?->id,
            $roleAdminTU?->id,
            $rolePersonalia?->id,
        ]);

        // Helper: create + assign roles
        $make = function (array $data) use (&$allMenus) {
            $roleIds = $data['_roles'] ?? null;
            unset($data['_roles']);
            $data['id'] = $data['id'] ?? (string) Str::uuid();
            $menu = SidebarMenu::create($data);
            if ($roleIds !== null) {
                if ($roleIds === []) {
                    // no roles — skip sync
                } elseif (empty($roleIds)) {
                    // none assigned
                } else {
                    $menu->roles()->sync($roleIds);
                }
            }
            $allMenus[$data['name']] = $menu;
            return $menu;
        };

        $allMenus = [];

        // ══════════════════════════════════════════════════════════════
        // SUPER ADMIN SECTION
        // ══════════════════════════════════════════════════════════════
        $saSection = $make([
            'name' => 'Super Admin', 'is_group_header' => true,
            'icon' => 'ri-shield-star-line', 'order' => 1,
            '_roles' => $superAdminOnly,
        ]);
        $make(['name' => 'Manajemen User',        'route' => 'user.sa.users.index',                'icon' => 'ri-user-settings-line', 'parent_id' => $saSection->id, 'order' => 10, '_roles' => $superAdminOnly]);
        $make(['name' => 'Roles & Permissions',    'route' => 'user.sa.roles.index',                'icon' => 'ri-shield-check-line',  'parent_id' => $saSection->id, 'order' => 11, '_roles' => $superAdminOnly]);
        $make(['name' => 'Permissions',            'route' => 'user.sa.permissions.index',          'icon' => 'ri-key-line',           'parent_id' => $saSection->id, 'order' => 12, '_roles' => $superAdminOnly]);
        $make(['name' => 'Audit Log',             'route' => 'user.sa.audit-logs.index',            'icon' => 'ri-file-history-line', 'parent_id' => $saSection->id, 'order' => 13, '_roles' => $superAdminOnly]);
        $make(['name' => 'Token & Sesi',          'route' => 'user.sa.tokens.index',               'icon' => 'ri-key-2-line',        'parent_id' => $saSection->id, 'order' => 14, '_roles' => $superAdminOnly]);
        $make(['name' => 'Failed Jobs',           'route' => 'user.sa.failed-jobs.index',          'icon' => 'ri-error-warning-line','parent_id' => $saSection->id, 'order' => 15, '_roles' => $superAdminOnly]);
        $make(['name' => 'Notifikasi Universal', 'route' => 'user.sa.notifications.index',        'icon' => 'ri-notification-3-line','parent_id' => $saSection->id, 'order' => 16, '_roles' => $superAdminOnly]);
        $make(['name' => 'Kelola Menu Sidebar',  'route' => 'user.sa.sidebar-menus.index',         'icon' => 'ri-menu-add-line',     'parent_id' => $saSection->id, 'order' => 17, '_roles' => $superAdminOnly]);
        $make(['name' => 'Password Reset Logs',  'route' => 'user.sa.password-reset-logs.index',   'icon' => 'ri-lock-password-line', 'parent_id' => $saSection->id, 'order' => 18, '_roles' => $superAdminOnly]);
        $make(['name' => 'Pengaturan Sistem',    'route' => 'user.sa.system-settings.index',      'icon' => 'ri-settings-3-line',    'parent_id' => $saSection->id, 'order' => 19, '_roles' => $superAdminOnly]);

        // ══════════════════════════════════════════════════════════════
        // MENU UTAMA
        // ══════════════════════════════════════════════════════════════

        // ── Dashboard ─────────────────────────────────────────────
        $make([
            'name' => 'Dashboard', 'icon' => 'ri-home-6-line', 'order' => 5,
            'route' => 'dashboard',
            '_roles' => $allRoleIds,
        ]);

        // ── Satuan Pendidikan ───────────────────────────────────────
        $make([
            'name' => 'Satuan Pendidikan', 'icon' => 'ri-government-line', 'order' => 6,
            'route' => 'schools-global.index',
            '_roles' => $academicRoles,
        ]);

        // ══════════════════════════════════════════════════════════════
        // GTK & PESERTA DIDIK
        // ══════════════════════════════════════════════════════════════
        $gtkPdSection = $make([
            'name' => 'GTK & Peserta Didik', 'icon' => 'ri-team-line', 'order' => 10,
            'is_group_header' => true,
            '_roles' => $academicRoles,
        ]);

            // ── Data GTK ──────────────────────────────────────────────
            $dataGtkSection = $make([
                'name' => 'Data GTK', 'icon' => 'ri-contacts-book-2-line', 'order' => 1,
                'parent_id' => $gtkPdSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => 'Guru',     'route' => 'user.gtk.index', 'icon' => 'ri-user-settings-line', 'parent_id' => $dataGtkSection->id, 'order' => 1, '_roles' => $academicRoles]);
            $make(['name' => 'Tendik',   'route' => 'user.gtk.index', 'icon' => 'ri-settings-3-line',  'parent_id' => $dataGtkSection->id, 'order' => 2, '_roles' => $academicRoles]);

            // ── Peserta Didik ─────────────────────────────────────────
            $pdSection = $make([
                'name' => 'Peserta Didik', 'icon' => 'ri-team-line', 'order' => 2,
                'parent_id' => $gtkPdSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => 'Data Kelas', 'route' => 'user.students.index',       'icon' => 'ri-group-line',        'parent_id' => $pdSection->id, 'order' => 1, '_roles' => $academicRoles]);
            $make(['name' => 'Rombel',    'route' => 'user.study-groups.index',    'icon' => 'ri-stack-line',         'parent_id' => $pdSection->id, 'order' => 2, '_roles' => $academicRoles]);
            $make(['name' => 'Mutasi PD', 'route' => 'user.mutations-in.index',      'icon' => 'ri-login-box-line',     'parent_id' => $pdSection->id, 'order' => 3, '_roles' => $academicRoles]);
            $make(['name' => 'PD Keluar', 'route' => 'user.mutations-out.index',    'icon' => 'ri-logout-box-line',    'parent_id' => $pdSection->id, 'order' => 4, '_roles' => $academicRoles]);
            $make(['name' => 'Promosi Santri', 'route' => 'user.student-promotions.index', 'icon' => 'ri-arrow-up-line', 'parent_id' => $pdSection->id, 'order' => 5, '_roles' => $academicRoles]);

        // ── Poin Pelanggaran ────────────────────────────────────────
        $make([
            'name' => 'Poin Pelanggaran', 'icon' => 'ri-spam-line', 'order' => 11,
            'route' => '#',
            '_roles' => $academicRoles,
        ]);

        // ── Data Alumni ────────────────────────────────────────────
        $make([
            'name' => 'Data Alumni', 'icon' => 'ri-group-2-line', 'order' => 12,
            'route' => '#',
            '_roles' => $academicRoles,
        ]);

        // ══════════════════════════════════════════════════════════════
        // AKADEMIK
        // ══════════════════════════════════════════════════════════════
        $akademikSection = $make([
            'name' => 'Akademik', 'icon' => 'ri-book-open-line', 'order' => 20,
            'is_group_header' => true,
            '_roles' => $academicRoles,
        ]);

            // ── Pelaksanaan Sumatif ───────────────────────────────────
            $sumatifSection = $make([
                'name' => 'Pelaksanaan Sumatif', 'icon' => 'ri-file-edit-line', 'order' => 1,
                'parent_id' => $akademikSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => 'Kisi-Kisi Soal', 'route' => '#', 'icon' => 'ri-file-text-line', 'parent_id' => $sumatifSection->id, 'order' => 1, '_roles' => $academicRoles]);
            $make(['name' => 'Soal Sumatif',    'route' => '#', 'icon' => 'ri-file-paper-2-line', 'parent_id' => $sumatifSection->id, 'order' => 2, '_roles' => $academicRoles]);

            // ── Data Nilai ────────────────────────────────────────────
            $nilaiSection = $make([
                'name' => 'Data Nilai', 'icon' => 'ri-survey-line', 'order' => 2,
                'parent_id' => $akademikSection->id,
                '_roles' => $academicRoles,
            ]);

            $make([
                'name' => 'Nilai STS', 'route' => 'user.schools.nilai.index', 'icon' => 'ri-file-chart-line', 'order' => 1,
                'parent_id' => $nilaiSection->id,
                '_roles' => $academicRoles,
            ]);
            $make([
                'name' => 'Nilai SAS', 'route' => 'user.schools.nilai.index', 'icon' => 'ri-file-chart-2-line', 'order' => 2,
                'parent_id' => $nilaiSection->id,
                '_roles' => $academicRoles,
            ]);
            $make([
                'name' => 'Buku Admin Guru', 'route' => 'user.schools.guru-mapel.index', 'icon' => 'ri-book-2-line', 'order' => 3,
                'parent_id' => $nilaiSection->id,
                '_roles' => array_filter([$roleGTK?->id]),
            ]);

            // ── Absensi ──────────────────────────────────────────────
            $absenSection = $make([
                'name' => 'Absensi', 'icon' => 'ri-contacts-book-line', 'order' => 3,
                'parent_id' => $akademikSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => 'Absensi GTK',           'route' => '#', 'icon' => 'ri-user-settings-line', 'parent_id' => $absenSection->id, 'order' => 1, '_roles' => $academicRoles]);

            $absenPdParent = $make([
                'name' => 'Absensi Peserta Didik', 'icon' => 'ri-user-heart-line', 'order' => 2,
                'parent_id' => $absenSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => '7A', 'route' => '#', 'icon' => 'ri-file-list-3-line', 'parent_id' => $absenPdParent->id, 'order' => 1, '_roles' => $academicRoles]);
            $make(['name' => '7B', 'route' => '#', 'icon' => 'ri-file-list-3-line', 'parent_id' => $absenPdParent->id, 'order' => 2, '_roles' => $academicRoles]);
            $make(['name' => '7C', 'route' => '#', 'icon' => 'ri-file-list-3-line', 'parent_id' => $absenPdParent->id, 'order' => 3, '_roles' => $academicRoles]);

            // ── Data Prestasi ────────────────────────────────────────
            $prestasiSection = $make([
                'name' => 'Data Prestasi', 'icon' => 'ri-trophy-line', 'order' => 4,
                'parent_id' => $akademikSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => 'Prestasi Akademik', 'route' => '#', 'icon' => 'ri-trophy-line',       'parent_id' => $prestasiSection->id, 'order' => 1, '_roles' => $academicRoles]);
            $make(['name' => 'Hafalan Qur\'an',   'route' => '#', 'icon' => 'ri-book-line',         'parent_id' => $prestasiSection->id, 'order' => 2, '_roles' => $academicRoles]);
            $make(['name' => 'Hafalan Hadits',    'route' => '#', 'icon' => 'ri-book-2-line',      'parent_id' => $prestasiSection->id, 'order' => 3, '_roles' => $academicRoles]);

            // ── Ekstrakurikuler ──────────────────────────────────────
            $make([
                'name' => 'Ekstrakurikuler', 'icon' => 'ri-basketball-line', 'order' => 5,
                'route' => '#',
                'parent_id' => $akademikSection->id,
                '_roles' => $academicRoles,
            ]);

            // ── Supervisi ────────────────────────────────────────────
            $make([
                'name' => 'Supervisi', 'icon' => 'ri-file-excel-2-line', 'order' => 6,
                'route' => '#',
                'parent_id' => $akademikSection->id,
                '_roles' => $academicRoles,
            ]);

        // ══════════════════════════════════════════════════════════════
        // UKS — USAHA KESEHATAN SEKOLAH
        // ══════════════════════════════════════════════════════════════
        $uksSection = $make([
            'name' => 'UKS', 'icon' => 'ri-heart-pulse-line', 'order' => 25,
            'is_group_header' => true,
            '_roles' => $academicRoles,
        ]);
            $make(['name' => 'Imunisasi',           'route' => 'user.uks.immunizations.index',           'icon' => 'ri-shot-line',          'parent_id' => $uksSection->id, 'order' => 1,  '_roles' => $academicRoles]);
            $make(['name' => 'Medical Check-up',    'route' => 'user.uks.health-checkups.index',        'icon' => 'ri-stethoscope-line',   'parent_id' => $uksSection->id, 'order' => 2,  '_roles' => $academicRoles]);
            $make(['name' => 'Izin Sakit',          'route' => 'user.uks.health-permits.index',         'icon' => 'ri-file-list-2-line',   'parent_id' => $uksSection->id, 'order' => 3,  '_roles' => $academicRoles]);
            $make(['name' => 'Inventori Obat',      'route' => 'user.uks.medicine-inventory.index',     'icon' => 'ri-flask-line',         'parent_id' => $uksSection->id, 'order' => 4,  '_roles' => $academicRoles]);
            $make(['name' => 'Pemberian Obat',      'route' => 'user.uks.medicine-logs.index',          'icon' => 'ri-bubble-chart-line',  'parent_id' => $uksSection->id, 'order' => 5,  '_roles' => $academicRoles]);
            $make(['name' => 'Antropometri',        'route' => 'user.uks.health-metrics.index',         'icon' => 'ri-body-scan-line',     'parent_id' => $uksSection->id, 'order' => 6,  '_roles' => $academicRoles]);
            $make(['name' => 'Konseling',           'route' => 'user.uks.counseling-records.index',     'icon' => 'ri-user-heart-line',    'parent_id' => $uksSection->id, 'order' => 7,  '_roles' => $academicRoles]);
            $make(['name' => 'Faskes Rujukan',      'route' => 'user.uks.facility-referrals.index',     'icon' => 'ri-hospital-line',      'parent_id' => $uksSection->id, 'order' => 8,  '_roles' => $academicRoles]);
            $make(['name' => 'Inspeksi Sanitasi',    'route' => 'user.uks.sanitation-inspections.index', 'icon' => 'ri-water-percent-line', 'parent_id' => $uksSection->id, 'order' => 9,  '_roles' => $academicRoles]);

        // ══════════════════════════════════════════════════════════════
        // ADMINISTRASI
        // ══════════════════════════════════════════════════════════════
        $adminSection = $make([
            'name' => 'Administrasi', 'icon' => 'ri-file-text-line', 'order' => 30,
            'is_group_header' => true,
            '_roles' => $academicRoles,
        ]);

            // ── Jadwal KBM ────────────────────────────────────────────
            $jadwalSection = $make([
                'name' => 'Jadwal KBM', 'icon' => 'ri-git-repository-line', 'order' => 1,
                'parent_id' => $adminSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => 'SK Guru',                  'route' => '#', 'icon' => 'ri-file-text-line',    'parent_id' => $jadwalSection->id, 'order' => 1, '_roles' => $academicRoles]);
            $make(['name' => 'Jadwal Pelajaran',         'route' => '#', 'icon' => 'ri-calendar-todo-line','parent_id' => $jadwalSection->id, 'order' => 2, '_roles' => $academicRoles]);
            $make(['name' => 'Jam Mengajar',              'route' => '#', 'icon' => 'ri-time-line',        'parent_id' => $jadwalSection->id, 'order' => 3, '_roles' => $academicRoles]);
            $make(['name' => 'Rekap Pergantian Jam',      'route' => '#', 'icon' => 'ri-arrow-left-right-line','parent_id' => $jadwalSection->id, 'order' => 4, '_roles' => $academicRoles]);

            // ── Surat Menyurat ────────────────────────────────────────
            $suratSection = $make([
                'name' => 'Surat Menyurat', 'icon' => 'ri-mail-send-line', 'order' => 2,
                'parent_id' => $adminSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => 'Surat Keluar', 'route' => '#', 'icon' => 'ri-send-plane-line', 'parent_id' => $suratSection->id, 'order' => 1, '_roles' => $academicRoles]);
            $make(['name' => 'Surat Masuk',  'route' => '#', 'icon' => 'ri-inbox-line',      'parent_id' => $suratSection->id, 'order' => 2, '_roles' => $academicRoles]);

            // ── Dokumen ISO ───────────────────────────────────────────
            $make([
                'name' => 'Dokumen ISO', 'icon' => 'ri-dashboard-2-line', 'order' => 3,
                'route' => '#',
                'parent_id' => $adminSection->id,
                '_roles' => $academicRoles,
            ]);

        // ══════════════════════════════════════════════════════════════
        // PENDUKUNG
        // ══════════════════════════════════════════════════════════════
        $dukungSection = $make([
            'name' => 'Pendukung', 'icon' => 'ri-tools-line', 'order' => 40,
            'is_group_header' => true,
            '_roles' => $academicRoles,
        ]);

            // ── Agenda Kegiatan ───────────────────────────────────────
            $agendaSection = $make([
                'name' => 'Agenda Kegiatan', 'icon' => 'ri-calendar-todo-line', 'order' => 1,
                'parent_id' => $dukungSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => 'Kaldik',          'route' => '#', 'icon' => 'ri-calendar-line',    'parent_id' => $agendaSection->id, 'order' => 1, '_roles' => $academicRoles]);
            $make(['name' => 'Pekan Efektif',   'route' => '#', 'icon' => 'ri-calendar-check-line','parent_id' => $agendaSection->id, 'order' => 2, '_roles' => $academicRoles]);

            // ── Sarana Prasarana ──────────────────────────────────────
            $sarprasSection = $make([
                'name' => 'Sarana Prasarana', 'icon' => 'ri-community-line', 'order' => 2,
                'parent_id' => $dukungSection->id,
                '_roles' => $academicRoles,
            ]);
            $make(['name' => 'Sarpras 7A', 'route' => '#', 'icon' => 'ri-building-line', 'parent_id' => $sarprasSection->id, 'order' => 1, '_roles' => $academicRoles]);
            $make(['name' => 'Sarpras 7B', 'route' => '#', 'icon' => 'ri-building-line', 'parent_id' => $sarprasSection->id, 'order' => 2, '_roles' => $academicRoles]);
            $make(['name' => 'Sarpras 7C', 'route' => '#', 'icon' => 'ri-building-line', 'parent_id' => $sarprasSection->id, 'order' => 3, '_roles' => $academicRoles]);

        // ══════════════════════════════════════════════════════════════
        // MASTER DATA
        // ══════════════════════════════════════════════════════════════
        $mdSection = $make([
            'name' => 'Master Data', 'icon' => 'bx bx-slider', 'order' => 50,
            '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $roleAdminTU?->id, $rolePersonalia?->id]),
        ]);
        $make(['name' => 'Jenis GTK',    'route' => 'user.master-data.jenis-gtk.index',     'parent_id' => $mdSection->id, 'order' => 1, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Jabatan',      'route' => 'user.master-data.jabatan.index',       'parent_id' => $mdSection->id, 'order' => 2, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Satuan Kerja', 'route' => 'user.master-data.satuan-kerja.index',  'parent_id' => $mdSection->id, 'order' => 3, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);

        // ══════════════════════════════════════════════════════════════
        // RECRUITMENT
        // ══════════════════════════════════════════════════════════════
        $atsSection = $make([
            'name' => 'Recruitment', 'icon' => 'ri-team-line', 'order' => 60,
            '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $rolePersonalia?->id]),
        ]);
        $make(['name' => 'Lowongan',          'route' => 'user.ats.jobs.index',        'parent_id' => $atsSection->id, 'order' => 1, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Kandidat',          'route' => 'user.ats.candidates.index',  'parent_id' => $atsSection->id, 'order' => 2, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Lamaran',           'route' => 'user.ats.applications.index','parent_id' => $atsSection->id, 'order' => 3, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Jadwal Interview',  'route' => 'user.ats.interviews.index',  'parent_id' => $atsSection->id, 'order' => 4, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Reports',           'route' => 'user.ats.reports.index',     'parent_id' => $atsSection->id, 'order' => 5, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Settings',          'route' => 'user.ats.settings.index',    'parent_id' => $atsSection->id, 'order' => 6, '_roles' => array_filter([$roleSuperAdmin?->id])]);

        // ══════════════════════════════════════════════════════════════
        // GTK REQUESTS & APPROVALS
        // ══════════════════════════════════════════════════════════════
        $reqSection = $make([
            'name' => 'GTK Requests & Approvals', 'icon' => 'ri-git-pull-request-line', 'order' => 70,
            '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $roleAdminTU?->id, $rolePersonalia?->id]),
        ]);
        $make(['name' => 'Daftar Request GTK',    'route' => 'user.gtk-requests.index',  'icon' => 'ri-list-ordered',                'parent_id' => $reqSection->id, 'order' => 1, '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $roleAdminTU?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Pengadaan GTK',         'route' => 'user.gtk-requests.create', 'icon' => 'ri-file-add-line',                'parent_id' => $reqSection->id, 'order' => 2, 'url' => '/gtk-requests/create?type=procurement', '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $roleAdminTU?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Pengangkatan Percobaan','route' => 'user.gtk-requests.create','icon' => 'ri-user-add-line',                'parent_id' => $reqSection->id, 'order' => 3, 'url' => '/gtk-requests/create?type=trial', '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $roleAdminTU?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Kenaikan Status GTK',  'route' => 'user.gtk-requests.create','icon' => 'ri-arrow-up-line',                'parent_id' => $reqSection->id, 'order' => 4, 'url' => '/gtk-requests/create?type=status_increase', '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $roleAdminTU?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Approval',             'route' => 'user.approvals.index',    'icon' => 'ri-checkbox-multiple-line',       'parent_id' => $reqSection->id, 'order' => 5, '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $roleAdminTU?->id, $rolePersonalia?->id])]);

        // ══════════════════════════════════════════════════════════════
        // JENJANG KARIR
        // ══════════════════════════════════════════════════════════════
        $jkSection = $make([
            'name' => 'Jenjang Karir', 'icon' => 'bx bx-rocket', 'order' => 80,
            '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $rolePersonalia?->id]),
        ]);
        $make(['name' => 'Career Path',     'route' => 'user.jenjang-karir.career-path.index', 'parent_id' => $jkSection->id, 'order' => 1, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Mutasi & Rotasi',  'route' => 'user.jenjang-karir.mutasi.index',      'parent_id' => $jkSection->id, 'order' => 2, '_roles' => array_filter([$roleSuperAdmin?->id, $roleMudir?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Promosi & Demosi', 'route' => 'user.jenjang-karir.promosi.index',    'parent_id' => $jkSection->id, 'order' => 3, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Talent Pool',      'route' => 'user.jenjang-karir.talent.index',      'parent_id' => $jkSection->id, 'order' => 4, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);
        $make(['name' => 'Succession Plan',  'route' => 'user.jenjang-karir.succession.index', 'parent_id' => $jkSection->id, 'order' => 5, '_roles' => array_filter([$roleSuperAdmin?->id, $rolePersonalia?->id])]);

        // ══════════════════════════════════════════════════════════════
        // TODO LIST
        // ══════════════════════════════════════════════════════════════
        $todoSection = $make([
            'name' => 'Todo List', 'icon' => 'ri-task-line', 'order' => 85,
            'route' => 'user.todos.index',
            '_roles' => array_filter([
                $roleSuperAdmin?->id,
                $roleMudir?->id,
                $roleAdminTU?->id,
                $roleTU?->id,
                $roleKepsek?->id,
                $roleWadir1?->id,
                $roleWadir2?->id,
                $rolePersonalia?->id,
                $roleGTK?->id,
            ]),
        ]);
        $make([
            'name' => 'Daftar Tugas', 'route' => 'user.todos.index',
            'parent_id' => $todoSection->id, 'order' => 1,
            '_roles' => array_filter([
                $roleSuperAdmin?->id,
                $roleMudir?->id,
                $roleAdminTU?->id,
                $roleTU?->id,
                $roleKepsek?->id,
                $roleWadir1?->id,
                $roleWadir2?->id,
                $rolePersonalia?->id,
                $roleGTK?->id,
            ]),
        ]);

        $this->command->info('SidebarMenuSeeder: ' . SidebarMenu::count() . ' menus created.');
    }
}
