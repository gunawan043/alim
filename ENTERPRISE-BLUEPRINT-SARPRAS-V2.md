# ENTERPRISE AI ENGINEERING FRAMEWORK — EA-002
# ENTERPRISE CAPABILITY BENCHMARK & PRODUCT BLUEPRINT
# Subject: Sarpras (Sarana Prasarana) — V2 Architecture & Product Vision
# Assessor: Principal Enterprise Solution Architect
# Date: 2026-07-01

# =====================================================================
# TABLE OF CONTENTS
# =====================================================================
# Part 1  — Enterprise Capability Matrix
# Part 2  — Enterprise Feature Matrix (Competitive Benchmark)
# Part 3  — Domain Model (DDD)
# Part 4  — Business Lifecycle (Ideal Asset Lifecycle)
# Part 5  — Module Blueprint (Greenfield Redesign)
# Part 6  — Roadmap (V1 → V2 → V3 → V4)
# Part 7  — Enterprise Scores
# Part 8  — CTO Recommendations (Strategic Investments)

# =====================================================================
# PART 1 — ENTERPRISE CAPABILITY MATRIX
# =====================================================================

## Legend

Status:   [✓] Implemented  [~] Partial  [✗] Missing  [◌] Planned
Priority: P0 Critical  P1 High  P2 Medium  P3 Low

================================================================================
CAPABILITY MAP
================================================================================

| # | Capability               | Status | Enterprise Target                              | Gap | BizPri | TechPri | Complexity | Dependencies        |
|---|--------------------------|--------|-------------------------------------------------|-----|--------|---------|------------|---------------------|
| 1 | Asset Planning           | ~      | Capital expenditure forecasts, rolling budgets  | 30% | P1     | P1      | High       | None                |
| 2 | Asset Procurement        | ✓      | End-to-end PO workflow                          | 20% | P0     | P0      | Medium     | None                |
| 3 | Procurement Approval     | ~      | Multi-level, role-based, configurable chains    | 40% | P1     | P1      | High       | Policy framework    |
| 4 | Receiving                | ✓      | ASN, 3-way match, quality hold                  | 50% | P1     | P1      | Medium     | None                |
| 5 | Vendor Management        | ✗      | Vendor master, scoring, SLA, contracts          | 100%| P0     | P0      | High       | None                |
| 6 | Inventory                | ✓      | Real-time stock, location, condition, valuation | 30% | P0     | P0      | Medium     | None                |
| 7 | Asset Registration       | ✓      | Manual, bulk import, automated from procurement | 20% | P1     | P1      | Low        | None                |
| 8 | Asset Classification     | ✓      | Hierarchical taxonomy, industry standards       | 10%  | P2     | P2      | Low        | None                |
| 9 | Asset Coding             | ~      | Sequential, policy-driven, prefix/year/counter  | 40%  | P1     | P1      | Medium     | Asset engine refactor|
|10 | QR                       | ✓      | Print, scan, embed in reports                   | 10%  | P2     | P2      | Low        | None                |
|11 | Barcode                  | ✗      | 1D/2D barcode fallback, RFID readiness          | 100% | P1     | P1      | Medium     | None                |
|12 | RFID Readiness           | ✗      | Tag schema, read-point tables, auto-tracking    | 100% | P2     | P2      | High       | Barcode              |
|13 | Asset Assignment         | ~      | Location-based + person-based, delegation       | 50%  | P2     | P1      | Medium     | None                |
|14 | Room Management          | ✓      | Full building→room hierarchy                    | 10%  | P2     | P2      | Low        | None                |
|15 | Building Management      | ✓      | CRUD, multi-floor, ownership, IMB               | 10%  | P3     | P3      | Low        | None                |
|16 | Borrowing                | ~      | Digital check-out, condition handshake, SLA     | 30%  | P1     | P1      | Medium     | None                |
|17 | Reservation              | ✓      | Real-time availability, conflict prevention     | 20%  | P1     | P1      | Medium     | None                |
|18 | Transfer                 | ✓      | Chain-of-custody audit trail                    | 15%  | P2     | P2      | Medium     | None                |
|19 | Maintenance              | ~      | Work-order system, technician dispatch          | 50%  | P0     | P0      | High       | Vendor mgmt          |
|20 | Preventive Maintenance   | ~      | Auto-triggered, calendar + usage-based          | 40%  | P1     | P1      | Medium     | Scheduler infrastructure |
|21 | Corrective Maintenance   | ~      | Ticketing, triage, SLA, escalation              | 40%  | P1     | P1      | High       | None                |
|22 | Inspection               | ~      | Standardized checklists, mobile forms            | 40%  | P1     | P1      | Medium     | None                |
|23 | Warranty                 | ✗      | Claim tracking, expiry alerts, provider linking | 100% | P0     | P1      | High       | Vendor mgmt          |
|24 | Insurance                | ✗      | Policy registry, coverage mapping, claims        | 100% | P1     | P2      | High       | Finance integration  |
|25 | Depreciation             | ✗      | Automated straight-line, declining balance       | 100% | P0     | P1      | High       | Finance integration  |
|26 | Financial Integration    | ✗      | GL sync, CAPEX/OPEX tagging, cost center         | 100% | P0     | P0      | Critical   | Depreciation         |
|27 | Asset Disposal           | ✗      | Approval workflow, scrap value, disposal method  | 100% | P0     | P1      | High       | Financial integration|
|28 | Lost Asset               | ~      | Investigation workflow, insurance claim          | 50%  | P1     | P1      | Medium     | None                |
|29 | Asset Audit              | ✓      | QR-driven single & bulk audit                    | 10%  | P1     | P1      | Low        | None                |
|30 | Reporting                | ~      | Standard + ad-hoc report builder                 | 40%  | P2     | P1      | Medium     | Analytics engine     |
|31 | Analytics                | ✗      | Trends, utilization, predictive maintenance      | 100% | P1     | P1      | High       | Depreciation         |
|32 | Dashboard                | ~      | Role-based, configurable widgets                 | 40%  | P2     | P2      | Medium     | None                |
|33 | Notification             | ✗      | Email, SMS, push — event-driven                  | 100% | P0     | P0      | High       | None                 |
|34 | Document Attachment      | ~      | Multi-file, virus scan, versioning               | 40%  | P2     | P2      | Medium     | None                 |
|35 | Approval Workflow        | ~      | Configurable, multi-level, delegation            | 50%  | P0     | P0      | High       | Policy framework     |
|36 | Audit Trail              | ~      | Full CRUD history, who-what-when, immutable      | 50%  | P0     | P0      | Medium     | None                 |
|37 | Mobile Support           | ✗      | Full responsive/mobile-first UI                  | 100% | P0     | P0      | Critical   | API first            |
|38 | Offline Support          | ✗      | Cached scans, queued submissions, sync           | 100% | P2     | P2      | Critical   | Mobile + API          |
|39 | API                      | ✗      | REST/GraphQL, versioned, paginated               | 100% | P0     | P0      | Critical   | Service layer        |
|40 | Integration              | ✗      | Event bus, webhooks, external system connectors  | 100% | P1     | P1      | High       | API                  |
|41 | Search                   | ~      | Multi-field, full-text, faceted                  | 40%  | P1     | P1      | Medium     | None                 |
|42 | Filtering                | ✓      | Per-resource filters                             | 20%  | P2     | P2      | Low        | None                 |
|43 | Sorting                  | ~      | Multi-column, ASC/DESC                           | 40%  | P2     | P2      | Low        | None                 |
|44 | Bulk Operations          | ~      | Bulk approve, bulk assign, bulk audit             | 40%  | P1     | P1      | Medium     | None                 |
|45 | Export                   | ~      | CSV, Excel, PDF, scheduled exports               | 30%  | P2     | P2      | Low        | None                 |
|46 | Import                   | ✓      | Excel/CSV with mapping, validation, preview       | 15%  | P1     | P1      | Medium     | None                 |

