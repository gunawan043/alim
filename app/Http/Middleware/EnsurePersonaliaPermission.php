<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePersonaliaPermission
{
    /**
     * Permission minimum per modul. Aksi tulis memakai permission *_edit;
     * approval memakai *_approve bila tersedia.
     */
    private const VIEW = [
        'payroll' => 'payroll_view',
        'cuti' => 'cuti_view',
        'kontrak' => 'kontrak_view',
        'kinerja' => 'kinerja_view',
        'pelatihan' => 'pelatihan_view',
        'kesejahteraan' => 'kesejahteraan_view',
        'absensi' => 'absensi_view',
        'kehadiran' => 'absensi_report',
        'peraturan' => 'gtk_view',
        'jam-kerja' => 'absensi_view',
        'analisis' => 'gtk_view',
        'rapor' => 'gtk_view',
    ];

    private const WRITE = [
        'payroll' => 'payroll_edit',
        'cuti' => 'cuti_edit',
        'kontrak' => 'kontrak_edit',
        'kinerja' => 'kinerja_edit',
        'pelatihan' => 'pelatihan_edit',
        'kesejahteraan' => 'kesejahteraan_edit',
        'absensi' => 'absensi_edit',
        'kehadiran' => 'absensi_edit',
        'peraturan' => 'gtk_edit',
        'jam-kerja' => 'absensi_edit',
        'analisis' => 'gtk_edit',
        'rapor' => 'gtk_edit',
    ];

    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if ($user->isSystemAdmin() || $user->isSuperAdmin()) {
            return $next($request);
        }

        $route = $request->route()?->getName() ?? '';
        $approvalAction = str_contains($route, 'approve') || str_contains($route, 'reject');
        $specialPermission = match (true) {
            $module === 'cuti' && $approvalAction => 'cuti_approve',
            $module === 'payroll' && (str_contains($route, 'publish') || str_contains($route, 'pay')) => 'payroll_edit',
            default => null,
        };
        $permission = $specialPermission
            ?: ($request->isMethod('GET') ? (self::VIEW[$module] ?? 'gtk_view') : (self::WRITE[$module] ?? 'gtk_edit'));

        // Permission yang belum di-seed tidak boleh membuat role Humas terkunci
        // secara tidak sengaja pada instalasi lama.
        if (! Permission::where('name', $permission)->where('guard_name', 'web')->exists()) {
            $legacyRoles = $module === 'absensi'
                ? ['Humas Personalia', 'Personalia', 'Satuan Pendidikan', 'Admin Tata Usaha']
                : ['Humas Personalia', 'Personalia'];

            if ($user->hasAnyRole($legacyRoles)) {
                return $next($request);
            }

            abort(403, 'Permission Personalia belum tersedia.');
        }

        $allowed = function_exists('canPermission') ? canPermission($permission) : false;
        abort_unless($allowed, 403, 'Anda tidak memiliki izin untuk modul Personalia ini.');

        return $next($request);
    }
}
