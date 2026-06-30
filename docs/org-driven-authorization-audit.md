---
name: org-driven-authorization-architecture-audit
description: Complete audit report of current auth system and recommended HR-driven role sync architecture for ALIM
metadata:
  type: project
---

# Organization-Driven Authorization Architecture — Audit Report

> Date: 2026-06-29

## A. Current Authorization Architecture

### 1. User Model
- **Table**: `users` (UUID PK)
- **Trait**: `Spatie\Permission\Traits\HasRoles` (`User.php:17`)
- **Relationships**:
  - `gtkProfile()` → `hasOne(GtkProfile::class)` — personal data
  - `employment()` → `hasOne(GtkEmployment::class)` — **primary HR position record**
  - `employments()` → `hasMany(GtkEmployment::class)` — history
  - `gtkWorkUnits()` → `hasMany(GtkWorkUnit::class)` — organizational placement
  - `additionalTasks()` → `hasMany(GtkAdditionalTask::class)` — extra duties
  - `careerPaths()` → `hasMany(GtkCareerPath::class)` — position history
  - `competencies()` → `hasMany(GtkCompetency::class)` — competency levels
  - `transferRequests()` → `hasMany(GtkTransferRequest::class)` — pending transfers
  - `workUnitHistories()` → `hasMany(GtkWorkUnitHistory::class)` — movement log
  - `pension()` → `hasOne(GtkPension::class)` — retirement

### 2. Spatie Permission Setup
- **Packages**: `spatie/laravel-permission ^6.x`
- **Guard**: `web` only
- **Custom Pivot Model**: `ModelHasRoles` (UUID PK on `model_has_roles`, `app/Models/ModelHasRoles.php`)
- **Custom Role Model**: `App\Models\Role`
- **Custom Permission Model**: `App\Models\Permission`
- **Migration**: `2026_02_10_010757_create_permission_tables.php`

### 3. Roles Table (RoleSeeder.php)
26 predefined roles with hierarchical levels:

| Level | Role | Derived From |
|-------|------|-------------|
| 1 | Super Admin | System-wide, manual |
| 2 | Mudir | Yayasan leadership |
| 3 | Wadir 1 | Directorate |
| 4 | Wadir 2 | Directorate |
| 5 | Personalia | HR department |
| 6 | Administrator | IT/Admin |
| 7 | Kepala Sekolah | School principal |
| 8 | Wakil Kepala Sekolah | Vice principal |
| 9 | Admin Tata Usaha | Admin finance staff |
| 10 | Tata Usaha | Finance staff (read-only) |
| 11-23 | Various teachers, dept heads, asrama, keuangan, sarpras | Positions |
| 24 | Wali Santri | Parents (non-employee) |
| 25 | GTK | Generic staff/teacher |

**Key observation**: Roles have a `level` column. The system uses **level-based hierarchy** (`MinRoleLevel` middleware compares level values).

### 4. Permissions
Managed via `PermissionRoleSeeder.php` — hardcoded role→permission mappings. Super Admin gets all permissions. GTK gets only `profile_view`, `profile_edit`, `password_change`.

### 5. Access Control Mechanisms
Three co-existing systems:

1. **Spatie Roles** — `hasRole()`, `hasAnyRole()`, `hasRoleNames()`, `assignRole()`, `syncRoles()`, `roles()->detach()`
2. **Role Level** — `MinRoleLevel` middleware compares `$user->roles()->min('level')` against a threshold
3. **Hardcoded `hasRole()` in controllers** — Massive repetition of role checks directly in controllers (AbsensiHarianController alone has 40+ `hasRole()` calls)

### 6. Middleware Stack
- `EnsureEmployeeAccess` — Entry-point validator (handles applicant, wali santri, no-role users)
- `RoleMiddleware` — Role-based route protection
- `MinRoleLevel` — Level-based route protection (level must be ≤ required)
- `RoleEnforced` — Secure token-based role enforcement

### 7. View Layer
- `SidebarComposer` — Builds menu based on user roles (`$user->roles->pluck('id')`)
- Blade directive: `@isActiveRoute` for active menu highlighting

### 8. Policy Layer
- `GtkProfilePolicy::can()` — Compares `$user->roles()->min('level')` against GTK profile hierarchy

---

## B. GTK Relationship Analysis (Organizational Position Tables)

