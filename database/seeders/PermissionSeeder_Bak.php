<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Dashboard permissions
            'dashboard_view',
            'dashboard_analytics_view',
            'dashboard_statistics_view',

            // User Management
            'user_view',
            'user_create',
            'user_edit',
            'user_delete',
            'user_export',
            'user_import',

            // Role Management
            'role_view',
            'role_create',
            'role_edit',
            'role_delete',
            'role_assign',

            // Permission Management
            'permission_view',
            'permission_assign',

            // GTK Management (Personalia)
            'gtk_view',
            'gtk_create',
            'gtk_edit',
            'gtk_delete',
            'gtk_export',
            'gtk_import',
            'gtk_status_toggle',
            'gtk_detail_view',
            'gtk_family_view',
            'gtk_family_edit',
            'gtk_employment_view',
            'gtk_employment_edit',
            'gtk_contact_view',
            'gtk_contact_edit',
            'gtk_address_view',
            'gtk_address_edit',

            // Work Unit Management
            'work_unit_view',
            'work_unit_create',
            'work_unit_edit',
            'work_unit_delete',
            'work_unit_assign',

            // Satuan Pendidikan Management
            'satpen_view',
            'satpen_create',
            'satpen_edit',
            'satpen_delete',
            'satpen_activate',
            'satpen_deactivate',

            // Data Master
            'data_master_view',
            'data_master_edit',

            // Wilayah (Province, City, District, Village)
            'wilayah_view',
            'wilayah_edit',
            'wilayah_sync',

            // Kepegawaian
            'kepegawaian_view',
            'kepegawaian_edit',
            'kepegawaian_report',

            // Absensi
            'absensi_view',
            'absensi_edit',
            'absensi_report',
            'absensi_approve',

            // Penggajian
            'penggajian_view',
            'penggajian_edit',
            'penggajian_process',
            'penggajian_report',

            // Cuti
            'cuti_view',
            'cuti_create',
            'cuti_edit',
            'cuti_approve',
            'cuti_report',

            // Penilaian Kinerja
            'penilaian_view',
            'penilaian_create',
            'penilaian_edit',
            'penilaian_report',

            // Pelatihan & Pengembangan
            'pelatihan_view',
            'pelatihan_create',
            'pelatihan_edit',
            'pelatihan_report',

            // Asrama / Boarding — Modul Dormitory
            // Sidebar visibility untuk 7 role asrama
            'menu-asrama-sidebar',
            'menu-head-asrama-sidebar',
            'menu-admin-asrama-sidebar',
            'menu-pendidikan-asrama-sidebar',
            'menu-kesehatan-asrama-sidebar',
            'menu-wali-asrama-sidebar',

            // Master data dormitory
            'dormitory_view',
            'dormitory_create',
            'dormitory_edit',

            // Wing (Gedung)
            'wing_view',
            'wing_create',
            'wing_edit',
            'wing_delete',

            // Room (Kamar)
            'room_view',
            'room_create',
            'room_edit',
            'room_delete',

            // Resident (Penghuni)
            'resident_view',
            'resident_create',
            'resident_edit',
            'resident_delete',

            // Absensi Asrama
            'attendance_view',
            'attendance_create',
            'attendance_edit',
            'attendance_approve',

            // Perizinan
            'permit_view',
            'permit_create',
            'permit_approve',

            // Pelanggaran
            'violation_view',
            'violation_create',
            'violation_edit',
            'violation_approve',

            // Penghargaan
            'reward_view',
            'reward_create',

            // Kunjungan
            'visit_view',
            'visit_create',
            'visit_approve',

            // Mutasi Kamar
            'room_move_view',
            'room_move_create',
            'room_move_approve',

            // Inventaris Asrama (berbeda dari inventory umum)
            'dormitory_inventory_view',
            'dormitory_inventory_create',
            'dormitory_inventory_edit',
            'dormitory_inventory_delete',

            // Kegiatan Harian
            'activity_view',
            'activity_create',
            'activity_edit',

            // Template Kegiatan
            'template_view',
            'template_create',
            'template_edit',

            // Post Informasi & Broadcast
            'post_view',
            'post_create',
            'post_edit',
            'broadcast_view',
            'broadcast_create',
            'broadcast_send',

            // Laporan Asrama
            'report_view',
            'report_export',
            'report_generate',

            // Approval Center
            'approval_center_view',
            'approval_center_approve',

            // Kebijakan Asrama
            'boarding_policy_view',
            'boarding_policy_create',
            'boarding_policy_edit',

            // Kalender
            'calendar_return_view',
            'calendar_return_create',
            'calendar_visit_view',
            'calendar_visit_create',

            // Santri (terkait role asrama)
            'student_view',
            'student_export',
            'mahrom_view',

            // Dashboard Pengasuh
            'pengasuh_dashboard_view',

            // Export Asrama
            'asrama_export',

            // Inventory/Asset
            'inventory_view',
            'inventory_create',
            'inventory_edit',
            'inventory_delete',
            'inventory_report',
            // Sarana Prasarana
            'sarpras_view',
            'sarpras_create',
            'sarpras_edit',
            'sarpras_delete',
            'sarpras_all_access',
            'sarpras_gedung_view',
            'sarpras_gedung_create',
            'sarpras_gedung_edit',
            'sarpras_gedung_delete',
            'sarpras_ruang_view',
            'sarpras_ruang_create',
            'sarpras_ruang_edit',
            'sarpras_ruang_delete',
            'sarpras_aset_view',
            'sarpras_aset_create',
            'sarpras_aset_edit',
            'sarpras_aset_delete',
            'sarpras_aset_import',
            'sarpras_peminjaman_view',
            'sarpras_peminjaman_create',
            'sarpras_peminjaman_approve',
            'sarpras_peminjaman_delete',
            'sarpras_maintenance_view',
            'sarpras_maintenance_create',
            'sarpras_maintenance_edit',
            'sarpras_maintenance_delete',
            'sarpras_booking_view',
            'sarpras_booking_create',
            'sarpras_booking_approve',
            'sarpras_booking_delete',
            'sarpras_perpindahan_view',
            'sarpras_perpindahan_create',
            'sarpras_pengadaan_view',
            'sarpras_pengadaan_create',
            'sarpras_pengadaan_approve',
            'sarpras_pengadaan_delete',
            'sarpras_qr_view',
            'sarpras_qr_generate',
            'sarpras_qr_print',
            'sarpras_qr_audit',
            'sarpras_laporan_view',
            'sarpras_laporan_export',

            // Laporan
            'laporan_view',
            'laporan_generate',
            'laporan_export',

            // Settings
            'settings_view',
            'settings_general_edit',
            'settings_email_edit',
            'settings_system_edit',

            // Backup & Restore
            'backup_view',
            'backup_create',
            'backup_restore',

            // Logs
            'log_view',
            'log_activity_view',
            'log_error_view',

            // Notifications
            'notification_view',
            'notification_send',
            'notification_settings',

            // Profile
            'profile_view',
            'profile_edit',
            'password_change',

            // Akademik — Global View
            'view_global_school_data',
            // Akademik — Super Admin support / debugging
            'impersonate_role',

            // School Management
            'school_view',
            'school_edit',

            // Grade Level Management
            'grade_level_view',
            'grade_level_create',
            'grade_level_edit',
            'grade_level_delete',

            // Study Group (Rombongan Belajar) Management
            'study_group_view',
            'study_group_create',
            'study_group_edit',
            'study_group_delete',

            // Student Management
            'student_view',
            'student_create',
            'student_edit',
            'student_delete',
            'student_export',
            'student_import',

            // Student Mutation
            'student_mutation_view',
            'student_mutation_create',
            'student_mutation_edit',
            'student_mutation_approve',
            'student_mutation_export',
            'student_mutation_import',
        ];

        $now = now();

        foreach ($permissions as $permission) {
            $group = $this->getPermissionGroup($permission);
            $description = $this->getPermissionDescription($permission);

            // Gunakan DB query langsung untuk menghindari masalah Eloquent
            $exists = DB::table('permissions')
                ->where('name', $permission)
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                DB::table('permissions')->insert([
                    'id' => Str::uuid()->toString(),
                    'name' => $permission,
                    'guard_name' => 'web',
                    'description' => $description,
                    'group' => $group,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                // Update jika sudah ada
                DB::table('permissions')
                    ->where('name', $permission)
                    ->where('guard_name', 'web')
                    ->update([
                        'description' => $description,
                        'group' => $group,
                        'updated_at' => $now,
                    ]);
            }
        }

        $this->command->info('✅ Permissions seeded successfully! Total: '.count($permissions));
    }

    private function getPermissionGroup(string $permission): string
    {
        if (str_starts_with($permission, 'menu-')) {
            return 'Sidebar';
        }
        if (str_starts_with($permission, 'dashboard_')) {
            return 'Dashboard';
        }
        if (str_starts_with($permission, 'user_')) {
            return 'User Management';
        }
        if (str_starts_with($permission, 'role_')) {
            return 'Role Management';
        }
        if (str_starts_with($permission, 'permission_')) {
            return 'Permission Management';
        }
        if (str_starts_with($permission, 'gtk_')) {
            return 'GTK Management';
        }
        if (str_starts_with($permission, 'work_unit_')) {
            return 'Work Unit';
        }
        if (str_starts_with($permission, 'satpen_')) {
            return 'Satuan Pendidikan';
        }
        if (str_starts_with($permission, 'wilayah_')) {
            return 'Wilayah';
        }
        if (str_starts_with($permission, 'kepegawaian_')) {
            return 'Kepegawaian';
        }
        if (str_starts_with($permission, 'absensi_')) {
            return 'Absensi';
        }
        if (str_starts_with($permission, 'penggajian_')) {
            return 'Penggajian';
        }
        if (str_starts_with($permission, 'cuti_')) {
            return 'Cuti';
        }
        if (str_starts_with($permission, 'penilaian_')) {
            return 'Penilaian';
        }
        if (str_starts_with($permission, 'pelatihan_')) {
            return 'Pelatihan';
        }
        if (str_starts_with($permission, 'inventory_')) {
            return 'Inventory';
        }
        if (str_starts_with($permission, 'sarpras_')) {
            return 'Sarana Prasarana';
        }
        if (str_starts_with($permission, 'laporan_')) {
            return 'Laporan';
        }
        if (str_starts_with($permission, 'settings_')) {
            return 'Settings';
        }
        if (str_starts_with($permission, 'backup_')) {
            return 'Backup';
        }
        if (str_starts_with($permission, 'log_')) {
            return 'Logs';
        }
        if (str_starts_with($permission, 'notification_')) {
            return 'Notifications';
        }
        if (str_starts_with($permission, 'profile_')) {
            return 'Profile';
        }
        if (str_starts_with($permission, 'student_')) {
            return 'Santri';
        }
        if (str_starts_with($permission, 'mahrom_')) {
            return 'Santri';
        }

        // Asrama / Boarding module
        if (str_starts_with($permission, 'dormitory_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'wing_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'room_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'resident_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'attendance_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'permit_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'violation_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'reward_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'visit_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'activity_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'template_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'post_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'broadcast_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'report_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'approval_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'boarding_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'calendar_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'pengasuh_')) {
            return 'Asrama';
        }
        if (str_starts_with($permission, 'asrama_')) {
            return 'Asrama';
        }

        if (str_starts_with($permission, 'school_')) {
            return 'Akademik';
        }
        if (str_starts_with($permission, 'grade_level_')) {
            return 'Akademik';
        }
        if (str_starts_with($permission, 'study_group_')) {
            return 'Akademik';
        }
        if ($permission === 'view_global_school_data') {
            return 'Akademik';
        }
        if (str_starts_with($permission, 'impersonate_')) {
            return 'Akademik';
        }

        return 'General';
    }

    private function getPermissionDescription(string $permission): ?string
    {
        $descriptions = [
            'dashboard_view' => 'Can view dashboard',
            'dashboard_analytics_view' => 'Can view analytics dashboard',
            'dashboard_statistics_view' => 'Can view statistics dashboard',
            'user_view' => 'Can view users',
            'user_create' => 'Can create users',
            'user_edit' => 'Can edit users',
            'user_delete' => 'Can delete users',
            'user_export' => 'Can export users data',
            'user_import' => 'Can import users data',
            'role_view' => 'Can view roles',
            'role_create' => 'Can create roles',
            'role_edit' => 'Can edit roles',
            'role_delete' => 'Can delete roles',
            'role_assign' => 'Can assign roles to users',
            'permission_view' => 'Can view permissions',
            'permission_assign' => 'Can assign permissions to roles',
            'gtk_view' => 'Can view GTK data',
            'gtk_create' => 'Can create GTK data',
            'gtk_edit' => 'Can edit GTK data',
            'gtk_delete' => 'Can delete GTK data',
            'gtk_export' => 'Can export GTK data',
            'gtk_import' => 'Can import GTK data',
            'gtk_status_toggle' => 'Can toggle GTK status',
            'gtk_detail_view' => 'Can view GTK details',
            'gtk_family_view' => 'Can view GTK family data',
            'gtk_family_edit' => 'Can edit GTK family data',
            'gtk_employment_view' => 'Can view GTK employment data',
            'gtk_employment_edit' => 'Can edit GTK employment data',
            'gtk_contact_view' => 'Can view GTK contact data',
            'gtk_contact_edit' => 'Can edit GTK contact data',
            'gtk_address_view' => 'Can view GTK address data',
            'gtk_address_edit' => 'Can edit GTK address data',
            'work_unit_view' => 'Can view work units',
            'work_unit_create' => 'Can create work units',
            'work_unit_edit' => 'Can edit work units',
            'work_unit_delete' => 'Can delete work units',
            'work_unit_assign' => 'Can assign work units',
            'satpen_view' => 'Can view satuan pendidikan',
            'satpen_create' => 'Can create satuan pendidikan',
            'satpen_edit' => 'Can edit satuan pendidikan',
            'satpen_delete' => 'Can delete satuan pendidikan',
            'satpen_activate' => 'Can activate satuan pendidikan',
            'satpen_deactivate' => 'Can deactivate satuan pendidikan',
            'data_master_view' => 'Can view master data',
            'data_master_edit' => 'Can edit master data',
            'wilayah_view' => 'Can view wilayah data',
            'wilayah_edit' => 'Can edit wilayah data',
            'wilayah_sync' => 'Can sync wilayah data',
            'kepegawaian_view' => 'Can view kepegawaian data',
            'kepegawaian_edit' => 'Can edit kepegawaian data',
            'kepegawaian_report' => 'Can view kepegawaian reports',
            'absensi_view' => 'Can view attendance data',
            'absensi_edit' => 'Can edit attendance data',
            'absensi_report' => 'Can view attendance reports',
            'absensi_approve' => 'Can approve attendance',
            'penggajian_view' => 'Can view payroll data',
            'penggajian_edit' => 'Can edit payroll data',
            'penggajian_process' => 'Can process payroll',
            'penggajian_report' => 'Can view payroll reports',
            'cuti_view' => 'Can view leave data',
            'cuti_create' => 'Can create leave requests',
            'cuti_edit' => 'Can edit leave data',
            'cuti_approve' => 'Can approve leave requests',
            'cuti_report' => 'Can view leave reports',
            'penilaian_view' => 'Can view performance assessments',
            'penilaian_create' => 'Can create performance assessments',
            'penilaian_edit' => 'Can edit performance assessments',
            'penilaian_report' => 'Can view performance assessment reports',
            'pelatihan_view' => 'Can view training data',
            'pelatihan_create' => 'Can create training programs',
            'pelatihan_edit' => 'Can edit training data',
            'pelatihan_report' => 'Can view training reports',
            'inventory_view' => 'Can view inventory',
            'inventory_create' => 'Can create inventory items',
            'inventory_edit' => 'Can edit inventory items',
            'inventory_delete' => 'Can delete inventory items',
            'inventory_report' => 'Can view inventory reports',
            'laporan_view' => 'Can view reports',
            'laporan_generate' => 'Can generate reports',
            'laporan_export' => 'Can export reports',
            'settings_view' => 'Can view settings',
            'settings_general_edit' => 'Can edit general settings',
            'settings_email_edit' => 'Can edit email settings',
            'settings_system_edit' => 'Can edit system settings',
            'backup_view' => 'Can view backups',
            'backup_create' => 'Can create backups',
            'backup_restore' => 'Can restore from backups',
            'log_view' => 'Can view logs',
            'log_activity_view' => 'Can view activity logs',
            'log_error_view' => 'Can view error logs',
            'notification_view' => 'Can view notifications',
            'notification_send' => 'Can send notifications',
            'notification_settings' => 'Can edit notification settings',
            'profile_view' => 'Can view profile',
            'profile_edit' => 'Can edit profile',
            'password_change' => 'Can change password',
            'view_global_school_data' => 'Can view all school data across all units (cross-school view)',
            'impersonate_role' => 'Can login as another user for support / debugging purposes (Super Admin only)',
            // Student
            'student_view' => 'Can view student data',
            'student_create' => 'Can create student data',
            'student_edit' => 'Can edit student data',
            'student_delete' => 'Can delete student data',
            'student_export' => 'Can export student data',
            'student_import' => 'Can import student data',
            // Student Mutation
            'student_mutation_view' => 'Can view student mutation data',
            'student_mutation_create' => 'Can create student mutation',
            'student_mutation_edit' => 'Can edit student mutation',
            'student_mutation_approve' => 'Can approve/reject student mutation',
            'student_mutation_export' => 'Can export student mutation data',
            'student_mutation_import' => 'Can import student mutation data',
        ];

        return $descriptions[$permission] ?? null;
    }
}
