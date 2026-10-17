# IMP-001 — Enterprise Modernization Backlog & Sprint Planning
# Enterprise AI Engineering Framework — Sarpras Platform
# Version: 1.0
# Date: 2026-07-01
# Classification: Confidential — CTO / Technical Steering Committee
# Prerequisite Documents: BR-001 (Business Requirements), EA-002 (Blueprint V2)

================================================================================
# DOCUMENT PURPOSE
================================================================================

This document transforms every finding from the Enterprise Architecture
Assessment, Blueprint V2, and Technical Audit into an EXECUTABLE modernization
backlog. Each item has business problem, technical root cause, acceptance
criteria, and rollback strategy.

Backlog items are grouped into 6 sprints that mirror the criticality ladder:
1. Production Safety (crisis stabilization)
2. Architecture Stabilization (technical foundation)
3. Business Completeness (capability gaps)
4. Performance Optimization (scalability)
5. API & Mobile Integration (platform expansion)
6. Testing & Production Readiness (quality gate)

================================================================================
# GLOSSARY
================================================================================

| Abbreviation | Meaning                                          |
|-------------|--------------------------------------------------|
| NFR         | Non-functional requirement                        |
| SoD         | Segregation of duties                             |
| SLA         | Service level agreement                           |
| MTTR        | Mean time to resolve                              |
| SQLi        | SQL injection                                     |
| BC/DR       | Business continuity / Disaster recovery           |
| RTO/RPO     | Recovery time / Recovery point objective          |
| COALESCE    | Laravel relationship eager-loading helper         |
| RBAC        | Role-based access control                         |
| CVE         | Common Vulnerabilities and Exposures              |
| XSS         | Cross-site scripting                              |

================================================================================
# PART 1 — MASTER BACKLOG
================================================================================

Every item includes: ID, Title, Business Problem, Technical Problem,
Root Cause, Target Outcome, Priority, Complexity, Risk, Dependencies,
Acceptance Criteria, Verification Steps, and Rollback Strategy.

────────────────────────────────────────────────────────────────────────────
SPRINT 1 — CRITICAL PRODUCTION SAFETY
────────────────────────────────────────────────────────────────────────────

────────────────────────────────────────────────────────────────────────────
TASK-1.01: Eliminate Information Disclosure in Error Responses
────────────────────────────────────────────────────────────────────────────
ID: TASK-1.01
Title: Lock down PII-leaking error responses in GtkController
Business Problem:
  The system exposes full user profiles (NIU, NUPTK, NIK, addresses, birth
  data, positions) whenever a malformed or invalid UUID is supplied to any
  GtkController endpoint. This is a direct data-breach violation of Indonesian
  personal data protection requirements.
Technical Problem:
  App\Exceptions\Handler::register() has a dedicated case for ModelNotFoundException
  that dumps a JSON response containing `$e->getModel()`, the invalid ID, the
  full `$user` object (including password_hash, personal_data JSON, relationship
  counts), and the request path. Any HTTP 404 on `/gtk/{uuid}` returns:
    {"error":"Not Found","details":{"model":"App\\\\Models\\\\User","id":"invalid-uuid","user":{"id":1,"niu":"000010020005","nuptk":"...",
     "nik":"...", "birth_place":"...", "birth_date":"...", ...}}}
Root Cause:
  Developer convenience error handler left in production code. The handler was
  designed for debugging during development and never removed before deployment.
Target Outcome:
  All API error responses MUST return a correlation ID only. PII, stack traces,
  model names, and internal state MUST NEVER reach the client.
Priority: P0
Complexity: S
Estimated Risk: LOW — only changes error-formatting logic
Dependencies: None
Acceptance Criteria:
  [ ] ModelNotFoundException handler returns only: {"error": "...", "reference": "<uuid>"}
  [ ] No personal_data, niu, nuptk, nik, or relationship counts in any response
  [ ] Internal Log::error() call preserves full diagnostic info
  [ ] Other endpoints (e.g., asset show, procurement show) do not leak data via 404
  [ ] Production test: curl -s http://localhost/gtk/non-uuid returns safe error
Verification Steps:
  1. Send request to /gtk/invalid-uuid-123 and verify response body is safe
  2. Send request to /api/v1/gtk/invalid-uuid-123 and verify response body is safe
  3. Inspect Laravel logs to confirm full error details ARE still logged internally
  4. Audit all other Controllers for similar try/catch blocks exposing model/user data
Rollback Strategy:
  Single-file change to App\Exceptions\Handler.php. Rollback: git checkout HEAD~1
  The old handler is harmless (just verbose) — no functional regression.
────────────────────────────────────────────────────────────────────────────
SPRINT 2 — ARCHITECTURE STABILIZATION
────────────────────────────────────────────────────────────────────────────

────────────────────────────────────────────────────────────────────────────
TASK-2.01: Introduce Sarpras Service Layer
────────────────────────────────────────────────────────────────────────────
ID: TASK-2.01
Title: Extract business logic from Sarpras controllers into a Service layer
Business Problem:
  Controllers in the Sarpras module perform both request-handling AND
  business logic (transactions, calculations, cross-model coordination).
  This coupling makes it impossible to reliably invoke business rules from
  background jobs, APIs, or scheduled tasks — limiting every future capability
  (notifications, automation, mobile API) to web-request contexts.
Technical Problem:
  13 Sarpras controllers average 250 lines each, with 10+ methods doing
  database writes, validations, cross-model queries, and view data preparation
  in a single method. Examples:
  - SarprasProcurementController@store() handles validation, school lookup,
    procurement creation, item creation, redirect — all in one method
  - SarprasAsetController@store() creates assets with conditional asset_code
    generation, room validation, photo handling
  - SarprasLoanController@handover() checks loan status, validates request body,
    updates loan state, and returns views — mixed HTTP + business logic
Root Cause:
  Laravel scaffolding convention was followed literally: controllers became
  the de facto application tier. No explicit separation between HTTP
  presentation and domain logic was ever established.
Target Outcome:
  All business logic for Sarpras modules is extracted into dedicated Service
  classes. Controllers handle only: input validation, service invocation,
  response formatting. Services handle: transactions, business rules,
  cross-model coordination.
Priority: P1
Complexity: L
Estimated Risk: MEDIUM — behavioral change across 13 controllers
Dependencies: None (but is a prerequisite for API, Mobile, Automation tasks)
Acceptance Criteria:
  [ ] SarprasProcurementService exists with create(), approve(), reject(),
      receive(), convertToAsset() methods
  [ ] SarprasAsetService exists with register(), update(), archive(),
      import(), export() methods
  [ ] SarprasLoanService exists with request(), approve(), handover(), returnAsset()
  [ ] All 13 controllers delegate to services — no business logic remains
  [ ] Existing test suite still passes after refactoring
  [ ] Service classes are unit-testable without HTTP context
Verification Steps:
  1. Run all existing Sarpras CRUD flows and verify behavior is unchanged
  2. Confirm service methods can be called from tinker/console commands
  3. Confirm zero controller method exceeds 30 lines after refactoring
  4. PHPUnit test: mock each service and verify controller passes through correctly
Rollback Strategy:
  Each service maps 1:1 to controller methods. Rollback: revert the
  Controller → Service extraction commits. Controllers work independently.
