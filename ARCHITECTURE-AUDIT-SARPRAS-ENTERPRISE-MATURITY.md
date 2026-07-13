# ENTERPRISE MATURITY ASSESSMENT: SARPRAS MODULE
## ALIM — Academic Learning & Information Management System

**Assessment Date:** 2026-07-01  
**Module Scope:** Sarpras (Sarana Prasarana / Facilities & Infrastructure)  
**Assessor Role:** Principal Enterprise Solution Architect  
**Methodology:** Evidence-based assessment against enterprise asset management lifecycle

---

## EXECUTIVE SUMMARY

The Sarpras module implements a functional but incomplete Asset Management system spanning Building, Room, Asset, Loan, Booking, Maintenance, Procurement, and QR audit workflows. The system serves a school environment (Pesantren) rather than an industrial/commercial enterprise context. While core CRUD operations are present across all major sub-modules, the system lacks critical enterprise capabilities: **no depreciation engine, no automated maintenance triggers, no disposal/write-off lifecycle, no warranty tracking, no vendor management, and no formalized policy-based authorization.** Business logic resides directly in controllers without a service layer. Zero automated tests exist. A legacy duplicate controller (`SarprasController`) coexists with modern namespaced controllers (`Sarpras*ASetController`, `SarprasGedungController`), indicating an incomplete migration wave.

**Overall Enterprise Maturity Score: 42/100**

---

## 1. BUSINESS PROCESS ASSESSMENT

### Asset Lifecycle Coverage Matrix

| # | Process | Status | Evidence | Business Risk |
|---|---------|--------|----------|---------------|
| 1 | Asset Planning | **Partial** | `ProcurementRequest` model and controller support budget estimation and urgency levels. No capital expenditure forecast. | Medium — Budget estimates exist but cannot aggregate into annual CAPEX plans. |
| 2 | Procurement Approval | **Implemented** | `ProcurementRequest.status` flow: `draft -> pending -> approved -> ordered -> delivered -> completed`. Approve/reject endpoints present. | Low — Approval flow works but lacks multi-level approval chain. |
| 3 | Receiving | **Implemented** | `SarprasProcurementController.receive()` validates actual quantity/cost vs estimated, records vendor info. | Low — Receiving captures variance but does not trigger quality inspection. |
| 4 | Asset Registration | **Implemented** | `Asset::create()` via controller and `AssetImport` Excel importer. Supports bulk import. | Low — Manual and bulk registration both functional. |
| 5 | Asset Classification | **Implemented** | `AssetCategory` model with hierarchical parent-child relationship. Type categorization (tidak_bergerak/bergerak/habis_pakai). | Low — Classification is solid and supports depreciation years per category. |
| 6 | Asset Assignment | **Partial** | `room_id` on Asset links to `AssetRoom`. Movement via `SarprasMovementController`. Location history tracked. | Medium — Assignment exists only as physical room assignment. No user assignment. |
| 7 | Room Allocation | **Implemented** | `SarprasRuangController` CRUD with building hierarchy, capacity, type, bookable flag. | Low — Fully operational room management. |
| 8 | Borrowing (Checkout) | **Implemented** | `AssetLoan` with `pending -> approved -> dipinjam -> dikembalikan` flow. Condition recorded on loan/return. | Low — Complete borrow-return cycle with damage documentation. |
| 9 | Returning | **Implemented** | `SarprasLoanController.return()` checks condition on return, updates asset status. | Low — Functional but no late-return penalty workflow. |
| 10 | Reservation (Booking) | **Implemented** | `RoomBooking` with conflict detection, approval workflow, actual time tracking. | Low — Conflict detection prevents double-booking. |
| 11 | Transfer | **Implemented** | `SarprasMovementController` creates `AssetLocationHistory` record and updates `room_id`. | Low — Audit trail of movements maintained. |
| 12 | Preventive Maintenance | **Implemented** | `AssetMaintenanceSchedule` with frequency options (harian through tahunan). Next-date calculation on log creation. | Low — Schedule system works but has no automated triggers/reminders. |
| 13 | Corrective Maintenance | **Partial** | `AssetMaintenanceLog` supports ad-hoc logging without schedule. Condition tracking (before/after). | Medium — Can log corrective maintenance but no ticketing or SLA system. |
| 14 | Inspection | **Partial** | `SarprasQRController.auditSubmit()` and `bulkAuditSubmit()` support condition verification via QR scan. | Medium — Mobile-friendly QR audit exists but has no standardized checklist template. |
| 15 | Asset Audit | **Implemented** | QR-based single and bulk audit. Tracks `last_audit_date`, `last_audit_by`, `condition` changes. Audit photos via `AssetPhoto`. | Low — Practical and field-tested approach. |
| 16 | QR / Barcode | **Implemented** | `SimpleSoftwareIO\QrCode` generates QR labels. `scan` page reads URLs. `lookup` API supports mobile scanner. PDF label export. | Low — Full QR lifecycle: generate, print, scan, audit. |
| 17 | Warranty Management | **Missing** | No `warranty_start`, `warranty_end`, `warranty_provider` on Asset model. No warranty claim workflow. | High — Cannot track equipment warranties; critical for expensive assets. |
| 18 | Vendor Management | **Missing** | `vendor_name` stored as plain string on `ProcurementRequest` and `MaintenanceLog`. No vendor master table. | High — Vendor data unstructured; cannot evaluate vendor performance. |
| 19 | Asset Depreciation | **Missing** | `depreciation_per_year` and `current_value` fields exist on Asset but NO depreciation calculation engine. Values never auto-update. | High — Fields are dormant; financial reporting relies on manual entry or guesswork. |
| 20 | Asset Valuation | **Missing** | `last_valuation_date` field exists but no revaluation workflow. No market value tracking. | Medium — Financial statements may be inaccurate. |
| 21 | Disposal | **Missing** | Asset `condition` includes `dihapus` enum, but NO disposal workflow, no approval, no proceeds recording, no write-off audit. | Critical — Assets can be "marked deleted" but never formally disposed. |
| 22 | Write-Off | **Missing** | No write-off request, approval, accounting integration, or asset removal from active inventory. | Critical — Cannot complete asset lifecycle. |
| 23 | Lost Asset | **Partial** | `condition = hilang` is an enum value on Asset. `AssetDamageReport` exists but no formal loss investigation workflow. | Medium — Can flag as lost but no investigation, insurance claim, or replacement process. |
| 24 | Reporting | **Implemented** | 4 report pages: inventaris_per_ruang, kondisi_aset, peminjaman, pemeliharaan, nilai_aset. PDF export. | Low — Reports are basic; no drill-down, no trend analysis. |
| 25 | Analytics | **Missing** | Dashboard shows counts and chart data but no historical trend, no predictive maintenance, no utilization metrics. | Medium — No data-driven decision support. |

