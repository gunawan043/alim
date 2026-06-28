# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

ALIM — Academic Learning & Information Management. A Laravel-based integrated education management system for Pondok pesantren (Islamic boarding schools), supporting dormitory, academic, GTK (staff), student, attendance, and daily activity modules. Production: `alim.sekolah.sch.id`

## Tech Stack

- **PHP 8.2+**, **Laravel 11** (but uses Laravel 10 file layout — `app/Http/Kernel.php` exists, do NOT migrate to Laravel 11 structure unless explicitly requested)
- **MySQL 8** (default connection, utf8mb4), SQLite/PostgreSQL/SQLSRV in config but unused
- **Blade** templates with **Bootstrap 5** (RTL-ready via rtlcss)
- **Vite 5** for frontend build; no SPA framework — pure Blade
- **Pusher** + Laravel Echo for realtime notifications
- **UUIDs** (`ramsey/uuid-doctrine`) for all primary keys
- **Spatie Laravel Permission ^6.24** for roles/permissions
- **Scout + Elasticsearch 9** for full-text search
- **Maatwebsite Excel ^3.1** for exports
- **Dompdf ^3.1** for PDF generation

## Key Architecture Patterns

### Routing
- Single massive `routes/web.php` (1,756 lines / 1,127 routes). All Blade-rendered admin panel.
- `routes/api.php` — lightweight public API + Sanctum-protected mobile endpoints under `mobile/v1/`.
- UUID route pattern applied globally via `Route::pattern('id', ...)`.
- Every route is prefixed with `{userId}` scope (user-scoped resources).

### Global Scopes / School Context
- `AppServiceProvider::boot()` attaches `school_context` global scopes to `Student`, `GradeLevel`, `StudyGroup` models.
- Automatically filters queries to the current user's school via `request()->attributes->get('schoolContextId')`.
- Opt out: `Model::withoutGlobalScope('school_context')`.
- In tests, ensure `schoolContextId` is set on the request, or models may return zero results.

### Event-Driven Cascades
The system is heavily event-driven. Lifecycle events trigger chained listeners:
- **Student**: Promotion/Graduation/Mutation-In/Mutation-Out/Exit-from-Rombel → each fires 4-5 listeners (status update, history close, academic provisioning, notification, audit logging).
- **GTK**: Teaching assignment changes → workload recalculation.
- **Academic**: `SubjectAssignedToStudyGroup` event → `ProvisionStudyGroupSubjectAcademicStructure` listener (implements `ShouldQueue`, queue: `academic-provision`) → `ProvisionStudyGroupSubjectAcademicStructureJob` → `StudyGroupSubjectProvisioner` (creates admin books, KKTP links, nilai placeholders).

See `app/Events/` (16 events), `app/Listeners/` (10 listeners), `app/Jobs/` (9 jobs), `app/Services/` (~26 services).

### Observers
- `StudyGroupObserver` and `DokumenIsoObserver` registered in `AppServiceProvider::boot()`.
- Observers fire events which cascade to listeners/jobs.

### Middleware
Custom middleware in `app/Http/Middleware/`:
- `SchoolContextMiddleware` — sets school scope
- `EnsureEmployeeAccess` — validates GTK existence
- `RoleMiddleware`, `RoleLevelMiddleware`, `EnsureRoleAccess`, `MinRoleLevel` — Spatie Permission extensions
- `CheckIpBlocked`, `Localization`, `VerifySecureToken`

### Views
- Layouts in `resources/views/layouts/`.
- Feature view directories named in Indonesian/Bahasa (e.g. `akademik/`, `dormitory/`, `gtk/`, `sarpras/`, `personalia/`, `evalusi/`).
- `Blade::if('isActiveRoute', ...)` custom conditional registered in `AppServiceProvider`.

### Auth
- **Web**: Session-based via `User` model. Guards through custom middleware stack.
- **API**: Laravel Sanctum for mobile apps.

