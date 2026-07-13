# BR-001 — Enterprise Vision & Business Requirement Specification
# Enterprise AI Engineering Framework — Sarpras Platform
# Version: 1.0
# Date: 2026-07-01
# Classification: Confidential — CTO / Enterprise Architecture Board
# Author: Enterprise Architecture Team

================================================================================
# 1. DOCUMENT PURPOSE & SCOPE
================================================================================

This document is the single source of truth for ALL business requirements
governing the Sarpras (Sarana Prasarana) platform. It captures the
enterprise-level business objectives, operational workflows, regulatory
constraints, and success criteria — not the technical implementation details.

**Non-negotiable principle:** Technical architecture decisions (which
framework, which database, which deployment strategy) shall be derived
from these requirements. They shall not drive them.

All subsequent architecture documents (EA-001, EA-002), module designs,
and sprint backlogs MUST reference this document and pass validation
against it. Any feature that cannot be traced to a requirement in BR-001
is classified as scope creep and must be rejected or escalated to the
Enterprise Architecture Board for approval.

### Document Governance

| Item | Value |
|------|-------|
| Owner | Enterprise Architecture Board |
| Review Cadence | Quarterly (or after any charter change) |
| Change Authority | CTO + Business Process Owner (Joint sign-off) |
| Linked Documents | EA-002 (Blueprint), TD-001 (Tech Spec), SD-001 (Sprint Plan) |
| Version Control | Semantic: Major = business charter change; Minor = clarification |

### Amendment Process

Any amendment to BR-001 requires:
1. Written change request describing the business driver
2. Impact assessment on existing requirements, roadmap, and architecture
3. Sign-off from both CTO AND Business Process Owner
4. Entry in the document changelog below

---

# 2. ENTERPRISE CONTEXT
================================================================================

## 2.1 Organization Profile

Sarpras is the Facilities & Asset Management platform operated by ALIM,
a multi-school educational institution in Indonesia. The platform serves:
- Multiple school sites (multi-tenancy / multi-work-unit)
- Three primary user cohorts: Administrators, Facility Staff, General Users
- Both office-network and field-operation (mobile/offline) usage patterns

The organization operates under Indonesian government asset management
regulations (PMK 102/2022 equivalent). Financial reporting must comply
with GAAP standards applicable to institutional accounting.

## 2.2 Current State (AS-IS)

The existing V1 system provides basic CRUD operations for assets, rooms,
buildings, borrowing, reservation, procurement request tracking, and
QR-based audit. It operates as a monolithic Laravel application served
through Blade templates on a single server.

**Key characteristics:**
- Business logic is embedded in controllers (no service layer)
- No automated testing (0% coverage)
- No formal API for external consumption
- Financial tracking fields exist but are dormant (manually maintained)
- Asset lifecycle ends at "registered" — no formal disposal or retirement
- Vendor data stored as free text strings (no structured master)
- Maintenance is logged ad-hoc (no work order discipline)
- No notification system (all operations require manual follow-up)
- Authorization relies on coarse-grained role middleware

## 2.3 Strategic Problem Statement

Without a formal business requirement specification, the platform has
drifted from its strategic purpose. Features are added reactively
(following user requests), refactors occur without business justification,
and architectural decisions lack traceability to measurable outcomes.

This creates:
- **Strategic drift:** Architecture follows implementation, not vision
- **Scope ambiguity:** Stakeholders disagree on what "done" means
- **Technical debt compounding:** Without business priorities, all features
  appear equally urgent, preventing focused improvement cycles
- **Compliance risk:** No defined requirement for audit trails, disposal
  workflows, or financial accuracy
- **Vendor lock-in to technology:** The team optimizes for framework
  preferences rather than business outcomes

BR-001 exists to eliminate these risks by establishing a fixed reference
point — a set of requirements against which ALL decisions can be validated.

---

# 3. ENTERPRISE VISION & STRATEGIC OBJECTIVES
================================================================================

## 3.1 Vision Statement

