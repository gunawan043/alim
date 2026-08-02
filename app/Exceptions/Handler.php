<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        ModelNotFoundException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            $this->logException($e);
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->wantsJson() && ! $request->ajax()) {
                return null;
            }

            $requestId = $this->resolveRequestId($request);
            $status = $this->resolveStatus($e);

            $payload = [
                'success' => false,
                'request_id' => $requestId,
                'error' => [
                    'message' => $this->safeMessage($e),
                    'type' => class_basename($e),
                ],
            ];

            if ($e instanceof ValidationException) {
                $payload['error']['message'] = 'Validasi gagal.';
                $payload['error']['errors'] = $e->errors();
                $status = 422;
            }

            if ($e instanceof AuthenticationException) {
                $payload['error']['message'] = 'Tidak terautentikasi.';
                $status = 401;
            }

            return response()->json($payload, $status);
        });

        // Render ModelNotFoundException as safe JSON for AJAX/jXHR/API requests.
        // Prevents any internal data (model class, attributes, file/line) from leaking
        // into the response body or stack traces.
        $this->renderable(function (ModelNotFoundException $e, $request) {
            // Generate a correlation ID for log tracing without exposing model info.
            $refId = (string) Str::uuid();

            // Internal diagnostic log — retains full stack trace and context.
            Log::error('ModelNotFoundException caught', [
                'reference_id' => $refId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_uri' => $request->fullUrl(),
                'request_method' => $request->method(),
            ]);

            // Build safe JSON response — only error message and reference UUID.
            $safeMessage = 'Resource tidak ditemukan.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'error' => $safeMessage,
                    'reference' => $refId,
                ], 404);
            }

            // For non-AJAX blade requests, fall through to Laravel's default 404 view.
            // No model details leak into the HTML response.
            return null;
        });
    }

    protected function logException(Throwable $e): void
    {
        if (! $this->shouldReport($e)) {
            return;
        }

        $request = request();
        $requestId = $this->resolveRequestId($request);

        $context = [
            'request_id' => $requestId,
            'user_id' => Auth::id(),
            'school_id' => optional(Auth::user())->school_id ?? optional(Auth::user())->active_school_id,
            'endpoint' => $request ? $request->method().' '.$request->path() : null,
            'ip' => $request ? $request->ip() : null,
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 250) : null,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        Log::error('Unhandled exception', $context);
    }

    protected function resolveRequestId(?Request $request): string
    {
        if (! $request) {
            return (string) Str::uuid();
        }

        $headerId = $request->header('X-Request-ID');
        if ($headerId && Str::isUuid($headerId)) {
            return (string) $headerId;
        }

        $containerId = app()->bound('request_id') ? app('request_id') : null;
        if ($containerId && Str::isUuid($containerId)) {
            return (string) $containerId;
        }

        return (string) Str::uuid();
    }

    protected function resolveStatus(Throwable $e): int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return 500;
    }

    protected function safeMessage(Throwable $e): string
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getMessage() ?: 'Terjadi kesalahan.';
        }

        return config('app.debug')
            ? $e->getMessage()
            : 'Terjadi kesalahan internal.';
    }
}
