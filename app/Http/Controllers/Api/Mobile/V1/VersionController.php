<?php

namespace App\Http\Controllers\Api\Mobile\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VersionController extends Controller
{
    private const APP_VERSION = '1.0.0';

    private const MINIMUM_SUPPORTED = '1.0.0';

    private const FORCE_UPDATE_BELOW = '1.0.0';

    public function version(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'version' => self::APP_VERSION,
                'build' => $this->buildNumber(),
                'minimum_supported' => self::MINIMUM_SUPPORTED,
                'force_update' => version_compare($this->clientVersion(), self::FORCE_UPDATE_BELOW, '<'),
            ],
        ]);
    }

    public function build(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'git_commit' => $this->gitCommit(),
                'build_time' => $this->buildTime(),
                'laravel_version' => $this->laravelVersion(),
                'php_version' => PHP_VERSION,
                'app_version' => self::APP_VERSION,
            ],
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'app_version' => self::APP_VERSION,
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'queue' => $this->checkQueue(),
                'storage' => $this->checkStorage(),
            ],
        ]);
    }

    public function health(): JsonResponse
    {
        $db = $this->checkDatabase();

        return response()->json([
            'success' => $db['healthy'],
            'data' => [
                'status' => $db['healthy'] ? 'ok' : 'degraded',
                'checks' => [
                    'database' => $db,
                ],
            ],
        ], $db['healthy'] ? 200 : 503);
    }

    private function buildNumber(): string
    {
        $commit = $this->gitCommit();
        if ($commit !== 'unknown') {
            return substr($commit, 0, 7);
        }

        return (string) (int) (microtime(true) - strtotime('2026-01-01'));
    }

    private function clientVersion(): string
    {
        return request()->header('X-App-Version', request()->input('version', self::APP_VERSION));
    }

    private function gitCommit(): string
    {
        $file = base_path('REVISION');
        if (is_file($file)) {
            $commit = trim((string) @file_get_contents($file));
            if ($commit !== '') {
                return substr($commit, 0, 40);
            }
        }

        $head = base_path('.git/HEAD');
        if (is_file($head)) {
            $ref = trim((string) @file_get_contents($head));
            if (str_starts_with($ref, 'ref:')) {
                $refPath = base_path('.git/'.trim(substr($ref, 4)));
                if (is_file($refPath)) {
                    return trim((string) @file_get_contents($refPath));
                }
            }

            return $ref;
        }

        return 'unknown';
    }

    private function buildTime(): string
    {
        $cacheKey = 'app.build_time';

        return Cache::rememberForever($cacheKey, function () {
            try {
                if (defined('LARAVEL_START')) {
                    return date(DATE_ATOM, (int) LARAVEL_START);
                }
            } catch (\Throwable $e) {
                // fall through
            }

            return date(DATE_ATOM);
        });
    }

    private function laravelVersion(): string
    {
        $app = $this->getApp();

        return method_exists($app, 'version') ? (string) $app->version() : 'unknown';
    }

    private function getApp()
    {
        return function_exists('app') ? app() : \Illuminate\Container\Container::getInstance();
    }

    private function checkDatabase(): array
    {
        $startedAt = microtime(true);
        try {
            DB::connection()->select('SELECT 1 AS health');
            $latency = round((microtime(true) - $startedAt) * 1000, 2);

            return [
                'healthy' => true,
                'driver' => DB::connection()->getDriverName(),
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            Log::warning('Health check: database query failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return [
                'healthy' => false,
                'driver' => config('database.default'),
                'error' => config('app.debug') ? $e->getMessage() : 'database unreachable',
            ];
        }
    }

    private function checkCache(): array
    {
        $startedAt = microtime(true);
        try {
            $key = '__health__'.uniqid();
            Cache::put($key, '1', 5);
            $read = Cache::get($key);
            Cache::forget($key);
            $latency = round((microtime(true) - $startedAt) * 1000, 2);

            return [
                'healthy' => $read === '1',
                'driver' => config('cache.default'),
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'driver' => config('cache.default'),
                'error' => config('app.debug') ? $e->getMessage() : 'cache unreachable',
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            $connection = config('queue.default');

            return [
                'healthy' => true,
                'driver' => $connection,
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'driver' => config('queue.default'),
                'error' => config('app.debug') ? $e->getMessage() : 'queue unreachable',
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk('local');
            $latencyStart = microtime(true);
            $disk->put('__health__.txt', '1');
            $read = $disk->get('__health__.txt');
            $disk->delete('__health__.txt');
            $latency = round((microtime(true) - $latencyStart) * 1000, 2);

            return [
                'healthy' => $read === '1',
                'disk' => 'local',
                'latency_ms' => $latency,
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'disk' => 'local',
                'error' => config('app.debug') ? $e->getMessage() : 'storage unreachable',
            ];
        }
    }
}
