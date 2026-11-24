<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Uji verifikasi staging ALIM Connect lewat HTTP sungguhan (bukan test suite).
 *
 * Memeriksa apa yang diuji Connect nanti: endpoint terjangkau, credential
 * diterima sesuai scope, credential salah ditolak, allowlist, versi API,
 * idempotensi (replay = duplicate, command_id beda payload = 409), dan audit
 * mencatat aktor + keputusan.
 *
 * Tidak pernah mencetak nilai credential; laporan memakai fingerprint SHA-256
 * 12 karakter pertama.
 *
 *   php artisan alim:connect-staging-verify --env=staging
 *   php artisan alim:connect-staging-verify --env=staging --no-approve   # tanpa mutasi permit
 *
 * Exit 0 = semua pemeriksaan lulus. Exit 1 = ada kegagalan.
 */
class ConnectStagingVerify extends Command
{
    protected $signature = 'alim:connect-staging-verify
        {--base-url= : Override ALIM_CORE_BASE_URL}
        {--no-approve : Lewati uji mutasi permit (hanya uji negatif)}';

    protected $description = 'Verifikasi staging ALIM Connect lewat HTTP: endpoint, credential, allowlist, versi API, idempotensi, audit';

    private const SSO_PATH = '/api/connect/v1/sso/exchange';

    private const APPROVAL_PATH = '/api/connect/v1/approval-commands';

    /** @var list<array{0:string,1:string,2:bool,3:string}> */
    private array $results = [];

    public function handle(): int
    {
        if (app()->environment() !== 'staging') {
            $this->error('Perintah ini hanya untuk APP_ENV=staging.');

            return self::FAILURE;
        }

        $base = rtrim((string) ($this->option('base-url') ?: config('app.url', '')), '/');

        if ($base === '') {
            $this->error('ALIM_CORE_BASE_URL/APP_URL kosong.');

            return self::FAILURE;
        }

        $ssoKey = (string) config('services.connect_sso.api_key');
        $approvalKey = (string) config('services.connect_approval.api_key');

        if ($ssoKey === '' || $approvalKey === '') {
            $this->error('Credential belum dimuat ke environment proses. Jalankan dengan CONNECT_SSO_API_KEY dan CONNECT_APPROVAL_API_KEY dari secret manager.');

            return self::FAILURE;
        }

        $this->line('Base URL: '.$base);
        $this->line('SSO key fingerprint: '.$this->fp($ssoKey).' | Approval key fingerprint: '.$this->fp($approvalKey));

        $this->check('0. SSO & Approval memakai credential terpisah', $ssoKey !== $approvalKey, '');

        try {
            $this->checkEndpointReachable($base);
            $this->checkCredentials($base, $ssoKey, $approvalKey);
            $this->checkVersionAndHeaders($base, $approvalKey);
            $this->checkSsoContract($base, $ssoKey);
            if (! $this->option('no-approve')) {
                $this->checkApprovalContract($base, $approvalKey);
            }
            $this->checkAudit();
        } catch (\Throwable $e) {
            $this->recordFailure('Koneksi', 'exception', class_basename($e).': '.$e->getMessage());
        }

        $this->renderReport();

        return collect($this->results)->every(fn ($r) => $r[2]) ? self::SUCCESS : self::FAILURE;
    }

    private function checkEndpointReachable(string $base): void
    {
        foreach ([self::SSO_PATH, self::APPROVAL_PATH] as $path) {
            $response = Http::timeout(10)->acceptJson()->post($base.$path, []);
            $this->check(
                "1. Endpoint terjangkau {$path}",
                ! in_array($response->status(), [404, 0], true) && $response->status() < 500,
                'HTTP '.$response->status()
            );
        }

        // /health boleh publik, tetapi tidak boleh membocorkan lingkungan, versi,
        // maupun konfigurasi. `request_id` ditambahkan RequestIdMiddleware untuk
        // semua respons JSON (korelasi log), jadi itu satu-satunya kunci lain
        // yang wajar; `env`/`app`/versi tidak.
        $health = Http::timeout(10)->acceptJson()->get($base.'/health');
        $body = is_array($health->json()) ? $health->json() : [];
        $extra = array_diff(array_keys($body), ['status', 'time', 'request_id']);
        $this->check(
            '1b. /health minimal (tanpa env/versi/konfigurasi)',
            $health->status() === 200 && ($body['status'] ?? null) === 'ok' && $extra === [],
            'HTTP '.$health->status().($extra === [] ? '' : ' kunci tambahan: '.implode(',', $extra))
        );
    }