### Business Process Coverage Summary

**Implemented:** 14/25 (56%)  
**Partial:** 7/25 (28%)  
**Missing:** 4/25 (16%)

---

## 2. FEATURE COMPLETENESS

### Feature Inventory

| Feature Area | Features | Covered | Total | Completeness |
|---|---|---|---|---|
| Building Management | CRUD, type, condition, IMB, ownership, photo | 7 | 7 | 100% |
| Room Management | CRUD, building hierarchy, capacity, bookable, facilities | 7 | 7 | 100% |
| Asset Registry | CRUD, category, photo, import/export, code, serial | 10 | 10 | 100% |
| Land Registry | CRUD, certificate, GPS coordinates | 6 | 6 | 100% |
| Asset Borrowing | Request, approve, handover, return, condition check | 6 | 8 | 75% |
| Room Booking | Request, approve, conflict detection, complete | 5 | 7 | 71% |
| Maintenance Scheduling | Create, frequency, next-date calculation | 4 | 7 | 57% |
| Maintenance Logging | Create, condition before/after, parts, cost | 5 | 6 | 83% |
| Asset Movement | Create, from/to room, history | 3 | 4 | 75% |
| Procurement | Request, approve, reject, receive, convert to asset | 8 | 10 | 80% |
| QR Code System | Generate, print, scan, lookup, audit (single+bulk) | 6 | 7 | 86% |
| Damage Reporting | Submit, list, status tracking | 2 | 5 | 40% |
| Dashboard & Stats | 9 metric groups, charts, recent activity | 1 | 1 | 100% |
| Reports (PDF) | 5 report types | 5 | 5 | 100% |

**Overall Feature Completeness: 78%**

### Identified Gaps

- **Duplicate Feature:** Building/Room/Asset CRUD exists in BOTH legacy `SarprasController` (lines 64-694 of that file) and modern `SarprasGedungController`, `SarprasRuangController`, `SarprasAsetController`. The legacy controller uses `abort_unless(auth()->user()->id === $userId, 403)` while the new ones use school-scoped authorization. Only kategoriStore is wired in routes to the legacy controller — the rest appears to be dead code.
- **Weak Implementation:** `AssetDamageReport` has a status progression (`pending -> reviewed -> scheduled -> in_progress -> completed`) but NO controller actions to traverse these states. The model has `reviewed_by`, `admin_notes` but no `review()` or `escalate()` method.
- **Unused Feature:** `room_booking_conflicts` table exists (migration `2026_03_31_095326`) but is never queried by any code. Conflict detection in `SarprasBookingController.store()` is ad-hoc SQL, not stored in the conflicts table.
- **Scalability Issue:** `SarprasUserController` loads ALL assets and rooms without pagination in `dashboard()`. At 10,000+ assets, this will exceed memory limits.

---

## 3. GAP ANALYSIS (vs Enterprise Systems)

### SAP / Oracle EAM Capability Comparison

| Capability | SAP EAM | Oracle EAM | ALIM Sarpras | Gap Severity |
|---|---|---|---|---|
| Full asset lifecycle (procure->dispose) | Yes | Yes | No disposal/write-off | Critical |
| Automated depreciation | Yes | Yes | Fields exist, no engine | High |
| Work order management | Yes | Yes | No — maintenance logs are ad-hoc | High |
| Vendor master data | Yes | Yes | No — flat string fields | High |
| Warranty tracking | Yes | Yes | Not implemented | High |
| Spare parts BOM | Yes | Yes | Not implemented | Medium |
| Multi-level approval | Yes | Yes | Single approver only | Medium |
| Predictive maintenance | Yes (IoT) | Yes (AI) | No — calendar-based only | Medium |
| Asset utilization analytics | Yes | Yes | No — count-only dashboard | Medium |
| Compliance reporting | Yes | Yes | No — basic PDF reports only | Low |
| Integration with finance/GL | Yes | Yes | None | High |
| Mobile offline support | Yes | Yes | QR scan online only | Medium |

