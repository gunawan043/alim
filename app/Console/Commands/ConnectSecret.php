<?php

namespace App\Console\Commands;

use App\Support\IntegrationApiConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Pengelola secret untuk kredensial integrasi ALIM Connect (staging).
 *
 * Secret disimpan TERENKRIPSI (APP_KEY) di berkas ber-permisi 0600 di luar
 * repository (default: `~/.alim/staging/connect-secrets.enc.json`). Berkas
 * JSON itu sendiri kemudian dienkripsi dengan APP_KEY, sehingga hanya bisa
 * dibuka oleh aplikasi dengan APP_KEY yang sama.
 *
 * Perintah ini TIDAK pernah mencetak nilai secret. Nilai hanya bisa diperoleh
 * lewat `get`, yang dirancang untuk disubstitusi ke environment proses:
 *
 *   CONNECT_SSO_API_KEY="$(php artisan alim:connect-secret get sso:exchange)" \
 *     php artisan serve --env=staging
 *
 * Konsekuensi: nilai rahasia tetap hidup di environment proses `serve`/php-fpm.
 * Untuk uji lokal ini adalah pilihan pragmatis; di server staging, gunakan
 * secret manager host (vault/systemd EnvironmentFile) dengan perintah yang sama.
 */
class ConnectSecret extends Command
{
    protected $signature = 'alim:connect-secret
        {action : generate | get | rotate | status | verify | export-env}
        {scope? : sso:exchange | approval:command}
        {--file= : Lokasi berkas secret (default: ~/.alim/staging/connect-secrets.enc.json)}
        {--stdout : Hanya untuk `get`/`export-env`; tanpa ini perintah menolak mencetak secret}';

    protected $description = 'Kelola kredensial staging ALIM Connect terenkripsi tanpa mencetak nilainya';

    private const MIN_LENGTH = 32;

    /** @var array<string, string> prefix konfigurasi => scope yang wajib dimiliki */
    private const SCOPES = [
        'connect_sso' => 'sso:exchange',
        'connect_approval' => 'approval:command',
    ];

    public function handle(): int
    {
        $action = (string) $this->argument('action');

        return match ($action) {
            'generate' => $this->generate((string) $this->argument('scope')),
            'get' => $this->get((string) $this->argument('scope')),
            'rotate' => $this->rotate(),
            'status' => $this->status(),
            'verify' => $this->verify(),
            'export-env' => $this->exportEnv(),
            default => $this->invalidAction($action),
        };
    }

    private function generate(string $scope): int
    {
        if (! in_array($scope, self::SCOPES, true)) {
            return $this->unknownScope($scope);
        }

        $path = $this->secretFile();
        $secrets = $this->read($path);

        if (isset($secrets[$scope])) {
            $this->error("Scope {$scope} sudah memiliki secret. Gunakan `rotate` atau `get`.");

            return self::FAILURE;
        }

        $secrets = $this->assignNew($secrets, $scope);

        if ($this->write($path, $secrets) === false) {
            return self::FAILURE;
        }

        $this->info("Secret scope {$scope} dibuat: 64 karakter hex (256-bit), disimpan terenkripsi.");
        $this->line('Berkas: '.$path.' (permission 0600, tidak masuk repository)');
        $this->line('Nilai tidak ditampilkan. Ambil dengan: php artisan alim:connect-secret get '.$scope.' --stdout');

        return self::SUCCESS;
    }

    private function get(string $scope): int
    {
        if (! in_array($scope, self::SCOPES, true)) {
            return $this->unknownScope($scope);
        }

        $secrets = $this->read($this->secretFile());

        if (! isset($secrets[$scope])) {
            $this->error("Secret {$scope} belum ada. Jalankan `alim:connect-secret generate {$scope}`.");

            return self::FAILURE;
        }

        if (! $this->option('stdout')) {
            $this->warn('Nilai secret tidak dicetak ke terminal. Gunakan `--stdout` untuk substitusi environment.');
            $this->line('Contoh: CONNECT_SSO_API_KEY="$(php artisan alim:connect-secret get '.$scope.' --stdout)"');

            return self::SUCCESS;
        }

        // Satu-satunya jalur yang menulis nilai, dan ditujukan untuk pipe
        // (substitusi env), bukan untuk dibaca manusia.
        $this->output->write($secrets[$scope]);

        return self::SUCCESS;
    }

    private function rotate(): int
    {
        $path = $this->secretFile();
        $secrets = $this->read($path);

        if ($secrets === []) {
            $this->error('Belum ada secret untuk dirotasi. Jalankan `generate` lebih dulu.');

            return self::FAILURE;
        }

        foreach (array_keys($secrets) as $scope) {
            $secrets = $this->assignNew($secrets, $scope);
        }

        if ($this->write($path, $secrets) === false) {
            return self::FAILURE;
        }

        $this->info('Semua secret dirotasi. Samakan dengan konfigurasi Connect pada jendela rotasi yang sama.');

        return self::SUCCESS;
    }