================================================================================
SUMMARY
================================================================================

Capability Coverage:  14/46 fully implemented  |  12/46 partially  |  20/46 missing
Enterprise Gap:       43% gap across all capabilities
Missing Domains:      Vendor, Warranty, Insurance, Depreciation Engine,
                      Financial Integration, Disposal, API, Mobile,
                      Notifications, Offline, Analytics, Integration

================================================================================
# PART 2 — ENTERPRISE FEATURE MATRIX (COMPETITIVE BENCHMARK)
================================================================================

## Comparison Dimensions

We compare business capabilities only — never UI.

| # | Capability                     | ALIM Sarpras (V1) | SAP EAM | Oracle EAM | ERPNext | IBM Maximo | Odoo Assets |
|---|--------------------------------|-------------------|---------|------------|---------|------------|-------------|
| 1 | Full asset lifecycle (plan→disposal) | No (missing disposal, depreciation) | Yes | Yes | Partial | Yes | Partial |
| 2 | Automated depreciation (SLDB, declining) | No (dormant fields) | Yes | Yes | Yes | Yes | Yes |
| 3 | Work order management | No (ad-hoc logs only) | Yes | Yes | Yes | Yes (core) | No |
| 4 | Vendor master & scoring | No (flat string) | Yes | Yes | Yes | Yes | Yes |
| 5 | Warranty tracking & claims | No | Yes | Yes | Yes | Yes | Yes |
| 6 | Multi-level approval | No (single approver) | Yes | Yes | Yes | Yes (custom) | Yes |
| 7 | SLA management | No | Yes | Yes | No | Yes (core) | No |
| 8 | Asset utilization analytics | No (count-only dashboard) | Yes | Yes | Partial | Yes | No |
| 9 | Predictive maintenance (IoT/usage-based) | No (calendar-only) | Yes (IoT) | Yes (AI) | No | Yes (AI) | No |
|10 | Finance/GL integration | None | Yes | Yes | Partial | Yes (partner) | Yes |
|11 | CAPEX forecasting | No budget aggregation | Yes | Yes | Partial | Partial | No |
|12 | Location hierarchy (site→building→room→shelf) | Building→Room→Asset | Yes | Yes | Yes | Yes | No |
|13 | Serial/asset code tracking | Random UUID (collision risk) | Yes | Yes | Yes | Yes | Yes |
|14 | QR/Barcode/RFID | QR only | Yes | Yes | Yes | Yes (RFID) | Yes |
|15 | Mobile offline scanning | Online only | Yes | Yes | Yes | Yes (core) | Yes |
|16 | Multi-entity / multi-site | School-scoped (partial) | Yes | Yes | Partial | Yes | Yes |
|17 | Audit trail (immutable) | Soft delete + created_by | Yes | Yes | Yes | Yes (core) | Partial |
|18 | Insurance mapping | None | Yes | Yes | No | Yes | No |
|19 | Disposal / write-off workflow | None (soft delete only) | Yes | Yes | Yes | Yes | Yes |
|20 | Parts / BOM management | None | Yes | Yes | Yes | Yes (core) | No |
|21 | Contract management | None | Yes | Yes | Partial | Yes | Yes |
|22 | Document management | Single photo + note | Yes (DMS) | Yes (DMS) | Partial | Yes (DMS) | Partial |
|23 | Automated notifications | None | Yes | Yes | Yes | Yes (core) | Yes |
|24 | REST API | None | Yes | Yes | Yes | Yes (core) | Yes |
|25 | Compliance reporting | Basic PDF | Yes | Yes | Partial | Yes | Partial |
|26 | Cost tracking (CAPEX/OPEX) | Acquisition price only | Yes | Yes | Partial | Yes | Partial |
|27 | Asset grouping / portfolio | Basic categories | Yes | Yes | Yes | Yes | Partial |
|28 | Condition monitoring trends | Point-in-time only | Yes | Yes | No | Yes (core) | No |
|29 | Service technician dispatch | No | No | Partial | No | Yes (core) | No |
|30 | Multi-currency support | IDR only | Yes | Yes | Yes | Yes | Yes |

## Identified Enterprise Gaps

### Missing Workflows
- No disposal/disposition workflow (critical for financial compliance)
- No warranty claim workflow
- No insurance claim workflow
- No purchase order creation (skips straight from approved to delivered)
- No work order lifecycle (no dispatch, no assignment, no parts consumption)

### Missing Modules
- Vendor Management module (no master data, no scoring, no contracts)
- Finance / Accounting module (no GL sync, no depreciation engine, no CAPEX)
- Service Technician module (no dispatch, no skills matrix, no shift scheduling)
- Parts / Spare Parts module (no BOM, no inventory of consumables)
- Contract Management module (no vendor SLA contracts, no service agreements)

### Missing Automation
- No scheduled depreciation recalculation (cron/job)
- No automatic maintenance schedule triggers
- No overdue detection jobs (loan overdue, maintenance overdue, warranty expiry)
- No notification system (email/SMS/push)
- No automated report scheduling (daily/weekly/monthly digest)

### Missing Governance
- No segregation of duties enforcement (requester != approver != receiver)
- No approval delegation / vacation mode
- No policy framework (configurable rules per organization)
- No immutable audit log (only soft delete, no action history)

### Missing Compliance
- No Indonesian government asset standard (PMK 102/2022 equivalent)
- No GAAP / IFRS depreciation compliance
- No data retention / archival policy
- No role-based access matrix (only coarse-grained role middleware)

### Missing Financial Integration
- No chart of accounts linkage
- No cost center mapping
- No capitalization threshold configuration
- No accumulated depreciation ledger
- No disposal gain/loss calculation

================================================================================
# PART 3 — DOMAIN MODEL (DOMAIN-DRIVEN DESIGN)
================================================================================

## 1. Asset Core Domain
**Responsibilities:** Define, register, classify, value, and retire assets.  
**Boundary:** Owns the Asset aggregate root, all asset subtypes (building, room, land, equipment, furniture).  
**Ownership:** Sarpras module owns this domain completely.  
**Relationships:** Feeds into Maintenance, Procurement, Finance, Audit.  
**Scalability:** Core table will grow to 100k+ records. Requires partitioning strategy at scale.

**Key Entities:**
- Asset (aggregate root) — uuid, code, name, category, condition, value, location, lifecycle_status
- AssetType — abstract base: equipment, furniture, building, land, vehicle, IT
- AssetLifecycle — registered → assigned → transferred → maintained → audited → disposed
- AssetValuation — acquisition_cost, accumulated_depreciation, current_book_value, salvage_value

---

