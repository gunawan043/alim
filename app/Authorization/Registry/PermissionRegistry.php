<?php

declare(strict_types=1);

namespace App\Authorization\Registry;

use App\Authorization\Exceptions\PermissionRegistryException;

final class PermissionRegistry
{
    /**
     * @var array<string, string>
     */
    private const PERMISSIONS = [
        'gtk.read' => 'GTK: read records',
        'gtk.write' => 'GTK: create/update records',
        'gtk.delete' => 'GTK: delete records',
        'gtk.approve' => 'GTK: approve role transitions',
        'gtk.assign' => 'GTK: assign role',
        'gtk.transfer' => 'GTK: submit/process transfer',
        'students.read' => 'Students: read records',
        'students.write' => 'Students: create/update records',
        'students.delete' => 'Students: delete records',
        'wali.read' => 'Wali Santri: read',
        'wali.write' => 'Wali Santri: create/update',
        'wali.assign' => 'Wali Santri: bind/unbind',
        'payroll.read' => 'Payroll: read',
        'payroll.write' => 'Payroll: create/update',
        'payroll.approve' => 'Payroll: approve',
        'payroll.disburse' => 'Payroll: disburse',
        'exam.read' => 'Exam: read',
        'exam.write' => 'Exam: create/update',
        'exam.publish' => 'Exam: publish results',
        'exam.supervise' => 'Exam: supervise sessions',
        'presensi.read' => 'Presensi: read',
        'presensi.write' => 'Presensi: record',
        'presensi.approve' => 'Presensi: approve corrections',
        'jadwalkbm.read' => 'Jadwal KBM: read',
        'jadwalkbm.write' => 'Jadwal KBM: create/update',
        'jadwalkbm.publish' => 'Jadwal KBM: publish',
        'jadwalkbm.draft' => 'Jadwal KBM: manage drafts',
        'jadwalkbm.review' => 'Jadwal KBM: review drafts',
        'jadwalkbm.archive' => 'Jadwal KBM: archive',
        'extracurricular.read' => 'Extracurricular: read',
        'extracurricular.write' => 'Extracurricular: create/update',
        'dormitory.read' => 'Dormitory: read',
        'dormitory.write' => 'Dormitory: create/update',
        'dormitory.broadcast' => 'Dormitory: send broadcast',
        'dormitory-master-all-access' => 'Dormitory master data: full read access (Wadir+)',
        'dormitory-master-admin-access' => 'Dormitory master data: create/update/delete (Administrator+)',
        'audit.read' => 'Audit: read',
        'audit.export' => 'Audit: export',
        'reports.read' => 'Reports: read',
        'reports.export' => 'Reports: export',

        // Teacher role groups (used by controller list views)
        'general_teacher.readable' => 'General teacher: readable',
        'student_teacher.readable' => 'Student teacher: readable',
        'general_tutor.readable' => 'General tutor: readable',

        // Admin role groups (used by Kaldik, login notification)
        'admin.tu.assessable' => 'Admin TU: assessable',
        'general_admin.administrable' => 'General admin: administrable',

        // Recruitment (used by ApplicationController)
        'personalia.recruitable' => 'Personalia: recruitable/interviewer',

        // Technician assignment (used by Sarpras/TechnicianAssignmentService)
        'sarpras.technician.assignable' => 'Sarpras technician: assignable',

        // Sarpras notification targets (used by Sarpras listeners)
        'sarpras.administrator.accessible' => 'Sarpras admin team: accessible',
        'sarpras.manager.approvable' => 'Sarpras manager: approvable',
        'sarpras.auditor.auditable' => 'Sarpras auditor: auditable',

        // Human Resources notification (used by HRDNotificationService)
        'hr.notification.recipient' => 'HR notification recipient',

        // Decree signers (used by InstitutionDecreeController)
        'decree.signer.certifiable' => 'Decree: certifiable signer',

        // GTK transfer approval chain (used by ApprovalController)
        'gtk.transfer.approve.kepalasekolah' => 'GTK transfer: approver Kepala Sekolah',
        'gtk.transfer.approve.wadir1' => 'GTK transfer: approver Wakil Mudir I',
        'gtk.transfer.approve.wadir2' => 'GTK transfer: approver Wakil Mudir II',
        'gtk.transfer.approve.mudir' => 'GTK transfer: approver Mudir',
        'gtk.transfer.approve.yayasan' => 'GTK transfer: approver Yayasan',

        // Wali Santri / parent communication (used by DormitoryService notifications)
        'wali_santri.communicable' => 'Wali Santri: reachable for notifications',

        // Exclusion filter (users to exclude from teacher lists)
        'general_staff.ineligible' => 'General staff: ineligible for teaching assignments',

        // Workspace activation keys (gate which panels appear in GTK sidebar)
        'workspace.wali-kelas' => 'Workspace: Wali Kelas panel',
        'workspace.coordinator-rumpun' => 'Workspace: Koordinator Rumpun panel',
        'workspace.waka-kurikulum' => 'Workspace: Waka Kurikulum panel',
        'workspace.structural' => 'Workspace: Staff Struktural panel',

        // Decree approval workflow
        'decree.submit' => 'SK: submit for approval',
        'decree.approve' => 'SK: approve or reject',
        'decree.view-all' => 'SK: view all decrees',

        // Coordinator monitoring
        'koordinator-rumpun.monitor' => 'Koordinator: monitor rumpun members',

        // Full sarpras access groups (used by SarprasWorkspacePolicy)
        'sarpras_all_access' => 'Full sarpras admin access',
        'sarpras_create' => 'Sarpras: create',
        'sarpras_edit' => 'Sarpras: edit',
        'sarpras_delete' => 'Sarpras: delete',
        'inventory_view' => 'Inventory: view assets',

        // Super Admin system-level gate
        'super-admin-only' => 'Super Admin only (system-level gate)',
    ];

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::PERMISSIONS;
    }

    public static function exists(string $permission): bool
    {
        return array_key_exists($permission, self::PERMISSIONS);
    }

    public static function validate(string $permission): void
    {
        if (! self::exists($permission)) {
            throw new PermissionRegistryException(
                "Permission '{$permission}' is not registered."
            );
        }
    }
}
