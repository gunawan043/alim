<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationPageController;
use App\Http\Controllers\SecureAccessController;
use App\Http\Controllers\GtkRecruitmentController;
use App\Http\Controllers\GTKController;
use App\Http\Controllers\BulkGraduationController;
use App\Http\Controllers\BulkPromotionController;
use App\Http\Controllers\WorkUnitController;
use App\Http\Controllers\GtkRequestController;
use App\Http\Controllers\Akademik\KktpController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GTKEducationController;
use App\Http\Controllers\GtkWizardController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSecurityController;
use App\Http\Controllers\RecruitmentPipelineController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\MasterData\MataPelajaranController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\SidebarMenuController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\PensionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentAchievementController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\JenjangKarirController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\SuperAdmin\RoleController;
use App\Http\Controllers\SuperAdmin\PermissionController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\TokenSesiController;
use App\Http\Controllers\SuperAdmin\FailedJobController;
use App\Http\Controllers\SuperAdmin\NotificationUniversalController;
use App\Http\Controllers\SuperAdmin\SidebarMenuManagementController;
use App\Http\Controllers\SuperAdmin\PasswordResetLogController;
use App\Http\Controllers\SuperAdmin\SchoolSwitchController;
use App\Http\Controllers\SuperAdmin\SystemSettingController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolUnitController;
use App\Http\Controllers\SchoolsGlobalController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\InstitutionDecreeController;
use App\Http\Controllers\DokumenIsoController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\OtherTeacherTaskController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\GradeLevelApiController;
use App\Http\Controllers\StudyGroupController;
use App\Http\Controllers\SarprasController;
use App\Http\Controllers\StudyGroupApiController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentMutationOutController;
use App\Http\Controllers\StudentMutationInController;
use App\Http\Controllers\ViolationPointController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\KaldikController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\NilaiGuruController;
use App\Http\Controllers\NilaiKelasController;
use App\Http\Controllers\WakaController;
use App\Http\Controllers\StudentImmunizationController;
use App\Http\Controllers\StudentHealthCheckupController;
use App\Http\Controllers\StudentHealthPermitController;
use App\Http\Controllers\StudentMedicineInventoryController;
use App\Http\Controllers\StudentMedicineLogController;
use App\Http\Controllers\StudentHealthMetricController;
use App\Http\Controllers\StudentCounselingRecordController;
use App\Http\Controllers\FacilityReferralController;
use App\Http\Controllers\SanitationInspectionController;
use App\Http\Controllers\DormitoryRoomMoveController;
use App\Http\Controllers\DormitoryInventoryController;
use App\Http\Controllers\DormitoryController;
use App\Http\Controllers\DormitoryResidentController;
use App\Http\Controllers\DormitoryAttendanceController;
use App\Http\Controllers\DormitoryPermitController;
use App\Http\Controllers\DormitoryViolationController;
use App\Http\Controllers\DormitoryPostController;
use App\Http\Controllers\DormitoryVisitLogController;
use App\Http\Controllers\DormitoryWingController;
use App\Http\Controllers\DormitoryRoomController;
use App\Http\Controllers\DormitoryRoomApiController;
use App\Http\Controllers\DormitoryMasterController;
use App\Http\Controllers\StudentMahromController;
use App\Http\Controllers\StudentPromotionController;

/*
|--------------------------------------------------------------------------
| GLOBAL ROUTE PATTERNS
| Definisikan UUID pattern secara global agar konsisten di semua route.
| Ini mencegah konflik antara literal route (/create, /find-student, dll)
| dengan wildcard route (/{id}, /{uuid}, dll).
|--------------------------------------------------------------------------
*/
$uuidPattern = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';

Route::pattern('uuid',           $uuidPattern);
Route::pattern('gtk',            $uuidPattern);
Route::pattern('userId',         $uuidPattern);
Route::pattern('id',             $uuidPattern);   // <-- FIX UTAMA: global pattern untuk {id}
Route::pattern('schoolId',       $uuidPattern);
Route::pattern('workUnitId',     $uuidPattern);
Route::pattern('recruitmentUuid',$uuidPattern);
Route::pattern('requestUuid',    $uuidPattern);
Route::pattern('approvalUuid',   $uuidPattern);
Route::pattern('mutationUuid',   $uuidPattern);
Route::pattern('violationUuid',  $uuidPattern);
Route::pattern('studentUuid',    $uuidPattern);
Route::pattern('gtkUuid',        $uuidPattern);
Route::pattern('workUnitUuid',   $uuidPattern);
Route::pattern('studyGroupId',   $uuidPattern);
Route::pattern('alumniUuid',     $uuidPattern);
Route::pattern('healthUuid',     $uuidPattern);
Route::pattern('asramaUuid',     $uuidPattern);
Route::pattern('residentUuid',   $uuidPattern);
Route::pattern('permitUuid',     $uuidPattern);
Route::pattern('postUuid',       $uuidPattern);
Route::pattern('visitUuid',      $uuidPattern);
Route::pattern('wingUuid',     $uuidPattern);
Route::pattern('roomUuid',     $uuidPattern);
Route::pattern('mahromUuid',   $uuidPattern);
Route::pattern('promotionId',  $uuidPattern);
Route::pattern('detailId',    $uuidPattern);
Route::pattern('violationUuid', $uuidPattern);
Route::pattern('kaldikId',    $uuidPattern);
Route::pattern('recapUuid',     $uuidPattern);

// adminBookId boleh UUID atau integer
Route::pattern('adminBookId', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}|[0-9]+');

/*
|--------------------------------------------------------------------------
| PUBLIC AUTH ROUTES (NO MIDDLEWARE!)
|--------------------------------------------------------------------------
*/
Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.process');
Route::post('/logout',[LoginController::class, 'logout'])->name('logout');