> Sarpras shall be the single, enterprise-grade operating system for
> institutional facilities management — managing the complete lifecycle
> of every tangible asset from planning through disposal — enabling
> data-driven stewardship, regulatory compliance, and proactive
> maintenance across all school sites.

## 3.2 Strategic Objectives (OKR Format)

### Objective 1: Complete Asset Lifecycle Coverage
**Business Goal:** Every asset, from acquisition to retirement, is tracked
in the system with full chain-of-custody and financial accountability.

| Key Result | Target |
|------------|--------|
| Disposal/retirement workflow operational | Month 6 |
| Automated depreciation running monthly | Month 8 |
| Procurement integrated with budget allocation | Month 6 |
| Financial GL entries auto-generated for all transactions | Month 10 |

### Objective 2: Regulatory & Financial Compliance
**Business Goal:** The platform satisfies all Indonesian government asset
management regulations and produces GAAP-compliant financial reports.

| Key Result | Target |
|------------|--------|
| Immutable audit trail capturing all writes | Month 4 |
| PMK-equivalent compliance report generator | Month 10 |
| Disposal gain/loss calculations accurate to GL | Month 10 |
| Role-based access matrix covering all entities | Month 4 |

### Objective 3: Operational Excellence
**Business Goal:** Facility operations are proactive, data-driven, and
efficient — minimizing downtime and maximizing resource utilization.

| Key Result | Target |
|------------|--------|
| Preventive maintenance scheduling automated | Month 8 |
| Mean time between maintenance failures reduced 30% | Month 18 |
| Field staff can perform offline QR audits | Month 12 |
| Vendor evaluation system deployed | Month 8 |

### Objective 4: Platform Scalability
**Business Goal:** The platform supports 100+ school sites without
performance degradation or architectural rewrites.

| Key Result | Target |
|------------|--------|
| Dashboard handles 10k+ assets without OOM crash | Month 4 |
| REST API serving 500+ concurrent mobile users | Month 10 |
| Zero-downtime deployment pipeline | Month 12 |
| Query performance: list endpoints < 2s at 50k records | Month 12 |

---

# 4. STAKEHOLDER MAPPING & ROLES
================================================================================

## 4.1 User Roles (Functional)

| Role | Description | Key Responsibilities | Access Level |
|------|-------------|---------------------|-------------|
| Super Admin | System-wide operator | Configure platform, manage organizations | Global |
| School Admin (Admin Sarpras) | Site-level administrator | Full CRUD, approval, reporting | School-scoped |
| Administrative Staff (Admin Tata Usaha) | Finance-adjacent operator | Procurement approval, budget oversight, financial reports | School-scoped |
| Wakil Kepala Sekolah | Deputy Principal | Higher-level procurement approval | School-scoped |
| Facility Staff | Day-to-day operators | Asset registration, audit, maintenance, lending | School-scoped |
| Service Technician | Maintenance executor | Work order fulfillment, condition reporting | Limited (maintenance + assigned assets) |
| General User / Teacher | Asset borrower | Request loans, book rooms, report issues | Self-service only |
| Auditor | Internal/external verifier | Read-only access to audit trails, compliance reports | Restricted read |
| Finance Officer | Financial system operator | Depreciation review, GL reconciliation, disposal accounting | Read + financial write |
| Vendor (External) | Supplier / service provider | Receive POs, submit invoices, view contract status | Portal access only |

## 4.2 Organizational Hierarchy (Data Model Constraint)

```
Super Admin
 └── Institution (ALIM)
      └── Schools (multi-tenant)
           └── Work Units (organizational divisions)
                └── Buildings
                     └── Rooms
                          └── Assets
```

**Requirement:** All data queries MUST be scoped to the user's school
context. Super Admins can cross-school queries. No user may access data
belonging to a school outside their assignment.

## 4.3 Segregation of Duties (SoD) Requirements

