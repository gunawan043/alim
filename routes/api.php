<?php

use App\Http\Controllers\Api\Connect\V1\ApprovalCommandController;
use App\Http\Controllers\Api\Connect\V1\ConnectSsoExchangeController;
use App\Http\Controllers\Api\Mobile\V1\AcademicController;
use App\Http\Controllers\Api\Mobile\V1\AnnouncementController;
use App\Http\Controllers\Api\Mobile\V1\AuthController;
use App\Http\Controllers\Api\Mobile\V1\DashboardController;
use App\Http\Controllers\Api\Mobile\V1\DeviceController;
use App\Http\Controllers\Api\Mobile\V1\DormitoryPermitController;
use App\Http\Controllers\Api\Mobile\V1\DormitoryReturnController;
use App\Http\Controllers\Api\Mobile\V1\DormitoryVisitController;
use App\Http\Controllers\Api\Mobile\V1\ForgotPasswordController;
use App\Http\Controllers\Api\Mobile\V1\JadwalController;
use App\Http\Controllers\Api\Mobile\V1\KalenderController;
use App\Http\Controllers\Api\Mobile\V1\MobileMahromController;
use App\Http\Controllers\Api\Mobile\V1\NotificationController;
use App\Http\Controllers\Api\Mobile\V1\PermitController;
use App\Http\Controllers\Api\Mobile\V1\RaportController;
use App\Http\Controllers\Api\Mobile\V1\SantriDataController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPassportApiController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetPredictiveApiController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetRepairVsReplaceApiController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\AssetTcoApiController;
use App\Http\Controllers\Api\Mobile\V1\Sarpras\SarprasIntelligenceApiController;
use App\Http\Controllers\Api\Mobile\V1\StudentController;
use App\Http\Controllers\Api\Mobile\V1\TahfidzController;
use App\Http\Controllers\Api\Mobile\V1\VersionController;
use App\Http\Controllers\Api\Mobile\V1\WaliChildInformationController;
use App\Http\Controllers\Api\Mobile\V1\WaliComplaintController;
use App\Http\Controllers\Api\Mobile\V1\WaliDocumentController;
use App\Http\Controllers\Api\Mobile\V1\WaliInformationController;
use App\Http\Controllers\Api\Mobile\V1\WaliLinkAdminController;
use App\Http\Controllers\Api\Mobile\V1\WaliLinkController;
use App\Http\Controllers\Api\Mobile\V1\WaliReportController;
use App\Http\Controllers\Api\Mobile\V1\WaliSantriController;
use App\Http\Controllers\Api\Mobile\V1\WaliSchoolController;
use App\Http\Controllers\Api\Mobile\V1\WaliSurveyController;
use App\Http\Controllers\Api\Pos\V1\DivisiController as PosDivisiController;
use App\Http\Controllers\Api\Pos\V1\JabatanController as PosJabatanController;
use App\Http\Controllers\Api\Pos\V1\JenisGtkController as PosJenisGtkController;
use App\Http\Controllers\Api\Pos\V1\KepegawaianController as PosKepegawaianController;
use App\Http\Controllers\Api\Pos\V1\KoneksiController as PosKoneksiController;
use App\Http\Controllers\Api\Pos\V1\SantriController as PosSantriController;
use App\Http\Controllers\Api\Pos\V1\SatuanKerjaController as PosSatuanKerjaController;
use App\Http\Controllers\Api\Pos\V1\SekolahController as PosSekolahController;
use App\Http\Controllers\Api\Recruitment\V1\JabatanController as RecruitmentJabatanController;
use App\Http\Controllers\Api\Recruitment\V1\JenisGtkController as RecruitmentJenisGtkController;
use App\Http\Controllers\Api\Recruitment\V1\KoneksiController as RecruitmentKoneksiController;
use App\Http\Controllers\Api\Recruitment\V1\PegawaiController as RecruitmentPegawaiController;
use App\Http\Controllers\Api\Recruitment\V1\RoleController as RecruitmentRoleController;
use App\Http\Controllers\Api\Recruitment\V1\SatuanKerjaController as RecruitmentSatuanKerjaController;
use App\Http\Controllers\Api\Recruitment\V1\SekolahController as RecruitmentSekolahController;
use App\Http\Controllers\Api\Sarpras\AssetPassportController;
use App\Http\Controllers\Api\Sarpras\RepairRequestController;
use App\Http\Controllers\Api\Sarpras\SarprasDashboardController;
use App\Http\Controllers\Api\Sarpras\StockOpnameController;
use App\Http\Controllers\Api\Sarpras\WorkOrderController;
use App\Http\Controllers\Sarpras\SarprasMobileSyncController;
use App\Http\Controllers\WilayahController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Public + Auth
|--------------------------------------------------------------------------
*/

