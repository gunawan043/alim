# ALIM — Dormitory (Asrama) Management System
## Complete Architecture Review & Refactoring Blueprint

**Date:** 2026-07-01
**Scope:** Dormitory/Asrama Management System only
**Classification:** Architecture Review (NO CODE CHANGES)

---

# PHASE 1: SYSTEM ARCHITECTURE MAP

## 1.1 Top-Level Module Hierarchy

```
School (Sekolah)
 └── Work Unit (Pengasuhan)
       └── Dormitory (Asrama) ────────────────┐
             ├── DormitoryWing (Gedung/Blok)  │
             │     └── DormitoryRoom (Kamar)  │
             │           └── DormitoryResident│ ◄── Student (Akademik)
             │                                │
             ├── DormitoryAttendance ─────────┤
             ├── DormitoryAttendanceRecap ────┤
             ├── DormitoryPermit ─────────────┤
             ├── DormitoryViolation ──────────┤
             ├── DormitoryRoomMove ───────────┤
             ├─�� DormitoryInventory ──────────┤
             ├── DormitoryPost ───────────────┤
             │     └── DormitoryPostResponse──┤
             ├── DormitoryActivityTemplate ───┤
             ├── DormitoryActivityLog ────────┤
             ├── DormitoryVisitLog ───────────┤
             ├── DormitoryEmergencyBroadcast──┤
             └── RoomSupervisor ──────────────┘
                                                │
              External Consumers                ▼
              ┌─────────────────────────────────────────────┐
              │ Mobile API (Wali Santri)                     │
              │ Dashboard & Analytics                        │
              │ Reports (Export)                             │
              │ Notifications (WhatsApp/In-App)              │
              │ Queue Workers (Overdue Processing)           │
              └─────────────────────────────────────────────┘
```

## 1.2 Component Dependency Map

| Layer | Components | Depends On |
|-------|-----------|------------|
| **Routing** | `web.php` route groups | Middleware, Controllers |
| **Controllers (14)** | DormitoryMaster, Dormitory, Wing, Room, Resident, Permit, Attendance, Violation, VisitLog, Post, Inventory, RoomMove, RoomApi, MobileApi | Models, DormitoryService, FormRequest (1 only) |
| **Models (16)** | Dormitory, Wing, Room, Resident, Attendance, AttendanceRecap, Permit, Violation, Inventory, VisitLog, Post, PostResponse, ActivityTemplate, ActivityLog, EmergencyBroadcast, RoomSupervisor | Laravel Eloquent, UUID trait, SoftDeletes (5), LogsDeletion (5) |
| **Services (1)** | DormitoryService | NotificationUniversalService |
| **Policy (1)** | DormitoryPolicy | User model |
| **Permissions (1)** | DormitoryPermissionProvider | User, role levels |
| **Jobs (0)** | None | — |
| **Events (0)** | None | — |
| **Listeners (0)** | None | — |
| **Observers (0)** | None | — |
| **Commands (1)** | ProcessOverduePermit (referenced in code, NOT registered) | DormitoryService |
| **Notifications** | Via NotificationUniversalService | Pusher/Echo, WhatsApp |
| **Views (46)** | Blade templates organized by sub-module | Layouts, sidebar |
| **Tests (1)** | WaliSantriApiTest (permit auth only) | Laravel PHPUnit |

## 1.3 External Dependencies

| External System | Purpose | Integration |
|----------------|---------|-------------|
| **Student Model** | Resident identity | BelongsTo relationship |
| **AcademicYear** | Year-scoped data | BelongsTo on every yearly entity |
| **StudentMahrom** | Parent/guardian for permits | FK on DormitoryPermit |
| **WaliSantri** | Mobile API authorization | Query filter |
| **StudentHealthPermit** | Sick permit validation | Cross-model query in DormitoryService |
| **NotificationUniversalService** | WhatsApp + In-App notifications | Service injection |
| **InstitutionDecree** | Supervisor assignment reference | FK on RoomSupervisor |
| **Spatie ActivityLog** | Deletion audit trail | LogsDeletion trait |
| **Maatwebsite/Excel** | Export capability (unused) | Not used for dormitory |

---

# PHASE 2: DEPENDENCY GRAPH

## 2.1 Entity Dependency Matrix

### Core Entities

| Entity | Incoming Deps | Outgoing Deps | Controllers | Services | Views | Routes |
|--------|--------------|---------------|-------------|----------|-------|--------|
| Dormitory | Wing, Room, Resident, Permit, Violation, Inventory, Post, ActivityTemplate, ActivityLog, VisitLog, EmergencyBroadcast, RoomSupervisor | School, WorkUnit, User(head) | DormitoryMaster, Dormitory | — | 4 | 7 CRUD |
| DormitoryWing | Room, RoomSupervisor | Dormitory, User(supervisor) | DormitoryWing, Dormitory(inline) | — | 4 | 7 CRUD |
| DormitoryRoom | Resident, Attendance, Permit, Violation, Inventory, ActivityLog, VisitLog, RoomMove | Dormitory, Wing | DormitoryRoom, Dormitory(inline), RoomApi | — | 4 | 7 CRUD |
| DormitoryResident | Attendance, Permit, Violation, RoomMove, ActivityLog, RoomApi | Student, Room, Dormitory, AcademicYear | DormitoryResident, RoomApi | — | 3 | 5 (+API 3) |

### Transactional Entities

| Entity | Incoming Deps | Outgoing Deps | Controllers | Services | Views | Routes |
|--------|--------------|---------------|-------------|----------|-------|--------|
| DormitoryAttendance | AttendanceRecap, AttendanceController | Dormitory, Room, Student, AcademicYear | DormitoryAttendance | DormitoryService(recap) | 3 | 5 |
| DormitoryAttendanceRecap | Dashboard, Reports (future) | Student, Room, Dormitory, AcademicYear | DormitoryAttendance (recap view) | DormitoryService | 1 | 1 |
| DormitoryPermit | PermitController, MobileApi | Student, Dormitory, Room, AcademicYear, User(approver), User(creator), StudentMahrom | DormitoryPermit, MobileApi | DormitoryService(notify) | 3 | 7 |
| DormitoryViolation | ViolationController | Student, Dormitory, Room, AcademicYear, User(recorder), User(witness) | DormitoryViolation | DormitoryService(notify) | 3 | 5 |
| DormitoryRoomMove | RoomMoveController | Student, Room(from), Room(to), Dormitory, AcademicYear, User(approver) | DormitoryRoomMove | — | 3 | 6 |

### Operational Entities

| Entity | Incoming Deps | Outgoing Deps | Controllers | Services | Views | Routes |
|--------|--------------|---------------|-------------|----------|-------|--------|
| DormitoryVisitLog | VisitLogController | Dormitory, Room, Student, User(approver), User(creator) | DormitoryVisitLog | — | 3 | 8 |
| DormitoryInventory | InventoryController | Room, Dormitory, User(checkedBy) | DormitoryInventory | — | 3 | 6 |
| DormitoryPost | PostController, Dashboard | Dormitory, User(creator), Student(responses) | DormitoryPost | — | 4 | 7 |
| DormitoryPostResponse | DormitoryPost | DormitoryPost, Student | — (inline) | — | — | — |
| DormitoryActivityTemplate | PostController(template) | Dormitory | — (inline) | — | 1 | 3 |
| DormitoryActivityLog | PostController(activity) | DormitoryResident, Dormitory, AcademicYear, User(recorded) | — (inline) | — | 1 | 1 |
| DormitoryEmergencyBroadcast | PostController(broadcast) | Dormitory, User(createdBy) | — (inline) | DormitoryService | 1 | 2 |
| RoomSupervisor | Dashboard/Reporting (future) | User, Room, Dormitory, AcademicYear, InstitutionDecree | — | — | — | 0 |