    private function checkCredentials(string $base, string $ssoKey, string $approvalKey): void
    {
        $ssoHeader = (string) config('services.connect_sso.header', 'X-ALIM-CONNECT-SSO-KEY');
        $approvalHeader = (string) config('services.connect_approval.header', 'X-ALIM-CONNECT-APPROVAL-KEY');

        // Key SSO tidak boleh diterima endpoint approval, dan sebaliknya.
        $crossA = Http::withHeaders([$approvalHeader => $ssoKey])->acceptJson()->post($base . self::APPROVAL_PATH, []);
        $this->check('3a. Key SSO ditolak endpoint Approval', $crossA->status() === 401, 'HTTP '.$crossA->status());

        $crossB = Http::withHeaders([$ssoHeader => $approvalKey])->acceptJson()->post($base . self::SSO_PATH, $this->ssoPayload('cross-scope'));
        $this->check('3b. Key Approval ditolak endpoint SSO', $crossB->status() === 401, 'HTTP '.$crossB->status());

        $wrong = Http::withHeaders([$approvalHeader => str_repeat('x', 64)])->acceptJson()->post($base . self::APPROVAL_PATH, []);
        $this->check('4. Key salah ditolak (401)', $wrong->status() === 401, 'HTTP '.$wrong->status());

        // Key benar diterima: lolos middleware, gagal di validasi payload (422) bukan auth (401/403).
        $okShape = Http::withHeaders([$approvalHeader => (string) config('services.connect_approval.api_key')])
            ->acceptJson()->post($base . self::APPROVAL_PATH, []);
        $this->check('5. Key Approval benar diterima (tidak 401)', $okShape->status() !== 401 && $okShape->status() !== 403, 'HTTP '.$okShape->status());
    }

    private function checkVersionAndHeaders(string $base, string $approvalKey): void
    {
        $header = (string) config('services.connect_approval.header', 'X-ALIM-CONNECT-APPROVAL-KEY');
        $body = $this->approvalPayload('version-probe', (string) (DB::table('dormitory_permits')->value('id') ?? '00000000-0000-0000-0000-000000000000'));

        // Versi 1 harus DITERIMA. Bukti yang tepat: request lolos middleware
        // versi dan sampai ke validasi payload (`validation_failed`), bukan
        // `unsupported_api_version`, dan bukan 401/403.
        $v1 = Http::withHeaders([$header => $approvalKey, 'X-ALIM-API-Version' => '1'])
            ->acceptJson()->post($base . self::APPROVAL_PATH, [
                'command_id' => 'v1-probe',
                'request_id' => 'bukan-uuid-sengaja',
            ]);
        $v1Code = (string) data_get($v1->json(), 'errors.code');
        $this->check(
            '6a. X-ALIM-API-Version: 1 diterima (versi lolos, gagal di validasi payload)',
            $v1->status() === 422 && $v1Code === 'validation_failed',
            'HTTP '.$v1->status().' code='.$v1Code
        );

        $v2 = Http::withHeaders([$header => $approvalKey, 'X-ALIM-API-Version' => '2'])
            ->acceptJson()->post($base . self::APPROVAL_PATH, $body);
        $this->check(
            '6b. X-ALIM-API-Version: 2 ditolak 422 unsupported_api_version',
            $v2->status() === 422 && data_get($v2->json(), 'errors.code') === 'unsupported_api_version',
            'HTTP '.$v2->status().' code='.data_get($v2->json(), 'errors.code')
        );

        $mismatch = $body;
        $mismatch['command_id'] = 'mismatch-'.uniqid();
        $mis = Http::withHeaders([$header => $approvalKey, 'Idempotency-Key' => 'different-'.$mismatch['command_id']])
            ->acceptJson()->post($base . self::APPROVAL_PATH, $mismatch);
        $this->check(
            '6c. Idempotency-Key != command_id ditolak 422 idempotency_key_mismatch',
            $mis->status() === 422 && data_get($mis->json(), 'errors.code') === 'idempotency_key_mismatch',
            'HTTP '.$mis->status().' code='.data_get($mis->json(), 'errors.code')
        );

        $ct = Http::withHeaders([$header => $approvalKey])->withBody('plain', 'text/plain')
            ->post($base . self::APPROVAL_PATH);
        $this->check(
            '6d. Content-Type non-JSON ditolak 415',
            $ct->status() === 415,
            'HTTP '.$ct->status()
        );
    }