────────────────────────────────────────────────────────────────────────────
TASK-2.02: Consolidate Sarpras Authorization
────────────────────────────────────────────────────────────────────────────
ID: TASK-2.02
Title: Replace ad-hoc role checks with formal Laravel Policies
Business Problem:
  Authorization for Sarpras features uses mixed, inconsistent patterns: some
  areas rely on middleware (`role:Admin Sarpras`), others check roles inline,
  and some have no explicit authorization. This creates authorization gaps
  where a user with the wrong role can access or modify data.
Technical Problem:
  1. Routes use `middleware(['auth', 'role:Admin Sarpras,Admin Tata Usaha'])` —
     a single middleware controls ALL Sarpras routes with no granularity
  2. No Laravel Policies exist for Asset, ProcurementRequest, AssetLoan, etc.
  3. School data isolation is delegated to base controller's scopeToSchool()
     but has a documented edge-case: if userId is present, school context
     override is skipped
  4. SarprasUserController has 24 methods but NO corresponding policy
Root Cause:
  Rapid development prioritized feature delivery over authorization rigor.
  The single-role-middleware pattern was chosen because it was easiest to
  implement, not because it satisfied the SoD requirements in BR-001.
Target Outcome:
  Every Sarpras model has a Laravel Policy enforcing:
  - Role-based capability access (who can do what)
  - School-scoped data isolation (which records are visible)
  - SoD constraints (who cannot do what in same workflow)
Priority: P1
Complexity: M
Estimated Risk: LOW-MEDIUM — restrictive policies may block legitimate actions
Dependencies: TASK-2.01 (service layer provides clean boundary for policy checks)
Acceptance Criteria:
  [ ] AssetPolicy, ProcurementRequestPolicy, AssetLoanPolicy, BookingPolicy exist
  [ ] Each policy enforces school scoping via Auth::user()->schools() check
  [ ] Middleware `role:` is replaced by route-level `authorize()` or `can()` calls
  [ ] Edge case: userId presence no longer bypasses school context
  [ ] All 13 controllers use policy checks for sensitive operations
Verification Steps:
  1. Auth as Admin Sarpras of School A, attempt to access School B's data → BLOCKED
  2. Auth as General User, attempt procurement approval → BLOCKED
  3. Auth with userId override, verify school context still enforced
  4. PHPUnit policy tests: each Policy@* method tested with positive/negative cases
Rollback Strategy:
  Policies are additive. Revert: disable policies in controllers and restore
  role-based middleware. Functionality unchanged — authorization loosened.
────────────────────────────────────────────────────────────────────────────
TASK-2.03: Database Index Audit & Migration Safety
──────────────────────────────────────────────���─────────────────────────────
ID: TASK-2.03
Title: Audit and fix database indexes for performance-critical queries
Business Problem:
  Without proper indexes, search, filter, and join queries degrade to full
  table scans as data grows. This directly threatens the V2 scalability
  target of 5,000+ assets and the dashboard response-time SLA (< 2s p95).
Technical Problem:
  1. Large tables (assets, procurement_requests, asset_loans) lack indexes on
     frequently queried columns (school_id, room_id, status, request_date)
  2. No covering indexes for common query patterns (WHERE + ORDER BY + SELECT)
  3. Migration execution is untested in production-like environments
  4. Foreign key relationships defined in models but not enforced in schema
Root Cause:
  Schema evolution happened organically via manual migrations without an
  indexing strategy. Development database sizes are too small to expose
  performance gaps.
Target Outcome:
  Every query identified in BR-001 Section 6.1 (Performance) has supporting
  indexes. All foreign keys are enforced at the database level. Migration
  execution follows a safe, reversible pattern.
Priority: P1
Complexity: M
Estimated Risk: LOW — index additions are non-breaking
Dependencies: None
Acceptance Criteria:
  [ ] Composite index on assets(school_id, status, created_at)
  [ ] Index on procurement_requests(school_id, status, request_date)
  [ ] Index on asset_loans(school_id, status, borrower_id, return_date)
  [ ] All foreign keys defined in migrations (not just model relationships)
  [ ] EXPLAIN ANALYZE on top 10 queries shows index usage
  [ ] Zero migration takes > 30 seconds on production-sized dataset (10k rows)
Verification Steps:
  1. php artisan db:optimize-indexes (or equivalent migration)
  2. Run EXPLAIN on: Asset::where('school_id', $id)->where('status', 'aktif')
  3. Verify dashboard queries use indexes via Laravel Debugbar query panel
  4. Load 10k test records and re-measure query times
Rollback Strategy:
  DROP INDEX is safe and instantaneous. Rollback: revert migration, indexes
  are removed. Application falls back to full scans (known to be slow).
────────────────────────────────────────────────────────────────────────────
TASK-2.04: Enforce Database Transaction Integrity
──────────────────────────────────────────────────────────��─────────────────
ID: TASK-2.04
Title: Wrap all multi-statement database operations in explicit transactions
Business Problem:
  Partial writes corrupt business data. If a procurement request is created
  but its items fail to save (validation error, constraint violation), the
  orphaned request exists with no items — creating a misleading "pending"
  item in the system. Similarly, asset loans without proper transaction
  boundaries can leave assets in limbo (marked as "borrowed" but the
  loan record is incomplete).
Technical Problem:
  Several controller methods perform multiple DB writes without transaction
  wrapping. The following methods are HIGH-RISK for partial writes:
  - ProcurementController@store(): creates ProcurementRequest + N items in one request
  - LoanController@handover(): updates loan status + potentially creates maintenance log
  - AsetController@store(): creates asset + optional photos + room association
Root Cause:
  Laravel's implicit transaction handling was relied upon without explicit
  db::transaction() wrapping. During rapid iteration, some methods had
  transactions added then removed when debugging.
Target Outcome:
  Every controller/store/update/delete method performing > 1 DB write is
  wrapped in an explicit DB transaction with proper retry handling for
  deadlock scenarios.
Priority: P0
Complexity: S
Estimated Risk: LOW — transactions improve data integrity
Dependencies: None
Acceptance Criteria:
  [ ] All methods with 2+ DB writes use DB::transaction()
  [ ] Deadlock retry (5 attempts) configured for high-concurrency operations
  [ ] Partial-write scenario tested and verified to roll back completely
  [ ] Transaction scope is visible in method signatures (code review)
Verification Steps:
  1. Force a constraint violation mid-transaction and verify entire operation rolls back
  2. Simulate deadlock and verify retry succeeds
  3. Code review: every store/update/delete method inspected for transaction coverage
Rollback Strategy:
  Removing transaction() wrapper is safe (it was the previous state). Rollback:
  revert the transaction addition commits. Risk: partial writes possible.
────────────────────────────────────────────────────────────────────────────
SPRINT 3 — BUSINESS COMPLETENESS
──────────────────────────────────────────────────────────────��─────────────

────────────────────────────────────────────────────────────────────────────
TASK-3.01: Implement Notification Engine
────────────────────────────────────────────────────────────────────────────
ID: TASK-3.01
Title: Build event-driven notification engine for all workflows
Business Problem:
  BR-001 mandates event-driven notifications for all state transitions,
  deadlines, and anomalies (BR-NOT-001). Currently, the system has ZERO
  notification infrastructure — users never receive alerts about approvals,
  overdue loans, warranty expirations, or budget consumption. This forces
  manual follow-up for every workflow step, reducing operational efficiency
  by an estimated 40%.
