<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Validation\ValidationException;

trait ApiResponse
{
    protected function ok(mixed $data = null, string $message = 'OK', int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge($this->defaultMeta(), $meta),
            'errors' => null,
        ], $status);
    }

    protected function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    protected function noContent(string $message = 'No Content'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => null,
            'meta' => $this->defaultMeta(),
            'errors' => null,
        ], 204);
    }

    protected function fail(string $message = 'Error', int $status = 400, mixed $errors = null, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => array_merge($this->defaultMeta(), $meta),
            'errors' => $errors,
        ], $status);
    }

    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->fail($message, 401);
    }

    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->fail($message, 403);
    }

    protected function notFound(string $message = 'Not Found'): JsonResponse
    {
        return $this->fail($message, 404);
    }

    protected function validationError(ValidationException $e): JsonResponse
    {
        return $this->fail(
            'The given data was invalid.',
            422,
            $e->errors()
        );
    }

    protected function fromValidationException(ValidationException $e): JsonResponse
    {
        return $this->validationError($e);
    }

    protected function serverError(\Throwable $e, string $message = 'Server Error'): JsonResponse
    {
        $payload = app()->environment('production') ? null : [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        return $this->fail($message, 500, $payload);
    }

    protected function paginated(LengthAwarePaginator $paginator, ?string $resource = null, string $message = 'OK'): JsonResponse
    {
        $items = $paginator->items();

        if ($resource && is_subclass_of($resource, JsonResource::class)) {
            $items = $resource::collection(collect($items))->resolve();
        }

        return $this->ok($items, $message, 200, [
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    protected function defaultMeta(): array
    {
        return [
            'request_id' => request()?->headers->get('X-Request-Id') ?? bin2hex(random_bytes(8)),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