### Core position-defining tables:

| Table | Model | Role Impact |
|-------|-------|------------|
| `gtk_employments` | GtkEmployment | **Primary**: jabatan, status_kepegawaian, satuan_kerja (via study_group), academic_year scope |
| `gtk_work_unit` | GtkWorkUnit | **Primary**: work unit assignment, jabatan (text), is_primary flag |
| `gtk_additional_tasks` | GtkAdditionalTask | Extra duties: decree-linked, hours_per_week, tmt/tst validity |
| `gtk_career_paths` | GtkCareerPath | Functional position: jabatan_fungsi, decree-linked, tmt/tst validity |
| `teaching_assignments` | TeachingAssignment | Academic role: subject+study_group+teacher, weekly_hours, role (Wali Kelas/Koordinator), status |
| `gtk_transfer_requests` | GtkTransferRequest | Pending/processed transfers between work units |
| `gtk_work_unit_histories` | GtkWorkUnitHistory | Historical log of assignments/transfers/removals |
| `jenis_gtk` / `jabatan` | JenisGtk, Jabatan | Reference data: Jabatan belongs to JenisGtk (e.g., "Guru" → "Guru Matematika") |

### Table relationships to User:
```
User ─1:1─> GtkProfile (personal identity)
User ─1:1─> GtkEmployment (current HR position — PRIMARY SOURCE)
User ─1:N─> GtkWorkUnit (organizational placement — can have multiple)
User ─1:N─> GtkAdditionalTask (extra duties — can have multiple, active/inactive)
User ─1:N─> GtkCareerPath (career position history — can be multiple, active/inactive)
User ─1:N─> TeachingAssignment (teaching role — can have multiple, active)
User ─1:N─> GtkCompetency (skills — does NOT affect role)
User ─1:1─> GtkContact (contact — does NOT affect role)
User ─1:N─> GtkEducation (education — does NOT affect role)
```

### Key insight: No single authoritative position table.
Position authority is distributed across `GtkEmployment`, `GtkWorkUnit`, `GtkAdditionalTask`, `GtkCareerPath`, and `TeachingAssignment`. **No automatic sync mechanism exists today** — roles are manually assigned.

---

## C. Synchronization Gaps

### Gap 1: Manual `assignRole()` in Controllers
**Locations**:
- `GtkWizardController@store:307` — `$user->assignRole('GTK')`
- `GtkWizardController@import:1628` — `$user->assignRole('gtk')`
- `PersonaliaController@create:62` — `$user->assignRole('Guru Umum')`
- `CandidateController@convert:300` — `$user->assignRole('candidate')`
- `SchoolSeeder:47` — `$user->syncRoles(['GTK'])`
- `SuperAdmin\UserController@store:67` — `$user->syncRoles($validated['roles'])`
- `SuperAdmin\UserController@update:110` — `$user->syncRoles(...)`
- `SuperAdmin\UserController#assignRoles:157` — `$user->syncRoles(...)`
- `PersonaliaController#changeRole:190` — `$user->syncRoles([$validated['role']])`

**Risk**: Every GTK creation/import assigns a default/static role. No event fired. Changing a GTK's position later will NOT update their role.

### Gap 2: Direct `roles()->detach()` on Delete
**Locations**:
- `GtkController@destroy:360` — `$user->roles()->detach()`
- `GtkController#bulkDelete:423` — `$user->roles()->detach()`

**Risk**: Manual detachment without audit or event. No `UserDeleted` or `GtkRemoved` event dispatched.

### Gap 3: Controller Hardcoded Role Checks
`AbsensiHarianController` has 40+ `hasRole()` calls repeated inline. `NilaiKelasController`, `NilaiGuruController`, `GtkAdditionalTaskController`, `DormitoryMasterController` all have hardcoded checks.

**Risk**: Cannot refactor authorization; adding new roles requires editing dozens of files.

### Gap 4: No Events for GTK Position Changes
Currently:
- `GtkProfileObserver` only fires `GtkProfileUpdated` (created/deleted only, not updated)
- `TeachingAssignmentObserver` fires `TeachingAssignmentChanged` → `TriggerGtkWorkloadRecalculation` (workload only, not auth)
- `StudyGroupSubjectObserver` fires `SubjectAssignedToStudyGroup` → `ProvisionStudyGroupSubjectAcademicStructure` (academic provisioning only)
- `StudentObserver` fires student lifecycle events

