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

### Authorization Module (v1 — FREEZEN)
- Located in `app/Authorization/`. Snapshot-based permission system layered on top of Spatie Laravel Permission. **No architecture rework without explicit request.**
- Entry points: `app/Authorization/Services/AuthorizationManager.php` (central gate, `allows($user, $permission, $context)`), `app/Authorization/Repositories/EloquentSnapshotRepository.php`, `app/Authorization/Models/PermissionSnapshot.php`.
- Resolver chain: `SnapshotResolver` → `EffectivePermissionBuilder` → `PermissionMergeResolver` → `PermissionCacheManager` (per-org/per-period cache).
- Providers: `GtkPermissionProvider`, `StudentPermissionProvider`, registered through `AuthorizationGateRegistrar` (gates) + `AuthorizationServiceProvider` (binding).
- Helpers in `app/Authorization/helpers.php` — **use these in code, not `hasPermissionTo()`**:
  - `canPermission(string $permission): bool` — current auth user
  - `cannotPermission(string $permission): bool` — inverse
  - `canUserPermission(User $user, string $permission): bool`
  - `getUserPermissionBag(): PermissionBag` — full bag for UI menus
- Bound via `Authorization\ValueObjects\OrganizationContext` (DI in request lifecycle). Fails closed when context missing or user unauthenticated.
- Jobs: `BuildSnapshotJob`, rebuild via `SnapshotRebuildService`. Audit trail: `SnapshotAuditLog`, `RevokedPermission`.
- Deferred issues: AUTH-101..106 — all LOW priority, none security risks.

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
├── Authorization/       # Snapshot-based permission system (v1 FREEZEN — see Auth section above)
├── Console/Commands/    # Custom artisan commands
├── Domain/              # Domain logic (RulesEngine, Timeline, Types) — framework-agnostic
├── Events/              # Domain events (student/GTK lifecycle)
├── Http/Controllers/    # Controllers (namespaced: Akademik/, Sarpras/, SuperAdmin/, Personalia/, Api/, MasterData/)
├── Http/Middleware/     # Custom middleware stack
├── Jobs/                # Queued jobs (ShouldQueue)
├── Listeners/           # Event listeners (cascade chain)
├── Models/              # Eloquent models (all use UUIDs)
├── Observers/           # Model observers
├── Policies/            # Legacy Spatie authorization policies
├── Services/            # Business logic services (~26 services, namespaced subdirs)
├── Support/             # Cross-cutting helpers (ApiResponse, LifecycleMessage)
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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5.8
- laravel/framework (LARAVEL) - v11
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- laravel/scout (SCOUT) - v10
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v10
- laravel-echo (ECHO) - v2

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v11 rules ===

## Laravel 11

- Use the `search-docs` tool to get version-specific documentation.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel 11 file structure.
- This is **perfectly fine** and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the Laravel 11 structure unless the user explicitly requests it.

### Laravel 10 Structure
- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### New Artisan Commands
- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - `php artisan make:enum`
    - `php artisan make:class`
    - `php artisan make:interface`

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

## PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
</laravel-boost-guidelines>