// ── PUBLIC: WILAYAH (Indonesia provinces/cities/districts/villages) ──────────
// Data ini tidak sensitif, tidak perlu login
Route::get('wilayah/provinces', [WilayahController::class, 'provinces'])->name('wilayah.provinces');
Route::get('wilayah/cities/{provinceCode}', [WilayahController::class, 'cities'])->name('wilayah.cities');
Route::get('wilayah/districts/{cityCode}', [WilayahController::class, 'districts'])->name('wilayah.districts');
Route::get('wilayah/villages/{districtCode}', [WilayahController::class, 'villages'])->name('wilayah.villages');

// ── MOBILE WALI API v1 ───────────────────────────────────────────────────────

Route::prefix('mobile/v1')->group(function () {

    // ── META / VERSION / BUILD ───────────────────────────────────────────────
    Route::get('version', [VersionController::class, 'version'])->name('mobile.v1.version');
    Route::get('build', [VersionController::class, 'build'])->name('mobile.v1.build');
    Route::get('system/status', [VersionController::class, 'status'])->name('mobile.v1.system.status');
    Route::get('health', [VersionController::class, 'health'])->name('mobile.v1.health');

    // ── PUSH NOTIFICATION DEVICES ────────────────────────────────────────────
    // Token FCM (Android) atau APNs (iOS) per instalasi aplikasi mobile.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('devices', [DeviceController::class, 'index'])->name('mobile.v1.devices.index');
        Route::post('devices', [DeviceController::class, 'store'])->name('mobile.v1.devices.store');
        Route::delete('devices/{device}', [DeviceController::class, 'destroy'])
            ->whereUuid('device')
            ->name('mobile.v1.devices.destroy');
    });

    // ── AUTH ─────────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('two-factor/verify', [AuthController::class, 'verifyMobileTwoFactor']);
        Route::post('two-factor/resend', [AuthController::class, 'resendMobileTwoFactor']);
        Route::post('google', [AuthController::class, 'google']);
        Route::post('logout', [AuthController::class, 'logout'])
            ->middleware('auth:sanctum');
        Route::post('logout-all', [AuthController::class, 'logoutAll'])
            ->middleware('auth:sanctum');

        // ── FORGOT / RESET PASSWORD (Sprint 2) ───────────────────────
        Route::post('forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
        Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);

        // Authenticated routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me'])->middleware(['wali.school.context', 'ability:profile.read']);
            Route::put('me', [AuthController::class, 'updateProfile'])->middleware('ability:profile.write');

            // ── SESSION MANAGEMENT (Sprint 2) ────────────────────────────────
            Route::get('sessions', [AuthController::class, 'sessions']);
            Route::patch('sessions/current', [AuthController::class, 'updateCurrentSession']);
            Route::delete('sessions/others', [AuthController::class, 'revokeOtherSessions']);
        });
    });

    // ── WALI API (user-scoped) ─────────────────────────────────────────────
    // Semua endpoint memakai {userId} UUID dan divalidasi api.user-scope.
    Route::prefix('{userId}/wali')->middleware(['auth:sanctum', 'api.user-scope', 'wali.mobile'])->group(function () {
        // Pilihan sekolah untuk wali multi-sekolah.
        Route::get('schools', [WaliSchoolController::class, 'index'])
            ->middleware(['wali.school.context', 'ability:school.read']);

        // Penghubungan wali–santri
        Route::prefix('links')->group(function () {
            Route::post('preview', [WaliLinkController::class, 'preview'])->middleware(['throttle:wali-link', 'ability:wali.link.write']);
            Route::post('confirm', [WaliLinkController::class, 'confirm'])->middleware('ability:wali.link.write');
            Route::get('children', [WaliLinkController::class, 'children'])->middleware('ability:wali.link.read');
            Route::get('siblings/{student}', [WaliLinkController::class, 'siblings'])->whereUuid('student')->middleware('ability:wali.link.read');
            Route::post('siblings/preview', [WaliLinkController::class, 'siblingPreview'])->middleware(['throttle:wali-link', 'ability:wali.link.write']);

            Route::prefix('admin')->group(function () {
                Route::get('pending', [WaliLinkAdminController::class, 'pending'])->middleware('ability:wali.link.manage');
                Route::post('{id}/approve', [WaliLinkAdminController::class, 'approve'])->whereUuid('id')->middleware('ability:wali.link.manage');
                Route::post('{id}/reject', [WaliLinkAdminController::class, 'reject'])->whereUuid('id')->middleware('ability:wali.link.manage');
                Route::post('{id}/suspend', [WaliLinkAdminController::class, 'suspend'])->whereUuid('id')->middleware('ability:wali.link.manage');
                Route::post('{id}/revoke', [WaliLinkAdminController::class, 'revoke'])->whereUuid('id')->middleware('ability:wali.link.manage');
            });
        });

        // Survei kepuasan
        Route::get('surveys', [WaliSurveyController::class, 'index'])->middleware('ability:survey.read');
        Route::get('surveys/{id}', [WaliSurveyController::class, 'show'])->whereUuid('id')->middleware('ability:survey.read');
        Route::post('surveys/{id}', [WaliSurveyController::class, 'submit'])->whereUuid('id')->middleware('ability:survey.write');

        // Kritik & saran
        Route::get('complaints', [WaliComplaintController::class, 'index'])->middleware('ability:complaint.read');
        Route::post('complaints', [WaliComplaintController::class, 'store'])->middleware('ability:complaint.write');
        Route::get('complaints/{id}', [WaliComplaintController::class, 'show'])->whereUuid('id')->middleware('ability:complaint.read');

        // Laporan ringkas
        Route::get('reports', [WaliReportController::class, 'index'])->middleware('ability:report.read');

        // Export dokumen
        Route::get('raport/{student}/pdf', [WaliDocumentController::class, 'raport'])->whereUuid('student')->middleware('ability:raport.read');
        Route::get('achievements/{id}/certificate', [WaliDocumentController::class, 'achievementCertificate'])->whereUuid('id')->middleware('ability:achievement.read');
        Route::get('tahfidz/certificates/{student}', [WaliDocumentController::class, 'tahfidzCertificates'])->whereUuid('student')->middleware('ability:tahfidz.read');
        Route::get('tahfidz/certificates/{certificate}/pdf', [WaliDocumentController::class, 'tahfidzCertificate'])->whereUuid('certificate')->middleware('ability:tahfidz.read');

        // Pengumuman & broadcast (sekolah/asrama/global)
        Route::get('announcements', [AnnouncementController::class, 'index'])->middleware('ability:announcement.read');
        Route::get('announcements/{id}', [AnnouncementController::class, 'show'])->whereUuid('id')->middleware('ability:announcement.read');
        Route::post('announcements/{id}/read', [AnnouncementController::class, 'markRead'])->whereUuid('id')->middleware('ability:announcement.read');
        Route::post('announcements/{id}/ack', [AnnouncementController::class, 'acknowledge'])->whereUuid('id')->middleware('ability:announcement.read');
    });

    // ── SANTRI ───────────────────────────────────────────────��────────────────
    Route::middleware(['auth:sanctum', 'wali.mobile', 'wali.school.context', 'organization.context'])->group(function () {
        Route::get('santri', [StudentController::class, 'index'])->middleware('ability:child.read');
        Route::get('santri/verify-nik', [StudentController::class, 'verifyNik'])->middleware('ability:child.read');
        Route::get('santri/{id}', [StudentController::class, 'show'])->middleware('ability:child.read');
        Route::post('santri', [StudentController::class, 'store'])->middleware('ability:child.write');

        // ── WALI-SANTRI LINKS ─────────────────────────────────────────────────
        Route::prefix('wali-santri')->group(function () {
            Route::post('start-registration', [WaliSantriController::class, 'startRegistration'])->middleware('ability:wali.link.write');
            Route::post('link', [WaliSantriController::class, 'link'])->middleware('ability:wali.link.write');
            Route::post('request', [WaliSantriController::class, 'requestWaliRole'])->middleware('ability:wali.link.write');
            Route::get('requests', [WaliSantriController::class, 'listRequests'])->middleware('ability:wali.link.read');
            Route::put('requests/{token}', [WaliSantriController::class, 'approveReject'])->middleware('ability:wali.link.write');
            Route::delete('{id}', [WaliSantriController::class, 'destroy'])->middleware('ability:wali.link.write');
        });

        // ── MAHROM MANAGEMENT (Sprint 5) ──────────────────────
        Route::prefix('students/{student}/mahrom')->group(function () {
            Route::get('/', [MobileMahromController::class, 'index'])->middleware('ability:mahrom.read');
            Route::post('/', [MobileMahromController::class, 'store'])->middleware('ability:mahrom.write');
            Route::put('/{mahrom}', [MobileMahromController::class, 'update'])->middleware('ability:mahrom.write');
            Route::patch('/{mahrom}', [MobileMahromController::class, 'update'])->middleware('ability:mahrom.write');
            Route::delete('/{mahrom}', [MobileMahromController::class, 'destroy'])->middleware('ability:mahrom.write');
        });

        // ── SANTRI DATA ───────────────────────────────────────────────────��──
        Route::prefix('santri/{id}')->group(function () {
            Route::get('attendance', [SantriDataController::class, 'attendance'])->middleware('ability:attendance.read');
            Route::get('attendance/history', [SantriDataController::class, 'attendanceHistory'])->middleware('ability:attendance.read');
            Route::get('grades', [SantriDataController::class, 'grades'])->middleware('ability:grades.read');
            Route::get('violations', [SantriDataController::class, 'violations'])->middleware('ability:violation.read');
            Route::get('dormitory', [SantriDataController::class, 'dormitoryInfo'])->middleware('ability:dormitory.read');
            Route::get('dormitory-attendance', [SantriDataController::class, 'dormitoryAttendance'])->middleware('ability:dormitory.read');
            Route::get('dormitory-violations', [SantriDataController::class, 'dormitoryViolations'])->middleware('ability:dormitory.read');
            Route::get('health', [SantriDataController::class, 'health'])->middleware('ability:health.read');
            Route::get('tahfidz', [SantriDataController::class, 'tahfidz'])->middleware('ability:tahfidz.read');
            Route::get('classes', [SantriDataController::class, 'currentClasses'])->middleware('ability:class.read');
            Route::get('achievements', [SantriDataController::class, 'achievements'])->middleware('ability:achievement.read');
            Route::get('mahroms', [SantriDataController::class, 'mahroms'])->middleware('ability:mahrom.read');

            // Informasi lanjutan per anak.
            Route::get('health/history', [WaliChildInformationController::class, 'healthHistory'])->middleware('ability:health.read');
            Route::get('library/loans', [WaliChildInformationController::class, 'libraryLoans'])->middleware('ability:library.read');
            Route::get('library/visits', [WaliChildInformationController::class, 'libraryVisits'])->middleware('ability:library.read');
            Route::get('library/member-card', [WaliChildInformationController::class, 'libraryMemberCard'])->middleware('ability:library.read');
            Route::get('class-history', [WaliChildInformationController::class, 'classHistory'])->middleware('ability:class.read');
            Route::get('dormitory/history', [WaliChildInformationController::class, 'dormitoryHistory'])->middleware('ability:dormitory.read');
            Route::get('dormitory/rewards', [WaliChildInformationController::class, 'dormitoryRewards'])->middleware('ability:dormitory.read');
            Route::get('extracurricular', [WaliChildInformationController::class, 'extracurricular'])->middleware('ability:extracurricular.read');
            Route::get('competitions', [WaliChildInformationController::class, 'competitions'])->middleware('ability:achievement.read');
            Route::get('teachers', [WaliChildInformationController::class, 'teachers'])->middleware('ability:school.read');
            Route::get('exam-schedules', [WaliChildInformationController::class, 'examSchedules'])->middleware('ability:schedule.read');
        });

        // ── NOTIFICATIONS ──────────────────────────────────────────────────
        Route::get('notifications', [NotificationController::class, 'index'])->middleware('ability:notification.read');
        Route::put('notifications/{id}/read', [NotificationController::class, 'markRead'])->middleware('ability:notification.write');

        // ── DORMITORY PERMIT ─────────────────────────────────────────────
        Route::get('dormitory-permits', [DormitoryPermitController::class, 'index'])->middleware('ability:permit.read');
        Route::post('dormitory/permit', [DormitoryPermitController::class, 'store'])->middleware('ability:permit.create');

        // ── DORMITORY VISIT (Sprint 4) ─────────────────────────────────────
        Route::get('dormitory/visits', [DormitoryVisitController::class, 'index'])->middleware('ability:dormitory.read');
        Route::post('dormitory/visit', [DormitoryVisitController::class, 'store'])->middleware('ability:dormitory.write');
        Route::get('dormitory/visits/{id}', [DormitoryVisitController::class, 'show'])->middleware('ability:dormitory.read');
        Route::patch('dormitory/visits/{id}/check-in', [DormitoryVisitController::class, 'checkIn'])->middleware('ability:dormitory.write');
        Route::patch('dormitory/visits/{id}/check-out', [DormitoryVisitController::class, 'checkOut'])->middleware('ability:dormitory.write');

        // ── DORMITORY RETURN (Sprint 4) ────────────────────────────────────
        Route::get('dormitory/returns', [DormitoryReturnController::class, 'index'])->middleware('ability:dormitory.read');
        Route::post('dormitory/return', [DormitoryReturnController::class, 'store'])->middleware('ability:dormitory.write');

        // ── INFORMASI UMUM ─────────────────────────────────────────────────
        Route::get('library/books', [WaliInformationController::class, 'libraryBooks'])->middleware('ability:library.read');
        Route::get('library/programs', [WaliInformationController::class, 'libraryPrograms'])->middleware('ability:library.read');
        Route::get('schools/{id}/profile', [WaliInformationController::class, 'schoolProfile'])->middleware('ability:school.read');
        Route::get('agendas', [WaliInformationController::class, 'agendas'])->middleware('ability:calendar.read');
        Route::get('regulations', [WaliInformationController::class, 'regulations'])->middleware('ability:regulation.read');
        Route::get('prayer-times', [WaliInformationController::class, 'prayerTimes'])->middleware('ability:calendar.read');

        // ── DASHBOARD ──────────────────────────────────────────────────────
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->middleware('ability:dashboard.read');
            Route::get('attendance', [DashboardController::class, 'attendance'])->middleware('ability:dashboard.read');
        });

        // ── JADWAL KBM (Sprint 3) ─────────────────────────────────────────
        Route::prefix('jadwal')->group(function () {
            Route::get('/', [JadwalController::class, 'index'])->middleware('ability:schedule.read');
            Route::get('week', [JadwalController::class, 'week'])->middleware('ability:schedule.read');
        });

        // ── IZIN / SAKIT (Sprint 3) ───────────────────────────────────────
        Route::prefix('permit')->group(function () {
            Route::post('request', [PermitController::class, 'request'])->middleware('ability:permit.create');
            Route::get('/', [PermitController::class, 'myPermits'])->middleware('ability:permit.read');
        });

        // ── RAPORT (Sprint 3) ─────────────────────────────────────────────
        Route::prefix('raport')->group(function () {
            Route::get('status', [RaportController::class, 'status'])->middleware('ability:raport.read');
            Route::get('{student_id}', [RaportController::class, 'show'])->middleware('ability:raport.read');
        });

        // ── TAHFIDZ (Sprint 3) ────────────────────────────────────────────
        Route::prefix('tahfidz')->group(function () {
            Route::get('/', [TahfidzController::class, 'index'])->middleware('ability:tahfidz.read');
            Route::get('progress', [TahfidzController::class, 'progress'])->middleware('ability:tahfidz.read');
        });

        // ── KALENDER KALDIK (Sprint 3) ────────────────────────────────────
        Route::get('kalender', [KalenderController::class, 'index'])->middleware('ability:calendar.read');

        // ── ACADEMIC SUMMARY (Sprint 3) ───────────────────────────────────
        Route::prefix('academic')->group(function () {
            Route::get('summary', [AcademicController::class, 'summary'])->middleware('ability:academic.read');
            Route::get('rewards', [AcademicController::class, 'rewards'])->middleware('ability:academic.read');
        });

        // ── NOTIFICATIONS — mark-all-read (Sprint 3) ──────────────────────
        Route::patch('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->middleware('ability:notification.write');
    });
});