**Missing**: No event when GtkEmployment changes, when GtkWorkUnit changes, when AdditionalTask is assigned/removed, when TransferRequest is approved, when TeachingAssignment is assigned.

### Gap 5: `EnsureEmployeeAccess` is Reactive, Not Proactive
`EnsureEmployeeAccess.php:61` checks `$this->hasValidEmployeeRole()` and **redirects** to a validator page. This means:
- A GTK promoted to Wadir 1 still sees GTK menus until an admin manually updates their role
- A resigned GTK retains access until manual cleanup

**Risk**: Authorization is always lagging behind organizational reality.

### Gap 6: Role Mapping Not Centralized
26 roles in `RoleSeeder` with no documented mapping from organizational position → role.
A person changing positions must be found manually and their role updated through various paths:
- `PersonaliaController#changeRole` — direct form
- `SuperAdmin\UserController#assignRoles` — quick AJAX
- Manual database edits in production

### Gap 7: Duplicate/Case-Insensitive Role Names
`RoleSeeder` uses `'GTK'` but `assignRole('gtk')` in `GtkWizardController@import:1628` uses lowercase. Spatie treats these as different strings.

### Gap 8: No Idempotency Guarantees
No mechanism to detect if a role is "consistent" with the user's position. Running sync multiple times produces inconsistent results because no baseline exists.

---

## D. Recommended Architecture

### Design Principles
1. **HR data is the single source of truth** — Spatie roles are purely a derived artifact
2. **Event-driven sync** — Zero role logic in controllers
3. **Centralized role mapping** — One place defines position → role
4. **Idempotent** — Running sync N times produces identical state
5. **Override-proof** — Manual role edits are detected and overwritten on next sync

### Architecture Flow

```
┌──────────────────────────────────────────────────────────────────────┐
│                     ORGANIZATIONAL POSITION CHANGE                   │
│                                                                      │
│  GtkEmployment updated                                               │
│  GtkWorkUnit updated                                                 │
│  GtkAdditionalTask assigned/removed                                  │
│  TeachingAssignment assigned                                         │
���  GtkTransferRequest approved                                         │
│  User status changed (is_active)                                     │
│                                                                      │
└──────────┬───────────────────────────────────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────────────────┐
│                         EVENT FIRED                                  │
│                                                                      │
│  PositionChanged(string $userId, array $changes, string $source)     │
│                                                                      │
│  Dispatched by Observers                                             │
│  Idempotent: includes user_id hash of position state                  │
└──────────┬────────────────────────────────────────────��──────────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────────────────┐
│                       LISTENER                                       │
│                                                                      │
│  SyncAuthorizationListener                                           │
│  — handles: ShouldQueue                                              │
│  — calls: RoleSynchronizationService::sync($userId)                   │
└──────────┬───────────────────────────────────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────────────────┐
│                   ROLE SYNCHRONIZATION SERVICE                        │
│                                                                      │
│  RoleSynchronizationService                                          │
│  ──────────────────────────────                                      │
│  1. resolveOrganizationalPosition($user)                              │
│     • Collects: GtkEmployment (primary)                              │
│     • Collects: GtkWorkUnit (primary=TRUE)                           │
│     • Collects: GtkAdditionalTask (active, no expiry)                 │
│     • Collects: GtkCareerPath (active, no expiry)                     │
│     • Collects: TeachingAssignment (status=ACTIVE)                    │
│     • Resolves highest-priority position                             │
│                                                                      │
│  2. mapToRole($position)                                              │
│     • Uses central PositionRoleMap configuration                     │
│     • Returns: RoleName or null                                      │
│                                                                      │
│  3. computeDesiredState($user)                                         │
│     • Determines: expected role                                      │
│     • Determines: expected permissions (derived from role)           │
│                                                                      │
│  4. reconcile($user, $desired)                                        │
│     • Compare current roles vs desired                               │
│     • If mismatch: syncRoles(), refresh permission cache             │
│     • If match: skip (idempotent)                                    │
│     • Log: RoleSynchronizationLog entry                              │
│                                                                      │
│  5. handleSpecialCases($user, $position)                              │
│     • is_active=false → detach all roles + enforce blocking          │
│     • status_kepegawaian in [PTT,GTT,Magang] → role=GTK             │
│     • status_kepegawaian in [PTY,GTY,KONTRAK] → elevated role        │
│     • Jabatan = "Wakil Direktur" → Wadir 1/2                        │
│     • Jabatan = "Mudir" → Mudir                                      │
└──────────┬───────────────────────────────────────────────────────────┘
           │
           ▼
┌──────────────────────────────────────────────────────────────────────┐
│                      SYSTEM EFFECTS                                  │
│                                                                      │
│  • Spatie roles updated via syncRoles()                              │
│  • Permission cache cleared                                          │
│  • Sidebar menu auto-refreshes on next request (SidebarComposer)     │
│  • Middleware gates auto-apply (RoleMiddleware, MinRoleLevel)        │
│  • Audit log: ROLE_SYNCHRONIZED event                                │
│  • Stale access revoked immediately                                  │
└──────────────────────────────────────────────────────────────────────┘
```