Technical Problem:
  1. No notification channel abstraction exists
  2. Laravel Notification system is not used anywhere in the Sarpras module
  3. No event/listener pattern is established for state transitions
  4. Background queue worker may not be configured for mail/SMS delivery
Root Cause:
  Notification system was deferred as a "nice-to-have." No state machine
  exists to emit events because controllers change model state directly
  without dispatching domain events.
Target Outcome:
  A notification system with:
  - Laravel events emitted on every state transition
  - Channel abstraction (in-app, email, SMS-ready)
  - Throttling (max N notifications per recipient per hour)
  - Quiet hours for non-urgent notifications
  - Digest mode for low-priority batched notifications
Priority: P0
Complexity: M
Estimated Risk: LOW — additive feature, no existing code affected
Dependencies: TASK-2.01 (services emit events at domain boundary)
Acceptance Criteria:
  [ ] Events: AssetRegistered, LoanRequested, ProcurementApproved,
      MaintenanceOverdue, WarrantyExpiring, BudgetAlert
  [ ] In-app notifications visible to recipients within 1 minute
  [ ] Email notifications sent via configured mail queue
  [ ] Throttle: max 10 notifications per user per hour (configurable)
  [ ] Quiet hours: non-urgent notifications batched for business hours
Verification Steps:
  1. Create a loan request → verify notification appears in recipient inbox
  2. Wait 30 rapid loan requests from same user → verify throttling
  3. Send notification outside business hours → verify quiet hours
  4. Queue worker processes email → verify sent item
Rollback Strategy:
  Notifications are side-effects. Remove event listeners and channel
  subscribers. Core workflow continues without notifications.
────────────────────────────────────────────────────────────────────────────
TASK-3.02: Asset Lifecycle State Machine
────────────────────────────────────────────────────────────────────────────
ID: TASK-3.02
Title: Formalize asset lifecycle states and transitions
Business Problem:
  Current asset model has a simplistic status field that does not distinguish
  between "in use", "being maintained", "under audit", "disposed", or
  "retired." BR-001 requires a formal lifecycle (PLANNED → ACTIVE →
  MAINTENANCE → UNDER_AUDIT → DISPOSAL_REQUESTED → DISPOSED → RETIRED)
  with immutability on disposed assets and mandatory archival for 5 years.
