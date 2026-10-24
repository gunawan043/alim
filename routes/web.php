<?php

use App\Http\Controllers\AbsensiHarianController;
use App\Http\Controllers\AcademicIntegrationController;
use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdminAsramaDashboardController;
use App\Http\Controllers\AdminTUDashboardController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AiWorkspaceController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Arsip\ArsipInventarisController;
use App\Http\Controllers\AsramaDashboardController;
use App\Http\Controllers\AtsDashboardController;
use App\Http\Controllers\Auth\AccessValidatorController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\ForcePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\Bahasa\BahasaAcademicController;
use App\Http\Controllers\Bahasa\BahasaDashboardController;
use App\Http\Controllers\Bahasa\BahasaReferenceController;
use App\Http\Controllers\Bahasa\LanguageActivityController;
use App\Http\Controllers\Bahasa\LanguageCertificationController;
use App\Http\Controllers\Bahasa\LanguageDailyLogController;
use App\Http\Controllers\Bahasa\LanguageViolationController;
use App\Http\Controllers\Bahasa\MufradatController;
use App\Http\Controllers\Boarding\BoardingEducationDashboardController;
// use App\Http\Controllers\Dashboard\BendaharaDashboardController;
// use App\Http\Controllers\Dashboard\GuruDashboardController;
// use App\Http\Controllers\Dashboard\KaTataUsahaDashboardController;
// use App\Http\Controllers\Dashboard\KepSatuanPendidikanDashboardController;
// use App\Http\Controllers\Dashboard\KoordEkskulDashboardController;
// use App\Http\Controllers\Dashboard\KoordGuruDashboardController;
// use App\Http\Controllers\Dashboard\KoordKesiswaanDashboardController;
// use App\Http\Controllers\Dashboard\KoordKurikulumDashboardController;
// use App\Http\Controllers\Dashboard\KoordLaboratoriumDashboardController;
// use App\Http\Controllers\Dashboard\KoordSarprasDashboardController;
// use App\Http\Controllers\Dashboard\StafTataUsahaDashboardController;
// use App\Http\Controllers\Dashboard\WakaSatuanPendidikanDashboardController;
// use App\Http\Controllers\Dashboard\WaliKelasDashboardController;
use App\Http\Controllers\Boarding\BoardingHeadDashboardController;
use App\Http\Controllers\Boarding\BoardingHealthDashboardController;
use App\Http\Controllers\BoardingApprovalCenterController;
use App\Http\Controllers\BoardingPolicyController;
use App\Http\Controllers\BoardingRegulationController;
use App\Http\Controllers\BulkGraduationController;
use App\Http\Controllers\BulkPromotionController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\ClassQrController;
use App\Http\Controllers\Dashboard\AsramaDashboardController as RoleAsramaDashboardController;
use App\Http\Controllers\Dashboard\KeuanganDashboardController;
use App\Http\Controllers\Dashboard\PersonaliaDashboardController as RolePersonaliaDashboardController;
use App\Http\Controllers\Dashboard\PimpinanDashboardController;
use App\Http\Controllers\Dashboard\SatuanPendidikanDashboardController;
use App\Http\Controllers\Dashboard\TahfidzDashboardController;
use App\Http\Controllers\Dashboard\UksDashboardController as RoleUksDashboardController;
use App\Http\Controllers\Dashboard\UnitRumahTanggaDashboardController;
use App\Http\Controllers\DeployController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\DokumenIsoController;
use App\Http\Controllers\DormitoryActivityController;
use App\Http\Controllers\DormitoryAttendanceController;
use App\Http\Controllers\DormitoryInventoryController;
use App\Http\Controllers\DormitoryLeavePolicyController;
use App\Http\Controllers\DormitoryMasterController;
use App\Http\Controllers\DormitoryPermitController;
use App\Http\Controllers\DormitoryPostController;
use App\Http\Controllers\DormitoryReportController;
use App\Http\Controllers\DormitoryResidentController;
use App\Http\Controllers\DormitoryReturnCalendarController;
use App\Http\Controllers\DormitoryReturnController;
use App\Http\Controllers\DormitoryRewardController;
use App\Http\Controllers\DormitoryRoomApiController;
use App\Http\Controllers\DormitoryRoomController;
use App\Http\Controllers\DormitoryRoomMoveController;
use App\Http\Controllers\DormitoryStaffAssignmentController;
use App\Http\Controllers\DormitoryViolationController;
use App\Http\Controllers\DormitoryVisitCalendarController;
use App\Http\Controllers\DormitoryVisitLogController;
use App\Http\Controllers\DormitoryStudentOperatorController;
use App\Http\Controllers\DormitoryWingController;
use App\Http\Controllers\Evaluasi\AsesmenController;
use App\Http\Controllers\Evaluasi\BankSoalController;
use App\Http\Controllers\Evaluasi\BankSoalTerpusatController;
use App\Http\Controllers\Evaluasi\KisiKisiController;
use App\Http\Controllers\Evaluasi\PaketSoalController;
use App\Http\Controllers\Evaluasi\PaketSoalTuController;
use App\Http\Controllers\Evaluasi\ReviewSoalController;
use App\Http\Controllers\Evaluasi\SoalController;
use App\Http\Controllers\FacilityReferralController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\GradeLevelApiController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\GtkAdditionalTaskController;
use App\Http\Controllers\GtkArabicNameController;
use App\Http\Controllers\GTKEducationController;
use App\Http\Controllers\GtkPositionController;
use App\Http\Controllers\GtkRecruitmentController;
use App\Http\Controllers\GtkRequestController;
use App\Http\Controllers\GtkWizardController;
use App\Http\Controllers\GuardianPortalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HumasComplaintController;
use App\Http\Controllers\HumasGuestVisitController;
use App\Http\Controllers\HumasMediaContactController;
use App\Http\Controllers\HumasSatisfactionController;
use App\Http\Controllers\HumasAnnouncementController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\InstitutionDecreeController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\JadwalKbmController;
use App\Http\Controllers\JamPelajaranController;
use App\Http\Controllers\JenjangKarirController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\KaldikController;
use App\Http\Controllers\Kurikulum\AiContentController;
use App\Http\Controllers\Kurikulum\AlurTujuanPembelajaranController;
use App\Http\Controllers\Kurikulum\CapaianPembelajaranController;
use App\Http\Controllers\Kurikulum\CetakDokumenKurikulumController;
use App\Http\Controllers\Kurikulum\CurriculumProfileBulkController;
use App\Http\Controllers\Kurikulum\CurriculumProfileController;
use App\Http\Controllers\Kurikulum\CurriculumProjectBankController;
use App\Http\Controllers\Kurikulum\CurriculumProjectController;
use App\Http\Controllers\Kurikulum\KurikulumController;
use App\Http\Controllers\Kurikulum\KurikulumEditGrantController;
use App\Http\Controllers\Kurikulum\PerangkatPembelajaranController;
use App\Http\Controllers\Kurikulum\ProsemController;
use App\Http\Controllers\Kurikulum\ProtaController;
use App\Http\Controllers\Kurikulum\RealisasiPembelajaranController;
use App\Http\Controllers\Kurikulum\SubjectGroupController;
use App\Http\Controllers\Kurikulum\TujuanPembelajaranController;
use App\Http\Controllers\Ljk\LjkController;
use App\Http\Controllers\Ljk\LjkResultController;
use App\Http\Controllers\Ljk\LjkScanController;
use App\Http\Controllers\MasterData\MataPelajaranController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\NilaiController;
use App\Http\Controllers\NilaiGuruController;
use App\Http\Controllers\NilaiKelasController;
use App\Http\Controllers\NotificationPageController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\OtherTeacherTaskController;
use App\Http\Controllers\PekanEfektifController as PekanEfektifOverviewController;
use App\Http\Controllers\PengasuhDashboardController;
use App\Http\Controllers\PensionController;
use App\Http\Controllers\PermitTypeController;
use App\Http\Controllers\Perpustakaan\LibraryCatalogController;
use App\Http\Controllers\Perpustakaan\LibraryDashboardController;
use App\Http\Controllers\Perpustakaan\LibraryFineController;
use App\Http\Controllers\Perpustakaan\LibraryGenreController;
use App\Http\Controllers\Perpustakaan\LibraryImportController;
use App\Http\Controllers\Perpustakaan\LibraryLoanController;
use App\Http\Controllers\Perpustakaan\LibraryMemberController;
use App\Http\Controllers\Perpustakaan\LibraryProgramController;
use App\Http\Controllers\Perpustakaan\LibraryReportController;
use App\Http\Controllers\Perpustakaan\LibraryRoomController;
use App\Http\Controllers\Perpustakaan\LibraryStockOpnameController;
use App\Http\Controllers\Perpustakaan\LibraryStudentOperatorController;
use App\Http\Controllers\Perpustakaan\LibraryUnitController;
use App\Http\Controllers\Perpustakaan\LibraryVisitController;
use App\Http\Controllers\Personalia\AbsensiGtkController;
use App\Http\Controllers\Personalia\CrossUnitAssignmentController;
use App\Http\Controllers\Personalia\AnalisisGtkController;
use App\Http\Controllers\Personalia\CutiController;
use App\Http\Controllers\Personalia\JamKerjaController;
use App\Http\Controllers\Personalia\KalenderKegiatanController;
use App\Http\Controllers\Personalia\KehadiranController;
// use App\Http\Controllers\SidebarMenuController; // REMOVED - Sidebar menu DB unused
use App\Http\Controllers\Personalia\KesejahteraanController;
use App\Http\Controllers\Personalia\KinerjaController;
use App\Http\Controllers\Personalia\KontrakController;
use App\Http\Controllers\Personalia\PayrollController;
use App\Http\Controllers\Personalia\PelatihanController;
use App\Http\Controllers\Personalia\PeraturanController;
use App\Http\Controllers\Personalia\PersonaliaDashboardController;
use App\Http\Controllers\Personalia\RaporGtkController;
use App\Http\Controllers\PositionProposalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSecurityController;
use App\Http\Controllers\RecruitmentPipelineController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomSupervisorController;
use App\Http\Controllers\SanitationInspectionController;
use App\Http\Controllers\Sarpras\AssetPassportController;
use App\Http\Controllers\Sarpras\AssetPassportPdfController;
use App\Http\Controllers\Sarpras\DivisionPortalController;
use App\Http\Controllers\Sarpras\SarprasAgendaController;
use App\Http\Controllers\Sarpras\SarprasAsetController;
use App\Http\Controllers\Sarpras\SarprasAssetMovementController;
use App\Http\Controllers\Sarpras\SarprasAuditorWorkspaceController;
use App\Http\Controllers\Sarpras\SarprasBookingController;
use App\Http\Controllers\Sarpras\SarprasCameraController;
use App\Http\Controllers\Sarpras\SarprasChecklistController;
use App\Http\Controllers\Sarpras\SarprasCleaningController;
use App\Http\Controllers\Sarpras\SarprasDashboardController;
use App\Http\Controllers\Sarpras\SarprasDataAuditController;
use App\Http\Controllers\Sarpras\SarprasDataExchangeController;
use App\Http\Controllers\Sarpras\SarprasDisposalController;
use App\Http\Controllers\Sarpras\SarprasDivisionPortalController;
use App\Http\Controllers\Sarpras\SarprasElectricityController;
use App\Http\Controllers\Sarpras\SarprasGedungController;
use App\Http\Controllers\Sarpras\SarprasIntelligenceDashboardController;
use App\Http\Controllers\Sarpras\SarprasKepalaApprovalController;
use App\Http\Controllers\Sarpras\SarprasLoanController;
use App\Http\Controllers\Sarpras\SarprasMaintenanceController;
use App\Http\Controllers\Sarpras\SarprasMovementController;
use App\Http\Controllers\Sarpras\SarprasOccupancyController;
use App\Http\Controllers\Sarpras\SarprasOfficerController;
// REMOVED - Sidebar menu DB unused
use App\Http\Controllers\Sarpras\SarprasPertamananController;
use App\Http\Controllers\Sarpras\SarprasPicApprovalController;
use App\Http\Controllers\Sarpras\SarprasPredictiveMaintenanceController;
use App\Http\Controllers\Sarpras\SarprasProcurementController;
use App\Http\Controllers\Sarpras\SarprasPurchaseOrderController;
use App\Http\Controllers\Sarpras\SarprasQRController;
use App\Http\Controllers\Sarpras\SarprasRepairController;
use App\Http\Controllers\Sarpras\SarprasRepairVsReplaceController;
use App\Http\Controllers\Sarpras\SarprasReportController;
use App\Http\Controllers\Sarpras\SarprasRuangController;
use App\Http\Controllers\Sarpras\SarprasSparepartController;
use App\Http\Controllers\Sarpras\SarprasStockOpnameController;
use App\Http\Controllers\Sarpras\SarprasTechnicianWorkspaceController;
use App\Http\Controllers\Sarpras\SarprasUserController;
use App\Http\Controllers\Sarpras\SarprasVendorController;
use App\Http\Controllers\Sarpras\SarprasWasteController;
use App\Http\Controllers\SarprasController;
use App\Http\Controllers\SatuanKerja\PositionController as SatuanPositionController;
use App\Http\Controllers\SatuanKerja\TaskController as SatuanTaskController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolsGlobalController;
use App\Http\Controllers\SchoolUnitController;
use App\Http\Controllers\SecureAccessController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StudentAchievementController;
use App\Http\Controllers\StudentArabicNameController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentCounselingRecordController;
use App\Http\Controllers\StudentHealthCheckupController;
use App\Http\Controllers\StudentHealthMetricController;
use App\Http\Controllers\StudentHealthPermitController;
use App\Http\Controllers\StudentImmunizationController;
use App\Http\Controllers\StudentMahromController;
use App\Http\Controllers\StudentMedicineInventoryController;
use App\Http\Controllers\StudentMedicineLogController;
use App\Http\Controllers\StudentMoveController;
use App\Http\Controllers\StudentMutationInController;
use App\Http\Controllers\StudentMutationOutController;
use App\Http\Controllers\StudentPhotoController;
use App\Http\Controllers\StudentPromotionController;
use App\Http\Controllers\StudentRoomHistoryController;
use App\Http\Controllers\StudentTimelineController;
use App\Http\Controllers\StudentViolationController;
use App\Http\Controllers\StudyGroupApiController;
use App\Http\Controllers\StudyGroupController;
use App\Http\Controllers\StudyGroupSubjectController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SuperAdmin\AiChatMonitorController;
use App\Http\Controllers\SuperAdmin\AiProviderController;
use App\Http\Controllers\SuperAdmin\AuditLogController;
use App\Http\Controllers\SuperAdmin\CurriculumStructureController;
use App\Http\Controllers\SuperAdmin\FailedJobController;
use App\Http\Controllers\SuperAdmin\NotificationUniversalController;
use App\Http\Controllers\SuperAdmin\PasswordResetLogController;
use App\Http\Controllers\SuperAdmin\PermissionController;
use App\Http\Controllers\SuperAdmin\RoleController;
use App\Http\Controllers\SuperAdmin\SchoolSwitchController;
use App\Http\Controllers\SuperAdmin\SidebarAccessController;
use App\Http\Controllers\SuperAdmin\SystemSettingController;
use App\Http\Controllers\SuperAdmin\TokenSesiController;
use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\SuratVerificationController;
use App\Http\Controllers\System\SystemDashboardController;
use App\Http\Controllers\System\ViewAsController;
use App\Http\Controllers\Tahfidz\HalaqahController;
use App\Http\Controllers\Tahfidz\MutabaahController;
use App\Http\Controllers\Tahfidz\SetoranController;
use App\Http\Controllers\Tahfidz\TahfidzAttendanceController;
use App\Http\Controllers\Tahfidz\TahfidzAuditController;
use App\Http\Controllers\Tahfidz\TahfidzConsistencyController;
use App\Http\Controllers\Tahfidz\TahfidzExecutiveController;
use App\Http\Controllers\Tahfidz\TahfidzInputController;
use App\Http\Controllers\Tahfidz\TahfidzInterventionController;
use App\Http\Controllers\Tahfidz\TahfidzMasterController;
use App\Http\Controllers\Tahfidz\TahfidzMonitoringController;
use App\Http\Controllers\Tahfidz\TahfidzProgressController;
use App\Http\Controllers\Tahfidz\TahfidzSantriController;
use App\Http\Controllers\Tahfidz\TahfidzTargetController;
use App\Http\Controllers\Tahfidz\TahfidzWorkspaceController;
use App\Http\Controllers\Tahfidz\TasmiController;
use App\Http\Controllers\TeacherQrScanController;
use App\Http\Controllers\TeachingAssignmentController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\TodoListController;
use App\Http\Controllers\Uks\BedManagementController;
use App\Http\Controllers\Uks\CareLocationController;
use App\Http\Controllers\Uks\ClinicController;
use App\Http\Controllers\Uks\GtkHealthController;
use App\Http\Controllers\Uks\MedicationAdministrationController;
use App\Http\Controllers\Uks\PatientController;
use App\Http\Controllers\Uks\PharmacyController;
use App\Http\Controllers\Uks\SchedulingController;
use App\Http\Controllers\Uks\StudentHealthController;
use App\Http\Controllers\Uks\TreatmentStatusController;
use App\Http\Controllers\UserKalkulasiNilaiController;
use App\Http\Controllers\UserLaporanController;
use App\Http\Controllers\UserSecurityController;
use App\Http\Controllers\Vendor\ProcurementController;
use App\Http\Controllers\Vendor\VendorPortalController;
use App\Http\Controllers\ViolationPointController;
use App\Http\Controllers\Waka\EkstrakurikulerAnggotaController;
use App\Http\Controllers\Waka\EkstrakurikulerController;
use App\Http\Controllers\Waka\PekanEfektifController;
use App\Http\Controllers\Waka\SupervisiController;
use App\Http\Controllers\Waka\SupervisiInstrumenController;
use App\Http\Controllers\Waka\SupervisiPengaturanController;
use App\Http\Controllers\Waka\SupervisiSkController;
use App\Http\Controllers\Waka\SuratKeluarController;
use App\Http\Controllers\Waka\SuratMasukController;
use App\Http\Controllers\Waka\SuratTemplateController;
use App\Http\Controllers\WakaController;
use App\Http\Controllers\WaliAsramaDashboardController;
use App\Http\Controllers\WaliSantriController;
use App\Http\Controllers\WaliSantriPortalController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Controllers\WorkUnitController;
use App\Http\Middleware\EnsureBoardingSchool;
use App\Http\Middleware\DenyStudentOperatorAi;
use App\Http\Middleware\EnsureLibraryUnit;
use App\Http\Middleware\EnsurePermission;
use App\Http\Middleware\EnsureSuperAdminOrSystemAdmin;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| GLOBAL ROUTE PATTERNS
| Definisikan UUID pattern secara global agar konsisten di semua route.
| Ini mencegah konflik antara literal route (/create, /find-student, dll)
| dengan wildcard route (/{id}, /{uuid}, dll).
|--------------------------------------------------------------------------
*/
$uuidPattern = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';

Route::pattern('uuid', $uuidPattern);
Route::pattern('gtk', $uuidPattern);
Route::pattern('userId', $uuidPattern);
Route::pattern('id', $uuidPattern);   // <-- FIX UTAMA: global pattern untuk {id}
Route::pattern('divisiId', $uuidPattern);
Route::pattern('schoolId', $uuidPattern);
Route::pattern('workUnitId', $uuidPattern);
Route::pattern('recruitmentUuid', $uuidPattern);
Route::pattern('requestUuid', $uuidPattern);
Route::pattern('approvalUuid', $uuidPattern);
Route::pattern('mutationUuid', $uuidPattern);
Route::pattern('violationUuid', $uuidPattern);
Route::pattern('studentUuid', $uuidPattern);
Route::pattern('gtkUuid', $uuidPattern);
Route::pattern('workUnitUuid', $uuidPattern);
Route::pattern('studyGroupId', $uuidPattern);
Route::pattern('alumniUuid', $uuidPattern);
Route::pattern('healthUuid', $uuidPattern);
Route::pattern('asramaUuid', $uuidPattern);
Route::pattern('targetUserId', $uuidPattern);
Route::pattern('dormitoryId', $uuidPattern);
Route::pattern('residentUuid', $uuidPattern);
Route::pattern('permitUuid', $uuidPattern);
Route::pattern('postUuid', $uuidPattern);
Route::pattern('visitUuid', $uuidPattern);
Route::pattern('wingUuid', $uuidPattern);
Route::pattern('roomUuid', $uuidPattern);
Route::pattern('mahromUuid', $uuidPattern);
Route::pattern('promotionId', $uuidPattern);
Route::pattern('detailId', $uuidPattern);
Route::pattern('violationUuid', $uuidPattern);
Route::pattern('kaldikId', $uuidPattern);
Route::pattern('recapUuid', $uuidPattern);

// adminBookId boleh UUID atau integer
Route::pattern('adminBookId', '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}|[0-9]+');