## 2. Procurement Domain
**Responsibilities:** Plan, request, approve, order, receive, and convert to assets.  
**Boundary:** Self-contained workflow from requisition to asset registration.  
**Ownership:** Sarpras owns procurement; Finance consumes the conversion data.  
**Relationships:** Consumes Vendor data. Produces Asset records.  
**Scalability:** Low volume (hundreds of requests/year). No partitioning needed.

**Key Entities:**
- ProcurementRequest (aggregate) — status: draft→pending→review→approved→ordered→delivered→completed
- ProcurementItem — category, quantity, estimated_cost, specification
- PurchaseOrder — external PO number, vendor, delivery terms, expected date
- ReceivingReport — actual_quantity, actual_cost, quality_check, condition_on_arrival
- BudgetAllocation — fiscal_year, cost_center, available_amount, committed_amount, spent_amount

---

## 3. Vendor Domain
**Responsibilities:** Maintain vendor master data, evaluate performance, manage contracts.  
**Boundary:** Isolated domain — can be consumed by Procurement, Maintenance, Finance.  
**Ownership:** Shared — Sarpras maintains it, but it is a cross-cutting concern.  
**Relationships:** Used by Procurement (purchase), Maintenance (service providers), Finance (payments).  
**Scalability:** Low volume (tens to hundreds of vendors). Simple indexed table.

**Key Entities:**
- Vendor — name, type (supplier/service), tax_id, contact, address, rating, status
- VendorContract — vendor_id, scope, start_date, end_date, terms, SLA, renewal_policy
- VendorTransaction — procurement_orders, maintenance_jobs, total_spend, on_time_rate, quality_score
- VendorEvaluation — periodic scoring: quality, timeliness, responsiveness, pricing

---

## 4. Maintenance Domain
**Responsibilities:** Track, schedule, execute, and verify all maintenance activities.  
**Boundary:** Self-contained maintenance lifecycle with work order aggregation.  
**Ownership:** Sarpras owns. Can be extended by a future "Facility Management" module.  
**Relationships:** Consumes Asset data. Produces maintenance costs that flow to Finance.  
**Scalability:** Medium — thousands of maintenance logs per institution annually.

**Key Entities:**
- MaintenanceSchedule — asset_id, type (preventive/corrective), frequency, next_due
- MaintenanceLog (aggregate) — type, date, technician, cost, parts_used, before/after condition
- WorkOrder — ticket number, priority, assignee, status: open→assigned→in_progress→resolved→verified
- MaintenancePolicy — rule-based triggers: "replace AC filter every 90 days", "inspect roof annually"
- SparePart — part_number, description, stock_level, reorder_point, unit_cost

---

## 5. Inventory Domain
**Responsibilities:** Track asset location, movement, lending, and borrowing.  
**Boundary:** Contains AssetLoan and RoomBooking aggregates.  
**Ownership:** Sarpras owns. Consumed by Academic (room scheduling) and User (my loans).  
**Relationships:** Connected to Room, Building, Asset, User.  
**Scalability:** Medium — active loans fluctuate seasonally. Needs index on (school_id, status, date).

**Key Entities:**
- AssetLoan (aggregate) — asset_id, borrower, status: requested→approved→checked_out→returned
- RoomBooking (aggregate) — room_id, booker, status: requested→approved→active→completed/cancelled
- AssetMovement (aggregate) — from_location, to_location, date, reason, authorized_by
- InventoryPosition — snapshot of where every asset is at any point in time

---

## 6. Audit Domain
**Responsibilities:** Verify physical existence, condition, and location of assets.  
**Boundary:** Isolated verification domain — produces audit reports.  
**Ownership:** Sarpras owns. Reports consumed by Finance (verification) and Admin (compliance).  
**Relationships:** Consumes Asset, Building, Room.  
**Scalability:** Low-medium — typically conducted quarterly or annually. Batch processing model.

**Key Entities:**
- AuditCycle (aggregate) — scope, date_range, auditor, status: planned→in_progress→reviewed→approved
- AuditFinding — asset_id, expected_location, actual_location, expected_condition, actual_condition
- PhysicalVerification — QR scan proof, photo evidence, verifier signature
- AuditReport — summary, discrepancies, recommendations, sign-off

---

## 7. Finance Domain (NEW)
**Responsibilities:** Asset valuation, depreciation, financial reporting, disposal gains/losses.  
**Boundary:** New domain. Connects asset data to financial records.  
**Ownership:** Shared — Sarpras calculates, Finance posts to GL.  
**Relationships:** Consumes Asset depreciation data. Produces GL journal entries.  
**Scalability:** Medium — monthly depreciation runs, annual revaluations.

**Key Entities:**
- DepreciationPolicy — method (straight_line, declining_balance, units_of_production), useful_life, salvage_pct
- DepreciationLedger (aggregate) — asset_id, period, opening_value, depreciation_amount, closing_value
- AssetRevaluation — date, appraised_value, appreciation, reason
- DisposalRecord — date, method (sell/scrap/transfer), proceeds, book_value, gain_loss

---

## 8. Notification Domain (NEW)
**Responsibilities:** Event-driven alerts and digests.  
**Boundary:** Cross-cutting domain consumed by all other domains.  
**Ownership:** Platform-level — built into Sarpras core but used by everyone.  
**Relationships:** Event bus receives events from all domains. Sends via email/SMS/push.  
**Scalability:** High volume — every maintenance, loan, procurement event triggers notifications.

**Key Entities:**
- NotificationRule (aggregate) — event_type, recipient_roles, channels, throttling, quiet_hours
- NotificationEvent — source_domain, event_payload, timestamp
- NotificationLog — sent_at, channel, status, delivery_receipt

---

## 9. Analytics Domain (NEW)
**Responsibilities:** Trends, dashboards, predictive insights.  
**Boundary:** Read-optimized analytical domain separate from OLTP.  
**Ownership:** Sarpras owns. Can evolve into a standalone BI module.  
**Relationships:** Reads aggregated data from Asset, Procurement, Maintenance, Audit.  
**Scalability:** Needs materialized views or data warehouse at scale.

**Key Entities:**
- MetricDefinition — name, formula, aggregation_period, refresh_interval
- TrendData — asset_condition_trend, maintenance_cost_trend, procurement_by_category
- UtilizationIndex — room_usage_pct, asset_idle_time, loan_turnover_rate
- PredictiveInsight — warranty_expiry_warning, maintenance_due_forecast, budget_projected

---

## Domain Relationship Map

```
┌──────────────┐
│  Asset Core   │ ◄─── anchor domain ─── all other domains relate here
└──────┬───────┘
       │
   ┌───┼──────────────────────────────────────┐
   ▼   ▼                                      ▼
┌──────┐ ┌──────────┐                   ┌──────────┐
│Procure │ │ Inventory│                   │  Audit   │
│  ment  │ │(Loan/    │                   │          │
│        │ │ Booking) │                   └────┬─────┘
└───┬────┘ └──────────┘                          │
    │                                           │
    ▼                                           ▼
┌──────────┐                                ┌──────────┐
│  Vendor   │                                │Finance   │
│  Domain   │                                │(Deprec.) │
└──────────┘                                └────┬─────┘
    ▲                                             │
    └──── combined ─── maintenance cost data ─────┘
                      ▼
               ┌────────────┐
               │ Maintenance │
               │   Domain    │
               └──────┬─────┘
                      │
    ┌─────────────────┼─────────────────┐
    ▼                 ▼                 ▼
┌────────┐     ┌────────────┐    ┌────────────┐
│Notify. │     │ Analytics  │    │   Mobile   │
│ Domain │     │   Domain   │    │  Gateway   │
└────────┘     └────────────┘    └────────────┘
```