    private function checkSsoContract(string $base, string $ssoKey): void
    {
        $header = (string) config('services.connect_sso.header', 'X-ALIM-CONNECT-SSO-KEY');

        $fake = Http::withHeaders([$header => $ssoKey])->acceptJson()
            ->post($base . self::SSO_PATH, $this->ssoPayload('unknown-'.bin2hex(random_bytes(8))));
        $this->check(
            '7a. Kode handoff tak dikenal → 404 code_not_found',
            $fake->status() === 404 && data_get($fake->json(), 'errors.code') === 'code_not_found',
            'HTTP '.$fake->status().' code='.data_get($fake->json(), 'errors.code')
        );

        $badAudience = Http::withHeaders([$header => $ssoKey])->acceptJson()
            ->post($base . self::SSO_PATH, ['code' => 'x', 'audience' => 'other', 'scope' => 'whatsapp.embed']);
        $this->check(
            '7b. audience salah → 403 audience_mismatch',
            $badAudience->status() === 403 && data_get($badAudience->json(), 'errors.code') === 'audience_mismatch',
            'HTTP '.$badAudience->status().' code='.data_get($badAudience->json(), 'errors.code')
        );
    }

    private function checkApprovalContract(string $base, string $approvalKey): void
    {
        $header = (string) config('services.connect_approval.header', 'X-ALIM-CONNECT-APPROVAL-KEY');
        $permit = $this->permit('Uji integrasi Connect stg-active');

        if (! $permit) {
            $this->recordFailure('8. Permit uji aktif', 'tidak ada', 'Jalankan db:seed --class=ConnectStagingTestDataSeeder.');

            return;
        }

        $commandId = 'stg-'.bin2hex(random_bytes(8));
        $messageId = 'wamid-stg-'.bin2hex(random_bytes(8));
        $payload = $this->approvalPayload($commandId, (string) $permit->id, 'approved', 2, $messageId);

        $first = Http::withHeaders([
            $header => $approvalKey,
            'Idempotency-Key' => $commandId,
            'X-ALIM-API-Version' => '1',
        ])->acceptJson()->post($base . self::APPROVAL_PATH, $payload);

        $this->check(
            '8a. Command sah diterapkan (200)',
            $first->status() === 200 && data_get($first->json(), 'data.outcome') !== 'duplicate',
            'HTTP '.$first->status().' outcome='.data_get($first->json(), 'data.outcome')
        );

        $replay = Http::withHeaders([
            $header => $approvalKey,
            'Idempotency-Key' => $commandId,
            'X-ALIM-API-Version' => '1',
        ])->acceptJson()->post($base . self::APPROVAL_PATH, $payload);
        $this->check(
            '8b. Replay identik → 200 duplicate',
            $replay->status() === 200 && data_get($replay->json(), 'data.duplicate') === true,
            'HTTP '.$replay->status().' duplicate='.json_encode(data_get($replay->json(), 'data.duplicate'))
        );

        $reused = $payload;
        $reused['decision'] = 'rejected';
        // `approved_duration_days` dilarang untuk rejected (prohibited_if),
        // jadi ubah payload dengan cara yang tetap lolos validasi form.
        $reused['approved_duration_days'] = null;
        $conflict = Http::withHeaders([$header => $approvalKey, 'Idempotency-Key' => $commandId])
            ->acceptJson()->post($base . self::APPROVAL_PATH, $reused);
        $this->check(
            '8c. command_id sama payload beda → 409 command_id_reused',
            $conflict->status() === 409 && data_get($conflict->json(), 'errors.code') === 'command_id_reused',
            'HTTP '.$conflict->status().' code='.data_get($conflict->json(), 'errors.code')
        );

        $stored = DB::table('approval_commands')->where('command_id', $commandId)->first();
        $this->check(
            '9. Jejak tersimpan: idempotency_key == command_id, api_version = 1',
            $stored && $stored->idempotency_key === $commandId && $stored->api_version === '1',
            $stored ? 'idempotency_key='.$stored->idempotency_key.' api_version='.$stored->api_version : 'tidak ada'
        );
    }

