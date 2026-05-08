<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix route prefixes: role.* → user.*
        $fixes = [
            'role.gtk.index'                           => 'user.gtk.index',
            'role.gtk-requests.create'                => 'user.gtk-requests.create',
            'role.gtk-requests.index'                 => 'user.gtk-requests.index',
            'role.approvals.index'                    => 'user.approvals.index',
            'role.ats.applications.index'              => 'user.ats.applications.index',
            'role.ats.candidates.index'               => 'user.ats.candidates.index',
            'role.ats.interviews.index'               => 'user.ats.interviews.index',
            'role.ats.jobs.index'                     => 'user.ats.jobs.index',
            'role.ats.reports.index'                  => 'user.ats.reports.index',
            'role.ats.settings.index'                 => 'user.ats.settings.index',
            'role.jenjang-karir.career-path.index'   => 'user.jenjang-karir.career-path.index',
            'role.jenjang-karir.mutasi.index'        => 'user.jenjang-karir.mutasi.index',
            'role.jenjang-karir.promosi.index'       => 'user.jenjang-karir.promosi.index',
            'role.jenjang-karir.succession.index'    => 'user.jenjang-karir.succession.index',
            'role.jenjang-karir.talent.index'         => 'user.jenjang-karir.talent.index',
            'role.master-data.jabatan.index'          => 'user.master-data.jabatan.index',
            'role.master-data.jenis-gtk.index'        => 'user.master-data.jenis-gtk.index',
            'role.master-data.satuan-kerja.index'    => 'user.master-data.satuan-kerja.index',
            'role.mutations-in.index'                 => 'user.mutations-in.index',
            'role.mutations-out.index'                => 'user.mutations-out.index',
            'role.sa.audit-logs.index'                => 'user.sa.audit-logs.index',
            'role.sa.failed-jobs.index'                => 'user.sa.failed-jobs.index',
            'role.sa.notifications.index'             => 'user.sa.notifications.index',
            'role.sa.password-reset-logs.index'       => 'user.sa.password-reset-logs.index',
            'role.sa.permissions.index'                => 'user.sa.permissions.index',
            'role.sa.roles.index'                     => 'user.sa.roles.index',
            'role.sa.sidebar-menus.index'             => 'user.sa.sidebar-menus.index',
            'role.sa.system-settings.index'            => 'user.sa.system-settings.index',
            'role.sa.tokens.index'                     => 'user.sa.tokens.index',
            'role.sa.users.index'                      => 'user.sa.users.index',
            'role.schools-global.index'               => 'user.schools-global.index',
            'role.students.index'                     => 'user.students.index',
            'role.study-groups.index'                  => 'user.study-groups.index',
        ];

        foreach ($fixes as $old => $new) {
            DB::table('sidebar_menus')
                ->where('route', $old)
                ->update(['route' => $new]);
        }
    }

    public function down(): void
    {
        $fixes = [
            'user.gtk.index'                           => 'role.gtk.index',
            'user.gtk-requests.create'                => 'role.gtk-requests.create',
            'user.gtk-requests.index'                 => 'role.gtk-requests.index',
            'user.approvals.index'                    => 'role.approvals.index',
            'user.ats.applications.index'              => 'role.ats.applications.index',
            'user.ats.candidates.index'               => 'role.ats.candidates.index',
            'user.ats.interviews.index'               => 'role.ats.interviews.index',
            'user.ats.jobs.index'                     => 'role.ats.jobs.index',
            'user.ats.reports.index'                  => 'role.ats.reports.index',
            'user.ats.settings.index'                 => 'role.ats.settings.index',
            'user.jenjang-karir.career-path.index'   => 'role.jenjang-karir.career-path.index',
            'user.jenjang-karir.mutasi.index'        => 'role.jenjang-karir.mutasi.index',
            'user.jenjang-karir.promosi.index'       => 'role.jenjang-karir.promosi.index',
            'user.jenjang-karir.succession.index'    => 'role.jenjang-karir.succession.index',
            'user.jenjang-karir.talent.index'         => 'role.jenjang-karir.talent.index',
            'user.master-data.jabatan.index'          => 'role.master-data.jabatan.index',
            'user.master-data.jenis-gtk.index'        => 'role.master-data.jenis-gtk.index',
            'user.master-data.satuan-kerja.index'    => 'role.master-data.satuan-kerja.index',
            'user.mutations-in.index'                 => 'role.mutations-in.index',
            'user.mutations-out.index'                => 'role.mutations-out.index',
            'user.sa.audit-logs.index'                => 'role.sa.audit-logs.index',
            'user.sa.failed-jobs.index'                => 'role.sa.failed-jobs.index',
            'user.sa.notifications.index'             => 'role.sa.notifications.index',
            'user.sa.password-reset-logs.index'       => 'role.sa.password-reset-logs.index',
            'user.sa.permissions.index'                => 'role.sa.permissions.index',
            'user.sa.roles.index'                     => 'role.sa.roles.index',
            'user.sa.sidebar-menus.index'             => 'role.sa.sidebar-menus.index',
            'user.sa.system-settings.index'            => 'role.sa.system-settings.index',
            'user.sa.tokens.index'                     => 'role.sa.tokens.index',
            'user.sa.users.index'                      => 'role.sa.users.index',
            'user.schools-global.index'               => 'role.schools-global.index',
            'user.students.index'                     => 'role.students.index',
            'user.study-groups.index'                  => 'role.study-groups.index',
        ];

        foreach ($fixes as $old => $new) {
            DB::table('sidebar_menus')
                ->where('route', $old)
                ->update(['route' => $new]);
        }
    }
};
