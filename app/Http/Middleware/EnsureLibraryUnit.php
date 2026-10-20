<?php

namespace App\Http\Middleware;

use App\Services\LibraryAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLibraryUnit
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route()?->getName();
        $user = $request->user();

        if (! $user || ! $route || ! str_starts_with($route, 'user.perpustakaan.')) {
            return $next($request);
        }

        if (str_starts_with($route, 'user.perpustakaan.units.')) {
            return $next($request);
        }

        $access = app(LibraryAccess::class);
        if ($access->canManage($user) && $access->unitsFor($user)->isEmpty()) {
            $userId = $request->route('userId') ?: $user->id;

            return redirect()
                ->route('user.perpustakaan.units.index', ['userId' => $userId])
                ->with('warning', 'Belum ada unit perpustakaan. Tambahkan unit terlebih dahulu agar halaman perpustakaan dapat digunakan.');
        }

        return $next($request);
    }
}
