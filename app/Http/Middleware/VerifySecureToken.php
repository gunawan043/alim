<?php

namespace App\Http\Middleware;

use App\Models\SecureAccessToken;
use Closure;

class VerifySecureToken
{
    public function handle($request, Closure $next)
    {
        $token = $request->route('token');

        $record = SecureAccessToken::where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            abort(403, 'Secure token invalid or expired');
        }

        if (
            $record->ip_address !== $request->ip() ||
            $record->user_agent !== $request->userAgent()
        ) {
            abort(403, 'Environment mismatch');
        }

        $request->merge(['secure_access' => $record]);

        return $next($request);
    }
}
