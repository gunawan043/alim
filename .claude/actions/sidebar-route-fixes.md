# Action Plan: Sidebar Fixes — Wrong Route Name Patterns

## Root Cause
The asrama sidebar files (admin-asrama, kepala-asrama, admin-pendidikan, wali-asrama, asrama-ro) all use `isActiveAsr($currentRoute, 'user.asrama.xxx')` to match against route names — but the actual route names are `asrama.xxx` (no `user.` prefix). The asrama routes live in `Route::prefix('asrama')->name('asrama.')->group(...)` under the main `Route::prefix('{userId}')` parent, but the `name()` on the group is `asrama.`, not `user.asrama.`.

Additionally, the `waka/` sidebar has hardcoded exact route name matches (`waka.absensi-gtk`, `waka.kisi-kisi-soal`, etc.) that correspond to redirect-only alias routes that **currently exist** in `routes/web.php`, but those names diverge from the actual underlying `user.*` routes used by the GTK admin-tu sidebar.

## Fixes Required

### 1. admin-asrama.blade.php — Fix ALL `user.asrama.` → `asrama.` prefix
**File:** `resources/views/layouts/sidebar/admin-asrama.blade.php`

Change all `isActiveAsr($currentRoute, 'user.asrama.` to `isActiveAsr($currentRoute, 'asrama.`

Also fix the fallback logic at the top:
- Line 19: `$currentRoute === 'user.asrama.my-profile'` → `$currentRoute === 'asrama.my-profile'`
- Line 27: `route('user.asrama.my-profile', ...)` → `route('asrama.my-profile', ...)`
- Lines 50-51: `$currentRoute === 'user.asrama.my-profile' || $currentRoute === 'user.asrama.show'` → `$currentRoute === 'asrama.my-profile' || $currentRoute === 'asrama.show'`
- Line 51: `route('user.asrama.my-profile', ...)` → `route('asrama.my-profile', ...)`
- Lines 57, 58, 64, 65, 71, 72, 78, 79: `user.asrama.wings.`, `user.asrama.rooms.`, `user.asrama.residents.`, `user.asrama.inventories.` → `asrama.wings.`, `asrama.rooms.`, `asrama.residents.`, `asrama.inventories.`
- Lines 87, 95, 102, 116, 123, 130, 137, 144, 153, 160, 167, 174, 183, 192, 199, 206: same pattern
- Line 109: `$currentRoute === 'user.asrama.permits.scan'` → `$currentRoute === 'asrama.permits.scan'`
- Lines 58, 65, 72, 79, 88, 96, 103, 110, 117, 124, 131, 138, 145, 154, 161, 168, 175, 184: `route('user.asrama.xxx', ...)` → `route('asrama.xxx', ...)`

### 2. kepala-asrama.blade.php — Fix ALL `user.asrama.` → `asrama.` prefix
**File:** `resources/views/layouts/sidebar/kepala-asrama.blade.php`

Same pattern as admin-asrama. Fix:
- Line 19: `$currentRoute === 'user.asrama.my-profile'` → `$currentRoute === 'asrama.my-profile'`
- Line 27: `route('user.asrama.my-profile', ...)` → `route('asrama.my-profile', ...)`
- All `isActiveAsr($currentRoute, 'user.asrama.` → `isActiveAsr($currentRoute, 'asrama.'`
- All `route('user.asrama.xxx', ...)` → `route('asrama.xxx', ...)`
- Line 192: `isActiveAsr($currentRoute, 'boarding-policies.')` → correct (this one is right, no user prefix)
- Line 199: `isActiveAsr($currentRoute, 'calendar.return.')` → correct (no user prefix needed)
- Line 206: `route('user.asrama.dormitory-returns', ...)` → `route('asrama.dormitory-returns', ...)`

### 3. admin-pendidikan.blade.php — Fix ALL `user.asrama.` → `asrama.` prefix
**File:** `resources/views/layouts/sidebar/admin-pendidikan.blade.php`

Same pattern. Fix all `user.asrama.xxx` → `asrama.xxx` in both `isActiveAsr` calls and `route()` calls.

### 4. wali-asrama.blade.php — Fix ALL `user.asrama.` → `asrama.` prefix
**File:** `resources/views/layouts/sidebar/wali-asrama.blade.php`

Same pattern. Fix all `user.asrama.xxx` → `asrama.xxx`.

### 5. asrama-ro.blade.php — Fix ALL `user.asrama.` → `asrama.` prefix
**File:** `resources/views/layouts/sidebar/asrama-ro.blade.php`

Same pattern. Fix all `user.asrama.xxx` → `asrama.xxx`.

