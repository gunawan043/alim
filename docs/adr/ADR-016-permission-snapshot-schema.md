# ADR-016: Permission Snapshot Schema

| Field        | Value                  |
|--------------|------------------------|
| Revision     | 1 (Architecture Board) |
| Status       | Accepted               |
| Date         | 2026-06-29             |
| Related ADRs | ADR-017, ADR-018, ADR-019, ADR-020, ADR-021 |

## Decision

Permission snapshots will be stored as an immutable table in PostgreSQL, backed by a three-table design consisting of:

- **permission\_snapshots**: stores the current permission snapshot for every combination of role, school, academic year, and context.
- **revoked\_permissions**: stores granular deny/override records which subtract permissions from a snapshot.
- **snapshot\_audit\_log**: records every snapshot mutation, creation, and deletion for traceability.

Snapshots are represented as JSONB objects, containing a hash-based scope key and an authorship fingerprint.

## Scope Key

Each snapshot is identified by a SHA256 hash derived from:

```
sha256(school_id + academic_year_id + dimension + context_path + role_id)
```

### Rationale

The scope hash eliminates storage duplication across rows that share identical scope attributes and simplifies the uniqueness constraint to a single indexed VARCHAR column. Composite columns increase the risk of index fragmentation across high-cardinality text-like IDs in PostgreSQL.

### Implementation

- A computed column or trigger populates the scope key before insert.
- Uniqueness enforced via unique constraint: `(scope_key)`.
- Query path uses `WHERE scope_key = $1` exclusively.
- The hash input order is deterministic.

## Fingerprint

Every mutation carries a fingerprint field recording the builder\_id (GTK user performing the operation), the timestamp, and the operation type.

### Trade-off: Persisted vs Calculated

**Decided: persisted.** The fingerprint is stored directly in the snapshot row alongside the payload.

**Rationale:**
- Enables audit queries such as "who modified these permissions last".
- Eliminates the need for joins to a separate audit trail for every permission calculation.
- Avoids re-computation in case of rebuild or cache miss.
- Adds ~96 bytes per snapshot row (acceptable given snapshot cardinality).

**Alternative: calculate only on rebuild.** This was rejected because:
- Rebuilds may occur hours or days apart.
- Audit queries must always answer "who changed what and when".
- Calculating on read is prohibitively expensive at scale.

## Snapshot Immutability

Snapshots are **immutable by design**. No UPDATE statement modifies an existing snapshot row. Mutations produce a new row (INSERT) with an incremented version number. The latest version is identified via the `is_current = TRUE` pointer scoped by `scope_key`; only one row per `scope_key` may hold that flag.

### Why Immutable?

1. **Auditability**: Every mutation is traceable via INSERT-only semantics.
2. **Event sourcing alignment**: Snapshots reflect a point-in-time state that corresponds to a deterministic event log.
3. **Cache friendliness**: Immutable rows eliminate race conditions between reads and writes.
4. **Rebuild safety**: A corrupted snapshot can be rolled back to a previous version row.

### Counter-argument Considered: Snapshots as Mutable Cache

If snapshots were treated as mutable cache (i.e., overwritten on updates):
- Every update destroys the audit trail.
- Rebuild from events loses the intermediate states.
- Rollbacks require separate history tracking.
- Race conditions between concurrent writes become possible.

**Conclusion: immutability is structurally required.**

## Revocation Override Semantics

When both a granted permission and an explicit revocation exist for the same scope, the explicit revocation **always overrides** the granted permission.

### Precedence Rules

1. **Snapshot payload** contains all permissions granted through the role, school, academic year, and dimension chain.
2. **Revoked permissions table** contains explicit deny records for individual permission IDs within a scope.
3. **Resolution algorithm**: `effective_permissions = snapshot_payload - revoked_permissions`.
4. **Conflict resolution**: For any permission ID present in both tables, the revoked\_permissions entry wins.
5. **Scope-bound**: Revocation in one scope (school + academic\_year + dimension) does not affect another scope.

### Example

