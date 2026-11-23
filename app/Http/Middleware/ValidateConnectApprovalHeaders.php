<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validasi header kontrak ALIM Connect on `POST /api/connect/v1/approval-commands`.
 *
 * Kontrak (Connect §4):
 *  - `Idempotency-Key` opsional; bila dikirim, nilainya harus sama dengan
 *    `command_id` di body sehingga tidak ada dua jalur idempotensi yang
 *    saling bertentangan.
 *  - `X-ALIM-API-Version` opsional; versi yang tidak dikenal harus 422,
 *    bukan 500 dan bukan diabaikan diam-diam.
 *  - `content-type` wajib `application/json`; selain itu 415.
 *
 * Middleware ini hanya memeriksa header. Idempotensi tetap dijalankan oleh
 * `ApprovalCommandService` melalui `command_id`; header hanya dikunci agar
 * konsisten dengan body.
 */
class ValidateConnectApprovalHeaders
{
    use ApiResponse;

    public const SUPPORTED_VERSIONS = ['1'];

    public function handle(Request $request, Closure $next): Response
    {
        $version = trim((string) $request->header('X-ALIM-API-Version', ''));

        if ($version !== '' && ! in_array($version, self::SUPPORTED_VERSIONS, true)) {
            return $this->fail(
                'Versi API tidak didukung. Gunakan X-ALIM-API-Version: 1.',
                422,
                [
                    'code' => 'unsupported_api_version',
                    'context' => [
                        'version' => mb_substr($version, 0, 32),
                        'supported' => self::SUPPORTED_VERSIONS,
                    ],
                ]
            );
        }

        if (! $request->isJson()) {
            return $this->fail(
                'Content-Type harus application/json.',
                415,
                ['code' => 'unsupported_media_type', 'context' => null]
            );
        }

        $idempotencyKey = $this->idempotencyKey($request);

        if ($idempotencyKey === null) {
            return $next($request);
        }

        $commandId = $request->input('command_id');
        $commandId = is_string($commandId) ? trim($commandId) : '';

        if ($idempotencyKey !== $commandId) {
            return $this->fail(
                'Idempotency-Key harus sama dengan command_id pada body.',
                422,
                [
                    'code' => 'idempotency_key_mismatch',
                    'context' => [
                        'idempotency_key' => mb_substr($idempotencyKey, 0, 100),
                        'command_id' => mb_substr($commandId, 0, 100),
                    ],
                ]
            );
        }

        // Diteruskan ke service untuk disimpan sebagai jejak audit.
        $request->attributes->set('connect_approval_idempotency_key', $idempotencyKey);
        $request->attributes->set('connect_approval_api_version', $version !== '' ? $version : null);

        return $next($request);
    }

    private function idempotencyKey(Request $request): ?string
    {
        $raw = $request->header('Idempotency-Key');

        if (! is_string($raw)) {
            return null;
        }

        $raw = trim($raw);

        return $raw === '' ? null : $raw;
    }

    /**
     * Dipakai controller untuk melengkapi respons agar Connect dapat mencocokkan
     * balasan dengan command yang dikirim.
     */
    public static function echoHeaders(Request $request, JsonResponse $response): JsonResponse
    {
        $key = $request->attributes->get('connect_approval_idempotency_key');

        if (is_string($key) && $key !== '') {
            $response->headers->set('Idempotency-Key', $key);
        }

        return $response;
    }
}
