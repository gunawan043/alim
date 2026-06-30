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
        'gtk.read'           => 'GTK: read records',
        'gtk.write'          => 'GTK: create/update records',
        'gtk.delete'         => 'GTK: delete records',
        'gtk.approve'        => 'GTK: approve role transitions',
        'gtk.assign'         => 'GTK: assign role',
        'gtk.transfer'       => 'GTK: submit/process transfer',
        'students.read'      => 'Students: read records',
        'students.write'     => 'Students: create/update records',
        'students.delete'    => 'Students: delete records',
        'wali.read'          => 'Wali Santri: read',
        'wali.write'         => 'Wali Santri: create/update',
        'wali.assign'        => 'Wali Santri: bind/unbind',
        'payroll.read'       => 'Payroll: read',
        'payroll.write'      => 'Payroll: create/update',
        'payroll.approve'    => 'Payroll: approve',
        'payroll.disburse'   => 'Payroll: disburse',
        'exam.read'          => 'Exam: read',
        'exam.write'         => 'Exam: create/update',
        'exam.publish'       => 'Exam: publish results',
        'exam.supervise'     => 'Exam: supervise sessions',
        'presensi.read'      => 'Presensi: read',
        'presensi.write'     => 'Presensi: record',
        'presensi.approve'   => 'Presensi: approve corrections',
        'jadwalkbm.read'     => 'Jadwal KBM: read',
        'jadwalkbm.write'    => 'Jadwal KBM: create/update',
        'jadwalkbm.publish'   => 'Jadwal KBM: publish',
        'extracurricular.read'   => 'Extracurricular: read',
        'extracurricular.write'  => 'Extracurricular: create/update',
        'dormitory.read'     => 'Dormitory: read',
        'dormitory.write'    => 'Dormitory: create/update',
        'dormitory.broadcast'=> 'Dormitory: send broadcast',
        'audit.read'         => 'Audit: read',
        'audit.export'       => 'Audit: export',
        'reports.read'       => 'Reports: read',
        'reports.export'     => 'Reports: export',
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
        if (!self::exists($permission)) {
            throw new PermissionRegistryException(
                "Permission '{$permission}' is not registered."
            );
        }
    }
}