## 2.2 Data Flow Diagram

```
User (Wali/Kepala Asrama/Admin Asrama/Musyrif)
    │
    ▼
Middleware (Auth → Role Check → SchoolContext → Dormitory Access)
    │
    ▼
Controller (14 controllers, 100+ actions)
    │
    ├─► FormRequest (1 file only — Mobile CreateDormitoryPermitRequest)
    │
    ├─► Validation (inline in controllers — NO centralized FormRequest)
    │
    ├─► DormitoryService (3 methods: notify, process, generate)
    │     └─► NotificationUniversalService (pusher + WhatsApp)
    │
    ├─► DB operations (direct Model::create/update/query)
    │
    ├─► File storage (Dormitory.logo_path, Permit.document_path, Post.attachment_path)
    │
    ▼
View (Blade templates — 46 files)
    │
    ▼
Browser (Bootstrap 5, Vite, Laravel Echo for realtime)
```

## 2.3 Mobile API Flow

```
Mobile Client (Wali App)
    │
    ▼
 Sanctum Authentication
    │
    ▼
Api/Mobile/V1/DormitoryPermitController
    │
    ├─► CreateDormitoryPermitRequest (validation only)
    │
    ├─► Wali-Santri relationship check (authorization)
    │
    ├─► DormitoryPermit::create()
    │
    ▼
Response JSON
```

---

# PHASE 3: SOURCE OF TRUTH ANALYSIS

## 3.1 Data Ownership Map

| Data Element | Owner Table | Write Authority | Read Consumers | Notes |
|-------------|------------|-----------------|----------------|-------|
| Room Capacity | `dormitory_rooms.capacity` | RoomController | ResidentController, RoomApiController, RoomMoveController, Dashboard | **DUPLICATED LOGIC** — 3+ controllers calculate occupancy separately |
| Active Resident Count | `dormitory_residents` WHERE is_active=1 | ResidentController, RoomApiController | RoomController, WingController, Dashboard | **NO SINGLE SOURCE** — each controller computes independently |
| Current Academic Year | `academic_years` WHERE is_active=1 | AcademicYear Model (global) | ALL dormitory controllers | **DUPLICATED QUERY** — 10+ places repeat `AcademicYear::where('is_active',true)->first()` |
| Dormitory Access | `dormitories` WHERE school_id=? | DormitoryMasterController, DormitoryController | — | **DUPLICATED CRUD** — 2 controllers for dormitory management (master vs school-level) |
| Permit Status | `dormitory_permits.status` | PermitController | ResidentController (show), Dashboard, Reports | Single source — good |
| Attendance Status | `dormitory_attendances.status` | AttendanceController | Recap, Dashboard, Reports | Single source — good |
| Violation Points | `dormitory_violations.points` | ViolationController | Dashboard, Student Profile | Single source — good |
| Room Supervisor | `room_supervisors` WHERE status='active' | RoomSupervisor Model (?) | — | **ORPHANED TABLE** — no controller manages it |
| Activity Log | `dormitory_activity_logs.data` | PostController (inline) | — | No dedicated controller |
| Emergency Broadcast | `dormitory_emergency_broadcasts` | PostController (inline) | — | No dedicated controller |

## 3.2 Source of Truth Problems

| Problem | Affected Tables | Root Cause | Impact |
|---------|----------------|------------|--------|
| Capacity calculation scattered | dormitory_rooms + dormitory_residents | No service encapsulation | Inconsistent counts across controllers |
| Dormitory CRUD duplicated | dormitories | 2 controllers (Master + regular) | Config confusion, permission overlap |
| Academic year query duplicated | ALL tables | Inline `AcademicYear::where` in every controller | Maintenance burden, risk of inconsistency |
| RoomSupervisor orphan | room_supervisors | Migration exists, no controller | No UI to manage supervisors |
| Activity/Broadcast managed inline | activity_logs, emergency_broadcasts | PostController does too much | Violation of SRP |
| AttendanceRecap computed on-demand | dormitory_attendance_recaps | DormitoryService method exists but no scheduled trigger | Manual generation only |
| Overdue processing incomplete | dormitory_permits | DormitoryService::processOverduePermits exists, but command NOT registered | Dead code — scheduled job never runs |

---

# PHASE 4: BUSINESS LIFECYCLE

## 4.1 Student → Dormitory Lifecycle

### Step 1: Student Acceptance (External to Dormitory)

| Aspect | Details |
|--------|---------|
| **Trigger** | Student created/activated in Akademik module |
| **Database** | `students` table |
| **Controller** | StudentController (Akademik) |
| **Events** | StudentCreated, StudentStatusChanged |
| **Dormitory Impact** | NO automatic dormitory provisioning — purely manual assignment |
| **Gap** | No event-driven dormitory assignment on student acceptance |

### Step 2: Dormitory Assignment (Check-In)

| Aspect | Details |
|--------|---------|
| **Trigger** | Admin/Staff creates DormitoryResident |
| **Database** | `dormitory_residents` INSERT |
| **Controller** | DormitoryResidentController::store() |
| **Service** | None — direct Model::create |
| **Validation** | Inline — student_id, room_id, check_in_date, bed_number, room capacity |
| **Event** | None |
| **Notification** | None |
| **Job** | None |
| **Audit** | Spatie ActivityLog (via LogsDeletion on related models) |
| **Issues** | No transaction wrapping (capacity check + insert are separate), no occupant update on Room/ Wing/ Dormitory capacity counters, no guardian notification |

### Step 3: Daily Attendance Recording

| Aspect | Details |
|--------|---------|
| **Trigger** | Wali kamar / Musyrif selects session + statuses |
| **Database** | `dormitory_attendances` batch INSERT/UPDATE |
| **Controller** | DormitoryAttendanceController::store() |
| **Validation** | Inline — attendance_date, session, attendances array |
| **Service** | None |
| **Event** | None |
| **Notification** | None |
| **Issues** | Batch insert without transaction, no duplicate prevention per (student, date, session), no realtime pulse |

### Step 4: Attendance Verification

| Aspect | Details |
|--------|---------|
| **Trigger** | Supervisor verifies recorded attendance |
| **Database** | `dormitory_attendances` UPDATE (verified_by, verified_at) |
| **Controller** | DormitoryAttendanceController::verify() |
| **Event** | None |
| **Impact** | Attendance becomes "locked" — feeds recaps and reports |

### Step 5: Monthly Attendance Recap

| Aspect | Details |
|--------|---------|
| **Trigger** | DormitoryService::generateMonthlyRecap() called manually |
| **Database** | `dormitory_attendance_recaps` upsert per student per month |
| **Controller** | DormitoryAttendanceController::recap() (views only) |
| **Service** | DormitoryService::generateMonthlyRecap() |
| **Scheduling** | NONE — command exists nowhere in Kernel or console.php |
| **Issue** | Recaps only exist if manually generated; no cron/scheduler |

### Step 6: Permit Request (Izin Pulang/Keluar)