// ── AUTHENTICATED ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'organization.context'])->get('/user', function (Request $request) {
    return $request->user();
});

// ── SARPRAS / ASSET MOBILE API v1 ────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'organization.context'])->prefix('sarpras')->group(function () {

    // ── ASSET PASSPORT ─────────────────────────────────────────────────────
    // Scan QR code or enter asset code → full passport with lifecycle data
    Route::get('passport/{lookup}', [
        AssetPassportApiController::class,
        'show',
    ])->name('api.sarpras.passport');

    // ── DAMAGE REPORT SUBMISSION (from mobile) ───────────────────────────
    Route::post('damage-report', [
        AssetPassportApiController::class,
        'submitDamageReport',
    ])->name('api.sarpras.damage-report');

    // ── REPAIR REQUEST WORKFLOW ──────────────────────────────────────────
    Route::prefix('repairs')->group(function () {
        Route::get('/', [
            AssetPassportApiController::class,
            'listRepairs',
        ])->name('api.sarpras.repairs.index');

        Route::post('{repairId}/verify', [
            AssetPassportApiController::class,
            'verifyRepair',
        ])->name('api.sarpras.repairs.verify');

        Route::post('{repairId}/generate-work-order', [
            AssetPassportApiController::class,
            'generateWorkOrder',
        ])->name('api.sarpras.repairs.generate-wo');

        Route::post('{repairId}/pic-verify', [
            AssetPassportApiController::class,
            'verifyByPic',
        ])->name('api.sarpras.repairs.pic-verify');
    });

    // ── WORK ORDER WORKFLOW ──────────────────────────────────────────────
    Route::prefix('work-orders')->group(function () {
        Route::post('{orderId}/assign', [
            AssetPassportApiController::class,
            'assignTechnician',
        ])->name('api.sarpras.work-orders.assign');

        Route::post('{orderId}/accept', [
            AssetPassportApiController::class,
            'acceptOrder',
        ])->name('api.sarpras.work-orders.accept');

        Route::post('{orderId}/start', [
            AssetPassportApiController::class,
            'startWork',
        ])->name('api.sarpras.work-orders.start');

        Route::post('{orderId}/complete', [
            AssetPassportApiController::class,
            'completeOrder',
        ])->name('api.sarpras.work-orders.complete');
    });

    // ── ASSET INTELLIGENCE (v2 mobile) ─────────────────────────────────────
    Route::prefix('tco')->group(function () {
        Route::get('{assetId}', [AssetTcoApiController::class, 'show'])->name('api.sarpras.tco.show');
        Route::post('compare', [AssetTcoApiController::class, 'compare'])->name('api.sarpras.tco.compare');
    });

    Route::prefix('repair-vs-replace')->group(function () {
        Route::get('{assetId}', [AssetRepairVsReplaceApiController::class, 'show'])->name('api.sarpras.rvr.show');
        Route::get('priority/list', [AssetRepairVsReplaceApiController::class, 'listHighPriority'])->name('api.sarpras.rvr.priority');
    });

    Route::prefix('predictive')->group(function () {
        Route::get('{assetId}', [AssetPredictiveApiController::class, 'show'])->name('api.sarpras.predictive.show');
        Route::get('high-risk/list', [AssetPredictiveApiController::class, 'highRisk'])->name('api.sarpras.predictive.high_risk');
    });

    Route::get('intelligence/summary', [SarprasIntelligenceApiController::class, 'summary'])->name('api.sarpras.intelligence.summary');
});

