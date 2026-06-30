# ADR-017: Write-Time Authorization Guard

| Field        | Value                  |
|--------------|------------------------|
| Revision     | 1 (Architecture Board) |
| Status       | Accepted               |
| Date         | 2026-06-29             |
| Related ADRs | ADR-016                |

## Decision

The Write-Time Authorization Guard is a custom CI-only static analysis tool implemented as a standalone PHP script that inspects the AST of every modified PHP file against a set of forbidden patterns and repository-specific allow-list rules.

The tool runs exclusively in CI pipelines and pre-commit hooks. It has no runtime presence.

## AST-Based Detection

The guard uses PHP-Parser to parse modified files into PHP ASTs. Forbidden patterns are matched as AST node types, not as regex strings.

### Why AST?

- **Eliminates false positives**: regex patterns on PHP source match strings inside comments, string literals, or unrelated variable names. AST analysis only inspects actual code structure.
- **Traverses inheritance**: regex cannot follow a method call to its definition; AST traversal resolves method calls to their declared class.
- **Survives code formatting**: PHP-CS-Fixer reformatting does not bypass AST inspection.

## Forbidden Patterns

The following patterns are forbidden in the `app/` tree outside of the explicitly allowed locations:

| ID | Pattern | Reason |
|----|---------|--------|
| W-01 | `DB::table()->insert(...)` for `permission_*` and `role_*` tables | Bypasses the snapshot builder |
| W-02 | Direct calls to `assignRole()` or `removeRole()` | Bypasses Spatie RoleSynchronizationService |
| W-03 | Modifying `Gate::define()` outside of `app/Authorization/` | Bypasses authorization layer |
| W-04 | Direct calls to `Spatie\Permission\Models\Permission::create()` | Bypasses PermissionRegistry |
| W-05 | Direct mutations of the `permissions` or `roles` JSONB column without snapshot rebuild | Bypasses snapshot immutability |

## Allow Markers

Approved exceptions are recorded via inline comments:

```php
// AUTH-GUARD: ALLOW <ID> <reason>
```

### Marker Format

- `ID` references one of the forbidden pattern IDs.
- `reason` is a free-text justification recorded in the audit log.

### Marker Placement Rules

- Marker must appear on the line immediately preceding the allowed operation.
- Each marker is consumed once per file per version.
- Marker audit logs are emitted to a separate tracking table.

### Why Not PHP Attributes?

PHP attributes (`#[AuthGuardAllow]`) require PHP 8.0+ and class metadata infrastructure. Inline comments are universally supported and survive refactoring tools that may strip attributes.

### Why Not Config File?

A config file (`config/auth-guard.php`) with allow rules would centralize allow-list logic but require file edits for every exception. Inline markers localize exceptions to the code they affect, improving locality of reasoning.

### Why Not Annotations?

Annotations are DocBlock comments parsed by static analyzers. Inline markers are simpler, lower-overhead, and do not depend on DocBlock parsing libraries.

## Allow Marker Governance

There is no arbitrary numeric limit on AUTH-GUARD: ALLOW markers. Each marker must carry the following identifiers, validated by CI:

### Required Fields

Every `// AUTH-GUARD: ALLOW` marker line must contain:

1. **Pattern ID** — references the forbidden pattern (W-01 through W-05).
2. **Justification** — free-text rationale explaining why the pattern is allowed in this specific location.
3. **Issue reference** — issue tracker ID (e.g., `JIRA-123`) that approved the exception.
4. **ADR reference** — identifier of the ADR that recorded the approved exception.

### Marker Format

```php
// AUTH-GUARD: ALLOW <ID> <justification> | issue: ISSUE-ID | adr: ADR-NNN
```

Example:

```php
// AUTH-GUARD: ALLOW W-02 manual role sync in legacy seeder | issue: AUTH-145 | adr: ADR-021
```

### CI Validation

The guard's CI script parses each marker line. A marker is rejected if:

1. It does not contain all four required fields.
2. The issue reference format is invalid.
3. The ADR reference cannot be resolved to an existing ADR document.
4. The justification length is less than 12 characters.

### Governance Principle