| Aspect | Details |
|--------|---------|
| **Trigger** | Resident or Wali creates permit via web or mobile |
| **Database** | `dormitory_permits` INSERT (status=pending) |
| **Controller** | DormitoryPermitController::store() or Mobile DormitoryPermitController::store() |
| **Validation** | Inline (web) + CreateDormitoryPermitRequest (mobile only) |
| **Service** | DormitoryService::validateMahromCompanion(), ::canApplySickPermit() |
| **Cross-check** | StudentHealthPermit for sick permits |
| **Event** | None |
| **Notification** | None on create, only on approval |

### Step 7: Permit Approval/Rejection

| Aspect | Details |
|--------|---------|
| **Trigger** | Authorized staff approves/rejects |
| **Database** | `dormitory_permits` UPDATE (status, approved_by, approved_at) |
| **Controller** | DormitoryPermitController::approve()/reject() |
| **Service** | DormitoryService::notifyMahromOnPermitApproval() |
| **Event** | None |
| **Notification** | WhatsApp + In-App to mahrom |

### Step 8: Permit Return / Overdue

| Aspect | Details |
|--------|---------|
| **Trigger** | Student returns OR automated overdue processing |
| **Database** | `dormitory_permits` UPDATE (actual_return_datetime, status) |
| **Controller** | DormitoryPermitController::returnRecord() (manual) |
| **Service** | DormitoryService::notifyMahromOnAlpa() for overdue |
| **Automation** | DormitoryService::processOverduePermits() exists but **command NOT registered** |
| **Issue** | Overdue processing is dead code — no scheduled execution |

### Step 9: Violation Recording

| Aspect | Details |
|--------|---------|
| **Trigger** | Musyrif/Wali kamar records violation |
| **Database** | `dormitory_violations` INSERT |
| **Controller** | DormitoryViolationController::store() |
| **Service** | DormitoryService::notifyMahromOnViolation() |
| **Event** | None |
| **Notification** | WhatsApp + In-App to mahrom immediately |

### Step 10: Room Transfer (Mutasi Kamar)

| Aspect | Details |
|--------|---------|
| **Trigger** | Request for room move |
| **Database** | `dormitory_room_moves` INSERT (status=pending) |
| **Controller** | DormitoryRoomMoveController::store() |
| **Validation** | Inline — from_room ≠ to_room, capacity check, resident active |
| **Approval** | DormitoryRoomMoveController::approve() — updates resident.room_id |
| **Transaction** | NONE — capacity check and room update not atomic |
| **Notification** | None |

### Step 11: Student Checkout / Graduation

| Aspect | Details |
|--------|---------|
| **Trigger** | Manual checkout OR student graduation (not automated) |
| **Database** | `dormitory_residents` UPDATE (check_out_date, check_out_reason, is_active=0) |
| **Controller** | DormitoryResidentController::checkout() |
| **Capacity Impact** | Room occupancy count decreases implicitly |
| **Event** | None |
| **Notification** | None |
| **Academic Cascade** | StudentGraduated event triggers academic provisioning — BUT NO dormitory cleanup |
| **Issue** | No automatic dormitory checkout on student graduation |

### Step 12: Archive

| Aspect | Details |
|--------|---------|
| **Mechanism** | DormitoryResident has NO soft delete |
| **Soft Deleted** | Only ActivityLog, ActivityTemplate, EmergencyBroadcast, Post, VisitLog |
| **Archive Query** | `WHERE is_active = 0 AND check_out_date IS NOT NULL` |
| **Issue** | No formal archival process, retention policy, or cleanup job |

---

# PHASE 5: DATA FLOW (Complete Operation)

## 5.1 Typical CRUD Flow (e.g., Create Room)

```
User → Browser
  ▼
POST /dormitory/{uuid}/kamar
  ▼
DormitoryRoomController::store(Request $request, string $userId, string $asramaUuid)
  ▼
// NO FormRequest validation
// Manual inline validation:
  $validated = $request->validate([
    'code' => 'required|string|max:20|unique:dormitory_rooms',
    'capacity' => 'required|integer|min:1',
    'room_type' => 'required|in:reguler,khusus,isolasi,musyrif',
  ]);
  ▼
$dormitory = Dormitory::findOrFail($asramaUuid);
$room = DormitoryRoom::create([...]);
  ▼
// NO service layer
// NO event fired
// NO capacity counter update
// NO notification
  ▼
return redirect()->route('dormitory.rooms.index', [...])
  ▼
Blade: dormitory/rooms/show.blade.php
```

## 5.2 Transaction Flow (e.g., Permit Approval)

```
User → POST /dormitory/{uuid}/izin/{permitUuid}/approve
  ▼
DormitoryPermitController::approve(Request, userId, asramaUuid, permitUuid)
  ▼
$permit = DormitoryPermit::where([...])->firstOrFail();
$permit->update([
  'status' => 'approved',
  'approved_by' => $userId,
  'approved_at' => now(),
]);
  ▼
DormitoryService::notifyMahromOnPermitApproval($permit)
  ▼
// NotificationUniversalService creates notification records
// Pusher broadcast for realtime
  ▼
return redirect()->back()->with('success', '...')
```

## 5.3 Data Flow Gaps

| Operation | Has Transaction? | Has Event? | Has Job? | Has Notification? | Has Audit Trail? |
|-----------|-----------------|------------|----------|-------------------|-----------------|
| Resident Check-In | NO | NO | NO | NO | Partial (ActivityLog trait on some models) |
| Room Assignment | NO | NO | NO | NO | NO |
| Attendance Recording | NO | NO | NO | NO | NO |
| Permit Creation | NO | NO | NO | NO | NO |
| Permit Approval | NO | NO | NO | YES | NO |
| Violation Recording | NO | NO | NO | YES | NO |
| Room Move Approval | NO | NO | NO | NO | NO |
| Checkout | NO | NO | NO | NO | NO |
| Overdue Processing | NO | NO | NO | YES | NO |
| Attendance Recap | NO | NO | NO | NO | NO |

---

# PHASE 6: TRANSACTION BOUNDARIES

## 6.1 Operations That MUST Use DB::transaction()

### 6.1.1 Resident Check-In (DormitoryResidentController::store)

**Current Flow:**
```php
// 1. Check duplicate resident — separate query
$exists = DormitoryResident::where('student_id', $studentId)
    ->where('academic_year_id', $yearId)->where('is_active', true)->exists();

// 2. Check room capacity — separate query
$occupied = DormitoryResident::where('room_id', $roomId)
    ->where('is_active', true)->count();

// 3. Insert resident — separate query
DormitoryResident::create([...]);
```

**Correct Flow:**
```php
DB::transaction(function () use ($data) {
    // All checks + insert in single atomic operation
    DormitoryResident::create($data);
    // Optionally: update dormitory_rooms.current_occupancy if added later
});
```

**Risk:** Race condition between capacity check and insert. Two simultaneous assigns could exceed capacity.

---

### 6.1.2 Room Move Approval (DormitoryRoomMoveController::approve)

**Current Flow:**
```php
// 1. Update move record status
$move->update(['approval_status' => 'approved', ...]);

// 2. Update resident room — separate query
$resident->update(['room_id' => $toRoomId, ...]);
```

**Correct Flow:**
```php
DB::transaction(function () use ($move, $toRoom) {
    $move->update([...]);
    $move->student->update(['room_id' => $toRoom->id]);
});
```

**Risk:** Move approved but resident not moved (or vice versa) — data inconsistency.

---

### 6.1.3 Bulk Attendance Recording (DormitoryAttendanceController::store)

**Current Flow:**
```php
// 1. Loop through attendees — each insert is separate
foreach ($attendances as $att) {
    DormitoryAttendance::updateOrCreate([...]);
}
```

