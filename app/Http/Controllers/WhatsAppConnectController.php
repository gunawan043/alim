<?php

namespace App\Http\Controllers;

use App\Authorization\ValueObjects\OrganizationContext;
use App\Services\Connect\ConnectHandoffService;
use App\Support\ConnectSsoConfig;
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
        $connectSsoUrl = ConnectSsoConfig::handoffUrl();

        $notConfiguredReason = match (true) {
            $frameOrigin === null => 'CONNECT_SSO_FRAME_ORIGIN belum berisi origin Connect yang valid (https).',
            $connectSsoUrl === '' => 'CONNECT_SSO_HANDOFF_URL belum berisi URL handoff Connect.',
            default => null,
        };

        $response = response()->view('whatsapp-connect.index', [
            'userId' => $userId,
            'frameOrigin' => $frameOrigin,
            'connectSsoUrl' => $connectSsoUrl,
            'handoffUrl' => route('user.whatsapp-connect.handoff', ['userId' => $userId]),
            'configured' => $notConfiguredReason === null,
            'notConfiguredReason' => $notConfiguredReason,
        ]);

        // Route-specific CSP only. Do not weaken the global web/API policy.
        if ($frameOrigin !== null) {
            $response->headers->set('Content-Security-Policy', $this->frameCsp($response, $frameOrigin));
        }

        return $response;
    }

    /**
     * Bangun ulang kebijakan global dan ganti hanya `frame-src`.
     *
     * Sebelumnya header ini ditimpa penuh dengan `frame-src 'self' <origin>`,
     * sehingga seluruh direktif global (script-src, style-src, object-src)
     * hilang dari halaman ini — bukan pelonggaran yang dimaksud, tetapi
     * justru kehilangan proteksi bawaan sambil menambah satu izin.
     */
    private function frameCsp($response, string $frameOrigin): string
    {
        $global = (string) $response->headers->get('Content-Security-Policy', '');
        $directives = [];

        foreach (explode(';', $global) as $part) {
            $part = trim($part);

            if ($part !== '') {
                $directives[] = $part;
            }
        }

        $frameSrc = "frame-src 'self' {$frameOrigin}";
        $replaced = false;

        foreach ($directives as $i => $directive) {
            if (str_starts_with($directive, 'frame-src ')) {
                $directives[$i] = $frameSrc;
                $replaced = true;
            }
        }

        if (! $replaced) {
            $directives[] = $frameSrc;
        }

        return implode('; ', $directives);
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
        return ConnectSsoConfig::originOf(ConnectSsoConfig::frameOrigin());
    }
}
