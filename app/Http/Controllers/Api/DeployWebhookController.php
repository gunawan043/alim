<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

class DeployWebhookController extends Controller
{
    public function github(Request $request)
    {
        $event = $request->header('X-GitHub-Event');
        $delivery = $request->header('X-GitHub-Delivery');
        $secret = config('app.deploy_webhook_secret') ?: env('DEPLOY_WEBHOOK_SECRET');

        if ($secret) {
            $signature = $request->header('X-Hub-Signature-256', '');
            $payload = $request->getContent();
            $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
            if (! hash_equals($expected, $signature)) {
                Log::warning('Deploy webhook: invalid signature', ['delivery' => $delivery]);
                return response('invalid signature', 401);
            }
        }

        if ($event !== 'push') {
            return response('ignored: ' . $event, 200);
        }

        $payload = $request->json()->all();
        $branch = str_replace('refs/heads/', '', $payload['ref'] ?? '');
        $targetBranch = config('app.deploy_branch', env('DEPLOY_BRANCH', 'main'));

        if ($branch !== $targetBranch) {
            return response('ignored branch: ' . $branch, 200);
        }

        $logFile = storage_path('logs/deploy-' . date('Ymd-His') . '.log');
        $appPath = base_path();
        $commands = [
            "cd {$appPath}",
            "git fetch origin {$targetBranch} 2>&1",
            "git reset --hard origin/{$targetBranch} 2>&1",
            "composer install --no-dev --no-interaction --prefer-dist 2>&1",
            "php artisan migrate --force 2>&1",
            "php artisan config:clear 2>&1",
            "php artisan route:clear 2>&1",
            "php artisan view:clear 2>&1",
            "php artisan cache:clear 2>&1",
            "php artisan storage:link 2>&1 || true",
            "php artisan optimize 2>&1",
            "npm run build 2>&1 || true",
        ];

        $log = "=== Deploy started at " . now() . " ===\n";
        $log .= "Event: {$event}\nDelivery: {$delivery}\nBranch: {$branch}\n\n";

        foreach ($commands as $cmd) {
            $log .= "\n$ {$cmd}\n";
            try {
                $process = Process::timeout(300)->run($cmd);
                $log .= $process->output();
                if ($process->failed()) {
                    $log .= "\nFAILED: " . $process->errorOutput();
                    file_put_contents($logFile, $log);
                    Log::error('Deploy failed', ['log' => $logFile]);
                    return response('deploy failed: ' . $cmd, 500);
                }
            } catch (\Throwable $e) {
                $log .= "\nERROR: " . $e->getMessage();
                file_put_contents($logFile, $log);
                Log::error('Deploy exception', ['error' => $e->getMessage()]);
                return response('deploy error', 500);
            }
        }

        $log .= "\n=== Deploy finished at " . now() . " ===";
        file_put_contents($logFile, $log);
        Log::info('Deploy success', ['log' => $logFile, 'delivery' => $delivery]);

        return response('deployed ' . $branch, 200);
    }
}