**Correct Flow:**
```php
DB::transaction(function () use ($data) {
    foreach ($data['attendances'] as $att) {
        DormitoryAttendance::updateOrCreate([...]);
    }
});
```

**Risk:** Partial attendance save — some students recorded, others not.

---

### 6.1.4 Permit Approval (DormitoryPermitController::approve)

**Current Flow:**
```php
$permit->update([...]);
// Notification is external (side effect, not transactional)
```

**Correct Flow:** Same — notification is a side effect and should NOT be in the transaction. Current approach is acceptable, but a DomainEvent should be fired instead of direct notification call.

---

### 6.1.5 Checkout (DormitoryResidentController::checkout)

**Current Flow:**
```php
$resident->update([
    'is_active' => false,
    'check_out_date' => $request->date,
    'check_out_reason' => $request->reason,
]);
```

**Correct Flow:** Consider wrapping in transaction if additional cleanup is added (e.g., freeing bed in RoomSupervisor, closing active permits/violations).

---

### 6.1.6 Summary Table

| Operation | Current | Should Use Transaction? | Priority |
|-----------|---------|------------------------|----------|
| Resident Store | NO | YES | HIGH |
| Room Move Approve | NO | YES | HIGH |
| Bulk Attendance | NO | YES | MEDIUM |
| Permit Approve | NO | PARTIAL (event instead of direct call) | MEDIUM |
| Checkout | NO | LOW (single update, but prepare for expansion) | LOW |
| Permit Create | NO | YES (mahrom validation + insert) | MEDIUM |
| Violation Create | NO | LOW (single insert + notification) | LOW |
| Room Move Store | NO | YES (capacity check + move request) | MEDIUM |

---

# PHASE 7: DOMAIN ANALYSIS

## 7.1 Bounded Contexts

### Context 1: Facility Management
**Responsibility:** Physical structure — buildings, wings, rooms
- Dormitory
- DormitoryWing
- DormitoryRoom
- DormitoryInventory

### Context 2: Resident Management
**Responsibility:** Student-housing relationship, lifecycle
- DormitoryResident
- DormitoryRoomMove
- RoomSupervisor (currently orphaned — should belong here)

### Context 3: Attendance & Activities
**Responsibility:** Daily presence tracking, activities
- DormitoryAttendance
- DormitoryAttendanceRecap
- DormitoryActivityTemplate
- DormitoryActivityLog

### Context 4: Permission & Leave
**Responsibility:** Student leave requests, approval workflow
- DormitoryPermit

### Context 5: Conduct & Discipline
**Responsibility:** Rule violations, enforcement
- DormitoryViolation

### Context 6: Communication
**Responsibility:** Announcements, broadcasts, posts
- DormitoryPost
- DormitoryPostResponse
- DormitoryEmergencyBroadcast

### Context 7: Visitor Management
**Responsibility:** External visitors, check-in/out
- DormitoryVisitLog

### Context 8: Notifications (Cross-Cutting)
**Responsibility:** Push, WhatsApp, In-App
- NotificationUniversalService (shared service, not dormitory-specific)

## 7.2 Coupling & Cohesion Analysis

| Issue | Context | Detail | Severity |
|-------|---------|--------|----------|
| **High Coupling** | All contexts | Every controller queries Dormitory::findOrFail directly — tight coupling to route parameter | MEDIUM |
| **Low Cohesion** | DormitoryPostController | Handles 4 contexts: Posts, Templates, Activity Logs, Broadcasts — all in one controller | HIGH |
| **Wrong Ownership** | Dormitory (main) | Contains 13 inline methods for Wing/Room CRUD �� duplicates DormitoryWingController/DormitoryRoomController | HIGH |
| **Wrong Ownership** | DormitoryMaster vs Dormitory | 2 controllers manage the same entity (Dormitory) with different role gates | HIGH |
| **Orphaned Entity** | RoomSupervisor | Table exists, migration exists, model exists — NO controller, NO routes, NO views | HIGH |
| **Orphaned Service** | DormitoryService::generateMonthlyRecap | Service method exists but no scheduled trigger or UI entry point | MEDIUM |
| **Orphaned Service** | DormitoryService::processOverduePermits | Service method exists but artisan command is NOT registered | HIGH |
| **Mixed Concerns** | DormitoryRoomApiController | Mixes availability query, bulk add, and remove — could be split | LOW |
| **Inconsistent Auth** | DormitoryPermissionProvider vs Controllers | Provider exists but is NEVER used — controllers use inline role checks | HIGH |

---

# PHASE 8: DUPLICATE LOGIC ANALYSIS

## 8.1 Duplicated Patterns

### Duplication 1: Dormitory Load Boilerplate

**Frequency:** 50+ occurrences across 12 controllers

```php
// Every method in every controller starts with this:
$dormitory = Dormitory::findOrFail($asramaUuid);
```

**Locations:** DormitoryWingController (6), DormitoryRoomController (6), DormitoryResidentController (5), DormitoryPermitController (5), DormitoryViolationController (5), DormitoryAttendanceController (5), DormitoryPostController (7), DormitoryVisitLogController (4), DormitoryRoomMoveController (6), DormitoryInventoryController (5)

**Root Cause:** No base controller, no model binding, no concern trait.

**Recommended Abstraction:** Route model binding for Dormitory, or a `DormitoryScoped` trait used by all controllers.

---

### Duplication 2: Academic Year Query

**Frequency:** 20+ occurrences

```php
$activeYear = AcademicYear::where('is_active', true)->first();
$academicYearId = $activeYear?->id;
```

**Locations:** Almost every controller's `create()` and `store()` methods.

**Recommended Abstraction:** A helper method on AcademicYear model (`AcademicYear::active()`) or a service/repository.

---

### Duplication 3: Stats/Count Pattern

**Frequency:** 8 controllers

```php
$total = Model::where('dormitory_id', $asramaUuid)->count();
$approved = Model::where('dormitory_id', $asramaUuid)->where('status', 'approved')->count();
$pending = Model::where('dormitory_id', $asramaUuid)->where('status', 'pending')->count();
```

**Root Cause:** No shared StatsService or scoped query builder.

**Recommended Abstraction:** `DormitoryStatsService` or Eloquent scope on each model.

---

### Duplication 4: Active Resident Query

**Frequency:** 6 controllers

```php
DormitoryResident::with(['student', 'room'])
    ->where('dormitory_id', $asramaUuid)
    ->where('is_active', true)
    ->get();
```

**Locations:** PermitController::create, ViolationController::create, AttendanceController::create, RoomMoveController::create, VisitLogController::create, RoomApiController::availableResidents

**Recommended Abstraction:** `DormitoryResident::activeForDormitory($dormitoryId)` scope.

---

### Duplication 5: Room Capacity Check

**Frequency:** 3 controllers

```php
$currentOccupancy = DormitoryResident::where('room_id', $roomId)
    ->where('is_active', true)->count();
if ($currentOccupancy >= $room->capacity) { /* ... */ }
```

**Locations:** ResidentController::store, RoomMoveController::store, RoomApiController::addResident

**Recommended Abstraction:** `DormitoryRoom::hasAvailableSpace()` method on the model.

---

### Duplication 6: Approval/Rejection Endpoints

**Frequency:** 3 controllers (Permit, VisitLog, RoomMove)

```php
$entity->update([
    'status' => 'approved',
    'approved_by' => auth()->id(),
    'approved_at' => now(),
]);
```