| Process | Requester | Approver | Receiver | Conflict If Same Person |
|---------|-----------|----------|----------|------------------------|
| Procurement Request | Any | Dept Head + Finance | Receiving Officer | REQ = APPROVER |
| Asset Registration | Receiving Officer | — | Custodian | — |
| Disposal | Asset Custodian | Committee (min. 2) | Finance | CUSTODIAN = FINANCE |
| Loan Extension | Borrower | Custodian | — | BORROWER = CUSTODIAN |
| Maintenance Close | Technician | Requester Verifies | — | TECHNICIAN = REQUESTER |
| Audit | Assigned Auditor | Supervisor Reviews | — | AUDITOR = CUSTODIAN (of audited items) |

**Requirement:** The system MUST enforce SoD rules at the workflow level.
Violations MUST be blocked with a clear error message identifying the
conflict.

---

# 5. BUSINESS REQUIREMENTS
================================================================================

Each requirement is tagged with:
- **ID:** Unique identifier for traceability
- **Priority:** P0 Critical / P1 High / P2 Medium / P3 Low
- **Lifecycle Phase:** V1.5 / V2 / V3 / V4
- **Source:** Strategic objective, regulation, or stakeholder need

## 5.1 Asset Management Domain

### BR-ASM-001: Complete Asset Lifecycle Tracking
**Priority:** P0 | **Phase:** V2 | **Source:** Strategic Objective 1
The system MUST track every asset through its complete lifecycle:
Planning → Procurement → Registration → Assignment → Maintenance →
Audit → Disposal → Retirement. An asset record MUST NOT be deletable;
disposal is achieved via a formal workflow that transitions the asset
to "Retired" status and moves it to a read-only archive.

**Acceptance Criteria:**
- Every asset has a lifecycle_status enum with at least: PLANNED, ACTIVE,
  MAINTENANCE, UNDER_AUDIT, DISPOSAL_REQUESTED, DISPOSED, RETIRED
- Soft-delete is prohibited for assets. Disposal replaces delete.
- Disposed assets remain searchable in archive for minimum 5 years.

### BR-ASM-002: Structured Asset Coding
**Priority:** P1 | **Phase:** V1.5 | **Source:** Architecture, Compliance
Asset codes MUST follow a configurable policy: PREFIX-YEAR-SEQUENCE
(e.g., IT-26-00042). Codes MUST be globally unique within an institution.
Prefixes are managed via a policy table, not hardcoded.

**Acceptance Criteria:**
- Asset codes are sequential and increment-safe under concurrent writes
- Policy table defines valid prefixes, their categories, and formatting rules
- Random UUID codes are deprecated; new assets use policy-driven codes

### BR-ASM-003: Asset Valuation & Financial Attributes
**Priority:** P0 | **Phase:** V2 | **Source:** SO1, SO2
Every asset MUST store acquisition cost, acquisition date, funding source,
and useful life. These values drive automated depreciation and financial
reporting.

**Acceptance Criteria:**
- Fields: acquisition_cost, acquisition_date, funding_source, useful_life_years,
  salvage_value_percentage, accumulated_depreciation, current_book_value
- Fields are populated during registration and are immutable after
  capitalization (corrections require a revaluation workflow).

### BR-ASM-004: QR Code & Barcode Identity
**Priority:** P1 | **Phase:** V2 | **Source:** Operational Excellence
Each asset MUST have a QR code (primary) and 1D/2D barcode (fallback)
printed and affixed. The code MUST encode the asset_code (not the UUID)
so that scanning is resilient to data migrations.

**Acceptance Criteria:**
- QR code contains only the asset_code string
- Barcode uses Code128 (1D) for legacy scanners
- Scanning an asset code returns the asset record regardless of which
  identifier (UUID or code) is used as primary key

## 5.2 Procurement Domain

### BR-PRC-001: End-to-End Procurement Workflow
**Priority:** P0 | **Phase:** V2 | **Source:** Strategic Objective 1
Procurement MUST follow a structured workflow:
Draft → Pending Approval → Reviewed → Approved/Rejected → Ordered →
Delivered → Received/Inspected → Completed. A Purchase Order MUST be
generated for every approved request before goods are ordered externally.

**Acceptance Criteria:**
- States: draft, pending, reviewed, approved, rejected, ordered, delivered,
  completed, cancelled (matching current STATUS_OPTIONS)