```
Snapshot grants: { read_students, edit_students, delete_students }
Revocations:    { delete_students }
Effective:      { read_students, edit_students }
```

### Design Constraint

No future architectural change may reverse this precedence. A granted permission must never silently cancel an explicit revocation.

---

## Concurrency Control

Concurrent snapshot builders operating on the same scope\_key must not produce duplicate rows.

### Strategy: PostgreSQL Advisory Lock

The snapshot builder acquires a transaction-scoped advisory lock before beginning rebuild:

```sql
SELECT pg_advisory_xact_lock(hashtext($scope_key));
```

`pg_advisory_xact_lock` is used because:

1. **Transaction-scoped**: the lock is released automatically at COMMIT or ROLLBACK; no manual unlock required.
2. **Deadlock-free**: no nested lock acquisition; only one lock per rebuild cycle.
3. **Fast**: advisory locks are implemented in PostgreSQL's lock manager, not as table locks.

### Duplicate Prevention Flow

1. Builder A and Builder B target the same scope\_key simultaneously.
2. Builder A acquires the advisory lock first.
3. Builder B waits on `pg_advisory_xact_lock`.
4. Builder A completes INSERT, COMMIT releases lock.
5. Builder B acquires lock, checks `is_current = TRUE` snapshot count.
6. If Builder B sees its own INSERT already committed, it skips duplicate INSERT.
7. If Builder B sees no snapshot yet, it proceeds with INSERT.

### Retry Behavior

If an advisory lock timeout occurs (external deadlock from an unrelated long-running transaction), the builder retries with exponential back-off:

```
retry_1: 1s
retry_2: 2s
retry_3: 4s
max_retries: 3
```

After max retries, the builder emits to the dead-letter queue for manual replay.

---

## Snapshot Rebuild Strategy

### Trigger Events

A snapshot rebuild is triggered when any of the following events occurs:

1. Role assignment or removal (GTK user action).
2. Permission definition change (via RoleSynchronizationService).
3. Academic year change affecting scope.
4. Manual rebuild request (GTK admin action).
5. Drift detection (weekly cron detects runtime vs snapshot mismatch).

### Rebuild Flow

The rebuild process follows a strict, deterministic pipeline:

```
Event
  ↓
Builder (RolePermissionBuilder::rebuild)
  ↓
New Snapshot Row (INSERT with version +1)
  ↓
Old Snapshot becomes inactive (is_current = FALSE)
  ↓
Cache Invalidated (PermissionResolver flushes scope_key from cache)
```

### Constraints

- No UPDATE of existing snapshot rows. Ever.
- No in-place modification of JSONB payload.
- Builder must produce the full permission tree; partial merges are not allowed.
- Snapshot\_audit\_log record is written for every INSERT, capturing fingerprint fields.

### Append-Only Behavior

All snapshot mutations produce new rows. Old rows remain readable via `version < current_version`.

### Builder Execution

- Builder runs in a single-threaded worker context per scope\_key (guaranteed by advisory lock).
- Parallel builds across different scope\_keys are allowed.
- Builder is idempotent: rebuild of the same event produces the same snapshot payload.

### Cache Invalidation

- After successful INSERT, the PermissionResolver service invalidates the cache for the affected scope\_key.
- Invalidated cache entries expire immediately; stale reads are resolved from the new snapshot row.
- Cache key: `perm_snapshot:{scope_key}:{version}`

### Retry Behavior

If the builder fails mid-execution (DB error, timeout):

1. No snapshot row is INSERTed (transaction rolls back).
2. Rebuild is queued for retry with exponential back-off (same as concurrency retry).
3. If all retries exhausted, event is replayed from the event log at the next available window.

---

## Migration Strategy

### Migration Order