    private function status(): int
    {
        $path = $this->secretFile();
        $secrets = $this->read($path);

        $rows = [];
        foreach (self::SCOPES as $prefix => $scope) {
            $rows[] = [
                $scope,
                isset($secrets[$scope]) ? 'ADA' : 'BELUM ADA',
                isset($secrets[$scope]) ? strlen($secrets[$scope]) : '—',
                $this->fingerprint($secrets[$scope] ?? null),
            ];
        }

        $this->table(['Scope', 'Status', 'Panjang', 'Fingerprint SHA-256 (12)'], $rows);
        $this->line('Berkas secret: '.$path);
        $this->line('Env sekarang: '.$this->envStatus());

        if (isset($secrets['sso:exchange'], $secrets['approval:command'])
            && hash_equals($secrets['sso:exchange'], $secrets['approval:command'])) {
            $this->error('SSO dan Approval memakai secret yang SAMA — wajib berbeda.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Uji bahwa nilai dari secret manager benar-benar diterima middleware
     * Core, lewat resolver yang sama (IntegrationApiConfig), tanpa HTTP.
     */
    private function verify(): int
    {
        $secrets = $this->read($this->secretFile());
        $failures = [];
        $rows = [];

        foreach (self::SCOPES as $prefix => $scope) {
            $key = $secrets[$scope] ?? null;

            if ($key === null) {
                $failures[] = "{$scope}: secret belum dibuat";
                $rows[] = [$scope, 'TIDAK ADA', '—'];

                continue;
            }

            $resolved = IntegrationApiConfig::key($prefix);
            $matched = $resolved !== '' && hash_equals($resolved, $key);
            $scopes = (array) config("services.{$prefix}.scopes", []);
            $scopeOk = in_array($scope, $scopes, true);

            if (! $matched) {
                $failures[] = "{$scope}: middleware tidak menerima secret (resolver kosong/berbeda)";
            }
            if (! $scopeOk) {
                $failures[] = "{$scope}: config services.{$prefix}.scopes tidak memuat scope ini";
            }
            if (strlen($key) < self::MIN_LENGTH) {
                $failures[] = "{$scope}: panjang < ".self::MIN_LENGTH.' karakter';
            }

            $rows[] = [$scope, $matched && $scopeOk && strlen($key) >= self::MIN_LENGTH ? 'DITERIMA' : 'DITOLAK', $this->fingerprint($key)];
        }

        $this->table(['Scope', 'Resolver middleware', 'Fingerprint'], $rows);

        if ($failures !== []) {
            $this->error('Verifikasi secret GAGAL:');
            foreach ($failures as $item) {
                $this->line('  - '.$item);
            }

            return self::FAILURE;
        }

        $this->info('Secret staging diterima middleware dengan scope yang benar.');

        return self::SUCCESS;
    }

    /**
     * Cetak baris environment untuk dipipe ke berkas sementara oleh operator.
     * Hanya untuk keperluan deploy ke secret manager host.
     */
    private function exportEnv(): int
    {
        if (! $this->option('stdout')) {
            $this->warn('Gunakan `--stdout` dan pipe ke berkas ber-permisi 0600.');

            return self::SUCCESS;
        }

        $secrets = $this->read($this->secretFile());
        $map = [
            'CONNECT_SSO_API_KEY' => 'sso:exchange',
            'CONNECT_APPROVAL_API_KEY' => 'approval:command',
        ];

        foreach ($map as $env => $scope) {
            if (! isset($secrets[$scope])) {
                $this->error("Secret {$scope} belum ada.");

                return self::FAILURE;
            }
            $this->output->writeln($env.'='.$secrets[$scope]);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $secrets
     * @return array<string, string>
     */
    private function assignNew(array $secrets, string $scope): array
    {
        do {
            $value = bin2hex(random_bytes(32));
        } while (in_array($value, $secrets, true));

        $secrets[$scope] = $value;

        return $secrets;
    }

    private function secretFile(): string
    {
        $path = (string) ($this->option('file') ?: (getenv('HOME') ?: sys_get_temp_dir()).'/.alim/staging/connect-secrets.enc.json');

        return $path;
    }

    /**
     * @return array<string, string>
     */
    private function read(string $path): array
    {
        if (! File::exists($path)) {
            return [];
        }

        try {
            $payload = Crypt::decryptString(trim(File::get($path)));
            $decoded = json_decode($payload, true);
        } catch (\Throwable) {
            $this->error('Berkas secret tidak dapat dibuka (APP_KEY berbeda atau berkas rusak).');

            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, string>  $secrets
     */
    private function write(string $path, array $secrets): bool
    {
        $dir = dirname($path);

        if (! File::isDirectory($dir) && ! File::makeDirectory($dir, 0700, true)) {
            $this->error('Tidak dapat membuat direktori secret: '.$dir);

            return false;
        }

        $payload = Crypt::encryptString(json_encode($secrets, JSON_PRETTY_PRINT));
        $tmp = $path.'.'.bin2hex(random_bytes(4)).'.tmp';

        if (File::put($tmp, $payload, true) === false) {
            $this->error('Tidak dapat menulis berkas secret.');

            return false;
        }

        chmod($tmp, 0600);

        if (! rename($tmp, $path)) {
            @unlink($tmp);
            $this->error('Tidak dapat mengganti berkas secret.');

            return false;
        }

        return true;
    }

    private function envStatus(): string
    {
        $parts = [];

        foreach (self::SCOPES as $prefix => $scope) {
            $resolved = IntegrationApiConfig::key($prefix);
            $parts[] = $scope.'='.($resolved === '' ? 'kosong' : 'terisi('.$this->fingerprint($resolved).')');
        }

        return implode(', ', $parts);
    }

    private function fingerprint(?string $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return substr(hash('sha256', $value), 0, 12);
    }

    private function unknownScope(string $scope): int
    {
        $this->error("Scope tidak dikenal: '{$scope}'. Pilih: ".implode(' | ', self::SCOPES));

        return self::FAILURE;
    }

    private function invalidAction(string $action): int
    {
        $this->error("Aksi tidak dikenal: '{$action}'");

        return self::FAILURE;
    }
}