- Payment states: belum_dibayar, dibayar_sebagian, lunas
- Rejection requires a mandatory reason field
- Approved requests can transition to PO generation (not automatic)

### BR-PRC-002: Three-Way Match Receiving
**Priority:** P1 | **Phase:** V2 | **Source:** Financial Compliance
Upon delivery, a receiving officer MUST perform a three-way match:
Purchase Order vs. Delivery Note vs. Physical Inspection. Discrepancies
place assets on Quality HOLD (cannot be registered until released).

**Acceptance Criteria:**
- Receiving report captures: actual_quantity, actual_cost, condition,
  photos, discrepancies
- Quality HOLD status prevents asset registration until released by supervisor

### BR-PRC-003: Budget Allocation & Consumption Tracking
**Priority:** P1 | **Phase:** V2 | **Source:** Strategic Objective 1
Each procurement request MUST reference a budget allocation. The system
MUST track committed_amount (approved POs) and spent_amount against
available_amount per fiscal year and cost center.

**Acceptance Criteria:**
- Budget allocation: fiscal_year, cost_center, available_amount,
  committed_amount, spent_amount
- Alerts at 80% and 100% budget consumption
- Requests exceeding available budget are blocked (with exception workflow)

## 5.3 Vendor Management Domain

### BR-VND-001: Structured Vendor Master Data
**Priority:** P0 | **Phase:** V2 | **Source:** Operational Excellence
All vendor references MUST use a structured Vendor record instead of
free-text strings. Vendors have profile data, contact information,
certifications, contracts, and transaction history.

**Acceptance Criteria:**
- Vendor entity: name, type (supplier/service), tax_id, contact, address,
  status (active/inactive/suspended)
- Vendor-scoped search in procurement and maintenance contexts
- Duplicate detection on name + tax_id combination

### BR-VND-002: Vendor Evaluation & Scoring
**Priority:** P1 | **Phase:** V2 | **Source:** Strategic Objective 3
Vendors MUST be evaluated periodically on quality, timeliness,
responsiveness, and pricing. Scores influence procurement decisions.

**Acceptance Criteria:**
- Evaluation triggers: after each completed transaction or periodically
  (quarterly)
- Composite score computed from weighted dimensions
- Minimum score threshold for "preferred vendor" designation

### BR-VND-003: Vendor Contract Management
**Priority:** P2 | **Phase:** V3 | **Source:** Compliance
Service providers MUST have contracts recording scope, terms, SLA,
and renewal dates. Contract expiry triggers procurement workflow.

**Acceptance Criteria:**
- Contract: vendor_id, scope, start_date, end_date, terms, SLA, renewal_policy
- Expiry alerts at 90/30/7 days before end date

## 5.4 Inventory & Operations Domain

### BR-INV-001: Asset Lending with Condition Handshake
**Priority:** P1 | **Phase:** V1.5 | **Source:** Operational Excellence
Asset loans MUST enforce a digital condition handshake:
Condition documented at check-out → Condition verified at check-in.
Discrepancies trigger maintenance tickets or damage reports.

**Acceptance Criteria:**
- States: requested, approved, dipinjam (checked_out), returned, rejected,
  cancelled (matching current STATUS_OPTIONS)
- Condition documented (photo or text) at both check-out and check-in
- Late return triggers automated escalation after configurable threshold

### BR-INV-002: Room Booking with Conflict Prevention
**Priority:** P1 | **Phase:** V1.5 | **Source:** Operational Excellence
Room bookings MUST check real-time availability and prevent conflicts.
Approved bookings lock the time slot; rejected/cancelled bookings release it.

**Acceptance Criteria:**
- States: pending, approved, rejected, cancelled, completed (matching current)
- Concurrent booking requests resolved by timestamp (first-come, first-served)
- Overlap detection: two bookings cannot occupy the same room at overlapping times

### BR-INV-003: Asset Transfer with Chain of Custody
**Priority:** P1 | **Phase:** V1.5 | **Source:** Compliance
Moving an asset BETWEEN locations (buildings, rooms) MUST create an
immutable audit trail documenting from, to, reason, and authorization.

