<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class DeployController extends Controller
{
    /**
     * Endpoint webhook untuk auto-deploy.
     *
     * URL: POST /webhook/deploy
     *
     * Konfigurasi GitHub Webhook:
     *   - Payload URL: https://<domain>/webhook/deploy
     *   - Content type: application/json
     *   - Secret: <isi DEPLOY_SECRET di .env>
     *   - SSL verification: enabled
     *   - Trigger: "Just the push event"
     */
    public function handle(Request $request)
    {
        $secret = config('deploy.secret');
        $branch = config('deploy.branch', 'main');
        $scriptPath = config('deploy.script_path');

        // 1. Validasi secret sudah dikonfigurasi
        if (empty($secret)) {
            Log::error('[DEPLOY] DEPLOY_SECRET belum diset di .env');
            return response()->json(['error' => 'Deploy belum dikonfigurasi.'], 500);
        }

        // 2. Validasi signature GitHub (X-Hub-Signature-256: sha256=<hmac>)
        $signature = $request->header('X-Hub-Signature-256');
        if (!$signature) {
            Log::warning('[DEPLOY] Request tanpa signature ditolak', [
                'ip' => $request->ip(),
                'ua' => $request->userAgent(),
            ]);
            return response()->json(['error' => 'Missing signature.'], 401);
        }

        $payload = $request->getContent();
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected, $signature)) {
            Log::warning('[DEPLOY] Signature tidak valid', [
                'ip' => $request->ip(),
                'expected_prefix' => substr($expected, 0, 20) . '...',
                'got_prefix' => substr($signature, 0, 20) . '...',
            ]);
            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        // 3. Parse payload & validasi branch
        $data = json_decode($payload, true) ?? [];
        $ref = $data['ref'] ?? null;            // e.g. "refs/heads/main"
        $pushedBranch = $ref ? str_replace('refs/heads/', '', $ref) : null;

        if ($pushedBranch !== $branch) {
            Log::info('[DEPLOY] Abaikan push ke branch lain', [
                'pushed' => $pushedBranch,
                'watched' => $branch,
            ]);
            return response()->json([
                'message' => "Ignored: push to {$pushedBranch}, watching {$branch}.",
            ], 200);
        }

        // 4. Validasi script tersedia
        if (!is_file($scriptPath)) {
            Log::error('[DEPLOY] Script tidak ditemukan', ['path' => $scriptPath]);
            return response()->json(['error' => "Script tidak ditemukan: {$scriptPath}"], 500);
        }

        // 5. Jalankan deploy script di background (non-blocking)
        $logFile = storage_path('logs/deploy-' . date('Y-m-d') . '.log');
        $logHandle = fopen($logFile, 'a');

        $commitId = $data['head_commit']['id'] ?? 'unknown';
        $commitMsg = $data['head_commit']['message'] ?? '';
        $pusher = $data['pusher']['name'] ?? 'unknown';

        fwrite($logHandle, "\n" . str_repeat('=', 60) . "\n");
        fwrite($logHandle, "[" . date('Y-m-d H:i:s') . "] DEPLOY DIMULAI\n");
        fwrite($logHandle, "Commit : {$commitId}\n");
        fwrite($logHandle, "Msg    : {$commitMsg}\n");
        fwrite($logHandle, "Pusher : {$pusher}\n");
        fwrite($logHandle, str_repeat('=', 60) . "\n");
        fclose($logHandle);

        // exec dengan nohup agar tidak tergantung request lifecycle
        $cmd = sprintf(
            'nohup bash %s >> %s 2>&1 &',
            escapeshellarg($scriptPath),
            escapeshellarg($logFile)
        );
        exec($cmd);

        Log::info('[DEPLOY] Deploy script dijalankan', [
            'commit' => substr($commitId, 0, 7),
            'log' => $logFile,
        ]);

        return response()->json([
            'status' => 'queued',
            'message' => 'Deploy sedang berjalan di background.',
            'commit' => substr($commitId, 0, 7),
            'log' => $logFile,
        ], 202);
    }

    /**
     * Health check endpoint (untuk monitoring & verifikasi webhook hidup).
     */
    public function health()
    {
        return response()->json([
            'status' => 'ok',
            'time' => now()->toIso8601String(),
            'app' => config('app.name'),
            'env' => app()->environment(),
        ]);
    }
}
