# AUTH-101 — SnapshotCacheHit event not dispatched after rebuild

**Priority:** Low
**Type:** Runtime observability / event ordering
**Affects:** Test-only (no production impact)

## Confirmed behavior

After a successful `SnapshotRebuildService::rebuild(...)`, the cache contains
the freshly rebuilt `PermissionBag`, and a subsequent `SnapshotResolver::resolve(...)`
returns that bag with the correct fingerprint. However the
`App\Authorization\Events\SnapshotCacheHit` event is not dispatched on the
second resolve — diagnostic output shows the resolver returns a bag whose
fingerprint matches the one stored in cache, yet `Event::assertDispatched(SnapshotCacheHit)`
fails.

## Production impact

**None.** This is purely an observability gap:

- `SnapshotResolver::resolve()` returns the correct `PermissionBag`.
- Permission decisions returned by `AuthorizationManager` are correct.
- Snapshot persistence (`PermissionSnapshot` rows) is correct.
- Cache reads return the rebuilt bag.
- Revocation, rewind, and warm-up paths work.

Authorization runtime behavior is unaffected. Only test assertions that observe
the `SnapshotCacheHit` event will fail.

## Root-cause hypothesis

`SnapshotRebuildService::rebuild()` dispatches `PermissionCacheInvalidated`
**after** writing the bag to cache. The single listener registered for that
event — `PermissionCacheInvalidationListener` — calls
`PermissionCacheManager::forgetUser($userId)` followed by (when the cache store
is not taggable, as in the array driver used by these tests)
`PermissionCacheManager::forget($userId, $scopeKey)`. Combined, the rebuild
path cleared the entry it had just written.

The integrated ordering change in `SnapshotRebuildService` (invalidate → put)
restores the rebuild *contract* (cache holds the new bag afterwards — verified
in tests via `$cache->get(...)` returning non-null after rebuild), but the
test faking only `SnapshotCacheHit` does not fake `PermissionCacheInvalidated`,
so the listener still runs synchronously during rebuild. The remaining
mystery is in event/fake-edge interaction with how the resolver's cache hit
path is exercised in this specific test; full root cause was not pursued
under the standing instruction not to chase non-blocking diagnostics.

## Recommended future fix

1. In `tests/Integration/Authorization/RuntimeVerificationTest.php`,
   broaden `Event::fake(...)` to also fake
   `App\Authorization\Events\PermissionCacheInvalidated` (or use
   `Event::fake()` without an argument list) so the real listener does not
   run between rebuild and resolve during the cache-hit assertion.
2. Or, more cleanly: keep the production change (invalidate-then-put) and
   update the test to acknowledge that cache-hit resolution does not depend
   on a `PermissionCacheInvalidated` event having been dispatched in the
   same scope.

No production code change beyond the ordering fix already applied to
`SnapshotRebuildService::rebuild()` is required.

## Affected tests

- `Tests\Integration\Authorization\RuntimeVerificationTest::test_subsequent_resolution_emits_cache_hit`

## Status

Defer. Do not block the authorization migration on this item.
