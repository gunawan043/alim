<?php

namespace App\Services\Connect;

use App\Domain\Exceptions\QuotaExceededException;
use App\Exceptions\ApprovalCommandException;
use App\Models\ApprovalCommand;
use App\Models\ConnectActorIdentity;
use App\Models\DormitoryPermit;
use App\Models\User;
use App\Services\Boarding\DormitoryPolicyAccess;
use App\Services\Boarding\LeaveWorkflowService;
use App\Services\Dormitory\DormitoryAudit;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Penerapan command persetujuan izin dari ALIM Connect.
 *
 * Kontrak perilaku:
 *  - Aktor TIDAK diambil dari payload; identitas WhatsApp harus terpetakan dan
 *    terverifikasi pada `connect_actor_identities`.
 *  - Otorisasi memakai aturan ketat `canApprovePermitCommand()`.
 *  - Idempotensi berbasis `command_id` + `external_message_id`.
 *  - Seluruh perubahan status dan pencatatan command terjadi dalam satu
 *    transaksi; kegagalan tidak meninggalkan perubahan parsial.
 */
class ApprovalCommandService
{
    public const DECISION_APPROVED = 'approved';

    public const DECISION_REJECTED = 'rejected';

    public function __construct(
        private readonly LeaveWorkflowService $leave,
        private readonly DormitoryPolicyAccess $access,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{idempotency_key?: ?string, api_version?: ?string}  $context
     * @return array<string, mixed>
     */
    public function handle(array $payload, array $context = []): array
    {
        $commandId = trim((string) $payload['command_id']);
        $permitId = (string) $payload['request_id'];
        $decision = (string) $payload['decision'];
        $source = (string) $payload['source'];
        $externalMessageId = trim((string) $payload['external_message_id']);
        $externalIdentity = ConnectActorIdentity::normalizeIdentity((string) $payload['actor_external_id']);
        $messageText = $this->redactMessageText($payload['message_text'] ?? null);
        $note = $payload['notes'] ?? null;
        $idempotencyKey = isset($context['idempotency_key']) && $context['idempotency_key'] !== ''
            ? (string) $context['idempotency_key']
            : $commandId;
        $apiVersion = isset($context['api_version']) && $context['api_version'] !== ''
            ? (string) $context['api_version']
            : null;

        $payloadHash = $this->payloadHash($payload, $externalIdentity);

        // ── 1. Idempotensi berdasarkan command_id ──────────────────────────
        $existing = ApprovalCommand::query()->where('command_id', $commandId)->first();

        if ($existing) {
            return $this->replay($existing, $payloadHash);
        }

        // ── 2. Tolak pemakaian ulang pesan eksternal ───────────────────────
        $replayedMessage = ApprovalCommand::query()
            ->where('external_message_id', $externalMessageId)
            ->first();

        if ($replayedMessage) {
            throw ApprovalCommandException::conflict(
                'Pesan eksternal ini sudah diproses untuk command lain.',
                'external_message_replayed',
                [
                    'command_id' => $replayedMessage->command_id,
                    'request_id' => $replayedMessage->permit_id,
                    'decision' => $replayedMessage->decision,
                ]
            );
        }

        // ── 3. Aktor dari pemetaan terverifikasi ───────────────────────────
        $actor = $this->resolveActor($externalIdentity);

        // ── 4. Permohonan izin ─────────────────────────────────────────────
        $permit = DormitoryPermit::query()->find($permitId);

        if (! $permit) {
            throw new ApprovalCommandException(
                'Permohonan izin tidak ditemukan.',
                404,
                'permit_not_found',
                ['request_id' => $permitId]
            );
        }

        // ── 5. Kewenangan aktor pada asrama permohonan ─────────────────────
        if (! $this->access->canApprovePermitCommand($actor, (string) $permit->dormitory_id)) {
            throw ApprovalCommandException::forbidden(
                'Aktor bukan Kepala Asrama aktif pada asrama permohonan ini.',
                'actor_not_authorized'
            );
        }

        // ── 6. Permohonan masih dapat diputuskan ───────────────────────────
        if (in_array($permit->secondary_status, ['cancelled', 'expired'], true)) {
            throw ApprovalCommandException::conflict(
                'Permohonan sudah ditandai '.$permit->secondary_status.' dan tidak dapat diputuskan lagi.',
                'permit_not_actionable',
                ['secondary_status' => $permit->secondary_status]
            );
        }

        if ($permit->status !== DormitoryPermit::STATUS_PENDING) {
            throw ApprovalCommandException::conflict(
                'Permohonan sudah diputuskan atau tidak lagi dapat diputuskan.',
                'permit_not_decidable',
                ['status' => $permit->status]
            );
        }

        if ($decision !== 'expired'
            && $permit->expected_return_datetime?->isPast()) {
            throw ApprovalCommandException::conflict(
                'Permohonan sudah kedaluwarsa.',
                'permit_expired',
                ['expected_return_at' => $permit->expected_return_datetime?->toIso8601String()]
            );
        }

        if ($decision === 'expired' && ! $permit->expected_return_datetime?->isPast()) {
            throw ApprovalCommandException::invalid(
                'Permohonan belum kedaluwarsa.',
                'permit_not_expired'
            );
        }

        // ── 7. Durasi ──────────────────────────────────────────────────────
        $days = null;
        if ($decision === self::DECISION_APPROVED) {
            $days = $this->validateDuration($payload['approved_duration_days'] ?? null, $permit);
        }

        // ── 8. Terapkan dalam satu transaksi ───────────────────────────────
        // Gunakan setUser + restore, bukan login: endpoint ini stateless dan
        // dapat berjalan pada worker/Octane. Jangan meninggalkan guard user
        // aktif setelah request selesai.
        $previousUser = Auth::user();
        Auth::setUser($actor);

        try {
            return DB::transaction(function () use (
                $permit, $decision, $days, $actor, $externalIdentity,
                $commandId, $source, $externalMessageId, $messageText, $note, $payloadHash,
                $idempotencyKey, $apiVersion
            ) {
                $locked = DormitoryPermit::query()->whereKey($permit->id)->lockForUpdate()->first();

                if (! $locked || $locked->status !== DormitoryPermit::STATUS_PENDING) {
                    throw ApprovalCommandException::conflict(
                        'Permohonan sudah diputuskan atau tidak lagi dapat diputuskan.',
                        'permit_not_decidable',
                        ['status' => $locked?->status]
                    );
                }

                if (in_array($locked->secondary_status, ['cancelled', 'expired'], true)) {
                    throw ApprovalCommandException::conflict(
                        'Permohonan sudah ditandai '.$locked->secondary_status.'.',
                        'permit_not_actionable',
                        ['secondary_status' => $locked->secondary_status]
                    );
                }

                // LeaveWorkflowService memakai auth()->id() untuk approved_by
                // dan audit; guard sudah menunjuk aktor terverifikasi.
                if ($decision === self::DECISION_APPROVED) {
                    $locked->expected_return_datetime = CarbonImmutable::instance($locked->departure_datetime)
                        ->addDays($days);
                    $locked->save();

                    $decided = $this->leave->approve($locked->id, (string) $locked->dormitory_id, $note);
                    $action = 'DORMITORY_PERMIT_APPROVED';
                } elseif ($decision === self::DECISION_REJECTED) {
                    $decided = $this->leave->reject($locked->id, (string) $locked->dormitory_id, $note);
                    $action = 'DORMITORY_PERMIT_REJECTED';
                } else {
                    $locked->update([
                        'secondary_status' => $decision,
                        'approval_note' => $note,
                        'last_actioned_by' => $actor->id,
                    ]);
                    $decided = $locked->fresh();
                    $action = match ($decision) {
                        'needs_clarification' => 'DORMITORY_PERMIT_NEEDS_CLARIFICATION',
                        'expired' => 'DORMITORY_PERMIT_EXPIRED',
                        'cancelled' => 'DORMITORY_PERMIT_CANCELLED',
                    };
                }

                DormitoryAudit::log($action, 'dormitory_permits', $decided->id, [
                    'command_id' => $commandId,
                    'decision' => $decision,
                    'source' => $source,
                    'external_message_id' => $externalMessageId,
                    'actor_external_identity' => $externalIdentity,
                    'message_text_hash' => $messageText !== null
                        ? hash('sha256', (string) $messageText)
                        : null,
                    'idempotency_key' => $idempotencyKey,
                    'api_version' => $apiVersion,
                ], required: true);

                $body = $this->appliedBody(
                    $decided,
                    $actor,
                    $commandId,
                    $decision,
                    $days,
                    $source,
                    $externalMessageId,
                    $messageText
                );

                ApprovalCommand::create([
                    'command_id' => $commandId,
                    'idempotency_key' => $idempotencyKey,
                    'api_version' => $apiVersion,
                    'permit_id' => $decided->id,
                    'actor_user_id' => $actor->id,
                    'actor_external_identity' => $externalIdentity,
                    'decision' => $decision,
                    'source' => $source,
                    'external_message_id' => $externalMessageId,
                    'message_text' => $messageText,
                    'payload_hash' => $payloadHash,
                    'response_code' => 200,
                    'response_body' => $body,
                    'processed_at' => now(),
                ]);

                return $body;
            });
        } catch (QuotaExceededException $e) {
            throw ApprovalCommandException::conflict(
                $e->getMessage(),
                'quota_exceeded',
                $e->details
            );
        } catch (QueryException $e) {
            // Balapan unik (command_id / external_message_id) → jawab sebagai
            // duplikat/konflik tanpa mengubah status izin.
            return $this->resolveRace($commandId, $externalMessageId, $payloadHash, $e);
        } finally {
            if ($previousUser) {
                Auth::setUser($previousUser);
            } else {
                Auth::guard('web')->forgetUser();
            }
        }
    }

    private function redactMessageText(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = trim((string) preg_replace('/\s+/', ' ', $value));
        $max = max(1, (int) config('services.connect_approval.message_text_max_length', 500));

        return mb_substr($normalized, 0, $max);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadHash(array $payload, string $externalIdentity): string
    {
        $canonical = [
            'command_id' => (string) $payload['command_id'],
            'request_id' => (string) $payload['request_id'],
            'decision' => (string) $payload['decision'],
            'approved_duration_days' => $payload['approved_duration_days'] ?? null,
            'actor_external_id' => $externalIdentity,
            'actor_core_person_id' => $payload['actor_core_person_id'] ?? null,
            'source' => (string) $payload['source'],
            'external_message_id' => (string) $payload['external_message_id'],
            'message_text' => $payload['message_text'] ?? null,
            'notes' => $payload['notes'] ?? null,
        ];

        return hash('sha256', (string) json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, mixed>
     */
    private function replay(ApprovalCommand $existing, string $payloadHash): array
    {
        if (! hash_equals((string) $existing->payload_hash, $payloadHash)) {
            throw ApprovalCommandException::conflict(
                'command_id sudah dipakai untuk payload yang berbeda.',
                'command_id_reused',
                ['command_id' => $existing->command_id]
            );
        }

        $body = $existing->response_body ?? [];
        $body['outcome'] = 'duplicate';
        $body['duplicate'] = true;

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveRace(string $commandId, string $externalMessageId, string $payloadHash, QueryException $e): array
    {
        $existing = ApprovalCommand::query()->where('command_id', $commandId)->first();

        if ($existing) {
            return $this->replay($existing, $payloadHash);
        }

        $replayedMessage = ApprovalCommand::query()
            ->where('external_message_id', $externalMessageId)
            ->first();

        if ($replayedMessage) {
            throw ApprovalCommandException::conflict(
                'Pesan eksternal ini sudah diproses untuk command lain.',
                'external_message_replayed',
                [
                    'command_id' => $replayedMessage->command_id,
                    'request_id' => $replayedMessage->permit_id,
                    'decision' => $replayedMessage->decision,
                ]
            );
        }

        throw $e;
    }

    private function resolveActor(string $externalIdentity): User
    {
        if ($externalIdentity === '') {
            throw ApprovalCommandException::forbidden(
                'Identitas pengirim tidak dikirim.',
                'actor_identity_missing'
            );
        }

        $identity = ConnectActorIdentity::query()
            ->where('channel', 'whatsapp')
            ->where('external_identity', $externalIdentity)
            ->where('is_active', true)
            ->whereNotNull('verified_at')
            ->first();

        if (! $identity) {
            throw ApprovalCommandException::forbidden(
                'Identitas pengirim belum terverifikasi sebagai personel Core.',
                'actor_identity_unmapped'
            );
        }

        $user = User::query()->find($identity->user_id);

        if (! $user || ! $user->is_active) {
            throw ApprovalCommandException::forbidden(
                'Akun personel untuk identitas ini tidak aktif.',
                'actor_inactive'
            );
        }

        $identity->forceFill(['last_seen_at' => now()])->save();

        return $user;
    }

    private function validateDuration(mixed $value, DormitoryPermit $permit): int
    {
        if (! is_int($value) && ! (is_string($value) && ctype_digit($value))) {
            throw ApprovalCommandException::invalid(
                'Durasi izin harus berupa bilangan bulat hari.',
                'duration_invalid'
            );
        }

        $days = (int) $value;
        $max = (int) config('services.connect_approval.max_duration_days', 365);

        if ($days < 1 || $days > $max) {
            throw ApprovalCommandException::invalid(
                "Durasi izin harus antara 1 dan {$max} hari.",
                'duration_out_of_range',
                ['max' => $max]
            );
        }

        if (! $permit->departure_datetime) {
            throw ApprovalCommandException::invalid(
                'Permohonan tidak memiliki waktu keberangkatan sehingga durasi tidak dapat dihitung.',
                'permit_missing_departure'
            );
        }

        return $days;
    }

    /**
     * @return array<string, mixed>
     */
    private function appliedBody(
        DormitoryPermit $permit,
        User $actor,
        string $commandId,
        string $decision,
        ?int $days,
        string $source,
        string $externalMessageId,
        ?string $messageText
    ): array {
        $outcome = in_array($decision, [self::DECISION_APPROVED, self::DECISION_REJECTED], true)
            ? 'applied'
            : $decision;

        return [
            'outcome' => $outcome,
            'duplicate' => false,
            'command_id' => $commandId,
            'request_id' => $permit->id,
            'decision' => $decision,
            'status' => $permit->status,
            'secondary_status' => $permit->secondary_status,
            'approved_duration_days' => $days,
            'departure_datetime' => $permit->departure_datetime?->toIso8601String(),
            'expected_return_datetime' => $permit->expected_return_datetime?->toIso8601String(),
            'decided_at' => ($permit->approved_at ?? now())->toIso8601String(),
            'actor' => [
                'core_person_id' => $actor->id,
                'name' => $actor->name,
            ],
            'source' => $source,
            'external_message_id' => $externalMessageId,
            'message_text' => $messageText,
        ];
    }
}
