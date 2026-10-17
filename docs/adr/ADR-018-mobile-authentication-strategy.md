# ADR-018: Mobile Authentication Strategy — Sanctum Token-Based Auth (Single Source of Truth)

| Field           | Value                                                                                                  |
|-----------------|--------------------------------------------------------------------------------------------------------|
| Revision        | 1 (Architecture Board)                                                                                 |
| Status          | Accepted                                                                                               |
| Date            | 2026-07-04                                                                                             |
| Related ADRs    | ADR-016 (Permission Snapshot Schema), ADR-017 (Write-Time Authorization Guard)                        |
| Supersedes      | None (corrects a bifurcated implementation; no previous ADR exists for mobile auth)                     |
| Author          | Architecture Board (Lead Backend + Lead Mobile API)                                                    |

---

## 1. Context

The ALIM system serves three distinct actor populations through three distinct surfaces, each with materially different trust, deployment, and threat models:

| Surface | Actor | Client | Trust Profile |
|---|---|---|---|
| **Web GTK Admin** | GTK (guru/staff), Super Admin | Browser (Laravel Blade + jQuery) | Server-rendered, cookie-bound, server controls session lifecycle |
| **Mobile Wali App** | Wali Santri only | React Native, distributed via Play Store / App Store | Untrusted device, long-lived install, may be shared / rooted / jailbroken |
| **Guardian Portal (Token-URL)** | Wali Santri only | Browser, via URL sent by admin (WhatsApp) | Magic-link style; browser is incidental; identity = the URL itself |

The mobile surface is the focus of this ADR because the current implementation contains a **bifurcated authentication state** that is not yet a runtime failure but is a latent defect:

| Path | Current code | Operational status |
|---|---|---|
| `routes/api.php` mobile routes | `auth:sanctum` middleware | **Active** — every mobile route file confirms this |
| `User` model | `HasApiTokens` trait imported from `Laravel\Sanctum` | **Active** — `User.php:12`, `:18` |
| `SuperAdmin\TokenSesiController::createToken()` | `$tokenable->createToken(...)` | **Active** — pure Sanctum API call, `:78` |
| `App\Models\WaliRegistrationToken` | Plain SHA-256 hash tokens (not JWT) | **Active** — for Guardian Portal only |
| `App\Http\Controllers\Api\Mobile\V1\AuthController` | `JWTAuth::fromUser(...)` (4 call sites) | **Dead** — `tymon/jwt-auth` is **not** in `composer.json`; calling `JWTAuth` will fatal-error at runtime |
| `App\Http\Middleware\ValidateMobileToken` | `JWTAuth::parseToken()->authenticate()` | **Dead** — same reason; never wired into routes/api.php for the mobile/v1 group anyway |

The gap analysis at `docs/mobile-api-gap-analysis.md:64-67` describes the mobile API as "uses `tymon/jwt-auth`. Refresh is implicit (re-login after TTL)" — this is the architectural lie that must be corrected. The mobile app documentation at `WALI_SANTRI_API_SCHEMA.md:7` already specifies `Authorization: Bearer {sanctum_token}`.

### Why the bifurcation exists

Reading the git-less history of `AuthController.php`, the implementer began with `tymon/jwt-auth` (a popular but heavy-weight JWT library) but during composer install never declared it. The migration to Sanctum was performed in `composer.json`, `User.php`, and `routes/api.php` but never propagated back into `AuthController.php` or `ValidateMobileToken.php`. The resulting state is a working Sanctum runtime with broken JWT controller code behind it.

### Why an ADR (not a silent fix)

This ADR is required rather than a one-line code change because:

1. **A silent deletion of the JWT controller code would erase architectural intent** — the original implementer may have had reasons for JWT (stateless, cross-domain) that the current Sanctum design also needs to satisfy.
2. **The Wali Santri threat model is unusual**: not just "mobile user" but "long-lived device install by a parent who may share the phone, may have a child who picks up the phone, may have a Play Store-distributed APK with no app-store account-bound security."
3. **Three auth-related decisions are bundled**: token format (JWT vs opaque), token transport (Bearer vs cookie), token revocation (stateless vs server-checked). Each has independent consequences.
4. **The Guardian Portal access_token pattern** is unrelated to the mobile question but is co-located in the same `User` model surface — we must explicitly rule it out as a candidate for mobile auth reuse.