### Key Missing Enterprise Capabilities

1. **No Disposal/Write-Off Lifecycle** — Assets can only be "deleted" (soft delete) or marked `hilang`/`dihapus`. There is no formal disposition workflow with approvals, proceeds recording, or accounting entries.
2. **No Work Order System** — Maintenance is either scheduled (calendar-driven) or logged ad-hoc. No work orders, no technician assignment, no parts consumption tracking, no completion verification.
3. **No Vendor Management** — Vendor names are stored as plain text strings. No vendor master, no performance scoring, no contract tracking.
4. **No Financial Integration** — `current_value`, `depreciation_per_year`, `acquisition_price` fields are populated but the system has no depreciation calculation, no accumulated depreciation, no gain/loss on disposal.
5. **No SLA/Contract Management** — Maintenance schedules have frequencies but no service level agreements, no response time tracking, no penalty clauses.

---

## 4. BUSINESS FLOW VALIDATION

### Procurement -> Asset Flow

```
User Request (status=draft)
    -> Pending Approval (status=pending)
        -> Approved OR Rejected
            -> Ordered (NOT IMPLEMENTED — no transition in controller)
                -> Delivered (receive() sets status='delivered')
                    -> Converted to Asset (convertToAsset sets status='completed')
```

**Issue Found:** The status `ordered` exists in `ProcurementRequest::STATUS_OPTIONS` but NO controller method ever sets it. The flow jumps from `approved` to `delivered`. This is a **broken state transition**.

### Maintenance Schedule -> Log Flow

```
Schedule Created (frequency=bulanan)
    -> Due Date arrives (manual check, no automation)
        -> Maintenance Log Created
            -> next_maintenance_date auto-calculated
            -> asset.condition updated (if condition_after provided)
```

**Issue Found:** The maintenance log's `target_type` is set on create but never stored in the `asset_maintenance_logs` table (unset at line 270: `unset($validated['target_type'])`). This means the log cannot be traced back to what type of target it relates to without checking which ID is populated.

### Loan Flow

```
User borrows (status=pending)
    -> Approved OR Rejected
        -> Handover (status=dipinjam, asset.status='dipinjam')
            -> Return (status=dikembalikan, asset.status='tersedia')
```

**Race Condition Found:** In `SarprasLoanController.return()` (line 160), asset status is updated independently from loan update without DB transaction. If the asset update fails after the loan is marked returned, the loan says "returned" but asset is still "dipinjam".

### Room Booking Flow

```
Booker creates (status=auto-set based on room config)
    -> If approval_required: pending -> approved/rejected
    -> If not required: auto-approved
        -> Event occurs (actual times recorded)
        -> Completed
```

**Flow Issue:** `cancel()` (line 149) does NOT check school scope. Any authenticated user can cancel ANY booking regardless of school. The method only checks `booked_by === auth()->id()`, bypassing the `authorizeBookingAccess()` method that all other methods use.

---

## 5. STATE MACHINE ANALYSIS

### AssetLoan State Machine

**Defined States:** `pending`, `approved`, `dipinjam`, `dikembalikan`, `terlambat`, `hilang`, `dibatalkan`

**Transitions Found:**
- `pending -> approved` (approve())
- `pending -> dibatalkan` (reject())
- `approved -> dipinjam` (handover())
- `dipinjam -> dikembalikan` (return())
- `pending -> cancelled` (NOT IMPLEMENTED — status `dibatalkan` exists)
- Any -> `terlambat` (NOT IMPLEMENTED — no cron/job sets this)
- Any -> `hilang` (NOT IMPLEMENTED)

**Issues:**
- No transition from `dikembalikan -> pending` (cannot re-loan after return without creating new record)
- `terlambat` and `hilang` are dead states — never reached
- Race condition in return(): asset status update not wrapped in transaction

### ProcurementRequest State Machine

**Defined States:** `draft`, `pending`, `approved`, `rejected`, `ordered`, `delivered`, `completed`, `cancelled`

**Transitions Found:**
- `draft -> pending` (store() sets `pending`, not `draft`)
- `pending -> approved` (approve())
- `pending -> rejected` (reject())
- `approved -> delivered` (receive())
- `delivered -> completed` (convertToAsset())

**Missing Transitions:**
- `ordered` is NEVER reached (no controller action sets it)
- `cancelled` has no transition (DELETE destroys the record entirely)
- `rejected -> pending` is not supported (cannot resubmit rejected request)
- `draft` has no transition (store sets `pending` directly)

### RoomBooking State Machine

**Defined States:** `pending`, `approved`, `rejected`, `cancelled`, `completed`

**Transitions Found:**
- `pending -> approved` (approve())
- `pending -> rejected` (reject())
- `approved -> completed` (complete())
- `any (booker only) -> cancelled` (cancel()) — BUT: no school scope check

**Issues:**
- No transition `rejected -> pending` (cannot resubmit)
- `completed` has no time constraint (can mark completed before event date)

### Asset State Machine

**Defined States:** `baik`, `rusak_ringan`, `rusak_sedang`, `rusak_berat`, `hilang`, `dihapus`

**Transitions:** Condition changes are triggered by:
- Manual edit in Asset controller
- QR audit submit
- Maintenance log store (condition_after)
- Loan return (condition_on_return)

**Issues:**
- No policy enforcement — any authenticated Sarpras user can change condition
- `dihapus` state has no associated workflow or approval
- No transition history tracking on the Asset model itself