/*
|--------------------------------------------------------------------------
| PUBLIC AUTH ROUTES (NO MIDDLEWARE!)
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', fn () => redirect()->route('root'))->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [\App\Http\Controllers\Auth\EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [\App\Http\Controllers\Auth\VerifyEmailController::class, '__invoke'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationNotificationController::class, 'store'])
        ->middleware(['throttle:6,1'])->name('verification.send');
});

Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.process');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ── Public Auth Routes ──────────────────────────────────────────────
Route::get('/access-denied', fn () => view('errors.wali-santri-blocked'))->name('access-denied');
Route::get('/auth/validator', [AccessValidatorController::class, 'show'])->middleware('auth')->name('auth.validator');
Route::post('/auth/validator/verify', [AccessValidatorController::class, 'verify'])->middleware('auth')->name('auth.validator.verify');

// ── Guardian Portal (Wali Santri Self-Service) ──────────────────────
Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/{token}', [GuardianPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/{token}/timeline', [GuardianPortalController::class, 'timeline'])->name('timeline');
    Route::get('/{token}/survei', [GuardianPortalController::class, 'satisfaction'])->name('satisfaction.index');
    Route::post('/{token}/survei/{id}', [GuardianPortalController::class, 'satisfactionSubmit'])->name('satisfaction.submit');
    Route::get('/{token}/announcements', [GuardianPortalController::class, 'announcements'])->name('announcements');
    Route::get('/{token}/announcements/{id}', [GuardianPortalController::class, 'announcementShow'])->name('announcements.show');
    Route::get('/{token}/notifications', [GuardianPortalController::class, 'notifications'])->name('notifications');
    Route::post('/{token}/notifications/{id}/read', [GuardianPortalController::class, 'markRead'])->name('notifications.read');
    Route::post('/{token}/notifications/read-all', [GuardianPortalController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/{token}/leave', [GuardianPortalController::class, 'submitLeave'])->name('leave');
    Route::post('/{token}/visit', [GuardianPortalController::class, 'submitVisit'])->name('visit');
    Route::post('/{token}/health', [GuardianPortalController::class, 'submitHealth'])->name('health');
});

Route::middleware('guest')->group(function () {
    Route::get('/two-factor-challenge', [TwoFactorController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorController::class, 'verify'])->middleware('throttle:otp')->name('two-factor.verify');
    Route::post('/two-factor-challenge/totp', [TwoFactorController::class, 'verifyTotp'])->middleware('throttle:otp')->name('two-factor.totp');
    Route::post('/two-factor-challenge/recovery', [TwoFactorController::class, 'verifyRecovery'])->middleware('throttle:otp')->name('two-factor.recovery');
    Route::post('/two-factor-challenge/resend', [TwoFactorController::class, 'resend'])->middleware('throttle:otp')->name('two-factor.resend');
    Route::post('/two-factor-challenge/cancel', [TwoFactorController::class, 'cancel'])->name('two-factor.cancel');

    Route::redirect('forgot-password', '/password/forgot');

    Route::prefix('password')->name('password.')->group(function () {
        Route::get('/forgot', [ForgotPasswordController::class, 'showForm'])->name('request');
        Route::post('/forgot', [ForgotPasswordController::class, 'sendOtp'])->middleware('throttle:otp')->name('email');
        Route::get('/otp', [ForgotPasswordController::class, 'showOtpForm'])->name('otp.form');
        Route::post('/otp', [ForgotPasswordController::class, 'verifyOtp'])->middleware('throttle:otp')->name('otp.verify');
        Route::post('/otp/resend', [ForgotPasswordController::class, 'resendOtp'])->middleware('throttle:otp')->name('otp.resend');
        Route::post('/otp/check', [ForgotPasswordController::class, 'checkOtpValidity'])->name('otp.check');
        Route::get('/reset', [ForgotPasswordController::class, 'showResetForm'])->name('reset.form');
        Route::post('/reset', [ForgotPasswordController::class, 'resetPassword'])->middleware('throttle:otp')->name('reset.update');
        Route::post('/cancel', [ForgotPasswordController::class, 'cancelReset'])->name('cancel');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('force-password', [ForcePasswordController::class, 'show'])->name('password.force');
    Route::post('force-password', [ForcePasswordController::class, 'update'])->name('password.force.update');
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES — ALL under /{userId}
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'employee.access'])->group(function () {

    Route::get('/', [HomeController::class, 'root'])->name('root');

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

    Route::get('/api/schools', [SchoolSwitchController::class, 'apiSchools'])->middleware(['auth'])->name('api.schools');
    Route::get('/api/dormitories', [SchoolSwitchController::class, 'apiDormitories'])->middleware(['auth'])->name('api.dormitories');
    Route::get('/api/wings-by-dormitory', [SchoolSwitchController::class, 'apiWings'])->middleware(['auth'])->name('api.wings-by-dormitory');
    Route::get('/api/rooms-by-wing', [SchoolSwitchController::class, 'apiRooms'])->middleware(['auth'])->name('api.rooms-by-wing');
    Route::get('/api/academic-years', [SchoolSwitchController::class, 'apiAcademicYears'])->middleware(['auth'])->name('api.academic-years');

    /*
    |--------------------------------------------------------------------------
    | IMPERSONATE (Super Admin support / debugging)
    |--------------------------------------------------------------------------
    | POST /impersonate/{targetUser}  — login as target user (non Super Admin).
    | POST /impersonate/stop          — logout & redirect to /login (re-login).
    | Permission gate enforced inside the controller (impersonate_role).
    */
    Route::post('/impersonate/{targetUser}', [ImpersonateController::class, 'start'])
        ->middleware(['auth'])
        ->where('targetUser', '[0-9a-fA-F-]{36}')
        ->name('impersonate.start');
    Route::post('/impersonate/stop', [ImpersonateController::class, 'stop'])
        ->middleware(['auth'])
        ->name('impersonate.stop');

    /*
    |--------------------------------------------------------------------------
    | USER GROUP: /{userId}
    |--------------------------------------------------------------------------
    */
    Route::prefix('{userId}')
        ->middleware(['auth', 'employee.access', 'role.access', 'school.context', 'organization.context'])
        ->name('user.')
        ->group(function () {

            // ── USER NOTIFICATIONS API ───────────────────────────────
            Route::prefix('notifications')->name('notifications.')->group(function () {
                Route::get('/', [NotificationUniversalController::class, 'index'])->name('index');
                Route::post('/{id}/mark-read', [NotificationUniversalController::class, 'markAsRead'])->name('mark-read');
                Route::post('/mark-all-read', [NotificationUniversalController::class, 'markAllRead'])->name('mark-all-read');
                Route::delete('/{id}', [NotificationUniversalController::class, 'destroy'])->name('destroy');
                Route::get('/unread-count', [NotificationUniversalController::class, 'unreadCount'])->name('unread-count');
                Route::get('/page', [NotificationPageController::class, 'index'])->name('page');
                Route::get('/preferensi', [NotificationPreferenceController::class, 'edit'])->name('preferences');
                Route::put('/preferensi', [NotificationPreferenceController::class, 'update'])->name('preferences.update');
            });

            // ── AI WORKSPACE (halaman penuh) ─────────────────────
            Route::get('ai', [AiWorkspaceController::class, 'index'])->name('ai.index')->middleware(DenyStudentOperatorAi::class);
            Route::post('ai/stream', [AiWorkspaceController::class, 'stream'])->name('ai.stream')->middleware(['throttle:20,1', DenyStudentOperatorAi::class]);

            // ── CHAT AI (widget pojok kanan) ─────────────────────
            Route::prefix('ai-chat')->name('ai-chat.')->middleware(DenyStudentOperatorAi::class)->group(function () {
                Route::get('/latest', [AiChatController::class, 'latest'])->name('latest');
                Route::get('/conversations', [AiChatController::class, 'conversations'])->name('conversations');
                Route::post('/send', [AiChatController::class, 'send'])->name('send')->middleware('throttle:20,1');
                Route::post('/{conversation}/resume-ai', [AiChatController::class, 'resumeAi'])->name('resume-ai');
                Route::post('/{conversation}/feedback', [AiChatController::class, 'feedback'])->name('feedback');
                Route::get('/{conversation}', [AiChatController::class, 'show'])->name('show');
                Route::delete('/{conversation}', [AiChatController::class, 'destroy'])->name('destroy');
            });

            // ── BAHASA: Mufradat, Pelanggaran, AM Nasyath Syahri ─────
            Route::prefix('bahasa')->name('bahasa.')->group(function () {
                Route::get('/', [BahasaDashboardController::class, 'index'])->name('dashboard');

                Route::prefix('academic')->name('academic.')->group(function () {
                    Route::get('/', [BahasaAcademicController::class, 'nilai'])->name('nilai');
                    Route::get('/progress', [BahasaAcademicController::class, 'progress'])->name('progress');
                    Route::get('/at-risk', [BahasaAcademicController::class, 'atRisk'])->name('at-risk');
                    Route::get('/leger', [BahasaAcademicController::class, 'leger'])->name('leger');
                    Route::get('/exams', [BahasaAcademicController::class, 'exams'])->name('exams');
                    Route::get('/report', [BahasaAcademicController::class, 'report'])->name('report');
                });

                Route::prefix('reference')->name('reference.')->group(function () {
                    Route::get('/teachers', [BahasaReferenceController::class, 'teachers'])->name('teachers');
                    Route::get('/subjects', [BahasaReferenceController::class, 'subjects'])->name('subjects');
                });

                Route::prefix('daily')->name('daily.')->group(function () {
                    Route::get('/', [LanguageDailyLogController::class, 'index'])->name('index');
                    Route::post('/', [LanguageDailyLogController::class, 'store'])->name('store');
                    Route::delete('/{log}', [LanguageDailyLogController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('certifications')->name('certifications.')->group(function () {
                    Route::get('/', [LanguageCertificationController::class, 'index'])->name('index');
                    Route::post('/', [LanguageCertificationController::class, 'store'])->name('store');
                    Route::get('/{certification}/print', [LanguageCertificationController::class, 'print'])->name('print');
                    Route::delete('/{certification}', [LanguageCertificationController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('mufradat')->name('mufradat.')->group(function () {
                    Route::get('/', [MufradatController::class, 'index'])->name('index');
                    Route::get('/create', [MufradatController::class, 'create'])->name('create');
                    Route::post('/', [MufradatController::class, 'store'])->name('store');

                    Route::get('/{period}/edit', [MufradatController::class, 'edit'])->name('edit');
                    Route::put('/{period}', [MufradatController::class, 'update'])->name('update');
                    Route::delete('/{period}', [MufradatController::class, 'destroy'])->name('destroy');
                    Route::post('/{period}/words', [MufradatController::class, 'storeWord'])->name('words.store');
                    Route::delete('/{period}/words/{word}', [MufradatController::class, 'destroyWord'])->name('words.destroy');
                    Route::post('/{period}/schedules', [MufradatController::class, 'storeSchedule'])->name('schedules.store');
                    Route::delete('/{period}/schedules/{schedule}', [MufradatController::class, 'destroySchedule'])->name('schedules.destroy');
                    Route::post('/{period}/mustamis', [MufradatController::class, 'storeMustami'])->name('mustamis.store');
                    Route::delete('/{period}/mustamis/{assignment}', [MufradatController::class, 'destroyMustami'])->name('mustamis.destroy');
                    Route::get('/{period}/attendance', [MufradatController::class, 'attendance'])->name('attendance');
                    Route::post('/{period}/attendance', [MufradatController::class, 'storeAttendance'])->name('attendance.store');
                    Route::get('/{period}/report', [MufradatController::class, 'report'])->name('report');
                    Route::get('/{period}/print-list', [MufradatController::class, 'printList'])->name('print-list');
                    Route::get('/{period}/print-attendance', [MufradatController::class, 'printAttendance'])->name('print-attendance');
                    Route::get('/{period}/print-report', [MufradatController::class, 'printReport'])->name('print-report');
                    Route::get('/{period}', [MufradatController::class, 'show'])->name('show');
                });

                Route::prefix('violations')->name('violations.')->group(function () {
                    Route::get('/', [LanguageViolationController::class, 'index'])->name('index');
                    Route::get('/report', [LanguageViolationController::class, 'report'])->name('report');
                    Route::get('/create', [LanguageViolationController::class, 'create'])->name('create');
                    Route::post('/', [LanguageViolationController::class, 'store'])->name('store');
                    Route::get('/{violation}/edit', [LanguageViolationController::class, 'edit'])->name('edit');
                    Route::put('/{violation}', [LanguageViolationController::class, 'update'])->name('update');
                    Route::delete('/{violation}', [LanguageViolationController::class, 'destroy'])->name('destroy');
                    Route::post('/{violation}/coachings', [LanguageViolationController::class, 'storeCoaching'])->name('coachings.store');
                    Route::get('/{violation}/print-pledge', [LanguageViolationController::class, 'printPledge'])->name('print-pledge');
                    Route::get('/{violation}/print-coaching', [LanguageViolationController::class, 'printCoaching'])->name('print-coaching');
                    Route::get('/{violation}', [LanguageViolationController::class, 'show'])->name('show');
                });

                Route::prefix('activities')->name('activities.')->group(function () {
                    Route::get('/', [LanguageActivityController::class, 'index'])->name('index');
                    Route::get('/create', [LanguageActivityController::class, 'create'])->name('create');
                    Route::post('/', [LanguageActivityController::class, 'store'])->name('store');
                    Route::get('/{activity}/edit', [LanguageActivityController::class, 'edit'])->name('edit');
                    Route::put('/{activity}', [LanguageActivityController::class, 'update'])->name('update');
                    Route::delete('/{activity}', [LanguageActivityController::class, 'destroy'])->name('destroy');
                    Route::get('/{activity}/print', [LanguageActivityController::class, 'print'])->name('print');
                    Route::get('/{activity}', [LanguageActivityController::class, 'show'])->name('show');
                });
            });

            // ── TAHFIDZ: MASTER MUQORROR & RPP ─────────────────────
            Route::prefix('tahfidz')->name('tahfidz.')->group(function () {
                Route::prefix('muqorror')->name('muqorror.')->group(function () {
                    Route::get('/', [TahfidzMasterController::class, 'muqorrorIndex'])->name('index');
                    Route::get('/create', [TahfidzMasterController::class, 'muqorrorCreate'])->name('create');
                    Route::post('/', [TahfidzMasterController::class, 'muqorrorStore'])->name('store');
                    Route::get('/{id}', [TahfidzMasterController::class, 'muqorrorShow'])->name('show');
                    Route::get('/{id}/edit', [TahfidzMasterController::class, 'muqorrorEdit'])->name('edit');
                    Route::put('/{id}', [TahfidzMasterController::class, 'muqorrorUpdate'])->name('update');
                    Route::delete('/{id}', [TahfidzMasterController::class, 'muqorrorDestroy'])->name('destroy');
                    Route::get('/{id}/print', [TahfidzMasterController::class, 'muqorrorPrint'])->name('print');
                });

                Route::prefix('rpp')->name('rpp.')->group(function () {
                    Route::get('/', [TahfidzMasterController::class, 'rppIndex'])->name('index');
                    Route::get('/create', [TahfidzMasterController::class, 'rppCreate'])->name('create');
                    Route::post('/', [TahfidzMasterController::class, 'rppStore'])->name('store');
                    Route::get('/{id}', [TahfidzMasterController::class, 'rppShow'])->name('show');
                    Route::get('/{id}/edit', [TahfidzMasterController::class, 'rppEdit'])->name('edit');
                    Route::put('/{id}', [TahfidzMasterController::class, 'rppUpdate'])->name('update');
                    Route::delete('/{id}', [TahfidzMasterController::class, 'rppDestroy'])->name('destroy');
                    Route::get('/{id}/print', [TahfidzMasterController::class, 'rppPrint'])->name('print');
                });
                Route::get('buku-kerja', [TahfidzWorkspaceController::class, 'index'])->name('workspace.index');
                Route::get('input-hafalan', [TahfidzInputController::class, 'index'])->name('input.index');
                Route::get('attendance', [TahfidzAttendanceController::class, 'index'])->name('attendance.index');
                Route::post('attendance', [TahfidzAttendanceController::class, 'store'])->name('attendance.store');
                Route::get('progress', [TahfidzProgressController::class, 'index'])->name('progress.index');
                Route::get('targets', [TahfidzTargetController::class, 'index'])->name('targets.index');
                Route::post('targets', [TahfidzTargetController::class, 'store'])->name('targets.store');
                Route::put('targets/{id}', [TahfidzTargetController::class, 'update'])->name('targets.update');
                Route::delete('targets/{id}', [TahfidzTargetController::class, 'destroy'])->name('targets.destroy');
                Route::get('audit', [TahfidzAuditController::class, 'index'])->name('audit.index');
                Route::get('consistency', [TahfidzConsistencyController::class, 'index'])->name('consistency.index');
                Route::get('executive', [TahfidzExecutiveController::class, 'index'])->name('executive.index');
                Route::post('audit/auto-verify', [TahfidzAuditController::class, 'autoVerifyPending'])->name('audit.auto-verify');
                Route::get('santri/{studentId}', [TahfidzSantriController::class, 'show'])->name('santri.show');
                Route::post('santri/{studentId}/interventions', [TahfidzInterventionController::class, 'store'])->name('santri.interventions.store');
                Route::get('interventions', [TahfidzInterventionController::class, 'index'])->name('interventions.index');
                Route::post('interventions/{interventionId}/complete', [TahfidzInterventionController::class, 'complete'])->name('interventions.complete');
                Route::get('monitoring', [TahfidzMonitoringController::class, 'index'])->name('monitoring.index');
                Route::post('monitoring/notify', [TahfidzMonitoringController::class, 'notify'])->name('monitoring.notify');

                Route::prefix('tasmi')->name('tasmi.')->group(function () {
                    Route::get('/', [TasmiController::class, 'index'])->name('index');
                    Route::post('/', [TasmiController::class, 'store'])->name('store');
                    Route::get('/certificates', [TasmiController::class, 'certificates'])->name('certificates');
                    Route::get('/certificates/audit', [TasmiController::class, 'certificateAudit'])->name('certificate.audit');
                    Route::get('/certificates/{certificateId}/print', [TasmiController::class, 'certificatePrint'])->name('certificate.print');
                    Route::post('/participants/{participantId}/score', [TasmiController::class, 'score'])->name('participant.score');
                    Route::post('/participants/{participantId}/verify', [TasmiController::class, 'verifyParticipant'])->name('participant.verify');
                    Route::get('/participants/{participantId}/instrument', [TasmiController::class, 'instrument'])->name('participant.instrument');
                    Route::post('/participants/{participantId}/certificate', [TasmiController::class, 'issueCertificate'])->name('participant.certificate');
                    Route::get('/flow', [TasmiController::class, 'flow'])->name('flow');
                    Route::get('/{id}', [TasmiController::class, 'show'])->name('show');
                    Route::put('/{id}', [TasmiController::class, 'update'])->name('update');
                    Route::delete('/{id}', [TasmiController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/participants', [TasmiController::class, 'addParticipant'])->name('participants.store');
                    Route::delete('/{id}/participants/{participantId}', [TasmiController::class, 'removeParticipant'])->name('participants.destroy');
                });

                Route::prefix('mutabaah')->name('mutabaah.')->group(function () {
                    Route::get('/', [MutabaahController::class, 'index'])->name('index');
                    Route::post('/', [MutabaahController::class, 'store'])->name('store');
                    Route::put('/{id}', [MutabaahController::class, 'update'])->name('update');
                    Route::delete('/{id}', [MutabaahController::class, 'destroy'])->name('destroy');
                    Route::get('/{studentId}/print', [MutabaahController::class, 'print'])->name('print');
                });

                Route::prefix('halaqah')->name('halaqah.')->group(function () {
                    Route::get('/', [HalaqahController::class, 'index'])->name('index');
                    Route::get('/create', [HalaqahController::class, 'create'])->name('create');
                    Route::post('/', [HalaqahController::class, 'store'])->name('store');
                    Route::get('/{id}', [HalaqahController::class, 'show'])->name('show');
                    Route::get('/{id}/edit', [HalaqahController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [HalaqahController::class, 'update'])->name('update');
                    Route::delete('/{id}', [HalaqahController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/members', [HalaqahController::class, 'addMembers'])->name('members.store');
                    Route::post('/{id}/targets', [HalaqahController::class, 'storeTarget'])->name('targets.store');
                    Route::put('/{id}/targets/{targetId}', [HalaqahController::class, 'updateTarget'])->name('targets.update');
                    Route::delete('/{id}/targets/{targetId}', [HalaqahController::class, 'destroyTarget'])->name('targets.destroy');
                    Route::post('/{id}/schedules', [HalaqahController::class, 'storeSchedule'])->name('schedules.store');
                    Route::put('/{id}/schedules/{scheduleId}', [HalaqahController::class, 'updateSchedule'])->name('schedules.update');
                    Route::delete('/{id}/schedules/{scheduleId}', [HalaqahController::class, 'destroySchedule'])->name('schedules.destroy');
                    Route::post('/{id}/members/{memberId}/remove', [HalaqahController::class, 'removeMember'])->name('members.remove');
                });

                Route::prefix('setoran')->name('setoran.')->group(function () {
                    Route::get('/', [SetoranController::class, 'index'])->name('index');
                    Route::get('/create', [SetoranController::class, 'create'])->name('create');
                    Route::post('/', [SetoranController::class, 'store'])->name('store');
                    Route::get('/{id}/edit', [SetoranController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [SetoranController::class, 'update'])->name('update');
                    Route::post('/{id}/verify', [SetoranController::class, 'verify'])->name('verify');
                    Route::delete('/{id}', [SetoranController::class, 'destroy'])->name('destroy');
                });

            });

            Route::prefix('perpustakaan')->name('perpustakaan.')->middleware(EnsureLibraryUnit::class)->group(function () {
                Route::get('/', [LibraryDashboardController::class, 'index'])->name('index');
                Route::get('/units', [LibraryUnitController::class, 'index'])->name('units.index');
                Route::post('/units', [LibraryUnitController::class, 'store'])->name('units.store');
                Route::put('/units/{id}', [LibraryUnitController::class, 'update'])->name('units.update');
                Route::post('/units/{id}/settings', [LibraryUnitController::class, 'updateSettings'])->name('units.settings');
                Route::get('/operators', [LibraryStudentOperatorController::class, 'index'])->name('operators.index');
                Route::post('/operators', [LibraryStudentOperatorController::class, 'store'])->name('operators.store');
                Route::post('/operators/{id}/toggle', [LibraryStudentOperatorController::class, 'toggle'])->name('operators.toggle');
                Route::post('/operators/{id}/reset-password', [LibraryStudentOperatorController::class, 'resetPassword'])->name('operators.reset-password');
                Route::get('/genres', [LibraryGenreController::class, 'index'])->name('genres.index');
                Route::post('/genres', [LibraryGenreController::class, 'store'])->name('genres.store');
                Route::put('/genres/{id}', [LibraryGenreController::class, 'update'])->name('genres.update');
                Route::delete('/genres/{id}', [LibraryGenreController::class, 'destroy'])->name('genres.destroy');
                Route::get('/catalog', [LibraryCatalogController::class, 'index'])->name('catalog.index');
                Route::post('/catalog/titles', [LibraryCatalogController::class, 'storeTitle'])->name('catalog.titles.store');
                Route::put('/catalog/titles/{id}', [LibraryCatalogController::class, 'updateTitle'])->name('catalog.titles.update');
                Route::delete('/catalog/titles/{id}', [LibraryCatalogController::class, 'destroyTitle'])->name('catalog.titles.destroy');
                Route::post('/catalog/items', [LibraryCatalogController::class, 'storeItem'])->name('catalog.items.store');
                Route::put('/catalog/items/{id}', [LibraryCatalogController::class, 'updateItem'])->name('catalog.items.update');
                Route::delete('/catalog/items/{id}', [LibraryCatalogController::class, 'destroyItem'])->name('catalog.items.destroy');
                Route::get('/catalog/labels', [LibraryCatalogController::class, 'printLabels'])->name('catalog.labels');
                Route::get('/members/students', [LibraryMemberController::class, 'searchStudents'])->name('members.students.search');
                Route::get('/members/gtk', [LibraryMemberController::class, 'searchGtk'])->name('members.gtk.search');
                Route::get('/members', [LibraryMemberController::class, 'index'])->name('members.index');
                Route::post('/members', [LibraryMemberController::class, 'store'])->name('members.store');
                Route::put('/members/{id}', [LibraryMemberController::class, 'update'])->name('members.update');
                Route::post('/members/{id}/replace-card', [LibraryMemberController::class, 'replaceCard'])->name('members.replace-card');
                Route::get('/import/members', [LibraryImportController::class, 'membersForm'])->name('imports.members.form');
                Route::post('/import/members', [LibraryImportController::class, 'membersStore'])->name('imports.members.store');
                Route::get('/import/members/template', [LibraryImportController::class, 'membersTemplate'])->name('imports.members.template');
                Route::get('/import/catalog', [LibraryImportController::class, 'catalogForm'])->name('imports.catalog.form');
                Route::post('/import/catalog', [LibraryImportController::class, 'catalogStore'])->name('imports.catalog.store');
                Route::get('/import/catalog/template', [LibraryImportController::class, 'catalogTemplate'])->name('imports.catalog.template');
                Route::get('/import/genres', [LibraryImportController::class, 'genresForm'])->name('imports.genres.form');
                Route::post('/import/genres', [LibraryImportController::class, 'genresStore'])->name('imports.genres.store');
                Route::get('/import/genres/template', [LibraryImportController::class, 'genresTemplate'])->name('imports.genres.template');
                Route::get('/members/{id}/card', [LibraryMemberController::class, 'printCard'])->name('members.card');
                Route::get('/members/cards/print', [LibraryMemberController::class, 'printCards'])->name('members.cards');
                Route::get('/loans', [LibraryLoanController::class, 'index'])->name('loans.index');
                Route::post('/loans', [LibraryLoanController::class, 'store'])->name('loans.store');
                Route::post('/loans/{id}/return', [LibraryLoanController::class, 'returnLoan'])->name('loans.return');
                Route::post('/loans/{id}/renew', [LibraryLoanController::class, 'renew'])->name('loans.renew');
                Route::get('/racks', [LibraryCatalogController::class, 'racks'])->name('racks.index');
                Route::post('/racks/{id}/move', [LibraryCatalogController::class, 'moveItem'])->name('racks.move');
                Route::get('/fines', [LibraryFineController::class, 'index'])->name('fines.index');
                Route::post('/fines/{id}/pay', [LibraryFineController::class, 'pay'])->name('fines.pay');
                Route::post('/fines/{id}/waive', [LibraryFineController::class, 'waive'])->name('fines.waive');
                Route::get('/reports', [LibraryReportController::class, 'index'])->name('reports.index');
                Route::get('/programs/{type}', [LibraryProgramController::class, 'index'])->name('programs.index')->where('type', 'procurement|donation|weeding|literacy');
                Route::post('/programs/{type}', [LibraryProgramController::class, 'store'])->name('programs.store')->where('type', 'procurement|donation|weeding|literacy');
                Route::put('/programs/{id}', [LibraryProgramController::class, 'update'])->name('programs.update');
                Route::delete('/programs/{id}', [LibraryProgramController::class, 'destroy'])->name('programs.destroy');
                Route::get('/visits', [LibraryVisitController::class, 'index'])->name('visits.index');
                Route::post('/visits', [LibraryVisitController::class, 'store'])->name('visits.store');
                Route::get('/rooms', [LibraryRoomController::class, 'index'])->name('rooms.index');
                Route::post('/rooms', [LibraryRoomController::class, 'store'])->name('rooms.store');
                Route::post('/rooms/{id}/approve', [LibraryRoomController::class, 'approve'])->name('rooms.approve');
                Route::post('/rooms/{id}/reject', [LibraryRoomController::class, 'reject'])->name('rooms.reject');
                Route::post('/rooms/{id}/complete', [LibraryRoomController::class, 'complete'])->name('rooms.complete');
                Route::get('/stock-opnames', [LibraryStockOpnameController::class, 'index'])->name('stock-opnames.index');
                Route::post('/stock-opnames', [LibraryStockOpnameController::class, 'store'])->name('stock-opnames.store');
                Route::post('/stock-opnames/{sessionId}/items/{itemId}', [LibraryStockOpnameController::class, 'updateItem'])->name('stock-opnames.items.update');
                Route::post('/stock-opnames/{id}/complete', [LibraryStockOpnameController::class, 'complete'])->name('stock-opnames.complete');
            });

            // ── SCHOOLS ───────────────────────────────────────────────
            Route::prefix('schools')->name('schools.')->group(function () {
                Route::get('/', [SchoolController::class, 'index'])->name('index');
                Route::get('/create', [SchoolController::class, 'create'])->name('create');
                Route::post('/', [SchoolController::class, 'store'])->name('store');
                Route::get('/global', [SchoolsGlobalController::class, 'index'])->name('global.index');

                // Satuan kerja sub-routes (sebelum {schoolId} wildcard)
                Route::prefix('satuan-kerja/{workUnitId}')->name('satuan-kerja.')->group(function () {
                    Route::get('/schools/{schoolId}', [SchoolUnitController::class, 'show'])->name('show');
                    Route::get('/schools/{schoolId}/edit', [SchoolUnitController::class, 'edit'])->name('edit');

                    // Jabatan GTK per satuan kerja
                    Route::get('/jabatan', [SatuanPositionController::class, 'index'])->name('positions');
                    Route::post('/jabatan/{id}/update', [SatuanPositionController::class, 'update'])->name('positions.update');
                    Route::post('/jabatan/mass-update', [SatuanPositionController::class, 'massUpdate'])->name('positions.mass-update');

                    // Tugas Tambahan GTK per satuan kerja
                    Route::get('/tugas-tambahan', [SatuanTaskController::class, 'additionalTasks'])->name('additional-tasks');
                    Route::post('/tugas-tambahan', [SatuanTaskController::class, 'storeAdditionalTask'])->name('additional-tasks.store');
                    Route::put('/tugas-tambahan/{id}', [SatuanTaskController::class, 'updateAdditionalTask'])->name('additional-tasks.update');
                    Route::delete('/tugas-tambahan/{id}', [SatuanTaskController::class, 'destroyAdditionalTask'])->name('additional-tasks.destroy');
                    Route::get('/jp-tugas-tambahan', [SatuanTaskController::class, 'taskHours'])->name('task-hours');
                    Route::put('/jp-tugas-tambahan', [SatuanTaskController::class, 'updateTaskHours'])->name('task-hours.update');

                    // Tugas Tambahan Guru per satuan kerja
                    Route::get('/tugas-guru', [SatuanTaskController::class, 'otherTasks'])->name('other-tasks');
                    Route::post('/tugas-guru', [SatuanTaskController::class, 'storeOtherTask'])->name('other-tasks.store');
                    Route::put('/tugas-guru/{id}', [SatuanTaskController::class, 'updateOtherTask'])->name('other-tasks.update');
                    Route::delete('/tugas-guru/{id}', [SatuanTaskController::class, 'destroyOtherTask'])->name('other-tasks.destroy');
                });

                // ── DATA NILAI
                Route::prefix('nilai')->name('nilai.')->group(function () {
                    Route::get('/', [NilaiController::class, 'index'])->name('index');
                    Route::get('/{adminBookId}/sts', [NilaiController::class, 'sts'])->name('sts');
                    Route::post('/{adminBookId}/sts', [NilaiController::class, 'storeSts'])->name('sts.store');
                    Route::get('/{adminBookId}/sas', [NilaiController::class, 'sas'])->name('sas');
                    Route::post('/{adminBookId}/sas', [NilaiController::class, 'storeSas'])->name('sas.store');
                    Route::post('/{adminBookId}/kunci', [NilaiController::class, 'lockBook'])->name('kunci');
                    Route::post('/{adminBookId}/buka-kunci', [NilaiController::class, 'unlockBook'])->name('buka-kunci');
                });

                // ── NILAI KELAS — untuk Admin TU / Waka / Wali Kelas / Kepsek
                Route::prefix('nilai-kelas')->name('nilai-kelas.')->group(function () {
                    Route::get('/', [NilaiKelasController::class, 'index'])->name('index');
                    Route::get('/{studyGroupId}/sts', [NilaiKelasController::class, 'sts'])->name('sts');
                    Route::post('/{studyGroupId}/sts', [NilaiKelasController::class, 'stsStore'])->name('sts.store');
                    Route::post('/{studyGroupId}/sts/kolom', [NilaiKelasController::class, 'stsColumns'])->name('sts.kolom');
                    Route::get('/{studyGroupId}/leger/cetak', [NilaiKelasController::class, 'legerCetak'])->name('leger.cetak');
                    Route::get('/{studyGroupId}/leger/download', [NilaiKelasController::class, 'legerDownload'])->name('leger.download');
                    Route::get('/{studyGroupId}/rapor', [NilaiKelasController::class, 'rapor'])->name('rapor');
                    Route::post('/{studyGroupId}/rapor/pengaturan', [NilaiKelasController::class, 'raporSettings'])->name('rapor.pengaturan');
                    Route::post('/{studyGroupId}/rapor/layout', [NilaiKelasController::class, 'raporLayoutSettings'])->name('rapor.layout');
                    Route::post('/{studyGroupId}/rapor/media', [NilaiKelasController::class, 'raporMediaSettings'])->name('rapor.media');
                    Route::post('/{studyGroupId}/rapor/mapel', [NilaiKelasController::class, 'raporSubjectSettings'])->name('rapor.subjects');
                    Route::post('/{studyGroupId}/rapor/ttd-wali', [NilaiKelasController::class, 'raporWaliTtd'])->name('rapor.wali-ttd');
                    Route::post('/{studyGroupId}/rapor/{studentId}/catatan', [NilaiKelasController::class, 'raporNote'])->name('rapor.note');
                    Route::post('/{studyGroupId}/rapor/finalize', [NilaiKelasController::class, 'raporFinalize'])->name('rapor.finalize');
                    Route::post('/{studyGroupId}/rapor/publish', [NilaiKelasController::class, 'raporPublish'])->name('rapor.publish');
                    Route::post('/{studyGroupId}/rapor/unpublish', [NilaiKelasController::class, 'raporUnpublish'])->name('rapor.unpublish');
                    Route::post('/{studyGroupId}/buku/{adminBookId}/publish', [NilaiKelasController::class, 'bookPublish'])->name('buku.publish');
                    Route::post('/{studyGroupId}/buku/{adminBookId}/unpublish', [NilaiKelasController::class, 'bookUnpublish'])->name('buku.unpublish');
                    Route::get('/{studyGroupId}/rapor/{studentId}/cetak', [NilaiKelasController::class, 'raporCetak'])->name('rapor.cetak');
                    Route::get('/{studyGroupId}/rapor/cetak-massal', [NilaiKelasController::class, 'raporCetakMassal'])->name('rapor.cetak-massal');
                });

                // ── GURU MAPEL — Buku Admin Guru
                // Route tulis dibungkus period.book: periode terkunci / bukan TA aktif → 403.
                Route::prefix('guru-mapel')->name('guru-mapel.')->group(function () {
                    Route::get('/', [NilaiGuruController::class, 'index'])->name('index');
                    Route::get('/{adminBookId}', [NilaiGuruController::class, 'wizard'])->name('wizard');
                    Route::get('/{adminBookId}/w1', [NilaiGuruController::class, 'wizard1'])->name('w1');
                    Route::get('/{adminBookId}/w2', [NilaiGuruController::class, 'wizard2'])->name('w2');
                    Route::get('/{adminBookId}/w3', [NilaiGuruController::class, 'wizard3'])->name('w3');
                    Route::get('/{adminBookId}/w4', [NilaiGuruController::class, 'wizard4'])->name('w4');
                    Route::get('/{adminBookId}/w5', [NilaiGuruController::class, 'wizard5'])->name('w5');
                    Route::get('/{adminBookId}/w6', [NilaiGuruController::class, 'wizard6'])->name('w6');

                    Route::middleware('period.book')->group(function () {
                        Route::post('/{adminBookId}/autosave', [NilaiGuruController::class, 'autoSave'])->name('autosave');
                        Route::post('/{adminBookId}/w1', [NilaiGuruController::class, 'wizard1Store'])->name('w1.store');
                        Route::post('/{adminBookId}/w2', [NilaiGuruController::class, 'wizard2Store'])->name('w2.store');
                        Route::post('/{adminBookId}/w3', [NilaiGuruController::class, 'wizard3Store'])->name('w3.store');
                        Route::post('/{adminBookId}/w3/bobot', [NilaiGuruController::class, 'wizard3Bobot'])->name('w3.bobot');
                        Route::post('/{adminBookId}/w3/kolom', [NilaiGuruController::class, 'wizard3Columns'])->name('w3.columns');
                        Route::post('/{adminBookId}/w3/visibilitas', [NilaiGuruController::class, 'wizard3Visibility'])->name('w3.visibility');
                        Route::post('/{adminBookId}/w4', [NilaiGuruController::class, 'wizard4Store'])->name('w4.store');
                        Route::post('/{adminBookId}/w4/kolom', [NilaiGuruController::class, 'wizard4Columns'])->name('w4.kolom');
                        Route::post('/{adminBookId}/w5', [NilaiGuruController::class, 'wizard5Store'])->name('w5.store');
                        Route::post('/{adminBookId}/w6', [NilaiGuruController::class, 'wizard6Store'])->name('w6.store');
                    });
                });

                // {schoolId} wildcard — harus di BAWAH semua literal routes
                Route::get('/{schoolId}', [SchoolController::class, 'show'])->name('show');
                Route::get('/{schoolId}/edit', [SchoolController::class, 'edit'])->name('edit');
                Route::put('/{schoolId}', [SchoolController::class, 'update'])->name('update');
                Route::post('/{schoolId}/media', [SchoolController::class, 'updateMedia'])->name('media');
                Route::delete('/{schoolId}', [SchoolController::class, 'destroy'])->name('destroy');
            });

            // ── SCHOOLS GLOBAL
            Route::get('/schools-global', [SchoolsGlobalController::class, 'index'])->name('schools-global.index');
            Route::get('/schools-global/kurikulum-profile', [CurriculumProfileBulkController::class, 'index'])->name('schools-global.kurikulum-profile.index');
            Route::post('/schools-global/kurikulum-profile', [CurriculumProfileBulkController::class, 'store'])->name('schools-global.kurikulum-profile.store');
            Route::post('/schools-global/kurikulum-profile/{batchId}/rollback', [CurriculumProfileBulkController::class, 'rollback'])->name('schools-global.kurikulum-profile.rollback');
            Route::get('/schools-global/index', [SchoolsGlobalController::class, 'index'])->name('schools-global-show.index');

            // ── GTK ────────────────────────────────────────────────
            Route::prefix('gtk')->name('gtk.')->group(function () {
                Route::get('/', [GtkWizardController::class, 'index'])->name('index');
                Route::get('/guru', [GtkWizardController::class, 'indexguru'])->name('indexguru');
                Route::get('/tendik', [GtkWizardController::class, 'indextendik'])->name('indextendik');
                Route::get('/create', [GtkWizardController::class, 'create'])->name('create');
                Route::post('/', [GtkWizardController::class, 'store'])->name('store');
                Route::get('/import', [GtkWizardController::class, 'import'])->name('import');
                Route::get('/import/template/{workUnitId}', [GtkWizardController::class, 'importTemplate'])->name('import.template');
                Route::post('/import/store', [GtkWizardController::class, 'importStore'])->name('import.store');

                // ── IMPORT NAMA ARAB GTK (menu terpisah; match NUPY) ──
                Route::get('/import-nama-arab', [GtkArabicNameController::class, 'form'])->name('import-nama-arab-form');
                Route::post('/import-nama-arab', [GtkArabicNameController::class, 'import'])->name('import-nama-arab-process');
                Route::get('/import-nama-arab/template', [GtkArabicNameController::class, 'template'])->name('import-nama-arab-template');
                Route::post('/import-nama-arab/generate', [GtkArabicNameController::class, 'generate'])->name('import-nama-arab-generate');

                // ── REVIEW & APPROVAL NAMA ARAB GTK ──
                Route::get('/review-nama-arab', [GtkArabicNameController::class, 'review'])->name('review-nama-arab');
                Route::post('/review-nama-arab/approve-all', [GtkArabicNameController::class, 'approveAll'])->name('review-nama-arab-approve-all');
                Route::post('/review-nama-arab/{gtkUserId}', [GtkArabicNameController::class, 'approve'])->name('review-nama-arab-approve');
                Route::post('/datatable', [GtkWizardController::class, 'datatable'])->name('datatable');
                Route::get('/export-preview', [GtkWizardController::class, 'exportPreview'])->name('export.preview');
                Route::get('/export', [GtkWizardController::class, 'export'])->name('export');
                Route::post('/verify-password', [GtkWizardController::class, 'verifyPassword'])->name('verify-password');
                Route::get('/satuan-kerja/{satuanKerja}', [GtkWizardController::class, 'indexByWorkUnit'])->name('by-work-unit');
                Route::post('/mass-update', [GtkWizardController::class, 'massUpdate'])->name('mass-update');
                Route::get('/massal', [GtkWizardController::class, 'massal'])->name('massal');

                // Education standalone (sebelum {uuid} wildcard)
                Route::prefix('educations')->name('educations.')->group(function () {
                    Route::post('/', [GTKEducationController::class, 'store'])->name('store');
                    Route::get('/{id}', [GTKEducationController::class, 'show'])->name('show');
                    Route::put('/{id}', [GTKEducationController::class, 'update'])->name('update');
                    Route::delete('/{id}', [GTKEducationController::class, 'destroy'])->name('destroy');
                });

                // GTK detail (UUID) — wildcard harus di bawah semua literal
                Route::prefix('{uuid}')->group(function () {
                    Route::get('/', [GtkWizardController::class, 'show'])->name('show');
                    Route::get('/edit', [GtkWizardController::class, 'edit'])->name('edit');
                    Route::put('/', [GtkWizardController::class, 'update'])->name('update');
                    Route::delete('/', [GtkWizardController::class, 'destroy'])->name('destroy');
                    Route::post('/toggle-status', [GtkWizardController::class, 'toggleStatus'])->name('toggle-status');
                    Route::post('/reset-password', [GtkWizardController::class, 'resetPassword'])->name('reset-password');

                    Route::prefix('profile')->name('profile.')->group(function () {
                        Route::get('/', [ProfileController::class, 'myProfile'])->name('show');
                        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
                        Route::put('/', [ProfileController::class, 'update'])->name('update');
                        Route::post('/photo', [ProfileController::class, 'uploadPhoto'])->name('photo.upload');
                        Route::delete('/photo', [ProfileController::class, 'deletePhoto'])->name('photo.delete');
                    });

                    Route::prefix('family-member')->name('family-member.')->group(function () {
                        Route::post('/store', [GtkWizardController::class, 'storeFamilyMember'])->name('store');
                        Route::get('/{id}/edit', [GtkWizardController::class, 'editFamilyMember'])->name('edit');
                        Route::put('/{id}', [GtkWizardController::class, 'updateFamilyMember'])->name('update');
                        Route::delete('/{id}', [GtkWizardController::class, 'deleteFamilyMember'])->name('delete');
                    });

                    Route::prefix('education')->name('education.')->group(function () {
                        Route::post('/store', [GtkWizardController::class, 'storeEducationModal'])->name('store');
                        Route::get('/{id}/edit', [GtkWizardController::class, 'editEducation'])->name('edit');
                        Route::put('/{id}', [GtkWizardController::class, 'updateEducation'])->name('update');
                        Route::delete('/{id}', [GtkWizardController::class, 'deleteEducation'])->name('delete');
                        Route::post('/{id}/verify', [GtkWizardController::class, 'verifyEducation'])->name('verify');
                    });

                    Route::prefix('health-data')->name('health-data.')->group(function () {
                        Route::post('/', [GtkWizardController::class, 'storeHealthData'])->name('store');
                        Route::put('/', [GtkWizardController::class, 'updateHealthData'])->name('update');
                    });
                });
            });

            // ── PENSION / PENSIUN ──────────────────────────────────
            Route::prefix('pension')->name('pension.')->group(function () {
                Route::get('/', [PensionController::class, 'index'])->name('index');
                Route::get('/settings', [PensionController::class, 'settings'])->name('settings');
                Route::post('/settings', [PensionController::class, 'updateSettings'])->name('settings.update');
                Route::get('/{uuid}', [PensionController::class, 'show'])->name('show');
                Route::get('/{uuid}/edit', [PensionController::class, 'edit'])->name('edit');
                Route::post('/{uuid}', [PensionController::class, 'update'])->name('update');
                Route::post('/datatable', [PensionController::class, 'datatable'])->name('datatable');
            });

            // ── MASTER DATA ────────────────────────────────────────
            Route::prefix('master-data')->name('master-data.')->group(function () {
                Route::get('/jenis-gtk', [MasterDataController::class, 'jenisGtkIndex'])->name('jenis-gtk.index');
                Route::post('/jenis-gtk', [MasterDataController::class, 'jenisGtkStore'])->name('jenis-gtk.store');
                Route::put('/jenis-gtk/{id}', [MasterDataController::class, 'jenisGtkUpdate'])->name('jenis-gtk.update');
                Route::delete('/jenis-gtk/{id}', [MasterDataController::class, 'jenisGtkDestroy'])->name('jenis-gtk.destroy');

                Route::get('/jabatan', [MasterDataController::class, 'jabatanIndex'])->name('jabatan.index');
                Route::post('/jabatan', [MasterDataController::class, 'jabatanStore'])->name('jabatan.store');
                Route::put('/jabatan/{id}', [MasterDataController::class, 'jabatanUpdate'])->name('jabatan.update');
                Route::delete('/jabatan/{id}', [MasterDataController::class, 'jabatanDestroy'])->name('jabatan.destroy');
                Route::get('/jabatan-by-jenis', [MasterDataController::class, 'getJabatanByJenis'])->name('jabatan.by-jenis');

                Route::get('/satuan-kerja', [MasterDataController::class, 'satuanKerjaIndex'])->name('satuan-kerja.index');
                Route::get('/satuan-kerja/{id}', [MasterDataController::class, 'satuanKerjaShow'])->name('satuan-kerja.show');
                Route::post('/satuan-kerja', [MasterDataController::class, 'satuanKerjaStore'])->name('satuan-kerja.store');
                Route::put('/satuan-kerja/{id}', [MasterDataController::class, 'satuanKerjaUpdate'])->name('satuan-kerja.update');
                Route::delete('/satuan-kerja/{id}', [MasterDataController::class, 'satuanKerjaDestroy'])->name('satuan-kerja.destroy');
                Route::post('/satuan-kerja/generate-code', [MasterDataController::class, 'satuanKerjaGenerateCode'])->name('satuan-kerja.generate-code');
                Route::post('/satuan-kerja/bulk-delete', [MasterDataController::class, 'satuanKerjaBulkDestroy'])->name('satuan-kerja.bulk-delete');
                Route::patch('/satuan-kerja/{id}/toggle-status', [MasterDataController::class, 'satuanKerjaToggleStatus'])->name('satuan-kerja.toggle-status');

                Route::get('/mata-pelajaran', [MataPelajaranController::class, 'index'])->name('mata-pelajaran.index');
                Route::post('/mata-pelajaran', [MataPelajaranController::class, 'store'])->name('mata-pelajaran.store');
                Route::post('/mata-pelajaran/{subjectId}/toggle', [MataPelajaranController::class, 'toggle'])->name('mata-pelajaran.toggle');
            });

            // ── KALDIK / AGENDA KEGIATAN ──────────────────────────
            Route::prefix('kaldik')->name('kaldik.')->group(function () {
                Route::get('/', [KaldikController::class, 'index'])->name('index');
                Route::get('/create', [KaldikController::class, 'create'])->name('create');
                Route::post('/', [KaldikController::class, 'store'])->name('store');
                Route::get('/{kaldikId}', [KaldikController::class, 'show'])->name('show');
                Route::get('/{kaldikId}/edit', [KaldikController::class, 'edit'])->name('edit');
                Route::put('/{kaldikId}', [KaldikController::class, 'update'])->name('update');
                Route::delete('/{kaldikId}', [KaldikController::class, 'destroy'])->name('destroy');
                Route::post('/{kaldikId}/toggle', [KaldikController::class, 'toggleActive'])->name('toggle');
            });

            // ── PEKAN EFEKTIF (turunan Kalender Pendidikan) ───────
            Route::prefix('pekan-efektif')->name('pekan-efektif.')->group(function () {
                Route::get('/', [PekanEfektifOverviewController::class, 'index'])->name('index');
            });

            // ── KURIKULUM & PERANGKAT PEMBELAJARAN ────────────────
            // Kalender → Pekan Efektif → JP Efektif → Kurikulum → CP → TP → ATP → Perangkat
            Route::prefix('kurikulum')->name('kurikulum.')->middleware('kurikulum.access')->group(function () {
                Route::prefix('profil')->name('profil.')->group(function () {
                    Route::get('/', [CurriculumProfileController::class, 'index'])->name('index');
                    Route::post('/', [CurriculumProfileController::class, 'store'])->name('store');
                });

                Route::prefix('proyek')->name('proyek.')->group(function () {
                    Route::get('/', [CurriculumProjectController::class, 'index'])->name('index');
                    Route::post('/', [CurriculumProjectController::class, 'store'])->name('store');
                    Route::put('/{id}', [CurriculumProjectController::class, 'update'])->name('update');
                    Route::delete('/{id}', [CurriculumProjectController::class, 'destroy'])->name('destroy');
                    Route::prefix('bank')->name('bank.')->group(function () {
                        Route::get('/', [CurriculumProjectBankController::class, 'index'])->name('index');
                        Route::post('/', [CurriculumProjectBankController::class, 'store'])->name('store');
                        Route::put('/{id}', [CurriculumProjectBankController::class, 'update'])->name('update');
                        Route::delete('/{id}', [CurriculumProjectBankController::class, 'destroy'])->name('destroy');
                    });

                    Route::get('/{id}/cetak', [CurriculumProjectController::class, 'print'])->name('print');
                    Route::get('/{id}/pdf', [CurriculumProjectController::class, 'pdf'])->name('pdf');
                    Route::post('/{id}/nilai', [CurriculumProjectController::class, 'assess'])->name('assess');
                    Route::delete('/{id}/nilai/{assessmentId}', [CurriculumProjectController::class, 'destroyAssessment'])->name('assessment.destroy');
                    Route::get('/{id}', [CurriculumProjectController::class, 'show'])->name('show');
                });

                Route::get('/', [KurikulumController::class, 'index'])->name('index');
                Route::post('/kktp', [KurikulumController::class, 'saveKktp'])->name('kktp.save');
                Route::post('/siapkan', [KurikulumController::class, 'siapkan'])->name('siapkan');

                // ── AI ASSISTANT — DRAFT RPM & SOAL ─────────────────
                Route::prefix('ai')->name('ai.')->group(function () {
                    Route::get('/', [AiContentController::class, 'index'])->name('index');
                    Route::post('/generate', [AiContentController::class, 'generate'])->name('generate');
                    Route::get('/{id}/edit', [AiContentController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [AiContentController::class, 'updateDraft'])->name('update');
                    Route::get('/{id}', [AiContentController::class, 'show'])->name('show');
                    Route::post('/{id}/items', [AiContentController::class, 'updateItems'])->name('items.update');
                    Route::post('/{id}/save-bank', [AiContentController::class, 'saveToBank'])->name('save-bank');
                    Route::post('/{id}/save-rpm', [AiContentController::class, 'saveToRpm'])->name('save-rpm');
                    Route::post('/{id}/save-project', [AiContentController::class, 'saveToProject'])->name('save-project');
                    Route::post('/{id}/approve', [AiContentController::class, 'approve'])->name('approve');
                    Route::delete('/{id}', [AiContentController::class, 'destroy'])->name('destroy');
                });

                // Master Rumpun Mata Pelajaran & penetapan koordinator (Kebijakan Akademik)
                Route::prefix('rumpun')->name('rumpun.')->group(function () {
                    Route::get('/', [SubjectGroupController::class, 'index'])->name('index');
                    Route::get('/lintas-satuan', [SubjectGroupController::class, 'crossSchool'])->name('cross-school');
                    Route::get('/{groupId}', [SubjectGroupController::class, 'show'])->name('show');
                    Route::put('/{groupId}/mapel', [SubjectGroupController::class, 'attachSubjects'])->name('subjects.attach');
                    Route::delete('/{groupId}/mapel/{subjectId}', [SubjectGroupController::class, 'detachSubject'])->name('subjects.detach');
                    Route::post('/{groupId}/koordinator', [SubjectGroupController::class, 'assignCoordinator'])->name('coordinator.assign');
                    Route::put('/{groupId}/koordinator/{taskId}', [SubjectGroupController::class, 'deactivateCoordinator'])->name('coordinator.deactivate');
                });

                // ── PERMINTAAN AKSES EDIT (keroyokan terkendali) ──
                Route::prefix('akses-edit')->name('akses-edit.')->group(function () {
                    Route::get('/', [KurikulumEditGrantController::class, 'index'])->name('index');
                    Route::post('/', [KurikulumEditGrantController::class, 'store'])->name('store');
                    Route::post('/{id}/approve', [KurikulumEditGrantController::class, 'approve'])->name('approve');
                    Route::post('/{id}/reject', [KurikulumEditGrantController::class, 'reject'])->name('reject');
                    Route::post('/{id}/revoke', [KurikulumEditGrantController::class, 'revoke'])->name('revoke');
                });

                Route::prefix('cp')->name('cp.')->group(function () {
                    Route::get('/', [CapaianPembelajaranController::class, 'index'])->name('index');
                    Route::post('/', [CapaianPembelajaranController::class, 'store'])->name('store');
                    Route::put('/{id}', [CapaianPembelajaranController::class, 'update'])->name('update');
                    Route::delete('/{id}', [CapaianPembelajaranController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('tp')->name('tp.')->group(function () {
                    Route::get('/', [TujuanPembelajaranController::class, 'index'])->name('index');
                    Route::post('/', [TujuanPembelajaranController::class, 'store'])->name('store');
                    Route::put('/{id}', [TujuanPembelajaranController::class, 'update'])->name('update');
                    Route::delete('/{id}', [TujuanPembelajaranController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/move', [TujuanPembelajaranController::class, 'move'])->name('move');
                });

                Route::prefix('atp')->name('atp.')->group(function () {
                    Route::get('/', [AlurTujuanPembelajaranController::class, 'index'])->name('index');
                    Route::post('/', [AlurTujuanPembelajaranController::class, 'store'])->name('store');
                    Route::get('/{id}', [AlurTujuanPembelajaranController::class, 'show'])->name('show');
                    Route::put('/{id}', [AlurTujuanPembelajaranController::class, 'update'])->name('update');
                    Route::delete('/{id}', [AlurTujuanPembelajaranController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/items', [AlurTujuanPembelajaranController::class, 'storeItem'])->name('items.store');
                    Route::put('/{id}/items/{itemId}', [AlurTujuanPembelajaranController::class, 'updateItem'])->name('items.update');
                    Route::delete('/{id}/items/{itemId}', [AlurTujuanPembelajaranController::class, 'destroyItem'])->name('items.destroy');
                    Route::post('/{id}/items/{itemId}/move', [AlurTujuanPembelajaranController::class, 'moveItem'])->name('items.move');
                });

                Route::prefix('perangkat')->name('perangkat.')->group(function () {
                    Route::get('/', [PerangkatPembelajaranController::class, 'index'])->name('index');
                    Route::post('/', [PerangkatPembelajaranController::class, 'store'])->name('store');
                    Route::get('/{id}', [PerangkatPembelajaranController::class, 'show'])->name('show');
                    Route::put('/{id}', [PerangkatPembelajaranController::class, 'update'])->name('update');
                    Route::delete('/{id}', [PerangkatPembelajaranController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/submit-review', [PerangkatPembelajaranController::class, 'submitReview'])->name('submit-review');
                    Route::post('/{id}/new-version', [PerangkatPembelajaranController::class, 'newVersion'])->name('new-version');
                });

                // ── PROTA — Program Tahunan ───────────────────────
                Route::prefix('prota')->name('prota.')->group(function () {
                    Route::get('/', [ProtaController::class, 'index'])->name('index');
                    Route::post('/', [ProtaController::class, 'store'])->name('store');
                    Route::get('/{id}', [ProtaController::class, 'show'])->name('show');
                    Route::put('/{id}', [ProtaController::class, 'update'])->name('update');
                    Route::delete('/{id}', [ProtaController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/sync', [ProtaController::class, 'sync'])->name('sync');
                    Route::post('/{id}/items', [ProtaController::class, 'storeItem'])->name('items.store');
                    Route::put('/{id}/items/{itemId}', [ProtaController::class, 'updateItem'])->name('items.update');
                    Route::delete('/{id}/items/{itemId}', [ProtaController::class, 'destroyItem'])->name('items.destroy');
                });

                // ── PROSEM — Program Semester ─────────────────────
                Route::prefix('prosem')->name('prosem.')->group(function () {
                    Route::get('/', [ProsemController::class, 'index'])->name('index');
                    Route::post('/', [ProsemController::class, 'store'])->name('store');
                    Route::get('/{id}', [ProsemController::class, 'show'])->name('show');
                    Route::put('/{id}', [ProsemController::class, 'update'])->name('update');
                    Route::delete('/{id}', [ProsemController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/sync', [ProsemController::class, 'sync'])->name('sync');
                    Route::put('/{id}/items/{itemId}/distribusi', [ProsemController::class, 'updateDistribusi'])->name('items.distribusi');
                    Route::post('/{id}/items/{itemId}/reset', [ProsemController::class, 'resetDistribusi'])->name('items.reset');
                });

                // ── REALISASI PEMBELAJARAN (Jurnal → TP/ATP → Asesmen) ──
                Route::get('/realisasi', [RealisasiPembelajaranController::class, 'index'])->name('realisasi.index');

                // ── CETAK PDF DOKUMEN KURIKULUM ───────────────────
                Route::prefix('cetak')->name('cetak.')->group(function () {
                    Route::get('/kaldik', [CetakDokumenKurikulumController::class, 'kaldik'])->name('kaldik');
                    Route::get('/pekan-efektif', [CetakDokumenKurikulumController::class, 'pekanEfektif'])->name('pekan-efektif');
                    Route::get('/atp/{id}', [CetakDokumenKurikulumController::class, 'atp'])->name('atp');
                    Route::get('/prota/{id}', [CetakDokumenKurikulumController::class, 'prota'])->name('prota');
                    Route::get('/prosem/{id}', [CetakDokumenKurikulumController::class, 'prosem'])->name('prosem');
                    Route::get('/rpm/{id}', [CetakDokumenKurikulumController::class, 'rpm'])->name('rpm');
                });
            });

            // ── JENJANG KARIR
            Route::prefix('jenjang-karir')->name('jenjang-karir.')->group(function () {
                Route::get('/career-path', [JenjangKarirController::class, 'careerPathIndex'])->name('career-path.index');
                Route::post('/career-path', [JenjangKarirController::class, 'careerPathStore'])->name('career-path.store');
                Route::put('/career-path/{id}', [JenjangKarirController::class, 'careerPathUpdate'])->name('career-path.update');
                Route::delete('/career-path/{id}', [JenjangKarirController::class, 'careerPathDestroy'])->name('career-path.destroy');

                Route::get('/mutasi-rotasi', [JenjangKarirController::class, 'mutasiIndex'])->name('mutasi.index');
                Route::post('/mutasi-rotasi', [JenjangKarirController::class, 'mutasiStore'])->name('mutasi.store');
                Route::put('/mutasi-rotasi/{id}', [JenjangKarirController::class, 'mutasiUpdate'])->name('mutasi.update');
                Route::post('/mutasi-rotasi/{id}/approve', [JenjangKarirController::class, 'mutasiApprove'])->name('mutasi.approve');
                Route::post('/mutasi-rotasi/{id}/reject', [JenjangKarirController::class, 'mutasiReject'])->name('mutasi.reject');
                Route::delete('/mutasi-rotasi/{id}', [JenjangKarirController::class, 'mutasiDestroy'])->name('mutasi.destroy');

                Route::get('/promosi-demosi', [JenjangKarirController::class, 'promosiIndex'])->name('promosi.index');
                Route::post('/promosi-demosi', [JenjangKarirController::class, 'promosiStore'])->name('promosi.store');
                Route::put('/promosi-demosi/{id}', [JenjangKarirController::class, 'promosiUpdate'])->name('promosi.update');
                Route::delete('/promosi-demosi/{id}', [JenjangKarirController::class, 'promosiDestroy'])->name('promosi.destroy');

                Route::get('/talent-pool', [JenjangKarirController::class, 'talentPoolIndex'])->name('talent.index');
                Route::post('/talent-pool', [JenjangKarirController::class, 'talentPoolStore'])->name('talent.store');
                Route::put('/talent-pool/{id}', [JenjangKarirController::class, 'talentPoolUpdate'])->name('talent.update');
                Route::delete('/talent-pool/{id}', [JenjangKarirController::class, 'talentPoolDestroy'])->name('talent.destroy');

                Route::get('/succession-plan', [JenjangKarirController::class, 'successionIndex'])->name('succession.index');
                Route::post('/succession-plan', [JenjangKarirController::class, 'successionStore'])->name('succession.store');
                Route::put('/succession-plan/{id}', [JenjangKarirController::class, 'successionUpdate'])->name('succession.update');
                Route::delete('/succession-plan/{id}', [JenjangKarirController::class, 'successionDestroy'])->name('succession.destroy');
                Route::post('/succession-plan/{id}/kandidat', [JenjangKarirController::class, 'successionKandidatStore'])->name('succession.kandidat.store');
                Route::delete('/succession-kandidat/{kandidatId}', [JenjangKarirController::class, 'successionKandidatDestroy'])->name('succession.kandidat.destroy');
            });

            // ── GTK RECRUITMENT ────────────────────────────────────
            Route::prefix('recruitment')->name('recruitment.')->group(function () {
                Route::get('/', [GtkRecruitmentController::class, 'index'])->name('index');
                Route::get('/create', [GtkRecruitmentController::class, 'create'])->name('create');
                Route::post('/', [GtkRecruitmentController::class, 'store'])->name('store');
                Route::prefix('{recruitmentUuid}')->group(function () {
                    Route::get('/', [GtkRecruitmentController::class, 'show'])->name('show');
                    Route::get('/edit', [GtkRecruitmentController::class, 'edit'])->name('edit');
                    Route::put('/', [GtkRecruitmentController::class, 'update'])->name('update');
                    Route::delete('/', [GtkRecruitmentController::class, 'destroy'])->name('destroy');
                });
            });

            // ── ATS RECRUITMENT ────────────────────────────────────
            Route::prefix('ats')->name('ats.')->group(function () {
                Route::get('/', [AtsDashboardController::class, 'index'])->name('index');
                Route::resource('jobs', JobController::class);
                Route::get('jobs/{job}/applications', [JobController::class, 'applications'])->name('jobs.applications');
                Route::post('jobs/{job}/duplicate', [JobController::class, 'duplicate'])->name('jobs.duplicate');
                Route::post('jobs/{job}/toggle-status', [JobController::class, 'toggleStatus'])->name('jobs.toggle-status');

                Route::get('jobs/{jobId}/pipeline', [RecruitmentPipelineController::class, 'index'])->name('pipeline.index');
                Route::get('jobs/{jobId}/pipeline/board', [RecruitmentPipelineController::class, 'board'])->name('pipeline.board');
                Route::get('jobs/{jobId}/pipeline/statistics', [RecruitmentPipelineController::class, 'getStatistics'])->name('pipeline.statistics');
                Route::post('jobs/{jobId}/pipeline/create', [RecruitmentPipelineController::class, 'createPipeline'])->name('pipeline.create');

                Route::resource('applications', ApplicationController::class);
                Route::get('applications/{application}/stages', [ApplicationController::class, 'stages'])->name('applications.stages');
                Route::post('applications/{application}/update-status', [ApplicationController::class, 'updateStatus'])->name('applications.update-status');
                Route::post('applications/{application}/update-nilai', [ApplicationController::class, 'updateNilai'])->name('applications.update-nilai');
                Route::post('applications/{application}/push-nilai', [ApplicationController::class, 'pushNilaiToRecruitment'])->name('applications.push-nilai');
                Route::post('applications/{application}/add-note', [ApplicationController::class, 'addNote'])->name('applications.add-note');
                Route::post('applications/{application}/send-message', [ApplicationController::class, 'sendMessage'])->name('applications.send-message');
                Route::post('applications/bulk-action', [ApplicationController::class, 'bulkAction'])->name('applications.bulk-action');
                Route::post('applications/announce-admin', [ApplicationController::class, 'announceAdminResults'])->name('applications.announce-admin');
                Route::get('applications/export/excel', [ApplicationController::class, 'exportExcel'])->name('applications.export-excel');
                Route::get('applications/export/pdf', [ApplicationController::class, 'exportPdf'])->name('applications.export-pdf');
                Route::post('applications/{applicationId}/move-next', [RecruitmentPipelineController::class, 'moveToNextStage'])->name('pipeline.move-next');
                Route::post('applications/{applicationId}/move-to-stage', [RecruitmentPipelineController::class, 'moveToStage'])->name('pipeline.move-to-stage');
                Route::get('applications/{application}/convert', [ApplicationController::class, 'convertToEmployee'])->name('applications.convert');
                Route::post('applications/{application}/convert', [ApplicationController::class, 'convertToEmployee'])->name('applications.convert');

                Route::resource('candidates', CandidateController::class);
                Route::post('candidates/{candidate}/verify-password', [CandidateController::class, 'verifyPassword'])->name('candidates.verify-password');
                Route::get('candidates/{candidate}/applications', [CandidateController::class, 'candidateApplications'])->name('candidates.applications');
                Route::get('candidates/{candidate}/download-cv', [CandidateController::class, 'downloadCv'])->name('candidates.download-cv');
                Route::get('candidates/{candidate}/timeline', [CandidateController::class, 'timeline'])->name('candidates.timeline');
                Route::post('candidates/{candidate}/add-skill', [CandidateController::class, 'addSkill'])->name('candidates.add-skill');
                Route::delete('candidates/{candidate}/remove-skill/{skill}', [CandidateController::class, 'removeSkill'])->name('candidates.remove-skill');
                Route::post('candidates/{candidate}/sync-documents', [CandidateController::class, 'syncDocuments'])->name('candidates.sync-documents');
                Route::post('candidates/{candidate}/sync-photo', [CandidateController::class, 'syncPhoto'])->name('candidates.sync-photo');

                Route::resource('interviews', InterviewController::class);
                Route::get('calendar-events', [InterviewController::class, 'calendarEvents'])->name('interviews.calendar-events');
                Route::post('interviews/{interview}/reschedule', [InterviewController::class, 'reschedule'])->name('interviews.reschedule');
                Route::post('interviews/{interview}/complete', [InterviewController::class, 'markComplete'])->name('interviews.complete');
                Route::post('interviews/{interview}/add-feedback', [InterviewController::class, 'addFeedback'])->name('interviews.add-feedback');
                Route::post('interviews/save-all', [InterviewController::class, 'saveAllResults'])->name('interviews.save-all');
                Route::post('interviews/announce-all', [InterviewController::class, 'announceAll'])->name('interviews.announce-all');
                Route::get('interviews/export', [InterviewController::class, 'exportResults'])->name('interviews.export');

                // Data Nilai - menampilkan semua pelamar dengan nilai & filter
                Route::prefix('data-nilai')->name('data-nilai.')->group(function () {
                    Route::get('/', [InterviewController::class, 'dataNilai'])->name('index');
                    Route::get('/dt', [InterviewController::class, 'dataNilaiDatatable'])->name('datatable');
                    Route::get('/export', [InterviewController::class, 'dataNilaiExport'])->name('export');
                });

                Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
                Route::get('reports/dashboard', [ReportController::class, 'dashboard'])->name('reports.dashboard');
                Route::get('reports/hiring-funnel', [ReportController::class, 'hiringFunnel'])->name('reports.hiring-funnel');
                Route::get('reports/time-to-hire', [ReportController::class, 'timeToHire'])->name('reports.time-to-hire');
                Route::get('reports/source-effectiveness', [ReportController::class, 'sourceEffectiveness'])->name('reports.source-effectiveness');
                Route::get('reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
                Route::post('reports/schedule', [ReportController::class, 'schedule'])->name('reports.schedule');

                Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
                Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
                Route::post('settings/stages', [SettingController::class, 'updateStages'])->name('settings.stages');
                Route::post('settings/email-templates', [SettingController::class, 'updateEmailTemplates'])->name('settings.email-templates');

                Route::get('applications/{application}/convert', [ApplicationController::class, 'convertToEmployee'])->name('applications.convert');
                Route::post('applications/{application}/convert', [ApplicationController::class, 'doConvert'])->name('applications.do-convert');

            });

            // ═══════════════════════════════════════════════════════════
            // PERSONDALIA — FULL HRD/SDM MENU
            // ═══════════════════════════════════════════════════════════

            // ── DASHBOARD PERSONALIA ──────────────────────────────────
            Route::get('/dashboard', [PersonaliaDashboardController::class, 'dashboard'])->name('dashboard');

            // ── ABSENSI GTK ───────────────────────────────────────────
            Route::prefix('absensi-gtk')->name('absensi-gtk.')->middleware('personalia.access:absensi')->group(function () {
                Route::get('/', [AbsensiGtkController::class, 'index'])->name('index');
                Route::get('/harian', [AbsensiGtkController::class, 'harian'])->name('harian');
                Route::get('/rekap-bulanan', [AbsensiGtkController::class, 'rekapBulanan'])->name('rekap-bulanan');
                Route::get('/izin', [AbsensiGtkController::class, 'izin'])->name('izin');
                Route::get('/settings', [AbsensiGtkController::class, 'settings'])->name('settings');
                Route::post('/settings', [AbsensiGtkController::class, 'settingsStore'])->name('settings.store');
                Route::post('/settings/qr', [AbsensiGtkController::class, 'qrSettingsStore'])->name('settings.qr');
                Route::post('/store', [AbsensiGtkController::class, 'store'])->name('store');
                Route::put('/{id}/update', [AbsensiGtkController::class, 'update'])->name('update');
                Route::post('/datatable', [AbsensiGtkController::class, 'datatable'])->name('datatable');
            });

            // ── ABSENSI GURU MAPEL VIA QR ─────────────────────────────
            Route::prefix('absensi-guru-mapel-qr')->name('teacher-qr.')->group(function () {
                Route::get('/scan', [TeacherQrScanController::class, 'scanIndex'])->name('scan');
                Route::match(['get', 'post'], '/scan/process/{study_group_id}', [TeacherQrScanController::class, 'scanProcess'])
                    ->name('scan.process');
                Route::post('/manual-checkin', [TeacherQrScanController::class, 'manualCheckin'])
                    ->middleware('permission:teacher-attendance_manual')
                    ->name('manual-checkin');
                // Riwayat: semua guru boleh melihat riwayatnya sendiri.
                // Scoping (own vs all) dilakukan di controller berdasarkan permission.
                Route::get('/history', [TeacherQrScanController::class, 'history'])
                    ->name('history');
                Route::get('/history/export', [TeacherQrScanController::class, 'exportHistory'])
                    ->name('history.export');
                Route::post('/manual-checkout', [TeacherQrScanController::class, 'manualCheckout'])
                    ->middleware('permission:teacher-attendance_manual')
                    ->name('manual-checkout');
                Route::get('/waka-dashboard', [TeacherQrScanController::class, 'wakaDashboard'])
                    ->middleware('permission:teacher-attendance_view')
                    ->name('waka-dashboard');
                Route::get('/manual', [TeacherQrScanController::class, 'manualIndex'])
                    ->middleware('permission:teacher-attendance_manual')
                    ->name('manual');
                Route::post('/manual', [TeacherQrScanController::class, 'manualStore'])
                    ->middleware('permission:teacher-attendance_manual')
                    ->name('manual.store');
            });

            // QR kelas (untuk cetak / bagi ke guru) — public signed URL
            Route::get('/qr', [ClassQrController::class, 'index'])
                ->name('qr.index');
            Route::get('/qr/{study_group_id}/image', [ClassQrController::class, 'qrImage'])
                ->name('qr.image');
            Route::get('/qr/{study_group_id}', [ClassQrController::class, 'show'])
                ->name('qr.show');
            Route::post('/qr/{study_group_id}/regenerate', [ClassQrController::class, 'regenerate'])
                ->name('qr.regenerate');
            Route::get('/qr/{study_group_id}/print', [ClassQrController::class, 'print'])
                ->name('qr.print');

            // ── PAYROLL & GAJI ────────────────────────────────────────
            Route::prefix('payroll')->name('payroll.')->middleware('personalia.access:payroll')->group(function () {
                Route::get('/', [PayrollController::class, 'index'])->name('index');
                Route::get('/create', [PayrollController::class, 'create'])->name('create');
                Route::post('/', [PayrollController::class, 'store'])->name('store');
                Route::get('/generate', [PayrollController::class, 'generateForm'])->name('generate');
                Route::post('/generate', [PayrollController::class, 'generate'])->name('generate.store');
                Route::get('/master', [PayrollController::class, 'master'])->name('master');
                Route::post('/master/rules', [PayrollController::class, 'updateRules'])->name('master.rules');
                Route::post('/master/tunjangan', [PayrollController::class, 'tunjanganStore'])->name('master.tunjangan.store');
                Route::put('/master/tunjangan/{id}', [PayrollController::class, 'tunjanganUpdate'])->name('master.tunjangan.update');
                Route::delete('/master/tunjangan/{id}', [PayrollController::class, 'tunjanganDestroy'])->name('master.tunjangan.destroy');
                Route::post('/master/import', [PayrollController::class, 'importMaster'])->name('master.import');
                Route::get('/master/template/{type}', [PayrollController::class, 'templateMaster'])->name('master.template');
                Route::post('/{id}/publish', [PayrollController::class, 'publish'])->name('publish');
                Route::post('/{id}/pay', [PayrollController::class, 'pay'])->name('pay');
                Route::get('/{id}/edit', [PayrollController::class, 'edit'])->name('edit');
                Route::put('/{id}', [PayrollController::class, 'update'])->name('update');
                Route::delete('/{id}', [PayrollController::class, 'destroy'])->name('destroy');
                Route::get('/potongan', [PayrollController::class, 'potongan'])->name('potongan');
                Route::get('/tunjangan', [PayrollController::class, 'tunjangan'])->name('tunjangan');
                Route::get('/bpjstk', [PayrollController::class, 'bpjstk'])->name('bpjstk');
                Route::get('/bpjs-kes', [PayrollController::class, 'bpjsKes'])->name('bpjs-kes');
                Route::get('/settings', [PayrollController::class, 'settings'])->name('settings');
                Route::post('/datatable', [PayrollController::class, 'datatable'])->name('datatable');
            });
            Route::prefix('payroll-slip')->name('payroll-slip.')->group(function () {
                Route::get('/', [PayrollController::class, 'slipIndex'])->name('index');
                Route::get('/{id}', [PayrollController::class, 'slipShow'])->name('show');
                Route::get('/{id}/pdf', [PayrollController::class, 'slipPdf'])->name('pdf');
            });

            // ── CUTI & IZIN ───────────────────────────────────────────
            Route::prefix('cuti')->name('cuti.')->group(function () {
                Route::get('/', [CutiController::class, 'index'])->name('index');
                Route::get('/create', [CutiController::class, 'create'])->name('create');
                Route::post('/', [CutiController::class, 'store'])->name('store');
                Route::get('/{id}', [CutiController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [CutiController::class, 'edit'])->name('edit');
                Route::put('/{id}', [CutiController::class, 'update'])->name('update');
                Route::delete('/{id}', [CutiController::class, 'destroy'])->name('destroy');
                Route::get('/approval', [CutiController::class, 'approval'])->name('approval')->middleware('personalia.access:cuti');
                Route::post('/{id}/approve', [CutiController::class, 'approve'])->name('approve')->middleware('personalia.access:cuti');
                Route::post('/{id}/reject', [CutiController::class, 'reject'])->name('reject')->middleware('personalia.access:cuti');
                Route::get('/rekap', [CutiController::class, 'rekap'])->name('rekap')->middleware('personalia.access:cuti');
                Route::get('/rekap/export', [CutiController::class, 'rekapExport'])->name('rekap.export')->middleware('personalia.access:cuti');
                Route::get('/rekap/excel', [CutiController::class, 'rekapExcel'])->name('rekap.excel')->middleware('personalia.access:cuti');
                Route::get('/rekap/pdf', [CutiController::class, 'rekapPdf'])->name('rekap.pdf')->middleware('personalia.access:cuti');
                Route::get('/quota', [CutiController::class, 'quota'])->name('quota')->middleware('personalia.access:cuti');
                Route::get('/settings', [CutiController::class, 'settings'])->name('settings')->middleware('personalia.access:cuti');
                Route::post('/settings', [CutiController::class, 'settingsStore'])->name('settings.store')->middleware('personalia.access:cuti');
                Route::post('/datatable', [CutiController::class, 'datatable'])->name('datatable');
            });

            // ── KONTRAK KERJA ─────────────────────────────────────────
            Route::prefix('kontrak')->name('kontrak.')->middleware('personalia.access:kontrak')->group(function () {
                Route::get('/', [KontrakController::class, 'index'])->name('index');
                Route::get('/create', [KontrakController::class, 'create'])->name('create');
                Route::post('/', [KontrakController::class, 'store'])->name('store');
                Route::get('/{id}', [KontrakController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [KontrakController::class, 'edit'])->name('edit');
                Route::put('/{id}', [KontrakController::class, 'update'])->name('update');
                Route::delete('/{id}', [KontrakController::class, 'destroy'])->name('destroy');
                Route::post('/{id}/perpanjang', [KontrakController::class, 'perpanjang'])->name('perpanjang');
                Route::get('/{id}/generate', [KontrakController::class, 'generate'])->name('generate');
                Route::get('/expiring', [KontrakController::class, 'expiring'])->name('expiring');
                Route::get('/template', [KontrakController::class, 'template'])->name('template');
                Route::post('/template', [KontrakController::class, 'templateStore'])->name('template.store');
                Route::put('/template/{id}', [KontrakController::class, 'templateUpdate'])->name('template.update');
                Route::delete('/template/{id}', [KontrakController::class, 'templateDestroy'])->name('template.destroy');
                Route::post('/expiring/remind', [KontrakController::class, 'remindExpiring'])->name('expiring.remind');
                Route::get('/settings', [KontrakController::class, 'settings'])->name('settings');
                Route::post('/datatable', [KontrakController::class, 'datatable'])->name('datatable');
            });

            // ── PENILAIAN KINERJA ─────────────────────────────────────
            Route::prefix('kinerja')->name('kinerja.')->middleware('personalia.access:kinerja')->group(function () {
                Route::get('/', [KinerjaController::class, 'index'])->name('index');
                Route::get('/create', [KinerjaController::class, 'create'])->name('create');
                Route::post('/', [KinerjaController::class, 'store'])->name('store');
                Route::get('/{id}', [KinerjaController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [KinerjaController::class, 'edit'])->name('edit');
                Route::put('/{id}', [KinerjaController::class, 'update'])->name('update');
                Route::delete('/{id}', [KinerjaController::class, 'destroy'])->name('destroy');
                Route::post('/{id}/finalize', [KinerjaController::class, 'finalize'])->name('finalize');
                Route::get('/periode', [KinerjaController::class, 'periode'])->name('periode');
                Route::post('/periode', [KinerjaController::class, 'periodeStore'])->name('periode.store');
                Route::put('/periode/{id}', [KinerjaController::class, 'periodeUpdate'])->name('periode.update');
                Route::delete('/periode/{id}', [KinerjaController::class, 'periodeDestroy'])->name('periode.destroy');
                Route::get('/indikator', [KinerjaController::class, 'indikator'])->name('indikator');
                Route::post('/komponen', [KinerjaController::class, 'komponenStore'])->name('komponen.store');
                Route::put('/komponen/{id}', [KinerjaController::class, 'komponenUpdate'])->name('komponen.update');
                Route::post('/indikator', [KinerjaController::class, 'indikatorStore'])->name('indikator.store');
                Route::put('/indikator/{id}', [KinerjaController::class, 'indikatorUpdate'])->name('indikator.update');
                Route::delete('/indikator/{id}', [KinerjaController::class, 'indikatorDestroy'])->name('indikator.destroy');
                Route::get('/reward', [KinerjaController::class, 'reward'])->name('reward');
                Route::post('/reward', [KinerjaController::class, 'rewardStore'])->name('reward.store');
                Route::delete('/reward/{id}', [KinerjaController::class, 'rewardDestroy'])->name('reward.destroy');
                Route::post('/punishment', [KinerjaController::class, 'punishmentStore'])->name('punishment.store');
                Route::delete('/punishment/{id}', [KinerjaController::class, 'punishmentDestroy'])->name('punishment.destroy');
                Route::get('/laporan', [KinerjaController::class, 'laporan'])->name('laporan');
                Route::get('/laporan/export', [KinerjaController::class, 'laporanExport'])->name('laporan.export');
                Route::get('/laporan/excel', [KinerjaController::class, 'laporanExcel'])->name('laporan.excel');
                Route::get('/laporan/pdf', [KinerjaController::class, 'laporanPdf'])->name('laporan.pdf');
                Route::post('/datatable', [KinerjaController::class, 'datatable'])->name('datatable');
            });

            // ── PELATIHAN & PENGEMBANGAN ─────────────────────────────
            Route::prefix('pelatihan')->name('pelatihan.')->middleware('personalia.access:pelatihan')->group(function () {
                Route::get('/', [PelatihanController::class, 'index'])->name('index');
                Route::get('/create', [PelatihanController::class, 'create'])->name('create');
                Route::post('/', [PelatihanController::class, 'store'])->name('store');
                Route::get('/{id}', [PelatihanController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [PelatihanController::class, 'edit'])->name('edit');
                Route::put('/{id}', [PelatihanController::class, 'update'])->name('update');
                Route::delete('/{id}', [PelatihanController::class, 'destroy'])->name('destroy');
                Route::get('/{id}/peserta', [PelatihanController::class, 'peserta'])->name('peserta');
                Route::post('/{id}/peserta-daftar', [PelatihanController::class, 'pesertaDaftar'])->name('peserta.daftar');
                Route::post('/peserta/{pesertaId}/{status}', [PelatihanController::class, 'pesertaUpdateStatus'])->name('peserta.status');
                Route::delete('/peserta/{pesertaId}', [PelatihanController::class, 'pesertaHapus'])->name('peserta.destroy');
                Route::get('/sertifikasi', [PelatihanController::class, 'sertifikasi'])->name('sertifikasi');
                Route::post('/sertifikasi', [PelatihanController::class, 'sertifikasiStore'])->name('sertifikasi.store');
                Route::get('/rekap', [PelatihanController::class, 'rekap'])->name('rekap');
                Route::post('/datatable', [PelatihanController::class, 'datatable'])->name('datatable');
            });

            // ── KESEJAHTERAAN GTK ─────────────────────────────────────
            Route::prefix('kesejahteraan')->name('kesejahteraan.')->middleware('personalia.access:kesejahteraan')->group(function () {
                Route::get('/', [KesejahteraanController::class, 'index'])->name('index');
                Route::get('/create', [KesejahteraanController::class, 'create'])->name('create');
                Route::post('/', [KesejahteraanController::class, 'store'])->name('store');
                Route::get('/{id}', [KesejahteraanController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [KesejahteraanController::class, 'edit'])->name('edit');
                Route::put('/{id}', [KesejahteraanController::class, 'update'])->name('update');
                Route::delete('/{id}', [KesejahteraanController::class, 'destroy'])->name('destroy');
                Route::get('/asuransi', [KesejahteraanController::class, 'asuransi'])->name('asuransi');
                Route::post('/asuransi', [KesejahteraanController::class, 'bpjsStore'])->name('bpjs.store');
                Route::put('/asuransi/{id}', [KesejahteraanController::class, 'bpjsUpdate'])->name('bpjs.update');
                Route::delete('/asuransi/{id}', [KesejahteraanController::class, 'bpjsDestroy'])->name('bpjs.destroy');
                Route::get('/benefit', [KesejahteraanController::class, 'benefit'])->name('benefit');
                Route::post('/benefit', [KesejahteraanController::class, 'benefitStore'])->name('benefit.store');
                Route::put('/benefit/{id}', [KesejahteraanController::class, 'benefitUpdate'])->name('benefit.update');
                Route::delete('/benefit/{id}', [KesejahteraanController::class, 'benefitDestroy'])->name('benefit.destroy');
                Route::get('/klaim', [KesejahteraanController::class, 'klaim'])->name('klaim');
                Route::post('/klaim', [KesejahteraanController::class, 'klaimStore'])->name('klaim.store');
                Route::post('/klaim/{id}/{status}', [KesejahteraanController::class, 'klaimProses'])->name('klaim.proses');
                Route::get('/laporan', [KesejahteraanController::class, 'laporan'])->name('laporan');
                Route::post('/datatable', [KesejahteraanController::class, 'datatable'])->name('datatable');
            });

            // ── PERATURAN & KEBIJAKAN ─────────────────────────────────
            Route::prefix('peraturan')->name('peraturan.')->middleware('personalia.access:peraturan')->group(function () {
                Route::get('/', [PeraturanController::class, 'index'])->name('index');
                Route::get('/create', [PeraturanController::class, 'create'])->name('create');
                Route::post('/', [PeraturanController::class, 'store'])->name('store');
                Route::get('/{id}', [PeraturanController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [PeraturanController::class, 'edit'])->name('edit');
                Route::put('/{id}', [PeraturanController::class, 'update'])->name('update');
                Route::delete('/{id}', [PeraturanController::class, 'destroy'])->name('destroy');
                Route::get('/kategori', [PeraturanController::class, 'kategori'])->name('kategori');
                Route::post('/kategori', [PeraturanController::class, 'kategoriStore'])->name('kategori.store');
                Route::put('/kategori/{id}', [PeraturanController::class, 'kategoriUpdate'])->name('kategori.update');
                Route::delete('/kategori/{id}', [PeraturanController::class, 'kategoriDestroy'])->name('kategori.destroy');
                // Note: controller uses violation* method names (Pelanggaran = violation)
                Route::get('/pelanggaran', [PeraturanController::class, 'violation'])->name('pelanggaran');
                Route::post('/pelanggaran', [PeraturanController::class, 'violationStore'])->name('pelanggaran.store');
                Route::get('/pelanggaran/{id}', [PeraturanController::class, 'violation'])->name('pelanggaran.show');
                Route::put('/pelanggaran/{id}', [PeraturanController::class, 'violationUpdate'])->name('pelanggaran.update');
                Route::delete('/pelanggaran/{id}', [PeraturanController::class, 'violationDestroy'])->name('pelanggaran.destroy');
                // English alias routes
                Route::get('/violation', [PeraturanController::class, 'violation'])->name('violation');
                Route::post('/violation', [PeraturanController::class, 'violationStore'])->name('violation.store');
                Route::put('/violation/{id}', [PeraturanController::class, 'violationUpdate'])->name('violation.update');
                Route::delete('/violation/{id}', [PeraturanController::class, 'violationDestroy'])->name('violation.destroy');
                // Acknowledge read log
                Route::post('/{id}/acknowledge', [PeraturanController::class, 'acknowledge'])->name('acknowledge');
                Route::post('/datatable', [PeraturanController::class, 'datatable'])->name('datatable');
            });

            // ── JAM KERJA & SHIFT ─────────────────────────────────────
            Route::prefix('jam-kerja')->name('jam-kerja.')->middleware('personalia.access:jam-kerja')->group(function () {
                Route::get('/', [JamKerjaController::class, 'index'])->name('index');
                Route::get('/create', [JamKerjaController::class, 'create'])->name('create');
                Route::post('/', [JamKerjaController::class, 'store'])->name('store');
                Route::get('/{id}/edit', [JamKerjaController::class, 'edit'])->name('edit');
                Route::put('/{id}', [JamKerjaController::class, 'update'])->name('update');
                Route::delete('/{id}', [JamKerjaController::class, 'destroy'])->name('destroy');
                Route::get('/shift', [JamKerjaController::class, 'shift'])->name('shift');
                Route::get('/kalender', [JamKerjaController::class, 'kalender'])->name('kalender');
                Route::post('/datatable', [JamKerjaController::class, 'datatable'])->name('datatable');
            });

            // ── GTK REQUESTS ───────────────────────────────────────
            Route::prefix('humas')->name('humas.')->group(function () {
                Route::get('/media-contacts', [HumasMediaContactController::class, 'index'])->name('media-contacts.index');
                Route::post('/media-contacts', [HumasMediaContactController::class, 'store'])->name('media-contacts.store');
                Route::put('/media-contacts/{id}', [HumasMediaContactController::class, 'update'])->name('media-contacts.update');
                Route::delete('/media-contacts/{id}', [HumasMediaContactController::class, 'destroy'])->name('media-contacts.destroy');
                Route::get('/guest-visits', [HumasGuestVisitController::class, 'index'])->name('guest-visits.index');
                Route::post('/guest-visits', [HumasGuestVisitController::class, 'store'])->name('guest-visits.store');
                Route::post('/guest-visits/{id}/checkout', [HumasGuestVisitController::class, 'checkout'])->name('guest-visits.checkout');
                Route::get('/satisfaction', [HumasSatisfactionController::class, 'index'])->name('satisfaction.index');
                Route::post('/satisfaction', [HumasSatisfactionController::class, 'store'])->name('satisfaction.store');
                Route::post('/satisfaction/{id}/publish', [HumasSatisfactionController::class, 'publish'])->name('satisfaction.publish');
                Route::delete('/satisfaction/{id}', [HumasSatisfactionController::class, 'destroy'])->name('satisfaction.destroy');
                Route::get('/announcements', [HumasAnnouncementController::class, 'index'])->name('announcements.index');
                Route::post('/announcements', [HumasAnnouncementController::class, 'store'])->name('announcements.store');
                Route::put('/announcements/{id}', [HumasAnnouncementController::class, 'update'])->name('announcements.update');
                Route::post('/announcements/{id}/publish', [HumasAnnouncementController::class, 'publish'])->name('announcements.publish');
                Route::delete('/announcements/{id}', [HumasAnnouncementController::class, 'destroy'])->name('announcements.destroy');
                Route::get('/complaints', [HumasComplaintController::class, 'index'])->name('complaints.index');
                Route::post('/complaints', [HumasComplaintController::class, 'store'])->name('complaints.store');
                Route::post('/complaints/{id}/respond', [HumasComplaintController::class, 'respond'])->name('complaints.respond');
            });

            Route::get('/announcements', [HumasAnnouncementController::class, 'feed'])->name('announcements.index');
            Route::get('/announcements/{id}', [HumasAnnouncementController::class, 'show'])->name('announcements.show');
            Route::get('/survei', [HumasSatisfactionController::class, 'feed'])->name('satisfaction.index');
            Route::post('/survei/{id}', [HumasSatisfactionController::class, 'submit'])->name('satisfaction.submit');
            Route::prefix('gtk-requests')->name('gtk-requests.')->group(function () {
                Route::get('/', [GtkRequestController::class, 'index'])->name('index');
                Route::get('/create', [GtkRequestController::class, 'create'])->name('create');
                Route::post('/', [GtkRequestController::class, 'store'])->name('store');
                Route::post('/{id}/submit', [GtkRequestController::class, 'submit'])->name('submit');
                Route::post('/{id}/approve', [GtkRequestController::class, 'approve'])->name('approve');
                Route::post('/{id}/reject', [GtkRequestController::class, 'reject'])->name('reject');
                Route::prefix('{requestUuid}')->group(function () {
                    Route::get('/', [GtkRequestController::class, 'show'])->name('show');
                    Route::get('/edit', [GtkRequestController::class, 'edit'])->name('edit');
                    Route::put('/', [GtkRequestController::class, 'update'])->name('update');
                    Route::delete('/', [GtkRequestController::class, 'destroy'])->name('destroy');
                });
            });

            // ── APPROVALS ──────────────────────────────────────────
            Route::prefix('approvals')->name('approvals.')->group(function () {
                Route::get('/', [ApprovalController::class, 'index'])->name('index');
                Route::get('/my-pending', [ApprovalController::class, 'myPending'])->name('my-pending');
                Route::get('/history', [ApprovalController::class, 'history'])->name('history');
                Route::prefix('{approvalUuid}')->group(function () {
                    Route::get('/', [ApprovalController::class, 'show'])->name('show');
                    Route::post('/approve', [ApprovalController::class, 'approve'])->name('approve');
                    Route::post('/reject', [ApprovalController::class, 'reject'])->name('reject');
                    Route::get('/track', [ApprovalController::class, 'track'])->name('track');
                });
            });

            // ── ACADEMIC YEARS ─────────────────────────────────────
            Route::prefix('academic-years')->name('academic-years.')->group(function () {
                Route::get('/', [AcademicYearController::class, 'index'])->name('index');
                Route::get('/create', [AcademicYearController::class, 'create'])->name('create');
                Route::post('/', [AcademicYearController::class, 'store'])->name('store');
                Route::get('/{id}', [AcademicYearController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [AcademicYearController::class, 'edit'])->name('edit');
                Route::put('/{id}', [AcademicYearController::class, 'update'])->name('update');
                Route::delete('/{id}', [AcademicYearController::class, 'destroy'])->name('destroy');
                Route::get('/{id}/toggle-active', [AcademicYearController::class, 'toggleActive'])->name('toggle-active');
            });

            // ── ARSIP & PENUTUPAN PERIODE (Inventarisasi Arsip) ────
            Route::prefix('arsip')->name('arsip.')->group(function () {
                Route::get('/', [ArsipInventarisController::class, 'index'])->name('index');
                Route::get('/dokumen/{documentId}', [ArsipInventarisController::class, 'download'])->name('dokumen.download');
                Route::post('/{id}/kunci', [ArsipInventarisController::class, 'lock'])->name('lock');
                Route::post('/{id}/buka', [ArsipInventarisController::class, 'unlock'])->name('unlock');
                Route::post('/{id}/rapor', [ArsipInventarisController::class, 'arsipkanRapor'])->name('rapor');
                Route::post('/{id}/leger', [ArsipInventarisController::class, 'arsipkanLeger'])->name('leger');
            });

            // ── GRADE LEVELS ───────────────────────────────────────
            Route::prefix('grade-levels')->name('grade-levels.')->group(function () {
                Route::get('/', [GradeLevelController::class, 'index'])->name('index');
                Route::get('/create', [GradeLevelController::class, 'create'])->name('create');
                Route::post('/', [GradeLevelController::class, 'store'])->name('store');
                Route::get('/{id}', [GradeLevelController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [GradeLevelController::class, 'edit'])->name('edit');
                Route::put('/{id}', [GradeLevelController::class, 'update'])->name('update');
                Route::delete('/{id}', [GradeLevelController::class, 'destroy'])->name('destroy');
                Route::post('/{id}/subjects/add', [GradeLevelController::class, 'addSubject'])->name('subjects.add');
                Route::delete('/{id}/subjects/remove', [GradeLevelController::class, 'removeSubject'])->name('subjects.remove');
                Route::post('/{id}/kktp', [GradeLevelController::class, 'saveKktp'])->name('kktp.save');
            });

            // ── SUBJECTS ───────────────────────────────────────────
            Route::prefix('subjects')->name('subjects.')->group(function () {
                Route::get('/', [SubjectController::class, 'index'])->name('index');
                Route::get('/create', [SubjectController::class, 'create'])->name('create');
                Route::get('/pemadatan/create', [SubjectController::class, 'createPemadatan'])->name('pemadatan.create');
                Route::post('/pemadatan', [SubjectController::class, 'storePemadatan'])->name('pemadatan.store');
                Route::post('/', [SubjectController::class, 'store'])->name('store');
                Route::get('/{id}', [SubjectController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [SubjectController::class, 'edit'])->name('edit');
                Route::put('/{id}/names', [SubjectController::class, 'updateNames'])->name('names.update');
                Route::put('/{id}', [SubjectController::class, 'update'])->name('update');
                Route::delete('/{id}', [SubjectController::class, 'destroy'])->name('destroy');
            });

            // ── INSTITUTION DECREES ────────────────────────────────
            Route::prefix('institution-decrees')->name('institution-decrees.')->group(function () {
                Route::get('/', [InstitutionDecreeController::class, 'index'])->name('index');
                Route::get('/create', [InstitutionDecreeController::class, 'create'])->name('create');
                Route::post('/', [InstitutionDecreeController::class, 'store'])->name('store');
                Route::get('/{id}', [InstitutionDecreeController::class, 'show'])->name('show');
                Route::get('/{id}/print', [InstitutionDecreeController::class, 'print'])->name('print');
                Route::get('/{id}/edit', [InstitutionDecreeController::class, 'edit'])->name('edit');
                Route::put('/{id}', [InstitutionDecreeController::class, 'update'])->name('update');
                Route::delete('/{id}', [InstitutionDecreeController::class, 'destroy'])->name('destroy');
            });

            // ── DOKUMEN ISO ─────────────────────────────────────────
            Route::prefix('dokumen-iso')->name('dokumen-iso.')->group(function () {
                Route::get('/', [DokumenIsoController::class, 'index'])->name('index');
                Route::post('/', [DokumenIsoController::class, 'store'])->name('store');
                Route::put('/{id}', [DokumenIsoController::class, 'update'])->name('update');
                Route::delete('/{id}', [DokumenIsoController::class, 'destroy'])->name('destroy');
                Route::get('/subscriptions', [DokumenIsoController::class, 'subscriptions'])->name('subscriptions');
                Route::post('/subscriptions/{divisiId}/subscribe', [DokumenIsoController::class, 'subscribe'])->name('subscribe');
                Route::post('/subscriptions/{divisiId}/unsubscribe', [DokumenIsoController::class, 'unsubscribe'])->name('unsubscribe');
            });

            // ── DIVISI ──────────────────────────────────────────────
            Route::prefix('divisi')->name('divisi.')->group(function () {
                Route::get('/', [DivisiController::class, 'index'])->name('index');
                Route::post('/', [DivisiController::class, 'store'])->name('store');
                Route::put('/{id}', [DivisiController::class, 'update'])->name('update');
                Route::delete('/{id}', [DivisiController::class, 'destroy'])->name('destroy');
            });

            // ── STUDY GROUPS API (for matrix builder) ─────────────
            Route::get('/study-groups/by-school', [StudyGroupApiController::class, 'bySchool'])->name('study-groups.by-school');

            // ── TEACHING ASSIGNMENTS ───────────────────────────────
            Route::prefix('teaching-assignments')->name('teaching-assignments.')->group(function () {
                Route::get('/', [TeachingAssignmentController::class, 'index'])->name('index');
                Route::get('/create', [TeachingAssignmentController::class, 'create'])->name('create');
                Route::get('/bulk', [TeachingAssignmentController::class, 'bulkCreate'])->name('bulk-create');
                Route::post('/bulk', [TeachingAssignmentController::class, 'bulkStore'])->name('bulk-store');
                Route::post('/', [TeachingAssignmentController::class, 'store'])->name('store');
                // Matriks (literal, harus sebelum /{id})
                Route::get('/matrix/{decree_id}/edit', [TeachingAssignmentController::class, 'editMatrix'])->name('edit-matrix');
                Route::put('/matrix/{decree_id}', [TeachingAssignmentController::class, 'updateMatrix'])->name('update-matrix');
                Route::get('/{id}', [TeachingAssignmentController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [TeachingAssignmentController::class, 'edit'])->name('edit');
                Route::put('/{id}', [TeachingAssignmentController::class, 'update'])->name('update');
                Route::delete('/{id}', [TeachingAssignmentController::class, 'destroy'])->name('destroy');
            });

            // ── OTHER TEACHER TASKS (Tugas Tambahan GTK) ─────────────
            Route::prefix('other-teacher-tasks')->name('other-teacher-tasks.')->group(function () {
                Route::get('/', [OtherTeacherTaskController::class, 'index'])->name('index');
                Route::post('/', [OtherTeacherTaskController::class, 'store'])->name('store');
                Route::put('/{id}', [OtherTeacherTaskController::class, 'update'])->name('update');
                Route::delete('/{id}', [OtherTeacherTaskController::class, 'destroy'])->name('destroy');
            });

            // ── PENUGASAN GTK DARI PONDOK ─────────────────────────
            Route::prefix('cross-unit-assignments')->name('cross-unit-assignments.')->group(function () {
                Route::get('/', [CrossUnitAssignmentController::class, 'index'])->name('index');
                Route::post('/', [CrossUnitAssignmentController::class, 'store'])->name('store');
                Route::put('/{id}', [CrossUnitAssignmentController::class, 'update'])->name('update');
                Route::patch('/{id}/toggle', [CrossUnitAssignmentController::class, 'toggle'])->name('toggle');
            });

            // ── GTK ADDITIONAL TASKS ───────────────────────────────
            Route::prefix('gtk-additional-tasks')->name('gtk-additional-tasks.')->group(function () {
                Route::get('/', [GtkAdditionalTaskController::class, 'index'])->name('index');
                Route::get('/{id}', [GtkAdditionalTaskController::class, 'show'])->name('show');
                Route::get('/create', [GtkAdditionalTaskController::class, 'create'])->name('create');
                Route::post('/', [GtkAdditionalTaskController::class, 'store'])->name('store');
                Route::get('/{id}/edit', [GtkAdditionalTaskController::class, 'edit'])->name('edit');
                Route::put('/{id}', [GtkAdditionalTaskController::class, 'update'])->name('update');
                Route::delete('/{id}', [GtkAdditionalTaskController::class, 'destroy'])->name('destroy');
            });

            // ── GTK POSITION PROPOSALS ─────────────────────────────
            Route::prefix('gtk-position-proposals')->name('gtk-position-proposals.')->group(function () {
                Route::get('/', [PositionProposalController::class, 'index'])->name('index');
                Route::get('/create', [PositionProposalController::class, 'create'])->name('create');
                Route::post('/', [PositionProposalController::class, 'store'])->name('store');
                Route::get('/{uuid}', [PositionProposalController::class, 'show'])->name('show');
                Route::post('/{uuid}/approve', [PositionProposalController::class, 'approve'])->name('approve');
                Route::post('/{uuid}/reject', [PositionProposalController::class, 'reject'])->name('reject');
                Route::post('/{uuid}/cancel', [PositionProposalController::class, 'cancel'])->name('cancel');
            });

            // ── GTK POSITION MANAGEMENT ────────────────────────────
            Route::prefix('gtk-positions')->name('gtk-positions.')->group(function () {
                Route::get('/', [GtkPositionController::class, 'index'])->name('index');
                Route::post('/{id}/update', [GtkPositionController::class, 'update'])->name('update');
                Route::post('/mass-update', [GtkPositionController::class, 'massUpdate'])->name('mass-update');
            });

            // ── KALKULASI NILAI ───────────────────────────────────
            // Route::prefix('kalkulasi-nilai')->name('kalkulasi-nilai.')->group(function () {
            //     Route::get('/', [UserKalkulasiNilaiController::class, 'index'])->name('index');
            //     Route::get('/{id}', [UserKalkulasiNilaiController::class, 'show'])->name('show');
            //     Route::post('/calculate', [UserKalkulasiNilaiController::class, 'calculate'])->name('calculate');
            // });

            // ── STUDY GROUPS ───────────────────────────────────────
            Route::prefix('study-groups')->name('study-groups.')->group(function () {
                Route::get('/', [StudyGroupController::class, 'index'])->name('index');
                Route::get('/create', [StudyGroupController::class, 'create'])->name('create');
                Route::post('/', [StudyGroupController::class, 'store'])->name('store');
                Route::get('/{id}', [StudyGroupController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [StudyGroupController::class, 'edit'])->name('edit');
                Route::put('/{id}', [StudyGroupController::class, 'update'])->name('update');
                Route::delete('/{id}', [StudyGroupController::class, 'destroy'])->name('destroy');

                // ── STUDY GROUP SUBJECTS ───────────────────────────────
                Route::get('/{id}/subjects', [StudyGroupSubjectController::class, 'indexView'])->name('subjects.index');
                Route::get('/{id}/subjects/available', [StudyGroupSubjectController::class, 'available'])->name('subjects.available');
                Route::get('/{id}/subjects/create', [StudyGroupSubjectController::class, 'create'])->name('subjects.create');
                Route::post('/{id}/subjects', [StudyGroupSubjectController::class, 'store'])->name('subjects.store');
                Route::post('/{id}/subjects/bulk', [StudyGroupSubjectController::class, 'bulkStore'])->name('subjects.bulk-store');
                Route::get('/{id}/subjects/{assignmentId}', [StudyGroupSubjectController::class, 'show'])->name('subjects.show');
                Route::get('/{id}/subjects/{assignmentId}/edit', [StudyGroupSubjectController::class, 'edit'])->name('subjects.edit');
                Route::put('/{id}/subjects/{assignmentId}', [StudyGroupSubjectController::class, 'update'])->name('subjects.update');
                Route::delete('/{id}/subjects/{assignmentId}', [StudyGroupSubjectController::class, 'destroy'])->name('subjects.destroy');
            });

            // ── JADWAL KBM (PELAJARAN) ─────────────────────────────
            Route::prefix('jadwal-kbm')->name('jadwal-kbm.')->group(function () {
                Route::get('/', [JadwalKbmController::class, 'index'])->name('index');
                Route::get('/generate', [JadwalKbmController::class, 'generateIndex'])->name('generate');
                Route::post('/generate', [JadwalKbmController::class, 'generate'])->name('generate.store');
                Route::post('/generate/{studyGroupId}', [JadwalKbmController::class, 'generateSingle'])->name('generate.execute');
                Route::get('/teacher/{teacherId}', [JadwalKbmController::class, 'forTeacher'])->name('for-teacher');
                Route::get('/{studyGroupId}', [JadwalKbmController::class, 'show'])->name('show');
                Route::get('/{studyGroupId}/edit', [JadwalKbmController::class, 'edit'])->name('edit');
                Route::put('/{studyGroupId}', [JadwalKbmController::class, 'update'])->name('update');
                Route::get('/{studyGroupId}/cetak', [JadwalKbmController::class, 'cetak'])->name('cetak');
            });

            // ── MASTER JAM PELAJARAN (slot waktu & istirahat) ──────
            Route::prefix('jam-pelajaran')->name('jam-pelajaran.')->group(function () {
                Route::get('/', [JamPelajaranController::class, 'index'])->name('index');
                Route::post('/', [JamPelajaranController::class, 'store'])->name('store');
                Route::post('/template', [JamPelajaranController::class, 'template'])->name('template');
                Route::put('/{slotId}', [JamPelajaranController::class, 'update'])->name('update');
                Route::delete('/{slotId}', [JamPelajaranController::class, 'destroy'])->name('destroy');
            });

            // ── ASESMEN WORKSPACE ───────────────────────────────────
            Route::prefix('asesmen')->name('asesmen.')->group(function () {
                Route::get('/', [AsesmenController::class, 'index'])->name('index');
                Route::get('/cetak', [AsesmenController::class, 'cetak'])->name('cetak');
                Route::get('/{kisiUuid}', [AsesmenController::class, 'show'])->name('show');
            });

            // ── EVALUASI: KISI-KISI SOAL ────────────────────────────
            Route::prefix('kisi-kisi-soal')->name('kisi-kisi-soal.')->group(function () {
                Route::get('/', [KisiKisiController::class, 'index'])->name('index');
                Route::get('/create', [KisiKisiController::class, 'create'])->name('create');
                Route::post('/', [KisiKisiController::class, 'store'])->name('store');
                Route::get('/{kisiUuid}/cetak', [KisiKisiController::class, 'cetak'])->name('cetak');
                Route::get('/{kisiUuid}', [KisiKisiController::class, 'show'])->name('show');
                Route::get('/{kisiUuid}/edit', [KisiKisiController::class, 'edit'])->name('edit');
                Route::put('/{kisiUuid}', [KisiKisiController::class, 'update'])->name('update');
                Route::delete('/{kisiUuid}', [KisiKisiController::class, 'destroy'])->name('destroy');
            });

            // ── EVALUASI: BANK SOAL ──────────────────────────────────
            Route::prefix('bank-soal')->name('bank-soal.')->group(function () {
                Route::get('/', [BankSoalController::class, 'index'])->name('index');
                Route::get('/create', [BankSoalController::class, 'create'])->name('create');
                Route::post('/', [BankSoalController::class, 'store'])->name('store');
                Route::get('/{id}', [BankSoalController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [BankSoalController::class, 'edit'])->name('edit');
                Route::put('/{id}', [BankSoalController::class, 'update'])->name('update');
                Route::delete('/{id}', [BankSoalController::class, 'destroy'])->name('destroy');
                Route::get('/{bankId}/soal', [BankSoalController::class, 'soalList'])->name('soal-list');
                Route::post('/{id}/clone', [BankSoalController::class, 'clone'])->name('clone');
            });

            // ── EVALUASI: SOAL (nested under bank-soal) ──────────────
            Route::prefix('bank-soal/{bankId}/soal')->name('soal.')->group(function () {
                Route::get('/create', [SoalController::class, 'create'])->name('create');
                Route::post('/wacana', [SoalController::class, 'storeWacana'])->name('wacana.store');
                Route::post('/wacana/upload', [SoalController::class, 'uploadWacanaImage'])->name('wacana.upload');
                Route::delete('/wacana/{wacanaId}', [SoalController::class, 'destroyWacana'])->name('wacana.destroy');
                Route::put('/wacana/{wacanaId}', [SoalController::class, 'updateWacana'])->name('wacana.update');
                Route::post('/', [SoalController::class, 'store'])->name('store');
                Route::get('/{id}/edit', [SoalController::class, 'edit'])->name('edit');
                Route::put('/{id}', [SoalController::class, 'update'])->name('update');
                Route::delete('/{id}', [SoalController::class, 'destroy'])->name('destroy');
                Route::post('/{id}/submit-review', [SoalController::class, 'submitForReview'])->name('submit-review');
                Route::post('/{id}/approve', [SoalController::class, 'approve'])->name('approve');
            });

            // ── EVALUASI: PAKET SOAL (SUMATIF) ──────────────────────
            Route::prefix('paket-soal')->name('paket-soal.')->group(function () {
                Route::get('/', [PaketSoalController::class, 'index'])->name('index');
                Route::get('/create/{kisiKisiId}', [PaketSoalController::class, 'create'])->name('create');
                Route::post('/{kisiKisiId}', [PaketSoalController::class, 'store'])->name('store');
                Route::get('/{paketUuid}', [PaketSoalController::class, 'show'])->name('show');
                Route::post('/{paketUuid}/publish', [PaketSoalController::class, 'publish'])->name('publish');
                Route::post('/{paketUuid}/layout', [PaketSoalController::class, 'simpanLayout'])->name('layout');
                Route::post('/{paketUuid}/unpublish', [PaketSoalController::class, 'unpublish'])->name('unpublish');
                Route::post('/{paketUuid}/reroll', [PaketSoalController::class, 'reroll'])->name('reroll');
                Route::delete('/{paketUuid}', [PaketSoalController::class, 'destroy'])->name('destroy');

                // Bank Soal Terpusat: quality gate → approval → distribusi
                Route::post('/{paketUuid}/quality-gate', [PaketSoalController::class, 'qualityGate'])->name('quality-gate');
                Route::post('/{paketUuid}/submit-approval', [PaketSoalController::class, 'submitApproval'])->name('submit-approval');
                Route::get('/{paketUuid}/distribusi', [PaketSoalController::class, 'distribution'])->name('distribusi');
                Route::post('/{paketUuid}/distribute', [PaketSoalController::class, 'distribute'])->name('distribute');
                Route::put('/{paketUuid}/komposisi', [PaketSoalController::class, 'simpanKomposisi'])->name('komposisi.simpan');
                Route::post('/{paketUuid}/komposisi/generate', [PaketSoalController::class, 'generateKomposisi'])->name('komposisi.generate');
                Route::get('/{paketUuid}/naskah', [PaketSoalController::class, 'naskah'])->name('naskah');
                Route::get('/{paketUuid}/naskah/cetak', [PaketSoalController::class, 'naskahCetak'])->name('naskah.cetak');
                Route::get('/{paketUuid}/kartu-soal', [PaketSoalController::class, 'kartuSoal'])->name('kartu-soal');
                Route::get('/{paketUuid}/kartu-soal/cetak', [PaketSoalController::class, 'kartuSoalCetak'])->name('kartu-soal.cetak');
                Route::get('/{paketUuid}/validasi-soal', [PaketSoalController::class, 'validasiSoal'])->name('validasi-soal');
                Route::get('/{paketUuid}/validasi-soal/cetak', [PaketSoalController::class, 'validasiSoalCetak'])->name('validasi-soal.cetak');
                Route::put('/{paketUuid}/distribusi/{distributionId}/terima', [PaketSoalController::class, 'terimaDistribusi'])->name('distribusi.terima');
            });

            // ── BANK SOAL TERPUSAT (LINTAS SATUAN PENDIDIKAN) ───────
            Route::prefix('bank-soal-terpusat')->name('bank-soal-terpusat.')->group(function () {
                Route::get('/', [BankSoalTerpusatController::class, 'index'])->name('index');
                Route::post('/{soalId}/reuse', [BankSoalTerpusatController::class, 'reuse'])->name('reuse');
                Route::get('/{soalId}/detail', [BankSoalTerpusatController::class, 'detail'])->name('detail');
                Route::get('/{soalId}/compare/{comparedId}', [BankSoalTerpusatController::class, 'compare'])->name('compare');
            });

            // ── REVIEW GURU SERUMPUN (SOAL & PAKET) ─────────────────
            Route::prefix('review-soal')->name('review-soal.')->group(function () {
                Route::get('/', [ReviewSoalController::class, 'index'])->name('index');
                Route::get('/{assignmentId}', [ReviewSoalController::class, 'show'])->name('show');
                Route::post('/{assignmentId}/decide', [ReviewSoalController::class, 'decide'])->name('decide');
            });

            // ── TATA USAHA: PAKET FINAL (CETAK/PERBANYAK) ───────────
            Route::prefix('tu-paket-soal')->name('tu-paket-soal.')->group(function () {
                Route::get('/', [PaketSoalTuController::class, 'index'])->name('index');
                Route::post('/{paketUuid}/print-jobs', [PaketSoalTuController::class, 'storePrintJob'])->name('print-jobs.store');
                Route::put('/{paketUuid}/print-jobs/{jobId}', [PaketSoalTuController::class, 'updatePrintJob'])->name('print-jobs.update');
            });

            // ── LJK SMART SCANNER (lembar jawaban + kamera HP) ──────
            Route::prefix('ljk')->name('ljk.')->group(function () {
                Route::get('/', [LjkController::class, 'index'])->name('index');
                Route::get('/create', [LjkController::class, 'create'])->name('create');
                Route::post('/', [LjkController::class, 'store'])->name('store');

                // Gambar scan privat (3 segmen — di atas /{ljkUuid} agar tidak bentrok).
                Route::get('/scan/gambar/{scanUuid}', [LjkScanController::class, 'image'])->name('scan.image');

                Route::get('/{ljkUuid}', [LjkController::class, 'show'])->name('show');
                Route::get('/{ljkUuid}/soal', [LjkController::class, 'questions'])->name('soal');
                Route::get('/{ljkUuid}/soal/pdf', [LjkController::class, 'soalPdf'])->name('soal.pdf');
                Route::get('/{ljkUuid}/pdf', [LjkController::class, 'pdf'])->name('pdf');
                Route::get('/{ljkUuid}/preview', [LjkController::class, 'preview'])->name('preview');
                Route::get('/{ljkUuid}/kandidat', [LjkController::class, 'candidates'])->name('candidates');
                Route::post('/{ljkUuid}/batal', [LjkController::class, 'cancel'])->name('cancel');

                Route::get('/{ljkUuid}/scan', [LjkScanController::class, 'page'])->name('scan');
                Route::post('/{ljkUuid}/scan', [LjkScanController::class, 'store'])->name('scan.store');

                Route::get('/{ljkUuid}/hasil', [LjkResultController::class, 'index'])->name('hasil');
                Route::get('/{ljkUuid}/hasil/{participantUuid}', [LjkResultController::class, 'participant'])->name('hasil.peserta');
                Route::post('/{ljkUuid}/jawaban/{answerUuid}/review', [LjkResultController::class, 'review'])->name('review');
                Route::post('/{ljkUuid}/final', [LjkResultController::class, 'finalize'])->name('finalize');
                Route::post('/{ljkUuid}/sinkron', [LjkResultController::class, 'sync'])->name('sync');
                Route::get('/{ljkUuid}/koreksi', [LjkResultController::class, 'corrections'])->name('corrections');
            });
            // ── STUDENT PROMOTIONS ────────────────────────────────
            Route::prefix('student-promotions')->name('student-promotions.')->group(function () {
                Route::get('/', [StudentPromotionController::class, 'index'])->name('index');
                Route::get('/create', [StudentPromotionController::class, 'create'])->name('create');
                Route::post('/', [StudentPromotionController::class, 'store'])->name('store');
                Route::get('/{id}', [StudentPromotionController::class, 'show'])->name('show');
                Route::put('/{id}/cancel', [StudentPromotionController::class, 'cancel'])->name('cancel');
                Route::put('/{id}/execute', [StudentPromotionController::class, 'execute'])->name('execute');
                Route::delete('/{id}', [StudentPromotionController::class, 'destroy'])->name('destroy');
                // Detail update
                Route::put('/{id}/detail/{detailId}', [StudentPromotionController::class, 'updateDetail'])->name('update-detail');
            });

            // ── STUDENTS ───────────────────────────────────────────
            // ── PENCARIAN GLOBAL (TOPBAR) ─────────────────────────
            Route::get('/search', [GlobalSearchController::class, 'index'])->name('search')->withoutMiddleware([EnsurePermission::class]);

            Route::prefix('students')->name('students.')->group(function () {
                Route::get('/', [StudentController::class, 'index'])->name('index');
                Route::get('/create', [StudentController::class, 'create'])->name('create')->middleware('dormitory.restrict:students');
                Route::post('/', [StudentController::class, 'store'])->name('store')->middleware('dormitory.restrict:students');
                Route::get('/find-student', [StudentController::class, 'findStudent'])->name('find-student');
                Route::get('/import', [StudentController::class, 'importForm'])->name('import-form')->middleware('dormitory.restrict:students');
                Route::post('/import', [StudentController::class, 'importProcess'])->name('import-process')->middleware('dormitory.restrict:students');
                Route::get('/template', [StudentController::class, 'downloadTemplate'])->name('template');

                // ── IMPORT NAMA ARAB SANTRI (menu terpisah; match NIS/NISN) ──
                Route::get('/import-nama-arab', [StudentArabicNameController::class, 'form'])->name('import-nama-arab-form')->middleware('dormitory.restrict:students');
                Route::post('/import-nama-arab', [StudentArabicNameController::class, 'import'])->name('import-nama-arab-process')->middleware('dormitory.restrict:students');
                Route::get('/import-nama-arab/template', [StudentArabicNameController::class, 'template'])->name('import-nama-arab-template');
                Route::post('/import-nama-arab/generate', [StudentArabicNameController::class, 'generate'])->name('import-nama-arab-generate')->middleware('dormitory.restrict:students');

                // ── REVIEW & APPROVAL NAMA ARAB SANTRI ──
                Route::get('/review-nama-arab', [StudentArabicNameController::class, 'review'])->name('review-nama-arab')->middleware('dormitory.restrict:students');
                Route::post('/review-nama-arab/approve-all', [StudentArabicNameController::class, 'approveAll'])->name('review-nama-arab-approve-all')->middleware('dormitory.restrict:students');
                Route::post('/review-nama-arab/{studentId}', [StudentArabicNameController::class, 'approve'])->name('review-nama-arab-approve')->middleware('dormitory.restrict:students');
                // Global mahrom listing MUST come before {santriUuid} so it isn't shadowed by the show route.
                Route::get('/mahroms', [StudentMahromController::class, 'globalIndex'])->name('mahroms.global');
                // Global CRUD for mahrom (cross-student): tambah/edit/hapus dari halaman Data Mahrom.
                Route::get('/mahroms/tambah', [StudentMahromController::class, 'globalCreate'])->name('mahroms.globalCreate');
                Route::post('/mahroms', [StudentMahromController::class, 'globalStore'])->name('mahroms.globalStore');
                Route::get('/mahroms/{mahromUuid}', [StudentMahromController::class, 'globalShow'])->name('mahroms.globalShow');
                Route::get('/mahroms/{mahromUuid}/edit', [StudentMahromController::class, 'globalEdit'])->name('mahroms.globalEdit');
                Route::put('/mahroms/{mahromUuid}', [StudentMahromController::class, 'globalUpdate'])->name('mahroms.globalUpdate');
                Route::delete('/mahroms/{mahromUuid}', [StudentMahromController::class, 'globalDestroy'])->name('mahroms.globalDestroy');
                // Foto santri (upload/hapus via AJAX)
                Route::post('/{santriUuid}/photo', [StudentPhotoController::class, 'upload'])->name('photo.upload');
                Route::delete('/{santriUuid}/photo', [StudentPhotoController::class, 'destroy'])->name('photo.destroy');
                // Data wali (ayah/ibu/wali) — sebelumnya view orphan tanpa route
                Route::get('/{santriUuid}/wali/edit', [WaliSantriController::class, 'edit'])->name('wali.edit');
                Route::put('/{santriUuid}/wali', [WaliSantriController::class, 'update'])->name('wali.update');

                Route::get('/{santriUuid}', [StudentController::class, 'show'])->name('show');
                Route::get('/{santriUuid}/edit', [StudentController::class, 'edit'])->name('edit')->middleware('dormitory.restrict:students');
                Route::put('/{santriUuid}', [StudentController::class, 'update'])->name('update')->middleware('dormitory.restrict:students');
                Route::delete('/{santriUuid}', [StudentController::class, 'destroy'])->name('destroy')->middleware('dormitory.restrict:students');

                // Student Mahroms (per-studenti)
                Route::get('/{santriUuid}/mahrom', [StudentMahromController::class, 'index'])->name('mahroms.index');
                Route::get('/{santriUuid}/mahrom/list', [StudentMahromController::class, 'listForStudent'])->name('mahroms.list');
                Route::get('/{santriUuid}/mahrom/tambah', [StudentMahromController::class, 'create'])->name('mahroms.create');
                Route::post('/{santriUuid}/mahrom', [StudentMahromController::class, 'store'])->name('mahroms.store');
                Route::get('/{santriUuid}/mahrom/{mahromUuid}', [StudentMahromController::class, 'show'])->name('mahroms.show');
                Route::get('/{santriUuid}/mahrom/{mahromUuid}/edit', [StudentMahromController::class, 'edit'])->name('mahroms.edit');
                Route::put('/{santriUuid}/mahrom/{mahromUuid}', [StudentMahromController::class, 'update'])->name('mahroms.update');
                Route::delete('/{santriUuid}/mahrom/{mahromUuid}', [StudentMahromController::class, 'destroy'])->name('mahroms.destroy');
            });

            // ── MUTASI KELUAR ──────────────────────────────────────
            Route::prefix('mutations-out')->name('mutations-out.')->group(function () {
                Route::get('/', [StudentMutationOutController::class, 'index'])->name('index');
                Route::get('/create', [StudentMutationOutController::class, 'create'])->name('create');
                Route::post('/', [StudentMutationOutController::class, 'store'])->name('store');
                Route::get('/find-student', [StudentMutationOutController::class, 'findStudent'])->name('find-student');
                Route::get('/hijri-convert', [StudentMutationOutController::class, 'hijriConvert'])->name('hijri-convert');
                Route::post('/bulk', [StudentMutationOutController::class, 'bulk'])->name('bulk');
                Route::get('/{mutationUuid}', [StudentMutationOutController::class, 'show'])->name('show');
                Route::post('/{mutationUuid}/submit', [StudentMutationOutController::class, 'submit'])->name('submit');
                Route::post('/{mutationUuid}/approve', [StudentMutationOutController::class, 'approve'])->name('approve');
                Route::post('/{mutationUuid}/reject', [StudentMutationOutController::class, 'reject'])->name('reject');
                Route::delete('/{mutationUuid}', [StudentMutationOutController::class, 'destroy'])->name('destroy');
                Route::get('/{mutationUuid}/print', [StudentMutationOutController::class, 'print'])->name('print');
            });

            // ── LULUS ──────────────────────────────────────────────
            Route::prefix('mutations-lulus')->name('mutations-lulus.')->group(function () {
                Route::get('/', [StudentMutationOutController::class, 'index'])->name('index');
                Route::get('/create', [StudentMutationOutController::class, 'create'])->name('create');
                Route::post('/', [StudentMutationOutController::class, 'store'])->name('store');
                Route::get('/find-student', [StudentMutationOutController::class, 'findStudent'])->name('find-student');
                Route::post('/bulk', [StudentMutationOutController::class, 'bulk'])->name('bulk');
                Route::get('/{mutationUuid}', [StudentMutationOutController::class, 'show'])->name('show');
                Route::post('/{mutationUuid}/submit', [StudentMutationOutController::class, 'submit'])->name('submit');
                Route::post('/{mutationUuid}/approve', [StudentMutationOutController::class, 'approve'])->name('approve');
                Route::post('/{mutationUuid}/reject', [StudentMutationOutController::class, 'reject'])->name('reject');
                Route::delete('/{mutationUuid}', [StudentMutationOutController::class, 'destroy'])->name('destroy');
                Route::get('/{mutationUuid}/print', [StudentMutationOutController::class, 'print'])->name('print');
            });

            // ── DROP OUT ───────────────────────────────────────────
            Route::prefix('mutations-do')->name('mutations-do.')->group(function () {
                Route::get('/', [StudentMutationOutController::class, 'index'])->name('index');
                Route::get('/create', [StudentMutationOutController::class, 'create'])->name('create');
                Route::post('/', [StudentMutationOutController::class, 'store'])->name('store');
                Route::get('/find-student', [StudentMutationOutController::class, 'findStudent'])->name('find-student');
                Route::post('/bulk', [StudentMutationOutController::class, 'bulk'])->name('bulk');
                Route::get('/{mutationUuid}', [StudentMutationOutController::class, 'show'])->name('show');
                Route::post('/{mutationUuid}/submit', [StudentMutationOutController::class, 'submit'])->name('submit');
                Route::post('/{mutationUuid}/approve', [StudentMutationOutController::class, 'approve'])->name('approve');
                Route::post('/{mutationUuid}/reject', [StudentMutationOutController::class, 'reject'])->name('reject');
                Route::delete('/{mutationUuid}', [StudentMutationOutController::class, 'destroy'])->name('destroy');
                Route::get('/{mutationUuid}/print', [StudentMutationOutController::class, 'print'])->name('print');
            });

            // ── MUTASI MASUK ───────────────────────────────────────
            Route::prefix('mutations-in')->name('mutations-in.')->group(function () {
                Route::get('/', [StudentMutationInController::class, 'index'])->name('index');
                Route::get('/create', [StudentMutationInController::class, 'create'])->name('create');
                Route::post('/', [StudentMutationInController::class, 'store'])->name('store');
                Route::get('/find-student', [StudentMutationInController::class, 'findStudent'])->name('find-student');
                Route::get('/hijri-convert', [StudentMutationInController::class, 'hijriConvert'])->name('hijri-convert');
                Route::post('/bulk', [StudentMutationInController::class, 'bulk'])->name('bulk');
                Route::get('/{mutationUuid}', [StudentMutationInController::class, 'show'])->name('show');
                Route::post('/{mutationUuid}/submit', [StudentMutationInController::class, 'submit'])->name('submit');
                Route::post('/{mutationUuid}/approve', [StudentMutationInController::class, 'approve'])->name('approve');
                Route::post('/{mutationUuid}/reject', [StudentMutationInController::class, 'reject'])->name('reject');
                Route::delete('/{mutationUuid}', [StudentMutationInController::class, 'destroy'])->name('destroy');
                Route::get('/{mutationUuid}/print', [StudentMutationInController::class, 'print'])->name('print');
            });

            // ── POIN PELANGGARAN ───────────────────────────────────
            Route::prefix('violation-points')->name('violation-points.')->group(function () {
                Route::get('/', [ViolationPointController::class, 'index'])->name('index');
                Route::get('/create', [ViolationPointController::class, 'create'])->name('create');
                Route::post('/', [ViolationPointController::class, 'store'])->name('store');
                Route::get('/find-student', [ViolationPointController::class, 'findStudent'])->name('find-student');
                Route::get('/dashboard', [ViolationPointController::class, 'dashboard'])->name('dashboard');
                Route::get('/recap', [ViolationPointController::class, 'recap'])->name('recap');
                Route::get('/recap/{studentUuid}', [ViolationPointController::class, 'recapDetail'])->name('recap.detail');
                Route::get('/export-pdf', [ViolationPointController::class, 'exportPdf'])->name('export-pdf');
                // Wildcard di bawah semua literal
                Route::get('/{violationUuid}', [ViolationPointController::class, 'show'])->name('show');
                Route::get('/{violationUuid}/edit', [ViolationPointController::class, 'edit'])->name('edit');
                Route::put('/{violationUuid}', [ViolationPointController::class, 'update'])->name('update');
                Route::delete('/{violationUuid}', [ViolationPointController::class, 'destroy'])->name('destroy');
            });

            // ── ABSENSI HARIAN PESERTA DIDIK ───────────────────────
            Route::prefix('absensi/harian')->name('absensi.harian.')->group(function () {
                Route::get('/', [AbsensiHarianController::class, 'index'])->name('index');
                Route::get('/create', [AbsensiHarianController::class, 'create'])->name('create');
                Route::post('/', [AbsensiHarianController::class, 'store'])->name('store');
                Route::get('/recap/detail', [AbsensiHarianController::class, 'recapDetail'])->name('recap.detail');
                Route::get('/recap/semester', [AbsensiHarianController::class, 'recapSemester'])->name('recap.semester');
                Route::get('/recap', [AbsensiHarianController::class, 'recap'])->name('recap');
                Route::get('/{studentUuid}', [AbsensiHarianController::class, 'show'])->name('show');
                Route::get('/{studentUuid}/export', [AbsensiHarianController::class, 'exportStudent'])->name('export.student');
                Route::post('/{recordId}/verifikasi', [AbsensiHarianController::class, 'verify'])->name('verifikasi');
            });

            // ═══════════════════════════════════════════════════════════════
            //  PENGELOLAAN ASRAMA (MASTER — CRUD Standalone)
            // ═══════════════════════════════════════════════════════════════
            Route::prefix('dormitory-master')->name('dormitory-master.')->middleware(EnsureBoardingSchool::class)->group(function () {
                Route::get('/', [DormitoryMasterController::class, 'index'])->name('index');
                Route::get('/create', [DormitoryMasterController::class, 'create'])->name('create');
                Route::post('/', [DormitoryMasterController::class, 'store'])->name('store');
                Route::get('/{asramaUuid}', [DormitoryMasterController::class, 'show'])->name('show');
                Route::get('/{asramaUuid}/edit', [DormitoryMasterController::class, 'edit'])->name('edit');
                Route::put('/{asramaUuid}', [DormitoryMasterController::class, 'update'])->name('update');
                Route::delete('/{asramaUuid}', [DormitoryMasterController::class, 'destroy'])->name('destroy');
            });

            // ═══════════════════════════════════════════════════════════════
            //  BOARDING POLICY MANAGEMENT
            // ═══════════════════════════════════════════════════════════════
            Route::prefix('boarding-policies')->name('boarding-policies.')->group(function () {
                Route::get('/', [BoardingPolicyController::class, 'index'])->name('index');
                Route::get('/create', [BoardingPolicyController::class, 'create'])->name('create');
                Route::post('/', [BoardingPolicyController::class, 'store'])->name('store');
                Route::get('/{id}', [BoardingPolicyController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [BoardingPolicyController::class, 'edit'])->name('edit');
                Route::put('/{id}', [BoardingPolicyController::class, 'update'])->name('update');
                Route::patch('/{id}/quota', [BoardingPolicyController::class, 'updateQuota'])->name('updateQuota');
                Route::delete('/{id}', [BoardingPolicyController::class, 'destroy'])->name('destroy');
            });

            // ═══════════════════════════════════════════════════════════════
            //  BOARDING REGULATIONS MANAGEMENT (Peraturan Asrama)
            // ═══════════════════════════════════════════════════════════════
            Route::prefix('boarding-regulations')->name('boarding-regulations.')->group(function () {
                Route::get('/', [BoardingRegulationController::class, 'index'])->name('index');
                Route::get('/create', [BoardingRegulationController::class, 'create'])->name('create');
                Route::post('/', [BoardingRegulationController::class, 'store'])->name('store');
                Route::get('/export', [BoardingRegulationController::class, 'export'])->name('export');
                Route::get('/{id}/print', [BoardingRegulationController::class, 'print'])->name('print');
                Route::get('/{id}', [BoardingRegulationController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [BoardingRegulationController::class, 'edit'])->name('edit');
                Route::put('/{id}', [BoardingRegulationController::class, 'update'])->name('update');
                Route::delete('/{id}', [BoardingRegulationController::class, 'destroy'])->name('destroy');
                Route::post('/{id}/publish', [BoardingRegulationController::class, 'publish'])->name('publish');
                Route::post('/{id}/archive', [BoardingRegulationController::class, 'archive'])->name('archive');
            });

            // Calendar: Return (Kalender Kepulangan)
            Route::prefix('calendar/return')->name('calendar.return.')->group(function () {
                Route::get('/', [DormitoryReturnCalendarController::class, 'index'])->name('index');
                Route::get('/{id}', [DormitoryReturnCalendarController::class, 'show'])->name('show');
                Route::patch('/{id}/returned', [DormitoryReturnCalendarController::class, 'markReturned'])->name('mark-returned');
            });

            // Calendar: Visit (Kalender Kunjungan)
            Route::prefix('calendar/visit')->name('calendar.visit.')->group(function () {
                Route::get('/', [DormitoryVisitCalendarController::class, 'index'])->name('index');
                Route::get('/{id}', [DormitoryVisitCalendarController::class, 'show'])->name('show');
                Route::patch('/{id}/check-in', [DormitoryVisitCalendarController::class, 'checkIn'])->name('check-in');
                Route::patch('/{id}/check-out', [DormitoryVisitCalendarController::class, 'checkOut'])->name('check-out');
            });

            // Student Timeline per Santri
            Route::get('/students/{studentId}/timeline', [StudentTimelineController::class, 'show'])->name('students.timeline');
            Route::get('/students/{studentId}/room-history', [StudentRoomHistoryController::class, 'show'])->name('students.room-history');
            Route::get('/students/{studentId}/violations', [StudentViolationController::class, 'index'])->name('students.violations');
            Route::get('/students/{studentId}/health', [App\Http\Controllers\StudentHealthController::class, 'show'])->name('students.health');

            // Pengasuh / Wali Asrama / Admin Asrama → dashboard role Asrama
            Route::redirect('/dashboard/pengasuh', '/{userId}/dashboard/asrama')->name('dashboard.pengasuh');

            // Boarding Role Dashboards
            Route::get('/dashboard/boarding-head', [BoardingHeadDashboardController::class, 'index'])->name('dashboard.boarding-head');
            Route::get('/dashboard/boarding-education', [BoardingEducationDashboardController::class, 'index'])->name('dashboard.boarding-education');
            Route::get('/dashboard/boarding-health', [BoardingHealthDashboardController::class, 'index'])->name('dashboard.boarding-health');

            // ── SUPER ADMIN → Konsol Sistem (/system) ─────────────────
            // Dashboard Super Admin adalah Konsol Sistem (system.dashboard).
            Route::redirect('/dashboard/super-admin', '/system')->name('dashboard.super-admin');

            // ── DASHBOARD UNIT RUMAH TANGGA (1 role = 1 dashboard) ───
            Route::get('/dashboard/unit-rumah-tangga', [UnitRumahTanggaDashboardController::class, 'index'])
                ->name('dashboard.unit-rumah-tangga');

            // ── DASHBOARD HUMAS PERSONALIA (1 role = 1 dashboard, global) ──
            Route::get('/dashboard/personalia', [RolePersonaliaDashboardController::class, 'index'])
                ->name('dashboard.personalia');

            // ── DASHBOARD DEPARTEMEN TAHFIDZ (1 role = 1 dashboard) ──
            Route::get('/dashboard/tahfidz', [TahfidzDashboardController::class, 'index'])
                ->name('dashboard.tahfidz');

            // ── DASHBOARD KEUANGAN (1 role = 1 dashboard) ────────────
            Route::get('/dashboard/keuangan', [KeuanganDashboardController::class, 'index'])
                ->name('dashboard.keuangan');

            // ── DASHBOARD PIMPINAN (1 role = 1 dashboard, global) ────
            Route::get('/dashboard/pimpinan', [PimpinanDashboardController::class, 'index'])
                ->name('dashboard.pimpinan');

            // ── DASHBOARD SATUAN PENDIDIKAN (1 role = 1 dashboard) ───
            // Widget = jabatan struktural + tugas tambahan (dijumlahkan & dedup).
            Route::get('/dashboard/satuan-pendidikan', [SatuanPendidikanDashboardController::class, 'index'])
                ->name('dashboard.satuan-pendidikan');

            // Redirect route lama per-jabatan → dashboard role di atas.
            // Nama route dipertahankan supaya sidebar, login redirect, dan link lama tetap jalan.
            foreach ([
                'kepala-satuan-pendidikan',
                'wakil-kepala',
                'guru',
                'ka-tata-usaha',
                'staf-tata-usaha',
                'bendahara',
                'wali-kelas',
                'koordinator-guru',
                'koordinator-kurikulum',
                'koordinator-kesiswaan',
                'koordinator-ekskul',
                'koordinator-lab',
                'koordinator-sarpras',
            ] as $legacyJabatanSlug) {
                Route::redirect(
                    "/dashboard/{$legacyJabatanSlug}",
                    '/{userId}/dashboard/satuan-pendidikan'
                )->name("dashboard.{$legacyJabatanSlug}");
            }

            // DEBUG TEST ROUTE - check if basic routing works
            Route::get('/route-test', function () {
                return '<pre>Routes are LOADED! Basic working test at /route-test</pre>';
            });

            // GTK Dashboard - simple working version with inline Blade (bypasses complex controller/view dependencies)
            Route::get('/dashboard/gtk', function () {
                $user = auth()->user();

                return view('gtk.simple_inline_dashboard', compact('user'));
            })->name('dashboard.gtk');

            // Boarding Role Dashboards (continued from earlier)
            Route::get('/dashboard/admin-tu', [AdminTUDashboardController::class, 'index'])->name('dashboard.admin-tu');
            Route::redirect('/dashboard/admin-asrama', '/{userId}/dashboard/asrama')->name('dashboard.admin-asrama');
            Route::redirect('/dashboard/wali-asrama', '/{userId}/dashboard/asrama')->name('dashboard.wali-asrama');
            // ── DASHBOARD ASRAMA (1 role = 1 dashboard, ber-scope) ───
            Route::get('/dashboard/asrama', [RoleAsramaDashboardController::class, 'index'])->name('dashboard.asrama');

            // Academic Integration
            Route::get('/academic', [AcademicIntegrationController::class, 'index'])->name('academic.index');
            Route::post('/academic/sync', [AcademicIntegrationController::class, 'sync'])->name('academic.sync');

            // Wali Santri Portal
            Route::get('/wali/dashboard', [WaliSantriPortalController::class, 'index'])->name('wali.dashboard');
            Route::post('/wali/request-permit', [WaliSantriPortalController::class, 'requestPermit'])->name('wali.request-permit');

            // ═══════════════════════════════════════════════════════════════
            //  ASRAMA / DORMITORY
            // ═══════════════════════════════════════════════════════════════
            Route::prefix('asrama')->name('asrama.')->middleware(EnsureBoardingSchool::class)->group(function () {
                Route::get('/operator-santri', [DormitoryStudentOperatorController::class, 'index'])->name('operators.index');
                Route::post('/operator-santri', [DormitoryStudentOperatorController::class, 'store'])->name('operators.store');
                Route::get('/operator-santri/students', [DormitoryStudentOperatorController::class, 'searchStudents'])->name('operators.students');
                Route::post('/operator-santri/{id}/toggle', [DormitoryStudentOperatorController::class, 'toggle'])->name('operators.toggle');
                Route::post('/operator-santri/{id}/reset-password', [DormitoryStudentOperatorController::class, 'resetPassword'])->name('operators.reset-password');

                // ── ASRAMA UTAMA ─────────────────────────────────────
                Route::get('/', [DormitoryMasterController::class, 'index'])->name('index');
                Route::get('/profil-saya', [DormitoryMasterController::class, 'myProfile'])->name('my-profile');
                Route::get('/create', [DormitoryMasterController::class, 'create'])->name('create');
                Route::post('/', [DormitoryMasterController::class, 'store'])->name('store');
                Route::get('/{asramaUuid}', [DormitoryMasterController::class, 'show'])->name('show');
                Route::get('/{asramaUuid}/edit', [DormitoryMasterController::class, 'edit'])->name('edit');
                Route::put('/{asramaUuid}', [DormitoryMasterController::class, 'update'])->name('update');
                Route::delete('/{asramaUuid}', [DormitoryMasterController::class, 'destroy'])->name('destroy');

                // ── STANDALONE WING CRUD ───────────────────────────────────
                Route::get('/{asramaUuid}/gedung', [DormitoryWingController::class, 'index'])->name('wings.index');
                Route::get('/{asramaUuid}/gedung/buat', [DormitoryWingController::class, 'create'])->name('wings.create');
                Route::post('/{asramaUuid}/gedung', [DormitoryWingController::class, 'store'])->name('wings.store');
                Route::get('/{asramaUuid}/gedung/{wingUuid}', [DormitoryWingController::class, 'show'])->name('wings.show');
                Route::get('/{asramaUuid}/gedung/{wingUuid}/edit', [DormitoryWingController::class, 'edit'])->name('wings.edit');
                Route::put('/{asramaUuid}/gedung/{wingUuid}', [DormitoryWingController::class, 'update'])->name('wings.update');
                Route::delete('/{asramaUuid}/gedung/{wingUuid}', [DormitoryWingController::class, 'destroy'])->name('wings.destroy');

                // ── STANDALONE ROOM CRUD ─────────────────────────────────
                Route::get('/{asramaUuid}/kamar', [DormitoryRoomController::class, 'index'])->name('rooms.index');
                Route::get('/{asramaUuid}/kamar/buat', [DormitoryRoomController::class, 'create'])->name('rooms.create');
                Route::post('/{asramaUuid}/kamar', [DormitoryRoomController::class, 'store'])->name('rooms.store');
                Route::get('/{asramaUuid}/kamar/{roomUuid}', [DormitoryRoomController::class, 'show'])->name('rooms.show');
                Route::get('/{asramaUuid}/penempatan', [DormitoryRoomController::class, 'placement'])->name('rooms.placement');

                // ── IMPORT DATA ASRAMA (kamar/gedung) ────────────────
                Route::get('/{asramaUuid}/import', [\App\Http\Controllers\DormitoryImportController::class, 'index'])->name('imports.index');
                Route::get('/{asramaUuid}/import/kamar/template', [\App\Http\Controllers\DormitoryImportController::class, 'roomsTemplate'])->name('imports.rooms.template');
                Route::post('/{asramaUuid}/import/kamar', [\App\Http\Controllers\DormitoryImportController::class, 'roomsStore'])->name('imports.rooms.store');
                Route::get('/{asramaUuid}/kamar/{roomUuid}/edit', [DormitoryRoomController::class, 'edit'])->name('rooms.edit');
                Route::put('/{asramaUuid}/kamar/{roomUuid}', [DormitoryRoomController::class, 'update'])->name('rooms.update');
                Route::delete('/{asramaUuid}/kamar/{roomUuid}', [DormitoryRoomController::class, 'destroy'])->name('rooms.destroy');

                // ── ROOM API ─────────────────────────────────────────────
                Route::get('/{asramaUuid}/kamar/{roomUuid}/penghuni-tersedia', [DormitoryRoomApiController::class, 'availableResidents'])->name('api.rooms.available-residents');
                Route::get('/{asramaUuid}/santri-tersedia', [DormitoryRoomApiController::class, 'pullableStudents'])->name('api.pullable-students');
                Route::post('/{asramaUuid}/kamar/{roomUuid}/tempatkan-santri', [DormitoryRoomApiController::class, 'assignStudents'])->name('api.rooms.assign-students');
                Route::get('/{asramaUuid}/santri/status-boarding', [DormitoryRoomApiController::class, 'studentsStatus'])->name('api.boarding-status');
                Route::post('/{asramaUuid}/santri/status-boarding', [DormitoryRoomApiController::class, 'updateBoardingStatus'])->name('api.boarding-status.update');
                Route::post('/{asramaUuid}/penghuni/{residentId}/berhenti-mondok', [DormitoryRoomApiController::class, 'checkoutResident'])->name('api.residents.checkout');
                Route::post('/{asramaUuid}/penghuni/{residentId}/pindah-kamar', [DormitoryRoomApiController::class, 'requestMove'])->name('api.residents.request-move');
                Route::get('/{asramaUuid}/mutasi-kamar-tertunda', [DormitoryRoomApiController::class, 'pendingMoves'])->name('api.room-moves.pending');
                Route::post('/{asramaUuid}/mutasi-kamar/{moveId}/setujui', [DormitoryRoomApiController::class, 'approveMove'])->name('api.room-moves.approve');
                Route::post('/{asramaUuid}/mutasi-kamar/{moveId}/tolak', [DormitoryRoomApiController::class, 'rejectMove'])->name('api.room-moves.reject');
                Route::post('/{asramaUuid}/kamar/{roomUuid}/penghuni-massal', [DormitoryRoomApiController::class, 'bulkAddResidents'])->name('api.rooms.bulk-add-residents');
                Route::post('/{asramaUuid}/kamar/{roomUuid}/penghuni-keluar', [DormitoryRoomApiController::class, 'removeResident'])->name('api.rooms.remove-resident');

                // API Helpers
                Route::get('/api/wings', [DormitoryMasterController::class, 'apiWingsByDormitory'])->name('api.wings');
                Route::get('/api/rooms', [DormitoryMasterController::class, 'apiRoomsByWing'])->name('api.rooms');

                // ── MUTASI KAMAR ───────────────────────────────────────
                Route::get('/{asramaUuid}/mutasi-kamar', [DormitoryRoomMoveController::class, 'index'])->name('room-moves.index');
                Route::get('/{asramaUuid}/mutasi-kamar/aju', [DormitoryRoomMoveController::class, 'create'])->name('room-moves.create');
                Route::post('/{asramaUuid}/mutasi-kamar', [DormitoryRoomMoveController::class, 'store'])->name('room-moves.store');
                Route::get('/{asramaUuid}/mutasi-kamar/{moveUuid}', [DormitoryRoomMoveController::class, 'show'])->name('room-moves.show');
                Route::post('/{asramaUuid}/mutasi-kamar/{moveUuid}/approve', [DormitoryRoomMoveController::class, 'approve'])->name('room-moves.approve');
                Route::post('/{asramaUuid}/mutasi-kamar/{moveUuid}/reject', [DormitoryRoomMoveController::class, 'reject'])->name('room-moves.reject');

                // ── WALI KAMAR (Room Supervisor) ────────────────────────
                Route::get('/{asramaUuid}/wali-kamar', [RoomSupervisorController::class, 'index'])->name('room-supervisors.index');
                Route::get('/{asramaUuid}/wali-kamar/tetapkan', [RoomSupervisorController::class, 'create'])->name('room-supervisors.create');
                Route::post('/{asramaUuid}/wali-kamar', [RoomSupervisorController::class, 'store'])->name('room-supervisors.store');
                Route::get('/{asramaUuid}/wali-kamar/{supervisorUuid}', [RoomSupervisorController::class, 'show'])->name('room-supervisors.show');
                Route::get('/{asramaUuid}/wali-kamar/{supervisorUuid}/edit', [RoomSupervisorController::class, 'edit'])->name('room-supervisors.edit');
                Route::put('/{asramaUuid}/wali-kamar/{supervisorUuid}', [RoomSupervisorController::class, 'update'])->name('room-supervisors.update');
                Route::delete('/{asramaUuid}/wali-kamar/{supervisorUuid}', [RoomSupervisorController::class, 'destroy'])->name('room-supervisors.destroy');
                Route::post('/{asramaUuid}/wali-kamar/{supervisorUuid}/akhiri', [RoomSupervisorController::class, 'endAssignmentAction'])->name('room-supervisors.end');

                // Profile Wali Kamar (pegawai)
                Route::get('/wali-kamar/profil/{supervisorUserUuid}', [RoomSupervisorController::class, 'supervisorProfile'])->name('room-supervisors.profile');

                // ── PENGHUNI ──────────────────────────────────────────
                Route::get('/{asramaUuid}/penghuni', [DormitoryResidentController::class, 'index'])->name('residents.index');
                Route::get('/{asramaUuid}/penghuni/tambah', [DormitoryResidentController::class, 'create'])->name('residents.create');
                Route::post('/{asramaUuid}/penghuni', [DormitoryResidentController::class, 'store'])->name('residents.store');
                Route::get('/{asramaUuid}/penghuni/find-student', [DormitoryResidentController::class, 'findStudent'])->name('residents.find-student');
                Route::get('/{asramaUuid}/penghuni/{residentUuid}', [DormitoryResidentController::class, 'show'])->name('residents.show');
                Route::post('/{asramaUuid}/penghuni/{residentUuid}/checkout', [DormitoryResidentController::class, 'checkout'])->name('residents.checkout');
                Route::post('/{asramaUuid}/penghuni/{residentUuid}/consent', [DormitoryResidentController::class, 'storeConsent'])->name('residents.consent.store');
                Route::post('/{asramaUuid}/penghuni/{residentUuid}/consent/{consentUuid}/revoke', [DormitoryResidentController::class, 'revokeConsent'])->name('residents.consent.revoke');

                // ── ABSENSI ───────────────────────────────────────────
                Route::get('/{asramaUuid}/absensi', [DormitoryAttendanceController::class, 'index'])->name('attendance.index');
                Route::get('/{asramaUuid}/absensi/catat', [DormitoryAttendanceController::class, 'create'])->name('attendance.create');
                Route::post('/{asramaUuid}/absensi', [DormitoryAttendanceController::class, 'store'])->name('attendance.store');
                Route::post('/{asramaUuid}/absensi/verify', [DormitoryAttendanceController::class, 'verify'])->name('attendance.verify');
                Route::get('/{asramaUuid}/absensi/rekap', [DormitoryAttendanceController::class, 'recap'])->name('attendance.recap');

                // ── PERIZINAN ────────────────────────────────────────
                Route::get('/{asramaUuid}/izin', [DormitoryPermitController::class, 'index'])->name('permits.index');
                Route::get('/{asramaUuid}/izin/aju', [DormitoryPermitController::class, 'create'])->name('permits.create');
                Route::post('/{asramaUuid}/izin', [DormitoryPermitController::class, 'store'])->name('permits.store');
                Route::get('/{asramaUuid}/izin/{permitUuid}', [DormitoryPermitController::class, 'show'])->name('permits.show');
                Route::get('/{asramaUuid}/izin/{permitUuid}/qr', [DormitoryPermitController::class, 'qrImage'])->name('permits.qr');
                Route::post('/{asramaUuid}/izin/{permitUuid}/approve', [DormitoryPermitController::class, 'approve'])->name('permits.approve');
                Route::post('/{asramaUuid}/izin/{permitUuid}/reject', [DormitoryPermitController::class, 'reject'])->name('permits.reject');
                Route::post('/{asramaUuid}/izin/{permitUuid}/return', [DormitoryPermitController::class, 'returnRecord'])->name('permits.return');
                Route::post('/{asramaUuid}/izin/{permitUuid}/pickup', [DormitoryPermitController::class, 'pickup'])->name('permits.pickup');
                Route::post('/{asramaUuid}/izin/{permitUuid}/pickup-authorization', [DormitoryPermitController::class, 'authorizePickup'])->name('permits.pickup-authorization');
                Route::post('/{asramaUuid}/izin/{permitUuid}/revoke-card', [DormitoryPermitController::class, 'revokeCard'])->name('permits.revoke-card');
                Route::post('/{asramaUuid}/izin/{permitUuid}/regenerate-card', [DormitoryPermitController::class, 'regenerateCard'])->name('permits.regenerate-card');
                Route::post('/{asramaUuid}/izin/{permitUuid}/status', [DormitoryPermitController::class, 'updateStatus'])->name('permits.update-status');
                Route::get('/{asramaUuid}/izin/{permitUuid}/card', [DormitoryPermitController::class, 'card'])->name('permits.card');

                // ── QUOTA CHECK FOR APPROVAL ─────────────────────
                Route::post('/{asramaUuid}/izin/quota-check', [DormitoryPermitController::class, 'inspectQuota'])->name('permits.quota.check');

                // ── SCAN & VERIFY (QR / manual) ───────────────────
                Route::get('/{asramaUuid}/izin/scan', [DormitoryPermitController::class, 'scan'])->name('permits.scan')->withoutMiddleware([EnsurePermission::class]);
                Route::post('/{asramaUuid}/izin/scan', [DormitoryPermitController::class, 'scanStore'])->name('permits.scan.store')->middleware('throttle:30,1');
                Route::get('/izin/verify', [DormitoryPermitController::class, 'verify'])->name('permits.verify')->withoutMiddleware([EnsurePermission::class]);
                // Public QR scan endpoint for mobile apps
                Route::get('/izin/public-scan/{token}', [DormitoryPermitController::class, 'publicVerify'])->name('permits.public-verify');

                // ── KONFIGURASI IZIN PER-ASRAMA ─────────────────────
                Route::get('/{asramaUuid}/izin/pengaturan', [DormitoryLeavePolicyController::class, 'index'])->name('leave-policies.index');
                Route::get('/{asramaUuid}/izin/pengaturan/{permitType}', [DormitoryLeavePolicyController::class, 'show'])->name('leave-policies.show');
                Route::post('/{asramaUuid}/izin/pengaturan', [DormitoryLeavePolicyController::class, 'storeOrUpdate'])->name('leave-policies.store');
                Route::post('/{asramaUuid}/izin/pengaturan/apply-defaults', [DormitoryLeavePolicyController::class, 'applyDefaults'])->name('leave-policies.apply-defaults');
                Route::post('/{asramaUuid}/izin/pengaturan/kunjungan', [DormitoryLeavePolicyController::class, 'updateVisitPolicy'])->name('leave-policies.visit');
                Route::get('/{asramaUuid}/poin/katalog', [\App\Http\Controllers\DormitoryPointCatalogController::class, 'index'])->name('point-catalog.index');
                Route::post('/{asramaUuid}/poin/katalog', [\App\Http\Controllers\DormitoryPointCatalogController::class, 'store'])->name('point-catalog.store');
                Route::put('/{asramaUuid}/poin/katalog/{catalogUuid}', [\App\Http\Controllers\DormitoryPointCatalogController::class, 'update'])->name('point-catalog.update');
                Route::delete('/{asramaUuid}/poin/katalog/{catalogUuid}', [\App\Http\Controllers\DormitoryPointCatalogController::class, 'destroy'])->name('point-catalog.destroy');
                Route::post('/{asramaUuid}/poin/katalog/{catalogUuid}/toggle', [\App\Http\Controllers\DormitoryPointCatalogController::class, 'toggle'])->name('point-catalog.toggle');

                // ── JENIS IZIN (PERMIT TYPES) CRUD ───────────────────────
                Route::get('/{asramaUuid}/izin/pengaturan/permit-types', [PermitTypeController::class, 'index'])->name('permit-types.index');
                Route::get('/{asramaUuid}/izin/pengaturan/permit-types/create', [PermitTypeController::class, 'create'])->name('permit-types.create');
                Route::post('/{asramaUuid}/izin/pengaturan/permit-types', [PermitTypeController::class, 'store'])->name('permit-types.store');
                Route::get('/{asramaUuid}/izin/pengaturan/permit-types/{permitType}', [PermitTypeController::class, 'show'])->name('permit-types.show');
                Route::get('/{asramaUuid}/izin/pengaturan/permit-types/{permitType}/edit', [PermitTypeController::class, 'edit'])->name('permit-types.edit');
                Route::put('/{asramaUuid}/izin/pengaturan/permit-types/{permitType}', [PermitTypeController::class, 'update'])->name('permit-types.update');
                Route::delete('/{asramaUuid}/izin/pengaturan/permit-types/{permitType}', [PermitTypeController::class, 'destroy'])->name('permit-types.destroy');
                Route::post('/{asramaUuid}/izin/pengaturan/permit-types/{permitType}/toggle-active', [PermitTypeController::class, 'toggleActive'])->name('permit-types.toggle-active');
                Route::get('/{asramaUuid}/izin/cetak-kartu', [DormitoryPermitController::class, 'bulkCard'])->name('permits.bulk-card');

                // ── WIZARD IZIN KEPULANGAN (4 steps) ─────────────
                // ── KEDATANGAN SANTRI (halaman rekap & modal catat masuk) ─────────
                Route::get('/{asramaUuid}/kepulangan', [DormitoryReturnController::class, 'index'])->name('dormitory-returns.index');
                Route::post('/{asramaUuid}/kepulangan/{permitUuid}/record', [DormitoryReturnController::class, 'record'])->name('dormitory-returns.record');

                // ── RIWAYAT KEPULANGAN PER SANTRI ────────────────
                Route::get('/{asramaUuid}/riwayat/{studentUuid}', [DormitoryReturnController::class, 'history'])->name('dormitory-returns.history');

                // ── STATISTIK KEPULANGAN (dashboard mini) ─────────
                Route::get('/{asramaUuid}/statistik-kepulangan', [DormitoryReturnController::class, 'statistics'])->name('dormitory-returns.statistics');

                // ── PELANGGARAN ─────────────────────────────────────
                Route::get('/{asramaUuid}/pelanggaran', [DormitoryViolationController::class, 'index'])->name('violations.index');
                Route::get('/{asramaUuid}/pelanggaran/tambah', [DormitoryViolationController::class, 'create'])->name('violations.create');
                Route::post('/{asramaUuid}/pelanggaran', [DormitoryViolationController::class, 'store'])->name('violations.store');
                Route::get('/{asramaUuid}/pelanggaran/{violationUuid}', [DormitoryViolationController::class, 'show'])->name('violations.show');
                Route::post('/{asramaUuid}/pelanggaran/{violationUuid}/notify', [DormitoryViolationController::class, 'notifyParent'])->name('violations.notify');

                // ── PENGHARGAAN ─────────────────────────────────────
                Route::get('/{asramaUuid}/penghargaan', [DormitoryRewardController::class, 'index'])->name('rewards.index');
                Route::get('/{asramaUuid}/penghargaan/tambah', [DormitoryRewardController::class, 'create'])->name('rewards.create');
                Route::post('/{asramaUuid}/penghargaan', [DormitoryRewardController::class, 'store'])->name('rewards.store');
                Route::get('/{asramaUuid}/penghargaan/{rewardUuid}', [DormitoryRewardController::class, 'show'])->name('rewards.show');
                Route::get('/{asramaUuid}/penghargaan/{rewardUuid}/edit', [DormitoryRewardController::class, 'edit'])->name('rewards.edit');
                Route::put('/{asramaUuid}/penghargaan/{rewardUuid}', [DormitoryRewardController::class, 'update'])->name('rewards.update');
                Route::delete('/{asramaUuid}/penghargaan/{rewardUuid}', [DormitoryRewardController::class, 'destroy'])->name('rewards.destroy');

                // ── INFORMASI & BROADCAST ─────────────────────────────
                Route::get('/{asramaUuid}/informasi', [DormitoryPostController::class, 'index'])->name('posts.index');
                Route::get('/{asramaUuid}/informasi/buat', [DormitoryPostController::class, 'create'])->name('posts.create');
                Route::post('/{asramaUuid}/informasi', [DormitoryPostController::class, 'store'])->name('posts.store');
                Route::get('/{asramaUuid}/informasi/{postUuid}', [DormitoryPostController::class, 'show'])->name('posts.show');
                Route::get('/{asramaUuid}/informasi/{postUuid}/edit', [DormitoryPostController::class, 'edit'])->name('posts.edit');
                Route::put('/{asramaUuid}/informasi/{postUuid}', [DormitoryPostController::class, 'update'])->name('posts.update');
                Route::delete('/{asramaUuid}/informasi/{postUuid}', [DormitoryPostController::class, 'destroy'])->name('posts.destroy');

                // Activity Templates
                Route::get('/{asramaUuid}/template-kegiatan', [DormitoryPostController::class, 'templateIndex'])->name('templates.index');
                Route::post('/{asramaUuid}/template-kegiatan', [DormitoryPostController::class, 'templateStore'])->name('templates.store');
                Route::post('/{asramaUuid}/template-kegiatan/{session}/toggle', [DormitoryPostController::class, 'templateToggle'])->name('templates.toggle');

                // Activity Logs
                // ── PRESENSI KEGIATAN ASRAMA ─────────────────────────
                Route::prefix('{asramaUuid}/kegiatan')->name('kegiatan.')->group(function () {
                    Route::get('/', [DormitoryActivityController::class, 'index'])->name('index');
                    Route::get('/create', [DormitoryActivityController::class, 'create'])->name('create');
                    Route::post('/', [DormitoryActivityController::class, 'store'])->name('store');
                    Route::get('/rutin', [DormitoryActivityController::class, 'schedules'])->name('schedules');
                    Route::post('/rutin', [DormitoryActivityController::class, 'scheduleStore'])->name('schedules.store');
                    Route::put('/rutin/{scheduleId}', [DormitoryActivityController::class, 'scheduleUpdate'])->name('schedules.update');
                    Route::delete('/rutin/{scheduleId}', [DormitoryActivityController::class, 'scheduleDestroy'])->name('schedules.destroy');
                    Route::post('/rutin/generate', [DormitoryActivityController::class, 'scheduleGenerate'])->name('schedules.generate');
                    Route::get('/{activityId}', [DormitoryActivityController::class, 'show'])->name('show');
                    Route::get('/{activityId}/presensi', [DormitoryActivityController::class, 'attendance'])->name('attendance');
                    Route::post('/{activityId}/presensi', [DormitoryActivityController::class, 'attendanceStore'])->name('attendance.store');
                    Route::post('/{activityId}/tutup', [DormitoryActivityController::class, 'close'])->name('close');
                    Route::post('/{activityId}/buka', [DormitoryActivityController::class, 'reopen'])->name('reopen');
                });

                Route::get('/{asramaUuid}/log-kegiatan', [DormitoryPostController::class, 'activityLogIndex'])->name('activities.index');

                // Emergency Broadcast
                Route::get('/{asramaUuid}/broadcast', [DormitoryPostController::class, 'broadcastIndex'])->name('broadcasts.index');
                Route::post('/{asramaUuid}/broadcast', [DormitoryPostController::class, 'broadcastStore'])->name('broadcasts.store');
                Route::get('/{asramaUuid}/broadcast/{broadcastUuid}', [DormitoryPostController::class, 'broadcastShow'])->name('broadcasts.show');
                Route::delete('/{asramaUuid}/broadcast/{broadcastUuid}', [DormitoryPostController::class, 'broadcastDestroy'])->name('broadcasts.destroy');

                // ── INVENTARIS KAMAR ───────────────────────────────────
                Route::get('/{asramaUuid}/inventaris', [DormitoryInventoryController::class, 'index'])->name('inventories.index');
                Route::get('/{asramaUuid}/inventaris/tambah', [DormitoryInventoryController::class, 'create'])->name('inventories.create');
                Route::post('/{asramaUuid}/inventaris', [DormitoryInventoryController::class, 'store'])->name('inventories.store');
                Route::get('/{asramaUuid}/inventaris/{itemUuid}/edit', [DormitoryInventoryController::class, 'edit'])->name('inventories.edit');
                Route::put('/{asramaUuid}/inventaris/{itemUuid}', [DormitoryInventoryController::class, 'update'])->name('inventories.update');
                Route::delete('/{asramaUuid}/inventaris/{itemUuid}', [DormitoryInventoryController::class, 'destroy'])->name('inventories.destroy');

                // ── KUNJUNGAN ────────────────────────────────────────
                Route::get('/{asramaUuid}/kunjungan', [DormitoryVisitLogController::class, 'index'])->name('visits.index');
                Route::get('/{asramaUuid}/kunjungan/aju', [DormitoryVisitLogController::class, 'create'])->name('visits.create');
                Route::post('/{asramaUuid}/kunjungan', [DormitoryVisitLogController::class, 'store'])->name('visits.store');
                Route::post('/{asramaUuid}/kunjungan/quota-check', [DormitoryVisitLogController::class, 'quotaCheck'])->name('visits.quota.check');
                Route::get('/{asramaUuid}/kunjungan/{visitUuid}', [DormitoryVisitLogController::class, 'show'])->name('visits.show');
                Route::get('/{asramaUuid}/kunjungan/{visitUuid}/card', [DormitoryVisitLogController::class, 'card'])->name('visits.card');
                Route::post('/{asramaUuid}/kunjungan/{visitUuid}/approve', [DormitoryVisitLogController::class, 'approve'])->name('visits.approve');
                Route::post('/{asramaUuid}/kunjungan/{visitUuid}/reject', [DormitoryVisitLogController::class, 'reject'])->name('visits.reject');
                Route::post('/{asramaUuid}/kunjungan/{visitUuid}/check-in', [DormitoryVisitLogController::class, 'checkIn'])->name('visits.check-in');
                Route::post('/{asramaUuid}/kunjungan/{visitUuid}/check-out', [DormitoryVisitLogController::class, 'checkOut'])->name('visits.check-out');
                // ── BLACKLIST TAMU ───────────────────────────────────
                Route::get('/{asramaUuid}/blacklist-tamu', [\App\Http\Controllers\DormitoryVisitorRestrictionController::class, 'index'])->name('visitor-restrictions.index');
                Route::post('/{asramaUuid}/blacklist-tamu', [\App\Http\Controllers\DormitoryVisitorRestrictionController::class, 'store'])->name('visitor-restrictions.store');
                Route::post('/{asramaUuid}/blacklist-tamu/{restrictionUuid}/toggle', [\App\Http\Controllers\DormitoryVisitorRestrictionController::class, 'toggle'])->name('visitor-restrictions.toggle');
                Route::delete('/{asramaUuid}/blacklist-tamu/{restrictionUuid}', [\App\Http\Controllers\DormitoryVisitorRestrictionController::class, 'destroy'])->name('visitor-restrictions.destroy');

                Route::get('/{asramaUuid}/kunjungan/scan', [DormitoryVisitLogController::class, 'scan'])->name('visits.scan')->withoutMiddleware([EnsurePermission::class]);
                Route::post('/{asramaUuid}/kunjungan/scan', [DormitoryVisitLogController::class, 'scanStore'])->name('visits.scan.store')->middleware('throttle:30,1');
                Route::get('/kunjungan/verify', [DormitoryVisitLogController::class, 'verify'])->name('visits.verify')->withoutMiddleware([EnsurePermission::class]);

                // ── APPROVAL CENTER (Inbox terpadu) ───────────────────────
                Route::get('/{asramaUuid}/approval-center', [BoardingApprovalCenterController::class, 'index'])->name('approval-center');
                Route::post('/{asramaUuid}/approval-center/approve', [BoardingApprovalCenterController::class, 'approve'])->name('approval-center.approve');
                Route::post('/{asramaUuid}/approval-center/reject', [BoardingApprovalCenterController::class, 'reject'])->name('approval-center.reject');

                // ── LAPORAN ASRAMA ──────────────────────────────────────
                Route::get('/{asramaUuid}/laporan', [DormitoryReportController::class, 'index'])->name('reports.index');
                Route::get('/{asramaUuid}/laporan/presensi', [DormitoryReportController::class, 'attendance'])->name('reports.attendance');
                Route::get('/{asramaUuid}/laporan/perizinan', [DormitoryReportController::class, 'permits'])->name('reports.permits');
                Route::get('/{asramaUuid}/laporan/perizinan/excel', [DormitoryReportController::class, 'permitsExcel'])->name('reports.permits.excel');
                Route::get('/{asramaUuid}/laporan/pelanggaran', [DormitoryReportController::class, 'violations'])->name('reports.violations');
                Route::get('/{asramaUuid}/laporan/penghargaan', [DormitoryReportController::class, 'rewards'])->name('reports.rewards');
                Route::get('/{asramaUuid}/laporan/inventaris', [DormitoryReportController::class, 'inventoriesHtml'])->name('reports.inventories');
                Route::get('/{asramaUuid}/laporan/kebersihan', [DormitoryReportController::class, 'sanitationHtml'])->name('reports.sanitation');
                Route::get('/{asramaUuid}/laporan/penghuni', [DormitoryReportController::class, 'occupancy'])->name('reports.occupancy');
                Route::get('/{asramaUuid}/laporan/santri/{studentId}', [DormitoryReportController::class, 'studentDetail'])->name('reports.student-detail');
                Route::get('/{asramaUuid}/laporan/kunjungan', [DormitoryReportController::class, 'visits'])->name('reports.visits');
                Route::get('/{asramaUuid}/laporan/mutasi-kamar', [DormitoryReportController::class, 'roomMoves'])->name('reports.room-moves');
                Route::get('/{asramaUuid}/laporan/keluar-asrama', [DormitoryReportController::class, 'checkouts'])->name('reports.checkouts');
                Route::get('/{asramaUuid}/laporan/kegiatan', [DormitoryReportController::class, 'activities'])->name('reports.activities');
                Route::get('/{asramaUuid}/laporan/audit-log', [DormitoryReportController::class, 'auditLog'])->name('reports.audit');

                // ── STAF PERIZINAN SCOPE MANAGEMENT ─────────────────────────────
                Route::get('/staf-assignments/{targetUserId}', [DormitoryStaffAssignmentController::class, 'index'])->name('staf-assignments.index');
                Route::put('/staf-assignments/{targetUserId}', [DormitoryStaffAssignmentController::class, 'update'])->name('staf-assignments.update');
                Route::delete('/staf-assignments/{targetUserId}/{dormitoryId}', [DormitoryStaffAssignmentController::class, 'destroy'])->name('staf-assignments.destroy');
            });

            // ═══════════════════════════════════════════════════════════════
            //  UKS — UKS MODULES
            // ═══════════════════════════════════════════════════════════════
            Route::prefix('uks')->name('uks.')->group(function () {

                // Imunisasi
                Route::get('/immunizations', [StudentImmunizationController::class, 'index'])->name('immunizations.index');
                Route::get('/immunizations/create', [StudentImmunizationController::class, 'create'])->name('immunizations.create');
                Route::post('/immunizations', [StudentImmunizationController::class, 'store'])->name('immunizations.store');
                Route::get('/immunizations/{uuid}', [StudentImmunizationController::class, 'show'])->name('immunizations.show');
                Route::get('/immunizations/{uuid}/edit', [StudentImmunizationController::class, 'edit'])->name('immunizations.edit');
                Route::put('/immunizations/{uuid}', [StudentImmunizationController::class, 'update'])->name('immunizations.update');
                Route::delete('/immunizations/{uuid}', [StudentImmunizationController::class, 'destroy'])->name('immunizations.destroy');
                Route::get('/immunizations/student/{studentUuid}', [StudentImmunizationController::class, 'byStudent'])->name('immunizations.by-student');

                // Medical Check-up
                Route::get('/health-checkups', [StudentHealthCheckupController::class, 'index'])->name('health-checkups.index');
                Route::get('/health-checkups/create', [StudentHealthCheckupController::class, 'create'])->name('health-checkups.create');
                Route::post('/health-checkups', [StudentHealthCheckupController::class, 'store'])->name('health-checkups.store');
                Route::get('/health-checkups/{uuid}', [StudentHealthCheckupController::class, 'show'])->name('health-checkups.show');
                Route::get('/health-checkups/{uuid}/edit', [StudentHealthCheckupController::class, 'edit'])->name('health-checkups.edit');
                Route::put('/health-checkups/{uuid}', [StudentHealthCheckupController::class, 'update'])->name('health-checkups.update');
                Route::delete('/health-checkups/{uuid}', [StudentHealthCheckupController::class, 'destroy'])->name('health-checkups.destroy');

                // Izin Sakit
                Route::get('/health-permits', [StudentHealthPermitController::class, 'index'])->name('health-permits.index');
                Route::get('/health-permits/create', [StudentHealthPermitController::class, 'create'])->name('health-permits.create');
                Route::post('/health-permits', [StudentHealthPermitController::class, 'store'])->name('health-permits.store');
                Route::get('/health-permits/{uuid}', [StudentHealthPermitController::class, 'show'])->name('health-permits.show');
                Route::get('/health-permits/{uuid}/edit', [StudentHealthPermitController::class, 'edit'])->name('health-permits.edit');
                Route::put('/health-permits/{uuid}', [StudentHealthPermitController::class, 'update'])->name('health-permits.update');
                Route::delete('/health-permits/{uuid}', [StudentHealthPermitController::class, 'destroy'])->name('health-permits.destroy');
                Route::post('/health-permits/{uuid}/approve', [StudentHealthPermitController::class, 'approve'])->name('health-permits.approve');
                Route::post('/health-permits/{uuid}/reject', [StudentHealthPermitController::class, 'reject'])->name('health-permits.reject');
                Route::post('/health-permits/{uuid}/notify-parent', [StudentHealthPermitController::class, 'notifyParent'])->name('health-permits.notify-parent');

                // Inventori Obat
                Route::get('/medicine-inventory', [StudentMedicineInventoryController::class, 'index'])->name('medicine-inventory.index');
                Route::get('/medicine-inventory/create', [StudentMedicineInventoryController::class, 'create'])->name('medicine-inventory.create');
                Route::post('/medicine-inventory', [StudentMedicineInventoryController::class, 'store'])->name('medicine-inventory.store');
                Route::get('/medicine-inventory/{uuid}', [StudentMedicineInventoryController::class, 'show'])->name('medicine-inventory.show');
                Route::get('/medicine-inventory/{uuid}/edit', [StudentMedicineInventoryController::class, 'edit'])->name('medicine-inventory.edit');
                Route::put('/medicine-inventory/{uuid}', [StudentMedicineInventoryController::class, 'update'])->name('medicine-inventory.update');
                Route::delete('/medicine-inventory/{uuid}', [StudentMedicineInventoryController::class, 'destroy'])->name('medicine-inventory.destroy');

                // Pemberian Obat
                Route::get('/medicine-logs', [StudentMedicineLogController::class, 'index'])->name('medicine-logs.index');
                Route::get('/medicine-logs/create', [StudentMedicineLogController::class, 'create'])->name('medicine-logs.create');
                Route::post('/medicine-logs', [StudentMedicineLogController::class, 'store'])->name('medicine-logs.store');
                Route::get('/medicine-logs/{uuid}', [StudentMedicineLogController::class, 'show'])->name('medicine-logs.show');
                Route::delete('/medicine-logs/{uuid}', [StudentMedicineLogController::class, 'destroy'])->name('medicine-logs.destroy');

                // Antropometri
                Route::get('/health-metrics', [StudentHealthMetricController::class, 'index'])->name('health-metrics.index');
                Route::get('/health-metrics/dashboard', [StudentHealthMetricController::class, 'dashboard'])->name('health-metrics.dashboard');
                Route::get('/health-metrics/student/{studentUuid}', [StudentHealthMetricController::class, 'studentChart'])->name('health-metrics.student');
                Route::get('/health-metrics/create', [StudentHealthMetricController::class, 'create'])->name('health-metrics.create');
                Route::post('/health-metrics', [StudentHealthMetricController::class, 'store'])->name('health-metrics.store');
                Route::get('/health-metrics/{uuid}', [StudentHealthMetricController::class, 'show'])->name('health-metrics.show');
                Route::get('/health-metrics/{uuid}/edit', [StudentHealthMetricController::class, 'edit'])->name('health-metrics.edit');
                Route::put('/health-metrics/{uuid}', [StudentHealthMetricController::class, 'update'])->name('health-metrics.update');
                Route::delete('/health-metrics/{uuid}', [StudentHealthMetricController::class, 'destroy'])->name('health-metrics.destroy');

                // Konseling
                Route::get('/counseling-records', [StudentCounselingRecordController::class, 'index'])->name('counseling-records.index');
                Route::get('/counseling-records/create', [StudentCounselingRecordController::class, 'create'])->name('counseling-records.create');
                Route::post('/counseling-records', [StudentCounselingRecordController::class, 'store'])->name('counseling-records.store');
                Route::get('/counseling-records/{uuid}', [StudentCounselingRecordController::class, 'show'])->name('counseling-records.show');
                Route::get('/counseling-records/{uuid}/edit', [StudentCounselingRecordController::class, 'edit'])->name('counseling-records.edit');
                Route::put('/counseling-records/{uuid}', [StudentCounselingRecordController::class, 'update'])->name('counseling-records.update');
                Route::delete('/counseling-records/{uuid}', [StudentCounselingRecordController::class, 'destroy'])->name('counseling-records.destroy');

                // Student Counseling (alias for unified GTK sidebar)
                Route::get('/student-counseling', [StudentCounselingRecordController::class, 'index'])->name('student-counseling.index');
                Route::get('/student-counseling/create', [StudentCounselingRecordController::class, 'create'])->name('student-counseling.create');
                Route::post('/student-counseling', [StudentCounselingRecordController::class, 'store'])->name('student-counseling.store');
                Route::get('/student-counseling/{uuid}', [StudentCounselingRecordController::class, 'show'])->name('student-counseling.show');

                // Faskes Rujukan
                Route::get('/facility-referrals', [FacilityReferralController::class, 'index'])->name('facility-referrals.index');
                Route::get('/facility-referrals/create', [FacilityReferralController::class, 'create'])->name('facility-referrals.create');
                Route::post('/facility-referrals', [FacilityReferralController::class, 'store'])->name('facility-referrals.store');
                Route::get('/facility-referrals/{uuid}', [FacilityReferralController::class, 'show'])->name('facility-referrals.show');
                Route::get('/facility-referrals/{uuid}/edit', [FacilityReferralController::class, 'edit'])->name('facility-referrals.edit');
                Route::put('/facility-referrals/{uuid}', [FacilityReferralController::class, 'update'])->name('facility-referrals.update');
                Route::delete('/facility-referrals/{uuid}', [FacilityReferralController::class, 'destroy'])->name('facility-referrals.destroy');

                // Sanitasi
                Route::get('/sanitation-inspections', [SanitationInspectionController::class, 'index'])->name('sanitation-inspections.index');
                Route::get('/sanitation-inspections/dashboard', [SanitationInspectionController::class, 'dashboard'])->name('sanitation-inspections.dashboard');
                Route::get('/sanitation-inspections/create', [SanitationInspectionController::class, 'create'])->name('sanitation-inspections.create');
                Route::post('/sanitation-inspections', [SanitationInspectionController::class, 'store'])->name('sanitation-inspections.store');
                Route::get('/sanitation-inspections/{uuid}', [SanitationInspectionController::class, 'show'])->name('sanitation-inspections.show');
                Route::get('/sanitation-inspections/{uuid}/edit', [SanitationInspectionController::class, 'edit'])->name('sanitation-inspections.edit');
                Route::put('/sanitation-inspections/{uuid}', [SanitationInspectionController::class, 'update'])->name('sanitation-inspections.update');
                Route::post('/sanitation-inspections/{uuid}/mark-complete', [SanitationInspectionController::class, 'markComplete'])->name('sanitation-inspections.mark-complete');
                Route::delete('/sanitation-inspections/{uuid}', [SanitationInspectionController::class, 'destroy'])->name('sanitation-inspections.destroy');

                // ── MAHROM AUTO-MATCH ────────────────────────────────────
                Route::get('/visits/find-mahrom/{studentId}', [DormitoryVisitLogController::class, 'findMahrom'])->name('visits.find-mahrom');

                // ── LAPORAN UKS ─────────────────────────────────────────
                Route::get('/laporan', [\App\Http\Controllers\Uks\ReportController::class, 'index'])->name('reports.index');
                Route::get('/laporan/presensi', [\App\Http\Controllers\Uks\ReportController::class, 'attendance'])->name('reports.attendance');
                Route::get('/laporan/perizinan', [\App\Http\Controllers\Uks\ReportController::class, 'permits'])->name('reports.permits');
                Route::get('/laporan/pelanggaran', [\App\Http\Controllers\Uks\ReportController::class, 'violations'])->name('reports.violations');
                Route::get('/laporan/penghargaan', [\App\Http\Controllers\Uks\ReportController::class, 'rewards'])->name('reports.rewards');
                Route::get('/laporan/inventaris', [\App\Http\Controllers\Uks\ReportController::class, 'inventories'])->name('reports.inventories');
                Route::get('/laporan/kebersihan', [\App\Http\Controllers\Uks\ReportController::class, 'sanitation'])->name('reports.sanitation');
                Route::get('/laporan/penghuni', [\App\Http\Controllers\Uks\ReportController::class, 'occupancy'])->name('reports.occupancy');
                Route::get('/laporan/santri/{studentId}', [\App\Http\Controllers\Uks\ReportController::class, 'studentDetail'])->name('reports.student-detail');
                Route::get('/laporan/export/{report}', [\App\Http\Controllers\Uks\ReportController::class, 'exportCsv'])->name('reports.export');
                Route::get('/laporan/pdf/{report}', [\App\Http\Controllers\Uks\ReportController::class, 'exportPdf'])->name('reports.pdf');

                // ── DASHBOARD UKS ─────────────────────────────────────────────
                Route::get('/', [RoleUksDashboardController::class, 'index'])->name('dashboard');

                // ── KLINIK UKS — ANTREAN, TRIASE, SOAP, RESEP, RUJUKAN ────────
                Route::prefix('clinic')->name('clinic.')->group(function () {
                    Route::get('/', [ClinicController::class, 'queue'])->name('queue');
                    Route::get('/{uuid}', [ClinicController::class, 'workspace'])->name('workspace');
                    Route::post('/{uuid}/call', [ClinicController::class, 'call'])->name('call');
                    Route::post('/{uuid}/start', [ClinicController::class, 'start'])->name('start');
                    Route::post('/{uuid}/finish', [ClinicController::class, 'finish'])->name('finish');
                    Route::post('/{uuid}/cancel', [ClinicController::class, 'cancel'])->name('cancel');
                    Route::post('/{uuid}/encounter', [ClinicController::class, 'storeEncounter'])->name('encounter.store');
                    Route::post('/{uuid}/prescriptions', [ClinicController::class, 'storePrescription'])->name('prescriptions.store');
                    Route::post('/{uuid}/referrals', [ClinicController::class, 'storeReferral'])->name('referrals.store');
                });

                // ── FARMASI UKS — ANTREAN RESEP & PENYERAHAN OBAT ─────────────
                Route::prefix('pharmacy')->name('pharmacy.')->group(function () {
                    Route::get('/', [PharmacyController::class, 'index'])->name('index');
                    Route::post('/{uuid}/dispense', [PharmacyController::class, 'dispense'])->name('dispense');
                    Route::post('/{uuid}/cancel', [PharmacyController::class, 'cancel'])->name('cancel');
                });

                // ── CETAK RESEP ───────────────────────────────────────────────
                Route::get('prescriptions/{uuid}/print', [ClinicController::class, 'printPrescription'])->name('prescriptions.print');

                // ── RUJUKAN PASIEN UKS ────────────────────────────────────────
                Route::prefix('referrals')->name('referrals.')->group(function () {
                    Route::get('/', [ClinicController::class, 'referralsIndex'])->name('index');
                    Route::get('/{uuid}/print', [ClinicController::class, 'printReferral'])->name('print');
                    Route::post('/{uuid}/return', [ClinicController::class, 'markReferralReturned'])->name('return');
                });

                // ── STUDENT HEALTH — DATA KRISTIAN ────────────────────────────────
                Route::prefix('student-health')->name('student-health.')->group(function () {
                    // Health records index
                    Route::get('/', [StudentHealthController::class, 'index'])->name('index');
                    // Individual health profile view
                    Route::get('/{studentUuid}', [StudentHealthController::class, 'show'])->name('profile');
                });

                // ── PENJADWALAN UKS — SISTEM SHIFT 24 JAM ───────────────────────────
                Route::prefix('scheduling')->name('scheduling.')->group(function () {
                    // Dashboard of scheduling system
                    Route::get('/', [SchedulingController::class, 'index'])->name('index');
                    // View detailed weekly schedule
                    Route::get('/view/{uuid}', [SchedulingController::class, 'show'])->name('view');
                    // Store new shift assignment
                    Route::post('/', [SchedulingController::class, 'store'])->name('store');
                    // Update shift assignment
                    Route::put('{uuid}', [SchedulingController::class, 'update'])->name('update');
                    // Delete shift assignment
                    Route::delete('{uuid}', [SchedulingController::class, 'destroy'])->name('destroy');
                    // Export schedule (CSV)
                    Route::get('/export', [SchedulingController::class, 'export'])->name('export');
                });

                // ── GTK & KESEHATAN ────────────────────────────────────────
                Route::prefix('gtk-health')->name('gtk-health.')->group(function () {
                    Route::get('/', [GtkHealthController::class, 'index'])->name('index');
                    Route::get('/{gtkUuid}', [GtkHealthController::class, 'show'])->name('show');
                    Route::put('/{gtkUuid}', [GtkHealthController::class, 'update'])->name('update');
                    Route::get('/{gtkUuid}/records', [GtkHealthController::class, 'showRecords'])->name('records.index');
                    Route::post('/{gtkUuid}/records', [GtkHealthController::class, 'storeRecord'])->name('records.store');
                    Route::post('/{gtkUuid}/medical-history', [GtkHealthController::class, 'storeMedicalHistory'])->name('medical-history.store');
                    Route::post('/{gtkUuid}/vaccinations', [GtkHealthController::class, 'storeVaccination'])->name('vaccinations.store');
                    Route::delete('/vaccinations/{uuid}', [GtkHealthController::class, 'destroyVaccination'])->name('vaccinations.destroy');
                });

                // ── PROFIL KESEHATAN SAYA ──────────────────────────────────────
                // Halaman profil kesehatan GTK milik sendiri (yang sedang login)
                Route::get('/profile', [GtkHealthController::class, 'selfProfile'])->name('profile');

                // ── PAPAN LOKASI PERAWATAN (ASRAMA / UKS / SEKOLAH / RUMAH) ──
                Route::prefix('care-locations')->name('care-locations.')->group(function () {
                    Route::get('/', [CareLocationController::class, 'index'])->name('index');
                    Route::post('/{uuid}', [CareLocationController::class, 'update'])->name('update');
                });

                // ── BED MANAGEMENT ─────────────────────────────────────────
                Route::prefix('beds')->name('beds.')->group(function () {
                    Route::get('/', [BedManagementController::class, 'index'])->name('index');
                    Route::get('/create', [BedManagementController::class, 'create'])->name('create');
                    Route::post('/', [BedManagementController::class, 'store'])->name('store');
                    Route::get('/{uuid}', [BedManagementController::class, 'show'])->name('show');
                    Route::get('/{uuid}/edit', [BedManagementController::class, 'edit'])->name('edit');
                    Route::put('/{uuid}', [BedManagementController::class, 'update'])->name('update');
                    Route::delete('/{uuid}', [BedManagementController::class, 'destroy'])->name('destroy');
                });

                // ── PATIENT ACTIONS (API-style POST endpoints) ─────────────
                Route::post('patients/{uuid}/medicate', [PatientController::class, 'administerMedication'])->name('patients.administer-medication');
                Route::post('patients/{uuid}/treatment', [PatientController::class, 'recordTreatment'])->name('patients.record-treatment');
                Route::post('patients/{uuid}/assign-bed', [PatientController::class, 'assignBed'])->name('patients.assign-bed');
                Route::post('patients/{uuid}/release-bed', [PatientController::class, 'releaseBed'])->name('patients.release-bed');

                // ── TRACKING PASIENT — RPM / RAWAT / PULANG / BALIK KEMBALI ──
                Route::prefix('patients')->name('patients.')->group(function () {
                    Route::get('/', [PatientController::class, 'index'])->name('index');
                    Route::get('/create', [PatientController::class, 'create'])->name('create');
                    Route::get('/{uuid}', [PatientController::class, 'show'])->name('show');
                    Route::post('/', [PatientController::class, 'store'])->name('store');
                    Route::put('/{uuid}', [PatientController::class, 'update'])->name('update');
                    Route::post('/{uuid}/status', [PatientController::class, 'changeStatus'])->name('change-status');
                    Route::post('/{uuid}/discharge', [PatientController::class, 'discharge'])->name('discharge');
                    Route::post('/{uuid}/return', [PatientController::class, 'markReturn'])->name('mark-return');
                    // Datatable for AJAX
                    Route::post('/datatable', [PatientController::class, 'datatable'])->name('datatable');
                });

                // ── STATUS PERAWATAN UKS (pelengkap) ─────────────────────
                Route::prefix('treatment-status')->name('treatment-status.')->group(function () {
                    Route::get('/{uuid}', [TreatmentStatusController::class, 'show'])->name('show');
                    Route::post('/{uuid}/status', [TreatmentStatusController::class, 'updateStatus'])->name('update-status');
                    Route::post('/{uuid}/notes', [TreatmentStatusController::class, 'storeNote'])->name('store-note');
                    Route::post('/{uuid}/assign-bed', [TreatmentStatusController::class, 'assignBed'])->name('assign-bed');
                    Route::post('/{uuid}/release-bed', [TreatmentStatusController::class, 'releaseBed'])->name('release-bed');
                });

                // ── PEMBERIAN OBAT (histori lengkap) ─────────────────────
                Route::prefix('medication-administrations')->name('medication-administrations.')->group(function () {
                    Route::post('/{uuid}', [MedicationAdministrationController::class, 'store'])->name('store');
                });
            });

            // ── DATA ALUMNI ────────────────────────────────────────
            Route::prefix('alumni')->name('alumni.')->group(function () {
                Route::get('/', [AlumniController::class, 'index'])->name('index');
                Route::get('/statistics', [AlumniController::class, 'statistics'])->name('statistics');
                Route::get('/export', [AlumniController::class, 'export'])->name('export');
                Route::post('/bulk-verify', [AlumniController::class, 'bulkVerify'])->name('bulk-verify');
                // Wildcard di bawah literal
                Route::get('/{alumniUuid}', [AlumniController::class, 'show'])->name('show');
                Route::get('/{alumniUuid}/edit', [AlumniController::class, 'edit'])->name('edit');
                Route::put('/{alumniUuid}', [AlumniController::class, 'update'])->name('update');
                Route::post('/{alumniUuid}/verify', [AlumniController::class, 'verify'])->name('verify');
            });

            // ── BULK GRADUATION ────────────────────────────────────
            Route::prefix('bulk-graduation')->name('bulk-graduation.')->group(function () {
                Route::get('/{studyGroupId?}', [BulkGraduationController::class, 'index'])->name('index');
                Route::post('/', [BulkGraduationController::class, 'store'])->name('store');
            });

            // ── BULK PROMOTION ─────────────────────────────────────
            Route::prefix('bulk-promotion')->name('bulk-promotion.')->group(function () {
                Route::get('/{studyGroupId?}', [BulkPromotionController::class, 'index'])->name('index');
                Route::post('/{studyGroupId}', [BulkPromotionController::class, 'store'])->name('store');
                Route::get('/api/study-groups', [BulkPromotionController::class, 'getStudyGroups'])->name('api.study-groups');
                Route::post('/promote', [BulkPromotionController::class, 'promote'])->name('promote');
            });

            // ── STUDENT MOVE (PINDAH ROMBEL, TINGKAT SAMA) ──────────
            Route::prefix('student-move')->name('student-move.')->group(function () {
                Route::get('/', [StudentMoveController::class, 'index'])->name('index');
                Route::post('/', [StudentMoveController::class, 'store'])->name('store');
            });

            // ── OPERATOR / ADMIN TU / WAKA DASHBOARD (shared dashboard) ──
            Route::prefix('operator')->name('operator.')->group(function () {
                Route::get('/dashboard', [WakaController::class, 'dashboard'])->name('dashboard');
            });

            // ── WORK UNITS ─────────────────────────────────────────
            Route::prefix('work-units')->name('work-units.')->group(function () {
                Route::get('/', [WorkUnitController::class, 'index'])->name('index');
                Route::post('/', [WorkUnitController::class, 'store'])->name('store');
                Route::post('/bulk-delete', [WorkUnitController::class, 'bulkDestroy'])->name('bulk-destroy');
                Route::post('/generate-code', [WorkUnitController::class, 'generateCode'])->name('generate-code');
                Route::get('/{id}', [WorkUnitController::class, 'show'])->name('show');
                Route::put('/{id}', [WorkUnitController::class, 'update'])->name('update');
                Route::delete('/{id}', [WorkUnitController::class, 'destroy'])->name('destroy');
                Route::patch('/{id}/toggle-status', [WorkUnitController::class, 'toggleStatus'])->name('toggle-status');
            });

            // ── PROFILE ────────────────────────────────────────────
            Route::prefix('profile')->name('profile.')->group(function () {
                Route::get('/my', [ProfileController::class, 'myProfile'])->name('my');
                Route::get('/my/edit', [ProfileController::class, 'editMyProfile'])->name('my.edit');
                Route::put('/my', [ProfileController::class, 'updateMyProfile'])->name('my.update');
                Route::post('/my/photo', [ProfileController::class, 'uploadPhoto'])->name('my.photo.upload');
                Route::delete('/my/photo', [ProfileController::class, 'deletePhoto'])->name('my.photo.delete');
                Route::get('/security', [ProfileSecurityController::class, 'index'])->name('security');
                Route::post('/security/totp/setup', [ProfileSecurityController::class, 'setupTotp'])->name('security.totp.setup');
                Route::post('/security/totp/confirm', [ProfileSecurityController::class, 'confirmTotp'])->name('security.totp.confirm');
                Route::post('/security/totp/disable', [ProfileSecurityController::class, 'disableTotp'])->name('security.totp.disable');
                Route::post('/security/recovery-codes', [ProfileSecurityController::class, 'generateRecoveryCodes'])->name('security.recovery-codes');
                Route::post('/security/sessions/revoke-others', [ProfileSecurityController::class, 'revokeOtherWebSessions'])->name('security.sessions.revoke-others');
                Route::delete('/security/sessions/{sessionId}', [ProfileSecurityController::class, 'revokeWebSession'])->name('security.sessions.revoke');
                Route::post('/security/mobile-sessions/revoke-all', [ProfileSecurityController::class, 'revokeOtherMobileSessions'])->name('security.mobile-sessions.revoke-all');
                Route::delete('/security/mobile-sessions/{tokenId}', [ProfileSecurityController::class, 'revokeMobileSession'])->name('security.mobile-sessions.revoke');
                Route::get('/cv/{uuid}', [ProfileController::class, 'downloadCv'])->name('cv');
            });

            // ── API INTERNAL ───────────────────────────────────────
            Route::prefix('api')->name('api.')->group(function () {
                Route::get('grade-levels/by-school/{schoolId}', [GradeLevelApiController::class, 'bySchool'])->name('grade-levels.by-school');
                Route::get('grade-levels/by-academic-year/{academicYearId}', [GradeLevelApiController::class, 'byAcademicYear'])->name('grade-levels.by-academic-year');
                Route::get('teachers/by-school/{schoolId}', [GradeLevelApiController::class, 'teachersBySchool'])->name('teachers.by-school');

                Route::get('study-groups/{studyGroupId}/students/unassigned', [StudyGroupApiController::class, 'unassignedStudents'])->name('study-groups.students.unassigned');
                Route::get('study-groups/{studyGroupId}/students/assigned', [StudyGroupApiController::class, 'getAssignedStudents'])->name('study-groups.students.assigned');
                Route::post('study-groups/{studyGroupId}/students/add', [StudyGroupApiController::class, 'addStudent'])->name('study-groups.students.add');
                Route::post('study-groups/{studyGroupId}/students/bulk-add', [StudyGroupApiController::class, 'bulkAddStudents'])->name('study-groups.students.bulk-add');
                Route::post('study-groups/{studyGroupId}/students/remove', [StudyGroupApiController::class, 'removeStudent'])->name('study-groups.students.remove');
            });

            /*
            |--------------------------------------------------------------------------
            | SUPER ADMIN: /{userId}/sa
            |--------------------------------------------------------------------------
            */
            Route::prefix('sa')->name('sa.')->group(function () {

                Route::prefix('users')->name('users.')->group(function () {
                    Route::get('/', [UserController::class, 'index'])->name('index');
                    Route::get('/create', [UserController::class, 'create'])->name('create');
                    Route::post('/', [UserController::class, 'store'])->name('store');
                    Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [UserController::class, 'update'])->name('update');
                    Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
                    Route::post('/{id}/assign-roles', [UserController::class, 'assignRoles'])->name('assign-roles');
                    Route::post('/{id}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password');
                });

                Route::prefix('roles')->name('roles.')->group(function () {
                    Route::get('/', [RoleController::class, 'index'])->name('index');
                    Route::get('/{id}', [RoleController::class, 'show'])->name('show');
                    Route::post('/', [RoleController::class, 'store'])->name('store');
                    Route::put('/{id}', [RoleController::class, 'update'])->name('update');
                    Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('permissions')->name('permissions.')->group(function () {
                    Route::get('/', [PermissionController::class, 'index'])->name('index');
                    Route::post('/', [PermissionController::class, 'store'])->name('store');
                    Route::put('/{id}', [PermissionController::class, 'update'])->name('update');
                    Route::delete('/{id}', [PermissionController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('divisi')->name('divisi.')->group(function () {
                    Route::get('/', [App\Http\Controllers\SuperAdmin\DivisiController::class, 'index'])->name('index');
                    Route::post('/', [App\Http\Controllers\SuperAdmin\DivisiController::class, 'store'])->name('store');
                    Route::put('/{id}', [App\Http\Controllers\SuperAdmin\DivisiController::class, 'update'])->name('update');
                    Route::delete('/{id}', [App\Http\Controllers\SuperAdmin\DivisiController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('sidebar-access')->name('sidebar-access.')->group(function () {
                    Route::get('/', [SidebarAccessController::class, 'index'])->name('index');
                    Route::put('/{menuKey}', [SidebarAccessController::class, 'update'])->name('update');
                    Route::post('/', [SidebarAccessController::class, 'store'])->name('store');
                    Route::delete('/{menuKey}', [SidebarAccessController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
                    Route::get('/', [AuditLogController::class, 'index'])->name('index');
                    Route::get('/export', [AuditLogController::class, 'export'])->name('export');
                    Route::get('/{id}', [AuditLogController::class, 'show'])->name('show');
                });

                Route::prefix('tokens')->name('tokens.')->group(function () {
                    Route::get('/', [TokenSesiController::class, 'index'])->name('index');
                    Route::post('/', [TokenSesiController::class, 'createToken'])->name('create');
                    Route::delete('/sessions/{id}', [TokenSesiController::class, 'revokeToken'])->name('sessions.revoke');
                    Route::delete('/secure/{id}', [TokenSesiController::class, 'revokeSecureToken'])->name('secure.revoke');
                });

                Route::prefix('failed-jobs')->name('failed-jobs.')->group(function () {
                    Route::get('/', [FailedJobController::class, 'index'])->name('index');
                    Route::post('/{id}/retry', [FailedJobController::class, 'retry'])->name('retry');
                    Route::post('/retry-all', [FailedJobController::class, 'retryAll'])->name('retry-all');
                    Route::delete('/{id}', [FailedJobController::class, 'destroy'])->name('destroy');
                    Route::post('/flush', [FailedJobController::class, 'flush'])->name('flush');
                });

                Route::prefix('notifications')->name('notifications.')->group(function () {
                    Route::get('/', [NotificationUniversalController::class, 'index'])->name('index');
                    Route::get('/create', [NotificationUniversalController::class, 'create'])->name('create');
                    Route::post('/', [NotificationUniversalController::class, 'store'])->name('store');
                    Route::post('/{id}/mark-read', [NotificationUniversalController::class, 'markAsRead'])->name('mark-read');
                    Route::post('/mark-all-read', [NotificationUniversalController::class, 'markAllRead'])->name('mark-all-read');
                    Route::delete('/{id}', [NotificationUniversalController::class, 'destroy'])->name('destroy');
                });

                Route::get('/password-reset-logs', [PasswordResetLogController::class, 'index'])->name('password-reset-logs.index');

                Route::prefix('struktur-kurikulum')->name('curriculum-structure.')->group(function () {
                    // Alamat lama (hub) dialihkan ke halaman matriks — satu halaman, tanpa duplikasi.
                    Route::get('/', fn () => redirect()->route(
                        'user.sa.curriculum-structure.matrix',
                        ['userId' => request()->route('userId')]
                    ))->name('index');
                    Route::get('/per-jenjang', [CurriculumStructureController::class, 'matrix'])->name('matrix');
                    Route::post('/terapkan', [CurriculumStructureController::class, 'apply'])->name('apply');
                    Route::post('/mapel', [CurriculumStructureController::class, 'storeSubject'])->name('subject.store');
                    Route::put('/mapel/{code}', [CurriculumStructureController::class, 'updateSubject'])->name('subject.update');
                    Route::delete('/mapel/{code}', [CurriculumStructureController::class, 'destroySubject'])->name('subject.destroy');
                });

                Route::prefix('system-settings')->name('system-settings.')->group(function () {
                    Route::get('/', [SystemSettingController::class, 'index'])->name('index');
                    Route::post('/', [SystemSettingController::class, 'update'])->name('update');
                    Route::post('/test-email', [SystemSettingController::class, 'testEmail'])->name('test-email');
                    Route::post('/test-google-drive', [SystemSettingController::class, 'testGoogleDrive'])->name('test-google-drive');
                    Route::get('/clear-cache', [SystemSettingController::class, 'clearCache'])->name('clear-cache');
                });

                // ── PENGATURAN PROVIDER AI (multi-platform) ─────────
                Route::prefix('ai-providers')->name('ai-providers.')->group(function () {
                    Route::get('/', [AiProviderController::class, 'index'])->name('index');
                    Route::post('/', [AiProviderController::class, 'store'])->name('store');
                    Route::put('/{id}', [AiProviderController::class, 'update'])->name('update');
                    Route::post('/{id}/default', [AiProviderController::class, 'setDefault'])->name('default');
                    Route::post('/{id}/toggle', [AiProviderController::class, 'toggleActive'])->name('toggle');
                    Route::post('/{id}/test', [AiProviderController::class, 'test'])->name('test');
                    Route::delete('/{id}', [AiProviderController::class, 'destroy'])->name('destroy');
                });

                Route::prefix('ai-chat')->name('ai-chat.')->group(function () {
                    Route::get('/', [AiChatMonitorController::class, 'index'])->name('index');
                    Route::get('/{conversation}', [AiChatMonitorController::class, 'show'])->name('show');
                    Route::post('/{conversation}/reply', [AiChatMonitorController::class, 'reply'])->name('reply');
                    Route::post('/{conversation}/close', [AiChatMonitorController::class, 'close'])->name('close');
                    Route::post('/{conversation}/reopen', [AiChatMonitorController::class, 'reopen'])->name('reopen');
                    Route::delete('/{conversation}', [AiChatMonitorController::class, 'destroy'])->name('destroy');
                });

                Route::post('/users/{user}/unlock', [UserSecurityController::class, 'unlock'])->name('users.unlock');
                Route::post('/ip-unblock', [UserSecurityController::class, 'unblockIp'])->name('ip.unblock');

                Route::prefix('schools')->name('schools.')->group(function () {
                    Route::get('/', [SchoolController::class, 'index'])->name('index');
                    Route::get('/create', [SchoolController::class, 'create'])->name('create');
                    Route::post('/', [SchoolController::class, 'store'])->name('store');
                    Route::get('/{id}/edit', [SchoolController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [SchoolController::class, 'update'])->name('update');
                    Route::delete('/{id}', [SchoolController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/toggle-status', [SchoolController::class, 'toggleStatus'])->name('toggle-status');
                });

                Route::prefix('dormitories')->name('dormitories.')->group(function () {
                    Route::get('/', [DormitoryMasterController::class, 'index'])->name('index');
                    Route::get('/create', [DormitoryMasterController::class, 'create'])->name('create');
                    Route::post('/', [DormitoryMasterController::class, 'store'])->name('store');
                    Route::get('/{id}/edit', [DormitoryMasterController::class, 'edit'])->name('edit');
                    Route::put('/{id}', [DormitoryMasterController::class, 'update'])->name('update');
                    Route::delete('/{id}', [DormitoryMasterController::class, 'destroy'])->name('destroy');
                    Route::post('/{id}/toggle-status', [DormitoryMasterController::class, 'toggleStatus'])->name('toggle-status');
                });
            });

            // ── SARANA PRASARANA (MANDIRI) ─────────────────────────
            // Dikelola oleh akun Sarpras / Unit Rumah Tangga (PAH-ADM-003)

            // ── KE HADIRAN (REKAP & LAPORAN) ──────────────────────────
            Route::prefix('kehadiran')->name('kehadiran.')->group(function () {
                Route::get('/pergantian-jam', [KehadiranController::class, 'pergantianJam'])->name('pergantian-jam');
                Route::get('/pergantian-jam/export', [KehadiranController::class, 'pergantianJamExport'])->name('pergantian-jam.export');
                Route::get('/rekap', [KehadiranController::class, 'rekap'])->name('rekap');
                Route::get('/cuti-izin', [KehadiranController::class, 'cutiIzin'])->name('cuti-izin');
            });

            // ── RAPOR GTK ───────────────────────────────────────────
            Route::prefix('rapor-gtk')->name('rapor-gtk.')->group(function () {
                Route::get('/akademik', [RaporGtkController::class, 'akademik'])->name('akademik');
                Route::get('/disiplin', [RaporGtkController::class, 'disiplin'])->name('disiplin');
                Route::get('/kepribadian', [RaporGtkController::class, 'kepribadian'])->name('kepribadian');
                Route::get('/administrasi', [RaporGtkController::class, 'administrasi'])->name('administrasi');
                Route::get('/tahunan', [RaporGtkController::class, 'tahunan'])->name('tahunan');
            });

            // ── KALENDER KEGIATAN ────────────────────────────────────
            Route::prefix('kalender-kegiatan')->name('kalender-kegiatan.')->group(function () {
                Route::get('/akademik', [KalenderKegiatanController::class, 'akademik'])->name('akademik');
                Route::get('/kontrak', [KalenderKegiatanController::class, 'kontrak'])->name('kontrak');
                Route::get('/evaluasi', [KalenderKegiatanController::class, 'evaluasi'])->name('evaluasi');
                Route::get('/training', [KalenderKegiatanController::class, 'training'])->name('training');
            });

            // ── ANALISIS GTK ──────────────────────────────────────────
            Route::prefix('analisis-gtk')->name('analisis-gtk.')->group(function () {
                Route::get('/beban-kerja', [AnalisisGtkController::class, 'bebanKerja'])->name('beban-kerja');
                Route::get('/rasio-ideal', [AnalisisGtkController::class, 'rasioIdeal'])->name('rasio-ideal');
                Route::get('/proyeksi', [AnalisisGtkController::class, 'proyeksi'])->name('proyeksi');
                Route::get('/gap', [AnalisisGtkController::class, 'gap'])->name('gap');
            });

            // ── TODO LIST ──────────────────────────────────────────
            Route::prefix('todos')->name('todos.')->group(function () {
                // Todo Lists (AJAX)
                Route::get('/lists', [TodoListController::class, 'index'])->name('lists.index');
                Route::post('/lists', [TodoListController::class, 'store'])->name('lists.store');
                Route::put('/lists/{id}', [TodoListController::class, 'update'])->name('lists.update');
                Route::delete('/lists/{id}', [TodoListController::class, 'destroy'])->name('lists.destroy');
                Route::post('/lists/set-default/{id}', [TodoListController::class, 'setDefault'])->name('lists.set-default');
                Route::post('/lists/reorder', [TodoListController::class, 'reorder'])->name('lists.reorder');

                // Main Todo CRUD
                Route::get('/', [TodoController::class, 'index'])->name('index');
                Route::get('/{id}', [TodoController::class, 'show'])->name('show');
                Route::post('/', [TodoController::class, 'store'])->name('store');
                Route::put('/{id}', [TodoController::class, 'update'])->name('update');
                Route::delete('/{id}', [TodoController::class, 'destroy'])->name('destroy');

                // Subtasks
                Route::post('/{id}/subtasks', [TodoController::class, 'subtaskStore'])->name('subtasks.store');
                Route::put('/{id}/subtasks/{subtaskId}/toggle', [TodoController::class, 'subtaskToggle'])->name('subtasks.toggle');
                Route::delete('/{id}/subtasks/{subtaskId}', [TodoController::class, 'subtaskDestroy'])->name('subtasks.destroy');

                // Comments
                Route::post('/{id}/comments', [TodoController::class, 'commentStore'])->name('comments.store');
                Route::delete('/{id}/comments/{commentId}', [TodoController::class, 'commentDestroy'])->name('comments.destroy');

                // Attachments
                Route::post('/{id}/attachments', [TodoController::class, 'attachmentStore'])->name('attachments.store');
                Route::delete('/{id}/attachments/{attachmentId}', [TodoController::class, 'attachmentDestroy'])->name('attachments.destroy');
            });

            // ── STUDENT ACHIEVEMENTS ────────────────────────────────────
            Route::prefix('student-achievements')->name('student-achievement.')->group(function () {
                Route::get('/', [StudentAchievementController::class, 'index'])->name('index');
                Route::get('/create', [StudentAchievementController::class, 'create'])->name('create');
                Route::post('/', [StudentAchievementController::class, 'store'])->name('store');
                Route::get('/{id}', [StudentAchievementController::class, 'show'])->name('show');
                Route::get('/{id}/edit', [StudentAchievementController::class, 'edit'])->name('edit');
                Route::put('/{id}', [StudentAchievementController::class, 'update'])->name('update');
                Route::delete('/{id}', [StudentAchievementController::class, 'destroy'])->name('destroy');
                Route::get('/find-student', [StudentAchievementController::class, 'findStudent'])->name('find-student');
                Route::get('/import', [StudentAchievementController::class, 'importForm'])->name('import-form');
                Route::post('/import', [StudentAchievementController::class, 'importProcess'])->name('import-process');
                Route::get('/template', [StudentAchievementController::class, 'downloadTemplate'])->name('template');
            });

            // ── LAPORAN PONDOK ───────────────────────────────────────
            Route::get('/laporan', [UserLaporanController::class, 'index'])->name('laporan.index');
            Route::get('/laporan/presensi', [UserLaporanController::class, 'presensi'])->name('laporan.presensi');
            Route::get('/laporan/santri', [UserLaporanController::class, 'santri'])->name('laporan.santri');
            Route::get('/laporan/gtk', [UserLaporanController::class, 'gtk'])->name('laporan.gtk');
            Route::get('/laporan/keuangan', [UserLaporanController::class, 'keuangan'])->name('laporan.keuangan');
            Route::get('/laporan/asrama', [UserLaporanController::class, 'asrama'])->name('laporan.asrama');
            Route::get('/laporan/pdf/{type}', [UserLaporanController::class, 'pdf'])->name('laporan.pdf');
            Route::get('/laporan/export/{type}', [UserLaporanController::class, 'export'])->name('laporan.export');
        });
});

// Scattered controller/model/middleware imports relocated to top of file (PHP requires all `use` at the top).
// import list: AbsensiHarianController, AcademicIntegrationController, AdminAsramaDashboardController, AdminTUDashboardController,
//   AsramaDashboardController, Boarding\BoardingEducationDashboardController, Boarding\BoardingHeadDashboardController,
//   Boarding\BoardingHealthDashboardController, DormitoryLeavePolicyController, DormitoryReturnCalendarController,
//   DormitoryReturnController, DormitoryVisitCalendarController, PengasuhDashboardController, PositionProposalController,
//   Sarpras\SarprasPurchaseOrderController, Sarpras\SarprasSparepartController, Sarpras\SarprasVendorController,
//   StudentRoomHistoryController, StudentTimelineController, StudentViolationController, SuperAdmin\SidebarAccessController,
//   System\SystemDashboardController, System\ViewAsController, TodoController, TodoListController, Uks\BedManagementController,
//   Uks\GtkHealthController, Uks\MedicationAdministrationController, Uks\PatientController, Uks\SchedulingController,
//   Uks\StudentHealthController, Uks\TreatmentStatusController, Uks\UksDashboardController, UserKalkulasiNilaiController,
//   UserLaporanController, Vendor\ProcurementController, Vendor\VendorPortalController, WaliAsramaDashboardController,
//   WaliSantriPortalController, EnsurePermission, EnsureSuperAdminOrSystemAdmin, User, WorkUnit

/*
|--------------------------------------------------------------------------
| SARANA PRASARANA (MANDIRI)
|--------------------------------------------------------------------------
| Dikelola oleh akun Sarpras / Unit Rumah Tangga (PAH-ADM-003)
| Akses langsung di /sarpras/* — TIDAK menggunakan prefix {userId}
*/
Route::middleware(['auth', 'sarpras.access'])->prefix('{userId}/sarpras')->name('sarpras.')->group(function () {

    // Dashboard
    Route::get('/', [SarprasDashboardController::class, 'index'])->name('dashboard');

    // Dashboard user satuan kerja (info aset saja)
    Route::get('/user/dashboard', [SarprasUserController::class, 'dashboard'])->name('user.dashboard');

    // ── ASET ───────────────────────────────────────────
    Route::get('/user/aset', [SarprasUserController::class, 'indexAsset'])->name('user.aset.index');
    Route::get('/user/aset/create', [SarprasUserController::class, 'createAsset'])->name('user.aset.create');
    Route::get('/user/aset/{id}', [SarprasUserController::class, 'showAsset'])->name('user.aset.show');
    Route::post('/user/aset', [SarprasUserController::class, 'storeAsset'])->name('user.aset.store');
    Route::get('/user/aset/{id}/edit', [SarprasUserController::class, 'editAsset'])->name('user.aset.edit');
    Route::put('/user/aset/{id}', [SarprasUserController::class, 'updateAsset'])->name('user.aset.update');
    Route::delete('/user/aset/{id}', [SarprasUserController::class, 'destroyAsset'])->name('user.aset.destroy');

    // ── IMPORT ASET ───────────────────────────────────
    Route::get('/user/aset/import', [SarprasUserController::class, 'importForm'])->name('user.aset.import');
    Route::post('/user/aset/import', [SarprasUserController::class, 'importProcess'])->name('user.aset.import.process');
    Route::get('/user/aset/import/template', [SarprasUserController::class, 'importTemplate'])->name('user.aset.import.template');
    Route::get('/user/aset/import/{roomId}', [SarprasUserController::class, 'importForm'])->name('user.aset.import.room');
    Route::post('/user/aset/import/{roomId}', [SarprasUserController::class, 'importProcess'])->name('user.aset.import.room.process');
    Route::get('/user/aset/import/{roomId}/template', [SarprasUserController::class, 'importTemplate'])->name('user.aset.import.room.template');

    // ── RUANG ─────────────────────────────────────────
    Route::get('/user/ruang', [SarprasUserController::class, 'indexRoom'])->name('user.ruang.index');
    Route::get('/user/ruang/create', [SarprasUserController::class, 'createRoom'])->name('user.ruang.create');
    Route::post('/user/ruang', [SarprasUserController::class, 'storeRoom'])->name('user.ruang.store');
    Route::get('/user/ruang/{id}', [SarprasUserController::class, 'showRoom'])->name('user.ruang.show');
    Route::put('/user/ruang/{id}', [SarprasUserController::class, 'updateRoom'])->name('user.ruang.update');
    Route::delete('/user/ruang/{id}', [SarprasUserController::class, 'destroyRoom'])->name('user.ruang.destroy');

    // ── KERUSAKAN ──────────────────────────────────────
    Route::get('/user/kerusakan', [SarprasUserController::class, 'indexKerusakan'])->name('user.kerusakan.index');
    Route::get('/user/kerusakan/create', [SarprasUserController::class, 'createKerusakan'])->name('user.kerusakan.create');
    Route::post('/user/kerusakan', [SarprasUserController::class, 'storeDamageReport'])->name('user.kerusakan.store');
    Route::get('/user/tindakan', [SarprasUserController::class, 'createActionRequest'])->name('user.action.create');
    Route::post('/user/tindakan', [SarprasUserController::class, 'storeActionRequest'])->name('user.action.store');

    // ── PENGADAAN ──────────────────────────────────────
    Route::get('/user/pengadaan', [SarprasUserController::class, 'indexPengadaan'])->name('user.pengadaan.index');
    Route::get('/user/pengadaan/create', [SarprasUserController::class, 'createPengadaan'])->name('user.pengadaan.create');
    Route::post('/user/pengadaan', [SarprasUserController::class, 'storeProcurementRequest'])->name('user.pengadaan.store');

    // Gedung
    Route::get('/gedung', [SarprasGedungController::class, 'index'])->name('gedung.index');
    Route::get('/gedung/create', [SarprasGedungController::class, 'create'])->name('gedung.create');
    Route::post('/gedung', [SarprasGedungController::class, 'store'])->name('gedung.store');
    Route::get('/gedung/{id}', [SarprasGedungController::class, 'show'])->name('gedung.show');
    Route::get('/gedung/{id}/edit', [SarprasGedungController::class, 'edit'])->name('gedung.edit');
    Route::put('/gedung/{id}', [SarprasGedungController::class, 'update'])->name('gedung.update');
    Route::delete('/gedung/{id}', [SarprasGedungController::class, 'destroy'])->name('gedung.destroy');

    // Ruang
    Route::get('/ruang', [SarprasRuangController::class, 'index'])->name('ruang.index');
    Route::post('/ruang/import', [SarprasRuangController::class, 'import'])->name('ruang.import');
    Route::get('/ruang/create', [SarprasRuangController::class, 'create'])->name('ruang.create');
    Route::post('/ruang', [SarprasRuangController::class, 'store'])->name('ruang.store');
    Route::get('/ruang/{id}', [SarprasRuangController::class, 'show'])->name('ruang.show');
    Route::get('/ruang/{id}/edit', [SarprasRuangController::class, 'edit'])->name('ruang.edit');
    Route::put('/ruang/{id}', [SarprasRuangController::class, 'update'])->name('ruang.update');
    Route::delete('/ruang/{id}', [SarprasRuangController::class, 'destroy'])->name('ruang.destroy');

    // Monitoring rombel & kamar untuk Sarpras/Kebersihan (TU/Kepala URT)
    Route::get('/monitoring/rombel-kamar', [SarprasOccupancyController::class, 'index'])->name('monitoring.rombel-kamar');

    // Petugas Sarpras Satuan Kerja
    Route::get('/petugas', [SarprasOfficerController::class, 'index'])->name('petugas.index');
    Route::post('/petugas', [SarprasOfficerController::class, 'store'])->name('petugas.store');
    Route::post('/petugas/{id}/toggle', [SarprasOfficerController::class, 'toggle'])->name('petugas.toggle');
    Route::post('/petugas/{id}/primary', [SarprasOfficerController::class, 'setPrimary'])->name('petugas.primary');

    // Aset / Inventaris
    Route::get('/aset', [SarprasAsetController::class, 'index'])->name('aset.index');
    Route::get('/aset/create', [SarprasAsetController::class, 'create'])->name('aset.create');
    Route::post('/aset', [SarprasAsetController::class, 'store'])->name('aset.store');
    Route::get('/aset/import', [SarprasAsetController::class, 'importForm'])->name('aset.import');
    Route::post('/aset/import', [SarprasAsetController::class, 'importProcess'])->name('aset.import.process');
    Route::get('/aset/template', [SarprasAsetController::class, 'template'])->name('aset.template');
    Route::post('/aset/{id}/verifikasi', [SarprasAsetController::class, 'verify'])->name('aset.verify');
    Route::get('/aset/{id}', [SarprasAsetController::class, 'show'])->name('aset.show');
    Route::get('/aset/{id}/edit', [SarprasAsetController::class, 'edit'])->name('aset.edit');
    Route::put('/aset/{id}', [SarprasAsetController::class, 'update'])->name('aset.update');
    Route::delete('/aset/{id}', [SarprasAsetController::class, 'destroy'])->name('aset.destroy');
    Route::post('/aset/{id}/photo', [SarprasAsetController::class, 'addPhoto'])->name('aset.photo.add');
    Route::delete('/aset/{id}/photo/{photoId}', [SarprasAsetController::class, 'deletePhoto'])->name('aset.photo.delete');

    // Import / Export data Sarpras
    Route::prefix('data-exchange')->name('data-exchange.')->group(function () {
        Route::get('/', [SarprasDataExchangeController::class, 'index'])->name('index');
        Route::get('/{dataset}/template', [SarprasDataExchangeController::class, 'template'])->name('template');
        Route::get('/{dataset}/export', [SarprasDataExchangeController::class, 'export'])->name('export');
        Route::post('/{dataset}/import', [SarprasDataExchangeController::class, 'import'])->name('import');
    });

    // Kategori (Ajax)
    Route::get('/kategori/list', [SarprasController::class, 'kategoriList'])->name('kategori.list');
    Route::post('/kategori', [SarprasController::class, 'kategoriStore'])->name('kategori.store');

    // Peminjaman Aset
    Route::get('/peminjaman', [SarprasLoanController::class, 'index'])->name('peminjaman.index');
    Route::get('/peminjaman/create', [SarprasLoanController::class, 'create'])->name('peminjaman.create');
    Route::post('/peminjaman', [SarprasLoanController::class, 'store'])->name('peminjaman.store');
    Route::get('/peminjaman/{id}', [SarprasLoanController::class, 'show'])->name('peminjaman.show');
    Route::post('/peminjaman/{id}/approve', [SarprasLoanController::class, 'approve'])->name('peminjaman.approve');
    Route::post('/peminjaman/{id}/reject', [SarprasLoanController::class, 'reject'])->name('peminjaman.reject');
    Route::post('/peminjaman/{id}/handover', [SarprasLoanController::class, 'handover'])->name('peminjaman.handover');
    Route::post('/peminjaman/{id}/return', [SarprasLoanController::class, 'return'])->name('peminjaman.return');
    Route::delete('/peminjaman/{id}', [SarprasLoanController::class, 'destroy'])->name('peminjaman.destroy');

    // Pemeliharaan - Jadwal
    Route::get('/pemeliharaan/jadwal', [SarprasMaintenanceController::class, 'scheduleIndex'])->name('pemeliharaan.schedule.index');
    Route::get('/pemeliharaan/timeline', [SarprasMaintenanceController::class, 'scheduleTimeline'])->name('pemeliharaan.schedule.timeline');
    Route::get('/pemeliharaan/jadwal/create', [SarprasMaintenanceController::class, 'scheduleCreate'])->name('pemeliharaan.schedule.create');
    Route::post('/pemeliharaan/jadwal', [SarprasMaintenanceController::class, 'scheduleStore'])->name('pemeliharaan.schedule.store');
    Route::get('/pemeliharaan/jadwal/{id}', [SarprasMaintenanceController::class, 'scheduleShow'])->name('pemeliharaan.schedule.show');
    Route::get('/pemeliharaan/jadwal/{id}/edit', [SarprasMaintenanceController::class, 'scheduleEdit'])->name('pemeliharaan.schedule.edit');
    Route::put('/pemeliharaan/jadwal/{id}', [SarprasMaintenanceController::class, 'scheduleUpdate'])->name('pemeliharaan.schedule.update');
    Route::delete('/pemeliharaan/jadwal/{id}', [SarprasMaintenanceController::class, 'scheduleDestroy'])->name('pemeliharaan.schedule.destroy');
    Route::post('/pemeliharaan/jadwal/{id}/work-order', [SarprasMaintenanceController::class, 'storeScheduleWorkOrder'])->name('pemeliharaan.schedule.work-order');
    Route::post('/pemeliharaan/jadwal/{id}/approve', [SarprasMaintenanceController::class, 'approveSchedule'])->name('pemeliharaan.schedule.approve');
    Route::post('/pemeliharaan/jadwal/{id}/reschedule', [SarprasMaintenanceController::class, 'requestReschedule'])->name('pemeliharaan.schedule.reschedule');
    Route::post('/pemeliharaan/jadwal/{id}/reject', [SarprasMaintenanceController::class, 'rejectSchedule'])->name('pemeliharaan.schedule.reject');

    // Listrik, token, dan penanganan padam
    Route::get('/listrik', [SarprasElectricityController::class, 'index'])->name('electricity.index');
    Route::get('/listrik/padam', [SarprasElectricityController::class, 'outages'])->name('electricity.outages.index');
    Route::post('/listrik/meter', [SarprasElectricityController::class, 'storeMeter'])->name('electricity.meter.store');
    Route::post('/listrik/meter/{meterId}/toggle', [SarprasElectricityController::class, 'toggleMeter'])->name('electricity.meter.toggle');
    Route::post('/listrik/meter/{meterId}/purchase', [SarprasElectricityController::class, 'storePurchase'])->name('electricity.purchase.store');
    Route::post('/listrik/padam', [SarprasElectricityController::class, 'storeOutage'])->name('electricity.outage.store');
    Route::get('/listrik/padam/{outageId}', [SarprasElectricityController::class, 'showOutage'])->name('electricity.outage.show');
    Route::post('/listrik/padam/{outageId}/action', [SarprasElectricityController::class, 'storeAction'])->name('electricity.outage.action');
    Route::post('/listrik/padam/{outageId}/resolve', [SarprasElectricityController::class, 'resolveOutage'])->name('electricity.outage.resolve');

    // Audit kualitas data Sarpras
    Route::get('/audit-data', [SarprasDataAuditController::class, 'index'])->name('audit.index');

    // Pengelolaan sampah DLH
    Route::get('/sampah', [SarprasWasteController::class, 'index'])->name('waste.index');
    Route::post('/sampah/sumber', [SarprasWasteController::class, 'storeSource'])->name('waste.source.store');
    Route::post('/sampah/catatan', [SarprasWasteController::class, 'storeLog'])->name('waste.log.store');
    Route::post('/sampah/catatan/{logId}/verify', [SarprasWasteController::class, 'verifyLog'])->name('waste.log.verify');
    Route::post('/sampah/penyerahan', [SarprasWasteController::class, 'storeHandover'])->name('waste.handover.store');
    Route::get('/sampah/fasilitas', [SarprasWasteController::class, 'facilities'])->name('waste.facilities');
    Route::post('/sampah/fasilitas', [SarprasWasteController::class, 'storeFacility'])->name('waste.facility.store');
    Route::put('/sampah/fasilitas/{facilityId}', [SarprasWasteController::class, 'updateFacility'])->name('waste.facility.update');
    Route::post('/sampah/fasilitas/{facilityId}/toggle', [SarprasWasteController::class, 'toggleFacility'])->name('waste.facility.toggle');
    Route::get('/sampah/laporan', [SarprasWasteController::class, 'report'])->name('waste.report');
    Route::get('/sampah/laporan/pdf', [SarprasWasteController::class, 'reportPdf'])->name('waste.report.pdf');

    // Kebersihan / CS
    Route::get('/kebersihan', [SarprasCleaningController::class, 'index'])->name('kebersihan.index');

    // Agenda harian pertamanan
    Route::prefix('pertamanan')->name('pertamanan.')->group(function () {
        Route::get('/agenda', [SarprasPertamananController::class, 'agenda'])->name('agenda.index');
        Route::post('/agenda', [SarprasPertamananController::class, 'saveAgenda'])->name('agenda.save');
        Route::post('/agenda/items', [SarprasPertamananController::class, 'storeItem'])->name('agenda.items.store');
        Route::post('/agenda/items/{itemId}/update', [SarprasPertamananController::class, 'updateItem'])->name('agenda.items.update');
        Route::delete('/agenda/items/{itemId}', [SarprasPertamananController::class, 'destroyItem'])->name('agenda.items.destroy');
    });
    Route::get('/kebersihan/area', [SarprasCleaningController::class, 'areas'])->name('kebersihan.areas');
    Route::get('/kebersihan/laporan', [SarprasCleaningController::class, 'reports'])->name('kebersihan.reports');
    Route::get('/kebersihan/program', [SarprasCleaningController::class, 'programs'])->name('kebersihan.programs');
    Route::post('/kebersihan/program/{programId}/check', [SarprasCleaningController::class, 'toggleProgramCheck'])->name('kebersihan.program.check');
    Route::put('/kebersihan/program/{programId}', [SarprasCleaningController::class, 'updateProgram'])->name('kebersihan.program.update');
    Route::delete('/kebersihan/program/{programId}', [SarprasCleaningController::class, 'destroyProgram'])->name('kebersihan.program.destroy');
    Route::post('/kebersihan/area', [SarprasCleaningController::class, 'storeArea'])->name('kebersihan.area.store');
    Route::get('/kebersihan/area/{areaId}', [SarprasCleaningController::class, 'showArea'])->name('kebersihan.area.show');
    Route::put('/kebersihan/area/{areaId}', [SarprasCleaningController::class, 'updateArea'])->name('kebersihan.area.update');
    Route::delete('/kebersihan/area/{areaId}', [SarprasCleaningController::class, 'destroyArea'])->name('kebersihan.area.destroy');
    Route::post('/kebersihan/area/{areaId}/toggle', [SarprasCleaningController::class, 'toggleArea'])->name('kebersihan.area.toggle');
    Route::post('/kebersihan/area/{areaId}/task', [SarprasCleaningController::class, 'storeTask'])->name('kebersihan.task.store');
    Route::put('/kebersihan/task/{taskId}', [SarprasCleaningController::class, 'updateTask'])->name('kebersihan.task.update');
    Route::delete('/kebersihan/task/{taskId}', [SarprasCleaningController::class, 'destroyTask'])->name('kebersihan.task.destroy');
    Route::post('/kebersihan/task/{taskId}/toggle', [SarprasCleaningController::class, 'toggleTask'])->name('kebersihan.task.toggle');
    Route::post('/kebersihan/area/{areaId}/assignment', [SarprasCleaningController::class, 'storeAssignment'])->name('kebersihan.assignment.store');
    Route::post('/kebersihan/assignment/{assignmentId}/toggle', [SarprasCleaningController::class, 'toggleAssignment'])->name('kebersihan.assignment.toggle');
    Route::post('/kebersihan/program', [SarprasCleaningController::class, 'storeProgram'])->name('kebersihan.program.store');
    Route::post('/kebersihan/program/{programId}/toggle', [SarprasCleaningController::class, 'toggleProgram'])->name('kebersihan.program.toggle');
    Route::get('/kebersihan/laporan/create', [SarprasCleaningController::class, 'createReport'])->name('kebersihan.report.create');
    Route::post('/kebersihan/laporan', [SarprasCleaningController::class, 'storeReport'])->name('kebersihan.report.store');
    Route::post('/kebersihan/laporan/autosave', [SarprasCleaningController::class, 'autosaveReport'])->name('kebersihan.report.autosave');
    Route::post('/kebersihan/laporan/finalize', [SarprasCleaningController::class, 'finalizeReport'])->name('kebersihan.report.finalize');
    Route::get('/kebersihan/laporan/{reportId}', [SarprasCleaningController::class, 'showReport'])->name('kebersihan.report.show');
    Route::post('/kebersihan/laporan/{reportId}/verify', [SarprasCleaningController::class, 'verifyReport'])->name('kebersihan.report.verify');

    // Kamera dokumentasi mandiri
    Route::get('/kamera', [SarprasCameraController::class, 'index'])->name('camera.index');
    Route::get('/kamera/geocode', [SarprasCameraController::class, 'reverseGeocode'])->name('camera.geocode');

    // Agenda kerja petugas Sarpras
    Route::get('/agenda', [SarprasAgendaController::class, 'index'])->name('agenda.index');

    // Checklist pemeliharaan
    Route::get('/checklist', [SarprasChecklistController::class, 'index'])->name('checklist.index');
    Route::get('/checklist/rekap', [SarprasChecklistController::class, 'recap'])->name('checklist.recap');
    Route::get('/checklist/template/{templateId}', [SarprasChecklistController::class, 'showTemplate'])->name('checklist.template');
    Route::get('/checklist/template/{templateId}/start', [SarprasChecklistController::class, 'startTemplateForm'])->name('checklist.template.start.form');
    Route::post('/checklist/template/{templateId}/start', [SarprasChecklistController::class, 'startTemplate'])->name('checklist.template.start');
    Route::get('/pemeliharaan/jadwal/{scheduleId}/checklist', [SarprasChecklistController::class, 'start'])->name('checklist.start');
    Route::post('/pemeliharaan/jadwal/{scheduleId}/checklist', [SarprasChecklistController::class, 'store'])->name('checklist.store');
    Route::get('/checklist/instances/{instanceId}', [SarprasChecklistController::class, 'showInstance'])->name('checklist.instance');
    Route::post('/checklist/instances/{instanceId}/save', [SarprasChecklistController::class, 'save'])->name('checklist.save');
    Route::post('/checklist/instances/{instanceId}/autosave', [SarprasChecklistController::class, 'autosave'])->name('checklist.autosave');
    Route::post('/checklist/instances/{instanceId}/complete', [SarprasChecklistController::class, 'complete'])->name('checklist.complete');

    // Antrean perbaikan (laporan kerusakan -> repair request -> work order)
    Route::get('/perbaikan', [SarprasRepairController::class, 'index'])->name('repair.index');
    Route::get('/perbaikan/{repairId}', [SarprasRepairController::class, 'show'])->name('repair.show');
    Route::post('/perbaikan/{repairId}/work-order', [SarprasRepairController::class, 'storeWorkOrder'])->name('repair.work-order');

    // Pemeliharaan - Riwayat Perawatan
    Route::get('/pemeliharaan/riwayat', [SarprasMaintenanceController::class, 'logIndex'])->name('pemeliharaan.log.index');
    Route::get('/pemeliharaan/riwayat/create', [SarprasMaintenanceController::class, 'logCreate'])->name('pemeliharaan.log.create');
    Route::post('/pemeliharaan/riwayat', [SarprasMaintenanceController::class, 'logStore'])->name('pemeliharaan.log.store');
    Route::get('/pemeliharaan/riwayat/{id}', [SarprasMaintenanceController::class, 'logShow'])->name('pemeliharaan.log.show');

    // Booking Ruangan
    Route::get('/booking', [SarprasBookingController::class, 'index'])->name('booking.index');
    Route::get('/booking/create', [SarprasBookingController::class, 'create'])->name('booking.create');
    Route::post('/booking', [SarprasBookingController::class, 'store'])->name('booking.store');
    Route::get('/booking/{id}', [SarprasBookingController::class, 'show'])->name('booking.show');
    Route::post('/booking/{id}/approve', [SarprasBookingController::class, 'approve'])->name('booking.approve');
    Route::post('/booking/{id}/reject', [SarprasBookingController::class, 'reject'])->name('booking.reject');
    Route::post('/booking/{id}/cancel', [SarprasBookingController::class, 'cancel'])->name('booking.cancel');
    Route::post('/booking/{id}/complete', [SarprasBookingController::class, 'complete'])->name('booking.complete');

    // Riwayat Perpindahan
    Route::get('/perpindahan', [SarprasMovementController::class, 'index'])->name('perpindahan.index');
    Route::get('/perpindahan/create', [SarprasMovementController::class, 'create'])->name('perpindahan.create');
    Route::post('/perpindahan', [SarprasMovementController::class, 'store'])->name('perpindahan.store');
    Route::get('/perpindahan/aset/{id}', [SarprasMovementController::class, 'byAsset'])->name('perpindahan.by-asset');
    Route::get('/perpindahan/{id}', [SarprasMovementController::class, 'show'])->name('perpindahan.show');

    // Pengadaan Barang
    Route::get('/pengadaan', [SarprasProcurementController::class, 'index'])->name('pengadaan.index');
    Route::get('/pengadaan/create', [SarprasProcurementController::class, 'create'])->name('pengadaan.create');
    Route::post('/pengadaan', [SarprasProcurementController::class, 'store'])->name('pengadaan.store');
    Route::get('/pengadaan/{id}', [SarprasProcurementController::class, 'show'])->name('pengadaan.show');
    Route::get('/pengadaan/{id}/edit', [SarprasProcurementController::class, 'edit'])->name('pengadaan.edit');
    Route::put('/pengadaan/{id}', [SarprasProcurementController::class, 'update'])->name('pengadaan.update');
    Route::get('/pengadaan/{id}/receive', [SarprasProcurementController::class, 'receiveForm'])->name('pengadaan.receive-form');
    Route::get('/pengadaan/{id}/convert', [SarprasProcurementController::class, 'convertForm'])->name('pengadaan.convert-form');
    Route::post('/pengadaan/{id}/approve', [SarprasProcurementController::class, 'approve'])->name('pengadaan.approve');
    Route::post('/pengadaan/{id}/reject', [SarprasProcurementController::class, 'reject'])->name('pengadaan.reject');
    Route::post('/pengadaan/{id}/receive', [SarprasProcurementController::class, 'receive'])->name('pengadaan.receive');
    Route::post('/pengadaan/{id}/convert', [SarprasProcurementController::class, 'convertToAsset'])->name('pengadaan.convert');
    Route::delete('/pengadaan/{id}', [SarprasProcurementController::class, 'destroy'])->name('pengadaan.destroy');

    // QR Code & Audit
    Route::get('/qr', [SarprasQRController::class, 'index'])->name('qr.index');
    Route::post('/qr/generate-all', [SarprasQRController::class, 'generateAll'])->name('qr.generate-all');
    Route::post('/qr/generate/{id}', [SarprasQRController::class, 'generateOne'])->name('qr.generate');
    Route::get('/qr/print', [SarprasQRController::class, 'printLabel'])->name('qr.print');
    Route::get('/qr/pdf', [SarprasQRController::class, 'downloadPdf'])->name('qr.pdf');
    Route::get('/qr/lookup-page', [SarprasQRController::class, 'lookupPage'])->name('qr.lookup-page');
    Route::get('/qr/lookup', [SarprasQRController::class, 'lookup'])->name('qr.lookup.get');
    Route::get('/qr/scanner', [SarprasQRController::class, 'scanner'])->name('qr.scanner');
    Route::post('/qr/lookup', [SarprasQRController::class, 'lookup'])->name('qr.lookup');
    Route::get('/qr/audit/{id}', [SarprasQRController::class, 'audit'])->name('qr.audit');
    Route::post('/qr/audit/{id}', [SarprasQRController::class, 'auditSubmit'])->name('qr.audit.submit');
    Route::get('/qr/bulk-audit', [SarprasQRController::class, 'bulkAudit'])->name('qr.bulk-audit');
    Route::post('/qr/bulk-audit', [SarprasQRController::class, 'bulkAuditSubmit'])->name('qr.bulk-audit.submit');

    // Stock Opname — URT + satuan kerja
    Route::get('/stock-opname', [SarprasStockOpnameController::class, 'index'])->name('stock-opname.index');
    Route::get('/stock-opname/create', [SarprasStockOpnameController::class, 'create'])->name('stock-opname.create');
    Route::post('/stock-opname', [SarprasStockOpnameController::class, 'store'])->name('stock-opname.store');
    Route::get('/stock-opname/{sessionId}', [SarprasStockOpnameController::class, 'show'])->name('stock-opname.show');
    Route::post('/stock-opname/{sessionId}/start', [SarprasStockOpnameController::class, 'start'])->name('stock-opname.start');
    Route::post('/stock-opname/{sessionId}/scan', [SarprasStockOpnameController::class, 'scan'])->name('stock-opname.scan');
    Route::post('/stock-opname/{sessionId}/items/{itemId}', [SarprasStockOpnameController::class, 'record'])->name('stock-opname.record');
    Route::post('/stock-opname/{sessionId}/close', [SarprasStockOpnameController::class, 'close'])->name('stock-opname.close');
    Route::post('/stock-opname/{sessionId}/cancel', [SarprasStockOpnameController::class, 'cancel'])->name('stock-opname.cancel');

    // Asset Passport — full lifecycle view
    Route::get('/assets/{uuid}/passport', [AssetPassportController::class, 'show'])->name('assets.passport');
    Route::get('/assets/{uuid}/passport.json', [AssetPassportController::class, 'json'])->name('assets.passport.json');
    Route::get('/assets/{uuid}/passport/v2', [AssetPassportController::class, 'showV2'])->name('assets.passport.v2');
    Route::get('/assets/{uuid}/passport/v2.json', [AssetPassportController::class, 'jsonV2'])->name('assets.passport.v2.json');
    Route::get('/scan/{code}', [AssetPassportController::class, 'scan'])->name('assets.scan');

    // Division portal
    Route::get('/division', [DivisionPortalController::class, 'index'])->name('division.index');
    Route::get('/division/assets', [DivisionPortalController::class, 'assets'])->name('division.assets');
    Route::get('/division/assets/{assetId}', [DivisionPortalController::class, 'showAsset'])->name('division.asset.show');

    // Laporan
    Route::get('/laporan', [SarprasReportController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/inventaris-per-ruang', [SarprasReportController::class, 'inventarisPerRuang'])->name('laporan.inventaris-per-ruang');
    Route::get('/laporan/inventaris-per-ruang/pdf', [SarprasReportController::class, 'inventarisPerRuangPdf'])->name('laporan.inventaris-per-ruang.pdf');
    Route::get('/laporan/buku-induk', [SarprasReportController::class, 'bukuInduk'])->name('laporan.buku-induk');
    Route::get('/laporan/buku-induk/pdf', [SarprasReportController::class, 'bukuIndukPdf'])->name('laporan.buku-induk.pdf');
    Route::get('/laporan/kartu-inventaris-ruangan', [SarprasReportController::class, 'kartuInventarisRuang'])->name('laporan.kir');
    Route::get('/laporan/kartu-inventaris-ruangan/pdf', [SarprasReportController::class, 'kartuInventarisRuangPdf'])->name('laporan.kir.pdf');
    Route::get('/laporan/kondisi-aset', [SarprasReportController::class, 'kondisiAset'])->name('laporan.kondisi-aset');
    Route::get('/laporan/kondisi-aset/pdf', [SarprasReportController::class, 'kondisiAsetPdf'])->name('laporan.kondisi-aset.pdf');
    Route::get('/laporan/peminjaman', [SarprasReportController::class, 'peminjaman'])->name('laporan.peminjaman');
    Route::get('/laporan/buku-peminjaman', [SarprasReportController::class, 'bukuPeminjaman'])->name('laporan.buku-peminjaman');
    Route::get('/laporan/buku-peminjaman/pdf', [SarprasReportController::class, 'bukuPeminjamanPdf'])->name('laporan.buku-peminjaman.pdf');
    Route::get('/laporan/pemeliharaan', [SarprasReportController::class, 'pemeliharaan'])->name('laporan.pemeliharaan');
    Route::get('/laporan/nilai-aset', [SarprasReportController::class, 'nilaiAset'])->name('laporan.nilai-aset');
    Route::get('/laporan/export', [SarprasReportController::class, 'exportExcel'])->name('laporan.export');

    // ── VENDOR MANAGEMENT ─────────────────────────────────────
    Route::get('/vendor', [SarprasVendorController::class, 'index'])->name('vendor.index');
    Route::get('/vendor/create', [SarprasVendorController::class, 'create'])->name('vendor.create');
    Route::post('/vendor', [SarprasVendorController::class, 'store'])->name('vendor.store');
    Route::get('/vendor/rank', [SarprasVendorController::class, 'rank'])->name('vendor.rank');
    Route::get('/vendor/{id}', [SarprasVendorController::class, 'show'])->name('vendor.show');
    Route::get('/vendor/{id}/edit', [SarprasVendorController::class, 'edit'])->name('vendor.edit');
    Route::put('/vendor/{id}', [SarprasVendorController::class, 'update'])->name('vendor.update');
    Route::delete('/vendor/{id}', [SarprasVendorController::class, 'destroy'])->name('vendor.destroy');

    // ── SPAREPART MANAGEMENT ──────────────────────────────────
    Route::get('/sparepart', [SarprasSparepartController::class, 'index'])->name('sparepart.index');
    Route::get('/sparepart/create', [SarprasSparepartController::class, 'create'])->name('sparepart.create');
    Route::post('/sparepart', [SarprasSparepartController::class, 'store'])->name('sparepart.store');
    Route::get('/sparepart/low-stock', [SarprasSparepartController::class, 'lowStock'])->name('sparepart.low-stock');
    Route::get('/sparepart/dead-stock', [SarprasSparepartController::class, 'deadStock'])->name('sparepart.dead-stock');
    Route::get('/sparepart/{id}', [SarprasSparepartController::class, 'show'])->name('sparepart.show');
    Route::get('/sparepart/{id}/edit', [SarprasSparepartController::class, 'edit'])->name('sparepart.edit');
    Route::put('/sparepart/{id}', [SarprasSparepartController::class, 'update'])->name('sparepart.update');
    Route::post('/sparepart/{id}/receive', [SarprasSparepartController::class, 'receive'])->name('sparepart.receive');
    Route::post('/sparepart/{id}/adjust', [SarprasSparepartController::class, 'adjust'])->name('sparepart.adjust');

    // ── PURCHASE ORDER MANAGEMENT ─────────────────────────────
    Route::get('/po', [SarprasPurchaseOrderController::class, 'index'])->name('po.index');
    Route::get('/po/create', [SarprasPurchaseOrderController::class, 'create'])->name('po.create');
    Route::post('/po', [SarprasPurchaseOrderController::class, 'store'])->name('po.store');
    Route::get('/po/{id}', [SarprasPurchaseOrderController::class, 'show'])->name('po.show');
    Route::post('/po/{id}/approve', [SarprasPurchaseOrderController::class, 'approve'])->name('po.approve');
    Route::post('/po/{id}/cancel', [SarprasPurchaseOrderController::class, 'cancel'])->name('po.cancel');
    Route::post('/po/{id}/receive', [SarprasPurchaseOrderController::class, 'receive'])->name('po.receive');

    // ── MULTI-STAGE ASSET MOVEMENTS ──────────────────────────
    Route::get('/movements', [SarprasAssetMovementController::class, 'index'])->name('movements.index');
    Route::post('/movements', [SarprasAssetMovementController::class, 'store'])->name('movements.store');
    Route::get('/movements/{id}', [SarprasAssetMovementController::class, 'show'])->name('movements.show');
    Route::post('/movements/{id}/approve', [SarprasAssetMovementController::class, 'approve'])->name('movements.approve');
    Route::post('/movements/{id}/reject', [SarprasAssetMovementController::class, 'reject'])->name('movements.reject');
    Route::post('/movements/{id}/start-transit', [SarprasAssetMovementController::class, 'startTransit'])->name('movements.start_transit');
    Route::post('/movements/{id}/received', [SarprasAssetMovementController::class, 'confirmReceived'])->name('movements.received');
    Route::post('/movements/{id}/verify', [SarprasAssetMovementController::class, 'verify'])->name('movements.verify');
    Route::post('/movements/{id}/complete', [SarprasAssetMovementController::class, 'complete'])->name('movements.complete');
    Route::get('/movements/{id}/snapshot', [SarprasAssetMovementController::class, 'snapshot'])->name('movements.snapshot');

    // ── TECHNICIAN WORKSPACE ──���──────────────────────────────
    Route::get('/teknisi', [SarprasTechnicianWorkspaceController::class, 'dashboard'])->name('teknisi.dashboard');
    Route::get('/teknisi/{orderId}', [SarprasTechnicianWorkspaceController::class, 'show'])->name('teknisi.show');
    Route::post('/teknisi/{orderId}/start', [SarprasTechnicianWorkspaceController::class, 'start'])->name('teknisi.start');
    Route::post('/teknisi/{orderId}/pause', [SarprasTechnicianWorkspaceController::class, 'pause'])->name('teknisi.pause');
    Route::post('/teknisi/{orderId}/resume', [SarprasTechnicianWorkspaceController::class, 'resume'])->name('teknisi.resume');
    Route::post('/teknisi/{orderId}/complete', [SarprasTechnicianWorkspaceController::class, 'complete'])->name('teknisi.complete');
    Route::post('/teknisi/{orderId}/note', [SarprasTechnicianWorkspaceController::class, 'logNote'])->name('teknisi.note');
    Route::get('/teknisi/{orderId}/snapshot', [SarprasTechnicianWorkspaceController::class, 'snapshot'])->name('teknisi.snapshot');
    Route::post('/teknisi/{orderId}/claim', [SarprasTechnicianWorkspaceController::class, 'claim'])->name('teknisi.claim');

    // ── AUDITOR WORKSPACE ────────────────────────────────────
    Route::get('/auditor', [SarprasAuditorWorkspaceController::class, 'dashboard'])->name('auditor.dashboard');
    Route::post('/auditor/session', [SarprasAuditorWorkspaceController::class, 'startSession'])->name('auditor.session');
    Route::post('/auditor/session/{sessionId}/asset/{assetId}/found', [SarprasAuditorWorkspaceController::class, 'markFound'])->name('auditor.found');
    Route::post('/auditor/session/{sessionId}/asset/{assetId}/missing', [SarprasAuditorWorkspaceController::class, 'markMissing'])->name('auditor.missing');
    Route::post('/auditor/session/{sessionId}/asset/{assetId}/condition', [SarprasAuditorWorkspaceController::class, 'updateCondition'])->name('auditor.condition');
    Route::post('/auditor/session/{sessionId}/close', [SarprasAuditorWorkspaceController::class, 'closeSession'])->name('auditor.close');
    Route::get('/auditor/session/{sessionId}/progress', [SarprasAuditorWorkspaceController::class, 'progress'])->name('auditor.progress');

    // ── DIVISION PORTAL ──────────────────────────────────────
    Route::get('/divisi', [SarprasDivisionPortalController::class, 'dashboard'])->name('divisi.dashboard');
    Route::get('/divisi/assets', [SarprasDivisionPortalController::class, 'assets'])->name('divisi.assets');
    Route::get('/divisi/asset/{assetId}', [SarprasDivisionPortalController::class, 'showAsset'])->name('divisi.asset_show');
    Route::get('/divisi/asset/{assetId}/lapor', [SarprasDivisionPortalController::class, 'showReportForm'])->name('divisi.report_form');
    Route::post('/divisi/report-issue', [SarprasDivisionPortalController::class, 'reportIssue'])->name('divisi.report_issue');
    Route::post('/divisi/request-maintenance', [SarprasDivisionPortalController::class, 'requestMaintenance'])->name('divisi.request_maintenance');
    Route::get('/divisi/history', [SarprasDivisionPortalController::class, 'history'])->name('divisi.history');

    // ── PIC APPROVAL ──────────────────────────────────────
    Route::get('/pic', [SarprasPicApprovalController::class, 'index'])->name('pic.index');
    Route::get('/pic/{id}', [SarprasPicApprovalController::class, 'show'])->name('pic.show');
    Route::post('/pic/{id}/verify', [SarprasPicApprovalController::class, 'verify'])->name('pic.verify');

    // ── KEPALA SARPRAS APPROVAL ─────────────────────────────
    Route::get('/kepala', [SarprasKepalaApprovalController::class, 'index'])->name('kepala.index');
    Route::get('/kepala/{id}', [SarprasKepalaApprovalController::class, 'show'])->name('kepala.show');
    Route::post('/kepala/{id}/approve', [SarprasKepalaApprovalController::class, 'approve'])->name('kepala.approve');

    // ── ASSET DISPOSAL ─────────────────────────────────────
    Route::get('/disposal/pending', [SarprasDisposalController::class, 'pending'])->name('disposal.pending');
    Route::post('/disposal/{asset}/approve', [SarprasDisposalController::class, 'approve'])->name('disposal.approve');
    Route::post('/disposal/{asset}/reject', [SarprasDisposalController::class, 'reject'])->name('disposal.reject');
    Route::post('/disposal/{asset}/sale', [SarprasDisposalController::class, 'recordSale'])->name('disposal.sale');

    // ── REPAIR VS REPLACE ENGINE ───────────────────────────
    Route::get('/repair-vs-replace', [SarprasRepairVsReplaceController::class, 'index'])->name('rvr.index');
    Route::get('/repair-vs-replace/{asset}', [SarprasRepairVsReplaceController::class, 'show'])->name('rvr.show');
    Route::post('/repair-vs-replace/{asset}/evaluate', [SarprasRepairVsReplaceController::class, 'evaluate'])->name('rvr.evaluate');
    Route::post('/repair-vs-replace/bulk', [SarprasRepairVsReplaceController::class, 'evaluateSchool'])->name('rvr.bulk');

    // ── PREDICTIVE MAINTENANCE ─────────────────────────────
    Route::get('/predictive-maintenance', [SarprasPredictiveMaintenanceController::class, 'index'])->name('predictive.index');
    Route::get('/predictive-maintenance/{asset}', [SarprasPredictiveMaintenanceController::class, 'show'])->name('predictive.show');
    Route::get('/api/predictive/high-risk', [SarprasPredictiveMaintenanceController::class, 'highRisk'])->name('predictive.high_risk');

    // ── PDF EXPORT ─────────────────────────────────────────
    Route::get('/assets/{uuid}/passport.pdf', [AssetPassportPdfController::class, 'download'])->name('assets.passport.pdf');
    Route::get('/assets/{uuid}/passport/v2.pdf', [AssetPassportPdfController::class, 'download'])->name('assets.passport.v2.pdf');
    Route::get('/assets/{uuid}/passport/stream', [AssetPassportPdfController::class, 'stream'])->name('assets.passport.stream');

    // ── INTELLIGENCE DASHBOARD ─────────────────────────────
    Route::get('/intelligence-dashboard', [SarprasIntelligenceDashboardController::class, 'index'])->name('dashboard.intelligence');
    Route::get('/api/intelligence-dashboard.json', [SarprasIntelligenceDashboardController::class, 'json'])->name('dashboard.intelligence.json');
});

/*
|--------------------------------------------------------------------------
| WADIR 1 SUBDOMAIN
|--------------------------------------------------------------------------
*/
Route::domain('wadir1.'.env('APP_DOMAIN', 'localhost'))
    ->middleware(['auth', 'role:Wadir 1'])
    ->name('wadir1.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('wadir1.gtk.index'));

        Route::prefix('gtk')->name('gtk.')->group(function () {
            Route::get('/', [GtkWizardController::class, 'index'])->name('index');
            Route::get('/filter', [GtkWizardController::class, 'filter'])->name('filter');
            Route::get('/{uuid}', [GtkWizardController::class, 'show'])->name('show');
            Route::get('/by-work-unit/{workUnitUuid}', [GtkWizardController::class, 'indexByWorkUnit'])->name('by-work-unit');
        });

        Route::prefix('work-units')->name('work-units.')->group(function () {
            Route::get('/', [WorkUnitController::class, 'index'])->name('index');
            Route::get('/{workUnitUuid}', [WorkUnitController::class, 'show'])->name('show');
        });

        Route::get('/reports', fn () => view('wadir1.reports.index'))->name('reports.index');

        Route::get('/statistics', function () {
            $gtkCount = User::whereHas('employment')->count();
            $workUnitCount = WorkUnit::count();

            return response()->json(['gtk_count' => $gtkCount, 'work_unit_count' => $workUnitCount]);
        })->name('statistics');
    });

/*
|--------------------------------------------------------------------------
| WAKA / ADMIN TU SUBDOMAIN
|--------------------------------------------------------------------------
*/
Route::prefix('waka')
    ->middleware(['auth', 'role:Satuan Pendidikan|Super Admin', 'school.context'])
    ->name('waka.')
    ->group(function () {
        Route::get('/', [WakaController::class, 'dashboard'])->name('dashboard');

        // ── GTK & PESERTA DIDIK ─────────────────────────────────
        Route::get('/gtk-guru', fn () => redirect()->route('user.gtk.indexguru', ['userId' => auth()->user()->id]))->name('gtk-guru');
        Route::get('/gtk-tendik', fn () => redirect()->route('user.gtk.indextendik', ['userId' => auth()->user()->id]))->name('gtk-tendik');
        Route::get('/peserta-didik/data-kelas', fn () => redirect()->route('user.study-groups.index', ['userId' => auth()->user()->id]))->name('peserta-didik.data-kelas');
        Route::get('/peserta-didik/rombel/{kelas}', function ($kelas) {
            return redirect()->route('user.study-groups.index', ['userId' => auth()->user()->id])->with('rombel-filter', $kelas);
        })->name('peserta-didik.rombel');
        Route::get('/peserta-didik/mutasi', fn () => redirect()->route('user.mutations-in.index', ['userId' => auth()->user()->id]))->name('peserta-didik.mutasi');
        Route::get('/peserta-didik/masuk', fn () => redirect()->route('user.mutations-in.index', ['userId' => auth()->user()->id]))->name('peserta-didik.masuk');
        Route::get('/peserta-didik/keluar', fn () => redirect()->route('user.mutations-out.index', ['userId' => auth()->user()->id]))->name('peserta-didik.keluar');
        Route::get('/poin-pelanggaran', fn () => redirect()->route('user.violation-points.dashboard', ['userId' => auth()->user()->id]))->name('poin-pelanggaran');
        // ── KISI-KISI, SOAL, NILAI (partial — P1: full route registration) ──
        Route::get('/kisi-kisi-soal', fn () => redirect()->route('user.kisi-kisi-soal.index', ['userId' => auth()->user()->id]))->name('kisi-kisi-soal');
        Route::get('/soal-sumatif', fn () => redirect()->route('user.paket-soal.index', ['userId' => auth()->user()->id]))->name('soal-sumatif');
        Route::get('/bank-soal', fn () => redirect()->route('user.bank-soal.index', ['userId' => auth()->user()->id]))->name('bank-soal');
        // Nilai STS/SAS require studyGroupId (UUID) — redirect to StudyGroups list
        Route::get('/nilai-sts/{kelas}', function ($kelas) {
            return redirect()->route('user.study-groups.index', ['userId' => auth()->user()->id])->with('rombel-filter', $kelas);
        })->name('nilai-sts');
        Route::get('/nilai-sas/{kelas}', function ($kelas) {
            return redirect()->route('user.study-groups.index', ['userId' => auth()->user()->id])->with('rombel-filter', $kelas);
        })->name('nilai-sas');

        // ── ABSENSI ──────────────────────────────────────────────
        Route::get('/absensi-gtk', fn () => redirect()->route('user.absensi-gtk.index', ['userId' => auth()->user()->id]))->name('absensi-gtk');
        Route::get('/absensi-pd/{kelas}', function ($kelas) {
            return redirect()->route('user.absensi.harian.recap', ['userId' => auth()->user()->id]);
        })->name('absensi-pd');

        // ── PRESTASI & HAFALAN ───────────────────────────────────
        Route::get('/prestasi-akademik', fn () => redirect()->route('user.student-achievement.index', ['userId' => auth()->user()->id]))->name('prestasi-akademik');
        Route::get('/hafalan-quran', fn () => redirect()->route('user.student-achievement.index', ['userId' => auth()->user()->id])->with('achievement_type', 'hafalan_quran'))->name('hafalan-quran');
        Route::get('/hafalan-hadits', fn () => redirect()->route('user.student-achievement.index', ['userId' => auth()->user()->id])->with('achievement_type', 'hafalan_hadits'))->name('hafalan-hadits');

        // ── EKSTRAKURIKULER ──────────────────────────────────────
        Route::middleware('satuan.feature:ekstrakurikuler')->group(function () {
            Route::resource('ekstrakurikuler', EkstrakurikulerController::class);
            Route::post('ekstrakurikuler/{ekstrakurikuler}/anggota', [EkstrakurikulerAnggotaController::class, 'store'])
                ->name('ekstrakurikuler.anggota.store');
            Route::put('ekstrakurikuler/{ekstrakurikuler}/anggota/{id}', [EkstrakurikulerAnggotaController::class, 'update'])
                ->name('ekstrakurikuler.anggota.update');
            Route::delete('ekstrakurikuler/{ekstrakurikuler}/anggota/{id}', [EkstrakurikulerAnggotaController::class, 'destroy'])
                ->name('ekstrakurikuler.anggota.destroy');
        });

        // ── JADWAL SUPERVISI ───────────────────────────────────
        Route::get('/supervisi/jadwal-guru', [SupervisiController::class, 'teacherSchedule'])
            ->middleware('satuan.feature:supervisi')
            ->name('supervisi.jadwal-guru');
        Route::get('/supervisi/jadwal/cetak', [SupervisiController::class, 'schedulePrint'])
            ->middleware('satuan.feature:supervisi')
            ->name('supervisi.jadwal.cetak');

        // ── SUPERVISI ───────────────────────────────────────────
        Route::middleware('satuan.feature:supervisi')->group(function () {
            Route::resource('supervisi', SupervisiController::class);

            Route::get('/{supervisi}/penilaian', [SupervisiController::class, 'penilaian'])->name('supervisi.penilaian');
            Route::post('/{supervisi}/penilaian', [SupervisiController::class, 'penilaianStore'])->name('supervisi.penilaian.store');
            Route::get('/{supervisi}/cetak', [SupervisiController::class, 'cetak'])->name('supervisi.cetak');
            Route::post('/{supervisi}/distribusi', [SupervisiController::class, 'markDistributed'])
                ->middleware('satuan.feature:supervisi-admin')->name('supervisi.distribute');
            Route::post('/{supervisi}/lapor-wadir', [SupervisiController::class, 'reportToWadir'])
                ->middleware('satuan.feature:supervisi-admin')->name('supervisi.report-wadir');
            Route::post('/{supervisi}/refleksi', [SupervisiController::class, 'saveReflection'])
                ->middleware('satuan.feature:supervisi-admin')->name('supervisi.reflection');
        });

        // ── TIM SUPERVISI & SK ─────────────────────────────────
        Route::prefix('supervisi-sk')->middleware('satuan.feature:supervisi-admin')->name('supervisi-sk.')->group(function () {
            Route::get('/', [SupervisiSkController::class, 'index'])->name('index');
            Route::post('/', [SupervisiSkController::class, 'store'])->name('store');
            Route::get('/{id}', [SupervisiSkController::class, 'edit'])->name('edit');
            Route::put('/{id}', [SupervisiSkController::class, 'update'])->name('update');
            Route::delete('/{id}', [SupervisiSkController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/anggota-bulk', [SupervisiSkController::class, 'storeMemberBulk'])->name('member.bulk');
            Route::put('/{id}/anggota', [SupervisiSkController::class, 'syncMembers'])->name('member.sync');
            Route::post('/{id}/anggota', [SupervisiSkController::class, 'storeMember'])->name('member.store');
            Route::delete('/{id}/anggota/{memberId}', [SupervisiSkController::class, 'destroyMember'])->name('member.destroy');
            Route::get('/{id}/cetak', [SupervisiSkController::class, 'cetak'])->name('cetak');
        });

        // ── PENGATURAN SUPERVISOR ───────────────────────────────
        Route::prefix('supervisi-pengaturan')->middleware('satuan.feature:supervisi-admin')->name('supervisi-pengaturan.')->group(function () {
            Route::get('/', fn () => redirect()->route('waka.supervisi.index'))->name('index');
            Route::post('/', [SupervisiPengaturanController::class, 'store'])->name('store');
            Route::post('/{id}/toggle', [SupervisiPengaturanController::class, 'toggle'])->name('toggle');
            Route::delete('/{id}', [SupervisiPengaturanController::class, 'destroy'])->name('destroy');
        });

        // ── PENGATURAN INSTRUMEN SUPERVISI (bagian → item + poin) ──
        Route::prefix('supervisi-instrumen')->name('supervisi-instrumen.')->group(function () {
            Route::get('/', [SupervisiInstrumenController::class, 'index'])->middleware('satuan.feature:supervisi')->name('index');
            Route::get('/{id}', [SupervisiInstrumenController::class, 'edit'])->middleware('satuan.feature:supervisi')->name('edit');

            Route::middleware('satuan.feature:supervisi-admin')->group(function () {
                Route::post('/', [SupervisiInstrumenController::class, 'store'])->name('store');
                Route::put('/{id}', [SupervisiInstrumenController::class, 'update'])->name('update');
                Route::post('/{id}/duplikat', [SupervisiInstrumenController::class, 'duplicate'])->name('duplikat');
                Route::post('/{id}/bagian', [SupervisiInstrumenController::class, 'storeBagian'])->name('bagian.store');
                Route::put('/bagian/{bagianId}', [SupervisiInstrumenController::class, 'updateBagian'])->name('bagian.update');
                Route::delete('/bagian/{bagianId}', [SupervisiInstrumenController::class, 'destroyBagian'])->name('bagian.destroy');
                Route::post('/bagian/{bagianId}/item', [SupervisiInstrumenController::class, 'storeItem'])->name('item.store');
                Route::put('/item/{itemId}', [SupervisiInstrumenController::class, 'updateItem'])->name('item.update');
                Route::delete('/item/{itemId}', [SupervisiInstrumenController::class, 'destroyItem'])->name('item.destroy');
                Route::post('/item/{itemId}/pindah', [SupervisiInstrumenController::class, 'moveItem'])->name('item.move');
            });
        });

        // ── SURAT MENYURAT ──────────────────────────────────────
        Route::middleware('satuan.feature:surat')->group(function () {
            Route::get('surat-template', [SuratTemplateController::class, 'index'])->name('surat-template.index');
            Route::post('surat-template', [SuratTemplateController::class, 'store'])->name('surat-template.store');
            Route::put('surat-template/{id}', [SuratTemplateController::class, 'update'])->name('surat-template.update');
            Route::delete('surat-template/{id}', [SuratTemplateController::class, 'destroy'])->name('surat-template.destroy');

            Route::resource('surat-masuk', SuratMasukController::class);

            Route::get('surat-keluar/{id}/cetak', [SuratKeluarController::class, 'cetak'])->name('surat-keluar.cetak');
            Route::resource('surat-keluar', SuratKeluarController::class);
        });

        // ── PEKAN EFEKTIF ───────────────────────────────────────
        Route::post('pekan-efektif/generate', [PekanEfektifController::class, 'generate'])
            ->name('pekan-efektif.generate');
        Route::resource('pekan-efektif', PekanEfektifController::class);

        // ── ADMINISTRASI GTK ─────────────────────────────────────
        Route::get('/sk-guru', fn () => redirect()->route('user.teaching-assignments.index', ['userId' => auth()->user()->id]))->name('sk-guru');
        Route::get('/jadwal-pelajaran', fn () => redirect()->route('user.jadwal-kbm.index', ['userId' => auth()->user()->id]))->name('jadwal-pelajaran');
        Route::get('/jam-mengajar', fn () => redirect()->route('user.teaching-assignments.index', ['userId' => auth()->user()->id]))->name('jam-mengajar');
        Route::get('/rekap-pergantian-jam', fn () => redirect()->route('user.kehadiran.pergantian-jam', ['userId' => auth()->user()->id]))->name('rekap-pergantian-jam');

        // ── DOKUMEN / AGENDA / SARANA ────────────────────────────
        Route::get('/dokumen-iso', fn () => redirect()->route('user.dokumen-iso.index', ['userId' => auth()->user()->id]))->name('dokumen-iso');
        Route::get('/kaldik', fn () => redirect()->route('user.kaldik.index', ['userId' => auth()->user()->id]))->name('kaldik');
        // Sidebar alias for `route('waka.pekan-efektif')`
        Route::get('/pekan-efektif-redirect', fn () => redirect()->route('waka.pekan-efektif.index'))->name('pekan-efektif-alias');
        Route::get('/sarana-prasarana', fn () => redirect()->route('sarpras.dashboard', ['userId' => auth()->user()->id]))->name('sarana-prasarana');

        // ── ALUMNI ───────────────────────────────────────────────
        Route::get('/data-alumni', fn () => redirect()->route('user.alumni.index', ['userId' => auth()->user()->id]))->name('data-alumni');
    });

/*
|--------------------------------------------------------------------------
| SPA FALLBACK
|--------------------------------------------------------------------------
*/
// ── VENDOR PORTAL ───────────────────────────────────────────────────────────────
Route::prefix('vendor')->name('vendor.')->group(function () {
    // Auth
    Route::get('/login', [App\Http\Controllers\Vendor\Auth\LoginController::class, 'show'])->name('login');
    Route::post('/login', [App\Http\Controllers\Vendor\Auth\LoginController::class, 'store'])->name('login.store');
    Route::post('/logout', [App\Http\Controllers\Vendor\Auth\LoginController::class, 'destroy'])->name('logout');

    // Portal (authenticated)
    Route::middleware('auth:vendor')->group(function () {
        Route::get('/', [VendorPortalController::class, 'index'])->name('dashboard');

        // Procurement Requests
        Route::get('/procurement', [ProcurementController::class, 'index'])->name('procurement.index');
        Route::get('/procurement/create', [ProcurementController::class, 'create'])->name('procurement.create');
        Route::post('/procurement', [ProcurementController::class, 'store'])->name('procurement.store');
        Route::get('/procurement/{id}', [ProcurementController::class, 'show'])->name('procurement.show');
        Route::post('/procurement/{id}/status', [ProcurementController::class, 'updateStatus'])->name('procurement.status.update');

        // Orders
        Route::get('/orders', [VendorPortalController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{id}', [VendorPortalController::class, 'orderShow'])->name('orders.show');
        Route::post('/orders/{id}/status', [VendorPortalController::class, 'orderUpdateStatus'])->name('orders.status.update');

        // Invoices
        Route::get('/invoices', [VendorPortalController::class, 'invoices'])->name('invoices.index');

        // Performance
        Route::get('/performance', [VendorPortalController::class, 'performance'])->name('performance');
    });
});

// ── SYSTEM ADMIN + SUPER ADMIN ─────────────────────────────────────────
Route::middleware(['auth', EnsureSuperAdminOrSystemAdmin::class])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        // System Admin dashboards (non-ViewAs endpoints)
        Route::get('/', [SystemDashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/features', [SystemDashboardController::class, 'features'])->name('features');
        Route::post('/features/uks-mode', [SystemDashboardController::class, 'updateUksMode'])->name('features.uks-mode');
        Route::get('/monitoring', [SystemDashboardController::class, 'monitoring'])->name('monitoring');
        Route::get('/maintenance', [SystemDashboardController::class, 'maintenance'])->name('maintenance');
        Route::get('/config', [SystemDashboardController::class, 'config'])->name('config');
        Route::get('/devtools', [SystemDashboardController::class, 'devtools'])->name('devtools');

        // ── SYSTEM-WIDE VIOLATIONS & PERMITS (for dashboard "Lihat Semua") ─
        Route::get('/violations', [DormitoryViolationController::class, 'allViolations'])->name('violations.index');
        Route::get('/permits', [DormitoryPermitController::class, 'allPermits'])->name('permits.index');

        // View As (both System Admin and Super Admin can use)
        Route::post('/view-as/role', [ViewAsController::class, 'setRole'])->name('view-as.role');
        Route::post('/view-as/login-as', [ViewAsController::class, 'loginAs'])->name('view-as.login-as');
        Route::post('/view-as/restore', [ViewAsController::class, 'restore'])->name('view-as.restore');
        Route::get('/view-as/users', [ViewAsController::class, 'listUsers'])->name('view-as.users');
        Route::post('/view-as/context', [ViewAsController::class, 'setContext'])->name('view-as.context');
        Route::post('/view-as/reset', [ViewAsController::class, 'reset'])->name('view-as.reset');
        Route::get('/view-as/state', [ViewAsController::class, 'state'])->name('view-as.state');
    });

// ── WEBHOOK WHATSAPP CLOUD API (META) ────────────────────────────────────────
Route::get('/webhook/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsAppWebhookController::class, 'receive']);

// Redirect tautan Sarpras lama tanpa userId ke format wajib /{userId}/sarpras.
Route::middleware(['auth'])->get('/sarpras/{path?}', function (Request $request, ?string $path = null) {
    $target = '/'.auth()->id().'/sarpras'.($path ? '/'.$path : '');
    $query = $request->getQueryString();

    return redirect($target.($query ? '?'.$query : ''));
})->where('path', '.*')->name('sarpras.legacy');

Route::fallback([HomeController::class, 'index']);

// ── AUTO-DEPLOY WEBHOOK ────────────────────────────────────────────────────────
// Lihat: app/Http/Controllers/DeployController.php
Route::post('/webhook/deploy', [DeployController::class, 'handle']);
Route::get('/health', [DeployController::class, 'health']);

Route::view('/offline', 'offline')->name('offline');

// ── VERIFIKASI PUBLIK SURAT (QR) ─────────────────────────────────────────────
Route::get('/verifikasi-surat/{token}', [SuratVerificationController::class, 'show'])
    ->name('surat.verify');