================================================================================
# PART 4 — BUSINESS LIFECYCLE (IDEAL ASSET LIFECYCLE)
================================================================================

## THE COMPLETE ASSET LIFECYCLE

```
                    ╔═══════════════════════════════════════╗
                    ║         PHASE 1: PLANNING             ║
                    ╚═══════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│ Step 1.1: Annual Planning                                    │
│   - Department submits Yearly Asset Plan (YAP)               │
│   - YAP includes: projected costs, categories, urgency      │
│   - Finance reviews against budget ceiling                    │
│   - YAP approved → becomes baseline budget                   │
│                                                               │
│ Step 1.2: Capital Expenditure Forecast                       │
│   - Rolling 3-year forecast                                  │
│   - Links to depreciation schedule (replacement planning)    │
│   - Trigger: "X% of assets past useful life → recommend RFP" │
└─────────────────────────────────────────────────────────────┘


                    ╔═══════════════════════════════════════╗
                    ║      PHASE 2: PROCUREMENT             ║
                    ╚═══════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│ Step 2.1: Requisition                                       │
│   - User creates ProcurementRequest                        │
│   - References YAP line item or budget code                 │
│   - Attach specifications, estimated quantities, vendors    │
│                                                               │
│ Step 2.2: Approval Workflow (configurable chain)            │
│   - Line 1: Department Head approves (technical fit)       │
│   - Line 2: Finance approves (budget availability)          │
│   - Line 3: Director approves (above threshold)             │
│   - Rejection → notifies requester with reason              │
│   - Resubmit allowed after revision                         │
│                                                               │
│ Step 2.3: Purchase Order Creation                           │
│   - Approved requisition → generates external PO            │
│   - Sent to vendor via email/PDF                            │
│   - Expected delivery date captured                         │
│   - SLA terms: delivery window, penalty for late            │
│                                                               │
│ Step 2.4: Goods Receipt / Inspection                        │
│   - Receiving officer inspects upon delivery                │
│   - Three-way match: PO vs Delivery Note vs Inspection      │
│   - Records: actual quantity, actual cost, condition,       │
│     photos, discrepancies                                    │
│   - Quality HOLD → quarantine assets (cannot register yet)  │
│   - Quality PASS → proceed to asset registration              │
│   - Notification to requester: "Your items received"        │
└─────────────────────────────────────────────────────────────┘


                    ╔═══════════════════════════════════════╗
                    ║     PHASE 3: REGISTRATION             ║
                    ╚═══════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│ Step 3.1: Asset Creation                                    │
│   - Each received item → Asset record                       │
│   - Asset code assigned: PREFIX-YEAR-SEQUENCE (e.g., IT-26-0042) │
│   - Category linked from procurement category               │
│   - Location assigned (Building → Room → Shelf)             │
│   - Condition documented (new)                              │
│   - Acquisition cost, funding source, supplier captured     │
│   - Warranty start date recorded                            │
│   - Acquisition document attached (PO, invoice, BAST)       │
│                                                               │
│ Step 3.2: Label Generation                                  │
│   - QR Code generated (contains asset_code)                 │
│   - Barcode fallback printed (1D Code128)                   │
│   - RFID tag affixed (if high-value mobile asset)           │
│   - Label PDF downloaded for printing                       │
│   - Notification to asset custodian: "Label ready"          │
│                                                               │
│ Step 3.3: Acknowledgment                                    │
│   - Custodian receives asset (digital acknowledgment)       │
│   - Signs receipt in system                                 │
│   - Asset lifecycle status: REGISTERED → ACTIVE             │
└─────────────────────────────────────────────────────────────┘


                    ╔═══════════════════════════════════════╗
                    ║     PHASE 4: OPERATIONS               ║
                    ╚═══════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│ Step 4.1: Asset Assignment                                  │
���   - Physical assignment: Building → Room → Rack/Shelf       │
│   - Custodial assignment: User → Asset (for portable items) │
│   - Both can coexist (a laptop is IN Room 201, BORROWED    │
│     by Teacher A)                                           │
│                                                               │
│ Step 4.2: Lending / Borrowing                               │
│   - User requests asset → approved by custodian             │
│   - Condition checked out → recorded                        │
│   - Expected return date enforced                           │
│   - Overdue → automatic escalation (email → admin)          │
│   - Returned → condition checked in, damage flagged if any  │
│   - Re-lending: same flow, new loan record (linked to prev) │
│                                                               │
│ Step 4.3: Room Booking                                      │
│   - User books room → conflict check (real-time)            │
│   - Approval if required by room settings                   │
│   - Actual event times logged (start/end)                   │
│   - Condition before/after event documented                 │
│   - Automatic post-event: notification to custodian         │
│                                                               │
│ Step 4.4: Transfers / Movements                             │
│   - Asset moved: from → to with reason and authorization    │
│   - Chain of custody maintained                              │
│   - Both source and destination custodians notified         │
│   - History available: complete movement timeline           │
│                                                               │
│ Step 4.5: Maintenance (Preventive)                          │
│   - Schedule auto-created on registration (based on policy) │
│   - Example: "Inspect air conditioner every 90 days"        │
│   - Due date approaches → notification to maintenance team  │
│   - Work order auto-generated when due                      │
│   - Technician dispatched → condition_before documented     │
│   - Work performed → condition_after, parts used, cost      │
│   - Upon completion → next_schedule auto-calculated         │
│   - SLA tracked: response time, resolution time             │
│                                                               │
│ Step 4.6: Maintenance (Corrective)                          │
│   - Any user can report damage/issue                        │
│   - Report → ticket created with priority                   │
│   - Assigned to maintenance team or external vendor         │
│   - Technician works → log created                          │
│   - Escalation if unresolved within SLA                     │
│                                                               │
│ Step 4.7: Inspections                                       │
│   - Scheduled inspections (annual building inspection)      │
│   - Checklist-based: each item rated (ok/fixed/replace)    │
│   - Conducted via mobile/QR scan                            │
│   - Findings → maintenance tickets auto-generated           │
│   - Compliance report exported                              │
│                                                               │
│ Step 4.8: Depreciation (Monthly)                            │
│   - Automated monthly run                                   │
│   - Calculates: opening_value - depreciation_amount         │
│   - Updates: current_value, accumulated_depreciation        │
│   - Records in DepreciationLedger                           │
│   - Notification to Finance: "Monthly depreciation complete"│
│   - At end of useful life: asset → "fully depreciated"      │
│                                                               ���
│ Step 4.9: Warranty Tracking                                 │
│   - On registration: warranty_start, warranty_end recorded  │
│   - 30/60/7/1 days before expiry → notification             │
│   - During warranty: claim filed → vendor notified          │
│   - Post-warranty: repair cost tracked separately           │
└─────────────────────────────────────────────────────────────┘


                    ╔═══════════════════════════════════════╗
                    ║     PHASE 5: VERIFICATION             ║
                    ╚═══════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│ Step 5.1: Physical Audit                                    │
│   - Audit cycle created (annual/quarterly)                  │
│   - Assigned to auditor(s)                                  │
│   - Auditor visits each location, scans QR codes            │
│   - System shows expected: location, condition, custodian   │
│   - Auditor records actual: location, condition, status     │
│   - Discrepancies flagged: missing, wrong location,         │
│     condition degraded                                      │
│   - Bulk audit mode: scan 50+ assets at once                │
│   - Photo evidence for each verification                    │
│                                                               │
│ Step 5.2: Audit Review                                      │
│   - Discrepancy report generated                            │
│   - Auditor → Custodian reconciliation meeting              │
│   - Findings approved by supervisor                         │
│   - Asset conditions updated to match physical reality      │
│   - Missing assets → escalate to investigation              │
│   - Audit cycle status: → REVIEWED → APPROVED              │
└─────────────────────────────────────────────────────────────┘


                    ╔═══════════════════════════════════════╗
                    ║      PHASE 6: DISPOSITION               ║
                    ╚═══════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────┐
│ Step 6.1: Disposal Request                                  │
│   - Trigger: end of useful life, irreparable damage,        │
│     surplus, obsolete, lost/stolen                          │
│   - Request includes: justification, current condition,     │
│     recommended disposal method (sell/scrap/transfer)       │
│                                                               │
│ Step 6.2: Disposal Approval                                 │
│   - Committee review (minimum 2-3 approvers)                │
│   - Finance checks: book value, potential gain/loss         │
│   - Director/Gov representative approves (per regulations)  │
│                                                               │
│ Step 6.3: Execution                                         │
│   - Method selection: Auction, scrap sale, transfer to      │
│     another institution, donation, destruction                │
│   - Proceeds recorded                                       │
│   - Gain/Loss calculated: proceeds - book_value             │
│   - Documentation attached (auction result, destruction     │
│     certificate, transfer order)                             │
│                                                               │
│ Step 6.4: Asset Retirement                                  │
│   - Asset lifecycle status → DISPOSED                       │
│   - Removed from active inventory                           │
│   - Moved to retired assets archive (5-year retention)      │
│   - Final depreciation run to zero balance                  │
│   - GL entry posted to Finance                              │
│   - Notification to all stakeholders                        │
│                                                               │
│ Step 6.5: Replacement Planning                              │
│   - If disposal was need-driven → procurement requisition   │
│     auto-suggested (lessons learned)                        │
│   - Links back to Phase 1: Planning                         │
└─────────────────────────────────────────────────────────────┘


                    ╔═══════════════════════════════════════╗
                    ║   LOOP BACK → ANNUAL CYCLE              ║
                    ╚═══════════════════════════════════════╝

```