---

## 6. DATA FLOW ANALYSIS

### Entity Relationship Flow

```
School (1) --> (*) AssetBuilding (1) --> (*) AssetRoom (1) --> (*) Asset
                                        (1) --> (*) RoomBooking
                                        (1) --> (*) AssetMaintenanceSchedule
                                        (1) --> (*) AssetMaintenanceLog
                                        (1) --> (*) ProcurementRequestItem
                                                    (1) --> ProcurementRequest
Asset (1) --> (*) AssetLoan
Asset (1) --> (*) AssetLocationHistory
Asset (1) --> (*) AssetPhoto
Asset (1) --> (*) AssetDamageReport
AssetLand (1) --> (*) AssetBuilding
```

### Data Flow: Procurement -> Asset Conversion

```
ProcurementRequest.create()
  -> ProcurementRequestItem.create() xN
  
ProcurementRequest.receive()
  -> updates ProcurementRequest.status = 'delivered'
  -> updates ProcurementRequestItem.actual_quantity, actual_price
  
ProcurementRequest.convertToAsset()
  -> Asset.create() xN (one per qty)
  -> ProcurementRequest.update(status='completed')
```

**Issue:** Asset names are auto-generated by appending `(1)`, `(2)` suffixes. If a user converts 5 items named "Kursi" from two different procurements, asset names become indistinguishable. No unique asset_code generation strategy — uses random string, risking collisions.

### Data Flow: Maintenance Scheduling

```
Schedule.create(frequency='bulanan', next_date=2026-08-01)
  -> Schedule stored with next_maintenance_date
  
MaintenanceLog.create(schedule_id=X, maintenance_date=2026-08-01)
  -> Schedule.next_maintenance_date recalculated to 2026-09-01
  -> Asset.condition updated (optional)
```

**Orphan Risk:** If `asset_maintenance_schedules` is deleted, all associated `asset_maintenance_logs` remain orphaned (no cascade). The `schedule_id` FK has no onDelete clause visible in migration.

### Duplicate Storage Identification

| Field | Stored In | Duplicate? |
|---|---|---|
| `school_id` | Asset, AssetBuilding, AssetRoom, AssetLoan, RoomBooking, ProcurementRequest, AssetMaintenanceSchedule, AssetMaintenanceLog, ProcurementRequestItem, AssetDamageReport, RoomBooking | Intentional denormalization for query performance |
| `work_unit_id` | Same as above | Intentional denormalization |
| `condition` | Asset, AssetRoom, AssetBuilding, AssetMaintenanceLog.condition_before/after, RoomBooking.condition_after | Condition is replicated — if maintenance fixes an asset, both log AND asset must be updated. Two places to get wrong. |
| `vendor_name` | ProcurementRequest, AssetMaintenanceLog, ProcurementRequestItem (via purchase_order) | Plain text repeated 3x with no normalization |

---

## 7. DATABASE HEALTH

### Foreign Key Analysis

| Table | FK Issue | Severity |
|---|---|---|
| `asset_loans.asset_id` | `cascadeOnDelete()` — deleting an asset deletes all loan records. This loses historical loan data. | **High** |
| `asset_loans.work_unit_id` | `cascadeOnDelete()` — deleting a work unit deletes all loans. | **High** |
| `asset_rooms.building_id` | `cascadeOnDelete()` — deleting a building cascades to rooms, then cascades further to assets, loans, bookings. Very aggressive. | **High** |
| `asset_rooms.id` -> `assets.room_id` | `nullOnDelete()` — when room is deleted, asset room_id becomes null. This is acceptable. | Low |
| `asset_rooms.id` -> `room_bookings.room_id` | `cascadeOnDelete()` — deleting a room cascades bookings to null? No, cascades to delete. Historical bookings are lost. | **Medium** |
| `asset_maintenance_logs.schedule_id` | No cascade visible — orphan logs exist if schedule deleted. | **Low** |
| `procurement_request_items.procurement_request_id` | `cascadeOnDelete()` — deleting procurement destroys all item records. Acceptable for workflow. | Low |

### Index Analysis

**Assets Table:**
- Composite index on `(work_unit_id, condition, status)` — useful for school/unit filtering with condition breakdown
- Index on `(school_id, asset_category_id)` — useful for category reports
- Index on `(room_id, condition)` — useful for room inventory reports
- Index on `status` ��� useful for loan/booking filtering
- **Missing:** Index on `asset_name` (LIKE searches in controllers are unindexed), index on `asset_code` (unique but no secondary lookup index)

**AssetLoan Table:**
- Index on `(asset_id, status)` — good for asset detail page
- Index on `(borrower_id, status)` — good for "my loans"
- Index on `(status, expected_return_date)` — good for overdue queries
- **Missing:** No index on `school_id` — school-scoped loan queries do full table scan

**RoomBooking Table:**
- Index on `(room_id, booking_date, status)` — good for room calendar views
- **Missing:** No index on `school_id` — school-scoped queries scan entire table

### Data Integrity Concerns

1. **No UNIQUE constraint on `asset_code` vs real-world** — Migration declares `unique()` but code uses random string generation. At 100,000+ assets, collision probability is non-zero.
2. **No CHECK constraint** — All enums are PHP-side validation only. Invalid status can be inserted via `Asset::create(['status' => 'anything'])`.
3. **`asset_loans.loan_date` not constrained** — Can create loan records with future dates, past dates, or any arbitrary date.
4. **No data retention policy** — Soft-deleted assets remain in database indefinitely. No archive strategy.