**Acceptance Criteria:**
- Transfer record: from_location, to_location, date, reason, authorized_by,
  accepted_by (destination custodian signature)
- Both source and destination custodians receive notification
- Historical movement timeline available on asset record

## 5.5 Maintenance Domain

### BR-MNT-001: Work Order Ticketing System
**Priority:** P0 | **Phase:** V2 | **Source:** Strategic Objective 3
Maintenance operations MUST transition from ad-hoc logging to a formal
work order system with ticket number, priority, assignee, and status
lifecycle: open → assigned → in_progress → resolved → verified.

**Acceptance Criteria:**
- Every maintenance job has a work order (generated or manually created)
- Technician assignment recorded with timestamp
- Resolution requires a before/after condition description and optional photos
- Requester verification of resolution closes the ticket

### BR-MNT-002: Preventive Maintenance Scheduling
**Priority:** P1 | **Phase:** V2 | **Source:** Strategic Objective 3
Maintenance policies MUST be configurable per asset category or type.
Examples: "Inspect AC every 90 days", "Check roof annually". When a
maintenance date arrives, a work order is auto-generated.

**Acceptance Criteria:**
- Maintenance policy: asset_category_id, type (preventive/corrective),
  frequency_days, trigger_condition
- Auto-generation of work orders when next_due date passes
- Next schedule recalculated automatically after each completion

### BR-MNT-003: SLA Management
**Priority:** P1 | **Phase:** V2 | **Source:** Operational Excellence
Maintenance work orders MUST have SLA targets for response time and
resolution time. Breaches trigger escalation.

**Acceptance Criteria:**
- SLA: response_deadline, resolution_deadline per priority level
- Escalation notifications sent when SLA approaching deadline
- Breach count tracked per technician (performance metric)

## 5.6 Warranty & Insurance Domain

### BR-WTI-001: Warranty Tracking with Expiry Alerts
**Priority:** P0 | **Phase:** V2 | **Source:** Financial Compliance
Every new asset MUST record warranty start and end dates linked to a
vendor. The system MUST alert at 30/60/90 days before expiry and when
expired.

**Acceptance Criteria:**
- Warranty period captured at asset registration
- Automated alerts at configurable intervals before expiry
- During warranty: claim filing workflow linked to vendor

### BR-WTI-002: Insurance Mapping
**Priority:** P2 | **Phase:** V3 | **Source:** Risk Management
Assets (or groups of assets) MAY be mapped to insurance policies. Claims
are tracked separately with adjuster contact, settlement amount, and
outcome.

## 5.7 Finance & Depreciation Domain

### BR-FIN-001: Automated Monthly Depreciation
**Priority:** P0 | **Phase:** V2 | **Source:** Strategic Objective 2, GAAP
The system MUST calculate and post monthly depreciation for all active
assets using configurable methods: straight-line, declining balance, or
units of production.

**Acceptance Criteria:**
- Depreciation policy per asset category (method, useful life, salvage %)
- Monthly cron job computes depreciation for all assets
- Depreciation ledger records: period, opening_value, depreciation_amount,
  closing_value, accumulated_depreciation
- Assets fully depreciated (book_value <= salvage_value) stop depreciation

### BR-FIN-002: General Ledger Integration
**Priority:** P0 | **Phase:** V2 | **Source:** Strategic Objective 2
Asset transactions (acquisition, depreciation, disposal, revaluation)
MUST generate journal entries that can be exported to or synced with
an external financial system.

**Acceptance Criteria:**
- Chart of accounts mapping per asset category
- Journal entry: date, account_code, debit, credit, description, reference
- Export format: CSV/Excel compatible with standard accounting systems
- GL sync status tracked per entry (pending/synced/failed)

### BR-FIN-003: CAPEX/OPEX Classification
**Priority:** P1 | **Phase:** V2 | **Source:** Compliance
Each asset acquisition MUST be tagged as CAPEX (capital expenditure)
or OPEX (operating expenditure). Tags drive financial reporting and
budget categorization.