Route::middleware('guest')->group(function () {
    Route::prefix('password')->name('password.')->group(function () {
        Route::get('/forgot',       [ForgotPasswordController::class, 'showForm'])->name('request');
        Route::post('/forgot',      [ForgotPasswordController::class, 'sendOtp'])->name('email');
        Route::get('/otp',          [ForgotPasswordController::class, 'showOtpForm'])->name('otp.form');
        Route::post('/otp',         [ForgotPasswordController::class, 'verifyOtp'])->name('otp.verify');
        Route::post('/otp/resend',  [ForgotPasswordController::class, 'resendOtp'])->name('otp.resend');
        Route::post('/otp/check',   [ForgotPasswordController::class, 'checkOtpValidity'])->name('otp.check');
        Route::get('/reset',        [ForgotPasswordController::class, 'showResetForm'])->name('reset.form');
        Route::post('/reset',       [ForgotPasswordController::class, 'resetPassword'])->name('update');
        Route::post('/cancel',      [ForgotPasswordController::class, 'cancelReset'])->name('cancel');
    });
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES — ALL under /{userId}
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/', [HomeController::class, 'root'])->name('root');

    Route::get('/notifications', [NotificationPageController::class, 'index'])
        ->name('user.notifications.index');

    Route::get('/access/{token}/data-pegawai', [SecureAccessController::class, 'dataPegawai'])
        ->name('secure.data.pegawai')
        ->middleware(['verify.secure.token']);

    /*
    |--------------------------------------------------------------------------
    | SCHOOL SWITCH (Super Admin)
    |--------------------------------------------------------------------------
    | POST /school-switch — Switch Super Admin school context via session.
    | Does NOT require {userId} prefix so it works from any page.
    */
    Route::post('/school-switch', [SchoolSwitchController::class, 'switch'])
        ->middleware(['auth'])
        ->name('school-switch');

    /*
    |--------------------------------------------------------------------------
    | USER GROUP: /{userId}
    |--------------------------------------------------------------------------
    */
    Route::prefix('{userId}')
        ->middleware(['auth', 'role.access', 'school.context'])
        ->name('user.')
        ->group(function () {

            // ── USER NOTIFICATIONS API ───────────────────────────────
            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/',                [NotificationUniversalController::class, 'index'])->name('index');
                Route::post('/{id}/mark-read', [NotificationUniversalController::class, 'markAsRead'])->name('mark-read');
                Route::post('/mark-all-read',  [NotificationUniversalController::class, 'markAllRead'])->name('mark-all-read');
                Route::delete('/{id}',         [NotificationUniversalController::class, 'destroy'])->name('destroy');
            });

            // ── SCHOOLS ───────────────────────────────────────────────
            Route::prefix('schools')->name('schools.')->group(function () {
                Route::get('/',       [SchoolController::class, 'index'])->name('index');
                Route::get('/create', [SchoolController::class, 'create'])->name('create');
                Route::post('/',      [SchoolController::class, 'store'])->name('store');
                Route::get('/global', [SchoolsGlobalController::class, 'index'])->name('global.index');

                // Satuan kerja sub-routes (sebelum {schoolId} wildcard)
                Route::prefix('satuan-kerja/{workUnitId}')->name('satuan-kerja.')->group(function () {
                    Route::get('/schools/{schoolId}',      [SchoolUnitController::class, 'show'])->name('show');
                    Route::get('/schools/{schoolId}/edit', [SchoolUnitController::class, 'edit'])->name('edit');
                });

                // ── DATA NILAI
                Route::prefix('nilai')->name('nilai.')->group(function () {
                    Route::get('/',                    [NilaiController::class, 'index'])->name('index');
                    Route::get('/{adminBookId}/sts',   [NilaiController::class, 'sts'])->name('sts');
                    Route::post('/{adminBookId}/sts',  [NilaiController::class, 'storeSts'])->name('sts.store');
                    Route::get('/{adminBookId}/sas',   [NilaiController::class, 'sas'])->name('sas');
                    Route::post('/{adminBookId}/sas',  [NilaiController::class, 'storeSas'])->name('sas.store');
                });

                // ── NILAI KELAS — untuk Admin TU / Waka / Wali Kelas / Kepsek
                Route::prefix('nilai-kelas')->name('nilai-kelas.')->group(function () {
                    Route::get('/{studyGroupId}/sts',  [NilaiKelasController::class, 'sts'])->name('sts');
                    Route::post('/{studyGroupId}/sts', [NilaiKelasController::class, 'stsStore'])->name('sts.store');
                    Route::get('/{studyGroupId}/leger/cetak',     [NilaiKelasController::class, 'legerCetak'])->name('leger.cetak');
                    Route::get('/{studyGroupId}/leger/download',  [NilaiKelasController::class, 'legerDownload'])->name('leger.download');
                    Route::get('/{studyGroupId}/rapor',            [NilaiKelasController::class, 'rapor'])->name('rapor');
                    Route::get('/{studyGroupId}/rapor/{studentId}/cetak', [NilaiKelasController::class, 'raporCetak'])->name('rapor.cetak');
                });

                // ── KKTP / KKM — untuk Admin TU / Waka / Kepsek
                Route::prefix('kktp')->name('kktp.')->group(function () {
                    Route::get('/',      [KktpController::class, 'index'])->name('index');
                    Route::post('/',     [KktpController::class, 'store'])->name('store');
                    Route::post('/generate', [KktpController::class, 'generate'])->name('generate');
                });

                // ── GURU MAPEL — Buku Admin Guru
                Route::prefix('guru-mapel')->name('guru-mapel.')->group(function () {
                    Route::get('/',               [NilaiGuruController::class, 'index'])->name('index');
                    Route::post('/{adminBookId}/autosave', [NilaiGuruController::class, 'autoSave'])->name('autosave');
                    Route::get('/{adminBookId}',  [NilaiGuruController::class, 'wizard'])->name('wizard');
                    Route::get('/{adminBookId}/w1',  [NilaiGuruController::class, 'wizard1'])->name('w1');
                    Route::post('/{adminBookId}/w1', [NilaiGuruController::class, 'wizard1Store'])->name('w1.store');
                    Route::get('/{adminBookId}/w2',  [NilaiGuruController::class, 'wizard2'])->name('w2');
                    Route::post('/{adminBookId}/w2', [NilaiGuruController::class, 'wizard2Store'])->name('w2.store');
                    Route::get('/{adminBookId}/w3',  [NilaiGuruController::class, 'wizard3'])->name('w3');
                    Route::post('/{adminBookId}/w3', [NilaiGuruController::class, 'wizard3Store'])->name('w3.store');
                    Route::post('/{adminBookId}/w3/bobot', [NilaiGuruController::class, 'wizard3Bobot'])->name('w3.bobot');
                    Route::get('/{adminBookId}/w4',  [NilaiGuruController::class, 'wizard4'])->name('w4');
                    Route::post('/{adminBookId}/w4', [NilaiGuruController::class, 'wizard4Store'])->name('w4.store');
                    Route::get('/{adminBookId}/w5',  [NilaiGuruController::class, 'wizard5'])->name('w5');
                    Route::post('/{adminBookId}/w5', [NilaiGuruController::class, 'wizard5Store'])->name('w5.store');
                    Route::get('/{adminBookId}/w6',  [NilaiGuruController::class, 'wizard6'])->name('w6');
                    Route::post('/{adminBookId}/w6', [NilaiGuruController::class, 'wizard6Store'])->name('w6.store');
                });

                // {schoolId} wildcard — harus di BAWAH semua literal routes
                Route::get('/{schoolId}',      [SchoolController::class, 'show'])->name('show');
                Route::get('/{schoolId}/edit', [SchoolController::class, 'edit'])->name('edit');
                Route::put('/{schoolId}',      [SchoolController::class, 'update'])->name('update');
                Route::delete('/{schoolId}',   [SchoolController::class, 'destroy'])->name('destroy');
            });

            // ── SCHOOLS GLOBAL
            Route::get('/schools-global',       [SchoolsGlobalController::class, 'index'])->name('schools-global.index');
            Route::get('/schools-global/index', [SchoolsGlobalController::class, 'index'])->name('schools-global-show.index');

            // ── GTK ────────────────────────────────────────────────
            Route::prefix('gtk')->name('gtk.')->group(function () {
                Route::get('/',                             [GtkWizardController::class, 'index'])->name('index');
                Route::get('/guru',                         [GtkWizardController::class, 'indexguru'])->name('indexguru');
                Route::get('/tendik',                       [GtkWizardController::class, 'indextendik'])->name('indextendik');
                Route::get('/create',                       [GtkWizardController::class, 'create'])->name('create');
                Route::post('/',                            [GtkWizardController::class, 'store'])->name('store');
                Route::get('/import',                       [GtkWizardController::class, 'import'])->name('import');
                Route::get('/import/template/{workUnitId}', [GtkWizardController::class, 'importTemplate'])->name('import.template');
                Route::post('/import/store',                [GtkWizardController::class, 'importStore'])->name('import.store');
                Route::post('/datatable',                   [GtkWizardController::class, 'datatable'])->name('datatable');
                Route::get('/export-preview',               [GtkWizardController::class, 'exportPreview'])->name('export.preview');
                Route::get('/export',                       [GtkWizardController::class, 'export'])->name('export');
                Route::post('/verify-password',             [GtkWizardController::class, 'verifyPassword'])->name('verify-password');
                Route::get('/satuan-kerja/{satuanKerja}',  [GTKController::class, 'indexByWorkUnit'])->name('by-work-unit');

                // Education standalone (sebelum {uuid} wildcard)
                Route::prefix('educations')->name('educations.')->group(function () {
                    Route::post('/',        [GTKEducationController::class, 'store'])->name('store');
                    Route::get('/{id}',    [GTKEducationController::class, 'show'])->name('show');
                    Route::put('/{id}',    [GTKEducationController::class, 'update'])->name('update');
                    Route::delete('/{id}', [GTKEducationController::class, 'destroy'])->name('destroy');
                });

                // GTK detail (UUID) — wildcard harus di bawah semua literal
                Route::prefix('{uuid}')->group(function () {
                    Route::get('/',               [GtkWizardController::class, 'show'])->name('show');
                    Route::get('/edit',           [GtkWizardController::class, 'edit'])->name('edit');
                    Route::put('/',               [GtkWizardController::class, 'update'])->name('update');
                    Route::delete('/',            [GtkWizardController::class, 'destroy'])->name('destroy');
                    Route::post('/toggle-status', [GtkWizardController::class, 'toggleStatus'])->name('toggle-status');
                    Route::post('/reset-password',[GtkWizardController::class, 'resetPassword'])->name('reset-password');

                    Route::prefix('profile')->name('profile.')->group(function () {
                        Route::get('/',         [ProfileController::class, 'show'])->name('show');
                        Route::get('/edit',     [ProfileController::class, 'edit'])->name('edit');
                        Route::put('/',         [ProfileController::class, 'update'])->name('update');
                        Route::post('/photo',   [ProfileController::class, 'uploadPhoto'])->name('photo.upload');
                        Route::delete('/photo', [ProfileController::class, 'deletePhoto'])->name('photo.delete');
                    });

                    Route::prefix('family-member')->name('family-member.')->group(function () {
                        Route::post('/store',    [GtkWizardController::class, 'storeFamilyMember'])->name('store');
                        Route::get('/{id}/edit', [GtkWizardController::class, 'editFamilyMember'])->name('edit');
                        Route::put('/{id}',      [GtkWizardController::class, 'updateFamilyMember'])->name('update');
                        Route::delete('/{id}',   [GtkWizardController::class, 'deleteFamilyMember'])->name('delete');
                    });

                    Route::prefix('education')->name('education.')->group(function () {
                        Route::post('/store',        [GtkWizardController::class, 'storeEducationModal'])->name('store');
                        Route::get('/{id}/edit',    [GtkWizardController::class, 'editEducation'])->name('edit');
                        Route::put('/{id}',         [GtkWizardController::class, 'updateEducation'])->name('update');
                        Route::delete('/{id}',      [GtkWizardController::class, 'deleteEducation'])->name('delete');
                        Route::post('/{id}/verify', [GtkWizardController::class, 'verifyEducation'])->name('verify');
                    });
                });
            });

            // ── PENSION / PENSIUN ──────────────────────────────────
            Route::prefix('pension')->name('pension.')->group(function () {
                Route::get('/',                   [PensionController::class, 'index'])->name('index');
                Route::get('/settings',          [PensionController::class, 'settings'])->name('settings');
                Route::post('/settings',          [PensionController::class, 'updateSettings'])->name('settings.update');
                Route::get('/{uuid}/edit',        [PensionController::class, 'edit'])->name('edit');
                Route::post('/{uuid}',            [PensionController::class, 'update'])->name('update');
                Route::post('/datatable',         [PensionController::class, 'datatable'])->name('datatable');
            });

            // ── MASTER DATA ────────────────────────────────────────
            Route::prefix('master-data')->name('master-data.')->group(function () {
                Route::get('/jenis-gtk',         [MasterDataController::class, 'jenisGtkIndex'])->name('jenis-gtk.index');
                Route::post('/jenis-gtk',        [MasterDataController::class, 'jenisGtkStore'])->name('jenis-gtk.store');
                Route::put('/jenis-gtk/{id}',    [MasterDataController::class, 'jenisGtkUpdate'])->name('jenis-gtk.update');
                Route::delete('/jenis-gtk/{id}', [MasterDataController::class, 'jenisGtkDestroy'])->name('jenis-gtk.destroy');

                Route::get('/jabatan',           [MasterDataController::class, 'jabatanIndex'])->name('jabatan.index');
                Route::post('/jabatan',          [MasterDataController::class, 'jabatanStore'])->name('jabatan.store');
                Route::put('/jabatan/{id}',      [MasterDataController::class, 'jabatanUpdate'])->name('jabatan.update');
                Route::delete('/jabatan/{id}',   [MasterDataController::class, 'jabatanDestroy'])->name('jabatan.destroy');
                Route::get('/jabatan-by-jenis',  [MasterDataController::class, 'getJabatanByJenis'])->name('jabatan.by-jenis');

                Route::get('/satuan-kerja',              [MasterDataController::class, 'satuanKerjaIndex'])->name('satuan-kerja.index');
                Route::get('/satuan-kerja/{id}',      [MasterDataController::class, 'satuanKerjaShow'])->name('satuan-kerja.show');
                Route::post('/satuan-kerja',           [MasterDataController::class, 'satuanKerjaStore'])->name('satuan-kerja.store');
                Route::put('/satuan-kerja/{id}',       [MasterDataController::class, 'satuanKerjaUpdate'])->name('satuan-kerja.update');
                Route::delete('/satuan-kerja/{id}',   [MasterDataController::class, 'satuanKerjaDestroy'])->name('satuan-kerja.destroy');
                Route::post('/satuan-kerja/generate-code', [MasterDataController::class, 'satuanKerjaGenerateCode'])->name('satuan-kerja.generate-code');
                Route::post('/satuan-kerja/bulk-delete',   [MasterDataController::class, 'satuanKerjaBulkDestroy'])->name('satuan-kerja.bulk-delete');
                Route::patch('/satuan-kerja/{id}/toggle-status', [MasterDataController::class, 'satuanKerjaToggleStatus'])->name('satuan-kerja.toggle-status');

                Route::get('/mata-pelajaran',         [MataPelajaranController::class, 'index'])->name('mata-pelajaran.index');
                Route::post('/mata-pelajaran',        [MataPelajaranController::class, 'store'])->name('mata-pelajaran.store');
                Route::post('/mata-pelajaran/{subjectId}/toggle', [MataPelajaranController::class, 'toggle'])->name('mata-pelajaran.toggle');
            });

            // ── KALDIK / AGENDA KEGIATAN ──────────────────────────
            Route::prefix('kaldik')->name('kaldik.')->group(function () {
                Route::get('/',                    [KaldikController::class, 'index'])->name('index');
                Route::get('/create',              [KaldikController::class, 'create'])->name('create');
                Route::post('/',                   [KaldikController::class, 'store'])->name('store');
                Route::get('/{kaldikId}',          [KaldikController::class, 'show'])->name('show');
                Route::get('/{kaldikId}/edit',     [KaldikController::class, 'edit'])->name('edit');
                Route::put('/{kaldikId}',          [KaldikController::class, 'update'])->name('update');
                Route::delete('/{kaldikId}',       [KaldikController::class, 'destroy'])->name('destroy');
                Route::post('/{kaldikId}/toggle',  [KaldikController::class, 'toggleActive'])->name('toggle');
            });

            // ── JENJANG KARIR
            Route::prefix('jenjang-karir')->name('jenjang-karir.')->group(function () {
                Route::get('/career-path',             [JenjangKarirController::class, 'careerPathIndex'])->name('career-path.index');
                Route::post('/career-path',            [JenjangKarirController::class, 'careerPathStore'])->name('career-path.store');
                Route::put('/career-path/{id}',        [JenjangKarirController::class, 'careerPathUpdate'])->name('career-path.update');
                Route::delete('/career-path/{id}',     [JenjangKarirController::class, 'careerPathDestroy'])->name('career-path.destroy');

                Route::get('/mutasi-rotasi',            [JenjangKarirController::class, 'mutasiIndex'])->name('mutasi.index');
                Route::post('/mutasi-rotasi',           [JenjangKarirController::class, 'mutasiStore'])->name('mutasi.store');
                Route::put('/mutasi-rotasi/{id}',      [JenjangKarirController::class, 'mutasiUpdate'])->name('mutasi.update');
                Route::post('/mutasi-rotasi/{id}/approve', [JenjangKarirController::class, 'mutasiApprove'])->name('mutasi.approve');
                Route::post('/mutasi-rotasi/{id}/reject',  [JenjangKarirController::class, 'mutasiReject'])->name('mutasi.reject');
                Route::delete('/mutasi-rotasi/{id}',   [JenjangKarirController::class, 'mutasiDestroy'])->name('mutasi.destroy');

                Route::get('/promosi-demosi',           [JenjangKarirController::class, 'promosiIndex'])->name('promosi.index');
                Route::post('/promosi-demosi',          [JenjangKarirController::class, 'promosiStore'])->name('promosi.store');
                Route::put('/promosi-demosi/{id}',     [JenjangKarirController::class, 'promosiUpdate'])->name('promosi.update');
                Route::delete('/promosi-demosi/{id}',  [JenjangKarirController::class, 'promosiDestroy'])->name('promosi.destroy');

                Route::get('/talent-pool',             [JenjangKarirController::class, 'talentPoolIndex'])->name('talent.index');
                Route::post('/talent-pool',            [JenjangKarirController::class, 'talentPoolStore'])->name('talent.store');
                Route::put('/talent-pool/{id}',        [JenjangKarirController::class, 'talentPoolUpdate'])->name('talent.update');
                Route::delete('/talent-pool/{id}',     [JenjangKarirController::class, 'talentPoolDestroy'])->name('talent.destroy');

                Route::get('/succession-plan',         [JenjangKarirController::class, 'successionIndex'])->name('succession.index');
                Route::post('/succession-plan',        [JenjangKarirController::class, 'successionStore'])->name('succession.store');
                Route::put('/succession-plan/{id}',    [JenjangKarirController::class, 'successionUpdate'])->name('succession.update');
                Route::delete('/succession-plan/{id}', [JenjangKarirController::class, 'successionDestroy'])->name('succession.destroy');
                Route::post('/succession-plan/{id}/kandidat',      [JenjangKarirController::class, 'successionKandidatStore'])->name('succession.kandidat.store');
                Route::delete('/succession-kandidat/{kandidatId}', [JenjangKarirController::class, 'successionKandidatDestroy'])->name('succession.kandidat.destroy');
            });

            // ── GTK RECRUITMENT ────────────────────────────────────
            Route::prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('/',       [GtkRecruitmentController::class, 'index'])->name('index');
                Route::get('/create', [GtkRecruitmentController::class, 'create'])->name('create');
                Route::post('/',      [GtkRecruitmentController::class, 'store'])->name('store');
                Route::prefix('{recruitmentUuid}')->group(function () {
                    Route::get('/',     [GtkRecruitmentController::class, 'show'])->name('show');
                    Route::get('/edit', [GtkRecruitmentController::class, 'edit'])->name('edit');
                    Route::put('/',     [GtkRecruitmentController::class, 'update'])->name('update');
                    Route::delete('/',  [GtkRecruitmentController::class, 'destroy'])->name('destroy');
                });
            });

            // ── ATS RECRUITMENT ────────────────────────────────────
            Route::prefix('ats')->name('ats.')->group(function () {
                Route::resource('jobs', JobController::class);
                Route::get('jobs/{job}/applications',   [JobController::class, 'applications'])->name('jobs.applications');
                Route::post('jobs/{job}/duplicate',     [JobController::class, 'duplicate'])->name('jobs.duplicate');
                Route::post('jobs/{job}/toggle-status', [JobController::class, 'toggleStatus'])->name('jobs.toggle-status');

                Route::get('jobs/{jobId}/pipeline',            [RecruitmentPipelineController::class, 'index'])->name('pipeline.index');
                Route::get('jobs/{jobId}/pipeline/board',      [RecruitmentPipelineController::class, 'board'])->name('pipeline.board');
                Route::get('jobs/{jobId}/pipeline/statistics', [RecruitmentPipelineController::class, 'getStatistics'])->name('pipeline.statistics');
                Route::post('jobs/{jobId}/pipeline/create',    [RecruitmentPipelineController::class, 'createPipeline'])->name('pipeline.create');

                Route::resource('applications', ApplicationController::class);
                Route::get('applications/{application}/stages',         [ApplicationController::class, 'stages'])->name('applications.stages');
                Route::post('applications/{application}/update-status', [ApplicationController::class, 'updateStatus'])->name('applications.update-status');
                Route::post('applications/{application}/add-note',      [ApplicationController::class, 'addNote'])->name('applications.add-note');
                Route::post('applications/{application}/send-message',  [ApplicationController::class, 'sendMessage'])->name('applications.send-message');
                Route::post('applications/bulk-action',                 [ApplicationController::class, 'bulkAction'])->name('applications.bulk-action');
                Route::get('applications/export/excel',                 [ApplicationController::class, 'exportExcel'])->name('applications.export-excel');
                Route::get('applications/export/pdf',                   [ApplicationController::class, 'exportPdf'])->name('applications.export-pdf');
                Route::post('applications/{applicationId}/move-next',     [RecruitmentPipelineController::class, 'moveToNextStage'])->name('pipeline.move-next');
                Route::post('applications/{applicationId}/move-to-stage', [RecruitmentPipelineController::class, 'moveToStage'])->name('pipeline.move-to-stage');

                Route::resource('candidates', CandidateController::class);
                Route::get('candidates/{candidate}/download-cv',             [CandidateController::class, 'downloadCv'])->name('candidates.download-cv');
                Route::get('candidates/{candidate}/timeline',                [CandidateController::class, 'timeline'])->name('candidates.timeline');
                Route::post('candidates/{candidate}/add-skill',              [CandidateController::class, 'addSkill'])->name('candidates.add-skill');
                Route::delete('candidates/{candidate}/remove-skill/{skill}', [CandidateController::class, 'removeSkill'])->name('candidates.remove-skill');

                Route::resource('interviews', InterviewController::class);
                Route::get('calendar-events',                       [InterviewController::class, 'calendarEvents'])->name('interviews.calendar-events');
                Route::post('interviews/{interview}/reschedule',    [InterviewController::class, 'reschedule'])->name('interviews.reschedule');
                Route::post('interviews/{interview}/complete',      [InterviewController::class, 'markComplete'])->name('interviews.complete');
                Route::post('interviews/{interview}/add-feedback',  [InterviewController::class, 'addFeedback'])->name('interviews.add-feedback');

                Route::get('reports',                      [ReportController::class, 'index'])->name('reports.index');
                Route::get('reports/dashboard',            [ReportController::class, 'dashboard'])->name('reports.dashboard');
                Route::get('reports/hiring-funnel',        [ReportController::class, 'hiringFunnel'])->name('reports.hiring-funnel');
                Route::get('reports/time-to-hire',         [ReportController::class, 'timeToHire'])->name('reports.time-to-hire');
                Route::get('reports/source-effectiveness', [ReportController::class, 'sourceEffectiveness'])->name('reports.source-effectiveness');
                Route::get('reports/export/{type}',        [ReportController::class, 'export'])->name('reports.export');
                Route::post('reports/schedule',            [ReportController::class, 'schedule'])->name('reports.schedule');

                Route::get('settings',                  [SettingController::class, 'index'])->name('settings.index');
                Route::post('settings',                 [SettingController::class, 'update'])->name('settings.update');
                Route::post('settings/stages',          [SettingController::class, 'updateStages'])->name('settings.stages');
                Route::post('settings/email-templates', [SettingController::class, 'updateEmailTemplates'])->name('settings.email-templates');
            });

            // ── GTK REQUESTS ───────────────────────────────────────
            Route::prefix('gtk-requests')->name('gtk-requests.')->group(function () {
                Route::get('/',             [GtkRequestController::class, 'index'])->name('index');
                Route::get('/create',       [GtkRequestController::class, 'create'])->name('create');
                Route::post('/',            [GtkRequestController::class, 'store'])->name('store');
                Route::post('/{id}/submit', [GtkRequestController::class, 'submit'])->name('submit');
                Route::prefix('{requestUuid}')->group(function () {
                    Route::get('/',     [GtkRequestController::class, 'show'])->name('show');
                    Route::get('/edit', [GtkRequestController::class, 'edit'])->name('edit');
                    Route::put('/',     [GtkRequestController::class, 'update'])->name('update');
                    Route::delete('/',  [GtkRequestController::class, 'destroy'])->name('destroy');
                });
            });

            // ── APPROVALS ──────────────────────────────────────────
            Route::prefix('approvals')->name('approvals.')->group(function () {
                Route::get('/',           [ApprovalController::class, 'index'])->name('index');
                Route::get('/my-pending', [ApprovalController::class, 'myPending'])->name('my-pending');
                Route::get('/history',    [ApprovalController::class, 'history'])->name('history');
                Route::prefix('{approvalUuid}')->group(function () {
                    Route::get('/',         [ApprovalController::class, 'show'])->name('show');
                    Route::post('/approve', [ApprovalController::class, 'approve'])->name('approve');
                    Route::post('/reject',  [ApprovalController::class, 'reject'])->name('reject');
                    Route::get('/track',    [ApprovalController::class, 'track'])->name('track');
                });
            });

            // ── ACADEMIC YEARS ─────────────────────────────────────
            Route::prefix('academic-years')->name('academic-years.')->group(function () {
                Route::get('/',                   [AcademicYearController::class, 'index'])->name('index');
                Route::get('/create',             [AcademicYearController::class, 'create'])->name('create');
                Route::post('/',                  [AcademicYearController::class, 'store'])->name('store');
                Route::get('/{id}',              [AcademicYearController::class, 'show'])->name('show');
                Route::get('/{id}/edit',          [AcademicYearController::class, 'edit'])->name('edit');
                Route::put('/{id}',              [AcademicYearController::class, 'update'])->name('update');
                Route::delete('/{id}',            [AcademicYearController::class, 'destroy'])->name('destroy');
                Route::get('/{id}/toggle-active', [AcademicYearController::class, 'toggleActive'])->name('toggle-active');
            });

            // ── GRADE LEVELS ───────────────────────────────────────
            Route::prefix('grade-levels')->name('grade-levels.')->group(function () {
                Route::get('/',          [GradeLevelController::class, 'index'])->name('index');
                Route::get('/create',    [GradeLevelController::class, 'create'])->name('create');
                Route::post('/',         [GradeLevelController::class, 'store'])->name('store');
                Route::get('/{id}',     [GradeLevelController::class, 'show'])->name('show');
                Route::get('/{id}/edit',[GradeLevelController::class, 'edit'])->name('edit');
                Route::put('/{id}',     [GradeLevelController::class, 'update'])->name('update');
                Route::delete('/{id}',  [GradeLevelController::class, 'destroy'])->name('destroy');
                Route::post('/{id}/subjects/add',   [GradeLevelController::class, 'addSubject'])->name('subjects.add');
                Route::delete('/{id}/subjects/remove', [GradeLevelController::class, 'removeSubject'])->name('subjects.remove');
                Route::post('/{id}/kktp',   [GradeLevelController::class, 'saveKktp'])->name('kktp.save');
            });

            // ── SUBJECTS ───────────────────────────────────────────
            Route::prefix('subjects')->name('subjects.')->group(function () {
                Route::get('/',          [SubjectController::class, 'index'])->name('index');
                Route::get('/create',    [SubjectController::class, 'create'])->name('create');
                Route::post('/',         [SubjectController::class, 'store'])->name('store');
                Route::get('/{id}',     [SubjectController::class, 'show'])->name('show');
                Route::get('/{id}/edit',[SubjectController::class, 'edit'])->name('edit');
                Route::put('/{id}',     [SubjectController::class, 'update'])->name('update');
                Route::delete('/{id}',  [SubjectController::class, 'destroy'])->name('destroy');
            });

            // ── INSTITUTION DECREES ────────────────────────────────
            Route::prefix('institution-decrees')->name('institution-decrees.')->group(function () {
                Route::get('/',          [InstitutionDecreeController::class, 'index'])->name('index');
                Route::get('/create',    [InstitutionDecreeController::class, 'create'])->name('create');
                Route::post('/',         [InstitutionDecreeController::class, 'store'])->name('store');
                Route::get('/{id}',     [InstitutionDecreeController::class, 'show'])->name('show');
                Route::get('/{id}/print', [InstitutionDecreeController::class, 'print'])->name('print');
                Route::get('/{id}/edit',[InstitutionDecreeController::class, 'edit'])->name('edit');
                Route::put('/{id}',     [InstitutionDecreeController::class, 'update'])->name('update');
                Route::delete('/{id}',  [InstitutionDecreeController::class, 'destroy'])->name('destroy');
            });

            // ── DOKUMEN ISO ─────────────────────────────────────────
            Route::prefix('dokumen-iso')->name('dokumen-iso.')->group(function () {
                Route::get('/',        [DokumenIsoController::class, 'index'])->name('index');
                Route::post('/',       [DokumenIsoController::class, 'store'])->name('store');
                Route::put('/{id}',   [DokumenIsoController::class, 'update'])->name('update');
                Route::delete('/{id}', [DokumenIsoController::class, 'destroy'])->name('destroy');
            });

            // ── DIVISI ──────────────────────────────────────────────
            Route::prefix('divisi')->name('divisi.')->group(function () {
                Route::get('/',        [DivisiController::class, 'index'])->name('index');
                Route::post('/',       [DivisiController::class, 'store'])->name('store');
                Route::put('/{id}',   [DivisiController::class, 'update'])->name('update');
                Route::delete('/{id}', [DivisiController::class, 'destroy'])->name('destroy');
            });

            // ── STUDY GROUPS API (for matrix builder) ─────────────
            Route::get('/study-groups/by-school', [StudyGroupApiController::class, 'bySchool'])->name('study-groups.by-school');

            // ── TEACHING ASSIGNMENTS ───────────────────────────────
            Route::prefix('teaching-assignments')->name('teaching-assignments.')->group(function () {
                Route::get('/',          [TeachingAssignmentController::class, 'index'])->name('index');
                Route::get('/create',    [TeachingAssignmentController::class, 'create'])->name('create');
                Route::post('/',         [TeachingAssignmentController::class, 'store'])->name('store');
                // Matriks (literal, harus sebelum /{id})
                Route::get('/matrix/{decree_id}/edit', [TeachingAssignmentController::class, 'editMatrix'])->name('edit-matrix');
                Route::put('/matrix/{decree_id}',      [TeachingAssignmentController::class, 'updateMatrix'])->name('update-matrix');
                Route::get('/{id}',     [TeachingAssignmentController::class, 'show'])->name('show');
                Route::get('/{id}/edit',[TeachingAssignmentController::class, 'edit'])->name('edit');
                Route::put('/{id}',     [TeachingAssignmentController::class, 'update'])->name('update');
                Route::delete('/{id}',  [TeachingAssignmentController::class, 'destroy'])->name('destroy');
            });

            // ── OTHER TEACHER TASKS (Tugas Tambahan GTK) ─────────────
            Route::prefix('other-teacher-tasks')->name('other-teacher-tasks.')->group(function () {
                Route::get('/',          [OtherTeacherTaskController::class, 'index'])->name('index');
                Route::post('/',         [OtherTeacherTaskController::class, 'store'])->name('store');
                Route::put('/{id}',     [OtherTeacherTaskController::class, 'update'])->name('update');
                Route::delete('/{id}',  [OtherTeacherTaskController::class, 'destroy'])->name('destroy');
            });

            // ── GTK ADDITIONAL TASKS ───────────────────────────────
            Route::prefix('gtk-additional-tasks')->name('gtk-additional-tasks.')->group(function () {
                Route::get('/',          [\App\Http\Controllers\GtkAdditionalTaskController::class, 'index'])->name('index');
                Route::get('/create',    [\App\Http\Controllers\GtkAdditionalTaskController::class, 'create'])->name('create');
                Route::post('/',         [\App\Http\Controllers\GtkAdditionalTaskController::class, 'store'])->name('store');
                Route::get('/{id}/edit', [\App\Http\Controllers\GtkAdditionalTaskController::class, 'edit'])->name('edit');
                Route::put('/{id}',      [\App\Http\Controllers\GtkAdditionalTaskController::class, 'update'])->name('update');
                Route::delete('/{id}',   [\App\Http\Controllers\GtkAdditionalTaskController::class, 'destroy'])->name('destroy');
            });

            // ── STUDY GROUPS ───────────────────────────────────────
            Route::prefix('study-groups')->name('study-groups.')->group(function () {
                Route::get('/',          [StudyGroupController::class, 'index'])->name('index');
                Route::get('/create',    [StudyGroupController::class, 'create'])->name('create');
                Route::post('/',         [StudyGroupController::class, 'store'])->name('store');
                Route::get('/{id}',     [StudyGroupController::class, 'show'])->name('show');
                Route::get('/{id}/edit',[StudyGroupController::class, 'edit'])->name('edit');
                Route::put('/{id}',     [StudyGroupController::class, 'update'])->name('update');
                Route::delete('/{id}',  [StudyGroupController::class, 'destroy'])->name('destroy');
            });

            // ── STUDENT PROMOTIONS ────────────────────────────────
            Route::prefix('student-promotions')->name('student-promotions.')->group(function () {
                Route::get('/',                        [StudentPromotionController::class, 'index'])->name('index');
                Route::get('/create',                  [StudentPromotionController::class, 'create'])->name('create');
                Route::post('/',                       [StudentPromotionController::class, 'store'])->name('store');
                Route::get('/{id}',                   [StudentPromotionController::class, 'show'])->name('show');
                Route::put('/{id}/cancel',            [StudentPromotionController::class, 'cancel'])->name('cancel');
                Route::put('/{id}/execute',            [StudentPromotionController::class, 'execute'])->name('execute');
                Route::delete('/{id}',                 [StudentPromotionController::class, 'destroy'])->name('destroy');
                // Detail update
                Route::put('/{id}/detail/{detailId}',  [StudentPromotionController::class, 'updateDetail'])->name('update-detail');
            });

            // ── STUDENTS ───────────────────────────────────────────
            Route::prefix('students')->name('students.')->group(function () {
                Route::get('/',               [StudentController::class, 'index'])->name('index');
                Route::get('/create',        [StudentController::class, 'create'])->name('create');
                Route::post('/',             [StudentController::class, 'store'])->name('store');
                Route::get('/find-student',  [StudentController::class, 'findStudent'])->name('find-student');
                Route::get('/{santriUuid}',        [StudentController::class, 'show'])->name('show');
                Route::get('/{santriUuid}/edit',   [StudentController::class, 'edit'])->name('edit');
                Route::put('/{santriUuid}',        [StudentController::class, 'update'])->name('update');
                Route::delete('/{santriUuid}',     [StudentController::class, 'destroy'])->name('destroy');

                // Student Mahroms
                Route::get('/{santriUuid}/mahrom',            [StudentMahromController::class, 'index'])->name('mahroms.index');
                Route::get('/{santriUuid}/mahrom/tambah',    [StudentMahromController::class, 'create'])->name('mahroms.create');
                Route::post('/{santriUuid}/mahrom',          [StudentMahromController::class, 'store'])->name('mahroms.store');
                Route::get('/{santriUuid}/mahrom/{mahromUuid}',       [StudentMahromController::class, 'show'])->name('mahroms.show');
                Route::get('/{santriUuid}/mahrom/{mahromUuid}/edit',  [StudentMahromController::class, 'edit'])->name('mahroms.edit');
                Route::put('/{santriUuid}/mahrom/{mahromUuid}',       [StudentMahromController::class, 'update'])->name('mahroms.update');
                Route::delete('/{santriUuid}/mahrom/{mahromUuid}',    [StudentMahromController::class, 'destroy'])->name('mahroms.destroy');
            });

            // ── MUTASI MASUK ───────────────────────────────────────
            Route::prefix('mutations-in')->name('mutations-in.')->group(function () {
                Route::get('/',                        [StudentMutationInController::class, 'index'])->name('index');
                Route::get('/create',                  [StudentMutationInController::class, 'create'])->name('create');
                Route::post('/',                       [StudentMutationInController::class, 'store'])->name('store');
                Route::get('/hijri-convert',            [StudentMutationInController::class, 'hijriConvert'])->name('hijri-convert');
                Route::get('/{mutationUuid}',          [StudentMutationInController::class, 'show'])->name('show');
                Route::post('/{mutationUuid}/submit',  [StudentMutationInController::class, 'submit'])->name('submit');
                Route::post('/{mutationUuid}/approve', [StudentMutationInController::class, 'approve'])->name('approve');
                Route::post('/{mutationUuid}/reject',  [StudentMutationInController::class, 'reject'])->name('reject');
                Route::delete('/{mutationUuid}',       [StudentMutationInController::class, 'destroy'])->name('destroy');
                Route::get('/{mutationUuid}/print',    [StudentMutationInController::class, 'print'])->name('print');
            });

            // ── MUTASI KELUAR ──────────────────────────────────────
            Route::prefix('mutations-out')->name('mutations-out.')->group(function () {
                Route::get('/',                        [StudentMutationOutController::class, 'index'])->name('index');
                Route::get('/create',                  [StudentMutationOutController::class, 'create'])->name('create');
                Route::post('/',                       [StudentMutationOutController::class, 'store'])->name('store');
                Route::get('/find-student',            [StudentMutationOutController::class, 'findStudent'])->name('find-student');
                Route::get('/hijri-convert',           [StudentMutationOutController::class, 'hijriConvert'])->name('hijri-convert');
                Route::get('/{mutationUuid}',          [StudentMutationOutController::class, 'show'])->name('show');
                Route::post('/{mutationUuid}/submit',  [StudentMutationOutController::class, 'submit'])->name('submit');
                Route::post('/{mutationUuid}/approve', [StudentMutationOutController::class, 'approve'])->name('approve');
                Route::post('/{mutationUuid}/reject',  [StudentMutationOutController::class, 'reject'])->name('reject');
                Route::delete('/{mutationUuid}',       [StudentMutationOutController::class, 'destroy'])->name('destroy');
                Route::get('/{mutationUuid}/print',    [StudentMutationOutController::class, 'print'])->name('print');
            });

            // ── LULUS ──────────────────────────────────────────────
            Route::prefix('mutations-lulus')->name('mutations-lulus.')->group(function () {
                Route::get('/',                        [StudentMutationOutController::class, 'index'])->name('index');
                Route::get('/create',                  [StudentMutationOutController::class, 'create'])->name('create');
                Route::post('/',                       [StudentMutationOutController::class, 'store'])->name('store');
                Route::get('/find-student',            [StudentMutationOutController::class, 'findStudent'])->name('find-student');
                Route::get('/{mutationUuid}',          [StudentMutationOutController::class, 'show'])->name('show');
                Route::post('/{mutationUuid}/submit',  [StudentMutationOutController::class, 'submit'])->name('submit');
                Route::post('/{mutationUuid}/approve', [StudentMutationOutController::class, 'approve'])->name('approve');
                Route::post('/{mutationUuid}/reject',  [StudentMutationOutController::class, 'reject'])->name('reject');
                Route::delete('/{mutationUuid}',       [StudentMutationOutController::class, 'destroy'])->name('destroy');
                Route::get('/{mutationUuid}/print',    [StudentMutationOutController::class, 'print'])->name('print');
            });

            // ── DROP OUT ───────────────────────────────────────────
            Route::prefix('mutations-do')->name('mutations-do.')->group(function () {
                Route::get('/',                        [StudentMutationOutController::class, 'index'])->name('index');
                Route::get('/create',                  [StudentMutationOutController::class, 'create'])->name('create');
                Route::post('/',                       [StudentMutationOutController::class, 'store'])->name('store');
                Route::get('/find-student',            [StudentMutationOutController::class, 'findStudent'])->name('find-student');
                Route::get('/{mutationUuid}',          [StudentMutationOutController::class, 'show'])->name('show');
                Route::post('/{mutationUuid}/submit',  [StudentMutationOutController::class, 'submit'])->name('submit');
                Route::post('/{mutationUuid}/approve', [StudentMutationOutController::class, 'approve'])->name('approve');
                Route::post('/{mutationUuid}/reject',  [StudentMutationOutController::class, 'reject'])->name('reject');
                Route::delete('/{mutationUuid}',       [StudentMutationOutController::class, 'destroy'])->name('destroy');
                Route::get('/{mutationUuid}/print',    [StudentMutationOutController::class, 'print'])->name('print');
            });

            // ── MUTASI MASUK ───────────────────────────────────────
            Route::prefix('mutations-in')->name('mutations-in.')->group(function () {
                Route::get('/',                        [StudentMutationInController::class, 'index'])->name('index');
                Route::get('/create',                  [StudentMutationInController::class, 'create'])->name('create');
                Route::post('/',                       [StudentMutationInController::class, 'store'])->name('store');
                Route::get('/find-student',            [StudentMutationInController::class, 'findStudent'])->name('find-student');
                Route::get('/{mutationUuid}',          [StudentMutationInController::class, 'show'])->name('show');
                Route::post('/{mutationUuid}/submit',  [StudentMutationInController::class, 'submit'])->name('submit');
                Route::post('/{mutationUuid}/approve', [StudentMutationInController::class, 'approve'])->name('approve');
                Route::post('/{mutationUuid}/reject',  [StudentMutationInController::class, 'reject'])->name('reject');
                Route::delete('/{mutationUuid}',       [StudentMutationInController::class, 'destroy'])->name('destroy');
                Route::get('/{mutationUuid}/print',    [StudentMutationInController::class, 'print'])->name('print');
            });

            // ── POIN PELANGGARAN ───────────────────────────────────
            Route::prefix('violation-points')->name('violation-points.')->group(function () {
                Route::get('/',                    [ViolationPointController::class, 'index'])->name('index');
                Route::get('/create',              [ViolationPointController::class, 'create'])->name('create');
                Route::post('/',                   [ViolationPointController::class, 'store'])->name('store');
                Route::get('/find-student',        [ViolationPointController::class, 'findStudent'])->name('find-student');
                Route::get('/dashboard',           [ViolationPointController::class, 'dashboard'])->name('dashboard');
                Route::get('/recap',               [ViolationPointController::class, 'recap'])->name('recap');
                Route::get('/recap/{studentUuid}', [ViolationPointController::class, 'recapDetail'])->name('recap.detail');
                Route::get('/export-pdf',          [ViolationPointController::class, 'exportPdf'])->name('export-pdf');
                // Wildcard di bawah semua literal
                Route::get('/{violationUuid}',       [ViolationPointController::class, 'show'])->name('show');
                Route::get('/{violationUuid}/edit',  [ViolationPointController::class, 'edit'])->name('edit');
                Route::put('/{violationUuid}',       [ViolationPointController::class, 'update'])->name('update');
                Route::delete('/{violationUuid}',    [ViolationPointController::class, 'destroy'])->name('destroy');
            });

            // ── ABSENSI HARIAN PESERTA DIDIK ───────────────────────
            Route::prefix('absensi/harian')->name('absensi.harian.')->group(function () {
                Route::get('/',                         [App\Http\Controllers\AbsensiHarianController::class, 'index'])->name('index');
                Route::get('/create',                   [App\Http\Controllers\AbsensiHarianController::class, 'create'])->name('create');
                Route::post('/',                        [App\Http\Controllers\AbsensiHarianController::class, 'store'])->name('store');
                Route::get('/recap/detail',             [App\Http\Controllers\AbsensiHarianController::class, 'recapDetail'])->name('recap.detail');
                Route::get('/recap/semester',           [App\Http\Controllers\AbsensiHarianController::class, 'recapSemester'])->name('recap.semester');
                Route::get('/recap',                    [App\Http\Controllers\AbsensiHarianController::class, 'recap'])->name('recap');
                Route::get('/{studentUuid}',            [App\Http\Controllers\AbsensiHarianController::class, 'show'])->name('show');
                Route::get('/{studentUuid}/export',     [App\Http\Controllers\AbsensiHarianController::class, 'exportStudent'])->name('export.student');
            });

            // ═══════════════════════════════════════════════════════════════
            //  PENGELOLAAN ASRAMA (MASTER — CRUD Standalone)
            // ═══════════════════════════════════════════════════════════════
            Route::prefix('dormitory-master')->name('dormitory-master.')->group(function () {
                Route::get('/',                        [DormitoryMasterController::class, 'index'])->name('index');
                Route::get('/create',                  [DormitoryMasterController::class, 'create'])->name('create');
                Route::post('/',                       [DormitoryMasterController::class, 'store'])->name('store');
                Route::get('/{asramaUuid}',            [DormitoryMasterController::class, 'show'])->name('show');
                Route::get('/{asramaUuid}/edit',       [DormitoryMasterController::class, 'edit'])->name('edit');
                Route::put('/{asramaUuid}',             [DormitoryMasterController::class, 'update'])->name('update');
                Route::delete('/{asramaUuid}',         [DormitoryMasterController::class, 'destroy'])->name('destroy');
            });

            // ═══════════════════════════════════════════════════════════════
            //  ASRAMA / DORMITORY
            // ═══════════════════════════════════════════════════════════════
            Route::prefix('asrama')->name('asrama.')->group(function () {

                // ── ASRAMA UTAMA ─────────────────────────────────────
                Route::get('/',                        [DormitoryController::class, 'index'])->name('index');
                Route::get('/create',                  [DormitoryController::class, 'create'])->name('create');
                Route::post('/',                       [DormitoryController::class, 'store'])->name('store');
                Route::get('/{asramaUuid}',             [DormitoryController::class, 'show'])->name('show');
                Route::get('/{asramaUuid}/edit',        [DormitoryController::class, 'edit'])->name('edit');
                Route::put('/{asramaUuid}',             [DormitoryController::class, 'update'])->name('update');
                Route::delete('/{asramaUuid}',          [DormitoryController::class, 'destroy'])->name('destroy');

                // ── STANDALONE WING CRUD ───────────────────────────────────
                Route::get('/{asramaUuid}/gedung',            [DormitoryWingController::class, 'index'])->name('wings.index');
                Route::get('/{asramaUuid}/gedung/buat',       [DormitoryWingController::class, 'create'])->name('wings.create');
                Route::post('/{asramaUuid}/gedung',           [DormitoryWingController::class, 'store'])->name('wings.store');
                Route::get('/{asramaUuid}/gedung/{wingUuid}',  [DormitoryWingController::class, 'show'])->name('wings.show');
                Route::get('/{asramaUuid}/gedung/{wingUuid}/edit', [DormitoryWingController::class, 'edit'])->name('wings.edit');
                Route::put('/{asramaUuid}/gedung/{wingUuid}', [DormitoryWingController::class, 'update'])->name('wings.update');
                Route::delete('/{asramaUuid}/gedung/{wingUuid}', [DormitoryWingController::class, 'destroy'])->name('wings.destroy');

                // ── STANDALONE ROOM CRUD ─────────────────────────────────
                Route::get('/{asramaUuid}/kamar',            [DormitoryRoomController::class, 'index'])->name('rooms.index');
                Route::get('/{asramaUuid}/kamar/buat',       [DormitoryRoomController::class, 'create'])->name('rooms.create');
                Route::post('/{asramaUuid}/kamar',           [DormitoryRoomController::class, 'store'])->name('rooms.store');
                Route::get('/{asramaUuid}/kamar/{roomUuid}', [DormitoryRoomController::class, 'show'])->name('rooms.show');
                Route::get('/{asramaUuid}/kamar/{roomUuid}/edit', [DormitoryRoomController::class, 'edit'])->name('rooms.edit');
                Route::put('/{asramaUuid}/kamar/{roomUuid}', [DormitoryRoomController::class, 'update'])->name('rooms.update');
                Route::delete('/{asramaUuid}/kamar/{roomUuid}', [DormitoryRoomController::class, 'destroy'])->name('rooms.destroy');

                // ── ROOM API ─────────────────────────────────────────────
                Route::get('/{asramaUuid}/kamar/{roomUuid}/penghuni-tersedia', [DormitoryRoomApiController::class, 'availableResidents'])->name('api.rooms.available-residents');
                Route::post('/{asramaUuid}/kamar/{roomUuid}/penghuni-massal',  [DormitoryRoomApiController::class, 'bulkAddResidents'])->name('api.rooms.bulk-add-residents');
                Route::post('/{asramaUuid}/kamar/{roomUuid}/penghuni-keluar',  [DormitoryRoomApiController::class, 'removeResident'])->name('api.rooms.remove-resident');

                // API Helpers
                Route::get('/api/wings',               [DormitoryController::class, 'apiWingsByDormitory'])->name('api.wings');
                Route::get('/api/rooms',               [DormitoryController::class, 'apiRoomsByWing'])->name('api.rooms');

                // ── MUTASI KAMAR ───────────────────────────────────────
                Route::get('/{asramaUuid}/mutasi-kamar',             [DormitoryRoomMoveController::class, 'index'])->name('room-moves.index');
                Route::get('/{asramaUuid}/mutasi-kamar/aju',          [DormitoryRoomMoveController::class, 'create'])->name('room-moves.create');
                Route::post('/{asramaUuid}/mutasi-kamar',             [DormitoryRoomMoveController::class, 'store'])->name('room-moves.store');
                Route::get('/{asramaUuid}/mutasi-kamar/{moveUuid}',    [DormitoryRoomMoveController::class, 'show'])->name('room-moves.show');
                Route::post('/{asramaUuid}/mutasi-kamar/{moveUuid}/approve', [DormitoryRoomMoveController::class, 'approve'])->name('room-moves.approve');
                Route::post('/{asramaUuid}/mutasi-kamar/{moveUuid}/reject',  [DormitoryRoomMoveController::class, 'reject'])->name('room-moves.reject');

                // ── PENGHUNI ──────────────────────────────────────────
                Route::get('/{asramaUuid}/penghuni',             [DormitoryResidentController::class, 'index'])->name('residents.index');
                Route::get('/{asramaUuid}/penghuni/tambah',      [DormitoryResidentController::class, 'create'])->name('residents.create');
                Route::post('/{asramaUuid}/penghuni',            [DormitoryResidentController::class, 'store'])->name('residents.store');
                Route::get('/{asramaUuid}/penghuni/find-student', [DormitoryResidentController::class, 'findStudent'])->name('residents.find-student');
                Route::get('/{asramaUuid}/penghuni/{residentUuid}', [DormitoryResidentController::class, 'show'])->name('residents.show');
                Route::post('/{asramaUuid}/penghuni/{residentUuid}/checkout', [DormitoryResidentController::class, 'checkout'])->name('residents.checkout');

                // ── ABSENSI ───────────────────────────────────────────
                Route::get('/{asramaUuid}/absensi',            [DormitoryAttendanceController::class, 'index'])->name('attendance.index');
                Route::get('/{asramaUuid}/absensi/catat',       [DormitoryAttendanceController::class, 'create'])->name('attendance.create');
                Route::post('/{asramaUuid}/absensi',           [DormitoryAttendanceController::class, 'store'])->name('attendance.store');
                Route::post('/{asramaUuid}/absensi/verify',     [DormitoryAttendanceController::class, 'verify'])->name('attendance.verify');
                Route::get('/{asramaUuid}/absensi/rekap',      [DormitoryAttendanceController::class, 'recap'])->name('attendance.recap');

                // ── PERIZINAN ────────────────────────────────────────
                Route::get('/{asramaUuid}/izin',              [DormitoryPermitController::class, 'index'])->name('permits.index');
                Route::get('/{asramaUuid}/izin/aju',          [DormitoryPermitController::class, 'create'])->name('permits.create');
                Route::post('/{asramaUuid}/izin',             [DormitoryPermitController::class, 'store'])->name('permits.store');
                Route::get('/{asramaUuid}/izin/{permitUuid}', [DormitoryPermitController::class, 'show'])->name('permits.show');
                Route::post('/{asramaUuid}/izin/{permitUuid}/approve',  [DormitoryPermitController::class, 'approve'])->name('permits.approve');
                Route::post('/{asramaUuid}/izin/{permitUuid}/reject',   [DormitoryPermitController::class, 'reject'])->name('permits.reject');
                Route::post('/{asramaUuid}/izin/{permitUuid}/return',    [DormitoryPermitController::class, 'returnRecord'])->name('permits.return');

                // ── PELANGGARAN ─────────────────────────────────────
                Route::get('/{asramaUuid}/pelanggaran',             [DormitoryViolationController::class, 'index'])->name('violations.index');
                Route::get('/{asramaUuid}/pelanggaran/tambah',      [DormitoryViolationController::class, 'create'])->name('violations.create');
                Route::post('/{asramaUuid}/pelanggaran',           [DormitoryViolationController::class, 'store'])->name('violations.store');
                Route::get('/{asramaUuid}/pelanggaran/{violationUuid}', [DormitoryViolationController::class, 'show'])->name('violations.show');
                Route::post('/{asramaUuid}/pelanggaran/{violationUuid}/notify', [DormitoryViolationController::class, 'notifyParent'])->name('violations.notify');

                // ── INFORMASI & BROADCAST ─────────────────────────────
                Route::get('/{asramaUuid}/informasi',          [DormitoryPostController::class, 'index'])->name('posts.index');
                Route::get('/{asramaUuid}/informasi/buat',     [DormitoryPostController::class, 'create'])->name('posts.create');
                Route::post('/{asramaUuid}/informasi',        [DormitoryPostController::class, 'store'])->name('posts.store');
                Route::get('/{asramaUuid}/informasi/{postUuid}',    [DormitoryPostController::class, 'show'])->name('posts.show');
                Route::get('/{asramaUuid}/informasi/{postUuid}/edit', [DormitoryPostController::class, 'edit'])->name('posts.edit');
                Route::put('/{asramaUuid}/informasi/{postUuid}',     [DormitoryPostController::class, 'update'])->name('posts.update');
                Route::delete('/{asramaUuid}/informasi/{postUuid}',  [DormitoryPostController::class, 'destroy'])->name('posts.destroy');

                // Activity Templates
                Route::get('/{asramaUuid}/template-kegiatan',   [DormitoryPostController::class, 'templateIndex'])->name('templates.index');
                Route::post('/{asramaUuid}/template-kegiatan', [DormitoryPostController::class, 'templateStore'])->name('templates.store');
                Route::post('/{asramaUuid}/template-kegiatan/{session}/toggle', [DormitoryPostController::class, 'templateToggle'])->name('templates.toggle');

                // Activity Logs
                Route::get('/{asramaUuid}/log-kegiatan',       [DormitoryPostController::class, 'activityLogIndex'])->name('activities.index');

                // Emergency Broadcast
                Route::get('/{asramaUuid}/broadcast',          [DormitoryPostController::class, 'broadcastIndex'])->name('broadcasts.index');
                Route::post('/{asramaUuid}/broadcast',        [DormitoryPostController::class, 'broadcastStore'])->name('broadcasts.store');

                // ── INVENTARIS KAMAR ───────────────────────────────────
                Route::get('/{asramaUuid}/inventaris',              [DormitoryInventoryController::class, 'index'])->name('inventories.index');
                Route::get('/{asramaUuid}/inventaris/tambah',       [DormitoryInventoryController::class, 'create'])->name('inventories.create');
                Route::post('/{asramaUuid}/inventaris',             [DormitoryInventoryController::class, 'store'])->name('inventories.store');
                Route::get('/{asramaUuid}/inventaris/{itemUuid}/edit', [DormitoryInventoryController::class, 'edit'])->name('inventories.edit');
                Route::put('/{asramaUuid}/inventaris/{itemUuid}',    [DormitoryInventoryController::class, 'update'])->name('inventories.update');
                Route::delete('/{asramaUuid}/inventaris/{itemUuid}', [DormitoryInventoryController::class, 'destroy'])->name('inventories.destroy');

                // ── KUNJUNGAN ────────────────────────────────────────
                Route::get('/{asramaUuid}/kunjungan',           [DormitoryVisitLogController::class, 'index'])->name('visits.index');
                Route::get('/{asramaUuid}/kunjungan/aju',      [DormitoryVisitLogController::class, 'create'])->name('visits.create');
                Route::post('/{asramaUuid}/kunjungan',        [DormitoryVisitLogController::class, 'store'])->name('visits.store');
                Route::get('/{asramaUuid}/kunjungan/{visitUuid}',   [DormitoryVisitLogController::class, 'show'])->name('visits.show');
                Route::post('/{asramaUuid}/kunjungan/{visitUuid}/approve', [DormitoryVisitLogController::class, 'approve'])->name('visits.approve');
                Route::post('/{asramaUuid}/kunjungan/{visitUuid}/reject',  [DormitoryVisitLogController::class, 'reject'])->name('visits.reject');
                Route::post('/{asramaUuid}/kunjungan/{visitUuid}/check-in',  [DormitoryVisitLogController::class, 'checkIn'])->name('visits.check-in');
                Route::post('/{asramaUuid}/kunjungan/{visitUuid}/check-out', [DormitoryVisitLogController::class, 'checkOut'])->name('visits.check-out');
            });

            // ═══════════════════════════════════════════════════════════════
            //  UKS — UKS MODULES
            // ═══════════════════════════════════════════════════════════════
            Route::prefix('uks')->name('uks.')->group(function () {

                // Imunisasi
                Route::get('/immunizations',               [StudentImmunizationController::class, 'index'])->name('immunizations.index');
                Route::get('/immunizations/create',         [StudentImmunizationController::class, 'create'])->name('immunizations.create');
                Route::post('/immunizations',              [StudentImmunizationController::class, 'store'])->name('immunizations.store');
                Route::get('/immunizations/{uuid}',        [StudentImmunizationController::class, 'show'])->name('immunizations.show');
                Route::get('/immunizations/{uuid}/edit',  [StudentImmunizationController::class, 'edit'])->name('immunizations.edit');
                Route::put('/immunizations/{uuid}',       [StudentImmunizationController::class, 'update'])->name('immunizations.update');
                Route::delete('/immunizations/{uuid}',    [StudentImmunizationController::class, 'destroy'])->name('immunizations.destroy');
                Route::get('/immunizations/student/{studentUuid}', [StudentImmunizationController::class, 'byStudent'])->name('immunizations.by-student');

                // Medical Check-up
                Route::get('/health-checkups',             [StudentHealthCheckupController::class, 'index'])->name('health-checkups.index');
                Route::get('/health-checkups/create',      [StudentHealthCheckupController::class, 'create'])->name('health-checkups.create');
                Route::post('/health-checkups',            [StudentHealthCheckupController::class, 'store'])->name('health-checkups.store');
                Route::get('/health-checkups/{uuid}',       [StudentHealthCheckupController::class, 'show'])->name('health-checkups.show');
                Route::get('/health-checkups/{uuid}/edit',  [StudentHealthCheckupController::class, 'edit'])->name('health-checkups.edit');
                Route::put('/health-checkups/{uuid}',      [StudentHealthCheckupController::class, 'update'])->name('health-checkups.update');
                Route::delete('/health-checkups/{uuid}',  [StudentHealthCheckupController::class, 'destroy'])->name('health-checkups.destroy');

                // Izin Sakit
                Route::get('/health-permits',               [StudentHealthPermitController::class, 'index'])->name('health-permits.index');
                Route::get('/health-permits/create',        [StudentHealthPermitController::class, 'create'])->name('health-permits.create');
                Route::post('/health-permits',             [StudentHealthPermitController::class, 'store'])->name('health-permits.store');
                Route::get('/health-permits/{uuid}',        [StudentHealthPermitController::class, 'show'])->name('health-permits.show');
                Route::get('/health-permits/{uuid}/edit',  [StudentHealthPermitController::class, 'edit'])->name('health-permits.edit');
                Route::put('/health-permits/{uuid}',       [StudentHealthPermitController::class, 'update'])->name('health-permits.update');
                Route::delete('/health-permits/{uuid}',    [StudentHealthPermitController::class, 'destroy'])->name('health-permits.destroy');
                Route::post('/health-permits/{uuid}/approve',       [StudentHealthPermitController::class, 'approve'])->name('health-permits.approve');
                Route::post('/health-permits/{uuid}/reject',        [StudentHealthPermitController::class, 'reject'])->name('health-permits.reject');
                Route::post('/health-permits/{uuid}/notify-parent', [StudentHealthPermitController::class, 'notifyParent'])->name('health-permits.notify-parent');

                // Inventori Obat
                Route::get('/medicine-inventory',          [StudentMedicineInventoryController::class, 'index'])->name('medicine-inventory.index');
                Route::get('/medicine-inventory/create',   [StudentMedicineInventoryController::class, 'create'])->name('medicine-inventory.create');
                Route::post('/medicine-inventory',         [StudentMedicineInventoryController::class, 'store'])->name('medicine-inventory.store');
                Route::get('/medicine-inventory/{uuid}',   [StudentMedicineInventoryController::class, 'show'])->name('medicine-inventory.show');
                Route::get('/medicine-inventory/{uuid}/edit', [StudentMedicineInventoryController::class, 'edit'])->name('medicine-inventory.edit');
                Route::put('/medicine-inventory/{uuid}',   [StudentMedicineInventoryController::class, 'update'])->name('medicine-inventory.update');
                Route::delete('/medicine-inventory/{uuid}', [StudentMedicineInventoryController::class, 'destroy'])->name('medicine-inventory.destroy');

                // Pemberian Obat
                Route::get('/medicine-logs',               [StudentMedicineLogController::class, 'index'])->name('medicine-logs.index');
                Route::get('/medicine-logs/create',        [StudentMedicineLogController::class, 'create'])->name('medicine-logs.create');
                Route::post('/medicine-logs',              [StudentMedicineLogController::class, 'store'])->name('medicine-logs.store');
                Route::get('/medicine-logs/{uuid}',        [StudentMedicineLogController::class, 'show'])->name('medicine-logs.show');
                Route::delete('/medicine-logs/{uuid}',    [StudentMedicineLogController::class, 'destroy'])->name('medicine-logs.destroy');

                // Antropometri
                Route::get('/health-metrics',              [StudentHealthMetricController::class, 'index'])->name('health-metrics.index');
                Route::get('/health-metrics/dashboard',   [StudentHealthMetricController::class, 'dashboard'])->name('health-metrics.dashboard');
                Route::get('/health-metrics/student/{studentUuid}', [StudentHealthMetricController::class, 'studentChart'])->name('health-metrics.student');
                Route::get('/health-metrics/create',      [StudentHealthMetricController::class, 'create'])->name('health-metrics.create');
                Route::post('/health-metrics',            [StudentHealthMetricController::class, 'store'])->name('health-metrics.store');
                Route::get('/health-metrics/{uuid}',      [StudentHealthMetricController::class, 'show'])->name('health-metrics.show');
                Route::get('/health-metrics/{uuid}/edit', [StudentHealthMetricController::class, 'edit'])->name('health-metrics.edit');
                Route::put('/health-metrics/{uuid}',      [StudentHealthMetricController::class, 'update'])->name('health-metrics.update');
                Route::delete('/health-metrics/{uuid}',   [StudentHealthMetricController::class, 'destroy'])->name('health-metrics.destroy');

                // Konseling
                Route::get('/counseling-records',           [StudentCounselingRecordController::class, 'index'])->name('counseling-records.index');
                Route::get('/counseling-records/create',    [StudentCounselingRecordController::class, 'create'])->name('counseling-records.create');
                Route::post('/counseling-records',         [StudentCounselingRecordController::class, 'store'])->name('counseling-records.store');
                Route::get('/counseling-records/{uuid}',    [StudentCounselingRecordController::class, 'show'])->name('counseling-records.show');
                Route::get('/counseling-records/{uuid}/edit', [StudentCounselingRecordController::class, 'edit'])->name('counseling-records.edit');
                Route::put('/counseling-records/{uuid}',   [StudentCounselingRecordController::class, 'update'])->name('counseling-records.update');
                Route::delete('/counseling-records/{uuid}', [StudentCounselingRecordController::class, 'destroy'])->name('counseling-records.destroy');

                // Faskes Rujukan
                Route::get('/facility-referrals',          [FacilityReferralController::class, 'index'])->name('facility-referrals.index');
                Route::get('/facility-referrals/create',    [FacilityReferralController::class, 'create'])->name('facility-referrals.create');
                Route::post('/facility-referrals',        [FacilityReferralController::class, 'store'])->name('facility-referrals.store');
                Route::get('/facility-referrals/{uuid}',   [FacilityReferralController::class, 'show'])->name('facility-referrals.show');
                Route::get('/facility-referrals/{uuid}/edit', [FacilityReferralController::class, 'edit'])->name('facility-referrals.edit');
                Route::put('/facility-referrals/{uuid}',  [FacilityReferralController::class, 'update'])->name('facility-referrals.update');
                Route::delete('/facility-referrals/{uuid}', [FacilityReferralController::class, 'destroy'])->name('facility-referrals.destroy');

                // Sanitasi
                Route::get('/sanitation-inspections',       [SanitationInspectionController::class, 'index'])->name('sanitation-inspections.index');
                Route::get('/sanitation-inspections/dashboard', [SanitationInspectionController::class, 'dashboard'])->name('sanitation-inspections.dashboard');
                Route::get('/sanitation-inspections/create', [SanitationInspectionController::class, 'create'])->name('sanitation-inspections.create');
                Route::post('/sanitation-inspections',    [SanitationInspectionController::class, 'store'])->name('sanitation-inspections.store');
                Route::get('/sanitation-inspections/{uuid}', [SanitationInspectionController::class, 'show'])->name('sanitation-inspections.show');
                Route::get('/sanitation-inspections/{uuid}/edit', [SanitationInspectionController::class, 'edit'])->name('sanitation-inspections.edit');
                Route::put('/sanitation-inspections/{uuid}', [SanitationInspectionController::class, 'update'])->name('sanitation-inspections.update');
                Route::post('/sanitation-inspections/{uuid}/mark-complete', [SanitationInspectionController::class, 'markComplete'])->name('sanitation-inspections.mark-complete');
                Route::delete('/sanitation-inspections/{uuid}', [SanitationInspectionController::class, 'destroy'])->name('sanitation-inspections.destroy');
            });

            // ── DATA ALUMNI ────────────────────────────────────────
            Route::prefix('alumni')->name('alumni.')->group(function () {
                Route::get('/',               [AlumniController::class, 'index'])->name('index');
                Route::get('/statistics',     [AlumniController::class, 'statistics'])->name('statistics');
                Route::get('/export',         [AlumniController::class, 'export'])->name('export');
                // Wildcard di bawah literal
                Route::get('/{alumniUuid}',   [AlumniController::class, 'show'])->name('show');
                Route::get('/{alumniUuid}/edit',  [AlumniController::class, 'edit'])->name('edit');
                Route::put('/{alumniUuid}',   [AlumniController::class, 'update'])->name('update');
                Route::post('/{alumniUuid}/verify', [AlumniController::class, 'verify'])->name('verify');
            });

            // ── BULK GRADUATION ────────────────────────────────────
            Route::prefix('bulk-graduation')->name('bulk-graduation.')->group(function () {
                Route::get('/{studyGroupId?}', [BulkGraduationController::class, 'index'])->name('index');
                Route::post('/',              [BulkGraduationController::class, 'store'])->name('store');
            });

            // ── BULK PROMOTION ─────────────────────────────────────
            Route::prefix('bulk-promotion')->name('bulk-promotion.')->group(function () {
                Route::get('/{studyGroupId?}', [BulkPromotionController::class, 'index'])->name('index');
                Route::post('/{studyGroupId}', [BulkPromotionController::class, 'store'])->name('store');
                Route::get('/api/study-groups',    [BulkPromotionController::class, 'getStudyGroups'])->name('api.study-groups');
                Route::post('/promote',           [BulkPromotionController::class, 'promote'])->name('promote');
            });

            // ── WORK UNITS ─────────────────────────────────────────
            Route::prefix('work-units')->name('work-units.')->group(function () {
                Route::get('/',                      [WorkUnitController::class, 'index'])->name('index');
                Route::post('/',                     [WorkUnitController::class, 'store'])->name('store');
                Route::post('/bulk-delete',          [WorkUnitController::class, 'bulkDestroy'])->name('bulk-destroy');
                Route::post('/generate-code',        [WorkUnitController::class, 'generateCode'])->name('generate-code');
                Route::get('/{id}',                 [WorkUnitController::class, 'show'])->name('show');
                Route::put('/{id}',                 [WorkUnitController::class, 'update'])->name('update');
                Route::delete('/{id}',              [WorkUnitController::class, 'destroy'])->name('destroy');
                Route::patch('/{id}/toggle-status', [WorkUnitController::class, 'toggleStatus'])->name('toggle-status');
            });

            // ── PROFILE ────────────────────────────────────────────
            Route::prefix('profile')->name('profile.')->group(function () {
                Route::get('/my',          [ProfileController::class, 'myProfile'])->name('my');
                Route::get('/my/edit',     [ProfileController::class, 'editMyProfile'])->name('my.edit');
                Route::put('/my',          [ProfileController::class, 'updateMyProfile'])->name('my.update');
                Route::post('/my/photo',   [ProfileController::class, 'uploadPhoto'])->name('my.photo.upload');
                Route::delete('/my/photo', [ProfileController::class, 'deletePhoto'])->name('my.photo.delete');
                Route::get('/cv/{uuid}',   [ProfileController::class, 'downloadCv'])->name('cv');
            });

            // ── SIDEBAR MENU ───────────────────────────────────────
            Route::prefix('admin/sidebar-menu')->name('admin.sidebar-menu.')->group(function () {
                Route::get('/',        [SidebarMenuController::class, 'index'])->name('index');
                Route::post('/',       [SidebarMenuController::class, 'store'])->name('store');
                Route::put('/{id}',   [SidebarMenuController::class, 'update'])->name('update');
                Route::delete('/{id}',[SidebarMenuController::class, 'destroy'])->name('destroy');
            });

            // ── API INTERNAL ───────────────────────────────────────
            Route::prefix('api')->name('api.')->group(function () {
                Route::get('grade-levels/by-school/{schoolId}', [GradeLevelApiController::class, 'bySchool'])->name('grade-levels.by-school');
                Route::get('grade-levels/by-academic-year/{academicYearId}', [GradeLevelApiController::class, 'byAcademicYear'])->name('grade-levels.by-academic-year');
                Route::get('teachers/by-school/{schoolId}',     [GradeLevelApiController::class, 'teachersBySchool'])->name('teachers.by-school');

                Route::get('study-groups/{studyGroupId}/students/unassigned',  [StudyGroupApiController::class, 'unassignedStudents'])->name('study-groups.students.unassigned');
                Route::get('study-groups/{studyGroupId}/students/assigned',    [StudyGroupApiController::class, 'getAssignedStudents'])->name('study-groups.students.assigned');
                Route::post('study-groups/{studyGroupId}/students/add',        [StudyGroupApiController::class, 'addStudent'])->name('study-groups.students.add');
                Route::post('study-groups/{studyGroupId}/students/bulk-add',   [StudyGroupApiController::class, 'bulkAddStudents'])->name('study-groups.students.bulk-add');
                Route::post('study-groups/{studyGroupId}/students/remove',     [StudyGroupApiController::class, 'removeStudent'])->name('study-groups.students.remove');
            });

            /*
            |--------------------------------------------------------------------------
            | SUPER ADMIN: /{userId}/sa
            |--------------------------------------------------------------------------
            */
            Route::prefix('sa')->name('sa.')->group(function () {

                Route::prefix('users')->name('users.')->group(function () {
                    Route::get('/',                     [UserController::class, 'index'])->name('index');
                    Route::get('/create',               [UserController::class, 'create'])->name('create');
                    Route::post('/',                    [UserController::class, 'store'])->name('store');
                    Route::get('/{id}/edit',           [UserController::class, 'edit'])->name('edit');
                    Route::put('/{id}',                [UserController::class, 'update'])->name('update');
                    Route::delete('/{id}',             [UserController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
                    Route::post('/{id}/assign-roles',  [UserController::class, 'assignRoles'])->name('assign-roles');
                });

                Route::prefix('roles')->name('roles.')->group(function () {
                    Route::get('/',        [RoleController::class, 'index'])->name('index');
                    Route::post('/',       [RoleController::class, 'store'])->name('store');
                    Route::put('/{id}',   [RoleController::class, 'update'])->name('update');
                    Route::delete('/{id}',[RoleController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('permissions')->name('permissions.')->group(function () {
                    Route::get('/',        [PermissionController::class, 'index'])->name('index');
                    Route::post('/',       [PermissionController::class, 'store'])->name('store');
                    Route::put('/{id}',   [PermissionController::class, 'update'])->name('update');
                    Route::delete('/{id}',[PermissionController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
                    Route::get('/',       [AuditLogController::class, 'index'])->name('index');
                    Route::get('/export', [AuditLogController::class, 'export'])->name('export');
                    Route::get('/{id}',  [AuditLogController::class, 'show'])->name('show');
                });

                Route::prefix('tokens')->name('tokens.')->group(function () {
                    Route::get('/',                  [TokenSesiController::class, 'index'])->name('index');
                    Route::post('/',                 [TokenSesiController::class, 'createToken'])->name('create');
                    Route::delete('/sessions/{id}', [TokenSesiController::class, 'revokeToken'])->name('sessions.revoke');
                    Route::delete('/secure/{id}',   [TokenSesiController::class, 'revokeSecureToken'])->name('secure.revoke');
                });

                Route::prefix('failed-jobs')->name('failed-jobs.')->group(function () {
                    Route::get('/',           [FailedJobController::class, 'index'])->name('index');
                    Route::post('/{id}/retry',[FailedJobController::class, 'retry'])->name('retry');
                    Route::post('/retry-all', [FailedJobController::class, 'retryAll'])->name('retry-all');
                    Route::delete('/{id}',    [FailedJobController::class, 'destroy'])->name('destroy');
                    Route::post('/flush',     [FailedJobController::class, 'flush'])->name('flush');
                });

                Route::prefix('notifications')->name('notifications.')->group(function () {
                    Route::get('/',                 [NotificationUniversalController::class, 'index'])->name('index');
                    Route::get('/create',           [NotificationUniversalController::class, 'create'])->name('create');
                    Route::post('/',                [NotificationUniversalController::class, 'store'])->name('store');
                    Route::post('/{id}/mark-read', [NotificationUniversalController::class, 'markAsRead'])->name('mark-read');
                    Route::post('/mark-all-read',   [NotificationUniversalController::class, 'markAllRead'])->name('mark-all-read');
                    Route::delete('/{id}',         [NotificationUniversalController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('sidebar-menus')->name('sidebar-menus.')->group(function () {
                    Route::get('/',        [SidebarMenuManagementController::class, 'index'])->name('index');
                    Route::post('/',       [SidebarMenuManagementController::class, 'store'])->name('store');
                    Route::put('/{id}',   [SidebarMenuManagementController::class, 'update'])->name('update');
                    Route::delete('/{id}',[SidebarMenuManagementController::class, 'destroy'])->name('destroy');
                    Route::post('/reorder',[SidebarMenuManagementController::class, 'reorder'])->name('reorder');
                });

                Route::get('/password-reset-logs', [PasswordResetLogController::class, 'index'])->name('password-reset-logs.index');

                Route::prefix('system-settings')->name('system-settings.')->group(function () {
                    Route::get('/',            [SystemSettingController::class, 'index'])->name('index');
                    Route::post('/',           [SystemSettingController::class, 'update'])->name('update');
                    Route::get('/clear-cache', [SystemSettingController::class, 'clearCache'])->name('clear-cache');
                });

                Route::post('/users/{user}/unlock', [UserSecurityController::class, 'unlock'])->name('users.unlock');
            });

            // ── SARANA PRASARANA ──────────────────────────────────
            Route::prefix('sarpras')->name('sarpras.')->group(function () {
                // Gedung
                Route::get('/gedung',              [SarprasController::class, 'gedungIndex'])->name('gedung.index');
                Route::get('/gedung/create',       [SarprasController::class, 'gedungCreate'])->name('gedung.create');
                Route::post('/gedung',             [SarprasController::class, 'gedungStore'])->name('gedung.store');
                Route::get('/gedung/{id}',        [SarprasController::class, 'gedungShow'])->name('gedung.show');
                Route::get('/gedung/{id}/edit',   [SarprasController::class, 'gedungEdit'])->name('gedung.edit');
                Route::put('/gedung/{id}',        [SarprasController::class, 'gedungUpdate'])->name('gedung.update');
                Route::delete('/gedung/{id}',     [SarprasController::class, 'gedungDestroy'])->name('gedung.destroy');

                // Ruang
                Route::get('/ruang',              [SarprasController::class, 'ruangIndex'])->name('ruang.index');
                Route::get('/ruang/create',       [SarprasController::class, 'ruangCreate'])->name('ruang.create');
                Route::post('/ruang',             [SarprasController::class, 'ruangStore'])->name('ruang.store');
                Route::get('/ruang/{id}',        [SarprasController::class, 'ruangShow'])->name('ruang.show');
                Route::get('/ruang/{id}/edit',   [SarprasController::class, 'ruangEdit'])->name('ruang.edit');
                Route::put('/ruang/{id}',        [SarprasController::class, 'ruangUpdate'])->name('ruang.update');
                Route::delete('/ruang/{id}',     [SarprasController::class, 'ruangDestroy'])->name('ruang.destroy');

                // Aset / Inventaris
                Route::get('/aset',               [SarprasController::class, 'asetIndex'])->name('aset.index');
                Route::get('/aset/create',        [SarprasController::class, 'asetCreate'])->name('aset.create');
                Route::post('/aset',              [SarprasController::class, 'asetStore'])->name('aset.store');
                Route::get('/aset/import',        [SarprasController::class, 'asetImportForm'])->name('aset.import');
                Route::post('/aset/import',       [SarprasController::class, 'asetImportProcess'])->name('aset.import.process');
                Route::get('/aset/template',      [SarprasController::class, 'asetTemplate'])->name('aset.template');
                Route::get('/aset/{id}',         [SarprasController::class, 'asetShow'])->name('aset.show');
                Route::get('/aset/{id}/edit',    [SarprasController::class, 'asetEdit'])->name('aset.edit');
                Route::put('/aset/{id}',         [SarprasController::class, 'asetUpdate'])->name('aset.update');
                Route::delete('/aset/{id}',      [SarprasController::class, 'asetDestroy'])->name('aset.destroy');

                // Kategori (Ajax)
                Route::post('/kategori', [SarprasController::class, 'kategoriStore'])->name('kategori.store');
            });

            // ── TODO LIST ──────────────────────────────────────────
            Route::prefix('todos')->name('todos.')->group(function () {
                // Todo Lists (AJAX)
                Route::get('/lists',      [App\Http\Controllers\TodoListController::class, 'index'])->name('lists.index');
                Route::post('/lists',      [App\Http\Controllers\TodoListController::class, 'store'])->name('lists.store');
                Route::put('/lists/{id}',  [App\Http\Controllers\TodoListController::class, 'update'])->name('lists.update');
                Route::delete('/lists/{id}', [App\Http\Controllers\TodoListController::class, 'destroy'])->name('lists.destroy');
                Route::post('/lists/set-default/{id}', [App\Http\Controllers\TodoListController::class, 'setDefault'])->name('lists.set-default');
                Route::post('/lists/reorder', [App\Http\Controllers\TodoListController::class, 'reorder'])->name('lists.reorder');

                // Main Todo CRUD
                Route::get('/',                       [App\Http\Controllers\TodoController::class, 'index'])->name('index');
                Route::get('/{id}',                  [App\Http\Controllers\TodoController::class, 'show'])->name('show');
                Route::post('/',                      [App\Http\Controllers\TodoController::class, 'store'])->name('store');
                Route::put('/{id}',                  [App\Http\Controllers\TodoController::class, 'update'])->name('update');
                Route::delete('/{id}',               [App\Http\Controllers\TodoController::class, 'destroy'])->name('destroy');

                // Subtasks
                Route::post('/{id}/subtasks',                       [App\Http\Controllers\TodoController::class, 'subtaskStore'])->name('subtasks.store');
                Route::put('/{id}/subtasks/{subtaskId}/toggle',      [App\Http\Controllers\TodoController::class, 'subtaskToggle'])->name('subtasks.toggle');
                Route::delete('/{id}/subtasks/{subtaskId}',          [App\Http\Controllers\TodoController::class, 'subtaskDestroy'])->name('subtasks.destroy');

                // Comments
                Route::post('/{id}/comments',    [App\Http\Controllers\TodoController::class, 'commentStore'])->name('comments.store');
                Route::delete('/{id}/comments/{commentId}', [App\Http\Controllers\TodoController::class, 'commentDestroy'])->name('comments.destroy');

                // Attachments
                Route::post('/{id}/attachments',     [App\Http\Controllers\TodoController::class, 'attachmentStore'])->name('attachments.store');
                Route::delete('/{id}/attachments/{attachmentId}', [App\Http\Controllers\TodoController::class, 'attachmentDestroy'])->name('attachments.destroy');
            });

            // ── STUDENT ACHIEVEMENTS ────────────────────────────────────
            Route::prefix('student-achievements')->name('student-achievement.')->group(function () {
                Route::get('/',              [StudentAchievementController::class, 'index'])->name('index');
                Route::get('/create',       [StudentAchievementController::class, 'create'])->name('create');
                Route::post('/',            [StudentAchievementController::class, 'store'])->name('store');
                Route::get('/{id}',         [StudentAchievementController::class, 'show'])->name('show');
                Route::get('/{id}/edit',    [StudentAchievementController::class, 'edit'])->name('edit');
                Route::put('/{id}',        [StudentAchievementController::class, 'update'])->name('update');
                Route::delete('/{id}',     [StudentAchievementController::class, 'destroy'])->name('destroy');
                Route::get('/find-student', [StudentAchievementController::class, 'findStudent'])->name('find-student');
                Route::get('/import',       [StudentAchievementController::class, 'importForm'])->name('import-form');
                Route::post('/import',      [StudentAchievementController::class, 'importProcess'])->name('import-process');
                Route::get('/template',    [StudentAchievementController::class, 'downloadTemplate'])->name('template');
            });
        });
    });

/*
|--------------------------------------------------------------------------
| WADIR 1 SUBDOMAIN
|--------------------------------------------------------------------------
*/
Route::domain('wadir1.' . env('APP_DOMAIN', 'localhost'))
    ->middleware(['auth', 'role:Wadir 1'])
    ->name('wadir1.')
    ->group(function () {
        Route::get('/', fn() => redirect()->route('wadir1.gtk.index'));

        Route::prefix('gtk')->name('gtk.')->group(function () {
            Route::get('/', [GTKController::class, 'index'])->name('index');
            Route::get('/filter', [GTKController::class, 'filter'])->name('filter');
            Route::get('/{gtkUuid}', [GTKController::class, 'show'])->name('show');
            Route::get('/by-work-unit/{workUnitUuid}', [GTKController::class, 'indexByWorkUnit'])->name('by-work-unit');
        });

        Route::prefix('work-units')->name('work-units.')->group(function () {
            Route::get('/', [WorkUnitController::class, 'index'])->name('index');
            Route::get('/{workUnitUuid}', [WorkUnitController::class, 'show'])->name('show');
        });

        Route::get('/reports', fn() => view('wadir1.reports.index'))->name('reports.index');

        Route::get('/statistics', function () {
            $gtkCount      = \App\Models\User::whereHas('employment')->count();
            $workUnitCount = \App\Models\WorkUnit::count();
            return response()->json(['gtk_count' => $gtkCount, 'work_unit_count' => $workUnitCount]);
        })->name('statistics');
    });

/*
|--------------------------------------------------------------------------
| WAKA / ADMIN TU SUBDOMAIN
|--------------------------------------------------------------------------
*/
Route::domain('waka.' . env('APP_DOMAIN', 'localhost'))
    ->middleware(['auth', 'role:Admin Tata Usaha,Wakil Kepala Sekolah,Admin Sarpras'])
    ->name('waka.')
    ->group(function () {
        Route::get('/', [WakaController::class, 'dashboard'])->name('dashboard');

        Route::get('/gtk-guru',   fn() => view('waka.dashboard'))->name('gtk-guru');
        Route::get('/gtk-tendik', fn() => view('waka.dashboard'))->name('gtk-tendik');

        Route::get('/peserta-didik/data-kelas',     fn() => view('waka.dashboard'))->name('peserta-didik.data-kelas');
        Route::get('/peserta-didik/rombel/{kelas}', fn($kelas) => view('waka.dashboard'))->name('peserta-didik.rombel');
        Route::get('/peserta-didik/mutasi', fn() => view('waka.dashboard'))->name('peserta-didik.mutasi');
        Route::get('/peserta-didik/masuk', fn() => redirect()->route('user.mutations-in.index', ['userId' => auth()->user()->id]))->name('peserta-didik.masuk');
        Route::get('/peserta-didik/keluar', fn() => redirect()->route('user.mutations-out.index', ['userId' => auth()->user()->id]))->name('peserta-didik.keluar');

        Route::get('/poin-pelanggaran',    fn() => view('waka.dashboard'))->name('poin-pelanggaran');
        Route::get('/kisi-kisi-soal',      fn() => view('waka.dashboard'))->name('kisi-kisi-soal');
        Route::get('/soal-sumatif',        fn() => view('waka.dashboard'))->name('soal-sumatif');
        Route::get('/nilai-sts/{kelas}',   fn($kelas) => view('waka.dashboard'))->name('nilai-sts');
        Route::get('/nilai-sas/{kelas}',   fn($kelas) => view('waka.dashboard'))->name('nilai-sas');
        Route::get('/absensi-gtk',         fn() => view('waka.dashboard'))->name('absensi-gtk');
        Route::get('/absensi-pd/{kelas}',  fn($kelas) => view('waka.dashboard'))->name('absensi-pd');
        Route::get('/prestasi-akademik',   fn() => view('waka.dashboard'))->name('prestasi-akademik');
        Route::get('/hafalan-quran',       fn() => view('waka.dashboard'))->name('hafalan-quran');
        Route::get('/hafalan-hadits',      fn() => view('waka.dashboard'))->name('hafalan-hadits');
        Route::get('/ekstrakurikuler',     fn() => view('waka.dashboard'))->name('ekstrakurikuler');
        Route::get('/supervisi',           fn() => view('waka.dashboard'))->name('supervisi');
        Route::get('/sk-guru',             fn() => view('waka.dashboard'))->name('sk-guru');
        Route::get('/jadwal-pelajaran',    fn() => view('waka.dashboard'))->name('jadwal-pelajaran');
        Route::get('/jam-mengajar',        fn() => view('waka.dashboard'))->name('jam-mengajar');
        Route::get('/rekap-pergantian-jam',fn() => view('waka.dashboard'))->name('rekap-pergantian-jam');
        Route::get('/surat-keluar',        fn() => view('waka.dashboard'))->name('surat-keluar');
        Route::get('/surat-masuk',         fn() => view('waka.dashboard'))->name('surat-masuk');
        Route::get('/dokumen-iso',         fn() => view('waka.dashboard'))->name('dokumen-iso');
        Route::get('/kaldik',              fn() => view('waka.dashboard'))->name('kaldik');
        Route::get('/pekan-efektif',       fn() => view('waka.dashboard'))->name('pekan-efektif');
        Route::get('/sarana-prasarana',    fn() => view('waka.dashboard'))->name('sarana-prasarana');
        Route::get('/data-alumni', [AlumniController::class, 'index'])->name('data-alumni');
    });

/*
|--------------------------------------------------------------------------
| SPA FALLBACK
|--------------------------------------------------------------------------
*/
Route::fallback([HomeController::class, 'index']);