**Pattern:** Nearly identical approve/reject methods with only the model and redirect route changing.

**Recommended Abstraction:** A `ApprovableEntity` interface or base controller with generic approve/reject.

---

### Duplication 7: Supervisor Filtering

**Frequency:** 2 methods (WingController::create, WingController::edit)

```php
User::whereHas('employment')
    ->whereHas('employment', fn($q) => $q->where('school_id', $dormitory->school_id))
    ->get();
```

**Recommended Abstraction:** `User::schoolSupervisors($schoolId)` scope.

---

## 8.2 Summary Table

| Duplicate Pattern | Occurrences | Risk | Effort to Abstract |
|------------------|-------------|------|-------------------|
| Dormitory::findOrFail | 50+ | LOW | 1 day (route model binding) |
| AcademicYear query | 20+ | LOW | 2 hours (model scope) |
| Stats counting | 8 controllers | LOW | 1 day (StatsService) |
| Active resident query | 6 controllers | LOW | 2 hours (model scope) |
| Room capacity check | 3 controllers | MEDIUM (race condition) | 2 hours (model method) |
| Approve/reject | 3 controllers | LOW | 1 day (base controller / trait) |
| Supervisor filter | 2 methods | LOW | 1 hour (model scope) |

---

# PHASE 9: TECHNICAL DEBT ROADMAP

## 9.1 Debt by Category

### Architecture (12 items)

| # | Issue | Impact |
|---|-------|--------|
| A1 | DormitoryPostController handles 4 sub-domains (posts, templates, activity, broadcast) | Violates SRP |
| A2 | DormitoryController has 13 inline wing/room CRUD methods + API helpers | Bloated controller, duplicated elsewhere |
| A3 | DormitoryMasterController duplicates DormitoryController's CRUD for same entity | Confusing ownership |
| A4 | RoomSupervisor table has no controller/routes/views — orphaned entity | Incomplete feature |
| A5 | DormitoryService methods (generateMonthlyRecap, processOverduePermits) have no scheduled execution | Dead code path |
| A6 | No event-driven architecture — all operations are synchronous | Tight coupling, no async capability |
| A7 | DormitoryPermissionProvider exists but is never used | Authorization fragmentation |
| A8 | 14 controllers with no shared base/trait for common patterns | Code duplication |
| A9 | No repository/service layer for core entities (Room, Resident, Wing) | Fat controllers |
| A10 | Mobile API has only 1 endpoint (permit) — incomplete mobile coverage | Incomplete feature |

### Database (8 items)

| # | Issue | Impact |
|---|-------|--------|
| D1 | No indexes on frequently filtered columns (check_out_date, check_in_date, move_date) | Slow queries at scale |
| D2 | No foreign key cascades on some critical relationships (DormitoryResident.student_id) | Orphaned records risk |
| D3 | DormitoryAttendanceRecap uses integer for semester instead of enum | Type safety |
| D4 | DormitoryPermit uses string for secondary_status instead of enum | Type safety |
| D5 | No composite unique constraint on (student_id, room_id, academic_year_id) where active | Double booking risk |
| D6 | DormitoryInventories.item_condition uses string values not matching enum in model accessor | Mismatch |
| D7 | No partitioning strategy for attendance/activity logs (expected high volume) | Performance degradation |
| D8 | room_supervisors.decree_id references institution_decrees but no business logic creates decrees | Orphaned FK |

### Performance (6 items)

| # | Issue | Impact |
|---|-------|--------|
| P1 | N+1 queries in resident listing (each resident loads student + room without eager loading) | Query explosion |
| P2 | Room capacity calculated via COUNT(*) on every page load | Slow with large datasets |
| P3 | No query result caching for dormitory/wing/room hierarchies | Repeated DB hits |
| P4 | Attendance batch create without transaction or chunking | Slow for large dormitories |
| P5 | Duplicate queries for same dormitory data across controller methods | Memory wastage |
| P6 | No eager loading strategy documented — relies on implicit query patterns | Inconsistent performance |

### Security (5 items)

| # | Issue | Impact |
|---|-------|--------|
| S1 | `authorize()` always returns true in CreateDormitoryPermitRequest | Bypasses policy check |
| S2 | No authorization gate checks in most controllers — rely on role-hardware-coded abort_unless | Inconsistent enforcement |
| S3 | DormitoryMasterController uses manual role validation bypassing Laravel Policy system | Policy fragmentation |
| S4 | File upload (logo, attachment, document) lacks MIME type validation | Potential file injection |
| S5 | No rate limiting on API endpoints | DoS risk |

### Business Logic (7 items)

| # | Issue | Impact |
|---|-------|--------|
| B1 | No automatic dormitory checkout on student graduation | Manual cleanup required |
| B2 | No automatic dormitory assignment on student acceptance | Manual process only |
| B3 | Room capacity is NOT enforced at DB level | Race condition |
| B4 | Overdue permit processing exists but never scheduled | Compliance risk |
| B5 | Attendance recap is manual — should be automated | Reporting gaps |
| B6 | No validation that permit departure/return aligns with academic calendar | Business rule violation |
| B7 | Violation points have no cumulative threshold or auto-action | Incomplete discipline system |

### UI/UX (4 items)

| # | Issue | Impact |
|---|-------|--------|
| U1 | 46 blade templates — many are CRUD scaffolding with minimal differentiation | Maintenance overhead |
| U2 | No reactive/frontend framework — all updates require full page reload | Poor UX |
| U3 | Activity logs and broadcasts managed via PostController tabs — poor information architecture | Confusion |
| U4 | No bulk action support (bulk checkout, bulk attendance update per session) | Operator fatigue |

### Testing (1 item)

| # | Issue | Impact |
|---|-------|--------|
| T1 | Only 1 test file covering dormitory (mobile permit auth) — 100+ actions untested | Zero confidence in changes |

### API (2 items)

| # | Issue | Impact |
|---|-------|--------|
| API1 | Only 1 mobile API controller (DormitoryPermitController) — rest is web-only | Mobile clients missing |
| API2 | DormitoryRoomApiController uses non-standard routes (/kamar/{id}/penghuni-massal) | Inconsistent API design |

### Developer Experience (4 items)

| # | Issue | Impact |
|---|-------|--------|
| DX1 | No FormRequest classes for web controllers — inline validation everywhere | Inconsistent validation, no swagger/doc generation |
| DX2 | No code ideation/IDE completion for magic strings (enum values) — all strings, no PHP enums | Typo bugs |
| DX3 | 14 controllers in flat namespace — difficult to navigate | Cognitive overload |
| DX4 | No API documentation (OpenAPI/Swagger) for any endpoint | Onboarding friction |

---

# PHASE 10: PRODUCTION READINESS

## 10.1 Readiness Scorecard

| Dimension | Score (1-10) | Justification |
|-----------|-------------|---------------|
| **Scalability** | 4 | No queue processing, no caching, no partitioning, duplicate queries |
| **Maintainability** | 3 | High duplication, fat controllers, no service layer, no tests |
| **Availability** | 7 | No single points of failure, synchronous operations |
| **Recoverability** | 3 | No transactions, no soft deletes on critical tables, no audit on key entities |
| **Observability** | 4 | Spatie ActivityLog on 5 models only, no custom logging, no metrics |
| **Logging** | 5 | Basic Laravel logging + Spatie activity log — no structured/dormitory-specific logs |
| **Monitoring** | 2 | No dashboards, no alerts, no health checks for dormitory |
| **Backup** | 7 | Standard Laravel/MySQL backup — no dormitory-specific strategies |
| **Disaster Recovery** | 3 | No rollback scripts, no data restoration procedures |
| **Horizontal Scaling** | 3 | Session-based auth, no cache layer, no queue workers |
| **Queue Readiness** | 1 | Zero jobs for dormitory — everything synchronous |
| **Cache Readiness** | 2 | No caching of dormitory hierarchy, room availability, or stats |