### BR-FIN-004: Disposal Gain/Loss Calculation
**Priority:** P0 | **Phase:** V2 | **Source:** Strategic Objective 2
When an asset is disposed, the system MUST calculate: gain_or_loss =
proceeds_received - book_value. This figure MUST flow to the GL.

**Acceptance Criteria:**
- Disposal methods: sell, scrap, transfer, donate, destroy
- Proceeds amount entered during disposal execution
- Gain/loss auto-computed and recorded in DisposalRecord

## 5.8 Audit & Verification Domain

### BR-ADT-001: QR-Based Physical Audit
**Priority:** P1 | **Phase:** V1.5 | **Source:** Compliance
Annual/quarterly physical audits MUST be conducted via QR scanning.
The system displays expected condition/location; the auditor records
actual condition/location. Discrepancies are auto-flagged.

**Acceptance Criteria:**
- Audit cycle: planned → in_progress → reviewed → approved
- Single-scan mode: one asset at a time (detailed)
- Bulk-scan mode: rapid-fire scanning of multiple assets
- Discrepancy report auto-generated comparing expected vs. actual

### BR-ADT-002: Immutable Audit Trail
**Priority:** P0 | **Phase:** V1.5 | **Source:** Strategic Objective 2, PMK Compliance
EVERY create, update, and delete operation on EVERY entity MUST be
captured in an append-only audit log recording: who, what, when,
before values, and after values.

**Acceptance Criteria:**
- AuditLog entity: model_class, model_id, action, actor_id, actor_name,
  old_values (JSON), new_values (JSON), ip_address, user_agent, created_at
- Audit records are immutable (no update, no soft-delete)
- Audit trail accessible to Audit and Finance roles
- Queryable by entity, user, date range, or action type

## 5.9 Disposal & Retirement Domain

### BR-DIS-001: Formal Disposal Workflow
**Priority:** P0 | **Phase:** V2 | **Source:** Strategic Objective 1, Compliance
Asset retirement MUST follow a structured workflow:
Disposal Request → Committee Review → Disposal Approval → Execution →
Archive. The asset owner cannot unilaterally dispose of an asset.

**Acceptance Criteria:**
- Request requires justification and recommended disposal method
- Committee review requires minimum 2 independent approvers
- Disposal execution records: method, proceeds, documentation attachments
- Asset lifecycle_status transitions to DISPOSED
- Asset moved to read-only archive (searchable, non-modifiable)

## 5.10 Notification & Alerting Domain

### BR-NOT-001: Event-Driven Notification Engine
**Priority:** P0 | **Phase:** V1.5 | **Source:** Operational Excellence
ALL state transitions, deadlines, and anomalies MUST trigger configurable
notifications. Channels: in-app (default), email, SMS (optional).

**Acceptance Criteria:**
- Notification rules: event_type → recipient_roles → channels
- Throttling: max N notifications per recipient per hour
- Quiet hours: suppress non-urgent notifications outside business hours
- Digest mode: batch low-priority notifications into daily summary

### BR-NOT-002: Automated Alert Scenarios
**Priority:** P1 | **Phase:** V1.5 | **Source:** Operational Excellence
Mandatory alerts:
- Overdue loan (escalate after N days)
- Maintenance due / overdue
- Warranty expiring (90/30/7 days before)
- Budget consumption at 80% and 100%
- Procurement request awaiting approval > 48 hours
- Audit discrepancy unreviewed > 7 days

## 5.11 Reporting & Analytics Domain

### BR-RPT-001: Standard Reports
**Priority:** P1 | **Phase:** V2 | **Source:** Compliance
Mandatory standard reports:
- Inventory report (by category, location, status)
- Asset condition summary
- Active loans and overdue list
- Maintenance history and costs
- Depreciation schedule
- Procurement summary (by period, vendor, category)
- Audit findings summary

### BR-RPT-002: Ad-Hoc Report Builder
**Priority:** P2 | **Phase:** V3 | **Source:** Operational Excellence
Power users MUST be able to construct custom reports by selecting:
fields, filters, groupings, sort order, and export format (CSV/Excel/PDF).

