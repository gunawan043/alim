# Fix datagtk-edit Issues - TODO List

## Overview
Make datagtk-edit.blade.php work exactly like datagtk-add.blade.php with existing data preloaded.
Reference: datagtk-add works perfectly.

**Status**: Plan approved ✅ | Proceed step-by-step

## Steps (5 total)

### 1. [ ] ✅ Create this TODO.md (Current)

### 2. [x] ✅ Fix app/Http/Controllers/GtkWizardController.php
```
- Add update(Request $request, $id) method
- Copy logic from GtkController::update 
- Handle pendidikan[] array → sync GtkEducation
- Handle anggota_keluarga[] → sync GtkFamilyMember  
- Process pendidikan_files[] uploads if present
- Return JSON success/error
```
**Test**: `php artisan route:clear` → POST /personalia/gtk/1

### 3. [ ] Fix app/Models/User.php relations
```
Add:
public function educations() { 
  return $this->hasMany(GtkEducation::class); 
}
```

### 4. [x] ✅ Fix resources/views/Personalia/datagtk-edit.blade.php (Complete JS + preload)
```
Controller preload (edit method):
with(['profile.addresses', 'profile.familyMembers', 'educations', 'employment', 'contact', 'workUnits'])

View changes:
a. PHP preload script (before </script>):
```
@php
  $educationList = $gtk->educations->map(function($e){
    return [
      'id' => $e->id,
      'jenjang_pendidikan' => $e->jenjang_pendidikan,
      'nama_satuan_pendidikan' => $e->nama_satuan_pendidikan,
      // ... all fields
    ];
  });
  $familyList = $gtk->profile->familyMembers->map(fn($m) => [...]);
@endphp
<script>
  let educationList = @json($educationList ?? []);
  let familyList = @json($familyList ?? []);
  const existingDomisili = { province_code: '{{ $domisiliAddress?->province_code ?? "" }}', ... };
  const existingKtp = { ... };
  const currentJenisGtk = '{{ $gtk->employment?->jenis_gtk ?? "" }}';
  const currentJabatan = '{{ $gtk->employment?->jabatan ?? "" }}';
</script>

b. JS init: Add after DOMContentLoaded:
   renderEducationList();
   renderFamilyList(); 
   restoreAddressDropdowns();
   populateJabatan(currentJenisGtk, currentJabatan);

c. submitForm(): 
   fetch('{{ route("personalia.gtk.update", $gtk->id) }}', { method: 'POST', body: formData })

d. Copy COMPLETE JS from datagtk-add.blade.php (replace broken version)
```

### 5. [ ] ✅ Test & Complete
```
php artisan route:clear && php artisan view:clear
1. Load /personalia/gtk/1/edit → see existing data in steps/modals
2. Test Lanjut buttons, dropdown alamat (auto-load cities/etc)
3. Add/edit education → modal works, list renders  
4. Add/edit family → search spouse, modal works
5. Review → data preview shows
6. Simpan → success JSON, redirect index

All 6 issues fixed ✓
```

**Progress Tracking**: Update `- [x]` each completion