---

## 2. Decision

The ALIM mobile API (`/api/mobile/v1/*`) will use **Laravel Sanctum opaque, database-backed personal access tokens** as the single authentication mechanism, with a **dedicated middleware stack** and **explicit threat-model mitigations** layered on top.

### 2.1 Token format and transport

| Aspect | Choice | Rationale |
|---|---|---|
| Token format | Opaque random string (40-char SHA-1 style), stored hashed (SHA-256) in `personal_access_tokens` table | Sanctum's default; opaque = no claims to leak; hash-at-rest = DB compromise does not yield usable tokens |
| Token transport | `Authorization: Bearer <token>` header on every request | Standard for SPAs/mobile; no CSRF surface; works offline-of-cookie |
| Token persistence on device | Encrypted storage (Android: Keystore-backed EncryptedSharedPreferences; iOS: Keychain) | Plain storage on rooted devices = token theft; the mobile team owns this implementation but must commit to it in [[ADR-019-mobile-secure-storage]] |
| TLS | HTTPS mandatory (`APP_URL=https://alim.sekolah.sch.id`); HSTS header | Defense in depth — even with bearer tokens, TLS prevents trivial network sniffing |

### 2.2 Token abilities (capabilities, not roles)

Sanctum tokens support `abilities` (an array of strings). The mobile app uses abilities to scope tokens:

| Ability | Granted to | Endpoints covered |
|---|---|---|
| `mobile:read-self` | All Wali tokens | `/auth/me`, `/santri/*` (own children), `/notifications` |
| `mobile:write-permit` | Wali token after explicit re-auth or PIN | `POST /dormitory/permit`, `POST /santri` (link request) |
| `mobile:write-profile` | Wali token | `PUT /auth/me` |
| `mobile:link-request` | Wali token | `POST /wali-santri/request` |

The mobile app's login response issues a **minimal token** (`mobile:read-self`, `mobile:link-request`, `mobile:write-profile`). Privileged write abilities (`mobile:write-permit`) require an **explicit "Confirm with PIN" flow** that issues a short-ability-restricted token.

### 2.3 Token lifetime, rotation, and revocation

| Token type | Lifetime | Refresh | Revocation |
|---|---|---|---|
| Default wali login token | 30 days (configurable via `SANCTUM_MOBILE_TOKEN_EXPIRATION_MINUTES`) | Sliding (any authenticated request extends by 30 days if last activity > 25 days) | Sanctum **hard-delete** on `POST /auth/logout`, `POST /auth/logout-all`, `DELETE /auth/sessions/others`, password change, or admin action |
| PIN-reauth permit token | 10 minutes | None | Auto-expires; never persisted to disk by client |
| Forgot-password token | 60 minutes | None | Single-use; deleted after consumption |

**Revocation semantics — hard-delete, not soft-delete.**

Sanctum's `PersonalAccessToken` model does not use the `SoftDeletes` trait. Revocation is a row-level `DELETE FROM personal_access_tokens WHERE id = ?`. There is **no** `personal_access_tokens.deleted_at` column (verified against the Sprint 2 additive migration `2026_07_15_110000_add_device_metadata_to_personal_access_tokens.php`). If audit retention is required (e.g. "show this user they had a token active on 2026-07-15"), an `audit_logs` row must be written **before** the delete — this is a future concern, not a current requirement.

The mobile app is responsible for **detecting 401, prompting re-login, and clearing secure storage** on `POST /auth/logout`. The server never depends on the client for revocation.

### 2.4 Wali Santri hard-block from web

The `User` model is shared across web and mobile. **Wali Santri accounts (`is_wali = true`) are forbidden from authenticating against any web route.** This is implemented at the web `LoginController` level (already exists per code review: "Akun ini adalah Wali Santri dan tidak memiliki akses ke website ini").

The reverse direction is also enforced: **web-only GTK tokens** (issued via `SuperAdmin\TokenSesiController::createToken`) carry a name with the `admin:` surface prefix (e.g. `admin:super-admin:password:web:fp_admin_…`) that the mobile `auth:sanctum` middleware checks against a deny-list. A web GTK token presented to a mobile endpoint returns 403, not 401. The SuperAdmin surface uses `TokenName::admin(...)` so all PATs in `personal_access_tokens` follow the same 5-segment shape (mobile:`…` for wali, admin:`…` for super-admin/web-GTK) and the audit UX can distinguish them by the first segment.

