<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: RestrictDormitoryUserFromStudents
 *
 * Mencegah user kategori asrama (kepala asrama, admin asrama, admin pendidikan,
 * kepala uks, admin uks putra/putri, admin kesehatan, wali asrama, asrama) melakukan
 * operasi WRITE ke resource Student (POST/PUT/PATCH/DELETE).
 *
 * Pembatasan hanya berlaku untuk aksi NON-MAHROM. Method yang berhubungan dengan
 * StudentMahrom (path mengandung "/mahrom") tetap diizinkan.
 *
 * GET/HEAD/OPTIONS tetap lolos — pembatas hanya di level view (sembunyikan tombol).
 *
 * Cara pakai di route:
 *   Route::post('/students', ...)->middleware('dormitory.restrict:students');
 *
 * Argumen:
 *   - $resourceKey: hanya dicek bila nama route mengandung key ini (mis. 'students').
 *     Biarkan kosong untuk membatasi semua route.
 */
class RestrictDormitoryUserFromStudents
{
    public function handle(Request $request, Closure $next, string $resourceKey = 'students'): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isDormitoryUser()) {
            return $next($request);
        }

        // Kecualikan aksi terkait Mahrom (CRUD + read Mahrom tetap diizinkan).
        $path = $request->path();
        if (str_contains($path, '/mahrom')) {
            return $next($request);
        }

        // Hanya blokir jika route terkait resource Santri.
        if ($resourceKey !== '' && ! str_contains($path, $resourceKey)) {
            return $next($request);
        }

        // Untuk GET, hanya izinkan halaman list (index) dan detail (show).
        // Halaman create/edit/import template juga dianggap "form write" dan diblokir.
        if (in_array($request->method(), ['GET', 'HEAD'], true)) {
            $allowGet = ['students.index', 'students.show'];
            $routeName = optional($request->route())->getName();
            if (in_array($routeName, $allowGet, true)) {
                return $next($request);
            }
            abort(403, 'Akun asrama tidak diizinkan membuka formulir pengelolaan data Santri.');
        }

        // Metode tulis (POST/PUT/PATCH/DELETE) → selalu blokir.
        abort(403, 'Akun asrama tidak diizinkan mengubah data Santri. Hanya data Mahrom yang dapat dikelola.');
    }
}