## 10.2 Critical Gaps for Production Deployment

| Gap | Risk | Remediation |
|-----|------|-------------|
| No tests | Changes will break silently | Write feature tests first |
| No transactions | Data corruption on partial failures | Wrap all multi-step operations |
| No scheduled jobs | Overdue permits unprocessed, recaps stale | Register commands + Schedule |
| No caching | Slow queries under load | Cache dormitory hierarchy, room availability |
| No monitoring | Incidents detected late | Add health checks, alerts |
| Queue not used | Page loads slow during batch operations | Convert batch attendance, recap generation to jobs |

---

# PHASE 11: REFACTOR STRATEGY

## SAFE REFACTOR ORDER (Independently Deployable Phases)

### Phase 1: Foundation — Tests, Validation, Code Cleanup
- **Objective:** Establish safety net and consistent validation
- **Risk:** LOW
- **Files Affected:** 14 controllers, 1 FormRequest (new), 1 test file (new)
- **Includes:**
  - FormRequest classes for ALL web controller actions (12 new files)
  - Feature tests for critical paths (resident store, permit store, checkout)
  - Extract Dormitory::findOrFail into route model binding
- **Deployable:** YES — no behavioral change

### Phase 2: Authorization Unification
- **Objective:** Make all controllers use DormitoryPermissionProvider + Policies
- **Risk:** LOW
- **Files Affected:** DormitoryPolicy (expand), 12 controller files
- **Includes:**
  - Map DormitoryPermissionProvider gates to Laravel Policies
  - Replace inline abort_unless with $user->can()
  - Remove DormitoryMasterController duplication or clarify ownership
- **Deployable:** YES — behavioral change only in access control

### Phase 3: Transaction Safety
- **Objective:** Wrap all multi-step operations in DB::transaction()
- **Risk:** LOW
- **Files Affected:** ResidentController, RoomMoveController, AttendanceController, PermitController
- **Includes:**
  - Resident::store — capacity check + insert in transaction
  - RoomMove::approve — move record + resident update in transaction
  - Attendance::store — batch insert in transaction
  - Permit::store — mahrom validation + insert in transaction
- **Deployable:** YES — fixes race conditions

### Phase 4: Duplicate Logic Elimination
- **Objective:** Remove duplicated patterns
- **Risk:** LOW
- **Files Affected:** 14 controllers, 2 new services, 16 model files (scopes added)
- **Includes:**
  - Acade

c    - **Includes:**
      - Add model scopes: `AcademicYear::active()`, `DormitoryResident::activeForDormitory()`, `DormitoryRoom::hasAvailableSpace()`
      - Create `DormitoryStatsService` for counting patterns
      - Create `BaseDormitoryController` with approve/reject pattern
      - Consolidate DormitoryController inline wing/room methods into DormitoryWingController/DormitoryRoomController
- **Deployable:** YES — refactor only

### Phase 5: Business Logic — Events & Automation
- **Objective:** Introduce event-driven lifecycle
- **Risk:** MEDIUM
- **Files Affected:** 16 model files (events/observers), 8+ new event files, 6+ new listener files, Kernel.php
- **Includes:**
  - DormitoryResidentEvents: CheckedIn, CheckedOut, Assigned, Moved
  - DormitoryPermitEvents: Approved, Rejected, Overdue, Returned
  - DormitoryViolationEvents: Recorded
  - Observers for DormitoryResident and DormitoryPermit
  - Automatic checkout on StudentGraduated event listener
  - Automatic recap generation triggered by event or schedule
- **Deployable:** YES — additive, backward compatible

### Phase 6: Queue & Scheduled Jobs
- **Objective:** Move heavy operations off the request path
- **Risk:** MEDIUM
- **Files Affected:** 3 new job files, console.php or Kernel.php schedule
- **Includes:**
  - Register `ProcessOverduePermit` command in scheduler (hourly)
  - `GenerateAttendanceRecapJob` (daily/weekly)
  - `BulkAttendanceRecordJob` (on-demand via queue)
  - Convert DormitoryService methods to queued jobs
- **Deployable:** YES — requires queue worker

### Phase 7: Facility Cleanup — Orphaned Entities
- **Objective:** Activate RoomSupervisor management + clarify DormitoryMaster vs Dormitory
- **Risk:** MEDIUM
- **Files Affected:** 1 new controller (RoomSupervisorController), migration for current_occupancy column, 1 controller decision
- **Includes:**
  - RoomSupervisor CRUD controller + views (if business requirement exists)
  - Decide: Merge DormitoryMasterController into DormitoryController OR clarify hierarchy
  - Add `current_occupancy` column to dormitory_rooms with auto-update via observer
- **Deployable:** YES

### Phase 8: Performance Hardening
- **Objective:** Caching, indexing, query optimization
- **Risk:** LOW
- **Files Affected:** Migration files (indexes), model files (scopes/eager loading), config (cache)
- **Includes:**
  - Add database indexes for: (student_id, academic_year_id, is_active), (room_id, is_active), check_out_date, move_date
  - Cache dormitory hierarchy (Dormitory → Wing → Room tree) for 1 hour
  - Cache room availability for 5 minutes
  - Fix N+1 queries in all controllers
- **Deployable:** YES — purely additive performance improvements

