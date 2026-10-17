# Naming Convention — ALIM Project

> Canonical reference. All code must follow these rules. Deviations must be fixed immediately or documented as exceptions in `long-term-memory.md`.

---

## Single Source of Truth

| Context | Convention | Example |
|---|---|---|
| **Route URL** | kebab-case, plural nouns | `/academic-years`, `/study-groups` |
| **Route name** | dot.case, verb.action | `academic-years.index`, `students.store` |
| **Table** | plural snake_case | `students`, `academic_years` |
| **Column** | singular snake_case | `student_id`, `created_at` |
| **Pivot table** | singular both sides, underscore-joined | `gtk_work_unit` → **`gtk_work_units`** *(pending fix)* |
| **Foreign key** | `{referenced_table_singular}_id` | `student_id`, `school_id` |
| **Model class** | singular PascalCase | `Student`, `AcademicYear` |
| **Model instance** | camelCase | `$student`, `$academicYear` |
| **Controller** | PascalCase with `Controller` suffix | `StudentController` |
| **Variable / function** | camelCase | `$studentIds`, `getStudentData()` |
| **Scope method** | `scope` + PascalCase | `scopeActive($q)` |
| **Relationship method** | camelCase or snake_case | `school()`, `homeroomTeacher()` |
| **Middleware** | kebab-case | `role-access`, `verify-secure-token` |
| **Job/Event/Notification** | PascalCase | `SendWelcomeEmail` |
| **Seeder** | PascalCase with `Seeder` suffix | `StudentSeeder` |
| **Factory** | PascalCase with `Factory` suffix | `StudentFactory` |
| **Blade view** | kebab-case, dot-separated dir | `study-groups/index.blade.php` |
| **Config/ENV key** | SCREAMING_SNAKE_CASE | `DB_HOST`, `MAIL_MAILER` |
| **JavaScript variable** | camelCase | `studentData`, `academicYearsList` |
| **CSS class** | kebab-case | `.student-card`, `.form-label` |

---

## Rule Details

### Routes

```
# URL — always kebab-case, always plural resource nouns
GET  /academic-years          (NOT: /academicYear, /academic_year)
GET  /study-groups/UUID/edit (NOT: /studyGroups/UUID/edit)

# Route name — dot.verb format, route prefix matches URL prefix
academic-years.index
academic-years.create
academic-years.store
academic-years.show
academic-years.edit
academic-years.update
academic-years.destroy

# RESTful convention:
index   → list all
create  → show creation form
store   → persist new record
show    → display single record
edit    → show edit form
update  → persist edits
destroy → delete record
```

### Database Tables

```
✅ CORRECT
students              (plural, snake_case)
academic_years        (compound: both words plural)
gtk_work_units        (compound: both words plural)
study_groups          (compound: both words plural)
student_class_histories (compound pivot/conjunction)

❌ WRONG
student               (singular)
gtk_work_unit         (pivot should be plural — INCONSISTENCY)
talent_pool           (camelCase in table name — INCONSISTENCY)
succession_plan_kandidat (wrong plural on second word — INCONSISTENCY)
jabatan              (master data, singular — INTENTIONAL exception)
jenis_gtk            (master data, singular — INTENTIONAL exception)
```

> **Intentional exceptions**: `jabatan` and `jenis_gtk` store a fixed list of titles/GTK types. They are intentionally singular because they represent a vocabulary list, not a collection of events. Document exceptions in `long-term-memory.md`.

### Columns

```
✅ CORRECT
student_id, school_id, user_id, created_at, updated_at, deleted_at

❌ WRONG (mixed singular/plural in FK)
gtk_work_unit_id  (table is gtk_work_unit → column should be gtk_work_unit_id or work_unit_id)
```

### Foreign Keys

Foreign keys MUST follow `{referenced_table_singular}_id`:

```
students.school_id          → schools.id          ✅
study_groups.school_id      → schools.id          ✅
gtk_work_units.user_id      → users.id            ✅
gtk_work_units.work_unit_id → work_units.id        ✅
student_class_histories.student_id → students.id   ✅
```

If the referenced table name is compound, use the **singular form of the last word**:

```
academic_years.school_id?     → wrong if school is NOT referenced
study_groups.grade_level_id  → grades.id → grade_levels.id → grade_level_id ✅
```

### Models

```php
// ✅ CORRECT
class Student extends Model {}
class AcademicYear extends Model {}
class GtkWorkUnit extends Model {}
class StudyGroup extends Model {}

// ❌ WRONG
class StudentRecord extends Model {}   // "Record" is redundant
class SchoolYear extends Model {}     // Should be AcademicYear
class GTKProfile extends Model {}     // All-caps abbreviation
```

### Variables & Functions

```php
// ✅ CORRECT
$student = Student::find($id);
$studentIds = $students->pluck('id');
$academicYearName = $academicYear->name;
$isActiveFlag = true;
function getStudentData() {}
public function scopeActive($q) {}

// ❌ WRONG
$Student = Student::find($id);      // Capital variable for instance
$student_data = Student::find($id);   // snake_case variable
$std = Student::find($id);           // abbreviated
function get_student_data() {}       // snake_case function
```

### Relationship Naming

```php
// ✅ CORRECT — singular for belongsTo, plural for hasMany
public function school(): BelongsTo { return $this->belongsTo(School::class); }
public function students(): HasMany { return $this->hasMany(Student::class); }
public function classHistories(): HasMany { return $this->hasMany(StudentClassHistory::class); }

// ❌ WRONG
public function schools(): BelongsTo { ... }  // BelongsTo is always singular
public function student(): HasMany { ... }     // HasMany should be plural
```

### Scope Methods

```php
// ✅ CORRECT
public function scopeActive($q) { return $q->where('is_active', true); }
public function scopeBySchool($q, $schoolId) { return $q->where('school_id', $schoolId); }

// ❌ WRONG
public function scope_active($q) {}  // camelCase required
public function ActiveScope($q) {}  // PascalCase without "scope" prefix
```

---

## Currently Known Inconsistencies (pending fix)

> Auto-generated by naming-consistency-enforcer. See `long-term-memory.md → naming_issues` for full list with timestamps.

| ID | Location | Old | New | Reason |
|---|---|---|---|---|
| TBL-001 | `migrations/` + `app/Models/` | `gtk_work_unit` | `gtk_work_units` | Pivot table must be plural per Laravel convention |
| TBL-002 | `migrations/` + `app/Models/` | `talent_pool` | `talent_pools` | Table name must be plural |
| TBL-003 | `migrations/` + `app/Models/` | `succession_plan_kandidat` | `succession_plan_kandidats` | Last word must be plural |
| RT-001 | `routes/web.php` | `gtk-requests` (route prefix) | `gtk_requests` | Route prefix should match kebab-case URL standard (pending decide) |

---

## Enforcement

- **Linter**: Runs on every file save via `/skills/naming-linter/pre-commit.md`
- **Full scan**: Nightly via `/skills/naming-consistency-enforcer/scan-and-fix.md`
- **Code review**: Naming violations are **blocking** — PRs with inconsistencies must be fixed before merge
- **Discovery → Fix**: Use the `naming-consistency-enforcer` skill to scan and auto-fix

---

## Exception Process

To mark a naming choice as an **intentional exception**:

1. Add a comment in the code: `// naming-convention: intentional-exception — reason`
2. Document it in `long-term-memory.md → naming_issues` as `STATUS: intentional-exception`
3. Exceptions must be reviewed annually

---

## Anti-Patterns to Never Introduce

```php
// ❌ Never use reserved SQL words as column names
SELECT order, user, date FROM ... // "order" and "date" are reserved

// ❌ Never use camelCase in database identifiers
Table: "StudentRecords"   // must be: student_records
Column: "firstName"       // must be: first_name

// ❌ Never use kebab-case in PHP/Laravel code
$variable-name = 'value';  // must be: $variableName

// ❌ Never use PascalCase in route URLs
GET /Students/Edit/UUID    // must be: /students/UUID/edit

// ❌ Never use numbers at the start of names
Table: "2nd_batch_orders"  // must be: second_batch_orders

// ❌ Never use special characters in names
Column: "harga(s)/unit"    // must be: harga_per_unit
```