## Key Automation Opportunities Embedded

1. **Automated Maintenance Triggers:** When a maintenance log is created, the next schedule date is calculated automatically based on policy frequency.
2. **Automated Depreciation:** Monthly cron job runs depreciation for all active assets.
3. **Automated Overdue Detection:** Nightly job checks for overdue loans, overdue maintenance, expiring warranties.
4. **Automated Notifications:** Event-driven — every state change publishes events consumed by notification domain.
5. **Automated Discrepancy Flagging:** During audit, mismatches between system and physical are auto-flagged.
6. **Automated Budget Alerts:** When procurement spending exceeds 80% of budget → alert department head.

================================================================================
# PART 5 — MODULE BLUEPRINT (GREENFIELD REDSIGN)
================================================================================

## Ideal Module Hierarchy

```
Sarpras (Facilities & Asset Management Platform)
│
├── 1. Asset Core (anchor domain)
│   ├── Asset Registry (CRUD, classification, lifecycle status)
│   ├── Asset Types (equipment, furniture, building, land, vehicle, IT)
│   ├── Asset Categories (hierarchical taxonomy)
│   ├── Asset Coding Engine (sequential, policy-driven, prefixes)
│   ├── Asset Valuation (acquisition, depreciation, revaluation)
│   ├── Asset Subtypes
│   │   ├── Buildings (facility hierarchy)
│   │   ├── Rooms (space within buildings)
│   │   ├── Land (territorial assets)
│   │   └── Vehicles (special tracking: mileage, registration, insurance)
│   └── Asset Photos & Documents (multi-file, versioned, virus-scan)
│
├── 2. Procurement
│   ├── Requisitions (draft → pending → approval chain)
│   ├── Purchase Orders (external, vendor-linked)
│   ├── Receiving & Inspection (3-way match, quality hold)
│   ├── Budget Tracking (fiscal year, cost center, commitment)
│   └── Asset Conversion (receive → register in inventory)
│
├── 3. Vendor Management
│   ├── Vendor Master (profile, contact, documents, certifications)
│   ├── Vendor Contracts (scope, terms, SLA, renewal)
│   ├── Vendor Evaluations (periodic scoring: quality, timeliness, pricing)
│   └── Vendor Transactions (history: all purchases, repairs, services)
│
├── 4. Inventory
│   ├── Lending / Borrowing (digital check-out/in, condition handshake)
│   ├── Room Booking (real-time availability, conflict prevention)
│   ├── Transfers & Movements (chain of custody, authorization)
│   └── Asset Positioning (current + historical location map)
│
├── 5. Maintenance (CMMS sub-module)
│   ├── Work Orders (ticket-based: open → assigned → in_progress → resolved → verified)
│   ├── Preventive Scheduling (policy-driven: frequency, triggers, auto-generation)
│   ├── Corrective Dispatch (incident → triage → assign → resolve)
│   ├── Maintenance Logs (cost, parts, condition before/after, photos)
│   ├── Service Technicians (skills, shifts, workload balancing)
│   ├── Spare Parts (inventory of consumables, reorder points)
│   └── SLA Management (response time, resolution time, breach escalation)
│
├── 6. Warranty & Insurance
│   ├── Warranty Tracking (provider-linked, expiry alerts, claim filing)
│   └── Insurance Mapping (policy → asset group → coverage → claims)
│
├── 7. Audit & Verification
│   ├── Audit Cycles (scope, planning, execution, review, sign-off)
│   ├── QR/Barcode/RFID Scanning (mobile-first, offline-capable)
│   ├── Physical Verification (single + bulk scan)
│   ├── Discrepancy Management (findings → investigation → resolution)
│   └── Compliance Reporting (PMK-equivalent, government standards)
│
├── 8. Disposal & Retirement
│   ├── Disposal Requests (justification, method recommendation)
│   ├── Disposal Approval (committee workflow)
│   ├── Disposal Execution (auction/scrap/transfer/destruction)
│   ├── Gain/Loss Calculation (proceeds vs book value)
│   └── Retired Archive (5-year retention, searchable)
│
├── 9. Finance Integration
│   ├── Depreciation Engine (straight-line, declining balance, units-of-production)
│   ├── Depreciation Ledger (monthly periods, audit trail)
│   ├── CAPEX/OPEX Tagging (budget code → GL account mapping)
│   ├── Journal Entry Generation (auto-create entries for acquisitions, disposals)
│   ├── Asset Revaluation (annual professional appraisal)
│   └── Financial Reports (balance sheet asset value, accumulated depreciation)
│
├── 10. Analytics & Intelligence
│   ├── Dashboard (role-based, configurable widgets)
│   ├── Trend Analysis (condition over time, cost trends, utilization)
│   ├── Utilization Metrics (idle time, loan turnover, room occupancy)
│   ├── Predictive Insights (maintenance forecasting, warranty expiry,
│   │                        replacement budget projection)
│   └── KPIs (asset reliability, mean time between failures,
│              procurement cycle time, audit compliance rate)
│
├── 11. Reporting
│   ├── Standard Reports (inventory, condition, loan, maintenance, financial)
│   ├── Ad-Hoc Builder (drag-and-drop fields, filters, grouping)
│   ├── PDF Export (styled templates, watermarks, digital signatures)
│   ├── Scheduled Distribution (daily/weekly/monthly email digest)
│   └── Government Reports (standardized formats for regulatory submission)
│
├── 12. Mobile API Gateway
│   ├── REST API v2 (resource-oriented, versioned, paginated)
│   ├── GraphQL endpoint for complex queries (analytics, reporting)
│   ├── API Keys & OAuth2 (sanctum-based, scope-limited)
│   ├── Rate Limiting (per-user, per-ip, per-endpoint)
│   ├── Offline Sync Queue (webhooks for mobile PWA)
│   └── Versioned API Contracts (OpenAPI spec, auto-generated docs)
│
├── 13. Notification Engine
│   ├── Event Bus (domain events → notification consumers)
│   ├── Channels (email, SMS, push, in-app)
│   ├── Templates (Markdown-based, locale-aware)
│   ├── Preference Center (per-user channel selection, quiet hours)
│   └── Digest Mode (batch daily summary vs instant alerts)
│
├── 14. Audit & Compliance
│   ├── Immutable Audit Log (append-only, tamper-evident)
│   ├── Action History (who changed what, before/after values)
│   ├── Data Retention Policy (automatic archival, GDPR-equivalent)
│   └── Access Logging (login, export, deletion — all tracked)
│
├── 15. Platform Services
│   ├── Authentication & Authorization (roles, permissions, policies)
│   ├── Multi-tenancy (school-scoped, work-unit-scoped, super-admin)
│   ├── File Storage (local + S3-compatible, virus scan, CDN)
│   ├── Background Jobs (queue worker, retries, dead letter queue)
│   ├── Scheduled Tasks (cron: depreciation, overdue detection, notifications)
│   ├── Localization (Indonesian primary, English secondary)
│   └── Health Checks (endpoint monitoring, dependency status)
│
└── 16. Integration Hub
    ├── Webhooks (outbound: procurement, maintenance, audit events)
    ├── Incoming Events (from Finance, Academic, HR modules)
    ├── Export Connectors (CSV, Excel, PDF batch export)
    └── API Consumer Registry (third-party system connections)
```

