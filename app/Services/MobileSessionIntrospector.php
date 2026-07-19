<?php

namespace App\Services;

use App\Models\User;
use App\Support\TokenName;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Sprint 2 — Server-authoritative introspection of mobile sessions.
 *
 * Personal access tokens live in `personal_access_tokens` (Sanctum) and are
 * addressed only by their SHA-256 hash; the API surface must NEVER trust a
 * device label, IP, or fingerprint coming from the client. This service
 * reads from the DB and the Request object to project the canonical
 * session shape consumed by GET /auth/sessions and friends.
 *
 * Two contract guarantees:
 *  - "current_device" is true ONLY for the row whose .id equals the
 *    currently authenticated token's id. The client cannot nominate any
 *    other token as current.
 *  - "abilities" is always deserialised from the DB column; the wire-format
 *    "mobile:read-self" string the client received at login-time is
 *    advisory only.
 */
class MobileSessionIntrospector
{
    /**
     * Project a single token row to the canonical session shape.
     *
     * @return array<string, mixed>
     */
    public function describe(PersonalAccessToken $token, ?Request $request = null): array
    {
        $currentId = null;

        if ($request !== null && $request->user() !== null) {
            $current = $request->user()->currentAccessToken();
            if ($current instanceof PersonalAccessToken) {
                $currentId = $current->id;
            }
        }

        return $this->describeInternal($token, $currentId);
    }

    /**
     * Project every active token for the user to the canonical session shape.
     *
     * "Active" excludes soft-deleted rows and tokens already past expires_at.
     *
     * @return list<array<string, mixed>>
     */
    public function listForUser(User $user, ?Request $request = null): array
    {
        $currentId = null;

        if ($request !== null && $request->user() !== null) {
            $current = $request->user()->currentAccessToken();
            if ($current instanceof PersonalAccessToken) {
                $currentId = $current->id;
            }
        }

        $tokens = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $user->getKey())
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get();

        $now = Carbon::now();

        $rows = [];

        foreach ($tokens as $token) {
            if ($token->expires_at instanceof Carbon && $token->expires_at->lessThan($now)) {
                continue;
            }

            $rows[] = $this->describeInternal($token, $currentId);
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function describeInternal(PersonalAccessToken $token, ?string $currentId): array
    {
        $abilities = $this->decodeAbilities($token->abilities);

        return [
            'id' => (string) $token->id,
            'name' => $token->name,
            'platform' => TokenName::platformFromName($token->name),
            'device_label' => $token->device_label,
            'ip_last' => $token->ip_last,
            'fingerprint' => $token->fingerprint,
            'abilities' => $abilities,
            'created_at' => optional($token->created_at)->toIso8601String(),
            'last_used_at' => optional($token->last_used_at)->toIso8601String(),
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'current_device' => $currentId !== null && (string) $token->id === $currentId,
        ];
    }

    /**
     * Sanctum stores abilities as JSON; tolerate legacy rows that may
     * hold either JSON or NULL.
     *
     * @return list<string>
     */
    private function decodeAbilities(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_array($raw)) {
            $values = array_values($raw);

            return array_values(array_filter($values, 'is_string'));
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            if (! is_array($decoded)) {
                return [];
            }

            return array_values(array_filter($decoded, 'is_string'));
        }

        return [];
    }
}
