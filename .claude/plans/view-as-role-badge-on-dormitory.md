# Plan: Display ViewAs role badge on Dormitory pages

## Goal
When a System Admin activates "View As" on a dormitory (asrama) page, show a **visible role badge** on every dormitory page (show, index, edit, residents, attendance, permits, etc.) so the admin always knows which role they're impersonating.

## Key Findings (revised after investigation)

### Current ViewAs state
- **State stored in session** via `App\Services\ViewAsService` — `setCurrentViewRole(string $role)`, `getCurrentViewRole(): ?string`
- **System-admin gated** — `ViewAsController` checks `$user->isSystemAdmin()`
- **Helper `currentViewRole()`**: NOT yet a global helper function (would need to be added in `app/Http/helpers.php`)

### Current ViewAs UI (in main app only)
- **Switcher dropdown** in `resources/views/system/_switcher.blade.php` — renders the "View As" button + the "Currently viewing as <role>" alert
- **Included from** `resources/views/layouts/topbar.blade.php:187` — `@include('system._switcher')`
- **Variables fed by** `app/View/Composers/SidebarComposer.php` — already binds `$viewAsRole`, `$isViewingAs`, `$systemRoles`, `$schools`, `$viewAsSwitcherVisible`

### Layout chain (verified)
- `dormitory/show.blade.php` extends `'layouts.master'` (verified)
- `layouts/master` includes the topbar → which includes `system._switcher`
- So the switcher button SHOULD already render on dormitory pages if `$viewAsSwitcherVisible === true`

### Why the badge isn't showing on dormitory pages today
The switcher is rendered, but the **alert "Currently viewing as <role>" inside the dropdown is hidden until opened**. There is no **persistent visible badge** anywhere on the page. Additionally, the `$viewAsSwitcherVisible` flag may be off on dormitory routes.

## Recommended Approach

Add a **persistent, page-level ViewAs badge** that renders at the top of dormitory pages whenever a system admin is viewing as another role. The badge should:
- Be visible at all times (not hidden inside a dropdown)
- Show the role name
- Include a "Reset" / "Stop viewing as" link
- Render **above** the breadcrumb (so it's the first thing visible)
- Be added to a **dormitory-specific partial** that all dormitory pages can include

## Implementation Steps

### Step 1: Create a new partial `resources/views/dormitory/_view_as_badge.blade.php`
A small standalone partial that renders a Bootstrap alert when `$isViewingAs` is true:

```blade
@isset($isViewingAs)
    @if($isViewingAs && !empty($viewAsRole))
        <div class="alert alert-warning border-0 rounded-0 mb-0 d-flex align-items-center justify-content-between"
             role="alert"
             data-view-as-badge>
            <div>
                <i class="bx bx-analyse me-1"></i>
                <strong>View As:</strong>
                <span class="badge bg-warning text-dark ms-1">{{ $viewAsRole }}</span>
                <small class="text-muted ms-2">— Hak akses dibatasi sesuai role ini.</small>
            </div>
            <form method="POST" action="{{ route('system.view-as.reset') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-dark">
                    <i class="bx bx-x"></i> Stop Viewing As
                </button>
            </form>
        </div>
    @endif
@endisset
```

### Step 2: Include the partial in all dormitory pages
Two options:

**Option A — Add to `layouts.master`** (simplest, badge appears everywhere):
Add the `@include('dormitory._view_as_badge')` at the top of the `@yield('content')` section in `layouts/master.blade.php`.

**Option B — Add to each dormitory blade** (more targeted):
Add `@include('dormitory._view_as_badge')` inside `@section('content')` of each dormitory page that extends master.

**Recommendation: Option A** — one change, covers all dormitory pages and any future ones, and is harmless on non-dormitory pages because the `$isViewingAs` flag is already bound everywhere by SidebarComposer.

### Step 3: Verify SidebarComposer is wired into `layouts.master`
Check `AppServiceProvider` registers `SidebarComposer::class` for `layouts.master` and any other top-level layouts. If only registered for some layouts, register for all (`*` wildcard) or ensure dormitory routes use a layout that has it.

### Step 4: Verify `system.view-as.reset` route exists
Check `routes/web.php` (or wherever) has the reset route. If missing, add it.

## Files to Touch

1. **NEW** `resources/views/dormitory/_view_as_badge.blade.php` — the badge partial
2. **MODIFY** `resources/views/layouts/master.blade.php` — add `@include('dormitory._view_as_badge')` above the content yield
3. (Optional) **MODIFY** `app/Providers/AppServiceProvider.php` — confirm SidebarComposer is registered for `layouts.master`

## Risk Assessment
- **Low risk**: only renders when `$isViewingAs === true` (already set by SidebarComposer for system admins viewing-as)
- **No DB writes, no schema changes**: pure view-layer addition
- **Backward compatible**: regular users (not viewing-as) see nothing
- **Reset button**: reuses existing `system.view-as.reset` route — no new endpoints needed
- **Sidebar** already adapts to ViewAs (SidebarComposer line 35-40 hydrates `$roleIds` from the impersonated role), so the badge's appearance will match the sidebar's behavior

## Verification Plan
1. Log in as system admin
2. Open a dormitory page → confirm no badge (no ViewAs yet)
3. Click "View As" → select a role → submit
4. Reload the dormitory page → confirm badge appears at top with role name
5. Click "Stop Viewing As" → confirm badge disappears
6. Repeat on at least 3 different dormitory pages (show, residents, attendance)