// ── SARPRAS WEB API v2 ─────────────────────────────────────────────────────────
// Uses App\Http\Controllers\Api\Sarpras\* — web/scanner/admin facing
Route::middleware(['auth:sanctum', 'organization.context'])->prefix('sarpras/v2')->group(function () {

    // ── ENTERPRISE DASHBOARD ─────────────────────────────────────────────
    Route::prefix('dashboard')->group(function () {
        Route::get('overview', [SarprasDashboardController::class, 'overview']);
        Route::get('activity-feed', [SarprasDashboardController::class, 'activityFeed']);
        Route::get('category-heatmap', [SarprasDashboardController::class, 'categoryHeatmap']);
        Route::get('cost-leak-report', [SarprasDashboardController::class, 'costLeakReport']);
    });

    // ── ASSET PASSPORT (WEB) ─────────────────────────────────────────────
    Route::prefix('passport')->group(function () {
        Route::post('qr-scan', [AssetPassportController::class, 'qrScan']);
        Route::get('public/{token}', [AssetPassportController::class, 'qrPublicLookup']);
        Route::get('scan-history', [AssetPassportController::class, 'scanHistory']);
        Route::get('{assetId}', [AssetPassportController::class, 'passportDetail']);
        Route::get('{assetId}/cost-history', [AssetPassportController::class, 'costHistory']);
    });

    // ── REPAIR REQUESTS ──────────────────────────────────────────────────
    Route::prefix('repairs')->group(function () {
        Route::get('/', [RepairRequestController::class, 'index']);
        Route::post('/', [RepairRequestController::class, 'submit']);
        Route::get('{id}', [RepairRequestController::class, 'show']);
        Route::post('{id}/review', [RepairRequestController::class, 'review']);
    });

    // ── WORK ORDERS ──────────────────────────────────────────────────────
    Route::prefix('work-orders')->group(function () {
        Route::get('/', [WorkOrderController::class, 'index']);
        Route::post('/', [WorkOrderController::class, 'generateFromRepair']);
        Route::get('stats', [WorkOrderController::class, 'stats']);
        Route::get('{id}', [WorkOrderController::class, 'show']);
        Route::post('{id}/transition', [WorkOrderController::class, 'transition']);
        Route::post('{id}/progress', [WorkOrderController::class, 'addProgress']);
        Route::post('{id}/costs', [WorkOrderController::class, 'recordCost']);
        Route::post('{id}/spareparts', [WorkOrderController::class, 'recordSparePart']);
    });

    // ── STOCK OPNAME ─────────────────────────────────────────────────────
    Route::prefix('opname')->group(function () {
        Route::get('sessions', [StockOpnameController::class, 'sessions']);
        Route::post('sessions', [StockOpnameController::class, 'createSession']);
        Route::get('sessions/{sessionId}', [StockOpnameController::class, 'showSession']);
        Route::post('sessions/{sessionId}/start', [StockOpnameController::class, 'startSession']);
        Route::post('sessions/{sessionId}/close', [StockOpnameController::class, 'closeSession']);
        Route::post('sessions/{sessionId}/variance', [StockOpnameController::class, 'varianceReport']);
        Route::post('sessions/{sessionId}/items/{itemId}', [StockOpnameController::class, 'recordObservation']);
        Route::post('sessions/{sessionId}/qr-scan', [StockOpnameController::class, 'qrScan']);
    });

    // ── OFFLINE MOBILE SYNC (batch operations) ──────────────────────
    Route::prefix('sarpras/sync')->group(function () {
        Route::get('pull', [SarprasMobileSyncController::class, 'pull']);
        Route::post('push', [SarprasMobileSyncController::class, 'push']);
    });
});

