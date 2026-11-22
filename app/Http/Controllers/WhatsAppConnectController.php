<?php

namespace App\Http\Controllers;

use App\Authorization\ValueObjects\OrganizationContext;
use App\Services\Connect\ConnectHandoffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppConnectController extends Controller
{
    public function __construct(
        private readonly ConnectHandoffService $handoffs
    ) {}

    public function index(Request $request, string $userId)
    {
        $this->authorizeSelf($request, $userId);

        $frameOrigin = $this->frameOrigin();
        $connectSsoUrl = trim((string) config('services.connect_sso.handoff_url', ''));

        $response = response()->view('whatsapp-connect.index', [
            'userId' => $userId,
            'frameOrigin' => $frameOrigin,
            'connectSsoUrl' => $connectSsoUrl,
            'handoffUrl' => route('user.whatsapp-connect.handoff', ['userId' => $userId]),
            'configured' => $frameOrigin !== null && $connectSsoUrl !== '',
        ]);

        // Route-specific CSP only. Do not weaken the global web/API policy.
        if ($frameOrigin !== null) {
            $response->headers->set(
                'Content-Security-Policy',
                "frame-src 'self' {$frameOrigin}"
            );
        }

        return $response;
    }

    public function handoff(Request $request, string $userId): JsonResponse
    {
        $this->authorizeSelf($request, $userId);

        if (! canPermission(ConnectHandoffService::PERMISSION)) {
            abort(403, 'Anda tidak memiliki akses ke WhatsApp Connect.');
        }

        try {
            $context = app()->bound(OrganizationContext::class)
                ? app(OrganizationContext::class)
                : null;

            $data = $this->handoffs->issue($request->user(), $context);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Handoff berhasil dibuat.',
            ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp Connect handoff issue failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat handoff WhatsApp Connect.',
                'data' => null,
            ], 500);
        }
    }

    private function authorizeSelf(Request $request, string $userId): void
    {
        abort_unless($request->user() !== null, 401);
        abort_unless((string) $request->user()->id === (string) $userId, 403);
        abort_unless(canPermission(ConnectHandoffService::PERMISSION), 403);
    }

    private function frameOrigin(): ?string
    {
        $origin = trim((string) config('services.connect_sso.frame_origin', ''));

        if ($origin === '') {
            return null;
        }

        $parts = parse_url($origin);

        if (! $parts || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        if (! in_array($parts['scheme'], ['https', 'http'], true)) {
            return null;
        }

        if (app()->environment('production') && $parts['scheme'] !== 'https') {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host']
            .(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
