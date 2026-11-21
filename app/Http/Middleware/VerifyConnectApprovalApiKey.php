<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dedicated service-to-service key for ALIM Connect approval commands.
 *
 * This key must not be accepted by the POS/Recruitment APIs and is never
 * exposed to end users. The endpoint is read-write only for approval commands;
 * it is not a generic Core write API.
 *
 * Cakupan (scope) key dibatasi pada `approval:command`. Operator yang
 * mengonfigurasi key ini dengan cakupan lain akan menerima 403, sehingga key
 * tidak pernah menjadi kredensial tulis umum.
 */
class VerifyConnectApprovalApiKey extends VerifyIntegrationApiKey
{
    public const REQUIRED_SCOPE = 'approval:command';

    protected function configPrefix(): string
    {
        return 'connect_approval';
    }

    protected function integrationName(): string
    {
        return 'ALIM Connect Approval';
    }

    public function handle(Request $request, Closure $next): Response
    {
        $scopes = (array) config('services.connect_approval.scopes', [self::REQUIRED_SCOPE]);

        if (! in_array(self::REQUIRED_SCOPE, $scopes, true)) {
            return $this->scopeError();
        }

        return parent::handle($request, $next);
    }

    private function scopeError(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'API key ini tidak memiliki cakupan approval:command.',
            'data' => null,
            'meta' => ['timestamp' => now()->toIso8601String()],
            'errors' => ['code' => 'insufficient_scope'],
        ], 403);
    }
}