// ── POS / KASIR INTEGRATION API v1 ───────────────────────────────────────────
// Read-only master data for the external POS (kasir) application.
// Auth: static token via custom header (default X-ALIM-POS-KEY) or Bearer.
// Configure at Super Admin → Pengaturan Sistem → Integrasi POS.
Route::middleware('pos.api-key')->prefix('pos/v1')->group(function () {
    // ── TES KONEKSI ──────────────────────────────────────────────────────
    Route::get('ping', [PosKoneksiController::class, 'ping']);

    // ── SATUAN KERJA ─────────────────────────────────────────────────────
    Route::get('satuan-kerja', [PosSatuanKerjaController::class, 'index']);
    Route::get('satuan-kerja/{id}', [PosSatuanKerjaController::class, 'show'])->whereUuid('id');

    // ── KEPEGAWAIAN (GTK) ────────────────────────────────────────────────
    Route::get('kepegawaian', [PosKepegawaianController::class, 'index']);
    Route::get('kepegawaian/{id}', [PosKepegawaianController::class, 'show'])->whereUuid('id');

    // ── MASTER ORGANISASI ────────────────────────────────────────────────
    Route::get('divisi', [PosDivisiController::class, 'index']);
    Route::get('jabatan', [PosJabatanController::class, 'index']);
    Route::get('jenis-gtk', [PosJenisGtkController::class, 'index']);

    // ── PROFIL SEKOLAH / LEMBAGA ─────────────────────────────────────────
    Route::get('sekolah', [PosSekolahController::class, 'index']);
    Route::get('sekolah/{id}', [PosSekolahController::class, 'show'])->whereUuid('id');

    // ── SANTRI ───────────────────────────────────────────────────────────
    Route::get('santri', [PosSantriController::class, 'index']);
    Route::get('santri/{id}', [PosSantriController::class, 'show'])->whereUuid('id');
});

