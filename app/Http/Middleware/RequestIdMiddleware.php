<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID');
        if (! $requestId || ! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        $request->headers->set('X-Request-ID', $requestId);
        app()->instance('request_id', $requestId);

        Log::shareContext([
            'request_id' => $requestId,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 250),
            'endpoint' => $request->method().' '.$request->path(),
        ]);

        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('X-Request-ID', $requestId);
        }

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            if (is_array($data) && ! array_key_exists('request_id', $data)) {
                $data['request_id'] = $requestId;
                $response->setData($data);
            }
        }

        return $response;
    }
}