### 2.5 Guardian Portal token is NOT mobile auth

The `WaliSantri.access_token` (SHA-256 hashed) used by `GuardianPortalController` is **not** a candidate for the mobile API:

| Reason | Detail |
|---|---|
| Surface mismatch | Magic-link URL sent via WhatsApp — designed for one-shot browser session creation, not long-lived mobile app install |
| Lifecycle mismatch | Guardian Portal token persists indefinitely until admin reissues; mobile token rotates every 30 days |
| Audit granularity | Mobile needs per-request token; Guardian Portal only needs to identify the wali once per page load |
| Compromise blast radius | If a Guardian Portal token leaks from WhatsApp, attacker can impersonate wali on the web portal — already true today. Extending this to mobile would compound the breach |

The Guardian Portal pattern remains as-is. It is not "auth" in the mobile sense; it is "URL-as-identity."

### 2.6 Why not JWT (explicit rejection)

| Concern | Sanctum (chosen) | JWT (rejected) |
|---|---|---|
| Storage | Server-side hash in `personal_access_tokens` table; revocable in one UPDATE | Stateless; revocation requires a blocklist, defeating statelessness |
| DB dependency on auth | Required (one SELECT) | Not required, but at cost of revocation |
| Cross-domain | Not relevant — all mobile calls hit same-origin `https://alim.sekolah.sch.id` | Was a JWT selling point; not our use case |
| Token size | 40 chars | 200-400 chars (header.payload.signature) |
| Secret key rotation | DB row update | Requires re-signing all tokens or accepting mixed-validity window |
| Library maturity | First-party Laravel, maintained by Laravel team | `tymon/jwt-auth` historically has had CVEs; not in our composer.json; would require new vendor lock-in |
| Threat-model fit | Wali phones may be shared/lost; we WANT server-side kill switch | JWT with no blocklist = lost phone = permanent credential until natural expiry |

JWT is rejected because the Wali Santri threat model values **revocability over statelessness**. Lost-phone scenarios are common in our user base; server-side kill switch is non-negotiable.

### 2.7 Why not Passport (explicit rejection)

Passport is Laravel's full OAuth2 server. It is rejected because:

1. OAuth2 is designed for **third-party delegated authorization** (e.g., "login with Google"). Our wali app is **first-party** — the same organization owns the API and the app. OAuth2 ceremony (authorization code + PKCE + refresh tokens + scopes) adds complexity without solving a problem we have.
2. Passport requires a `users.oauth_clients` table and full OAuth2 state machine — overkill for our needs.
3. Sanctum gives us everything we need (server-revocable tokens, abilities, middleware) without the OAuth2 overhead.