## Responsibility Boundaries

| Module | Owns | Shares With | Consumes From | Produces For |
|--------|------|-------------|---------------|--------------|
| Asset Core | All asset master data | Finance (valuation), Audit (verification) | Procurement (registered assets), Vendor (warranty) | All other modules |
| Procurement | Requisition to receiving | Vendor (selection), Finance (budget) | Asset Core (creates), Budget (checks) | Asset Core (register), Finance (capex) |
| Vendor | Master data, scoring | Procurement (supplier selection), Maintenance (service) | — | Procurement, Maintenance, Finance (payments) |
| Inventory | Loans, bookings, transfers | — | Asset Core (positions), Notifications (alerts) | Users (borrower experience), Finance (utilization) |
| Maintenance | Work orders, schedules | Asset Core (condition updates), Finance (costs) | Asset Core (assets), Vendors (external service) | Asset Core (maintenance log), Finance (cost center) |
| Warranty & Insurance | Policy mapping | — | Asset Core (linked assets), Vendors (providers) | Notifications (expiry alerts), Finance (insurance claims) |
| Audit | Cycle management | Asset Core (condition sync) | All modules (read-only data) | Finance (verification), Admin (compliance) |
| Disposal | Retirement workflow | Asset Core (lifecycle status), Finance (gain/loss) | Asset Core (asset data), Committees (approval) | Asset Core (retired archive), Finance (GL entries) |
| Finance | Depreciation, GL sync | All asset-bearing modules | Asset Core (asset list), Procurement (acquisitions) | External Finance System, Reports |
| Analytics | Trends, insights | All modules (read-only aggregations) | All domains | Dashboard, Reports, Executive summaries |
| Reporting | Output generation | All modules (read-only) | Analytics (metrics) | Admin, Finance, Regulatory bodies |
| Mobile API | External interface | All modules (via service layer) | Service Layer | Mobile apps, third-party integrations |
| Notification | Event distribution | All modules (domain events) | Event Bus | Users, managers, finance, admin |
| Platform Services | Cross-cutting concerns | All modules | Infrastructure | All modules |
| Integration Hub | External connectivity | All modules (events) | External systems | External systems (webhooks, exports) |

================================================================================
# PART 6 — ROADMAP
================================================================================

## Current State: V1 (Existing)

**Scope:** Basic CRUD, QR audit, borrowing, booking, procurement, maintenance logging.  
**Architecture:** Fat controllers, no services, no tests, no API, no mobile.  
**Maturity Score:** 42/100  
**User Experience:** Web-only, Blade templates, single-server.  
**Production Status:** Conditionally production-ready (small scale, 1-2 schools).

---

## V1.5 — Stabilization & Foundation (Months 1-4)

**Theme:** "Fix the cracks before building upstairs."

### Goals
1. Eliminate duplicate/legacy code
2. Fix race conditions and security gaps
3. Extract service layer (SRP)
4. Add Form Request validation classes
5. Implement immutable audit trail
6. Add notifications foundation
7. Create test suite baseline (30% coverage)
8. Performance: paginate all list endpoints, add critical indexes
9. Fix FK cascade issues on destructive operations
10. Implement proper asset coding policy (sequential + prefix)

### Deliverables
- Service layer: 13 service classes (one per controller)
- AuditLog model + middleware (captures every write)
- Notification service (email via queue, in-app via events)
- PHPUnit test suite: all controllers + critical flows (50+ tests)
- Policy classes for all 11 models
- Form Request classes for all 25+ endpoints
- Asset coding engine: PREFIX-YEAR-NNNNN
- Depreciation fields populated (manual initial run + future automation)
- Disposal request stub (UI + model, approval workflow placeholder)

### Business Value
- Eliminates data corruption risk (race conditions, cascades)
- Enables compliance audits (immutable trail)
- Foundation for mobile (clean service boundaries)
- Enables hiring/training (testable code)

### Technical Value
- Separates HTTP from business logic (testable)
- Centralized validation (consistent, reusable)
- Performance baseline for scale
- Security hardening (policy-based authorization)

### Migration Strategy
- Phased rollout: stabilize one sub-module per week
- Zero downtime: backward compatible, new service layer sits alongside controllers
- Test everything, deploy Friday afternoon, monitor Monday morning

---

## V2 — Enterprise Feature Completion (Months 5-10)

**Theme:** "Close the enterprise gap."

### Goals
1. Full disposal & retirement workflow
2. Automated monthly depreciation engine
3. Vendor master + contracts + scoring
4. Work order management (ticketing system)
5. Warranty tracking with expiry alerts
6. REST API v2 (mobile-ready)
7. Role-based configurable dashboard
8. Multi-level approval workflow engine
9. Ad-hoc report builder
10. Barcode support (1D/2D) alongside QR
11. Finance GL integration skeleton
12. Bulk operations (bulk assign, bulk audit, bulk transfer)
13. Scheduled report distribution (email digest)