### 6. waka.blade.php — Fix hardcoded `waka.` exact matches to use `isActiveWaka`
**File:** `resources/views/layouts/sidebar/waka.blade.php`

The waka sidebar currently uses hardcoded exact route checks like:
- `$currentRoute === 'waka.absensi-gtk'` (line ~293) — correct, these are actual waka alias routes
- `$currentRoute === 'waka.kisi-kisi-soal'` — correct
- `$currentRoute === 'waka.bank-soal'` — correct
- `$currentRoute === 'waka.soal-sumatif'` — correct
- `$currentRoute === 'waka.prestasi-akademik'` — correct
- `$currentRoute === 'waka.hafalan-quran'` — correct
- `$currentRoute === 'waka.hafalan-hadits'` — correct
- `$currentRoute === 'waka.ekstrakurikuler.index'` — correct
- `$currentRoute === 'waka.supervisi.index'` — correct
- `$currentRoute === 'waka.pekan-efektif.index'` — correct
- `$currentRoute === 'waka.jam-mengajar'` — correct
- `$currentRoute === 'waka.rekap-pergantian-jam'` — correct
- `$currentRoute === 'waka.surat-keluar.index'` — correct
- `$currentRoute === 'waka.surat-masuk.index'` — correct

These are actually fine — the waka subdomain routes are real named routes. **No changes needed for waka.blade.php.**

### 7. unified-gtk.blade.php — Fix duplicate "Administrasi" menu-title
**File:** `resources/views/layouts/sidebar/unified-gtk.blade.php`

Line with duplicate `<li class="menu-title"><span>Administrasi</span></li>` — remove one of them (there are two consecutive lines).

### 8. admin-tu.blade.php — Remove hardcoded waka. routes
**File:** `resources/views/layouts/sidebar/admin-tu.blade.php`

The admin-tu sidebar has hardcoded waka. routes (kisi-kisi-soal, bank-soal, soal-sumatif, supervisi, pekan-efektif, absensi-gtk, prestasi-akademik, hafalan-quran, hafalan-hadits, jam-mengajar, rekap-pergantian-jam, surat-keluar, surat-masuk). These should use `isActiveTU($currentRoute, 'user.')` to check the underlying user routes instead, since admin-tu users should access the `user.*` routes, not the waka subdomain alias routes.

Actually, looking at this more carefully: the admin-tu sidebar references `waka.` routes in some places. Since admin-tu users don't have access to the waka subdomain, these should be changed to `user.` equivalents:
- `waka.kisi-kisi-soal` → `user.kisi-kisi-soal.index`
- `waka.bank-soal` → `user.bank-soal.index`
- `waka.soal-sumatif` → `user.paket-soal.index`
- `waka.absensi-gtk` → `user.absensi-gtk.index`
- `waka.prestasi-akademik` → `user.student-achievement.index` (with type param)
- `waka.hafalan-quran` → `user.student-achievement.index` (with type param)
- `waka.hafalan-hadits` → `user.student-achievement.index` (with type param)
- `waka.jam-mengajar` → `user.teaching-assignments.index` (per route definition)
- `waka.rekap-pergantian-jam` → `user.kehadiran.pergantian-jam`
- `waka.surat-keluar.index` → use user route pattern
- `waka.surat-masuk.index` → use user route pattern
- `waka.ekstrakurikuler.index` → check if there's a user equivalent
- `waka.supervisi.index` → check if there's a user equivalent
- `waka.pekan-efektif.index` → check if there's a user equivalent

### 9. unified-gtk.blade.php — Add `waka.` route patterns in Waka section
**File:** `resources/views/layouts/sidebar/unified-gtk.blade.php`

In the Waka/Structural section, the absensi dropdown has:
- `waka.absensi-gtk` hardcoded — this is fine for waka users
- But the user-side absensi routes also need to be covered

### 10. personalia.blade.php — Check `kehadiran-gtk` route patterns
**File:** `resources/views/layouts/sidebar/personalia.blade.php`

The personalia sidebar uses `isActiveP($currentRoute, 'user.absensi-gtk.')` and links to `route('user.absensi-gtk.harian', ...)`. Verify these routes exist.

## Priority Order
1. **admin-asrama.blade.php** — critical, all active links broken
2. **kepala-asrama.blade.php** — critical, all active links broken
3. **admin-pendidikan.blade.php** — critical, all asrama links broken
4. **wali-asrama.blade.php** — critical, all asrama links broken
5. **asrama-ro.blade.php** — critical, all asrama links broken
6. **unified-gtk.blade.php** — remove duplicate menu-title
7. **admin-tu.blade.php** — fix waka. references to use user. equivalents
