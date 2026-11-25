<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\ConnectActorIdentity;
use App\Models\User;
use App\Services\Boarding\DormitoryPolicyAccess;
use App\Services\Connect\ConnectHandoffService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Kelola pemetaan identitas WhatsApp → personel Core untuk command persetujuan
 * ALIM Connect.
 *
 * Sebelum command ini ada, pemetaan production hanya bisa dibuat lewat tinker.
 * Jalur itu tidak punya validasi format, tidak memeriksa kewenangan, dan tidak
 * meninggalkan jejak audit — padahal baris di `connect_actor_identities` adalah
 * SATU-SATUNYA hal yang menentukan siapa yang boleh menyetujui izin lewat
 * WhatsApp (lihat ApprovalCommandService::resolveActor()).
 *
 * Kontrak yang dijaga command ini, sama dengan yang dibaca ApprovalCommandService:
 *  - `channel` selalu `whatsapp`;
 *  - `external_identity` digit E.164 tanpa `+` (dinormalisasi lewat model yang sama);
 *  - hanya baris `is_active` + `verified_at` terisi yang dianggap aktor;
 *  - aktor harus Kepala Asrama aktif pada asrama yang diuji
 *    (DormitoryPolicyAccess::canApprovePermitCommand()).
 *
 * Pengaman:
 *  - menolak berjalan di APP_ENV=production tanpa --allow-production;
 *  - operasi yang mengubah data butuh --force (atau jawaban konfirmasi);
 *  - tidak mencetak nomor lengkap, tidak menulis nomor ke log/aplikasi;
 *  - setiap mutasi menulis baris AuditLog.
 *
 *   php artisan alim:connect-actor-map --inspect
 *   php artisan alim:connect-actor-map --inspect --user=<uuid>
 *   php artisan alim:connect-actor-map --create --number=628xxxx --user=<uuid> --dormitory=<uuid> --force
 *   php artisan alim:connect-actor-map --update --number=628xxxx --dormitory=<uuid> --force
 *   php artisan alim:connect-actor-map --revoke --number=628xxxx --force
 */
class ConnectActorMap extends Command
{
    protected $signature = 'alim:connect-actor-map
        {--create : Buat pemetaan baru (identitas belum terdaftar)}
        {--update : Ubah pemetaan yang sudah ada (personel / tampilan / status)}
        {--inspect : Tampilkan pemetaan yang ada (baca-saja)}
        {--revoke : Nonaktifkan pemetaan (baris tetap ada sebagai jejak)}
        {--number= : Nomor WhatsApp; digit E.164 tanpa "+", mis. 6281234567890}
        {--user= : UUID user Core tujuan (wajib untuk --create)}
        {--dormitory= : UUID asrama untuk memverifikasi kewenangan Kepala Asrama}
        {--display-name= : Nama tampilan opsional}
        {--operator= : Email operator yang menjalankan (diverifikasi, dicatat di audit)}
        {--allow-production : Izinkan berjalan di APP_ENV=production}
        {--force : Jalankan tanpa permintaan konfirmasi interaktif}';

    protected $description = 'Kelola pemetaan identitas WhatsApp ke personel Core untuk persetujuan ALIM Connect';

    public const AUDIT_TABLE = 'connect_actor_identities';

    public function handle(DormitoryPolicyAccess $access): int
    {
        if (! $this->environmentAllowed()) {
            return self::FAILURE;
        }

        $modes = array_filter([
            'inspect' => (bool) $this->option('inspect'),
            'create' => (bool) $this->option('create'),
            'update' => (bool) $this->option('update'),
            'revoke' => (bool) $this->option('revoke'),
        ]);

        if (count($modes) !== 1) {
            $this->error('Pilih tepat satu mode: --inspect, --create, --update, atau --revoke.');

            return self::INVALID;
        }

        $mode = (string) array_key_first($modes);

        try {
            return $mode === 'inspect'
                ? $this->inspect()
                : $this->mutate($mode, $access);
        } catch (Throwable $e) {
            // Jangan bocorkan isi exception mentah: bisa memuat nomor atau SQL.
            $this->error('Gagal: '.class_basename($e).'. Lihat log aplikasi untuk detail.');

            return self::FAILURE;
        }
    }

    private function environmentAllowed(): bool
    {
        if (app()->environment('production') && ! $this->option('allow-production')) {
            $this->error('Perintah ini menolak berjalan di APP_ENV=production tanpa --allow-production.');
            $this->line('Konfirmasi eksplisit diperlukan karena setiap perubahan menentukan siapa yang boleh menyetujui izin.');

            return false;
        }

        return true;
    }

    // ── Inspect (baca-saja) ────────────────────────────────────────────────

