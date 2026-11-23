<?php

namespace App\Http\Controllers\Api\Connect\V1;

use App\Exceptions\ApprovalCommandException;
use App\Http\Controllers\Controller;
use App\Http\Middleware\ValidateConnectApprovalHeaders;
use App\Http\Requests\Connect\ApprovalCommandRequest;
use App\Services\Connect\ApprovalCommandService;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * POST /api/connect/v1/approval-commands
 *
 * Satu-satunya endpoint tulis untuk ALIM Connect. Bukan API tulis generik:
 * hanya keputusan izin asrama yang sudah ada, dan hanya untuk aktor yang
 * identitas WhatsApp-nya terpetakan serta berwenang sebagai Kepala Asrama.
 *
 * Aturan balasan yang dipegang (kontrak Connect §7):
 *  - 2xx hanya untuk command yang benar-benar diterapkan atau replay sah.
 *  - Kegagalan sementara (gangguan DB, dll.) dibalas 5xx, bukan 4xx, agar
 *    Connect mengulang dengan `command_id` yang sama.
 */
class ApprovalCommandController extends Controller
{
    public function __construct(
        private readonly ApprovalCommandService $commands,
    ) {}

    public function store(ApprovalCommandRequest $request): JsonResponse
    {
        try {
            $result = $this->commands->handle($request->validated(), [
                'idempotency_key' => $request->attributes->get('connect_approval_idempotency_key'),
                'api_version' => $request->attributes->get('connect_approval_api_version'),
            ]);
        } catch (ApprovalCommandException $e) {
            return ValidateConnectApprovalHeaders::echoHeaders(
                $request,
                response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => null,
                    'meta' => ['timestamp' => now()->toIso8601String()],
                    'errors' => [
                        'code' => $e->errorCode,
                        'context' => $e->context,
                    ],
                ], $e->httpStatus)
            );
        } catch (Throwable $e) {
            // Kegagalan tak terduga: transaksi sudah di-rollback. Jangan pernah
            // membocorkan detail internal ke klien layanan.
            report($e);

            return ValidateConnectApprovalHeaders::echoHeaders(
                $request,
                response()->json([
                    'success' => false,
                    'message' => 'Command tidak dapat diproses saat ini.',
                    'data' => null,
                    'meta' => ['timestamp' => now()->toIso8601String()],
                    'errors' => ['code' => 'core_error', 'context' => null],
                ], 500)
            );
        }

        return ValidateConnectApprovalHeaders::echoHeaders(
            $request,
            response()->json([
                'success' => true,
                'message' => ($result['duplicate'] ?? false)
                    ? 'Command ini sudah pernah diproses.'
                    : 'Keputusan izin tersimpan.',
                'data' => $result,
                'meta' => ['timestamp' => now()->toIso8601String()],
                'errors' => null,
            ], 200)
        );
    }
}