### Phase 9: Mobile API Expansion
- **Objective:** Complete mobile API coverage
- **Risk:** LOW
- **Files Affected:** 4-5 new API controller files
- **Includes:**
  - Mobile/DormitoryAttendanceController (today's attendance for santri)
  - Mobile/DormitoryViolationController (santri violations for wali)
  - Mobile/DormitoryVisitLogController (visitor requests by wali)
  - Mobile/DormitoryResidentController (resident status check)
- **Deployable:** YES — additive, versioned under v1

### Phase 10: Observability & Monitoring
- **Objective:** Logging, metrics, dashboards
- **Risk:** LOW
- **Files Affected:** DormitoryService (logging), config/logging.php, new dashboard views
- **Includes:**
  - Structured logging for all dormitory operations
  - Health check endpoint: /api/health/dormitory
  - Dashboard metrics views (occupancy rate, attendance rate, violation trends)
  - ActivityLog trait on ALL dormitory models (not just 5)
- **Deployable:** YES — observational only

---

# PHASE 12: IMPLEMENTATION PLAN

## Phase 1: Foundation — Tests, Validation, Code Cleanup

| Attribute | Detail |
|-----------|--------|
| **Objectives** | FormRequest classes, route model binding, baseline test coverage |
| **Files Affected** | 14 controllers (modest changes), 12 new FormRequest files, 3+ new test files |
| **Risk** | LOW — pure refactor |
| **Dependencies** | None |
| **Rollback** | Revert git commit |
| **Testing Strategy** | Tests added in this phase verify existing behavior (golden master tests) |
| **Deployment** | Deploy during low-traffic window |

## Phase 2: Authorization Unification

| Attribute | Detail |
|-----------|--------|
| **Objectives** | Replace inline role checks with Policy + DormitoryPermissionProvider |
| **Files Affected** | DormitoryPolicy.php (expand), 12 controller files |
| **Risk** | LOW — authorization only, no business logic change |
| **Dependencies** | Phase 1 |
| **Rollback** | Revert — original abort_unless calls preserved in git |
| **Testing Strategy** | Feature tests for authorization gates (authorized/unauthorized access) |
| **Deployment** | Standard |

## Phase 3: Transaction Safety

| Attribute | Detail |
|-----------|--------|
| **Objectives** | Wrap all multi-step DB operations in DB::transaction() |
| **Files Affected** | ResidentController, RoomMoveController, AttendanceController, PermitController, ViolationController |
| **Risk** | LOW — actually reduces risk by preventing partial writes |
| **Dependencies** | Phase 1 (tests must pass first) |
| **Rollback** | Revert — transaction wrapping is safe to remove |
| **Testing Strategy** | Unit tests simulating race conditions, rollback scenarios |
| **Deployment** | Standard |

## Phase 4: Duplicate Logic Elimination

| Attribute | Detail |
|-----------|--------|
| **Objectives** | Remove duplicated boilerplate across 14 controllers |
| **Files Affected** | 14 controllers, 2 new service files, 16 model files (scopes added) |
| **Risk** | LOW — extracted to shared methods, no behavior change |
| **Dependencies** | Phase 3 |
| **Rollback** | Revert |
| **Testing Strategy** | Existing feature tests + new tests for extracted services |
| **Deployment** | Standard |

## Phase 5: Business Logic — Events & Automation

| Attribute | Detail |
|-----------|--------|
| **Objectives** | Event-driven lifecycle, automatic checkout on graduation, overdue processing |
| **Files Affected** | 16 models (+events), 8+ new event files, 6+ new listener files, 3 job files, Kernel.php |
| **Risk** | MEDIUM — introduces new behavior (automatic checkout, notifications) |
| **Dependencies** | Phase 4 (clean code base required) |
| **Rollback** | Disable event listeners via feature flag |
| **Testing Strategy** | Integration tests for event chains, end-to-end lifecycle tests |
| **Deployment** | Staging first, monitor for 48 hours |

## Phase 6: Queue & Scheduled Jobs

| Attribute | Detail |
|-----------|--------|
| **Objectives** | Async processing for heavy operations, scheduled overdue/recap jobs |
| **Files Affected** | 3 new job files, console.php, queue config |
| **Risk** | MEDIUM — requires queue worker configuration |
| **Dependencies** | Phase 5 (events fire jobs) |
| **Rollback** | Disable scheduler entries, process synchronously |
| **Testing Strategy** | Job tests with fake queues, scheduled command tests |
| **Deployment** | Requires queue worker restart |

## Phase 7: Facility Cleanup

| Attribute | Detail |
|-----------|--------|
| **Objectives** | Activate RoomSupervisor management, resolve DormitoryMaster vs Dormitory conflict |
| **Files Affected** | 1 new controller, 1 migration, 1 decision document, views |
| **Risk** | MEDIUM — business decision required (merge vs split controllers) |
| **Dependencies** | Phase 6 |
| **Rollback** | Keep old controller if merge decision favors consolidation |
| **Testing Strategy** | Feature tests for RoomSupervisor CRUD |
| **Deployment** | Requires stakeholder approval on controller structure |

## Phase 8: Performance Hardening

| Attribute | Detail |
|-----------|--------|
| **Objectives** | Indexes, caching, N+1 fix |
| **Files Affected** | 4-5 migrations (indexes), 2 service files (caching), 14 controllers (eager loading) |
| **Risk** | LOW — purely additive |
| **Dependencies** | Phase 7 |
| **Rollback** | Drop indexes, disable cache |
| **Testing Strategy** | Performance benchmarks (queries/sec, response time) |
| **Deployment** | Standard — run migrations during maintenance window |

## Phase 9: Mobile API Expansion

| Attribute | Detail |
|-----------|--------|
| **Objectives** | Extend mobile API to attendance, violations, visits, resident status |
| **Files Affected** | 4-5 new API controllers, routes/api.php |
| **Risk** | LOW — additive, versioned |
| **Dependencies** | Phase 6 (queue-ready APIs) |
| **Rollback** | Remove API routes |
| **Testing Strategy** | Feature tests for each API endpoint, mobile client integration |
| **Deployment** | Standard, API versioning protects existing clients |

## Phase 10: Observability & Monitoring

| Attribute | Detail |
|-----------|--------|
| **Objectives** | Structured logging, health checks, dashboards |
| **Files Affected** | DormitoryService, config files, 5+ new view files |
| **Risk** | LOW — observational only |
| **Dependencies** | Phase 9 |
| **Rollback** | Remove middleware/logging hooks |
| **Testing Strategy** | Verify log output format, health check endpoint returns 200 |
| **Deployment** | Standard |

---

# PHASE 13: MASTER TODO

## Architecture
- [ ] Consolidate DormitoryController inline wing/room methods
- [ ] Resolve DormitoryMasterController vs DormitoryController ownership
- [ ] Activate RoomSupervisor management (controller + views + routes)
- [ ] Move DormitoryPostController sub-contexts to dedicated controllers
- [ ] Define bounded context boundaries with clear ownership

## Authorization
- [ ] Expand DormitoryPolicy to cover all 18 gate checks
- [ ] Replace inline abort_unless with $user->can() in all controllers
- [ ] Fix CreateDormitoryPermitRequest::authorize() to use policy
- [ ] Wire DormitoryPermissionProvider into application boot flow

## Validation
- [ ] Create 12 FormRequest classes (one per controller action pattern)
- [ ] Replace all inline $request->validate() with FormRequest injection
- [ ] Convert string enums to PHP 8.1+ backed enums

## Transaction Safety
- [ ] Wrap Resident::store in DB::transaction()
- [ ] Wrap RoomMove::approve in DB::transaction()
- [ ] Wrap Attendance::store batch in DB::transaction()
- [ ] Wrap Permit::store in DB::transaction()
- [ ] Add room capacity constraint at DB level (trigger or application enforcement)

## Notification
- [ ] Replace direct DormitoryService::notify() calls with DomainEvents
- [ ] Add notification on resident check-in (guardian alert)
- [ ] Add notification on room assignment change
- [ ] Add notification on violation recording (ensure idempotent)

## Testing
- [ ] Create DormitoryResidentFeatureTest (store, show, checkout)
- [ ] Create DormitoryPermitFeatureTest (store, approve, reject, return)
- [ ] Create DormitoryAttendanceFeatureTest (batch store, verify)
- [ ] Create DormitoryRoomMoveFeatureTest (store, approve, reject)
- [ ] Create DormitoryPermissionTest (policy gate coverage)
- [ ] Achieve minimum 70% coverage on dormitory controllers

## Performance
- [ ] Add database indexes for query patterns
- [ ] Implement dormitory hierarchy cache
- [ ] Implement room availability cache
- [ ] Fix all N+1 queries in controller index/show methods
- [ ] Add query result caching for stats aggregations

## Indexes
- [ ] INDEX on dormitory_residents(student_id, academic_year_id, is_active)
- [ ] INDEX on dormitory_attendances(student_id, attendance_date, session)
- [ ] INDEX on dormitory_permits(student_id, status, departure_datetime)
- [ ] INDEX on dormitory_violations(student_id, violation_date)
- [ ] INDEX on dormitory_activity_logs(resident_id, activity_date)

## Events & Observers
- [ ] Create DormitoryResidentCheckedIn event + listener
- [ ] Create DormitoryResidentCheckedOut event + listener
- [ ] Create DormitoryPermitApproved event + listener
- [ ] Create DormitoryPermitOverdue event + listener
- [ ] Create DormitoryViolationRecorded event + listener
- [ ] Create DormitoryRoomAssigned event + listener
- [ ] Register DormitoryResidentObserver
- [ ] Register DormitoryPermitObserver

## Observers
- [ ] Create DormitoryResidentObserver (auto-update room occupancy)
- [ ] Create DormitoryPermitObserver (status change triggers)
- [ ] Create DormitoryAttendanceObserver (recap trigger on date boundary)
- [ ] Register observers in AuthServiceProvider or dedicated registrar

## Dashboard
- [ ] Extract stats calculation into DashboardStatsService
- [ ] Create real-time occupancy widget (cached)
- [ ] Create today's attendance widget (cached)
- [ ] Create active permits widget
- [ ] Create recent violations widget

## API
- [ ] Version mobile API under /api/mobile/v1/
- [ ] Create Mobile/DormitoryDashboardController
- [ ] Create Mobile/DormitoryAttendanceController
- [ ] Create Mobile/DormitoryPermitController (expand beyond store)
- [ ] Create Mobile/DormitoryViolationController
- [ ] Create Mobile/DormitoryVisitLogController
- [ ] Create Mobile/DormitoryResidentController (status view)

## Export
- [ ] Create DormitoryResidentExport (Maatwebsite ShouldExport)
- [ ] Create DormitoryAttendanceExport
- [ ] Create DormitoryPermitExport
- [ ] Create DormitoryViolationExport
- [ ] Create DormitoryInventoryExport

## Import
- [ ] Create DormitoryResidentImport (bulk assign from CSV)
- [ ] Create DormitoryAttendanceImport (bulk import from existing system)
- [ ] Create DormitoryRoomImport (bulk room creation)

## Reports
- [ ] OccupancyReportService
- [ ] AttendanceReportService
- [ ] ViolationTrendService
- [ ] PermitHistoryService
- [ ] RoomMovementHistoryService

## Developer Experience
- [ ] Create IDE stubs / PHPDoc for all shared methods
- [ ] Document API endpoints (OpenAPI spec)
- [ ] Create local development setup guide for dormitory module
- [ ] Add PHP 8.1+ enums for all string enum fields
- [ ] Add Laravel Pint rules if not already configured for dormitory

---

# APPENDIX A: FILE INVENTORY

## Controllers (14)
1. `app/Http/Controllers/DormitoryMasterController.php`
2. `app/Http/Controllers/DormitoryController.php`
3. `app/Http/Controllers/DormitoryWingController.php`
4. `app/Http/Controllers/DormitoryRoomController.php`
5. `app/Http/Controllers/DormitoryResidentController.php`
6. `app/Http/Controllers/DormitoryPermitController.php`
7. `app/Http/Controllers/DormitoryAttendanceController.php`
8. `app/Http/Controllers/DormitoryViolationController.php`
9. `app/Http/Controllers/DormitoryVisitLogController.php`
10. `app/Http/Controllers/DormitoryPostController.php`
11. `app/Http/Controllers/DormitoryInventoryController.php`
12. `app/Http/Controllers/DormitoryRoomMoveController.php`
13. `app/Http/Controllers/DormitoryRoomApiController.php`
14. `app/Http/Controllers/Api/Mobile/V1/DormitoryPermitController.php`

## Models (16)
1. `app/Models/Dormitory.php`
2. `app/Models/DormitoryWing.php`
3. `app/Models/DormitoryRoom.php`
4. `app/Models/DormitoryResident.php`
5. `app/Models/DormitoryAttendance.php`
6. `app/Models/DormitoryAttendanceRecap.php`
7. `app/Models/DormitoryPermit.php`
8. `app/Models/DormitoryViolation.php`
9. `app/Models/DormitoryRoomMove.php`
10. `app/Models/DormitoryInventory.php`
11. `app/Models/DormitoryVisitLog.php`
12. `app/Models/DormitoryPost.php`
13. `app/Models/DormitoryPostResponse.php`
14. `app/Models/DormitoryActivityTemplate.php`
15. `app/Models/DormitoryActivityLog.php`
16. `app/Models/DormitoryEmergencyBroadcast.php`
(plus: `RoomSupervisor` if exists, verify)

## Services (1)
1. `app/Services/DormitoryService.php`

## Policies (1)
1. `app/Policies/DormitoryPolicy.php`

## Permission Providers (1)
1. `app/Authorization/Providers/DormitoryPermissionProvider.php`

## Form Requests (1)
1. `app/Http/Requests/Mobile/CreateDormitoryPermitRequest.php`

## Jobs (0 dormitory-specific)
—

## Events (0 dormitory-specific)
—

## Listeners (0 dormitory-specific)
—

## Observers (0 dormitory-specific)
—

## Console Commands (2)
1. `app/Console/Commands/ProcessOverduePermitsCommand.php` (NOT registered in scheduler)
2. `app/Console/Commands/DeleteSantriData.php` (references dormitory tables)

## Views (46 blade files)
See Phase 2, Section 3 for full listing.

## Migrations (17+)
See Phase 2, Section I for full listing.

## Tests (1)
1. `tests/Feature/WaliSantriApiTest.php` (1 dormitory-related test method)

---

# APPENDIX B: ENTITY RELATIONSHIP SUMMARY

```
Dormitory (1)
  ├── (N) DormitoryWing
  │       └── (N) DormitoryRoom
  │             ├── (N) DormitoryResident ─── (1) Student
  │             ├── (N) DormitoryAttendance
  │             ├── (N) DormitoryPermit
  │             ├── (N) DormitoryViolation
  │             ├── (N) DormitoryInventory
  │             ├── (N) DormitoryVisitLog
  │             ├── (N) RoomSupervisor
  │             └── (N) DormitoryRoomMove (from_room)
  │                     └── (N) DormitoryRoom (to_room)
  ├── (N) DormitoryPost ─── (N) Student (via pivot)
  ├── (N) DormitoryActivityTemplate
  ├── (N) DormitoryEmergencyBroadcast
  └── (N) DormitoryPostResponse (via DormitoryPost)

DormitoryResident (1)
  ├── (N) DormitoryActivityLog
  └── (1) DormitoryRoom

Every entity above has (N) AcademicYear linkage.
```

---

# APPENDIX C: KEY METRICS

| Metric | Value |
|--------|-------|
| Total Tables | 17 dormitory-specific + 4 asset room tables (separate context) |
| Total Controllers | 14 |
| Total Models | 16 |
| Total Services | 1 |
| Total Policies | 1 |
| Total FormRequests | 1 (mobile only) |
| Total Events | 0 |
| Total Listeners | 0 |
| Total Jobs | 0 |
| Total Observers | 0 |
| Total Console Commands | 2 (1 unregistered) |
| Total Views | 46 |
| Total Routes | ~80+ dormitory-specific routes |
| Total Tests | 1 (covering ~1 action) |
| Test Coverage Estimate | <2% |
| Lines of Controller Code (estimated) | 2,000+ across 14 files |
| Duplicated Code Blocks (estimated) | 150+ |
| Missing Transactions | 5 critical operations |
| Missing Notifications | 6 critical lifecycle events |

---

*END OF ARCHITECTURE REVIEW*
*No code modifications made. No refactoring performed. Architecture blueprint only.*