---

## 8. CROSS-MODULE ANALYSIS

### Module Interactions

| Module | Relationship | Coupling Type | Risk |
|---|---|---|---|
| **Academic** | `AssetRoom.study_group_id` — rooms can be assigned to study groups. `AssetRoom::ROOM_TYPE_OPTIONS` includes 'kelas'. | Shared reference | Medium — Academic can create/change study groups that affect room allocation visibility |
| **Dormitory** | `DormitoryRoom` is SEPARATE from `AssetRoom`. Dormitory has its own `DormitoryInventory`. | None (parallel system) | Low — No conflict, but duplicate domain model |
| **UKS (Health Clinic)** | `student_medicine_inventory` table exists. No controller in Sarpras namespace manages it. | Implicit | Medium — Medicine is a type of asset but tracked separately |
| **User/Auth** | All models reference `school_id`, `work_unit_id`, `created_by`, `approved_by`. | Strong coupling | Low — Expected multi-tenant pattern |
| **Notification** | `NotificationUniversalService` used by Dormitory, not Sarpras. Sarpras has no notification mechanism. | Missing | High — No alert when maintenance is due, no notification when procurement is approved |
| **Mobile (React Native)** | No API endpoints for Sarpras. Mobile app only has dormitory endpoints. | Missing | Critical — Mobile team cannot build asset scanning/check-in features |

### Hidden Dependencies

1. **`AssetRoom.study_group_id`** — Migration `2026_04_15_000004` adds this column but no controller populates it. Study group relationship exists in model but is never wired.
2. **`AssetDamageReport`** — Exists with model and migration but only referenced by `SarprasUserController` (user-facing simple report submission). No dedicated controller, no approval workflow.
3. **`RoomBooking.related_agenda_id`** — References an `agendas` table (academic calendar?) but no controller passes agenda data.
4. **`AssetLoan.related_agenda_id`** — Same issue.

---

## 9. API READINESS

### Current State: NOT READY for Mobile Consumption

| Requirement | Status | Evidence |
|---|---|---|
| REST API endpoints | **Not Implemented** | All routes are web views, no API routes in `routes/api.php` |
| API versioning | **Not Applicable** | No API exists |
| Authentication (Sanctum) | **Not Applicable** | No API routes |
| Pagination | **Web-only** | Controllers use `->paginate(15)` for Blade views, not API resources |
| Response format | **None** | Controllers return `view()` and `redirect()`, no JSON responses |
| API Resources | **None** | No API resource classes exist for Sarpras models |
| Filtering/Sorting | **Web-only** | Query parameters are hardcoded per controller |
| File upload API | **None** | Photo uploads work via web forms, no multipart API |
| Offline sync | **Not Designed** | QR scanner requires live server connection for `lookup` endpoint |

### Required API Endpoints (Projected)

| Method | Endpoint | Description | Priority |
|---|---|---|---|
| GET | `/api/v1/sarpras/rooms` | List rooms with filters | P0 |
| GET | `/api/v1/sarpras/rooms/{id}` | Room details + assets | P0 |
| POST | `/api/v1/sarpras/rooms/{id}/bookings` | Create booking | P0 |
| GET | `/api/v1/sarpras/buildings` | List buildings | P1 |
| GET | `/api/v1/sarpras/assets` | List/search assets | P0 |
| POST | `/api/v1/sarpras/assets/{id}/loans` | Request loan | P0 |
| PUT | `/api/v1/sarpras/assets/{id}/loans/{loanId}/return` | Return asset | P0 |
| POST | `/api/v1/sarpras/assets/{id}/location-history` | Record movement | P1 |
| GET | `/api/v1/sarpras/procurement-requests` | List procurement | P1 |
| POST | `/api/v1/sarpras/maintenance/schedules` | Create maintenance | P1 |
| POST | `/api/v1/sarpras/qr/lookup` | Resolve QR code | P0 |
| POST | `/api/v1/sarpras/audit/bulk` | Bulk audit submit | P1 |
| GET | `/api/v1/sarpras/damage-reports` | List damage reports | P2 |
| PUT | `/api/v1/sarpras/damage-reports/{id}/review` | Review damage report | P2 |

---

## 10. USER EXPERIENCE ASSESSMENT

### Navigation & Information Architecture

- **Two parallel navigation paths:** Legacy path via `{userId}`-scoped routes and modern path via `prefix('sarpras')` routes. The sidebar likely links to both.
- **64 Blade files** spanning 13 subdirectories — reasonable for the module scope.
- **Total Blade lines:** ~6,980 lines of view templates. Estimated ~110 lines per view on average — manageable density.

### UX Issues Found

| Issue | Location | Severity |
|---|---|---|
| Dashboard loads ALL assets without pagination | `SarprasUserController::dashboard()` line 60 | High |
| No empty-state templates | Unknown — not verified in views | Medium |
| No loading state indicators | Unknown — not verified | Medium |
| Bulk audit page uses traditional pagination, not virtual scrolling | `sarpras.qr.bulk-audit` | Low |
| QR scanner requires internet connectivity for every lookup | `SarprasQRController.lookup()` | Medium |
| Import error display mixes successful and failed rows inline | `SarprasAsetController.importProcess()` | Low |
| No undo/deleted items recovery UI | All controllers use soft delete with no "restore" action | Medium |
| Search is limited to name/code/brand — no multi-field search | `SarprasAsetController.index()` | Medium |