### Console Commands
Commands in `app/Console/Commands/`. Notable: `BackfillAcademicCascade`, `CreateRombel`, `RecalculateGtkWorkload`, etc.

### Database
- 282+ migration files, versioned `YYYY_MM_DD_HHMMSS_name`.
- 31 seeders (roles, permissions, system settings).
- Stored procedures in production (`activate_academic_year`, `generate_teaching_decree`, `get_gtk_report`).
- Regional data: `indonesia_*` tables (province/city/district/village).
- Full schema reference: `zdata.txt` (consult before schema changes).

## Common Commands

### Development Server
```bash
php artisan serve
```

### Testing
```bash
php artisan test                          # All tests (Unit + Feature)
php artisan test --compact                  # Compact output
php artisan test --testsuite=Unit           # Unit tests only
php artisan test --testsuite=Feature        # Feature tests only
php artisan test tests/Feature/FooTest.php  # Single file
php artisan test --filter=test_name         # Single test method
```

Notes:
- Tests use **MySQL** (not SQLite — the SQLite in-memory option in `phpunit.xml` is commented out).
- BCRYPT rounds = 4, mail/cache/session = `array`, queue = `sync`.
- PHPUnit 10 is used (not Pest).

### Code Style
```bash
php artisan pint  # Laravel Pint (PSR-12, always use curly braces for control structures)
```
Run `vendor/bin/pint --dirty --format agent` before finalizing changes.

### Frontend
```bash
npm run dev      # Watch mode
npm run build    # Production build
npm run clean    # Remove compiled assets
npm run build-rtl # Process RTL CSS
```

If frontend changes don't appear in the UI, ask the user to run `npm run build` or `npm run dev`.

### Database
```bash
php artisan migrate                 # Run migrations
php artisan migrate:status          # Check pending/ran
php artisan db:seed                 # Seed initial data
```

Use Boost MCP `database-query` tool for read-only queries. Use `tinker` for PHP debugging/model inspection.

### Queues
```bash
php artisan queue:work              # Start queue worker
php artisan queue:restart           # Graceful worker restart (after deploy)
```

## File Structure Highlights

```
app/
├── Console/Commands/    # Custom artisan commands
├── Events/              # Domain events (student/GTK lifecycle)
├── Http/Controllers/    # Controllers (namespaced: Akademik/, Sarpras/, SuperAdmin/, Personalia/, Api/, MasterData/)
├── Http/Middleware/     # Custom middleware stack
├── Jobs/                # Queued jobs (ShouldQueue)
├── Listeners/           # Event listeners (cascade chain)
├── Models/              # Eloquent models (all use UUIDs)
├── Observers/           # Model observers
��── Policies/            # Authorization policies
├── Services/            # Business logic services
├── Traits/              # Shared traits (HasUuid, Encryptable)
└── View/Composers/      # View composers (sidebar)

database/
├── factories/           # Model factories
├── migrations/          # 282+ migration files
└── seeders/             # 31 seeders

routes/
├── web.php              # Main app routes (1,756 lines)
└── api.php              # Public + Sanctum-protected API

resources/
├── js/                  # Frontend assets
├── sass/                # SCSS source
└── views/               # Blade templates
```

## Important Notes

- `.env` is copied from `.env.backup` (not `.env.example`) — production credentials are backed up.
- `phpunit.xml` has SQLite in-memory commented out — tests expect MySQL.
- All models use UUID primary keys (char(36)).
- `Student::find(...)` may return unexpected results if school scope isn't set up in tests.
- Views/routes/controllers use Bahasa Indonesia naming conventions.
- Prefer `search-docs` MCP tool for Laravel ecosystem documentation before other approaches.
- Prefer `database-query` for read-only DB access; `tinker` for PHP debugging.
- Use `php artisan make:` commands to create new files.
- Always use explicit return type declarations and PHP 8 constructor property promotion.
- PHPDoc blocks preferred over inline comments. Never use inline comments within code unless something is very complex.