### Role Mapping Configuration (`config/position-role-map.php`)
Centralized mapping driving all role assignments:

```php
return [
    // Priority-ordered rules: first match wins
    'rules' => [
        // Yayasan leadership
        ['condition' => 'jabatan', 'value' => 'Mudir',                    'role' => 'Mudir'],
        ['condition' => 'jabatan', 'value' => 'Wakil Direktur 1',         'role' => 'Wadir 1'],
        ['condition' => 'jabatan', 'value' => 'Wakil Direktur 2',         'role' => 'Wadir 2'],
        
        // School leadership
        ['condition' => 'jabatan', 'value' => 'Kepala Sekolah',           'role' => 'Kepala Sekolah'],
        ['condition' => 'jabatan', 'value' => 'Wakil Kepala Sekolah',     'role' => 'Wakil Kepala Sekolah'],
        
        // Administration
        ['condition' => 'jabatan', 'value' => 'Admin Tata Usaha',         'role' => 'Admin Tata Usaha'],
        ['condition' => 'jabatan', 'value' => 'Tata Usaha',               'role' => 'Tata Usaha'],
        ['condition' => 'jabatan', 'value' => 'Keuangan',                 'role' => 'Keuangan'],
        
        // Department heads
        ['condition' => 'is_head_of_department', 'value' => true,          'role' => 'Coordinator Guru'],
        
        // Teaching roles (resolved from teaching_assignments)
        ['condition' => 'teaching_assignment.coordinator', 'value' => true, 'role' => 'Coordinator Guru'],
        
        // Work unit based
        ['condition' => 'work_unit.type', 'value' => 'Asrama',             'role' => 'Asrama'],
        ['condition' => 'work_unit.type', 'value' => 'Sarpras',            'role' => 'Sarpras'],
        
        // Default for all GTK
        ['condition' => 'has_employment', 'value' => true,                 'role' => 'GTK'],
        
        // Special: inactive/resigned
        ['condition' => 'employment_status', 'value' => 'resigned',        'role' => null], // no role
    ],
    
    // Priority weight: higher wins when multiple positions exist
    'resolution_strategy' => 'highest_priority', // or 'first_match'
];
```

### Event-Observer Wiring

| Observer | Fires Event | Triggered By |
|----------|------------|--------------|
| `GtkEmploymentObserver` | `PositionChanged` | create/update/delete |
| `GtkWorkUnitObserver` | `PositionChanged` | create/update/delete |
| `GtkAdditionalTaskObserver` | `PositionChanged` | create/update/delete (tmt/tst boundary) |
| `GtkCareerPathObserver` | `PositionChanged` | create/update/delete (tmt/tst boundary) |
| `TeachingAssignmentObserver` | `PositionChanged` | create/update/delete (NEW: extend existing) |
| `GtkTransferRequestObserver` | `PositionChanged` | status → APPROVED |
| `UserObserver` | `PositionChanged` | is_active toggled |

### New Models

#### RoleSynchronizationLog
Tracks every sync for audit/debugging:
```php
// table: role_synchronization_logs
$user_id, previous_role, new_role, trigger_event, trigger_source, 
position_hash, resolved_jabatan, status, details (JSON), created_at
```

### Guard Rails