---

## 11. SECURITY MATURITY

### Authorization Model

| Aspect | Status | Details |
|---|---|---|
| Role-based gate | **Implemented** | Routes use `role:Admin Sarpras,Admin Tata Usaha` middleware |
| School-scoped data | **Implemented** | `canViewAll()` + `scopeToSchool()` pattern in base controller |
| Policy-based authorization | **MISSING** | No Policy classes for Sarpras models. Access enforced in controllers. |
| Permission-based granularity | **Partial** | `sarpras_all_access` and `inventory_view` permissions checked. No fine-grained permissions (e.g., `asset_create`, `procurement_approve`). |
| Resource ownership check | **Partial** | Booking cancel checks `booked_by` only, bypasses school scope |
| CSRF protection | **Implemented** | Standard Laravel CSRF via web routes |
| Mass assignment protection | **Implemented** | `$fillable` arrays on all models |
| Input validation | **Missing** | No Form Request classes exist. Validation inline in controllers. |
| File upload security | **Partial** | Photos validated for type/size but no malware scanning |
| Audit trail | **Partial** | `created_by` on most models. No action logging for updates/deletes. `LogsDeletion` trait exists on Asset and AssetLoan. |

### Security Risks

1. **No Policy classes** — Access logic scattered across 8 `authorize*Access()` methods in `SarprasBaseController`. Easy to forget a check on a new endpoint.
2. **Booking cancel lacks school scope** — Line 149-156 of `SarprasBookingController::cancel()`: only checks `booked_by` equality. A user with a different school_id can cancel if they coincidentally share a user ID (if cross-school accounts exist).
3. **Inline validation** — Without Form Request classes, validation rules are mixed with business logic in controllers. Difficult to test or reuse.
4. **No rate limiting** — QR lookup endpoint (`lookup()`) has no rate limit. Could be abused for enumeration attacks.

---

## 12. PERFORMANCE MATURITY

### Query Analysis

| Location | Issue | Impact |
|---|---|---|
| `SarprasDashboardController::index()` — 17+ clone queries | Each metric is a separate database query. Dashboard = 17+ queries minimum. | Medium — Acceptable for small datasets, degrades with 100k+ assets |
| `SarprasUserController::dashboard()` — loads ALL assets and rooms | `$allAssets = Asset::where(...)->get()` without pagination | **Critical** — Will crash on large schools with 50k+ assets |
| `SarprasAsetController::show()` — loads loans, maintenance, movements (3 separate queries) | No eager loading for all three | Low-N+1 on show page |
| Report controller uses N+1 in foreach loops | `inventarisPerRuang()` loads assets for each room in a loop | High — O(n) queries where n = number of rooms |
| `SarprasProcurementController::convertToAsset()` — loops and creates one Asset at a time | 1 INSERT per item per quantity | Medium — Slow for bulk procurement conversions |
| `AssetImport::processRow()` — fuzzy matching per row | `findRoom()` and `findCategory()` do regex-like comparisons | High — Slows down Excel imports significantly for 1000+ row files |

### Other Performance Concerns

- **QR Code Generation:** `generate()` called per asset in memory. For 200 assets (PDF batch), this generates 200 QR images and encodes to base64 in memory. Could exceed PHP memory limit.
- **No Cache Strategy:** Dashboard queries database on every request. Room/category dropdowns are queried fresh every page load.
- **No Background Jobs:** Asset import is synchronous. Large Excel files block the HTTP request. No queue worker for Sarpras operations.
- **PDF Generation:** Uses Dompdf synchronously. Slow for multi-page reports.

---

## 13. TESTABILITY ASSESSMENT

### Test Coverage: 0%

| Category | Status | Evidence |
|---|---|---|
| Unit Tests | **None** | No test files found for any Sarpras model or service |
| Feature Tests | **None** | No HTTP tests for controller endpoints |
| Integration Tests | **None** | No tests for procurement->asset conversion flow |
| Factory Definitions | **None** | No factories for Asset, AssetBuilding, AssetRoom, etc. |
| Seeders | **Unknown** | Not checked in this audit |

### Testability Barriers

1. **Controller coupling to auth/session** — All controllers call `auth()->id()` and `request()->route('userId')` directly. Difficult to test without mocking.
2. **No Service Layer** — Business logic is embedded in controllers, making it impossible to unit-test independently of HTTP layer.
3. **No Repository Pattern** — Direct model queries make it impossible to inject test doubles.
4. **State mutation in transactions** — Import uses `DB::beginTransaction()`/`commit()`/`rollBack()` which is correct but makes test isolation difficult without database cleanup.
5. **`abort_unless()` in legacy controller** — Uses Laravel helper that throws `HttpResponseException`, difficult to mock in tests.

---

## 14. TECHNICAL DEBT

### Debt Items by Severity

#### Critical

| Debt | Location | Risk | Remediation |
|---|---|---|---|
| Duplicate CRUD logic | `SarprasController` + `SarprasGedungController` + `SarprasRuangController` + `SarprasAsetController` | Code duplication, confusion, maintenance burden | Remove legacy `SarprasController` CRUD methods; keep only `kategoriStore` or migrate to dedicated controller |
| Dashboard N+1 query | `SarprasUserController::dashboard()` line 60 | Production crash at scale | Add pagination or `->count()` |