1. **Step 1**: Create `permission_snapshots` table (primary structure).
2. **Step 2**: Create `revoked_permissions` table (foreign key to permission\_snapshots).
3. **Step 3**: Create `snapshot\_audit\_log` table.
4. **Step 4**: Create `permission\_snapshots\_archive` table (empty skeleton, populated by post-migration cron).
5. **Step 5**: Deploy snapshot builder service (behind feature flag).
6. **Step 6**: Seed initial snapshots from existing role data.
7. **Step 7**: Enable snapshot resolver behind feature flag toggle.
8. **Step 8**: Deprecate direct Spatie RoleSynchronizationService writes.

### Dependencies

- Step 5 depends on Step 1–4 (tables exist).
- Step 6 depends on Step 5 (builder must exist to seed).
- Step 7 depends on Step 6 (initial snapshots must exist before resolver is enabled).
- Step 8 depends on Step 7 (resolver must be proven stable for at least one academic cycle).

### Rollback Order (Reverse)

1. Disable snapshot resolver (rollback Step 7).
2. Drop initial snapshots (rollback Step 6).
3. Disable snapshot builder (rollback Step 5).
4. Drop `snapshot_audit_log` table (rollback Step 3).
5. Drop `revoked_permissions` table (rollback Step 2).
6. Drop `permission_snapshots` table (rollback Step 1).

Each rollback step must be tested in staging before production execution.

---

## Retention

- **Soft-delete retention**: 90 days. After 90 days, old snapshot versions are archived via a background job.
- **Archive strategy**: Archived rows are moved to a `permission_snapshots_archive` table on a monthly cron job.
- **Hard delete**: Archive data older than 365 days is purged.

### Why Archive Table?

- **Partitioning vs Archive**: Partitioning solves query performance, not compliance archival. Regulations and school accreditation requirements mandate historical retention independent of query speed.
- **Storage cost**: Archived data rarely queried; moving to cheaper storage (or a separate table with a less aggressive indexing strategy) reduces cost.

## JSONB Justification

### Why JSONB?

- Permissions are inherently hierarchical and variable in shape (role A may have different permission sets than role B).
- Relational normalization would require a separate table per permission dimension, increasing join complexity.
- JSONB enables partial updates via GIN indexes while keeping schema flexibility.

### Indexing Strategy

```sql
CREATE INDEX idx_snapshots_scope ON permission_snapshots(scope_key);
CREATE INDEX idx_snapshots_payload ON permission_snapshots USING gin(payload);
CREATE INDEX idx_revocations ON revoked_permissions(permission_id);
```

## Data Types

| Column | Type | Nullable | Default | Notes |
|--------|------|----------|---------|-------|
| id | BIGSERIAL | NO | — | Primary key |
| scope_key | CHAR(64) | NO | — | SHA256 hex digest |
| scope_hash_algorithm | VARCHAR(16) | NO | 'SHA256' | Algorithm identifier |
| payload | JSONB | NO | — | Permission tree |
| version | SMALLINT | NO | 1 | Incremental version |
| is_current | BOOLEAN | NO | TRUE | Soft pointer to latest |
| fingerprint_builder_id | BIGINT | NO | — | Who performed the operation |
| fingerprint_timestamp | TIMESTAMPTZ | NO | NOW() | When operation occurred |
| fingerprint_operation | VARCHAR(32) | NO | — | CREATE, UPDATE, ROLLBACK |
| created_at | TIMESTAMPTZ | NO | NOW() | Row creation time |
| deleted_at | TIMESTAMPTZ | YES | NULL | Soft delete marker |

## Relationship to Existing Audit System

The `snapshot_audit_log` table is **independent**. It does not reuse any existing application audit infrastructure because:

1. Performance isolation: snapshot audit queries must never compete with application audit queries.
2. Schema independence: snapshot audit schema is tightly coupled to the permission domain; mixing it with generic audit tables would dilute both.
3. Retention policy divergence: snapshot audit data follows a different lifecycle than general application audit data.

## Future Evolution

This design supports:

- **Multi-school**: scope_key incorporates school_id.
- **Multi-campus**: additional dimension field accommodates campus_id.
- **Distributed queues**: immutability eliminates write-write conflicts.
- **Multiple workers**: version-based optimistic concurrency control (no row locks needed).
