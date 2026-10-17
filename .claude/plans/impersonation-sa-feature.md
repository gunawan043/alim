# Impersonation — Super Admin (Role Switch Preview)

## Context

Saat ini SA hanya punya School Switch (filter data antar sekolah). Untuk membantu
debug/meniru pengalaman user tertentu (misal "kok GTK nilainya tidak muncul?"),
SA butuh fitur **Impersonate**: login ke akun user lain untuk melihat persis
apa yang user itu lihat.

Kebutuhan:
- Hanya user dengan permission `impersonate_role` (SA-only by default).
- Target = user dengan role **non Super Admin** (anti-lateral-privilege).
- Start: switch Auth identity ke target user; effectiveRoles/hak akses ikut
  identity target.
- Stop: clear session → user diarahkan ke `/login` (re-login penuh, sesuai
  keputusan user).
- Audit trail di `audit_logs` (kolom `action`, `record_type='user'`,
  `record_id=target`).

## Decisions

| Topic              | Choice                                                                                        |
|--------------------|------------------------------------------------------------------------------------------------|
| Target scope       | Hanya user dengan **roles non-Super Admin** (lihat `effectiveRoles()` tidak mengandung `super admin`) |
| Restore mechanism  | **Re-login penuh**: `Stop` → clear session + redirect `/login` (no session stack)             |
| UI trigger         | Dropdown "Login As…" di **topbar header-user** (dekat avatar)                                  |
| Permission         | `impersonate_role` (permission baru, group `Akademik`) → granted ke `Super Admin` saja       |
| Audit              | `audit_logs` rows: action `impersonate.start` / `impersonate.stop`, record_type=`user`         |

## Implementation Plan

### 1. Permission & Seeder
- Tambah 1 permission di `database/seeders/PermissionSeeder.php`:
  `'impersonate_role' => 'Can login as another user for support / debugging purposes'`
- Group mapping (`getPermissionGroup`): `impersonate_*` → `Akademik`.
- Grant ke `Super Admin` saja di `database/seeders/PermissionRoleSeeder.php` (tidak ke role lain).
- Pastikan baris 49-54 (`Super Admin: all permissions`) sudah otomatis
  include `impersonate_role` — ya, karena loop `$allPerms` jadi **no extra line** diperlukan.

### 2. Routes — `routes/web.php`
Tambah di grup `/school-switch` (auth middleware saja, tidak butuh role middleware karena check dilakukan di controller):

```php
Route::post('/impersonate/{targetUser}', [App\Http\Controllers\ImpersonateController::class, 'start'])
    ->middleware(['auth'])->name('impersonate.start');
Route::post('/impersonate/stop', [App\Http\Controllers\ImpersonateController::class, 'stop'])
    ->middleware(['auth'])->name('impersonate.stop');
```

> `start` di POST, return `redirect()->back()` dengan flash warning.
> `stop` di POST, redirect ke `route('login')`.

### 3. Controller — `app/Http/Controllers/ImpersonateController.php` (baru)

```php
public function start(Request $request, User $targetUser)
{
    $actor = $request->user();

    if (! canPermission('impersonate_role')) abort(403);

    if ($actor->id === $targetUser->id) {
        return back()->with('error', 'Anda tidak dapat login sebagai diri sendiri.');
    }

    // Target tidak boleh Super Admin (anti-lateral)
    if (in_array('super admin', array_map('strtolower', $targetUser->effectiveRoles()), true)) {
        abort(403, 'Tidak dapat impersonate Super Admin.');
    }

    if (! $targetUser->is_active) {
        return back()->with('error', 'User target non-aktif.');
    }

    // Audit before swap
    AuditLog::create([
        'user_id'     => $actor->id,
        'action'      => 'impersonate.start',
        'table_name'  => 'users',
        'record_id'   => $targetUser->id,
        'record_type' => 'user',
        'ip_address'  => $request->ip(),
        'user_agent'  => substr((string) $request->userAgent(), 0, 255),
    ]);

    Auth::login($targetUser);  // swap identity
    session()->regenerate();   // mitigasi session fixation

    return redirect()->route('root')
        ->with('warning', "Anda sekarang login sebagai {$targetUser->name}. Gunakan 'Stop Impersonate' untuk kembali.");
}

public function stop(Request $request)
{
    $target = $request->user();
    $actorId = session('impersonate.actor_id');

    if (! $actorId) {
        return redirect()->route('login');
    }

    AuditLog::create([
        'user_id'     => $actorId,
        'action'      => 'impersonate.stop',
        'table_name'  => 'users',
        'record_id'   => $target?->id,
        'record_type' => 'user',
        'ip_address'  => $request->ip(),
        'user_agent'  => substr((string) $request->userAgent(), 0, 255),
    ]);

    Auth::logout();
    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('login')
        ->with('info', 'Sesi impersonate diakhiri. Silakan login kembali.');
}
```

### 4. Middleware `DetectImpersonation` — `app/Http/Middleware/DetectImpersonation.php` (baru)

Bendera view-share supaya blade bisa nampilkan banner top + ubah label dropdown.
Tidak melakukan otorisasi (hanya observasi).

```php
public function handle(Request $request, Closure $next)
{
    // Flash flag setiap request yang current user adalah target impersonation,
    // di mana session sebelumnya masih menyimpan actor id.
    $isImpersonating = session()->has('impersonate.actor_id');

    // Note: kita tidak simpan actor_id di session karena restore = re-login.
    // Jadi "isImpersonating" cukup dari check Auth user? di topbar.
    // Middleware ini hanya share view var jika flash 'warning' menyebutkan impersonate.

    if (session()->has('impersonate.actor_id')) {
        view()->share('isImpersonating', true);
    }

    return $next($request);
}
```

