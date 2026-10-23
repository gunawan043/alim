# Legacy Test Backlog

Full suite saat ini belum hijau. Gate yang wajib hijau untuk modul asrama:

```bash
php artisan test --testsuite="Asrama Critical"
```

Backlog legacy yang masih perlu dimodernisasi (bukan regresi modul asrama):

- `tests/Feature/Sarpras/*` — fixture schema dan migrasi manual.
- `tests/Feature/TenantIsolationRegressionTest.php` — sebagian skenario wali/registration token.
- `tests/Feature/StudentLookupServiceTest.php` — fixture `schools.work_unit_id`.
- `tests/Feature/StudentDataTest.php` — fixture schema user/school.
- `tests/Integration/Authorization/*` — snapshot rebuild pada database test.
- `tests/Feature/StudyGroupSubjectAssignmentCascadeTest.php` — dua skenario cascade.
- `tests/Feature/SupervisiSmokeTest.php`, `tests/Feature/Uks/UksClinicWorkflowTest.php` — schema/fixture lama.

Aturan:

1. Jangan menambah failure baru pada `Asrama Critical`.
2. Setiap perbaikan fitur asrama harus menjalankan suite tersebut.
3. Legacy test diperbaiki per modul, bukan dihapus tanpa alasan.