### BR-RPT-003: Scheduled Report Distribution
**Priority:** P2 | **Phase:** V3 | **Source:** Operational Excellence
Reports MUST be schedulable for automatic email distribution:
daily summary, weekly digest, monthly financial package.

---

# 6. NON-FUNCTIONAL REQUIREMENTS
================================================================================

## 6.1 Performance

| Requirement | Threshold | Measurement |
|-------------|-----------|-------------|
| Page load (list view, 1k records) | < 2s | 95th percentile |
| Dashboard render time | < 3s | 95th percentile |
| QR scan → record load | < 500ms | p95 from API call |
| File import (1k rows) | < 30s | End-to-end |
| Concurrent users (mobile API) | 500 | Sustained over 1 hour |

## 6.2 Availability

| Requirement | Target |
|-------------|--------|
| Uptime (production) | 99.5% monthly |
| Deployment frequency | Weekly (V1.5), on-demand (post-V2) |
| Recovery time objective (RTO) | < 4 hours |
| Recovery point objective (RPO) | < 15 minutes (database) |

## 6.3 Security

| Requirement | Detail |
|-------------|--------|
| Authentication | Session-based (Laravel Sanctum) for web, Token-based for API |
| Authorization | Role + Policy-based, scoped to school context |
| Data isolation | Hard boundary between schools (no cross-tenant query without Super Admin) |
| Password policy | Min 8 chars, complexity required, bcrypt hashing |
| Audit logging | Tamper-evident, append-only, retained 7 years |
| File upload | Virus scan required, size limit 10MB per file, allowed types whitelisted |

## 6.4 Scalability

| Parameter | V1 Target | V3 Target |
|-----------|-----------|-----------|
| Asset records | 5,000 | 100,000+ |
| School sites | 1-2 | 100+ |
| Users per school | 50 | 500 |
| Maintenance logs/year | 1,000 | 50,000 |
| Procurement requests/year | 100 | 5,000 |
| Audit scans/session | 200 | 2,000 |

## 6.5 Usability

| Requirement | Detail |
|-------------|--------|
| Mobile support | Responsive design; PWA with offline scanning (V3) |
| Language | Indonesian primary, English secondary |
| Accessibility | WCAG 2.1 AA for critical paths (login, forms, reports) |
| Learning curve | New facility staff can perform audit scan within 10 minutes of training |

## 6.6 Maintainability

| Requirement | Target |
|-------------|--------|
| Test coverage (V1.5 end) | 30% |
| Test coverage (V2 end) | 60% |
| Test coverage (V3 end) | 80%+ |
| Build pipeline | CI/CD with automated tests (V3) |
| Documentation | API docs (OpenAPI), admin runbooks, CHANGELOG |

---

# 7. REGULATORY & COMPLIANCE CONSTRAINTS
================================================================================

## 7.1 Indonesian Government Asset Regulations

Sarpras MUST satisfy the requirements of Indonesian government asset
management regulations, including but not limited to:

- **PMK 102/2022:** Management of Government Assets — requires complete
  asset lifecycle documentation, annual physical inventory, depreciation
  using straight-line method, formal disposal with ministerial approval
  for high-value items.
- **PMK 212/2015:** Guidelines for Depreciation of Government Assets —
  specifies useful life tables per asset category and depreciation method.
- **Peraturan BPK:** Audit standards requiring traceable audit trails
  from ledger to physical asset and vice versa.

## 7.2 Data Retention Requirements

| Data Type | Retention Period |
|-----------|-----------------|
| Active asset records | Indefinite |
| Retired/disposed assets | Minimum 5 years post-retirement |
| Audit logs | Minimum 7 years |
| Procurement records | Minimum 10 years (financial records) |
| Maintenance logs | Minimum 5 years |
| Loan/booking records | 3 years |
| User activity logs | 2 years |
| System backups | 90 days (daily), 1 year (monthly snapshots) |

## 7.3 Financial Reporting Standards

- GAAP-compliant depreciation (straight-line or declining balance)
- Asset revaluation only via formal process (not ad-hoc)
- Disposal gain/loss recognized in same period as disposal
- Accumulated depreciation tracked per asset, not as a lump sum

---