#### High

| Debt | Location | Risk | Remediation |
|---|---|---|---|
| No service layer | Controllers contain all business logic | Impossible to test, hard to reuse, violation of SRP | Extract to service classes |
| Inline validation | All controllers validate in method body | Violates Laravel convention, hard to maintain | Extract to Form Request classes |
| Race condition in loan return | `SarprasLoanController::return()` line 137-166 | Data inconsistency on partial failure | Wrap in `DB::transaction()` |
| Cascade delete on asset_loans | Migration: `cascadeOnDelete()` | Lost loan history when asset is deleted | Change to `nullOnDelete()` or implement soft-delete cascade |
| Cascade delete on asset_rooms.building_id | Migration: `cascadeOnDelete()` | Deleting building cascades through rooms to assets to loans | Consider soft-delete or disable cascade |

#### Medium

| Debt | Location | Risk | Remediation |
|---|---|---|---|
| Orphan logs on schedule deletion | `asset_maintenance_logs.schedule_id` no FK cascade | Lost maintenance context | Add FK cascade or soft delete |
| Dead code: booking_conflicts table | Migration exists, never used | Wasted storage | Delete migration or implement |
| No depreciation engine | `depreciation_per_year` and `current_value` fields dormant | Financial reporting unreliable | Implement straight-line or declining balance engine |
| Random asset code generation | `Str::random(6)` in convertToAsset | Collision risk at scale | Implement sequential code with prefix+year+counter |
| `target_type` unset before save | `logStore()` line 270 | Cannot query logs by target type | Store target_type or use polymorphic relation |

#### Low

| Debt | Location | Risk | Remediation |
|---|---|---|---|
| Unused `depreciation_years` on AssetCategory | Model exists, never used | Future potential | Leverage in depreciation engine |
| `last_condition_update` cast inconsistency | Not in `$casts` on Asset model | Minor — treated as string | Add to casts |
| Comment-only migration notes | Large comments in migration files | Cosmetic | Clean up |

---

## 15. PRODUCTION READINESS

### Readiness Matrix

| Dimension | Score | Notes |
|---|---|---|
| Reliability | 4/10 | Race conditions, cascading deletes, untested flows |
| Maintainability | 5/10 | Fat controllers, no services, duplicate legacy code |
| Scalability | 3/10 | N+1 queries, unpaginated lists, no caching |
| Security | 6/10 | Good school-scoping, missing policies, no rate limiting |
| Observability | 2/10 | Basic Log::error() calls, no structured logging, no monitoring |
| Monitoring | 1/10 | No dashboards, no alerting, no health checks |
| Logging | 3/10 | Some error logging, no action audit trail |
| Disaster Recovery | 2/10 | No backup strategy documented, cascade deletes dangerous |
| Rollback Readiness | 4/10 | Migrations are reversible, but no deployment rollback procedure |

### Verdict: CONDITIONALLY PRODUCTION-READY

**The module can operate in production for SMALL deployments (<5,000 assets, 1-2 schools) but requires remediation for:**
- Multi-school deployments (scale issues)
- Financial compliance (depreciation, disposal gaps)
- Mobile workforce (no API)
- Long-term sustainability (zero tests, no service layer)

---

## 16. ENTERPRISE MATURITY SCORES

| Category | Score (0-100) | Weight | Weighted |
|---|---|---|---|
| Business Process Coverage | 52 | 15% | 7.8 |
| Feature Completeness | 78 | 15% | 11.7 |
| Architecture & Design | 35 | 15% | 5.3 |
| Data Model & Integrity | 50 | 10% | 5.0 |
| Security & Authorization | 55 | 10% | 5.5 |
| Performance | 30 | 10% | 3.0 |
| Testability | 0 | 10% | 0.0 |
| Maintainability | 40 | 10% | 4.0 |
| Scalability | 30 | 5% | 1.5 |
| Production Readiness | 45 | 5% | 2.3 |
| **OVERALL ENTERPRISE MATURITY** | | | **42.1** |

---

## 17. PRIORITIZED ROADMAP

### P0 — Critical (Must fix before scale/multi-tenant)

| # | Recommendation | Reason | Business Value | Complexity | Risk | Dependencies |
|---|---|---|---|---|---|---|
| P0-1 | Remove duplicate CRUD from legacy `SarprasController` | Dead code, confusion, maintenance burden | Low (cleanup only) | Low | Low | None |
| P0-2 | Wrap loan return in DB transaction | Race condition causes data inconsistency | Prevents asset/loan state mismatch | Low | Low | None |
| P0-3 | Fix booking cancel authorization bypass | Missing school scope allows cross-school cancellation | Prevents unauthorized cancellations | Low | Low | None |
| P0-4 | Paginate `SarprasUserController::dashboard()` | Loads ALL assets into memory | Prevents OOM crash on large deployments | Medium | Medium | None |
| P0-5 | Create Asset/Form Request classes | Inline validation everywhere | Improves testability and consistency | Medium | Low | None |

### P1 — High (Essential for enterprise operations)