    private function inspect(): int
    {
        $query = ConnectActorIdentity::query()->where('channel', 'whatsapp')->orderBy('created_at');

        if ($email = $this->option('user')) {
            $user = User::query()->where('email', $email)->first();

            if (! $user) {
                $this->error('User tidak ditemukan untuk --user tersebut.');

                return self::INVALID;
            }

            $query->where('user_id', $user->id);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->info('Belum ada pemetaan identitas Whatsapp.');

            return self::SUCCESS;
        }

        $rows->each(fn (ConnectActorIdentity $identity) => $this->describe($identity));

        return self::SUCCESS;
    }

    private function describe(ConnectActorIdentity $identity): void
    {
        $user = User::query()->find($identity->user_id);

        $this->line(sprintf(
            'aktif=%s  verified=%s  nomor=…%s  user=%s  nama=%s',
            $identity->is_active ? 'ya' : 'TIDAK',
            $identity->verified_at ? 'ya' : 'TIDAK',
            $this->mask($identity->external_identity),
            $identity->user_id,
            $user?->name ?? '(user terhapus)'
        ));

        if (! $identity->is_active || ! $identity->verified_at) {
            $this->warn('  → baris ini TIDAK dipakai ApprovalCommandService (butuh is_active + verified_at).');
        }
    }

    // ── Mutasi ─────────────────────────────────────────────────────────────

    private function mutate(string $mode, DormitoryPolicyAccess $access): int
    {
        $identity = $this->normalizedNumber();

        if ($identity === null) {
            return self::INVALID;
        }

        $existing = ConnectActorIdentity::query()
            ->where('channel', 'whatsapp')
            ->where('external_identity', $identity)
            ->first();

        if ($mode === 'create' && $existing) {
            $this->error('Nomor ini sudah dipetakan. Gunakan --update, atau --revoke lalu --create.');
            $this->line('Mencegah pemetaan ganda: unique (channel, external_identity).');

            return self::FAILURE;
        }

        if ($mode !== 'create' && ! $existing) {
            $this->error('Nomor ini belum dipetakan. Gunakan --create.');

            return self::FAILURE;
        }

        if ($mode === 'revoke') {
            return $this->revoke($existing);
        }

        // Setiap mutasi wajib punya operator yang bisa dicatat di audit.
        if (! $this->requireOperator()) {
            return self::INVALID;
        }

        // target user
        $userId = $this->option('user') ?: $existing?->user_id;

        if (! $userId) {
            $this->error('--user wajib untuk --create.');

            return self::INVALID;
        }

        $user = User::query()->find($userId);

        if (! $user) {
            $this->error('User Core tidak ditemukan.');

            return self::INVALID;
        }

        if (! $user->is_active) {
            $this->error('User Core nonaktif; ApprovalCommandService akan menolak aktor ini (actor_inactive).');

            return self::FAILURE;
        }

        // Tanpa bypass RBAC: personel yang dipetakan tetap harus lolos aturan
        // otorisasi yang sama persis dengan yang dipakai saat command masuk.
        $dormitoryId = (string) ($this->option('dormitory') ?? '');

        if ($dormitoryId !== '') {
            if (! Str::isUuid($dormitoryId)) {
                $this->error('--dormitory harus UUID asrama.');

                return self::INVALID;
            }

            if (! $access->canApprovePermitCommand($user, $dormitoryId)) {
                $this->error('User ini bukan Kepala Asrama aktif pada asrama tersebut.');
                $this->line('Command akan ditolak ApprovalCommandService dengan actor_not_authorized.');

                return self::FAILURE;
            }
        } else {
            $this->warn('Tanpa --dormitory, kewenangan Kepala Asrama TIDAK diverifikasi.');
        }

        if ($mode === 'update' && $existing && $existing->user_id !== $user->id) {
            $this->warn(sprintf(
                'Mengganti personel: %s → %s. Jejak audit tetap mencatat keduanya.',
                $existing->user_id,
                $user->id
            ));
        }

        if (! $this->confirmChange($mode, $identity, $user, $existing)) {
            $this->info('Dibatalkan; tidak ada perubahan.');

            return self::SUCCESS;
        }

        $operatorId = $this->operatorId();
        $before = $existing?->only(['user_id', 'display_name', 'is_active', 'verified_at']);

        $row = DB::transaction(function () use ($existing, $user, $identity, $operatorId) {
            $attributes = [
                'channel' => 'whatsapp',
                'external_identity' => $identity,
                'user_id' => $user->id,
                'display_name' => $this->option('display-name') ?: ($existing?->display_name),
                'is_active' => true,
                'verified_at' => $existing?->verified_at ?? now(),
                'verified_by' => $existing?->verified_by ?? $operatorId,
            ];

            if ($existing) {
                $existing->fill($attributes)->save();

                return $existing->refresh();
            }

            return ConnectActorIdentity::create($attributes);
        });

        $this->audit($mode === 'create' ? 'CONNECT_ACTOR_IDENTITY_MAPPED' : 'CONNECT_ACTOR_IDENTITY_UPDATED', $row, [
            'mode' => $mode,
            'number_masked' => $this->mask($identity),
            'number_fingerprint' => hash('sha256', $identity),
            'before' => $before,
            'after' => $row->only(['user_id', 'display_name', 'is_active', 'verified_at']),
            'operator_user_id' => $operatorId,
        ]);

        $this->info('Pemetaan tersimpan.');
        $this->describe($row);

        return self::SUCCESS;
    }

