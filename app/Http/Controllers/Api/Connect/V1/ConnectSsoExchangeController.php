<?php

namespace App\Http\Controllers\Api\Connect\V1;

use App\Exceptions\ConnectSsoException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Connect\ExchangeConnectHandoffRequest;
use App\Services\Connect\ConnectHandoffService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class ConnectSsoExchangeController extends Controller
{
    use ApiResponse;

    public function __invoke(
        ExchangeConnectHandoffRequest $request,
        ConnectHandoffService $handoffs
    ): JsonResponse {
        try {
            $data = $handoffs->redeem(
                (string) $request->validated('code'),
                (string) $request->validated('audience'),
                (string) $request->validated('scope'),
                $request->ip(),
                $request->userAgent()
            );

            return $this->ok($data, 'Handoff berhasil ditukar.');
        } catch (ConnectSsoException $e) {
            // Envelope sama dengan Approval Command API: Connect membaca
            // `errors.code` dan `errors.context`, bukan field sejajar.
            return $this->fail(
                $e->getMessage(),
                $e->httpStatus,
                [
                    'code' => $e->errorCode,
                    'context' => $e->context,
                ]
            );
        } catch (Throwable $e) {
            report($e);

            return $this->fail(
                'Pertukaran handoff gagal.',
                500,
                ['code' => 'core_error', 'context' => null]
            );
        }
    }
}
