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
            return $this->fail(
                $e->getMessage(),
                $e->httpStatus,
                array_merge(['code' => $e->errorCode], $e->context)
            );
        } catch (Throwable $e) {
            report($e);

            return $this->serverError($e, 'Pertukaran handoff gagal.');
        }
    }
}