    private function revoke(ConnectActorIdentity $identity): int
    {
        if (! $this->requireOperator()) {
            return self::INVALID;
        }

        if (! $identity->is_active) {
            $this->info('Pemetaan ini sudah nonaktif; tidak ada yang diubah.');

            return self::SUCCESS;
        }

        if (! $this->confirmChange('revoke', $identity->external_identity, null, $identity)) {
            $this->info('Dibatalkan; tidak ada perubahan.');

            return self::SUCCESS;
        }

        $operatorId = $this->operatorId();

        DB::transaction(fn () => $identity->forceFill(['is_active' => false])->save());

        $this->audit('CONNECT_ACTOR_IDENTITY_REVOKED', $identity, [
            'number_masked' => $this->mask($identity->external_identity),
            'number_fingerprint' => hash('sha256', $identity->external_identity),
            'operator_user_id' => $operatorId,
        ]);

        $this->info('Pemetaan dinonaktifkan. Baris dipertahankan sebagai jejak; command approval dari nomor ini akan ditolak.');

        return self::SUCCESS;
    }

    // ── Bantuan ────────────────────────────────────────────────────────────

    /**
     * Normalisasi lewat model yang sama dengan jalur runtime, lalu tolak bentuk
     * yang tidak E.164. `0812…` dan `+62…` diterima karena dinormalisasi ke
     * bentuk yang identik dengan yang dikirim Connect.
     */
    private function normalizedNumber(): ?string
    {
        $raw = trim((string) $this->option('number'));

        if ($raw === '') {
            $this->error('--number wajib diisi.');

            return null;
        }

        $normalized = ConnectActorIdentity::normalizeIdentity($raw);

        // E.164: kode negara 1–9 di depan, total 8–15 digit, tidak diawali 0.
        if (! preg_match('/^[1-9][0-9]{7,14}$/', $normalized)) {
            $this->error('Nomor tidak berbentuk E.164 setelah normalisasi (8–15 digit, tanpa "+").');

            return null;
        }

        if ($raw !== $normalized) {
            $this->line('Nomor dinormalisasi ke bentuk E.164 (digit saja).');
        }

        return $normalized;
    }

    private function confirmChange(string $mode, string $number, ?User $user, ?ConnectActorIdentity $existing): bool
    {
        if ($this->option('force')) {
            return true;
        }

        $this->line('Ringkasan perubahan:');
        $this->line('  mode      : '.$mode);
        $this->line('  nomor     : …'.$this->mask($number));

        if ($user) {
            $this->line('  personel  : '.$user->name.' ('.$user->id.')');
        }

        if ($mode === 'update' && $existing) {
            $this->line('  sebelumnya: '.$existing->user_id);
        }

        if (! $this->input->isInteractive()) {
            $this->error('Mode non-interaktif: tambahkan --force untuk menyetujui.');

            return false;
        }

        return $this->confirm('Lanjutkan?', false);
    }

    /**
     * Operator CLI tidak punya sesi login. Audit tetap harus menyebut manusia
     * yang menjalankan, jadi --operator wajib untuk setiap mutasi dan harus
     * menunjuk user Core aktif. Tidak ada fallback ke auth()->id(): pada CLI
     * nilainya null dan audit akan kehilangan jejak siapa yang memetakan.
     */
    private function requireOperator(): bool
    {
        if ($this->operatorId() !== null) {
            return true;
        }

        $this->error('--operator wajib diisi dengan email user Core aktif yang menjalankan perintah ini.');
        $this->line('Tanpa itu, audit tidak bisa menyebut siapa yang mengubah kewenangan persetujuan.');

        return false;
    }

    private function operatorId(): ?string
    {
        $email = trim((string) $this->option('operator'));

        if ($email === '') {
            return null;
        }

        $operator = User::query()->where('email', $email)->first();

        if (! $operator || ! $operator->is_active) {
            return null;
        }

        return (string) $operator->id;
    }

    private function audit(string $action, ConnectActorIdentity $identity, array $metadata): void
    {
        AuditLog::create([
            'user_id' => $this->operatorId(),
            'action' => $action,
            'table_name' => self::AUDIT_TABLE,
            'record_id' => $identity->id,
            'ip_address' => null,
            'user_agent' => 'artisan alim:connect-actor-map',
            'metadata' => $metadata + ['source' => 'cli'],
        ]);
    }

    private function mask(string $number): string
    {
        return strlen($number) <= 4 ? str_repeat('*', strlen($number)) : substr($number, -4);
    }
}
