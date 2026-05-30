<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class ValidateMobileToken
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return $this->unauthorized('USER_NOT_FOUND', 'User tidak ditemukan.');
            }

            if (!$user->is_active) {
                return $this->unauthorized('ACCOUNT_INACTIVE', 'Akun tidak aktif.');
            }

            if ($user->isLocked()) {
                return $this->unauthorized('ACCOUNT_LOCKED', 'Akun terkunci sementara.');
            }

            // Opsional: cek apakah user ini adalah wali
            if (!$user->is_wali) {
                return $this->forbidden('NOT_A_WALI', 'Akun ini bukan akun wali.');
            }

        } catch (TokenExpiredException $e) {
            return $this->unauthorized('TOKEN_EXPIRED', 'Token sudah kedaluwarsa. Silakan login ulang.');
        } catch (TokenInvalidException $e) {
            return $this->unauthorized('TOKEN_INVALID', 'Token tidak valid.');
        } catch (JWTException $e) {
            return $this->unauthorized('TOKEN_ABSENT', 'Token autentikasi diperlukan.');
        }

        return $next($request);
    }

    private function unauthorized(string $code, string $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => compact('code', 'message'),
        ], 401);
    }

    private function forbidden(string $code, string $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => compact('code', 'message'),
        ], 403);
    }
}
