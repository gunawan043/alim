<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dedicated service-to-service key for Connect -> Core SSO handoff exchange.
 * It is separate from the approval-command key and grants only sso:exchange.
 */
class VerifyConnectSsoApiKey extends VerifyIntegrationApiKey
{
    public const REQUIRED_SCOPE = 'sso:exchange';

    protected function configPrefix(): string
    {
        return 'connect_sso';
    }

    protected function integrationName(): string
    {
        return 'ALIM Connect SSO';
    }

    public function handle(Request $request, Closure $next): Response
    {
        $scopes = (array) config('services.connect_sso.scopes', [self::REQUIRED_SCOPE]);

        if (! in_array(self::REQUIRED_SCOPE, $scopes, true)) {
            return $this->scopeError();
        }

        return parent::handle($request, $next);
    }

    private function scopeError(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'API key ini tidak memiliki cakupan sso:exchange.',
            'data' => null,
            'meta' => ['timestamp' => now()->toIso8601String()],
            'errors' => ['code' => 'insufficient_scope'],
        ], 403);
    }
}