### Deliverables
- Disposal domain: request → committee approval → execution → archive
- DepreciationEngine class: straight-line, declining balance
- VendorManagement module: CRUD, evaluation, transactions
- WorkOrder system: assign, track, resolve, verify
- WarrantyTracker: expiry calculation, event-based alerts
- REST API: 25+ endpoints, OpenAPI spec, Sanctum auth
- DashboardService: role-based widget aggregation
- ApprovalWorkflowEngine: chain definition, delegation, audit trail
- ReportBuilder: field picker, filter builder, export template
- BarcodeService: generate 1D/2D, scan via mobile camera
- GLIntegration: journal entry templates, account mapping
- API Resource classes for all 11 models

### Business Value
- Complete asset lifecycle (registration to disposal)
- Financial compliance (depreciation, GL entries)
- Vendor accountability (scoring, contracts)
- Maintenance professionalism (ticketing, SLA)
- Mobile workforce (full API access)

### Technical Value
- Proven API contracts (used by mobile app)
- Configurable workflows (no code changes for new approval chains)
- Report builder eliminates custom report development
- Barcode expands beyond QR-only scanning

### Migration Strategy
- Parallel run: V1 APIs continue serving web, V2 APIs serve mobile
- Data migration: vendor names → vendor records (deduplication algorithm)
- Depreciation: one-time historical calculation for existing assets
- Gradual mobile feature adoption (users choose web or mobile)

---

## V3 — Intelligence & Scale (Months 11-18)

**Theme:** "From tracking to predicting."

### Goals
1. Analytics engine (trends, utilization, forecasting)
2. Predictive maintenance (usage-based triggers)
3. Multi-site deployment (100+ schools)
4. Performance optimization (caching, query optimization)
5. Offline mobile scanning (PWA with sync queue)
6. Insurance mapping
7. Spare parts inventory
8. Advanced search (full-text, faceted)
9. Automated compliance reports (government-standard)
10. Integration hub (webhooks, external connectors)
11. Data retention policy (automatic archival)
12. CI/CD pipeline with deployment gates

### Deliverables
- AnalyticsDashboard: 15+ pre-built widgets, custom metric builder
- PredictiveMaintenance: ML-assisted forecasting (first pass rule-based)
- MultiSiteManager: cross-site aggregation, regional reporting
- PerformanceLayer: Redis cache, query plan analysis, N+1 elimination
- OfflineScanner: PWA with IndexedDB sync, conflict resolution
- InsuranceModule: policy → asset group mapping, claim tracker
- SparePartsCatalog: BOM, reorder points, consumption tracking
- SearchEngine: Elasticsearch or Meilisearch integration
- ComplianceGenerator: government-standard templates, auto-signoff
- IntegrationHub: webhook manager, event consumer registry
- RetentionPolicy: configurable archive after X years
- CI/CD: GitHub Actions, automated testing, blue-green deployment

### Business Value
- Proactive maintenance reduces downtime by 30-50%
- Offline scanning enables field operations in low-connectivity areas
- Compliance automation saves 200+ hours/year of reporting labor
- Cross-site visibility enables resource optimization across institutions

### Technical Value
- Elasticsearch enables sub-second full-text search at any scale
- Redis caching reduces database load by 60-80%
- Blue-green deployment enables zero-downtime releases
- PWA with sync queue works in 3G/edge networks

### Migration Strategy
- Search: dual-write initially (MySQL + Elasticsearch), then switch read
- Caching: opt-in per endpoint, monitor cache hit rates, expand gradually
- Offline PWA: incremental — start with QR scan + bulk audit only
- Multi-site: add `site_id` column, migrate existing data as site 0

---

## V4 — Platform & Ecosystem (Months 19-24)

**Theme:"Become the operating system for institutional facilities."

### Goals
1. Plugin architecture (extensible domains)
2. Third-party marketplace (custom reports, integrations)
3. AI-assisted features (image-based condition assessment, smart categorization)
4. IoT sensor integration (temperature, humidity, vibration for facility monitoring)
5. Multi-currency support (international campuses)
6. Advanced financial modeling (total cost of ownership, NPV analysis)
7. Sustainability tracking (energy consumption, carbon footprint per asset)
8. Public transparency portal (anonymized asset data for public access)
9. API marketplace (third-party developers building on Sarpras API)

### Business Value
- Extensibility enables customization without core modifications
- AI reduces manual classification and condition assessment effort
- IoT transforms preventive maintenance into condition-based maintenance
- Sustainability reporting meets ESG requirements

### Technical Value
- Plugin architecture isolates customizations from core upgrades
- AI/ML pipeline reuses model training across institutions
- IoT bridge standardizes sensor data ingestion (MQTT, WebSocket)
- Public portal is read-only, no security risk to core data

---

## Phase Summary

| Phase | Duration | Focus | Maturity Target |
|-------|----------|-------|----------------|
| V1 (Current) | Already shipped | Basic CRUD + QR | 42/100 |
| V1.5 | Months 1-4 | Stabilize foundation | 60/100 |
| V2 | Months 5-10 | Enterprise features | 78/100 |
| V3 | Months 11-18 | Intelligence + scale | 88/100 |
| V4 | Months 19-24 | Platform + ecosystem | 95/100 |

================================================================================
# PART 7 — ENTERPRISE SCORES
================================================================================

## Current State (V1)

| Dimension | Score (0-100) | Commentary |
|-----------|---------------|------------|
| Business Capability | 52 | Core operations covered, lifecycle incomplete (no disposal, depreciation) |
| Architecture | 35 | Fat controllers, no services, no tests, duplicate legacy code |
| Scalability | 30 | N+1 queries, unpaginated lists, no caching, cascade deletes |
| Maintainability | 40 | Inline validation, no policies, duplicated CRUD |
| Operational Readiness | 45 | Works for small scale, breaks at 10k+ assets |
| Financial Readiness | 10 | No depreciation engine, no GL, no disposal accounting |
| Lifecycle Completeness | 40 | Plan→Register works, Register→Dispose broken |
| Integration Readiness | 5 | No API, no webhooks, no external data flow |
| API Readiness | 0 | No endpoints, no resources, no versioning |
| Mobile Readiness | 0 | Web-only, no API contract, no offline support |
| Security | 55 | School scoping present, but missing policies, audit trail |
| Performance | 30 | Unoptimized queries, no caching, sync file upload |
| Documentation | 20 | No inline docs, no API docs, no runbooks |
| **Overall Product Maturity** | **42** | Functional but incomplete |

## Target State (V3 — Month 18)

| Dimension | Target Score | Commentary |
|-----------|-------------|------------|
| Business Capability | 85 | Full lifecycle, vendor mgmt, work orders, insurance |
| Architecture | 80 | Service layer, modular, event-driven, plugin-ready |
| Scalability | 85 | Cached, indexed, partitioned, multi-site ready |
| Maintainability | 85 | Policy-based, testable (80%+ coverage), CI/CD |
| Operational Readiness | 90 | Monitoring, alerting, zero-downtime deployment |
| Financial Readiness | 80 | Automated depreciation, GL sync, disposal accounting |
| Lifecycle Completeness | 90 | Plan→Dispose full workflow with approvals |
| Integration Readiness | 85 | Webhooks, API, event bus, third-party connectors |
| API Readiness | 90 | REST + GraphQL, offline sync, versioned contracts |
| Mobile Readiness | 85 | PWA, offline scanning, push notifications |
| Security | 90 | Policy-based, immutable audit, RBAC with delegation |
| Performance | 85 | Elasticsearch search, Redis caching, optimized queries |
| Documentation | 85 | OpenAPI docs, admin guides, runbooks, CHANGELOG |
| **Overall Product Maturity** | **87** | Enterprise-grade |