# 8. OUT OF SCOPE (Explicitly Excluded)
================================================================================

The following are explicitly NOT part of the Sarpras platform scope:

| Item | Reason | Future Consideration |
|------|--------|---------------------|
| Payroll processing | Belongs to HR module | — |
| Student information system | Belongs to Academic module | Integration via event bus |
| Classroom scheduling | Belongs to Academic module | Shared room data via API |
| Procurement of consumables (stationery) | Different workflow, handled elsewhere | Vendor module integration |
| Human resources management | Distinct domain | Integration via event bus |
| Student fee/payment collection | Finance module responsibility | Procurement payment linkage |
| IoT sensor infrastructure | Hardware domain, not software | V4 consideration |
| AI/ML model training | Computational infrastructure | V4 consideration |

---

# 9. SUCCESS CRITERIA & KPIs
================================================================================

## 9.1 Platform Adoption KPIs

| KPI | Baseline (V1) | V2 Target | V3 Target |
|-----|---------------|-----------|-----------|
| % assets with complete lifecycle | 30% | 70% | 95% |
| Monthly depreciation run success rate | 0% (manual) | 95% | 100% |
| Mean time between maintenance failures | Unknown | Reduced 20% | Reduced 30% |
| Procurement cycle time (request to delivery) | N/A | Tracked | < 30 days avg |
| Audit completion rate (annual) | 100% (ad-hoc) | 100% (system-driven) | 100% (automated) |

## 9.2 Quality KPIs

| KPI | Baseline | V2 Target | V3 Target |
|-----|----------|-----------|-----------|
| Test coverage | 0% | 60% | 80%+ |
| Production incidents per month | Unknown | < 5 | < 2 |
| Mean time to resolve incidents | Unknown | < 4 hours | < 2 hours |
| API error rate (p99) | N/A | < 1% | < 0.1% |
| Page load p95 | > 5s | < 2s | < 1s |

## 9.3 Compliance KPIs

| KPI | Baseline | V2 Target | V3 Target |
|-----|----------|-----------|-----------|
| Audit trail coverage | 0% (none) | 100% of writes | 100% of writes |
| SoD violations caught | 0 | 100% | 100% |
| Disposal process adherence | 0% | 80% | 100% |
| Financial report accuracy | Manual estimation | Auto-computed | Auto-computed, GL-verified |

---

# 10. GLOSSARY
================================================================================

| Term | Definition |
|------|-----------|
| Asset | Any tangible, long-lived item owned or controlled by the institution |
| Asset Code | Human-readable unique identifier following policy (PREFIX-YEAR-SEQ) |
| Book Value | Acquisition cost minus accumulated depreciation |
| CAPEX | Capital Expenditure — investment in long-lived assets |
| OPEX | Operating Expenditure — day-to-day maintenance and operational costs |
| Depreciation | Systematic allocation of an asset's cost over its useful life |
| Disposal | Formal process of retiring an asset from active inventory |
| GL | General Ledger — the complete system of record for financial accounts |
| Multi-tenancy | Sharing a single deployment across multiple schools with data isolation |
| PMK 102/2022 | Indonesian Ministry of Finance regulation on government asset management |
| Requisition | A formal request to procure goods or services |
| SoD | Segregation of Duties — preventing conflicts of interest in workflows |
| Useful Life | Estimated period an asset remains operational before replacement |
| Voucher | Financial document supporting a transaction (invoice, delivery note, BAST) |
| Work Order | A maintainable ticket for a specific maintenance task |
| BAST | Berita Acara Serah Terima — Indonesian handover certificate |

---

# 11. DOCUMENT CHANGELOG
================================================================================

| Version | Date | Author | Change |
|---------|------|--------|--------|
| 1.0 | 2026-07-01 | Enterprise Architecture Team | Initial release |

---

*End of BR-001 — Enterprise Vision & Business Requirement Specification.*
*Report prepared: 2026-07-01*
*Classification: Confidential — CTO / Enterprise Architecture Board / Technical Steering Committee*
*Validated against: EA-002 (Blueprint), Audit Report (Architecture Maturity)*
