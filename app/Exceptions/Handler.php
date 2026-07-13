<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            //
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
}