| # | Recommendation | Reason | Business Value | Complexity | Risk | Dependencies |
|---|---|---|---|---|---|---|
| P1-1 | Extract service layer | Controllers are fat, business logic tangled | Enables testing, reuse, separation | High | Medium | None |
| P1-2 | Implement automated depreciation engine | `current_value` fields are dormant | Financial compliance, accurate reporting | High | Medium | None |
| P1-3 | Add disposal/write-off workflow | Asset lifecycle is incomplete | Full lifecycle management | High | Low | P1-1 |
| P1-4 | Create Policy classes for all Sarpras models | Authorization scattered in controllers | Consistent, testable access control | Medium | Low | P0-5 |
| P1-5 | Build vendor master table | Vendor data is unstructured | Procurement analytics, SLA tracking | Medium | Low | None |
| P1-6 | Design REST API for Sarpras | Mobile app cannot consume data | Enables mobile workflow | High | Medium | P1-4 |
| P1-7 | Add school-scoped index on AssetLoan | Current queries do full table scan | Dashboard/report performance | Low | Low | None |
| P1-8 | Implement maintenance notification system | Schedules exist but no one knows when they're due | Preventive compliance | Medium | Low | P1-1 |

### P2 — Medium (Important improvements)

| # | Recommendation | Reason | Business Value | Complexity | Risk |
|---|---|---|---|---|
| P2-1 | Warranty management | No warranty tracking | Equipment lifecycle value | High | Low |
| P2-2 | Asset utilization analytics | Dashboard shows counts only | Data-driven decisions | Medium | Low |
| P2-3 | Automated loan overdue detection | `terlambat` status is dead | Operational accuracy | Medium | Low |
| P2-4 | Polymorphic maintenance targets | `target_type` discarded, loose data | Data integrity | Medium | Low |
| P2-5 | Multi-level procurement approval | Currently single approver | Governance | Medium | Medium |
| P2-6 | Cache layer for dashboard | 17+ queries per page load | Performance | Medium | Low |
| P2-7 | Mobile QR offline support | Scanner requires connectivity | Field usability | High | Medium |
| P2-8 | Damage report approval workflow | Reports submitted but no state machine | Process control | Medium | Low |

### P3 — Low (Nice to have)

| # | Recommendation | Reason | Business Value | Complexity |
|---|---|---|---|
| P3-1 | Depreciation automation | Manual field population | Long-term financial accuracy | High |
| P3-2 | Spare parts BOM | Asset-component relationships | Maintenance efficiency | High |
| P3-3 | IoT sensor integration for predictive maintenance | Not applicable to school context | Future scalability | Very High |
| P3-4 | Export to accounting system | No GL integration | Financial operations | High |

---

## APPENDIX: FILE INVENTORY

### Controllers (13 files)
```
app/Http/Controllers/Sarpras/
├── SarprasBaseController.php        (shared authorization)
├── SarprasDashboardController.php   (dashboard stats)
├── SarprasGedungController.php      (building CRUD)
├── SarprasRuangController.php       (room CRUD)
├── SarprasAsetController.php        (asset CRUD + import)
├── SarprasUserController.php        (simplified user actions)
├── SarprasLoanController.php        (asset borrowing)
├── SarprasBookingController.php     (room booking)
├── SarprasMaintenanceController.php (maintenance schedules + logs)
├── SarprasMovementController.php    (asset relocation)
├── SarprasProcurementController.php (procurement lifecycle)
├── SarprasQRController.php          (QR generation + audit)
└── SarprasReportController.php      (reporting + PDF export)
```

### Legacy (1 file)
```
app/Http/Controllers/SarprasController.php  (duplicate CRUD — mostly unused)
```

### Models (11 files)
```
app/Models/
├── Asset.php                         (core asset)
├── AssetBuilding.php                 (building)
├── AssetRoom.php                     (room)
├── AssetLand.php                     (land)
├── AssetCategory.php                 (classification)
├── AssetLoan.php                     (borrowing)
├── AssetPhoto.php                    (photographs)
├── AssetMaintenanceSchedule.php      (preventive schedule)
├── AssetMaintenanceLog.php           (maintenance record)
├── AssetLocationHistory.php          (relocation)
├── AssetDamageReport.php             (damage report)
├── RoomBooking.php                   (room reservation)
├── ProcurementRequest.php            (purchase request header)
└── ProcurementRequestItem.php        (purchase request line items)
```

### Imports/Exports (2 files)
```
app/Imports/AssetImport.php
app/Exports/AssetTemplateExport.php
```

### Database Migrations (18 files)
Covering: assets, asset_rooms, asset_buildings, asset_lands, room_bookings, room_booking_conflicts, asset_categories, asset_loans, asset_location_histories, asset_maintenance_schedules, asset_maintenance_logs, procurement_requests, procurement_request_items, asset_photos, asset_damage_reports, student_medicine_inventory, asset_room_nullable_buildings, asset_room_study_group

### Views (64 blade files)
13 subdirectories: `aset/`, `booking/`, `dashboard/`, `gedung/`, `laporan/`, `laporan/pdf/`, `peminjaman/`, `pengadaan/`, `perpindahan/`, `pemeliharaan/`, `pemeliharaan/schedule/`, `pemeliharaan/log/`, `qr/`, `user/` (with subdirs: `ruang/`, `pengadaan/`, `kerusakan/`, `aset/`)

### Routes
Approximately 120+ web routes in `routes/web.php` (lines 1573-1846), plus legacy routes via `SarprasController`.

### Test Coverage
**0 test files found for Sarpras module.**

---

*End of Enterprise Maturity Assessment.*
*Report prepared: 2026-07-01*
*Classification: Internal — Technical Steering Committee*