// ── RECRUITMENT INTEGRATION API v1 ───────────────────────────────────────────
// Data referensi milik Personalia untuk aplikasi recruitment (applicant app).
// Auth: static token via custom header (default X-ALIM-RECRUITMENT-KEY).
Route::middleware('recruitment.api-key')->prefix('recruitment/v1')->group(function () {
    Route::get('ping', [RecruitmentKoneksiController::class, 'ping']);

    Route::get('pegawai', [RecruitmentPegawaiController::class, 'index']);
    Route::get('pegawai/{id}', [RecruitmentPegawaiController::class, 'show'])->whereUuid('id');

    Route::get('satuan-kerja', [RecruitmentSatuanKerjaController::class, 'index']);
    Route::get('satuan-kerja/{id}', [RecruitmentSatuanKerjaController::class, 'show'])->whereUuid('id');

    Route::get('jabatan', [RecruitmentJabatanController::class, 'index']);
    Route::get('jenis-gtk', [RecruitmentJenisGtkController::class, 'index']);

    Route::get('sekolah', [RecruitmentSekolahController::class, 'index']);
    Route::get('sekolah/{id}', [RecruitmentSekolahController::class, 'show'])->whereUuid('id');

    Route::get('role', [RecruitmentRoleController::class, 'index']);
});

// ── ALIM CONNECT APPROVAL COMMAND API v1 ────────────────────────────────────
// Dedicated service-to-service command endpoint. It is intentionally separate
// from the read-only organization/master API and from POS/Recruitment.
// Auth: dedicated approval key (default X-ALIM-CONNECT-APPROVAL-KEY) or Bearer.
Route::middleware(['connect.sso-key', 'throttle:60,1'])
    ->prefix('connect/v1')
    ->group(function () {
        Route::post('sso/exchange', [ConnectSsoExchangeController::class, '__invoke']);
    });

Route::middleware(['connect.approval-key', 'connect.contract-headers', 'throttle:60,1'])
    ->prefix('connect/v1')
    ->group(function () {
        Route::post('approval-commands', [ApprovalCommandController::class, 'store']);
    });
