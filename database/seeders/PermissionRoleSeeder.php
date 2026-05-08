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
            if (empty($names)) return [];
            return DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', $names)
                ->pluck('id')
                ->toArray();
        };

        // Helper: sync permissions for a role via direct DB insert
        $sync = function (string $roleId, array $permNames) use ($permIds) {
            if (empty($roleId)) return;
            $ids = $permIds($permNames);
            if (empty($ids)) return;
            $rows = array_map(fn ($pid) => ['permission_id' => $pid, 'role_id' => $roleId], $ids);
            DB::table('role_has_permissions')->insert($rows);
        };

        // ── SUPER ADMIN — all permissions ──────────────────────────────
        $saId = $roleId('Super Admin');
        if ($saId) {
            $allPerms = DB::table('permissions')->where('guard_name', 'web')->pluck('name')->toArray();
            $sync($saId, $allPerms);
            $this->command->info("  Super Admin: all permissions.");
        }

        // ── MUDIR — puncak pimpinan: lihat semua GTK, Santri, laporan ──
        $sync($roleId('Mudir') ?? '', [
            'dashboard_view',
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

        // ── PERSONALIA — urus data GTK ────────────────────────────────
        $sync($roleId('Personalia') ?? '', [
            'dashboard_view',
            'gtk_view', 'gtk_create', 'gtk_edit', 'gtk_delete',
            'gtk_export', 'gtk_import',
            'gtk_detail_view',
            'gtk_family_view', 'gtk_family_edit',
            'gtk_employment_view', 'gtk_employment_edit',
            'gtk_contact_view', 'gtk_contact_edit',
            'gtk_address_view', 'gtk_address_edit',
            'profile_view', 'profile_edit',
            'laporan_view', 'laporan_export',
        ]);

        // ── ADMIN TATA USAHA — scoped: GTK edit + Santri CRUD ─────────
        $sync($roleId('Admin Tata Usaha') ?? '', [
            'dashboard_view',
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
        ]);

        // ── TATA USAHA — read-only scoped: GTK view + Santri view + mutasi ajukan ─
        $sync($roleId('Tata Usaha') ?? '', [
            'dashboard_view',
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
            'gtk_view', 'gtk_detail_view', 'gtk_employment_view',
            'satpen_view',
            'school_view',
            'grade_level_view',
            'study_group_view',
            'student_view', 'student_export',
            'data_master_view',
            'profile_view', 'profile_edit',
            'laporan_view', 'laporan_export',
        ]);

        // ── GTK — hanya profile sendiri ───────────────────────────────
        $sync($roleId('GTK') ?? '', [
            'dashboard_view',
            'profile_view', 'profile_edit',
            'password_change',
        ]);

        $this->command->info('Permission-role assignments complete.');
    }
}
