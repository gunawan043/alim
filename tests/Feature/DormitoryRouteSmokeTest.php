<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 1 — Systematic Dormitory smoke test.
 * RULE: collect failures, do NOT fix.
 *
 * Iterates every dormitory-scoped route, hits it with valid auth,
 * captures response code + exception. Emits a structured JSON report
 * to /tmp/phase1_smoke.json for downstream table rendering.
 */
class DormitoryRouteSmokeTest extends TestCase
{
    private static $bootstrapped = false;

    private static $userId = null;

    private static $dormId = null;

    private static $wingId = null;

    private static $roomId = null;

    private static $permitId = null;

    private static $visitId = null;

    private static $violationId = null;

    private static $residentId = null;

    private static $postId = null;

    private static $policyId = null;

    private static $studentId = null;

    private static $moveId = null;

    private static $itemId = null;

    private static $idColMap = [];

    private static $user = null;

    protected function setUp(): void
    {
        parent::setUp();
        if (! self::$bootstrapped) {
            $this->bootstrapFixtures();
            self::$bootstrapped = true;
        }
        // Authenticate as the smoke user so web routes don't bounce to /login.
        $this->actingAs(self::$user);
    }

    private function columnType(string $table, string $col): ?string
    {
        try {
            $type = DB::connection()->selectOne(
                'SELECT DATA_TYPE AS t FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
                [$table, $col]
            );

            return $type->t ?? null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function bootstrapFixtures(): void
    {
        $required = ['users', 'dormitories'];
        foreach ($required as $t) {
            if (! DB::getSchemaBuilder()->hasTable($t)) {
                $this->markTestSkipped("Required table `{$t}` missing — cannot smoke-test.");
            }
        }

        // Pick first existing user (or insert minimal).
        $userId = DB::table('users')->value('id');
        if (! $userId) {
            $userId = (string) Str::uuid();
            DB::table('users')->insert([
                'id' => $userId,
                'name' => 'smoke',
                'email' => 'smoke@local.test',
                'password' => bcrypt('x'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        self::$userId = (string) $userId;

        // Try to load the Eloquent user for actingAs — fall back to minimal record.
        try {
            self::$user = User::find($userId);
        } catch (\Throwable $e) {
            self::$user = new User;
            self::$user->id = $userId;
            self::$user->name = 'smoke';
            self::$user->email = 'smoke@local.test';
        }

        // Try to grant every role to bypass RoleMiddleware. Use the
        // authorization layer if present, otherwise skip.
        try {
            if (method_exists(self::$user, 'assignRole')) {
                // Assign super_admin so most role-gates open up.
                self::$user->assignRole('super_admin');
            }
        } catch (\Throwable $e) { /* no role system — fine */
        }

        // Detect ID column type per table.
        $map = [
            'dormUuid' => ['dormitories', 'dormId'],
            'wingUuid' => ['dormitory_wings', 'wingId'],
            'roomUuid' => ['dormitory_rooms', 'roomId'],
            'permitUuid' => ['dormitory_permits', 'permitId'],
            'visitUuid' => ['dormitory_visit_logs', 'visitId'],
            'violationUuid' => ['dormitory_violations', 'violationId'],
            'residentUuid' => ['dormitory_residents', 'residentId'],
            'postUuid' => ['dormitory_posts', 'postId'],
            'moveUuid' => ['dormitory_room_moves', 'moveId'],
            'itemUuid' => ['dormitory_inventories', 'itemId'],
        ];
        foreach ($map as $key => [$tbl, $prop]) {
            $uuidType = $this->columnType($tbl, 'uuid');
            if ($uuidType) {
                $val = DB::table($tbl)->value('uuid');
                self::${$prop} = $val !== null ? (string) $val : null;
                self::$idColMap[$prop] = 'uuid';
            } else {
                $val = DB::table($tbl)->value('id');
                self::${$prop} = $val !== null ? (string) $val : null;
                self::$idColMap[$prop] = 'id';
            }
        }
        self::$policyId = null;
        try {
            self::$policyId = DB::table('dormitory_policies')->value('id');
        } catch (\Throwable $e) {
        }
        if (self::$policyId === null) {
            try {
                self::$policyId = DB::table('boarding_policies')->value('id');
            } catch (\Throwable $e) {
            }
        }
        if (self::$policyId !== null) {
            self::$policyId = (string) self::$policyId;
        }

        // Student id — use resident.student_id since residents/students align.
        try {
            self::$studentId = (string) (DB::table('dormitory_residents')->value('student_id') ?? '');
        } catch (\Throwable $e) {
            self::$studentId = null;
        }
        if (! self::$studentId) {
            try {
                self::$studentId = (string) (DB::table('students')->value('id') ?? '');
            } catch (\Throwable $e) {
                self::$studentId = null;
            }
        }
    }

    private function substitutePlaceholders(string $uri): string
    {
        $map = [
            '{userId}' => self::$userId,
            '{asramaUuid}' => self::$dormId,
            '{wingUuid}' => self::$wingId,
            '{roomUuid}' => self::$roomId,
            '{permitUuid}' => self::$permitId,
            '{visitUuid}' => self::$visitId,
            '{violationUuid}' => self::$violationId,
            '{residentUuid}' => self::$residentId,
            '{postUuid}' => self::$postId,
            '{moveUuid}' => self::$moveId,
            '{itemUuid}' => self::$itemId,
            '{uuid}' => self::$policyId,
            '{id}' => self::$policyId,
            '{studentUuid}' => self::$studentId,
            '{studentId}' => self::$studentId,
            '{santriUuid}' => self::$studentId,
            '{mahromUuid}' => null,
            '{session}' => 'default',
            '{token}' => 'smoke-token',
        ];
        $out = $uri;
        foreach ($map as $k => $v) {
            if ($v === null) {
                continue;
            }
            $out = str_replace($k, $v, $out);
        }
        if (preg_match('/\{[a-zA-Z0-9_]+\}/', $out)) {
            return '';
        }

        return $out;
    }

    private function record(string $method, string $uri, int $status, ?string $exceptionClass, ?string $exceptionMessage, ?string $responseSnippet): array
    {
        return compact('method', 'uri', 'status', 'exceptionClass', 'exceptionMessage', 'responseSnippet');
    }

    public function test_smoke_all_dormitory_routes(): void
    {
        $routes = json_decode(file_get_contents('/tmp/routes_dorm.json'), true);
        $this->assertNotEmpty($routes, 'Dormitory route list is empty — did filter script run?');

        $report = [];
        foreach ($routes as $r) {
            $method = strtoupper($r['method']);
            $uri = $this->substitutePlaceholders($r['uri']);

            if ($uri === '') {
                $report[] = $this->record($method, $r['uri'], 0, 'SkippedUnboundParam', null, null);

                continue;
            }

            $exceptionClass = null;
            $exceptionMessage = null;
            $status = 0;
            $responseSnippet = null;

            try {
                $resp = match ($method) {
                    'GET' => $this->get($uri),
                    'POST' => $this->post($uri, []),
                    'PUT' => $this->put($uri, []),
                    'PATCH' => $this->patch($uri, []),
                    'DELETE' => $this->delete($uri, []),
                    default => null,
                };
                if ($resp) {
                    $status = $resp->getStatusCode();
                    $content = $resp->getContent();
                    if (strlen($content) > 0 && strlen($content) < 200000) {
                        $responseSnippet = substr(strip_tags($content), 0, 240);
                    }
                }
            } catch (\Throwable $e) {
                $exceptionClass = get_class($e);
                $exceptionMessage = $e->getMessage();
            }

            $report[] = $this->record($method, $uri, $status, $exceptionClass, $exceptionMessage, $responseSnippet);
        }

        file_put_contents('/tmp/phase1_smoke.json', json_encode($report, JSON_PRETTY_PRINT));

        $ok = 0;
        $fail = 0;
        $skip = 0;
        foreach ($report as $row) {
            if ($row['status'] === 0 && $row['exceptionClass'] === 'SkippedUnboundParam') {
                $skip++;
            } elseif ($row['status'] >= 200 && $row['status'] < 400) {
                $ok++;
            } else {
                $fail++;
            }
        }
        echo "\n[Phase1 Smoke] OK=$ok FAIL=$fail SKIP=$skip / TOTAL=".count($report)."\n";
        $this->assertTrue(true);
    }
}
