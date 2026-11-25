<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ConnectApprovalConfig;
use App\Support\ConnectSsoConfig;
use Illuminate\Console\Command;

/**
 * Daftar periksa kesiapan SSO ALIM Connect, dari sisi Core.
 *
 * Rancangan: setiap baris adalah satu prasyarat yang bisa membatalkan handoff,
 * dan setiap baris menyebut **sisi mana** yang harus diperbaiki. Tanpa itu,
 * pesan "SSO belum jalan" tidak memberi tahu siapa yang harus bertindak.
 *
 * Perintah ini hanya membaca. Ia tidak menulis setting, tidak membuat key,
 * dan tidak pernah mencetak nilai rahasia — hanya ada/tidak dan panjang.
 *
 * Batas yang disadari: pemetaan `users.core_user_id` hidup di database
 * Connect, yang tidak dapat dibaca dari Core. Karena itu barisnya dilaporkan
 * sebagai "tidak dapat diperiksa dari sini", bukan ditebak "belum".
 */
class ConnectSsoReadiness extends Command
{
    protected $signature = 'alim:connect-sso-readiness
        {--json : Keluarkan hasil sebagai JSON}';

    protected $description = 'Periksa kesiapan SSO ALIM Connect dari sisi Core (baca-saja, tanpa mencetak secret)';

    private const OWNER_CORE = 'Core';
    private const OWNER_CONNECT = 'Connect';

    public function handle(): int
    {
        $checks = array_merge(
            $this->addressChecks(),
            $this->credentialChecks(),
            $this->permissionChecks(),
            $this->mappingChecks(),
        );

        if ($this->option('json')) {
            $this->line((string) json_encode($checks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->render($checks);
        }

        $blocked = array_filter($checks, fn (array $c) => $c['status'] === 'missing');

        return $blocked === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function addressChecks(): array
    {
        $origin = ConnectSsoConfig::originOf(ConnectSsoConfig::frameOrigin());
        $handoff = ConnectSsoConfig::handoffUrl();

        return [
            [
                'key' => 'CONNECT_SSO_FRAME_ORIGIN',
                'owner' => self::OWNER_CORE,
                'status' => $origin !== null ? 'ok' : 'missing',
                'detail' => $origin !== null
                    ? $origin
                    : 'belum berisi origin https yang valid',
            ],
            [
                'key' => 'CONNECT_SSO_HANDOFF_URL',
                'owner' => self::OWNER_CORE,
                'status' => $this->isHttpsUrl($handoff) ? 'ok' : 'missing',
                'detail' => $handoff !== '' ? $handoff : 'belum diisi',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function credentialChecks(): array
    {
        $ssoKey = ConnectSsoConfig::hasStoredKey();
        $approvalKey = ConnectApprovalConfig::hasStoredKey();

        return [
            [
                'key' => 'CONNECT_SSO_API_KEY (Core)',
                'owner' => self::OWNER_CORE,
                'status' => $ssoKey ? 'ok' : 'missing',
                'detail' => $ssoKey
                    ? 'tersimpan; nilai harus sama dengan ALIM_CONNECT_SSO_KEY di Connect'
                    : 'belum ada key — pasang di Pengaturan Sistem → WhatsApp Connect',
            ],
            [
                'key' => 'CONNECT_APPROVAL_API_KEY (Core)',
                'owner' => self::OWNER_CORE,
                'status' => $approvalKey ? 'ok' : 'optional',
                'detail' => $approvalKey
                    ? 'tersimpan'
                    : 'belum ada; hanya diperlukan untuk perintah persetujuan, bukan untuk SSO',
            ],
            [
                'key' => 'CONNECT_SSO_SCOPES',
                'owner' => self::OWNER_CORE,
                'status' => in_array('sso:exchange', ConnectSsoConfig::scopes(), true) ? 'ok' : 'missing',
                'detail' => implode(',', ConnectSsoConfig::scopes()) ?: '(kosong)',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function permissionChecks(): array
    {
        $count = User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('roles.permissions', fn ($p) => $p->where('name', 'whatsapp_connect_view'))
                    ->orWhereHas('permissions', fn ($p) => $p->where('name', 'whatsapp_connect_view'));
            })
            ->count();

        return [
            [
                'key' => 'Pengguna ber-izin whatsapp_connect_view',
                'owner' => self::OWNER_CORE,
                'status' => $count > 0 ? 'ok' : 'missing',
                'detail' => $count > 0
                    ? "{$count} pengguna aktif"
                    : 'belum ada; halaman WhatsApp Connect akan dialihkan sebelum sempat dirender',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mappingChecks(): array
    {
        return [
            [
                'key' => 'users.core_user_id (pemetaan akun)',
                'owner' => self::OWNER_CONNECT,
                'status' => 'unverifiable',
                'detail' => 'hidup di database Connect; tidak dapat dibaca dari Core. '
                    .'Isi lewat POST /api/auth/users atau PATCH /api/auth/users/:id '
                    .'dengan kredensial ADMIN. Tanpa pemetaan, handoff berhenti di '
                    .'403 identity_not_mapped meski semua key benar.',
            ],
            [
                'key' => 'ALIM_CORE_BASE_URL / ALIM_CORE_WEB_ORIGIN',
                'owner' => self::OWNER_CONNECT,
                'status' => 'unverifiable',
                'detail' => 'environment Connect. ALIM_CORE_WEB_ORIGIN harus sama dengan '
                    .'CONNECT_SSO_FRAME_ORIGIN di Core, agar Connect mengizinkan origin iframe.',
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $checks
     */
    private function render(array $checks): void
    {
        $this->newLine();
        $this->line('  Kesiapan SSO ALIM Connect (dibaca dari Core)');
        $this->newLine();

        foreach ($checks as $c) {
            // Lebar badge disamakan (7) agar kolom nama tetap sejajar; ansi
            // tidak dihitung sprintf, jadi ditambahkan sebagai string utuh.
            $badge = match ($c['status']) {
                'ok' => '<fg=green>SIAP   </>',
                'missing' => '<fg=red>KURANG </>',
                'optional' => '<fg=yellow>OPSIONL</>',
                default => '<fg=gray>?      </>',
            };

            $this->line(sprintf(
                '  %s  %-42s <fg=gray>[%s]</> %s',
                $badge,
                substr($c['key'], 0, 42),
                $c['owner'],
                $c['detail']
            ));
        }

        $missing = array_values(array_filter($checks, fn ($c) => $c['status'] === 'missing'));

        $this->newLine();

        if ($missing === []) {
            $this->line('  <fg=green>Tidak ada prasyarat Core yang kurang.</>');
            $this->line('  Barrier yang tersisa ada di Connect: ALIM_CONNECT_SSO_KEY,');
            $this->line('  ALIM_CORE_BASE_URL, ALIM_CORE_WEB_ORIGIN, dan users.core_user_id.');
        } else {
            $this->line('  <fg=red>'.count($missing).' prasyarat belum terpenuhi.</>');
            $this->line('  Perbaiki baris berstatus KURANG di atas sebelum menyalakan iframe.');
        }

        $this->newLine();
        $this->line('  Tidak ada nilai rahasia yang ditampilkan oleh perintah ini.');
        $this->newLine();
    }

    private function isHttpsUrl(string $url): bool
    {
        return $url !== '' && str_starts_with(strtolower($url), 'https://');
    }
}
