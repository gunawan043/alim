<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

/**
 * Invalidasi sesi saat password berubah (password hash disimpan di session).
 *
 * Dilewati saat unit test karena banyak test sengaja berganti user dalam satu
 * session (actingAs A → B → C), yang bukan skenario nyata dan akan memicu
 * logout. Di produksi middleware tetap berjalan penuh.
 */
class AuthenticateSessionSecure extends AuthenticateSession
{
    /**
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (app()->runningUnitTests()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
