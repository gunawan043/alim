<?php

namespace App\Http\Controllers\Api\Connect\V1;

use App\Exceptions\ApprovalCommandException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Connect\ApprovalCommandRequest;
use App\Services\Connect\ApprovalCommandService;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/connect/v1/approval-commands
 *
 * Satu-satunya endpoint tulis untuk ALIM Connect. Bukan API tulis generik:
 * hanya keputusan izin asrama yang sudah ada, dan hanya untuk aktor yang
 * identitas WhatsApp-nya terpetakan serta berwenang sebagai Kepala Asrama.
 */
class ApprovalCommandController extends Controller
{
    public function __construct(
        private readonly ApprovalCommandService $commands,
    ) {}

    public function store(ApprovalCommandRequest $request): JsonResponse
    {
        try {
            $result = $this->commands->handle($request->validated());
        } catch (ApprovalCommandException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $e->context === [] ? null : $e->context,
                'meta' => ['timestamp' => now()->toIso8601String()],
                'errors' => [
                    'code' => $e->errorCode,
                    'context' => $e->context,
                ],
            ], $e->httpStatus);
        }

        return response()->json([
            'success' => true,
            'message' => ($result['duplicate'] ?? false)
                ? 'Command ini sudah pernah diproses.'
                : 'Keputusan izin tersimpan.',
            'data' => $result,
            'meta' => ['timestamp' => now()->toIso8601String()],
            'errors' => null,
        ], 200);
    }
}