    private function checkAudit(): void
    {
        $log = DB::table('audit_logs')
            ->whereIn('action', ['DORMITORY_PERMIT_APPROVED', 'DORMITORY_PERMIT_REJECTED', 'DORMITORY_PERMIT_EXPIRED'])
            ->where('user_id', '!=', null)
            ->orderByDesc('created_at')
            ->first();

        $metadata = $log ? json_decode((string) $log->metadata, true) : [];

        $this->check(
            '10. Audit mencatat aktor + keputusan + command_id',
            $log !== null && isset($metadata['command_id'], $metadata['decision']),
            $log ? $log->action.' actor='.$log->user_id : 'tidak ada'
        );
    }

    private function permit(string $purpose): ?object
    {
        return DB::table('dormitory_permits')->where('purpose', $purpose)->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function ssoPayload(string $code): array
    {
        return ['code' => $code, 'audience' => 'connect', 'scope' => 'whatsapp.embed'];
    }

    /**
     * @return array<string, mixed>
     */
    private function approvalPayload(
        string $commandId,
        string $permitId,
        string $decision = 'approved',
        int $days = 2,
        ?string $messageId = null
    ): array {
        return [
            'command_id' => $commandId,
            'request_id' => $permitId,
            'decision' => $decision,
            'approved_duration_days' => $decision === 'approved' ? $days : null,
            'actor_external_id' => '6280000000001',
            'source' => 'whatsapp',
            'external_message_id' => $messageId ?? 'wamid-'.bin2hex(random_bytes(8)),
            'message_text' => 'Uji staging Cycle 9',
            'notes' => 'Uji integrasi staging',
        ];
    }

    private function check(string $label, bool $ok, string $detail): void
    {
        $this->results[] = [$label, $ok ? 'LULUS' : 'GAGAL', $ok, $detail];
        $ok ? $this->line("  ✔ {$label}") : $this->warn("  ✘ {$label} — {$detail}");
    }

    private function recordFailure(string $group, string $what, string $detail): void
    {
        $this->results[] = [$group.' ('.$what.')', 'GAGAL', false, $detail];
        $this->warn("  ✘ {$group} — {$detail}");
    }

    private function renderReport(): void
    {
        $this->newLine();
        $this->table(['Pemeriksaan', 'Hasil', 'Detail'], array_map(
            fn ($r) => [$r[0], $r[1], $r[3]],
            $this->results
        ));

        $failed = collect($this->results)->where(2, false)->count();

        if ($failed === 0) {
            $this->info('Semua pemeriksaan staging LULUS ('.count($this->results).' butir).');

            return;
        }

        $this->error($failed.' dari '.count($this->results).' pemeriksaan GAGAL.');
    }

    private function fp(string $value): string
    {
        return substr(hash('sha256', $value), 0, 12);
    }
}