If we ever open the API to **third-party integrators** (e.g., a separate vendor's parent-portal app), we add Passport as a separate guard at that time. Today's decision does not foreclose that future.

---

## 3. Threat model and explicit mitigations

| Threat | Mitigation |
|---|---|
| Stolen phone, app open | App idle timeout 5 min → biometric (fingerprint/FaceID) re-prompt; server-side session not affected (token still valid, but UI requires local re-auth) |
| Stolen phone, token exfiltrated from rooted device | EncryptedSharedPreferences / Keychain (mobile-team owned); SHA-256 hash at rest on server means DB dump is unusable |
| Token leaked via logs (client or server) | Tokens are opaque random — useless without DB lookup; logging middleware redacts `Authorization` header; CI lint rule forbids logging bearer tokens |
| Wali shares account with another parent | Same account, same data — we do not model "household sharing"; if the family needs two accounts, they create two. The `wali_santri` pivot already supports this (e.g., ayah + ibu) |
| MITM on local network | HTTPS-only; HSTS; certificate pinning is mobile-team decision (out of ADR scope) |
| Replay attack | Sanctum tokens have no replay nonce (stateless bearer) — accepted risk; mitigated by HTTPS + token rotation |
| Brute force | `User::incrementFailedLoginAttempts()` locks after 9 failures for 24h — already in `User.php:227-237` |
| CSRF | Bearer header is not auto-attached by browsers → CSRF impossible for mobile API; web uses Sanctum's CSRF cookie pattern separately |

---

## 4. Implementation contract

### 4.1 Files that MUST change (correct the bifurcation)

| File | Change |
|---|---|
| `app/Http/Controllers/Api/Mobile/V1/AuthController.php` | Replace `JWTAuth::fromUser($user)` with `$user->createToken('mobile-wali', ['mobile:read-self', 'mobile:link-request', 'mobile:write-profile'], now()->addDays(30))->plainTextToken`. Remove `use Tymon\JWTAuth\Facades\JWTAuth;` import. Token issuance happens in `register`, `login`, `google`. Token revocation in `logout` via `auth()->user()->currentAccessToken()->delete()`. Update `formatUser` and response payload to include `token_abilities` and `expires_at`. |
| `app/Http/Middleware/ValidateMobileToken.php` | Delete (or rewrite to a no-op) — its only callers are absent from `routes/api.php`, and `auth:sanctum` already does the equivalent work. The middleware as written dead-code-imports JWT classes that don't exist. |
| `docs/mobile-api-gap-analysis.md` line 64-67 | Update "Uses `tymon/jwt-auth`" → "Uses Laravel Sanctum personal access tokens with abilities. See ADR-018." |
| `WALI_SANTRI_API_SCHEMA.md` line 7 | No change needed (already says Sanctum). |

### 4.2 Files that MUST NOT change (out of scope)

| File | Reason |
|---|---|
| `routes/api.php` | Already correctly uses `auth:sanctum`; no change |
| `config/auth.php` | Default `web` guard is correct; Sanctum doesn't add a separate guard |
| `app/Models/User.php` | Already has `HasApiTokens`; no change |
| `app/Http/Controllers/GuardianPortalController.php` | Out of scope — Guardian Portal uses its own token mechanism |
| Any Spatie/Permission code | Out of scope — authorization layer is governed by ADR-016/017 |

### 4.3 New files to add

| File | Purpose |
|---|---|
| `app/Http/Middleware/EnforceWaliAbilities.php` | Custom middleware that checks `$request->user()->currentAccessToken()->abilities` against the route's required abilities; returns 403 if missing. Wired into routes after `auth:sanctum` for protected writes. |
| `app/Http/Controllers/Api/Mobile/V1/PinReauthController.php` | Handles "Confirm with PIN" flow that issues short-lived permit-write token. |
| `app/Models/MobileDevice.php` + migration | Track device fingerprint (OS, model, app version, install_id) for `personal_access_tokens` auditing. Optional but recommended for lost-phone flows. |
| `app/Console/Commands/RevokeExpiredTokens.php` | Scheduled command to soft-delete tokens older than 90 days since last use (defense in depth beyond the 30-day rotation). |

### 4.4 Mobile app contract

The mobile team must commit to:

1. **No token in logs** — assert in CI (mobile side; out of scope for backend ADR but contractually required)
2. **Secure storage** — see [[ADR-019-mobile-secure-storage]] (to be written)
3. **401 handling** — on 401 from any endpoint, clear secure storage and route to login screen
4. **Logout** — call `POST /auth/logout`, then clear secure storage, then navigate to login
5. **App upgrade** — on app version upgrade that changes auth contract, force re-login (handled by `mobile_force_relogin` flag in `/auth/me` response)

---

## 5. Consequences

### Positive

- **Single source of truth** — one auth library, one middleware, one mental model across the entire `/api/mobile/v1` surface
- **Revocability** — lost-phone scenarios are recoverable via `personal_access_tokens` row DELETE (Sanctum hard-delete), not a 30-day wait
- **Ability scoping** — `mobile:write-permit` can be revoked independently of `mobile:read-self` (e.g., parent revokes own ability to file permits while still being able to read)
- **No JWT key management** — no `JWT_SECRET` to rotate, no blocklist to maintain
- **Compatible with existing infrastructure** — Sanctum is already installed, already wired, already in `composer.json`. The fix is deletion of broken code, not introduction of new infrastructure.

### Negative

- **DB hit per request** — every authenticated mobile call performs a `personal_access_tokens` lookup. Negligible at our scale (< 10k wali users) but worth noting. Mitigated by Sanctum's built-in caching of the token lookup for the request lifetime.
- **No cross-domain SSO** — if we ever federate auth to another institution's wali portal, we will need to revisit (Passport or OIDC). Not a current requirement.
- **Token rotation logic lives in client** — the 30-day sliding window requires client-side refresh logic. If the mobile team fails to implement this, users get logged out at 30 days. Acceptable; documented in API contract.

### Neutral

- **Guardian Portal remains untouched** — its `WaliSantri.access_token` is a separate concern, intentionally not unified with mobile auth
- **Web GTK auth unchanged** — session-based, `Auth::attempt()`, unaffected

---

## 6. Alternatives considered

### A. JWT (current broken state)
Already analyzed in §2.6. Rejected.

### B. Passport OAuth2
Already analyzed in §2.7. Rejected.

### C. Laravel Sanctum SPA mode (cookie-based)
Sanctum's SPA mode uses first-party cookies + CSRF tokens. Designed for SPAs served from the same domain as the API. **Not applicable** to a React Native mobile app — cookies don't make sense for native HTTP clients. The token-bearer mode of Sanctum is the correct mode for native mobile.

### D. Session cookies for mobile (force web session model)
Treat the mobile app like a browser by issuing session cookies via the WebView cookie store. Rejected because:
1. Mobile HTTP libraries (axios on RN) handle cookies but iOS WKWebView and Android CookieManager each have separate stores — cross-platform consistency nightmare
2. Server has no signal for "is this a mobile session" vs "is this a web session" — leads to confusion in audit logs
3. The Guardian Portal already shows the cost of this: cookies have lifecycle issues on long-lived mobile installs

### E. Firebase Auth / Auth0 / external IdP
Outsourcing auth to a third party. Rejected because:
1. Indonesian parent users are not guaranteed to have a Google account that matches their wali identity
2. Adds latency and a third-party uptime dependency to every login
3. The "Google login" feature (already implemented in `AuthController::google()`) is an *additional* option, not the *only* option — parents without Google accounts can still register with email/password
4. Data residency concern — wali PII (NIK, no_kk) lives on Indonesian servers; offloading auth to a US-hosted IdP complicates this

---

## 7. Validation and rollout

### Validation steps before merge

1. **Code review** — verify all four JWT call sites in `AuthController` are removed; verify `ValidateMobileToken.php` is removed or rewritten
2. **Static analysis** — `grep -r "JWTAuth\|Tymon" app/ routes/` must return zero results
3. **Test** — write `tests/Feature/MobileAuthTest.php` covering: register issues Sanctum token, login issues Sanctum token, logout deletes token, token without `mobile:write-permit` gets 403 on permit endpoint, expired token gets 401, wrong abilities get 403
4. **Migration** — `personal_access_tokens` table exists from default Sanctum migration; verify it is present and indexed on `tokenable_id`

### Sprint 1 status (JWT removal + foundation)

| Sprint 1 outcome | Status |
|---|---|
| Remove all four JWT call sites from `AuthController` (register / login / google / me) | ✅ Done |
| Delete `app/Http/Middleware/ValidateMobileToken.php` (replaced by Sanctum guard) | ✅ Done |
| Add `App\Support\TokenExpiration` (single point of TTL access) | ✅ Done |
| Add `App\Support\TokenName` (5-segment colon-separated name format) | ✅ Done |
| Add `App\Support\AbilityRegistry` (role → abilities mapping with wildcard expansion) | ✅ Done |
| Wire `auth:sanctum` middleware on protected mobile routes | ✅ Done (routes/api.php) |
| Centralise TTL in `config/sanctum.php` (no hardcoded minutes in controllers) | ✅ Done |

Sprint 1 deliberately stops at the **foundation**: any request that previously returned a JWT now returns a Sanctum PAT with the canonical structured name, the role-derived abilities, and the operator-configured TTL. The hardening work — ability-based route authorisation (`abilities:*` middleware), PinReauthController for sensitive endpoints, the full test suite, and the Sanctum prune-expired scheduler — is explicitly scoped into Sprint 2+ and tracked in `mobile-api-gap-analysis.md`.

### Sprint 2 status (session management + brute-force lockout)

| Sprint 2 outcome | Status |
|---|---|
| `POST /api/mobile/v1/auth/logout-all` — revoke all tokens (used for "sign out everywhere") | ✅ Done |
| `GET /api/mobile/v1/auth/sessions` — list every active PAT for the authenticated user, with `current_device` flag | ✅ Done |
| `PATCH /api/mobile/v1/auth/sessions/current` — rename the current session (user-set device label) | ✅ Done |
| `DELETE /api/mobile/v1/auth/sessions/others` — revoke every PAT except the current one (matches Apple/Google "sign out other devices") | ✅ Done |
| Additive migration `2026_07_15_110000_add_device_metadata_to_personal_access_tokens` — adds `device_label`, `ip_last`, `fingerprint` columns to `personal_access_tokens` (NOT `platform` — platform is encoded in the `.name` segment; see §4 of `sanctum-token-architecture.md`) | ✅ Done |
| `App\Services\MobileSessionIntrospector` — single source of truth for session-list and describe logic | ✅ Done |
| Wire `User::incrementFailedLoginAttempts()` on failed login (was previously inert — dead code) | ✅ Done |
| Feature test `tests/Feature/MobileAuthSprint2Test.php` covering register / login / google / me / logout / sessions / update / revoke / 401 / expired / lockout / abilities / token-name / expiration-wiring | ✅ Done (composer-install blocker prevents local run; ready in repo) |
| `docs/sanctum-token-architecture.md` — full architecture summary (column meanings, helpers, response shape) | ✅ Done |

Sprint 2 stops at the **session-management surface**. The remaining hardening work — ability-based route authorisation (`abilities:*` middleware), PinReauthController for sensitive endpoints, and the Sanctum prune-expired scheduler — remains deferred to Sprint 3 and is tracked in `mobile-api-gap-analysis.md`.

### Rollout sequence

| Step | Action | Reversible? |
|---|---|---|
| 1 | Land ADR-018 | Yes (revert) |
| 2 | Remove JWT code from `AuthController.php`; replace with Sanctum token issuance | Yes (git revert) |
| 3 | Add `EnforceWaliAbilities` middleware | Yes |
| 4 | Add `PinReauthController` | Yes |
| 5 | Delete `ValidateMobileToken.php` | Yes (file restore) |
| 6 | Coordinate with mobile team: ship new app version that expects Sanctum tokens; force-relogin all existing JWT-token users (database check: `personal_access_tokens` is currently empty if no production mobile usage) | One-way for users but reversible at code level |
| 7 | Update `mobile-api-gap-analysis.md` documentation | Yes |

### Rollback

If Sanctum proves inadequate (unlikely, but possible if a future requirement forces OAuth2), the rollback is:
1. Install `laravel/passport`
2. Restore the JWT issuance code (kept in git history)
3. Migrate `personal_access_tokens` rows to Passport's `oauth_access_tokens` table
4. Re-issue all tokens

Estimated rollback effort: 2-3 days. Acceptable risk.

---

## 8. Open questions deferred to other ADRs

1. **Mobile secure storage of tokens** → [[ADR-019-mobile-secure-storage]] (to be written by Lead Mobile Engineer)
2. **Certificate pinning** → [[ADR-020-mobile-certificate-pinning]] (deferred until threat model finalized)
3. **Wali PIN requirements** (length, lockout policy) → [[ADR-021-wali-pin-policy]] (UX decision pending)
4. **Notification push authentication** (FCM tokens linking to user_id) → out of scope for this ADR

---

## 9. References

- `app/Models/User.php:12, :18, :137-145` — Sanctum trait and `secureAccessTokens()` relationship
- `app/Http/Controllers/SuperAdmin/TokenSesiController.php:68, :78` — `$tokenable->createToken(...)` canonical Sanctum usage
- `routes/api.php:27, :36, :44, :56` etc. — `auth:sanctum` middleware everywhere
- `composer.json:16` — `laravel/sanctum: ^4.0`
- `app/Http/Controllers/Api/Mobile/V1/AuthController.php:15, :44, :89, :137, :157` — the broken JWT call sites to be removed
- `app/Http/Middleware/ValidateMobileToken.php` — the orphan JWT middleware to be removed
- `WALI_SANTRI_API_SCHEMA.md:7` — already documents `Bearer {sanctum_token}`
- `docs/mobile-api-gap-analysis.md:64-67` — gap analysis incorrectly claiming JWT usage
- `app/Http/Controllers/GuardianPortalController.php:50-51` — `Auth::loginUsingId()` (separate Guardian Portal flow, out of scope)
- `app/Models/User.php:227-249` — `incrementFailedLoginAttempts`, `isLocked` (already-implemented brute-force lockout)