If marker accumulation signals architectural debt, the response is to **change the guard rules** rather than to silently add more markers. Each marker is visible and discoverable through CI output, issue tracking, and ADR audit trail.

## Runtime Detection

Production runtime monitoring is **out of scope** for the Write-Time Authorization Guard. The guard operates statically.

### Why Not Runtime?

- Runtime guards impose a per-request overhead.
- Runtime guards cannot inspect code paths not exercised during a request.
- CI guards catch violations before merge, which is the correct point of intervention.

### Complementary Runtime Monitoring

Existing application logging captures all permission denials via Laravel Gate. This provides a defense-in-depth layer for runtime drift detection but does not replace the static guard.

## CI Pipeline Integration

- **Pre-commit hook**: Optional fast-fail for developer convenience.
- **PR CI**: Mandatory gate.
- **Main branch CI**: Mandatory gate.

### Exit Codes

- `0`: no violations.
- `1`: violations detected; build fails.

## File Scope

The guard inspects files under `app/` exclusively.

### Rationale

- `app/` contains all business logic.
- `database/migrations/` is excluded because migrations are schema operations, not runtime behavior.
- `tests/` is excluded to allow test fixtures to construct scenarios.
- `config/` is excluded because configuration values do not execute authorization logic.

## Cross-Language Drift Detection

A complementary weekly cron job (separate from this guard) compares runtime permission resolutions against the snapshot table to detect drift introduced by manual database interventions. This addresses the "manual SQL bypass" concern without expanding the scope of the Write-Time Authorization Guard.

## Error Handling & Fallback

The guard must behave deterministically under failure conditions. Failure modes and their handling are defined below.

### Parser Failure (Invalid PHP Syntax)

- **Symptom**: PHP-Parser throws on files with syntax errors.
- **Action**: The guard logs the file path to a `parse_failures` table and **continues processing remaining files**. The guarded file is marked as `violated_parse_error` in the CI report.
- **CI outcome**: Build fails. Developer must fix syntax or suppress the guard for that file.
- **Rationale**: A parse failure is effectively a code defect that must be resolved before merge. Skipping it silently would allow the violation through.

### Malformed PHP (AST Produced but Empty Nodes)

- **Symptom**: Parser succeeds but produces no traversable nodes (edge case: empty files, pure config files).
- **Action**: Skip file. No violation, no audit log.
- **CI outcome**: Clean.

### Timeout (Long Processing)

- **Threshold**: 30 seconds per file.
- **Action**: File is skipped and logged as `violated_timeout`. The guard continues to the next file.
- **CI outcome**: Build fails. Developer receives the offending file path in the report.
- **Rationale**: A timeout indicates either a massive file or an infinite loop in pattern matching. Both are code defects.

### Dependency Failure (PHP-Parser Not Installed)

- **Symptom**: The PHP-Parser autoload cannot be resolved.
- **Action**: The guard aborts with exit code `99`. No files are processed.
- **CI outcome**: Build fails with dependency error. No false positives or negatives are possible in this state.
- **Recovery**: Developer must `composer install` before the guard can function.

### CI Behavior — Distinction: Guard Failure vs Application Violation

| Scenario | CI Exit Code | Meaning | Action |
|----------|-------------|---------|--------|
| No violations | `0` | Clean | Merge allowed |
| Forbidden pattern detected | `1` | Violation | Developer must fix or add approved marker |
| Parse failure | `1` | Defect + violation | Developer must fix syntax |
| Timeout | `1` | Defect + violation | Developer must optimize or split file |
| Dependency failure | `99` | Environment issue | Developer must fix environment |
| CI infrastructure failure (network, disk) | `98` | External | Manual triage required |

Guard failure (exit codes `98`, `99`) is **not** an application violation. It is an environmental issue that requires operator intervention, not developer code changes.

Application violations (exit codes `0`, `1`) are **code-level** issues that require developer action: fix the code, or submit an approved allow marker with all required fields (see Allow Marker Governance).

## Future Evolution

- **PHPStan integration**: AST-based patterns could be migrated to PHPStan rules in the future, reducing tool maintenance.
- **Rector integration**: Rector rule compatibility enables automated refactoring when guard rules change.
- **Laravel Pint compatibility**: CI ordering: Pint → auth-guard → tests.