## Progress Trajectory

```
V1 (42) ──V1.5 (60)───┐
                        ├──► V2 (78) ──► V3 (87) ──► V4 (95)
                  Stabilize    Enterprise    Intelligence  Platform
                  Foundation    Features      & Scale      & Ecosystem
```

================================================================================
# PART 8 — CTO RECOMMENDATIONS
================================================================================

## TOP 10 STRATEGIC INVESTMENTS

| # | Investment | Why Now | ROI Horizon | Risk |
|---|-----------|---------|-------------|------|
| 1 | Service layer extraction | Every business rule is in controllers — untestable, unscalable | Immediate (maintainability) | Medium (breaking changes) |
| 2 | Automated depreciation engine | Dormant financial fields waste an entire domain | 6 months | Medium (calculation correctness) |
| 3 | Disposal & retirement workflow | Asset lifecycle is incomplete without it | 6 months | Low (process-driven) |
| 4 | Vendor master module | Procurement decisions are blind without vendor data | 8 months | Low (data entry required) |
| 5 | Work order system | Maintenance is ad-hoc; tickets bring accountability | 8 months | Medium (workflow complexity) |
| 6 | REST API v2 | Mobile team is blocked; no external integrations possible | 10 months | High (architecture shift) |
| 7 | Notification engine | No alerts = no automation = all manual follow-up | 4 months | Low (event-driven) |
| 8 | Immutable audit trail | Compliance requirement; currently non-existent | 3 months | Low (append-only log) |
| 9 | Multi-level approval engine | Single approver is insufficient for enterprise governance | 6 months | Medium (configuration UX) |
|10 | CI/CD pipeline + test suite | Zero tests = zero confidence in releases | 3 months | Low (infrastructure) |

---

## TOP 10 ARCHITECTURAL IMPROVEMENTS

| # | Improvement | Current State | Target State | Impact |
|---|------------|---------------|-------------|--------|
| 1 | MVC → Service Layer Pattern | Controllers hold all business logic | Thin controllers, thick services | Testability ×10 |
| 2 | Inline Validation → Form Requests | Validation rules embedded in methods | Dedicated request classes | Consistency + reusability |
| 3 | Ad-hoc Authorization → Policy Classes | `authorizeXxxAccess()` scattered in controllers | Laravel Policies per model | Security audit passable |
| 4 | Synchronous Processing → Async Queue | File imports block HTTP requests | Queue workers with progress tracking | UX + throughput |
| 5 | No API → REST + GraphQL | Web routes only | Sanctum-authenticated API v2 | Mobile-ready, integratable |
| 6 | MySQL-only → Read Replicas | All queries hit primary | Read replicas for reports/dashboard | Performance at scale |
| 7 | No Caching → Redis Layer | 17+ queries per dashboard | Memoization + result caching | 80% faster dashboard |
| 8 | Fat Models → Domain Services | Business logic in models | Service objects, domain services | SRP compliance |
| 9 | Single Server → Containerized | Laravel on shared hosting | Docker, horizontal scaling | Multi-site ready |
|10 | Manual Deployment → CI/CD | Git push → deploy | GitHub Actions, automated tests, blue-green | Zero-downtime releases |

---

## TOP 10 BUSINESS IMPROVEMENTS

| # | Improvement | Current Pain | Future State | Benefit |
|---|-----------|-------------|-------------|---------|
| 1 | Full asset lifecycle | Assets can't be properly retired | Disposal → archive → financial close | Regulatory compliance |
| 2 | Accurate financial reporting | `current_value` is guessed | Monthly automated depreciation | Accurate balance sheets |
| 3 | Vendor accountability | Names are free text | Rated vendors, contract SLAs | Better procurement decisions |
| 4 | Proactive maintenance | Manual schedule checking | Automated alerts, work order generation | 30-50% less downtime |
| 5 | Field operations | QR only works online | Offline PWA scanning | Works in any connectivity |
| 6 | Mobile workforce | No mobile access | Full mobile app/API | Real-time updates from anywhere |
| 7 | Multi-school oversight | Dashboard crashes at scale | Read replicas, pagination, caching | 100+ schools supported |
| 8 | Audit readiness | No immutable history | Full action trail, tamper-evident | Government audits pass |
| 9 | Dispute resolution | No chain of custody | Movement logs, handover signatures | Clear accountability |
|10 | Budget justification | No historical data | 3-year cost trends, utilization metrics | Data-driven budget proposals |

---

## TOP 10 RISKS IF NOTHING CHANGES

| # | Risk | Impact | Timeline | Probability |
|---|-----|--------|---------|-------------|
| 1 | Dashboard OOM crash at 10k+ assets | System unusable for large schools | 6-12 months (with growth) | High |
| 2 | Data corruption from race conditions | Irreversible asset/loan mismatch | Immediate (any production load) | Medium |
| 3 | Audit failure — no disposal trail | Regulatory non-compliance | 12 months (next audit cycle) | High |
| 4 | Financial misreporting | Incorrect balance sheet values | Monthly (ongoing) | High |
| 5 | Vendor data inconsistency | Cannot compare suppliers | Ongoing | Medium |
| 6 | No API = no mobile adoption | Users forced to use web only | 12-24 months | High |
| 7 | Maintenance compliance failure | No one knows when to inspect | Ongoing | Medium |
| 8 | Single point of failure (no deployment process) | Bad deploy = hours of downtime | Any deployment | Medium |
| 9 | Knowledge bottleneck (bus factor = 1) | No one else can maintain the code | Ongoing | Medium |
|10 | Stagnation → platform abandonment | Organization migrates to competing solution | 24-36 months | Low-Medium |

================================================================================
# APPENDIX: TERMINOLOGY & GLOSSARY

| Term | Meaning in This Blueprint |
|------|--------------------------|
| Asset Core | The canonical master data of all facility assets |
| Service Layer | PHP classes encapsulating business logic, independent of HTTP |
| Work Order | A maintainable ticket representing a maintenance job |
| Disposal | Formal retirement of an asset from active inventory |
| GL Integration | Posting asset transactions to a General Ledger system |
| Offline Sync | PWA queues changes locally, syncs when online, resolves conflicts |
| Multi-tenancy | School/work-unit data isolation in a shared deployment |
| Immutable Audit | Append-only log that cannot be modified or deleted |
| Plugin Architecture | Extensible module system for custom domains |
| CAPEX | Capital Expenditure — investment in long-lived assets |
| OPEX | Operating Expenditure — day-to-day maintenance costs |
| BOM | Bill of Materials — spare parts needed for asset maintenance |
| SLA | Service Level Agreement — response/resolution time guarantees |

---

*End of Enterprise Capability Benchmark & Product Blueprint.*
*Report prepared: 2026-07-01*
*Classification: Confidential — CTO / Enterprise Architecture Board / Technical Steering Committee*
