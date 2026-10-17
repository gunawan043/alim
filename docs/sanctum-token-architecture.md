# Sanctum Token Architecture

| Field           | Value                                                                                                  |
|-----------------|--------------------------------------------------------------------------------------------------------|
| Revision        | 1 (Sprint 2)                                                                                          |
| Status          | Accepted (Sprint 2 follow-up to ADR-018)                                                               |
| Date            | 2026-07-15                                                                                             |
| Related ADRs    | ADR-018 (Mobile Authentication Strategy), ADR-016 (Permission Snapshot Schema)                        |

This document is the **architecture summary** of how the ALIM mobile API issues, names, expires, lists, and revokes Sanctum personal access tokens. Read this before touching `app/Http/Controllers/Api/Mobile/V1/AuthController.php`, `app/Models/User.php`, or `database/migrations/*personal_access_tokens*`.

---

## 1. Surface

- **Route prefix**: `/api/mobile/v1/auth/*`
- **Middleware**: `auth:sanctum` on every protected route
- **Storage**: `personal_access_tokens` table (Sanctum's default, extended by Sprint 2)
- **Hashing**: tokens stored as SHA-256 hash; the plaintext is only ever returned in the issuance response and is never logged

---

## 2. Token name format

Every token issued by the mobile API follows the **canonical 5-segment colon-separated format**:

```
mobile:{actor}:{channel}:{platform}:{fingerprint}
```

| Segment     | Allowed values                                | Example                |
|-------------|-----------------------------------------------|------------------------|
| `mobile`    | literal prefix; identifies mobile surface     | `mobile`               |
| `actor`     | actor population: `user` (Sprint 1 scope)     | `user`                 |
| `channel`   | auth channel: `password` \| `google`          | `password`             |
| `platform`  | client platform: `android` \| `ios` \| `unknown` | `android`             |
| `fingerprint` | `fp_*` derived from device id or IP hash     | `fp_ip_3a91...`        |

The format is enforced by `App\Support\TokenName::mobile($actor, $channel, $platform, $fingerprint)`. The client does **not** see the name — it receives the opaque Bearer string only.

---

## 3. Token columns (`personal_access_tokens`)

| Column        | Type           | Set by | Purpose |
|---------------|----------------|--------|---------|
| `id`          | bigint         | Sanctum | PK |
| `tokenable_*` | morph          | Sanctum | owner (always `User` for mobile) |
| `name`        | string         | controller | canonical 5-segment name (see §2) — **platform is encoded in segment 4**, not in a separate column |
| `token`       | string(64)     | Sanctum | SHA-256 hash of the plaintext token; plaintext never stored |
| `abilities`   | text           | controller | JSON array of ability strings from `AbilityRegistry::forRoles(...)` |
| `last_used_at`| timestamp null | Sanctum | updated automatically on each auth hit |
| `expires_at`  | timestamp null | controller | set via `TokenExpiration::mobileDefaultExpiresAt()`; null = never expires |
| `created_at`  | timestamp      | Sanctum | issuance time |
| `updated_at`  | timestamp      | Sanctum | |
| `device_label`| string(80) null | controller (`PATCH /sessions/current`) | user-set device nickname (e.g. "HP Pak Kades") |
| `ip_last`     | string(45) null | controller (`login`, `register`, `google`) | IP from `Request::ip()`, capped at 45 chars (IPv6 max) |
| `fingerprint` | string(40) null | controller | the `fp_*` value stored redundantly for diagnostics |

The Sprint 2 migration `2026_07_15_110000_add_device_metadata_to_personal_access_tokens` adds the four `device_*`/`ip_last`/`fingerprint` columns additively. It does **not** drop or modify any existing column. **`platform` is NOT a column** — it is the 4th segment of the `.name` string, parsed on read by `App\Support\TokenName::platformFromName($token->name)`. This was a deliberate design choice in Sprint 2: a separate `platform` column would duplicate a value that already lives in the canonical name, and every column we add is a column we must migrate, index, and audit.

If a future requirement demands an indexed platform column (e.g. "show me every Android token issued in the last 7 days"), we add it then. Until then: parse the name.

---

## 4. Token abilities

Abilities are derived exclusively from roles via `App\Support\AbilityRegistry::forRoles($user->effectiveRoles())`.

- Roles are read via `User::effectiveRoles()` (Spatie, with a fallback that reads `users.role` for the legacy single-role column)
- Wildcard `'*'` (admin / super-admin) is expanded by `AbilityRegistry` into the full catalog
- Abilities are stored as JSON on the token, NOT a runtime lookup — this means revoking a role does **not** retroactively strip abilities from already-issued tokens. If role revocation must take effect immediately, revoke the token via `DELETE /sessions/others` or `DELETE /sessions/{id}`.
- The catalog is `config/sanctum.php` under `abilities.catalog`. Adding an ability requires: (a) add to catalog, (b) add to every role's list that should receive it, (c) regenerate the mobile client's release notes.

---

## 5. Token expiration

All mobile tokens honour a single TTL configured in `config/sanctum.php`:

```php
'expiration' => [
    'mobile_default_minutes' => env('SANCTUM_MOBILE_DEFAULT_MINUTES', 60 * 24 * 30),
],
```

`App\Support\TokenExpiration` is the **only** allowed entry point to read this value:

| Method | Returns | Use |
|---|---|---|
| `mobileDefaultMinutes(): ?int` | int or null | for `expires_in` field in JSON response (seconds = minutes × 60) |
| `mobileDefaultExpiresAt(): ?Carbon` | Carbon or null | for `$user->createToken(..., $expiresAt)` third argument |

Setting `SANCTUM_MOBILE_DEFAULT_MINUTES=0` (or negative) makes the helper return null, which means "no expiration". This is **discouraged** but supported.

---

## 6. Endpoints (Sprint 1 + Sprint 2)

| Verb / Path | Auth | Purpose |
|---|---|---|
| `POST /api/mobile/v1/auth/register` | none | Create wali account, issue token |
| `POST /api/mobile/v1/auth/login` | none | Email/password login, issue token |
| `POST /api/mobile/v1/auth/google` | none | Google OAuth login (create-or-link), issue token |
| `POST /api/mobile/v1/auth/logout` | `sanctum` | Delete the current PAT |
| `POST /api/mobile/v1/auth/logout-all` | `sanctum` | Delete every PAT for this user |
| `GET /api/mobile/v1/auth/sessions` | `sanctum` | List every PAT with `current_device` flag |
| `PATCH /api/mobile/v1/auth/sessions/current` | `sanctum` | Rename current PAT (`device_label`) |
| `DELETE /api/mobile/v1/auth/sessions/others` | `sanctum` | Revoke every PAT except the current one |
| `GET /api/mobile/v1/auth/me` | `sanctum` | Current user + linked students |
| `PUT /api/mobile/v1/auth/me` | `sanctum` | Update profile fields |

### Response envelope (every endpoint)

```json
{
  "success": true,
  "message": "...",
  "data": { ... },
  "errors": null
}
```

### Token issuance response (register / login / google)

```json
{
  "success": true,
  "message": "Login berhasil.",
  "data": {
    "user":        { "id": "...", "name": "...", "email": "...", ... },
    "access_token": "<plaintext>",
    "token_type":  "Bearer",
    "expires_in":  2592000,
    "expires_at":  "2026-08-14T10:00:00+00:00",
    "abilities":   ["attendance.read", "grades.read", ...]
  }
}
```

### Session list response

```json
{
  "success": true,
  "message": "Daftar sesi berhasil diambil.",
  "data": {
    "sessions": [
      {
        "id": "abc-123",
        "device_label": "HP Pak Kades",
        "platform": "android",
        "ip_last": "10.0.0.5",
        "abilities": ["attendance.read", ...],
        "current_device": true,
        "created_at": "2026-07-15T10:00:00+00:00",
        "last_used_at": "2026-07-15T11:23:00+00:00",
        "expires_at": "2026-08-14T10:00:00+00:00"
      }
    ]
  }
}
```

`platform` is **derived** from the `.name` column by `App\Support\TokenName::platformFromName($name)`. It is not stored as a separate column. See §3.

---

## 7. Brute-force lockout (Sprint 2 fix)

`User::incrementFailedLoginAttempts()` and `User::isLocked()` were inherited from the original `users` migration (`failed_login_attempts` counter, `locked_at`, `locked_until` columns) but were **not wired** to the login path — the function existed, was never called. Sprint 2 fixes this by:

1. In `AuthController::login`, before `Hash::check`, check `$user->isLocked()` and reject with a translated message.
2. If `Hash::check` fails AND the user exists, call `$user->incrementFailedLoginAttempts()`.

The counter resets on successful login via `$user->resetFailedLoginAttempts()`. Threshold and lock duration are in `User::incrementFailedLoginAttempts` (currently 10 failed attempts ⇒ 15-minute lock).

This change is **non-breaking** for legitimate users (the old code never called `incrementFailedLoginAttempts` either, so legitimate users never had a counter to reset).

---

## 8. Out of scope (Sprint 3+)

- **Per-route ability checks** — `abilities:attendance.write` middleware wiring is deferred to Sprint 3. Currently every authenticated wali can hit every authenticated wali route.
- **PIN re-authentication** for sensitive endpoints (`POST /permits`, `PATCH /me/password`) — `PinReauthController` is not yet implemented.
- **Sanctum prune-expired scheduler** — the `sanctum:prune-expired` artisan command exists but is not on the scheduler.
- **Push notification session invalidation** — when a wali changes password, all other sessions are not yet force-revoked.
- **Cross-device session fingerprinting** — the `fingerprint` column is currently best-effort from request IP and device-id header; it is not yet a stable per-device token.

These items remain in `docs/mobile-api-gap-analysis.md` (when written) and `mobile-api-gap-analysis.md` backlog.

---

## 9. References

- `app/Http/Controllers/Api/Mobile/V1/AuthController.php` — the single controller that issues, revokes, and introspects mobile tokens
- `app/Models/User.php:402-411` — `effectiveRoles()`
- `app/Models/User.php:227-249` — `incrementFailedLoginAttempts`, `isLocked`, `resetFailedLoginAttempts`
- `app/Support/AbilityRegistry.php` — role-to-ability mapping with wildcard expansion
- `app/Support/TokenExpiration.php` — TTL single-source-of-truth
- `app/Support/TokenName.php` — canonical 5-segment name formatter
- `app/Services/MobileSessionIntrospector.php` — session list / describe logic
- `database/migrations/2026_07_15_110000_add_device_metadata_to_personal_access_tokens.php` — additive column migration
- `config/sanctum.php:115-186` — ability catalog and role mapping
- `routes/api.php` — mobile v1 route definitions
- `tests/Feature/MobileAuthSprint2Test.php` — Sprint 2 test coverage
- `docs/adr/ADR-018-mobile-authentication-strategy.md` — parent ADR