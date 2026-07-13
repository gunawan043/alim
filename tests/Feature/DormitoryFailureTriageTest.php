<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 1.1 — Triage 500s from the dormitory smoke test.
 * Captures exception messages with $this->withoutExceptionHandling().
 */
class DormitoryFailureTriageTest extends TestCase
{
    private static $bootstrapped = false;
    private static $user = null;
    private static $userId = null;
    private static $dormId = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$bootstrapped) {
            $userId = DB::table('users')->value('id');
            self::$userId = $userId ? (string) $userId : null;
            try {
                self::$user = User::find($userId);
                if (self::$user && method_exists(self::$user, 'assignRole')) {
                    self::$user->assignRole('super_admin');
                }
            } catch (\Throwable $e) { /* ignore */ }
            self::$bootstrapped = true;
        }
        if (self::$user) $this->actingAs(self::$user);
    }

    /**
     * Provide representative failing URLs from the smoke output,
     * exercise them with full exception capture.
     */
    public function testTriageFiveHundredRoutes(): void
    {
        $urls = [
            ['GET',  'broadcasting/auth'],
            ['POST', 'broadcasting/auth'],
            ['GET',  'peserta-didik/mutasi'],
            ['GET',  'poin-pelanggaran'],
            ['GET',  'sarpras/gedung'],
            ['POST', 'sarpras/gedung'],
            ['GET',  'sarpras/gedung/create'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/asrama'],
            ['POST', '91ddf450-55c2-47a1-a1fa-755097688dce/asrama'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/asrama/api/rooms'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/asrama/api/wings'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/asrama/create'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/absensi-gtk/izin'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/boarding-policies'],
            ['POST', '91ddf450-55c2-47a1-a1fa-755097688dce/boarding-policies'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/calendar/visit'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/dormitory-master'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/dormitory-master/create'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/jenjang-karir/mutasi-rotasi'],
            ['POST', '91ddf450-55c2-47a1-a1fa-755097688dce/jenjang-karir/mutasi-rotasi'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/kehadiran/cuti-izin'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/peraturan/violation'],
            ['POST', '91ddf450-55c2-47a1-a1fa-755097688dce/peraturan/violation'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/uks/health-permits'],
            ['POST', '91ddf450-55c2-47a1-a1fa-755097688dce/uks/health-permits'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/violation-points'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/violation-points/create'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/violation-points/dashboard'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/violation-points/export-pdf'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/violation-points/find-student'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/violation-points/recap'],
            ['GET',  '91ddf450-55c2-47a1-a1fa-755097688dce/wali/dashboard'],
            ['POST', '91ddf450-55c2-47a1-a1fa-755097688dce/wali/request-permit'],
            ['POST', 'api/mobile/v1/dormitory/permit'],
            ['POST', 'api/mobile/v1/wali-santri/link'],
            ['POST', 'api/mobile/v1/wali-santri/request'],
            ['GET',  'api/mobile/v1/wali-santri/requests'],
        ];

        $report = [];
        foreach ($urls as [$method, $url]) {
            $this->withoutExceptionHandling();
            try {
                $resp = match ($method) {
                    'GET' => $this->get($url),
                    'POST' => $this->post($url, []),
                    default => null,
                };
                $report[] = [
                    'method' => $method, 'url' => $url,
                    'status' => $resp ? $resp->getStatusCode() : 0,
                    'exception' => null, 'message' => null,
                ];
            } catch (\Throwable $e) {
                $report[] = [
                    'method' => $method, 'url' => $url,
                    'status' => 500,
                    'exception' => get_class($e),
                    'message' => substr($e->getMessage(), 0, 220),
                ];
            }
        }

        file_put_contents('/tmp/phase1_triage.json', json_encode($report, JSON_PRETTY_PRINT));
        $this->assertTrue(true);
    }
}