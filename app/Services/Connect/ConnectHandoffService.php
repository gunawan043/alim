<?php

namespace App\Services\Connect;

use App\Authorization\ValueObjects\OrganizationContext;
use App\Exceptions\ConnectSsoException;
use App\Models\AuditLog;
use App\Models\ConnectSsoHandoff;
use App\Models\User;
use App\Support\ConnectSsoConfig;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConnectHandoffService
{
    public const AUDIENCE = 'connect';

    public const SCOPE = 'whatsapp.embed';

    public const PERMISSION = 'whatsapp_connect_view';

    /**
     * Issue a one-time handoff code. Only the SHA-256 hash is persisted.
     *
     * @return array{code:string,audience:string,scope:string,expires_at:string,connect_sso_url:string}
     */
    public function issue(User $user, ?OrganizationContext $context = null): array
    {
        if (! $user->is_active || ! $this->userStillHasPermission($user)) {
            throw ConnectSsoException::forbidden(
                'Pengguna tidak berhak membuat handoff WhatsApp Connect.',
                'permission_revoked'
            );
        }

        $audience = self::AUDIENCE;
        $scope = self::SCOPE;
        $ttl = ConnectSsoConfig::handoffTtl();
        $expiresAt = now()->addSeconds($ttl);
        $code = $this->generateCode();

        try {
            DB::transaction(function () use ($user, $code, $audience, $scope, $expiresAt, $context) {
                $handoff = ConnectSsoHandoff::create([
                    'user_id' => $user->id,
                    'code_hash' => hash('sha256', $code),
                    'audience' => $audience,
                    'scope' => $scope,
                    'expires_at' => $expiresAt,
                    'metadata' => [
                        'school_id' => $context?->schoolId,
                        'academic_year_id' => $context?->academicYearId,
                        'role_dimension' => $context?->roleDimension,
                        'tenant' => $context?->tenant,
                    ],
                ]);

                $this->audit('WHATSAPP_CONNECT_HANDOFF_ISSUED', $user->id, [
                    'handoff_id' => $handoff->id,
                    'audience' => $audience,
                    'scope' => $scope,
                    'expires_at' => $expiresAt->toIso8601String(),
                ], required: true);
            });
        } catch (Throwable $e) {
            $this->auditFailure(null, 'issue_failed', ['error' => class_basename($e)]);
            throw $e;
        }

        return [
            'code' => $code,
            'audience' => $audience,
            'scope' => $scope,
            'expires_at' => $expiresAt->toIso8601String(),
            'connect_sso_url' => ConnectSsoConfig::handoffUrl(),
        ];
    }

    /**
     * Atomically redeem a one-time code and return the minimum Core identity.
     *
     * @return array{core_user_id:string,name:string,email:?string,audience:string,scope:string,issued_at:string,redeemed_at:string}
     */
    public function redeem(
        string $code,
        string $audience,
        string $scope,
        ?string $ip = null,
        ?string $userAgent = null
    ): array {
        if (trim($code) === '') {
            $this->auditFailure(null, 'code_required');
            throw ConnectSsoException::invalid('Kode handoff wajib diisi.', 'code_required');
        }

        if ($audience !== self::AUDIENCE) {
            $this->auditFailure(null, 'audience_mismatch', ['expected' => self::AUDIENCE]);
            throw ConnectSsoException::forbidden(
                'Audience handoff tidak sesuai.',
                'audience_mismatch',
                ['expected' => self::AUDIENCE]
            );
        }

        if ($scope !== self::SCOPE) {
            $this->auditFailure(null, 'scope_mismatch', ['expected' => self::SCOPE]);
            throw ConnectSsoException::forbidden(
                'Scope handoff tidak sesuai.',
                'scope_mismatch',
                ['expected' => self::SCOPE]
            );
        }

        $hash = hash('sha256', $code);
        $userIdForAudit = null;

        try {
            return DB::transaction(function () use ($hash, $ip, $userAgent, &$userIdForAudit) {
                $handoff = ConnectSsoHandoff::query()
                    ->where('code_hash', $hash)
                    ->lockForUpdate()
                    ->first();

                if (! $handoff) {
                    throw ConnectSsoException::notFound();
                }

                $userIdForAudit = (string) $handoff->user_id;

                if ($handoff->used_at !== null) {
                    throw ConnectSsoException::conflict(
                        'Kode handoff sudah pernah digunakan.',
                        'code_already_used'
                    );
                }

                if ($handoff->expires_at->isPast()) {
                    throw ConnectSsoException::gone();
                }

                $user = User::query()->find($handoff->user_id);

                if (! $user || ! $user->is_active) {
                    throw ConnectSsoException::forbidden(
                        'Pengguna Core tidak aktif.',
                        'user_inactive'
                    );
                }

                if (! $this->userStillHasPermission($user)) {
                    throw ConnectSsoException::forbidden(
                        'Permission WhatsApp Connect sudah tidak berlaku.',
                        'permission_revoked'
                    );
                }

                $updated = ConnectSsoHandoff::query()
                    ->whereKey($handoff->id)
                    ->whereNull('used_at')
                    ->update([
                        'used_at' => now(),
                        'used_ip' => $ip,
                        'used_user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
                    ]);

                if ($updated !== 1) {
                    throw ConnectSsoException::conflict(
                        'Kode handoff sudah pernah digunakan.',
                        'code_already_used'
                    );
                }

                $handoff->refresh();

                $this->audit('WHATSAPP_CONNECT_SSO_REDEEMED', $user->id, [
                    'handoff_id' => $handoff->id,
                    'audience' => $handoff->audience,
                    'scope' => $handoff->scope,
                    'redeemed_at' => $handoff->used_at?->toIso8601String(),
                ], required: true);

                return [
                    'core_user_id' => (string) $user->id,
                    'name' => (string) $user->name,
                    'email' => $user->email,
                    'audience' => (string) $handoff->audience,
                    'scope' => (string) $handoff->scope,
                    'issued_at' => $handoff->created_at?->toIso8601String(),
                    'redeemed_at' => $handoff->used_at?->toIso8601String(),
                ];
            });
        } catch (ConnectSsoException $e) {
            $this->auditFailure($userIdForAudit, $e->errorCode, $e->context);

            throw $e;
        } catch (Throwable $e) {
            $this->auditFailure($userIdForAudit, 'redeem_failed', ['error' => class_basename($e)]);

            throw $e;
        }
    }

    private function userStillHasPermission(User $user): bool
    {
        if ($user->isSystemAdmin() || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())) {
            return true;
        }

        // Query fresh DB state instead of relying on Spatie's per-user cache or
        // a permission snapshot, so revocation after handoff issuance is
        // enforced immediately.
        $direct = $user->permissions()
            ->where('permissions.name', self::PERMISSION)
            ->exists();

        if ($direct) {
            return true;
        }

        $viaRole = $user->roles()
            ->whereHas('permissions', fn ($query) => $query->where('permissions.name', self::PERMISSION))
            ->exists();

        if ($viaRole) {
            return true;
        }

        foreach ($user->activeDomains() as $domain) {
            if ($domain->permissions()->where('permissions.name', self::PERMISSION)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function generateCode(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function audit(string $action, ?string $userId, array $metadata, bool $required = false): void
    {
        try {
            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'table_name' => 'connect_sso_handoffs',
                'record_id' => $metadata['handoff_id'] ?? null,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'metadata' => $metadata,
            ]);
        } catch (Throwable $e) {
            if ($required) {
                throw $e;
            }
        }
    }

    private function auditFailure(?string $userId, string $reason, array $context = []): void
    {
        $this->audit('WHATSAPP_CONNECT_SSO_REDEEM_FAILED', $userId, [
            'reason' => $reason,
            'context' => $context,
        ]);
    }
}