> Catatan: Karena model restore = full re-login (bukan stack), state "sedang
> impersonate" di-detect cukup dari flash session key `impersonate.just_started`
> yang set di `start()` dan di-share via view. Tidak perlu session actor_id.

Revisi: pendekatan lebih sederhana. **Tidak perlu middleware baru.** Cukup
set session flag di controller dan share dari `ViewComposer` atau
sidebar composer existing.

### 5. View: Banner Impersonate — `resources/views/components/impersonate-banner.blade.php` (baru)

Banner sticky-top kuning/oranye, hanya muncul saat `session('impersonate.active')`:

```blade
@if (session('impersonate.active'))
  <div class="alert alert-warning rounded-0 mb-0 d-flex align-items-center justify-content-between">
    <div>
      <i class="ri-mask-line"></i>
      Anda login sebagai <strong>{{ Auth::user()->name }}</strong>
      ({{ implode(', ', Auth::user()->effectiveRoles()) }}).
    </div>
    <form method="POST" action="{{ route('impersonate.stop') }}">
      @csrf
      <button class="btn btn-sm btn-dark">Stop Impersonate</button>
    </form>
  </div>
@endif
```

Include di `resources/views/layouts/master.blade.php` tepat sebelum `<div id="layout-wrapper">`.

### 6. View: Dropdown "Login As" — `resources/views/components/impersonate-switcher.blade.php` (baru)

Dropdown searchable-list user non-Super Admin. Trigger di topbar header-user
(replace link "Profile" area). Hanya untuk user dgn permission `impersonate_role`.

- Field minimal: `name`, `roles[]`, `is_active`.
- Tombol "Login As" → POST form ke `route('impersonate.start', $u)`.
- Optional: filter by role & search box (JS-only, no backend).
- List di-load via controller composer (efficient — tidak query tiap dropdown toggle).

#### Composer — `app/Http/View/Composers/ImpersonateComposer.php` (baru)

```php
public function compose(View $view): void
{
    $user = auth()->user();
    $impersonatable = canPermission('impersonate_role') && ! in_array('super admin', array_map('strtolower', $user->effectiveRoles()), true)
        ? User::with('roles')
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', '!=', 'Super Admin'))
            ->orderBy('name')
            ->limit(50)
            ->get()
        : collect();

    $view->with('impersonatableUsers', $impersonatable);
}
```

Register di `AppServiceProvider::boot()`:

```php
View::composer('components.impersonate-switcher', ImpersonateComposer::class);
```

### 7. Topbar — `resources/views/layouts/topbar.blade.php`

Sisipkan `<li class="dropdown">` sebelum `<div class="dropdown ms-sm-3 header-item">`
(user dropdown). Tampilkan badge "SA" bila user punya permission `impersonate_role`.

```blade
@if (canPermission('impersonate_role'))
  <div class="dropdown ms-sm-2 header-item">
    @include('components.impersonate-switcher')
  </div>
@endif
```

### 8. Tests — `tests/Feature/SuperAdmin/ImpersonateTest.php` (baru)

- `test_sa_can_impersonate_non_super_admin` (200)
- `test_non_sa_cannot_impersonate` (403)
- `test_cannot_impersonate_super_admin` (403)
- `test_cannot_impersonate_self` (302 + error flash)
- `test_cannot_impersonate_inactive_user` (302 + error flash)
- `test_stop_impersonate_redirects_to_login` (200, AuditLog row exists)
- `test_audit_log_has_impersonate_actions` (audit row has action `impersonate.start`)

## Files

### New
- `app/Http/Controllers/ImpersonateController.php`
- `app/Http/View/Composers/ImpersonateComposer.php`
- `resources/views/components/impersonate-banner.blade.php`
- `resources/views/components/impersonate-switcher.blade.php`
- `tests/Feature/SuperAdmin/ImpersonateTest.php`

### Modified
- `database/seeders/PermissionSeeder.php` — add `impersonate_role` permission
- `database/seeders/PermissionRoleSeeder.php` — no change (Super Admin gets it via all-perms loop)
- `routes/web.php` — add 2 POST routes
- `app/Providers/AppServiceProvider.php` — register View composer
- `resources/views/layouts/master.blade.php` — include impersonate banner
- `resources/views/layouts/topbar.blade.php` — include impersonate-switcher

## Risk / Notes

1. **Tidak ada session-stack**: `Auth::login($targetUser)` menimpa identity.
   Karena restore = re-login penuh (sesuai keputusan), kita tidak menyimpan
   actor_id di session — lebih sederhana & minim risiko session-bug.
2. **`canPermission()` helper**: dipakai di controller & blade. Pastikan helper
   ini resolve via `AuthorizationManager` context — kalau context belum bound
   (mis. endpoint non-scoped), gunakan fallback `Gate::allows()` atau skip.
   Check existing helper signature dulu.
3. **Flash vs session vs DB**: state "sedang impersonate" akan hilang setelah
   reload pertama (banner pakai session flag). Mungkin perlu set persistent
   cookie atau hide-banner flag via Auth user check. **Pilihan aman**:
   set flash `impersonate.active=true` di `start`, dan biarkan banner
   hilang setelah 1 navigation — sengaja untuk membatasi "lupa diri sendiri".
4. **CSRF**: form POST sudah dilindungi `@csrf` di blade.
5. **Audit record_type**: existing `AuditLog::record()` morphTo expects
   `record_type, record_id` — `record_type` di sini `'user'` cocok dengan
   `morphTo` param `'record'` di model.
