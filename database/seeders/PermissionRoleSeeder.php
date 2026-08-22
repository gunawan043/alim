<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing assignments
        DB::table('role_has_permissions')->delete();

        // Helper: get role UUID by name
        $roleId = fn (string $name): ?string => DB::table('roles')
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->value('id');

        // Helper: get permission IDs by names (use DB facade to avoid UUID casting issues in seeder)
        $permIds = function (array $names): array {
            if (empty($names)) {
                return [];
            }

            return DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', $names)
                ->pluck('id')
                ->toArray();
        };

        // Helper: sync permissions for a role via direct DB insert
        $sync = function (string $roleId, array $permNames) use ($permIds) {
            if (empty($roleId)) {
                return;
            }
            $ids = $permIds($permNames);
            if (empty($ids)) {
                return;
            }
            $rows = array_map(fn ($pid) => ['permission_id' => $pid, 'role_id' => $roleId], $ids);
            DB::table('role_has_permissions')->insert($rows);
        };

        // Super Admin role removed: System Admin bypasses all checks via is_system_admin flag
        // (handled by Gate::before + AuthorizationManager short-circuit).
        // Role-based Super Admin (no flag) needs impersonate_role to use View-As switcher
        // for non-destructive role preview (one account, multiple role previews).
        $sync($roleId('Super Admin') ?? '', [
            'impersonate_role',
        ]);

        // ── MUDIR — puncak pimpinan: lihat semua GTK, Santri, laporan ──
        $sync($roleId('Mudir') ?? '', [
            'dashboard_view',
            'menu-wakil-kepala-sekolah-sidebar',
            'gtk_view', 'gtk_export', 'gtk_detail_view', 'gtk_employment_view',
            'satpen_view',
            'school_view',
            'grade_level_view',
            'study_group_view',
            'student_view', 'student_export',
            'data_master_view',
            'profile_view', 'profile_edit',
            'laporan_view', 'laporan_generate', 'laporan_export',
            'view_global_school_data',
        ]);

        // ── WADIR 1 — puncak pimpinan: lihat semua GTK + laporan ─────
        $sync($roleId('Wadir 1') ?? '', [
            'dashboard_view',
            'menu-wakil-kepala-sekolah-sidebar',
            'gtk_view', 'gtk_export', 'gtk_detail_view', 'gtk_employment_view',
            'satpen_view',
            'school_view',
            'grade_level_view',
            'study_group_view',
            'profile_view', 'profile_edit',
            'laporan_view', 'laporan_generate', 'laporan_export',
            'view_global_school_data',
        ]);

        // ── WADIR 2 — puncak pimpinan: lihat GTK + Santri + mutasi ────
        $sync($roleId('Wadir 2') ?? '', [
            'dashboard_view',
            'menu-wakil-kepala-sekolah-sidebar',
            'gtk_view', 'gtk_detail_view', 'gtk_employment_view',
            'satpen_view',
            'school_view',
            'grade_level_view',
            'study_group_view',
            'student_view', 'student_export',
            'student_mutation_view', 'student_mutation_create',
            'profile_view', 'profile_edit',
            'laporan_view', 'laporan_export',
            'view_global_school_data',
        ]);

        // ── PERSONDALIA — urus data GTK (global access) ─────────────────
        $sync($roleId('Personalia') ?? '', [
            'dashboard_view',
            'menu-personalia-sidebar',
            'gtk_view', 'gtk_create', 'gtk_edit', 'gtk_delete',
            'gtk_export', 'gtk_import',
            'gtk_detail_view',
            'gtk_family_view', 'gtk_family_edit',
            'gtk_employment_view', 'gtk_employment_edit',
            'gtk_contact_view', 'gtk_contact_edit',
            'gtk_address_view', 'gtk_address_edit',
            // Kebab-case aliases (dipakai di views/controllers)
            'gtk-role', 'gtk-create', 'gtk-update', 'gtk-delete',
            'profile_view', 'profile_edit',
            'laporan_view', 'laporan_export',
            'view_global_school_data',
            // Personalia HRD modules
            'payroll_view', 'payroll_create', 'payroll_edit',
            'cuti_view', 'cuti_approve',
            'kontrak_view', 'kontrak_create', 'kontrak_edit',
            'kinerja_view', 'kinerja_create', 'kinerja_edit',
            'pelatihan_view', 'pelatihan_create', 'pelatihan_edit',
            'kesejahteraan_view', 'kesejahteraan_create', 'kesejahteraan_edit',
            // Teacher attendance — needed for payroll reference
            'teacher-attendance_view',
            'teacher-attendance_report_export',
        ]);

        // ── ADMIN TATA USAHA — scoped: GTK edit + Santri CRUD ─────────
        $sync($roleId('Admin Tata Usaha') ?? '', [
            'dashboard_view',
            'menu-admin-tu-sidebar',
            'gtk_view', 'gtk_edit', 'gtk_export', 'gtk_import',
            'gtk_detail_view', 'gtk_employment_view', 'gtk_employment_edit',
            'satpen_view',
            'school_view',
            'grade_level_view', 'grade_level_create', 'grade_level_edit', 'grade_level_delete',
            'study_group_view', 'study_group_create', 'study_group_edit', 'study_group_delete',
            'student_view', 'student_create', 'student_edit', 'student_export', 'student_import',
            'student_mutation_view', 'student_mutation_create', 'student_mutation_edit',
            'student_mutation_approve', 'student_mutation_export',
            'data_master_view',
            'profile_view', 'profile_edit',
            'laporan_view', 'laporan_generate', 'laporan_export',
            // Teacher attendance — admin TU needed for payroll process
            'teacher-attendance_view',
            'teacher-attendance_report_export',
        ]);

        // ── TATA USAHA — read-only scoped: GTK view + Santri view + mutasi ajukan ─
        $sync($roleId('Tata Usaha') ?? '', [
            'dashboard_view',
            'menu-admin-tu-sidebar',
            'gtk_view', 'gtk_detail_view',
            'satpen_view',
            'school_view',
            'grade_level_view',
            'study_group_view',
            'student_view',
            'student_mutation_view', 'student_mutation_create', 'student_mutation_edit',
            'profile_view', 'profile_edit',
        ]);

        // ── KEPALA SEKOLAH — scoped: lihat GTK + Santri + laporan ─────
        $sync($roleId('Kepala Sekolah') ?? '', [
            'dashboard_view',
            'menu-wakil-kepala-sekolah-sidebar',
            'gtk_view', 'gtk_detail_view', 'gtk_employment_view',
            'satpen_view',
            'school_view',
            'grade_level_view',
            'study_group_view',
            'student_view', 'student_export',
            'data_master_view',
            'profile_view', 'profile_edit',
            'laporan_view', 'laporan_export',
            'students.read',
            // Teacher attendance — KSP needs full report export
            'teacher-attendance_view',
            'teacher-attendance_report_export',
        ]);

        // ── KEPALA ASRAMA — puncak asrama: approval semua modul, laporan seluruh asrama ─
        $sync($roleId('Kepala Asrama') ?? '', [
            // Dashboard
            'dashboard_view',
            'menu-head-asrama-sidebar',
            'profile_view', 'profile_edit',
            // Sidebar access
            'menu-asrama-sidebar', 'menu-head-asrama-sidebar', 'menu-admin-asrama-sidebar',
            'menu-pendidikan-asrama-sidebar', 'menu-kesehatan-asrama-sidebar', 'menu-wali-asrama-sidebar',
            // Master data — full CRUD
            'dormitory_view', 'dormitory_create', 'dormitory_edit',
            'wing_view', 'wing_create', 'wing_edit', 'wing_delete',
            'room_view', 'room_create', 'room_edit', 'room_delete',
            'resident_view', 'resident_create', 'resident_edit',
            // Approval center — full power
            'approval_center_view', 'approval_center_approve',
            // Attendance
            'attendance_view', 'attendance_create', 'attendance_approve',
            // Permits
            'permit_view', 'permit_approve',
            // Jenis Izin (CRUD permit types)
            'permit_type_view', 'permit_type_create', 'permit_type_edit', 'permit_type_delete', 'permit_type_toggle_active',
            // Violations
            'violation_view', 'violation_approve',
            // Rewards
            'reward_view', 'reward_create',
            // Visits
            'visit_view', 'visit_approve',
            // Room moves
            'room_move_view', 'room_move_approve',
            // Reports
            'report_view', 'report_generate', 'report_export',
            // Inventory
            'dormitory_inventory_view', 'dormitory_inventory_create',
            'dormitory_inventory_edit',
            // Activities & Templates
            'activity_view', 'template_view',
            // Posts & broadcasts
            'post_view', 'broadcast_view',
            // Boarding policy
            'boarding_policy_view', 'boarding_policy_create',
            'boarding_policy_edit',
            // Calendars
            'calendar_return_view', 'calendar_visit_view',
            // Students related
            'student_view', 'student_export',
            'mahrom_view',
            'asrama_export',
        ]);

        // ── ADMIN ASRAMA — sistem admin: master data, user, konfig asrama ─
        $sync($roleId('Admin Asrama') ?? '', [
            'dashboard_view',
            'menu-admin-asrama-sidebar',
            'profile_view', 'profile_edit',
            // Sidebar access
            'menu-asrama-sidebar', 'menu-head-asrama-sidebar', 'menu-admin-asrama-sidebar',
            'menu-pendidikan-asrama-sidebar', 'menu-kesehatan-asrama-sidebar', 'menu-wali-asrama-sidebar',
            // Master data — full CRUD
            'dormitory_view', 'dormitory_create', 'dormitory_edit',
            'wing_view', 'wing_create', 'wing_edit', 'wing_delete',
            'room_view', 'room_create', 'room_edit', 'room_delete',
            'resident_view', 'resident_create', 'resident_edit', 'resident_delete',
            // Attendance
            'attendance_view', 'attendance_create', 'attendance_edit',
            // Permits
            'permit_view', 'permit_create',
            // Jenis Izin (CRUD permit types)
            'permit_type_view', 'permit_type_create', 'permit_type_edit', 'permit_type_delete', 'permit_type_toggle_active',
            // Violations
            'violation_view', 'violation_create', 'violation_edit',
            // Rewards
            'reward_view', 'reward_create',
            // Visits
            'visit_view', 'visit_create',
            // Room moves
            'room_move_view', 'room_move_create',
            // Inventory
            'dormitory_inventory_view', 'dormitory_inventory_create',
            'dormitory_inventory_edit', 'dormitory_inventory_delete',
            // Activities & templates
            'activity_view', 'activity_create', 'activity_edit',
            'template_view', 'template_create', 'template_edit',
            // Posts & broadcasts
            'post_view', 'post_create', 'post_edit',
            'broadcast_view', 'broadcast_create', 'broadcast_send',
            // Reports
            'report_view', 'report_export',
            // Boarding policy
            'boarding_policy_view', 'boarding_policy_create',
            'boarding_policy_edit',
            // Calendars
            'calendar_return_view', 'calendar_return_create',
            'calendar_visit_view', 'calendar_visit_create',
            // Students related
            'student_view', 'student_export',
            'mahrom_view',
            // Approval center — view only
            'approval_center_view',
            'asrama_export',
        ]);

        // ── ADMIN PENDIDIKAN — akademik: izin, kebijakan, kalender ─
        $sync($roleId('Admin Pendidikan') ?? '', [
            'dashboard_view',
            'menu-pendidikan-asrama-sidebar',
            'profile_view', 'profile_edit',
            // Sidebar access (semua role asrama butuh ini untuk tampil)
            'menu-asrama-sidebar', 'menu-pendidikan-asrama-sidebar',
            // Boarding policy
            'boarding_policy_view', 'boarding_policy_create',
            'boarding_policy_edit',
            // Calendars
            'calendar_return_view', 'calendar_return_create',
            'calendar_visit_view', 'calendar_visit_create',
            // Permits
            'permit_view', 'permit_create', 'permit_approve',
            // Jenis Izin (CRUD permit types)
            'permit_type_view', 'permit_type_create', 'permit_type_edit',
            // Posts
            'post_view', 'post_create', 'post_edit',
            // Activities
            'activity_view', 'activity_create', 'activity_edit',
            // Templates
            'template_view', 'template_create', 'template_edit',
            // Reports
            'report_view', 'report_export',
            // Students related
            'student_view', 'student_export',
            'mahrom_view',
            'asrama_export',
        ]);

        // ── KEPALA UKS — UKS penuh, semua GTK (putra & putri) ──
        $sync($roleId('Kepala UKS') ?? '', [
            'dashboard_view',
            'menu-uks-sidebar',
            'profile_view', 'profile_edit',
            // Sidebar access
            'menu-asrama-sidebar', 'menu-uks-sidebar',
            // Reports
            'report_view', 'report_generate', 'report_export',
            // Activities
            'activity_view', 'activity_create', 'activity_edit',
            // Student view
            'student_view', 'student_export',
            'mahrom_view',
            'asrama_export',
            // GTK health data view/update
            'gtk_view', 'gtk_detail_view',
            // Patient tracking
            'uks_patient_view', 'uks_patient_create', 'uks_patient_edit',
        ]);

        // ── ADMIN UKS — GTK UKS semua gender (merge Putra & Putri) ──
        $sync($roleId('Admin UKS') ?? '', [
            'dashboard_view',
            'menu-uks-sidebar',
            'profile_view', 'profile_edit',
            'menu-asrama-sidebar', 'menu-uks-sidebar',
            'student_view',
            'gtk_view', 'gtk_detail_view',
            'uks_patient_view', 'uks_patient_create', 'uks_patient_edit',
        ]);

        // ── ADMIN UKS PUTRA — GTK UKS putra only ──
        $sync($roleId('Admin UKS Putra') ?? '', [
            'dashboard_view',
            'menu-uks-sidebar',
            'profile_view', 'profile_edit',
            'menu-asrama-sidebar', 'menu-uks-sidebar',
            'student_view',
            'gtk_view', 'gtk_detail_view',
            'uks_patient_view', 'uks_patient_create', 'uks_patient_edit',
        ]);

        // ── ADMIN UKS PUTRI — GTK UKS putri only ──
        $sync($roleId('Admin UKS Putri') ?? '', [
            'dashboard_view',
            'menu-uks-sidebar',
            'profile_view', 'profile_edit',
            'menu-asrama-sidebar', 'menu-uks-sidebar',
            'student_view',
            'gtk_view', 'gtk_detail_view',
            'uks_patient_view', 'uks_patient_create', 'uks_patient_edit',
        ]);

        // ── UKS — Legacy/General UKS role ──
        $sync($roleId('UKS') ?? '', [
            'dashboard_view',
            'menu-uks-sidebar',
            'profile_view', 'profile_edit',
            'menu-asrama-sidebar', 'menu-uks-sidebar',
            'student_view',
            'gtk_view', 'gtk_detail_view',
            'uks_patient_view', 'uks_patient_create', 'uks_patient_edit',
        ]);

        // ── ADMIN KESEHATAN (UKS fallback) — health checkup, obat, rujukan ─
        $sync($roleId('Admin Kesehatan') ?? '', [
            'dashboard_view',
            'menu-kesehatan-asrama-sidebar',
            'profile_view', 'profile_edit',
            'menu-asrama-sidebar', 'menu-kesehatan-asrama-sidebar',
            'report_view', 'report_generate', 'report_export',
            'activity_view', 'activity_create', 'activity_edit',
            'student_view', 'student_export',
            'mahrom_view',
            'asrama_export',
        ]);

        // ── WALI ASRAMA — pengasuh: absensi, pelanggaran, visite, kegiatan ─
        $sync($roleId('Wali Asrama') ?? '', [
            'dashboard_view',
            'menu-wali-asrama-sidebar',
            'profile_view', 'profile_edit',
            // Sidebar access
            'menu-asrama-sidebar', 'menu-wali-asrama-sidebar',
            // Attendance
            'attendance_view', 'attendance_create', 'attendance_edit',
            // Violations
            'violation_view', 'violation_create', 'violation_edit',
            // Rewards
            'reward_view', 'reward_create',
            // Visits
            'visit_view', 'visit_create',
            // Room moves
            'room_move_view', 'room_move_create',
            // Activities & templates
            'activity_view', 'activity_create',
            // Posts
            'post_view',
            // Resident view only
            'resident_view',
            // Room/Wing view
            'room_view', 'wing_view', 'dormitory_view',
            // Boarding policy
            'boarding_policy_view',
            // Calendars
            'calendar_return_view', 'calendar_visit_view',
            // Students related
            'student_view',
            'mahrom_view',
            // Approval center — view only
            'approval_center_view',
            // Pengasuh dashboard
            'pengasuh_dashboard_view',
            // Reports — view only
            'report_view',
        ]);

        // ── ASRAMA — read-only monitoring asrama ─
        $sync($roleId('Asrama') ?? '', [
            'dashboard_view',
            'menu-asrama-sidebar',
            'profile_view', 'profile_edit',
            // Sidebar access
            'menu-asrama-sidebar',
            // View-only all modules
            'dormitory_view',
            'wing_view', 'room_view',
            'resident_view',
            'attendance_view',
            'permit_view',
            'violation_view',
            'reward_view',
            'visit_view',
            'room_move_view',
            'dormitory_inventory_view',
            'activity_view',
            'template_view',
            'post_view',
            'broadcast_view',
            'report_view',
            'boarding_policy_view',
            'calendar_return_view', 'calendar_visit_view',
            'student_view',
            'mahrom_view',
            'approval_center_view',
            'pengasuh_dashboard_view',
        ]);

        // ── GURU — gabungan dari: Guru (Umum/Agama/Hadits/Bahasa Arab), GTK, Kepala Lembaga Pendidikan ─
        $sync($roleId('Guru') ?? '', [
            'dashboard_view',
            'menu-gtk-sidebar',
            'menu-wali-kelas-sidebar',
            'menu-coordinator-rumpun-sidebar',
            'menu-waka-kurikulum-sidebar',
            'profile_view', 'profile_edit',
            'password_change',
            'teacher-attendance_view',
            'teacher-attendance_export',
            'students.read',
            'students.write',
            'exam.read',
            'general_teacher.readable',
            'student_teacher.readable',
        ]);

        // ── GURU TAHFIDZ — Guru + akses tahfidz ────────────────────
        $sync($roleId('Guru Tahfidz') ?? '', [
            'dashboard_view',
            'menu-gtk-sidebar',
            'menu-wali-kelas-sidebar',
            'menu-coordinator-rumpun-sidebar',
            'menu-waka-kurikulum-sidebar',
            'profile_view', 'profile_edit',
            'password_change',
            'students.read',
            'students.write',
            'exam.read',
            'tahfidz_view', 'tahfidz_create', 'tahfidz_edit',
            'general_teacher.readable',
            'student_teacher.readable',
        ]);

        // ── COORDINATOR GURU — Guru + akses ekstra (data GTK lingkup KSP) ─
        $sync($roleId('Coordinator Guru') ?? '', [
            'dashboard_view',
            'menu-gtk-sidebar',
            'menu-wali-kelas-sidebar',
            'menu-coordinator-rumpun-sidebar',
            'menu-waka-kurikulum-sidebar',
            'gtk_view', 'gtk_detail_view',
            'school_view', 'grade_level_view', 'study_group_view',
            'profile_view', 'profile_edit',
            'password_change',
            'students.read',
            'students.write',
            'exam.read',
            'exam.write',
            'general_teacher.readable',
            'student_teacher.readable',
        ]);

        // ── DEPARTEMEN TAHFIDZ — Guru Tahfidz + akses tahfidz manajemen ─
        $sync($roleId('Departemen Tahfidz') ?? '', [
            'dashboard_view',
            'menu-gtk-sidebar',
            'menu-wali-kelas-sidebar',
            'menu-coordinator-rumpun-sidebar',
            'menu-waka-kurikulum-sidebar',
            'profile_view', 'profile_edit',
            'password_change',
            'students.read',
            'students.write',
            'exam.read',
            'exam.write',
            'tahfidz_view', 'tahfidz_create', 'tahfidz_edit',
            'general_teacher.readable',
            'student_teacher.readable',
        ]);

        // ── WAKIL KEPALA SEKOLAH — Waka full access + GTK base ─────
        $sync($roleId('Wakil Kepala Sekolah') ?? '', [
            'dashboard_view',
            'menu-gtk-sidebar',
            'menu-waka-kurikulum-sidebar',
            'menu-wali-kelas-sidebar',
            'menu-coordinator-rumpun-sidebar',
            'gtk_view', 'gtk_detail_view',
            'gtk_employment_view',
            'satpen_view',
            'school_view',
            'grade_level_view',
            'study_group_view',
            'student_view', 'student_export',
            'student_mutation_view', 'student_mutation_create', 'student_mutation_edit',
            'student_mutation_approve', 'student_mutation_export',
            'data_master_view',
            'profile_view', 'profile_edit',
            'laporan_view', 'laporan_generate', 'laporan_export',
            'view_global_school_data',
            'students.read',
            'students.write',
            'exam.read',
            'general_teacher.readable',
            'student_teacher.readable',
            // Teacher attendance — Waka needs full report export
            'teacher-attendance_view',
            'teacher-attendance_report_export',
            'teacher-attendance_manual',
        ]);

        // ── ADMIN SARPRAS — sidebar sarpras ───────────────────────────
        $sync($roleId('Admin Sarpras') ?? '', [
            'dashboard_view',
            'menu-admin-sarpras-sidebar',
            'profile_view', 'profile_edit',
            'sarpras_all_access',
            'inventory_view', 'inventory_create', 'inventory_edit',
        ]);

        // ── SARPRAS — staf sarpras (sidebar sarpras read-only) ────────
        $sync($roleId('Sarpras') ?? '', [
            'dashboard_view',
            'menu-sarpras-sidebar',
            'profile_view', 'profile_edit',
            'inventory_view',
        ]);

        // ── KEUANGAN — Keuangan/Bendahara ─────────────────────────────
        $sync($roleId('Keuangan') ?? '', [
            'dashboard_view',
            'menu-personalia-sidebar', // reuse personalia for payrol/cuti menu until finance sidebar exists
            'profile_view', 'profile_edit',
            'payroll_view', 'payroll_create', 'payroll_edit',
            'laporan_view', 'laporan_generate', 'laporan_export',
        ]);

        // ── ADMINISTRATOR — Kesekretariatan (TU) ──────────────────────
        $sync($roleId('Administrator') ?? '', [
            'dashboard_view',
            'menu-admin-tu-sidebar',
            'profile_view', 'profile_edit',
        ]);

        $this->command->info('Permission-role assignments complete.');
    }
}