Technical Problem:
  1. Asset model uses a flat status enum without transition validation
  2. No disposal workflow exists — assets can be deleted (soft-delete via
     Laravel'sSoftDeletes trait) instead of formally retired
  3. No audit trail captures lifecycle state changes
  4. Retrieved condition on asset update overwrites original acquisition
     condition data
Root Cause:
  Original implementation treated asset status as a simple label, not a
  state machine. The SoftDeletes trait was added for convenience but
  violates the "no-deletion" requirement in BR-001 BR-ASM-001.
Target Outcome:
  A formal state machine governing asset lifecycle transitions. Deleted
  assets are migrated to archived status. Lifecycle changes are logged
  in an immutable audit trail.
Priority: P0
Complexity: M
Estimated Risk: LOW — state machine is additive
Dependencies: TASK-2.01 (service layer hosts state machine logic)
Acceptance Criteria:
  [ ] Asset states: planning, acquired, active, maintenance, under_audit,
      disposal_requested, disposed, retired
  [ ] Invalid transitions are rejected with meaningful error messages
  [ ] Soft-deleted assets are migrated to "disposable" state
  [ ] All state transitions logged to audit trail
  [ ] Disposed assets are read-only (no updates except archival references)
Verification Steps:
  1. Attempt invalid transition (e.g., retired → active) → rejected
  2. Trigger valid transition (active → maintenance) → audited
  3. Query disposed assets → confirm read-only enforcement
  4. Audit trail query shows complete transition history
Rollback Strategy:
  State machine can be disabled by removing transitions from models.
  Assets revert to flat status field. Data loss: none.
────────────────────────────────────────────────────────────────────────────
TASK-3.03: Formal Procurement Approval Chain
────────────────────────────────────────────────────────────────────────────
ID: TASK-3.03
Title: Implement multi-level approval workflow for procurement
Business Problem:
  Current procurement has only a single "pending → approved/rejected"
  state. BR-001 requires multi-level approval with role-based approval
  authority and segregation of duties. Specifically: requester cannot
  be the same person as the approver. Budget allocation validation is
  missing — requests exceeding budget are not blocked.
Technical Problem:
  1. SarprasProcurementController@store() sets status to 'pending' with no
     initial reviewer
  2. approve()/reject() methods accept any authenticated user as approver
  3. No budget_allocation table or check exists
  4. No approval_history or audit trail for procurement decisions
Root Cause:
  The procurement feature was delivered with a minimal approval step rather
  than the multi-level workflow specified in the blueprint. Budget tracking
  was deferred as a "finance module" concern.
Target Outcome:
  Procurement approval follows a configurable multi-level chain:
  Department Head → Finance Review → (if above threshold) Deputy Principal.
  SoD enforced: requester ≠ approver at every level. Budget consumption
  checked before approval.
Priority: P0
Complexity: L
Estimated Risk: MEDIUM — approval flow changes may block existing workflows
Dependencies: TASK-2.02 (policy for SoD), TASK-3.01 (notification on approval)
Acceptance Criteria:
  [ ] Approval levels configurable per institution/threshold
  [ ] Requester cannot approve their own request
  [ ] Budget allocation checked before each approval step
  [ ] Approval rejection requires mandatory reason field
  [ ] Rejected requests cannot proceed to ordered state
  [ ] Approval history logged with approver, timestamp, comment
Verification Steps:
  1. Submit request as User A, attempt to approve as User A → BLOCKED
  2. Submit request exceeding budget → flagged at approval step
  3. Reject request without reason → field validation error
  4. Approve through 3 levels → verify full history in audit trail
Rollback Strategy:
  Multi-level approval can be collapsed to single-level by reverting config.
  Revert: disable approval_chain feature flag.
────────────────────────────────────────────────────────────────────────────
TASK-3.04: Asset QR Code Standardization
──────────────────────────────────────────���─────────────────────────────────
ID: TASK-3.04
Title: Shift QR codes from UUID encoding to asset_code encoding
Business Problem:
  Current QR codes encode the asset UUID, which is fragile across data
  migrations and incompatible with barcode scanners that expect human-readable
  codes. BR-001 requires QR codes to encode the asset_code (e.g., IT-26-00042)
  so scanning is resilient to internal ID changes.
Technical Problem:
  1. Existing QR codes in the database contain UUIDs
  2. Asset code generation is manual/not standardized (PREFIX-YEAR-SEQ)
  3. QR scanner route resolves by UUID, not by asset_code
  4. No barcode (Code128) fallback exists
Root Cause:
  Original implementation used UUID as the only identifier. Asset coding
  policy was defined later in BR-001 but not implemented retroactively.
Target Outcome:
  All new and existing QR codes encode asset_code. Scanning resolves to
  the asset record via asset_code lookup. Barcode fallback available.
Priority: P1
Complexity: M
Estimated Risk: LOW — backward-compatible if scanner accepts both formats
Dependencies: TASK-2.03 (indexes on asset_code for fast lookup)
Acceptance Criteria:
  [ ] New assets generate policy-driven codes: PREFIX-YEAR-SEQ
  [ ] QR codes contain only the asset_code string
  [ ] Scanner accepts both asset_code and UUID (backward compatible)
  [ ] Existing QR codes regenerated with asset_code
  [ ] Barcode (Code128) generation available as fallback
Verification Steps:
  1. Generate QR for new asset → decode → verify it contains asset_code not UUID
  2. Scan existing UUID-based QR → resolves to correct asset
  3. Scan new asset_code-based QR → resolves to correct asset
  4. Bulk regenerate all QR codes → verify all decode to asset_code
Rollback Strategy:
  QR content is metadata. Revert: restore UUID encoding in QR generation.
  Scanning still works via UUID resolution. Physical QR labels need reprint.
────────────────────────────────────────────────────────────────────────────
SPRINT 4 — PERFORMANCE OPTIMIZATION
────────────────────────────────────────────────────────────────────────────

────────────────────────────────────────────────────────────────────────────
TASK-4.01: Query Optimization & N+1 Elimination
────────────────────────────────────────────────────────────────────────────
ID: TASK-4.01
Title: Identify and resolve N+1 queries in Sarpras controllers and views
Business Problem:
  Dashboard and list pages load slowly due to N+1 queries — fetching related
  data in loops instead of eager loading. With 10k+ assets target, current
  pagination + N+1 pattern will exceed the < 2s page-load SLA.
Technical Problem:
  Controllers use with() but Blade views often access relationships without
  ensuring they're loaded. Typical patterns:
  - Loop through assets → access asset.room.name → triggers per-row query
  - Loop through procurement items → access item.category → N+1
  - Dashboard aggregates across all entities without cached query results
Root Cause:
  Laravel eager-loading was applied at the controller level but views
  introduce additional relationship accesses outside the query context.
  No query profiling exists to identify N+1 patterns.
Target Outcome:
  Zero N+1 queries on any Sarpras page. All queries verified via
  Laravel Debugbar or model-level N+1 monitoring. Page loads under SLA.
Priority: P1
Complexity: M
Estimated Risk: LOW — query optimization is always beneficial
Dependencies: TASK-2.03 (indexes supporting optimized queries)
Acceptance Criteria:
  [ ] Laravel Debugbar shows 0 N+1 queries on every Sarpras page
  [ ] Dashboard page load < 2s with 10k test records
  [ ] Asset list (paginated, 15/page) < 1s with 5k records
  [ ] Procurement index < 2s with 2k records
  [ ] Query count per page < 20 (including sub-queries)
Verification Steps:
  1. Install laravel-debugbar, enable query logging
  2. Hit every Sarpras page → verify N+1 = 0
  3. Load test with factory-generated data at target sizes
  4. Compare query count before/after optimization
Rollback Strategy:
  Reverting eager-loading changes may reintroduce N+1 but will NOT break
  functionality — only performance. Safe rollback.
────────────────────────────────────────────────────────────────────────────
TASK-4.02: Dashboard Caching Layer
────────────────────────────────────────────────────────────────────────────
ID: TASK-4.02
Title: Implement caching for dashboard aggregate queries
Business Problem:
  Dashboard queries compute aggregates (total_assets, active_loans,
  pending_approvals, maintenance_overdue) from scratch on every load.
  These aggregations don't change second-by-second — they change on
  transactions. Recomputing them every page view wastes CPU and database
  I/O.
Technical Problem:
  DashboardController@index() runs multiple COUNT/WITH aggregations on
  large tables. No caching strategy exists. Every page load triggers full
  scan of aggregates.
Root Cause:
  No caching abstraction was defined. The dashboard is treated as a
  simple query aggregation rather than a pre-computed snapshot.
Target Outcome:
  Dashboard aggregates are cached with configurable TTL (default 5 minutes).
  Cache invalidation triggered on relevant model events (AssetUpdated,
  LoanStateChange, ProcurementStateChanged).
Priority: P1
Complexity: M
Estimated Risk: LOW — caching is transparent to users
Dependencies: TASK-3.01 (events for cache invalidation)
Acceptance Criteria:
  [ ] Dashboard queries served from cache within 100ms
  [ ] Cache invalidated within 10s of relevant data change
  [ ] Stale cache detected (missing entry) re-populates transparently
  [ ] Per-user-caching: dashboards respect school context isolation
Verification Steps:
  1. Load dashboard twice → second load should be cached
  2. Change an asset → wait 10s → refresh dashboard → verify new data
  3. Load dashboard as School A user → School B data NOT visible
  4. Verify cache key includes school_id for isolation
Rollback Strategy:
  Remove cache wrapper from dashboard queries. Performance degrades to
  current state. No data loss.
────────────────────────────────────────────────────────────────────────────
TASK-4.03: Pagination & Result-Set Optimization
────────────────────────────────────────────────────────────────────────────
ID: TASK-4.03
Title: Optimize all list views for large datasets
Business Problem:
  Current pagination loads full Eloquent models with relationships into
  memory before slicing. For 10k+ records, this wastes memory on objects
  that are never displayed. Additionally, ORDER BY on non-indexed columns
  forces filesort.
Technical Problem:
  1. paginate() loads entire Eloquent hydrated models
  2. Relationships (with()) may load unnecessary data (e.g., photos, logs)
  3. Ordering by text columns (asset_name) without index causes filesort
Root Cause:
  Default Laravel paginate() was used without considering large-table
  performance. Relationship loading was added for convenience without
  profiling actual query plans.
Target Outcome:
  Efficient pagination using cursor-based or optimized offset pagination.
  Only necessary columns loaded. Ordering on indexed columns. Memory usage
  < 50MB per paginated request with 10k records.
Priority: P1
Complexity: S
Estimated Risk: LOW — pagination optimization is well-understood
Dependencies: TASK-2.03 (indexes)
Acceptance Criteria:
  [ ] paginate() uses select() to fetch only needed columns
  [ ] Relationships loaded selectively (lazy vs eager based on route)
  [ ] ORDER BY on indexed columns only
  [ ] Memory footprint < 50MB per request (measured via PHP xdebug)
Verification Steps:
  1. Load asset list with 10k records → measure memory via xdebug
  2. EXPLAIN query to verify no filesort
  3. Compare page load with/without relationship preloading
Rollback Strategy:
  Restore original paginate() without select() narrowing. Queries work
  but load more data. No functional regression.
────────────────────────────────────────────────────────────────────────────
SPRINT 5 — API & MOBILE INTEGRATION
────────────────────────────────────────────────────────────────────────────

────────────────────────────────────────────────────────────────────────────
TASK-5.01: Build REST API for Sarpras
────────────────────────────────────────────────────────────────────────────
ID: TASK-5.01
Title: Create versioned REST API for all Sarpras operations
Business Problem:
  The platform must expose a REST API to support mobile clients, third-party
  integrations, and automated workflows (notifications, depreciation cron).
  Currently, ALL Sarpras functionality is Blade-rendered views with no API
  layer.
Technical Problem:
  1. No API routes, controllers, or resource classes exist for Sarpras
  2. Existing routes are tightly coupled to Blade views
  3. Authentication uses session cookies (not API tokens suitable for mobile)
  4. Response format is HTML, not JSON
Root Cause:
  The application was built as a traditional server-rendered Laravel app
  before API-first was defined as a requirement. Retrofitting requires
  decoupling controllers from views.
Target Outcome:
  A versioned REST API (api/v1/) covering all Sarpras domains:
  - Assets: CRUD, import, export, photo management
  - Procurement: CRUD, approval workflow, receiving, PO generation
  - Loans: request, approve, handover, return
  - Bookings: request, approve, cancel, complete
  - Maintenance: schedule, logs, work orders
  - QR: generation, scanning, audit
  - Reports: standard report generation and export
Priority: P0
Complexity: XL
Estimated Risk: HIGH — foundational change affecting all downstream features
Dependencies: TASK-2.01 (service layer — API calls services, not controllers)
Acceptance Criteria:
  [ ] All Sarpras resources accessible via REST endpoints
  [ ] API uses token-based auth (Sanctum SPA mode)
  [ ] Response format: JSON with consistent envelope
  [ ] Rate limiting: 60 req/min per user
  [ ] OpenAPI spec generated and accessible at /api/v1/docs
  [ ] School scoping enforced at API level
Verification Steps:
  1. Authenticate via API token → access asset endpoint
  2. Access School B's assets as School A user → blocked
  3. Send 61 requests in 1 minute → 429 rate limited
  4. Swagger UI renders correct schema from OpenAPI spec
Rollback Strategy:
  API is additive alongside Blade routes. Rollback: disable API routes.
  Blade functionality completely unaffected.
────────────────────────────────────────────────────────────────────────────
TASK-5.02: Mobile-Responsive UI
────────────────────────────────────────────────────────────────────────────
ID: TASK-5.02
Title: Implement responsive design for all Sarpras Blade views
Business Problem:
  Facility staff conducting field audits and maintenance MUST be able to use
  the system from mobile devices. Current Blade views are desktop-only and
  unusable on screens < 768px.
Technical Problem:
  All Sarpras Blade templates use fixed-width containers and desktop-centric
  layouts with no responsive breakpoints, flexbox, or mobile navigation.
Root Cause:
  Desktop-first design was the standard when the app was built. Mobile
  support was defined as a V3 requirement but field staff need it earlier
  for audits and maintenance.
Priority: P1
Complexity: M
Estimated Risk: LOW — CSS changes, no business logic affected
Dependencies: None
Acceptance Criteria:
  [ ] All Sarpras pages usable at 375px viewport width
  [ ] Touch-friendly tap targets (min 44x44px)
  [ ] Forms usable on mobile (no horizontal scroll)
  [ ] Tables convert to cards/lists on mobile view
  [ ] Navigation collapses to hamburger menu on mobile
Verification Steps:
  1. Test all Sarpras pages on 375px viewport (Chrome DevTools + physical device)
  2. Verify form submission works on mobile
  3. Verify QR scanner link works on mobile browser
  4. Lighthouse accessibility > 90 on mobile
Rollback Strategy:
  CSS-only changes. Revert stylesheet modifications. Desktop layout restored.
────────────────────────────────────────────────────────────────────────────
SPRINT 6 — TESTING & PRODUCTION READINESS
────────────────────────────────────────────────────────────────────────────

────────────────────────────────────────────────────────────────────────────
TASK-6.01: Sarpras Test Suite (0% → 60%)
────────────────────────────────────────────────────────────────────────────
ID: TASK-6.01
Title: Build comprehensive test suite for Sarpras module
Business Problem:
  With 0% test coverage, any change to Sarpras code risks introducing
  regressions. Refactors (service layer extraction), feature additions
  (notifications, disposal), and infrastructure changes (indexing) all
  require reliable automated verification.
Technical Problem:
  1. Zero feature/unit tests exist for ANY Sarpras controller or model
  2. All 13 controllers handle validation, business logic, and response —
     untestable in current structure
  3. Database-dependent tests cannot be run without a configured test DB
Root Cause:
  Testing was never prioritized. No test infrastructure existed for the
  Sarpras module. Rapid delivery cycles excluded test writing.
Target Outcome:
  Achieve 60% test coverage for Sarpras module by Sprint 6 end. Tests
  cover: CRUD operations, state transitions, authorization, business
  rules (SoD, budget checks), import/export, and notification triggering.
Priority: P0
Complexity: L
Estimated Risk: LOW — tests are defensive, never breaking existing code
Dependencies: TASK-2.01 (services are unit-testable), TASK-2.02 (policies are testable)
Acceptance Criteria:
  [ ] Feature tests for all 13 controllers (index, create, store, show, edit, update, delete)
  [ ] Unit tests for all service methods
  [ ] Policy tests for all authorization scenarios
  [ ] State machine transition tests for asset lifecycle
  [ ] 60%+ line coverage on Sarpras codebase
  [ ] Tests run in < 60 seconds (CI-compatible)
Verification Steps:
  1. php artisan test --filter=Sarpras — all pass
  2. php artisan test --coverage — coverage report shows 60%+ on Sarpras files
  3. CI pipeline executes Sarpras tests in < 60s
  4. Negative tests (unauthorized, invalid input, SoD violations) all pass
Rollback Strategy:
  Tests cannot break production code. Deleting tests is safe. Adding tests
  is always forward-only improvement.
────────────────────────────────────────────────────────────────────────────
TASK-6.02: CI/CD Pipeline for Sarpras
────────────────────────────────────────────────────────────────────────────
ID: TASK-6.02
Title: Establish automated build, test, and deployment pipeline
Business Problem:
  Deployments are manual with no automated quality gates. Code can reach
  production without testing, linting, or security scanning. This creates
  unacceptable risk given the P0 findings (PII leakage, SoD gaps).
Technical Problem:
  1. No GitHub Actions (or equivalent CI) configuration exists
  2. No PHP-CS-Fixer, PHPStan, or Psalm configuration
  3. No automated testing on pull requests
  4. Deployment process is undocumented and manual
Root Cause:
  Single-developer deployment model eliminated the perceived need for CI/CD.
  As the team grows and the platform becomes mission-critical, this
  technical debt must be addressed.
Target Outcome:
  Automated pipeline that runs on every PR:
  - PHP CS Fixer (PSR-12)
  - PHPStan Level 5 baseline
  - PHPUnit test suite (Sarpras coverage threshold)
  - Dependency vulnerability scan (composer audit)
Priority: P2
Complexity: M
Estimated Risk: LOW — CI/CD is infrastructural, doesn't affect app code
Dependencies: TASK-6.01 (tests must pass before CI gate is effective)
Acceptance Criteria:
  [ ] PR triggers CI pipeline automatically
  [ ] Pipeline: lint → static analysis → test → security audit
  [ ] PR blocked if tests fail or coverage < 60%
  [ ] Production deployment documented and scripted
  [ ] Pipeline completes in < 10 minutes
Verification Steps:
  1. Open PR → verify CI runs and reports results
  2. Intentionally break a test → verify PR is blocked
  3. Measure pipeline duration → verify < 10 minutes
  4. Deploy to staging via pipeline → verify successful deployment
Rollback Strategy:
  CI configuration is separate from application code. Disabling CI is safe.
  Deployments revert to manual process.
────────────────────────────────────────────────────────────────────────────
END OF BACKLOG
────────────────────────────────────────────────────────────────────────────

================================================================================
# PART 2 — SPRINT PLANS
================================================================================

────────────────────────────────────────────────────────────────────────────
SPRINT 1: Critical Production Safety
────────────────────────────────────────────────────────────────────────────

Objective:
  Stabilize production by eliminating data-breach risk and fixing immediate
  correctness issues. Sprint 1 is purely defensive — no new features, no
  refactors of working code.

Business Value:
  - Eliminates active PII leak (GTK endpoint vulnerability)
  - Prevents potential regulatory violation under Indonesian data protection law
  - Protects institutional reputation and user trust

Technical Value:
  - Establishes error-response hardening pattern
  - Sets baseline for secure-by-default development

Files Expected To Change:
  - app/Exceptions/Handler.php (error response sanitization)
  - Any controller with try/catch exposing model/user data

Expected Risks:
  - Developer accustomed to verbose errors may find debugging harder initially
  - No functional regression expected

Regression Risks:
  - Very low: only changes error-response format, not business logic
  - Monitoring: check error rate in production for 48 hours post-deploy

Success Criteria:
  [ ] Every API endpoint returns safe error responses for all invalid inputs
  [ ] Internal logging still captures full diagnostic information
  [ ] Zero PII leaks verified via security scan of error responses
  [ ] Task TASK-1.01 marked Complete

────────────────────────────��───────────────────────────────────────────────
SPRINT 2: Architecture Stabilization
────────────────────────────────────────────────────────────────────────────

Objective:
  Establish clean architectural boundaries (service layer, policies, indexes,
  transactions) that enable every future feature to be built on a stable,
  testable foundation.

Business Value:
  - Enables safe refactoring and feature addition without regression risk
  - Enforces SoD compliance (critical for financial accountability)
  - Foundation for API, mobile, automation features

Technical Value:
  - Separation of concerns: controllers present, services compute
  - Explicit authorization: policies > ad-hoc role checks
  - Data integrity: transactions + indexes guarantee consistency at scale

Files Expected To Change:
  - app/Services/Sarpras/* (new service classes — ~6 files)
  - app/Policies/* (new policy classes — ~6 files)
  - app/Http/Controllers/Sarpras/* (refactor to use services + policies)
  - database/migrations/* (add indexes, foreign keys, transactions)
  - app/Models/* (add state machines, casts, scopes)

Expected Risks:
  - Service extraction may reveal edge cases in controller logic
  - Restrictive policies may block some legitimate user actions
  - Index additions increase disk usage (~5-10% on largest tables)

Regression Risks:
  - MEDIUM: Behavioral changes in authorization may surprise users
  - Mitigation: Comprehensive policy testing before deploy; gradual rollout
  - Transaction wrapping may expose latent deadlock scenarios (low probability)

Success Criteria:
  [ ] All 13 Sarpras controllers use service layer
  [ ] All 6+ Sarpras models have corresponding policies
  [ ] Zero N+1 queries in critical paths (verified via debugbar)
  [ ] All multi-write operations wrapped in transactions
  [ ] All foreign keys enforced at database level
  [ ] Tasks TASK-2.01, TASK-2.02, TASK-2.03, TASK-2.04 marked Complete

────────────────────────────────────────────────────────────────────────────
SPRINT 3: Business Completeness
────────────────────────────────────────────────────────────────────────────

Objective:
  Deliver the core business capabilities mandated by BR-001 that are currently
  missing: notifications, formal asset lifecycle, procurement approval chains,
  and standardized QR coding.

Business Value:
  - Notification engine eliminates 40% manual follow-up overhead
  - Asset lifecycle formalization enables regulatory compliance
  - Multi-level procurement approval ensures financial accountability
  - Standardized QR coding enables reliable field operations

Technical Value:
  - Events system establishes domain-driven architecture pattern
  - State machines enforce business rule integrity
  - Policy-driven coding creates reproducible asset identification

Files Expected To Change:
  - app/Notifications/* (notification channel classes)
  - app/Events/* (domain event classes)
  - app/Listeners/* (notification listeners)
  - app/Models/Asset.php (state machine, lifecycle states)
  - app/Models/ProcurementRequest.php (approval chain state machine)
  - app/Services/NotificationService.php
  - app/Services/AssetLifecycleService.php
  - resources/views/sarpras/* (notification inbox UI)
  - database/migrations/* (notification table, approval_history)

Expected Risks:
  - Notification volume may spike if throttling is misconfigured
  - State machine migration of existing assets must handle all current statuses
  - Approval chain configuration adds administrative overhead

Regression Risks:
  - MEDIUM: Existing assets must be mapped to new lifecycle states
  - Legacy asset statuses must be gracefully migrated (active → active, draft → planning)
  - Procurement records transition to approval chain model

Success Criteria:
  [ ] All state transitions validated by state machine
  [ ] Notifications delivered within 1 minute of triggering event
  [ ] Budget checks block over-budget approvals
  [ ] QR codes encode asset_code, scanner resolves by code
  [ ] Tasks TASK-3.01, TASK-3.02, TASK-3.03, TASK-3.04 marked Complete

────────────────────────────────────────────────────────────────────────────
SPRINT 4: Performance Optimization
────────────────────────────────────────────────────────────────────────────

Objective:
  Optimize query performance, introduce caching, and ensure all list views
  can handle the V2 target scale (5,000 assets) within SLA thresholds.

Business Value:
  - Dashboard response < 2s p95 meets SLA commitment
  - List views usable at scale (10k+ records without performance degradation)
  - Foundation for V3 target (100k+ records)

Technical Value:
  - Query optimization pattern reusable across all modules
  - Caching abstraction available for future features
  - Index strategy documented for new table additions

Files Expected To Change:
  - database/migrations/* (add composite indexes)
  - app/Http/Controllers/Sarpras/* (optimize queries, add select())
  - app/Models/* (add scopes, query scopes for common filters)
  - app/Services/DashboardCacheService.php (new)
  - config/cache.php (configure cache drivers/TTL)
  - bootstrap/app.php (if custom middleware for caching)

Expected Risks:
  - Cache invalidation bugs may serve stale dashboard data
  - Index additions may slow INSERT operations slightly (measurable but acceptable)
  - Select() narrowing may miss relationships if not carefully audited

Regression Risks:
  - LOW: Query optimizations never change semantics, only performance
  - Cache layer: cache miss falls back to fresh query (graceful degradation)

Success Criteria:
  [ ] Zero N+1 queries on any Sarpras page
  [ ] Dashboard < 100ms (cached), < 2s (cold)
  [ ] Asset list < 1s with 5k records
  [ ] Memory footprint < 50MB per paginated request
  [ ] Tasks TASK-4.01, TASK-4.02, TASK-4.03 marked Complete

────────────────────────────────────────────────────────────────────────────
SPRINT 5: API & Mobile Integration
────────────────────────────────────────────────────────────────────────────

Objective:
  Deliver a versioned REST API covering all Sarpras operations and responsive
  UI for mobile field operations.

Business Value:
  - Mobile-responsive UI enables field staff to conduct audits and maintenance
    from any device
  - REST API enables future integrations (finance systems, IoT sensors,
    third-party procurement portals)
  - API is prerequisite for native mobile apps

Technical Value:
  - API-first architecture established
  - Token-based auth decouples mobile/web from session auth
  - OpenAPI spec enables automatic client generation

Files Expected To Change:
  - routes/api.php (new — API routes)
  - app/Http/Controllers/Api/V1/Sarpras/* (new — API controllers, ~13 files)
  - app/Http/Resources/Sarpras/* (new — API resource classes, ~10 files)
  - app/Providers/RouteServiceProvider.php (API route grouping)
  - config/cors.php (API CORS configuration)
  - config/sanctum.php (API token auth)
  - resources/js/* (new ��� API client integration for mobile-responsive views)
  - resources/views/sarpras/* (responsive CSS additions)
  - public/css/sarpras-mobile.css (new)

Expected Risks:
  - API rate limiting may block automated integrations if not properly configured
  - Token-based auth change may affect existing session-based integrations
  - Mobile-responsive CSS may introduce layout shifts on desktop

Regression Risions:
  - LOW: API is additive; Blade views remain functional
  - Mobile CSS: visual regression testing on major browsers/devices required

Success Criteria:
  [ ] All Sarpras resources accessible via REST API
  [ ] API responds in < 200ms (p95)
  [ ] API rate limiting enforced at 60 req/min
  [ ] OpenAPI spec renders correctly at /api/v1/docs
  [ ] All Sarpras pages responsive at 375px viewport
  [ ] Tasks TASK-5.01, TASK-5.02 marked Complete

────────────────────────────────────────────────────────────────────────────
SPRINT 6: Testing & Production Readiness
────────────────────────────────────────────────────────────────────────────

Objective:
  Achieve production readiness through automated testing (60%+ coverage) and
  CI/CD pipeline establishment.

Business Value:
  - Automated testing eliminates regression risk for all prior sprints
  - CI/CD pipeline prevents broken code from reaching production
  - Measurable quality metrics satisfy stakeholder confidence requirements

Technical Value:
  - Testing patterns established for future developers
  - Pipeline configuration reusable across all modules
  - Quality gates prevent future technical debt accumulation

Files Expected To Change:
  - tests/Feature/Sarpras/* (new — feature tests, ~15 files)
  - tests/Unit/Sarpras/* (new — unit tests for services, ~10 files)
  - tests/CreatesApplication.php (test bootstrap)
  - phpunit.xml (test configuration)
  - .github/workflows/laravel.yml (new — CI pipeline)
  - composer.json (dev dependencies: phpstan, php-cs-fixer)
  - Makefile or bin/test (test runner scripts)
  - .env.example (test environment configuration)

Expected Risks:
  - Test suite may reveal latent bugs in existing code (expected — good thing)
  - CI pipeline configuration must handle test database provisioning
  - Coverage threshold (60%) may be ambitious for complex state machines

Regression Risks:
  - VERY LOW: Tests only observe behavior, they don't change it
  - Fixing discovered bugs is intentional — part of test-driven improvement

Success Criteria:
  [ ] PHPUnit test suite runs in < 60 seconds
  [ ] Sarpras coverage ≥ 60%
  [ ] CI pipeline blocks PRs with failing tests
  [ ] Pipeline completes in < 10 minutes
  [ ] Tasks TASK-6.01, TASK-6.02 marked Complete

================================================================================
# PART 3 — DEPENDENCY GRAPH
================================================================================

                    ┌─────────────────┐
                    │   SPRINT 1      │
                    │ Production Safe │
                    └────┬───────┬────┘
                         │       │
                    TASK-1.01
                  (Error hardening)
                         │
                    ┌────▼───────┐
                    │ SPRINT 2   │
                    │Architect-  │
                    │ ure Stabil│
                    └────┬───┬───┘
                         │   │
              ┌──────────┘   └──────────┐
              │                         │
       TASK-2.01                  TASK-2.02
     (Service Layer)          (Authorization
                              Policies + SoD)
              │                         │
              └──────────┬──────────────┘
                         │
              ┌──────────▼──────────┐
              │ SPRINT 3            │
              │ Business Completeness│
              └──────────┬──────────┘
                         │
              ┌──────────▼──────────┐
              │ SPRINT 4            │
              │ Performance Opt.    │
              └──────────┬──────────┘
                         │
              ┌──────────▼──────────┐
              │ SPRINT 5            │
              │ API & Mobile        │
              └──────────┬──────────┘
                         │
              ┌──────────▼──────────┐
              │ SPRINT 6            │
              │ Testing & Readiness │
              └─────────────────────┘

Dependency Chains:

  TASK-2.01 (Service Layer) — Blocks:
    ├── TASK-3.01 (Notification Events — services emit events)
    ├── TASK-3.02 (Asset Lifecycle — state machine in service)
    ├── TASK-3.03 (Approval Chain — service enforces workflow)
    ├── TASK-5.01 (REST API — services are API handlers)
    └── TASK-6.01 (Tests — services are unit-testable)

  TASK-2.02 (Policies) — Blocks:
    ├── TASK-3.02 (Lifecycle — SoD enforcement)
    ├── TASK-3.03 (Procurement — approval authority policies)
    └── TASK-5.01 (API — policy-based authorization)

  TASK-2.03 (Indexes) — Blocks:
    ├── TASK-4.01 (N+1 elimination — indexes support optimized queries)
    └── TASK-4.02 (Dashboard cache — indexes reduce cold-fill time)

  TASK-3.01 (Notifications) — Supports:
    └── TASK-4.02 (Cache invalidation — event-driven)

  TASK-3.03 (Approval Chain) — Requires:
    ├── TASK-2.02 (Policies — SoD checks)
    └── TASK-3.01 (Notifications — approval alerts)

================================================================================
# PART 4 — RECOMMENDED EXECUTION ORDER
================================================================================

Within each sprint, tasks execute in this order:

────────────────────────────────────────────────────────────────────────────
Sprint 1:
────────────────────────────────────────────────────────────────────────────
  1. TASK-1.01 (single-file, low-risk, immediate impact)

────────────────────────────────────────────────────────────────────────────
Sprint 2:
────────────────────────────────────────────────────────────────────────────
  1. TASK-2.03 (indexes — safe, non-breaking foundation)
  2. TASK-2.04 (transactions — data integrity before scale)
  3. TASK-2.01 (service layer — architectural refactor)
  4. TASK-2.02 (policies — authorization on top of clean services)

────────────────────────────────────────────────────────────────────────────
Sprint 3:
────────────────────────────────────────────────────────────────────────────
  1. TASK-3.04 (QR standardization — data correction, lowest risk)
  2. TASK-3.02 (asset lifecycle — state machine, core data model change)
  3. TASK-3.01 (notifications — event infrastructure)
  4. TASK-3.03 (procurement approval — business workflow complexity)

────────────────────────────────────────────────────────────────────────────
Sprint 4:
────────────────────────────────────────────────────────────────────────────
  1. TASK-4.01 (N+1 elimination — query profiling → fix)
  2. TASK-4.03 (pagination optimization — build on N+1 fixes)
  3. TASK-4.02 (dashboard caching — depends on stable queries)

────────────────────────────────────────────────────────────────────────────
Sprint 5:
────────────────────────────────────────────────────────────────────────────
  1. TASK-5.02 (mobile-responsive — CSS-only, immediate field value)
  2. TASK-5.01 (REST API — largest effort, depends on service layer)

────────────────────────────────────────────────────────────────────────────
Sprint 6:
────────────────────────────────────────────────────────────────────────────
  1. TASK-6.01 (test suite — validates all prior sprints)
  2. TASK-6.02 (CI/CD — gates on test suite existence)

================================================================================
# PART 5 — DEFINITION OF DONE
================================================================================

────────────────────────────────────────────────────────────────────────────
Sprint 1 DoD (Production Safety):
────────────────────────────────────────────────────────────────────────────
  [ ] All API error responses return safe envelope (no PII, no stack traces)
  [ ] Internal logging preserved at Log::error() level
  [ ] Security scan confirms zero PII leaks via error responses
  [ ] 48-hour production monitoring shows zero new leak incidents
  [ ] Code review approved by ≥ 1 senior engineer

─────────────────────────────────────────────────────────────────────────���──
Sprint 2 DoD (Architecture Stabilization):
────────────────────────────────────────────────────────────────────────────
  [ ] All 13 Sarpras controllers delegate business logic to services
  [ ] Every controller method ≤ 30 lines (excluding HTTP wiring)
  [ ] All 6+ models have corresponding Laravel Policies
  [ ] SoD conflicts blocked in policy (requester = approver rejected)
  [ ] All foreign keys enforced at database level (SHOW CREATE TABLE)
  [ ] All multi-write operations wrapped in DB::transaction()
  [ ] No regression: all existing Sarpras flows verified against staging
  [ ] Code review approved by Technical Lead + Architect

────────────────────────────────────────────────────────────────────────────
Sprint 3 DoD (Business Completeness):
────────────────────────────────────────────────────────────────────────────
  [ ] Asset lifecycle state machine: all valid/invalid transitions tested
  [ ] Disposed assets are read-only and archived (soft-delete disabled)
  [ ] Notifications delivered within 1 minute for all event types
  [ ] Notification throttling: max 10/hour/user (configurable)
  [ ] Procurement approval chain: multi-level, SoD-enforced, budget-checked
  [ ] Rejection requires mandatory reason
  [ ] QR codes encode asset_code; scanner resolves by code
  [ ] Existing assets migrated to new lifecycle states
  [ ] Migration script tested on staging with production-like data volume

────────────────────────────────────────────────────────────────────────────
Sprint 4 DoD (Performance Optimization):
────────────────────────────────────────────────────────────────────────────
  [ ] Laravel Debugbar confirms ZERO N+1 queries on every Sarpras page
  [ ] Dashboard cold-load < 2s, cached < 100ms (verified at 5k records)
  [ ] Asset list < 1s with 5k records (verified via load test)
  [ ] Procurement index < 2s with 2k records
  [ ] Memory footprint per request < 50MB (verified via xdebug)
  [ ] EXPLAIN ANALYZE on top 10 queries shows index usage (no filesort)
  [ ] Cache invalidation verified: data change reflected within 10 seconds

────────────────────────────────────────────────────────────────────────────
Sprint 5 DoD (API & Mobile):
────────────────────────────────────────────────────────────────────────────
  [ ] All Sarpras operations accessible via REST API endpoints
  [ ] API token authentication works (Sanctum SPA mode)
  [ ] API rate limiting enforced at 60 req/min per user
  [ ] School data isolation enforced at API level (cross-school access blocked)
  [ ] OpenAPI spec at /api/v1/docs renders correctly
  [ ] All Sarpras Blade views responsive at 375px viewport
  [ ] Form submission tested on physical mobile device (iOS + Android)
  [ ] Lighthouse accessibility score > 90 on mobile

────────────────────────────────────────────────────────────────────────────
Sprint 6 DoD (Testing & Production Readiness):
─────────��──────────────────────────────────────────────────────────────────
  [ ] PHPUnit test suite covers all Sarpras controllers, services, policies
  [ ] Line coverage on Sarpras codebase ≥ 60%
  [ ] Tests run in < 60 seconds
  [ ] CI pipeline on every PR: lint → static analysis → tests → security
  [ ] PR blocked if any test fails or coverage < 60%
  [ ] Pipeline duration < 10 minutes
  [ ] Staging deployment via pipeline tested successfully

================================================================================
# PART 6 — TASK RANKING
================================================================================

Tasks ranked by multi-dimensional analysis:

  Rank | ID       | Biz Impact | Tech Impact | Risk Reduction | Dev Cost | Dependency Depth
  -----+----------+------------+-------------+----------------+----------+----------------
   1   | TASK-1.01|     H      |     L       |      H         |    XS    |       0
   2   | TASK-2.04|     H      |     H       |      H         |    S     |       0
   3   | TASK-2.01|     M      |     H       |      M         |    L     |       0
   4   | TASK-2.02|     H      |     M       |      H         |    M     |       1
   5   | TASK-2.03|     M      |     M       |      M         |    M     |       0
   6   | TASK-3.02|     H      |     M       |      M         |    M     |       2
   7   | TASK-3.03|     H      |     M       |      H         |    L     |       3
   8   | TASK-3.01|     H      |     M       |      M         |    M     |       2
   9   | TASK-5.01|     M      |     H       |      L         |    XL    |       4
  10   | TASK-3.04|     M      |     S       |      S         |    M     |       2
  11   | TASK-4.01|     M      |     M       |      M         |    M     |       2
  12   | TASK-6.01|     M      |     H       |      H         |    L     |       5
  13   | TASK-5.02|     M      |     S       |      S         |    M     |       0
  14   | TASK-4.02|     S      |     M       |      M         |    M     |       3
  15   | TASK-4.03|     S      |     M       |      M         |    S     |       2
  16   | TASK-6.02|     S      |     S       |      M         |    S     |       6

Legend:
  H = High, M = Medium, L = Low, S = Starter, XL = Extra Large
  Dependency Depth = longest chain of transitive dependencies

────────────────────────────────────────────────────────────────────────────
EXECUTION SUMMARY
────────────────────────────────────────────────────────────────────────────

Total Tasks: 16
Total Sprints: 6
P0 Tasks: 6 (TASK-1.01, TASK-2.04, TASK-3.01, TASK-3.02, TASK-3.03, TASK-5.01)
P1 Tasks: 6 (TASK-2.01, TASK-2.02, TASK-2.03, TASK-3.04, TASK-4.01, TASK-5.02)
P2 Tasks: 4 (TASK-4.02, TASK-4.03, TASK-6.02, TASK-6.01 is P0)

Critical Path:
  TASK-1.01 → TASK-2.01 → TASK-3.01 → TASK-4.01 → TASK-5.01 → TASK-6.01

  (Safety → Services → Notifications → Queries → API → Tests)

This is the longest dependency chain and determines the minimum sprint
count. All other tasks can execute in parallel where dependencies allow.

================================================================================
*End of IMP-001 — Enterprise Modernization Backlog & Sprint Planning.*
*Report prepared: 2026-07-01*
*Classification: Confidential — CTO / Technical Steering Committee*
*Validated against: BR-001 (Business Requirements), EA-002 (Blueprint V2), Technical Audit*
*Next step: Execute Sprint 1 implementation (see IMP-002 for sprint task definitions)*
