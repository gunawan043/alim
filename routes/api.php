<?php

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
use App\Http\Controllers\Api\Mobile\V1\LjkController as MobileLjkController;
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
use App\Http\Controllers\Api\Mobile\V1\WaliComplaintController;
use App\Http\Controllers\Api\Mobile\V1\WaliDocumentController;
use App\Http\Controllers\Api\Mobile\V1\WaliLinkAdminController;
use App\Http\Controllers\Api\Mobile\V1\WaliLinkController;
use App\Http\Controllers\Api\Mobile\V1\WaliReportController;
use App\Http\Controllers\Api\Mobile\V1\WaliSantriController;
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
            Route::get('me', [AuthController::class, 'me']);
            Route::put('me', [AuthController::class, 'updateProfile']);

            // ── SESSION MANAGEMENT (Sprint 2) ────────────────────────────────
            Route::get('sessions', [AuthController::class, 'sessions']);
            Route::patch('sessions/current', [AuthController::class, 'updateCurrentSession']);
            Route::delete('sessions/others', [AuthController::class, 'revokeOtherSessions']);
        });
    });

    // ── WALI API (user-scoped) ─────────────────────────────────────────────
    // Semua endpoint memakai {userId} UUID dan divalidasi api.user-scope.
    Route::prefix('{userId}/wali')->middleware(['auth:sanctum', 'api.user-scope'])->group(function () {
        // Penghubungan wali–santri
        Route::prefix('links')->group(function () {
            Route::post('preview', [WaliLinkController::class, 'preview'])->middleware('throttle:wali-link');
            Route::post('confirm', [WaliLinkController::class, 'confirm']);
            Route::get('children', [WaliLinkController::class, 'children']);
            Route::get('siblings/{student}', [WaliLinkController::class, 'siblings'])->whereUuid('student');
            Route::post('siblings/preview', [WaliLinkController::class, 'siblingPreview'])->middleware('throttle:wali-link');

            Route::prefix('admin')->group(function () {
                Route::get('pending', [WaliLinkAdminController::class, 'pending']);
                Route::post('{id}/approve', [WaliLinkAdminController::class, 'approve'])->whereUuid('id');
                Route::post('{id}/reject', [WaliLinkAdminController::class, 'reject'])->whereUuid('id');
                Route::post('{id}/suspend', [WaliLinkAdminController::class, 'suspend'])->whereUuid('id');
                Route::post('{id}/revoke', [WaliLinkAdminController::class, 'revoke'])->whereUuid('id');
            });
        });

        // Survei kepuasan
        Route::get('surveys', [WaliSurveyController::class, 'index']);
        Route::get('surveys/{id}', [WaliSurveyController::class, 'show'])->whereUuid('id');
        Route::post('surveys/{id}', [WaliSurveyController::class, 'submit'])->whereUuid('id');

        // Kritik & saran
        Route::get('complaints', [WaliComplaintController::class, 'index']);
        Route::post('complaints', [WaliComplaintController::class, 'store']);
        Route::get('complaints/{id}', [WaliComplaintController::class, 'show'])->whereUuid('id');

        // Laporan ringkas
        Route::get('reports', [WaliReportController::class, 'index']);

        // Export dokumen
        Route::get('raport/{student}/pdf', [WaliDocumentController::class, 'raport'])->whereUuid('student');
        Route::get('achievements/{id}/certificate', [WaliDocumentController::class, 'achievementCertificate'])->whereUuid('id');
        Route::get('tahfidz/certificates/{student}', [WaliDocumentController::class, 'tahfidzCertificates'])->whereUuid('student');
        Route::get('tahfidz/certificates/{certificate}/pdf', [WaliDocumentController::class, 'tahfidzCertificate'])->whereUuid('certificate');

        // Pengumuman & broadcast (sekolah/asrama/global)
        Route::get('announcements', [AnnouncementController::class, 'index']);
        Route::get('announcements/{id}', [AnnouncementController::class, 'show'])->whereUuid('id');
        Route::post('announcements/{id}/read', [AnnouncementController::class, 'markRead'])->whereUuid('id');
        Route::post('announcements/{id}/ack', [AnnouncementController::class, 'acknowledge'])->whereUuid('id');
    });

    // ── SANTRI ───────────────────────────────────────────────��────────────────
    Route::middleware(['auth:sanctum', 'wali.school.context', 'organization.context'])->group(function () {
        Route::get('santri', [StudentController::class, 'index']);
        Route::get('santri/verify-nik', [StudentController::class, 'verifyNik']);
        Route::get('santri/{id}', [StudentController::class, 'show']);
        Route::post('santri', [StudentController::class, 'store']);

        // ── WALI-SANTRI LINKS ─────────────────────────────────────────────────
        Route::prefix('wali-santri')->group(function () {
            Route::post('start-registration', [WaliSantriController::class, 'startRegistration']);
            Route::post('link', [WaliSantriController::class, 'link']);
            Route::post('request', [WaliSantriController::class, 'requestWaliRole']);
            Route::get('requests', [WaliSantriController::class, 'listRequests']);
            Route::put('requests/{token}', [WaliSantriController::class, 'approveReject']);
            Route::delete('{id}', [WaliSantriController::class, 'destroy']);
        });

        // ── MAHROM MANAGEMENT (Sprint 5) ──────────────────────
        Route::prefix('students/{student}/mahrom')->group(function () {
            Route::get('/', [MobileMahromController::class, 'index']);
            Route::post('/', [MobileMahromController::class, 'store']);
            Route::put('/{mahrom}', [MobileMahromController::class, 'update']);
            Route::patch('/{mahrom}', [MobileMahromController::class, 'update']);
            Route::delete('/{mahrom}', [MobileMahromController::class, 'destroy']);
        });

        // ── SANTRI DATA ───────────────────────────────────────────────────��──
        Route::prefix('santri/{id}')->group(function () {
            Route::get('attendance', [SantriDataController::class, 'attendance']);
            Route::get('attendance/history', [SantriDataController::class, 'attendanceHistory']);
            Route::get('grades', [SantriDataController::class, 'grades']);
            Route::get('violations', [SantriDataController::class, 'violations']);
            Route::get('dormitory', [SantriDataController::class, 'dormitoryInfo']);
            Route::get('dormitory-attendance', [SantriDataController::class, 'dormitoryAttendance']);
            Route::get('dormitory-violations', [SantriDataController::class, 'dormitoryViolations']);
            Route::get('health', [SantriDataController::class, 'health']);
            Route::get('tahfidz', [SantriDataController::class, 'tahfidz']);
            Route::get('classes', [SantriDataController::class, 'currentClasses']);
            Route::get('achievements', [SantriDataController::class, 'achievements']);
            Route::get('mahroms', [SantriDataController::class, 'mahroms']);
        });

        // ── NOTIFICATIONS ──────────────────────────────────────────────────
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::put('notifications/{id}/read', [NotificationController::class, 'markRead']);

        // ── DORMITORY PERMIT ─────────────────────────────────────────────
        Route::get('dormitory-permits', [DormitoryPermitController::class, 'index']);
        Route::post('dormitory/permit', [DormitoryPermitController::class, 'store']);

        // ── DORMITORY VISIT (Sprint 4) ─────────────────────────────────────
        Route::get('dormitory/visits', [DormitoryVisitController::class, 'index']);
        Route::post('dormitory/visit', [DormitoryVisitController::class, 'store']);
        Route::get('dormitory/visits/{id}', [DormitoryVisitController::class, 'show']);
        Route::patch('dormitory/visits/{id}/check-in', [DormitoryVisitController::class, 'checkIn']);
        Route::patch('dormitory/visits/{id}/check-out', [DormitoryVisitController::class, 'checkOut']);

        // ── DORMITORY RETURN (Sprint 4) ────────────────────────────────────
        Route::get('dormitory/returns', [DormitoryReturnController::class, 'index']);
        Route::post('dormitory/return', [DormitoryReturnController::class, 'store']);

        // ── DASHBOARD ──────────────────────────────────────────────────────
        Route::prefix('dashboard')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('attendance', [DashboardController::class, 'attendance']);
        });

        // ── JADWAL KBM (Sprint 3) ─────────────────────────────────────────
        Route::prefix('jadwal')->group(function () {
            Route::get('/', [JadwalController::class, 'index']);
            Route::get('week', [JadwalController::class, 'week']);
        });

        // ── IZIN / SAKIT (Sprint 3) ───────────────────────────────────────
        Route::prefix('permit')->group(function () {
            Route::post('request', [PermitController::class, 'request']);
            Route::get('/', [PermitController::class, 'myPermits']);
        });

        // ── RAPORT (Sprint 3) ─────────────────────────────────────────────
        Route::prefix('raport')->group(function () {
            Route::get('status', [RaportController::class, 'status']);
            Route::get('{student_id}', [RaportController::class, 'show']);
        });

        // ── TAHFIDZ (Sprint 3) ────────────────────────────────────────────
        Route::prefix('tahfidz')->group(function () {
            Route::get('/', [TahfidzController::class, 'index']);
            Route::get('progress', [TahfidzController::class, 'progress']);
        });

        // ── KALENDER KALDIK (Sprint 3) ────────────────────────────────────
        Route::get('kalender', [KalenderController::class, 'index']);

        // ── ACADEMIC SUMMARY (Sprint 3) ───────────────────────────────────
        Route::prefix('academic')->group(function () {
            Route::get('summary', [AcademicController::class, 'summary']);
            Route::get('rewards', [AcademicController::class, 'rewards']);
        });

        // ── NOTIFICATIONS — mark-all-read (Sprint 3) ──────────────────────
        Route::patch('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    });
});

// ── AUTHENTICATED ─────────────────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'organization.context'])->get('/user', function (Request $request) {
    return $request->user();
});

// ── LJK SMART SCANNER (mobile guru) ─────────────────────────────────────────
Route::middleware(['auth:sanctum', 'organization.context'])->prefix('mobile/v1/ljk')->name('mobile.ljk.')->group(function () {
    Route::get('exams', [MobileLjkController::class, 'exams'])->name('exams');
    Route::get('exams/{exam}', [MobileLjkController::class, 'examDetail'])->name('exam.detail');
    Route::post('exams/{exam}/scans', [MobileLjkController::class, 'scan'])->name('scan');
    Route::get('scans/{scan}', [MobileLjkController::class, 'scanDetail'])->name('scan.detail');
    Route::patch('scans/{scan}/answers/{answer}', [MobileLjkController::class, 'correct'])->name('correct');
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
