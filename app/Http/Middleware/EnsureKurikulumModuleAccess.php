<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\KurikulumAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kebijakan akses modul Kurikulum & Perangkat:
 * - Boleh: tim kurikulum (editor + KSP/Wakil), guru/GTK pengajar, Super Admin.
 * - Ditolak: TU dan unit non-pendidikan (menu memang tidak ditampilkan;
 *   middleware ini menegakkan aturan di backend).
 */
class EnsureKurikulumModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && app(KurikulumAccess::class)->canAccessKurikulumModule($user)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses ke modul Kurikulum.');
    }
}