1. **`BlockManualRoleEdit` middleware** — Detects any direct `assignRole()`/`syncRoles()` call from web requests (not from the sync service) and logs a warning
2. **RoleDriftDetector** console command — Periodically compares organizational state vs actual role state; alerts on mismatch
3. **`$skipRoleSync` flag** on User model — Allows seeders/batch imports to bypass the guard temporarily

---

## E. Implementation Plan

### Phase 1: Foundation (1-2 days)
**Goal**: Establish the central role mapping and synchronization service.

1. Create `config/position-role-map.php` — centralized role-position rules
2. Create `app/Services/RoleSynchronizationService.php` — core sync logic
3. Create `app/Models/RoleSynchronizationLog.php` — audit trail
4. Create migration for `role_synchronization_logs` table
5. Add `resolvePreferredRole()` method that reads HR data and returns role name

**No behavior changes yet. Pure infrastructure.**

### Phase 2: Event Infrastructure (2-3 days)
**Goal**: Wire observers to fire events for every position-changing action.

1. Create `app/Events/PositionChanged.php` — carries user_id, changes array, source
2. Create `app/Listeners/SyncAuthorizationListener.php` — queued listener calling the service
3. Create `GtkEmploymentObserver` — fires event on create/update/delete
4. Create `GtkWorkUnitObserver` — fires event on create/update/delete
5. Create `GtkAdditionalTaskObserver` — fires event when tasks become active/expired
6. Create `GtkCareerPathObserver` — fires event when careers become active/expired
7. Create `TeachingAssignmentObserver` — extend existing to ALSO fire PositionChanged
8. Create `GtkTransferRequestObserver` — fires event on APPROVED status
9. Register all observers in `AppServiceProvider::boot()`

**Verify**: Every GTK position change route/observer/command fires the event.

### Phase 3: Gate Manual Edits (1 day)
**Goal**: Prevent controllers from bypassing the sync mechanism.

1. Create `app/Http/Middleware/BlockManualRoleEdit.php` — logs warnings for direct role edits
2. Create `$skipRoleSync` static flag on User model — controlled by env var `ALLOW_MANUAL_ROLES=false`
3. Wrap `PermissionRoleSeeder` to respect the flag
4. Add `php artisan role-sync:drift` command — detects mismatches

### Phase 4: Controller Cleanup (3-5 days)
**Goal**: Remove hardcoded `hasRole()` from controllers and replace with permissions.

1. Audit all controllers for `hasRole()` calls — build inventory list
2. Convert each hardcoded check to `can('permission_name')` pattern
3. Update `PermissionRoleSeeder` to ensure all converted permissions are assigned to correct roles
4. Remove `hasRole()` checks from controllers one module at a time
5. Verify each module with `php artisan test`

### Phase 5: Activation & Hardening (2-3 days)
**Goal**: Enable auto-sync and disable manual role management.

1. Update `EnsureEmployeeAccess` to work with new flow
2. Enable `BlockManualRoleEdit` globally
3. Set `ALLOW_MANUAL_ROLES=false` in production config
4. Fix `GtkWizardController@store` and `@import` to use service instead of direct `assignRole()`
5. Fix `PersonaliaController@create` similarly
6. Fix `SuperAdmin\UserController` — replace manual role editing with a "Request Role Change" workflow
7. Final test run of entire GTK lifecycle

### Phase 6: Monitoring & Validation (1-2 days)
**Goal**: Ensure ongoing correctness.

1. Create `php artisan role-sync:verify` — dry-run diagnostic command
2. Add CI step: `role-sync:drift` must pass before deploy
3. Document the new architecture in `docs/auth-architecture.md`
4. Train admins: roles are no longer manually managed

---

## Summary of Key Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Role assignment mechanism | Event-driven service | Decouples from controllers |
| Position source | GtkEmployment (primary) + supporting tables | Already exists, single source of truth |
| Role mapping | Central config file | Easy to add/change roles without code |
| Idempotency | Position hash comparison | Detects if state actually changed |
| Safety rail | BlockManualRoleEdit middleware | Catches bypass attempts immediately |
| Migration path | Phase 4 last | Permissions evolve naturally during controller cleanup |
| Reuse existing | Yes — Spatie Permission unchanged | No reason to rebuild |
| Observer strategy | Extend + create | Extends existing TeachingAssignmentObserver, creates 5 new ones |
