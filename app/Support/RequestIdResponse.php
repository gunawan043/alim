<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RequestIdResponse
{
    public static function inject(JsonResponse $response): JsonResponse
    {
        $requestId = app()->bound('request_id')
            ? app('request_id')
            : request()->header('X-Request-ID');

        if (! $requestId) {
            $requestId = (string) Str::uuid();
        }

        $requestId = (string) $requestId;
        $response->headers->set('X-Request-ID', $requestId);

        $data = $response->getData(true);
        if (is_array($data) && ! array_key_exists('request_id', $data)) {
            $data['request_id'] = $requestId;
            $response->setData($data);
        }

        return $response;
    }
}
