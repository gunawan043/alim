<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\GtkAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencegah IDOR pada data GTK (pegawai).
 *
 * Semua route di grup `gtk` yang memiliki parameter `{uuid}` (detail, edit,
 * data keluarga, pendidikan, kesehatan, dsb.) boleh dibaca oleh pegawai yang
 * sudah login (direktori internal), tetapi perubahan data lintas-pegawai hanya
 * boleh dilakukan oleh:
 *  - pemilik data (self-service), atau
 *  - pengelola GTK (System/Super Admin, Personalia, pemegang `gtk-edit-all`).
 */
class EnsureGtkAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $uuid = $request->route('uuid');

        if (! is_string($uuid) || $uuid === '') {
            return $next($request);
        }

        $actor = $request->user();

        if (! $actor) {
            abort(403, 'Unauthorized.');
        }

        $target = User::find($uuid);

        // Biarkan controller menghasilkan 404 untuk data yang tidak ada.
        if (! $target) {
            return $next($request);
        }

        $isWrite = ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        // Direktori internal: pegawai yang sudah login boleh melihat profil
        // rekan (perilaku lama dipertahankan). Yang dikunci adalah perubahan
        // data lintas-pegawai di bawah ini.
        if (! $isWrite) {
            return $next($request);
        }

        if ((string) $actor->id === (string) $target->id || GtkAccess::isManager($actor)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki akses untuk mengubah data GTK ini.');
    }
}
