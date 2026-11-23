<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ApprovalCommand;
use App\Models\ConnectActorIdentity;
use App\Models\Dormitory;
use App\Models\DormitoryPermit;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryStaffAssignment;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Boarding\LeaveWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * POST /api/connect/v1/approval-commands
 *
 * Memastikan command persetujuan dari ALIM Connect: aktor tidak dipercaya dari
 * payload, hanya Kepala Asrama aktif yang berwenang, idempoten, dan tidak
 * pernah meninggalkan perubahan parsial.
 */
class ConnectApprovalCommandTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'test-connect-approval-key';

    private const API_HEADER = 'X-ALIM-CONNECT-APPROVAL-KEY';

    private School $school;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private AcademicYear $academicYear;

    private User $admin;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.connect_approval.api_key' => self::API_KEY,
            'services.connect_approval.scopes' => ['approval:command'],
        ]);

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Connect', 'code' => 'WU-CONNECT',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId,
            'npsn' => '88776655',
            'name' => 'Sekolah Connect',
            'boarding_type' => 'boarding',
        ]);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId,
            'school_id' => $this->school->id,
            'code' => 'ASR-CONNECT',
            'name' => 'Asrama Connect',
            'gender' => 'putra',
        ]);

        $this->academicYear = AcademicYear::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);

        $wing = DormitoryWing::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'code' => 'W1',
            'name' => 'Sayap 1',
            'gender' => 'putra',
        ]);

        $this->room = DormitoryRoom::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id,
            'code' => 'R1',
            'name' => 'Kamar 1',
            'gender' => 'putra',
            'capacity' => 20,
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'id' => (string) Str::uuid(),
            'school_id' => $this->school->id,
            'nisn' => '9988776655',
            'name' => 'Santri Connect',
            'gender' => 'L',
            'status' => 'active',
            'boarding_status' => 'boarding',
        ]);

        DormitoryResident::create([
            'id' => (string) Str::uuid(),
            'dormitory_id' => $this->dormitory->id,
            'student_id' => $this->student->id,
            'academic_year_id' => $this->academicYear->id,
            'room_id' => $this->room->id,
            'bed_number' => 1,
            'check_in_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create(['name' => 'Admin Connect', 'is_active' => true]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    private function headers(array $overrides = []): array
    {
        return array_merge([self::API_HEADER => self::API_KEY], $overrides);
    }

    private function makePermit(string $status = DormitoryPermit::STATUS_PENDING): DormitoryPermit
    {
        return DormitoryPermit::create([
            'id' => (string) Str::uuid(),
            'student_id' => $this->student->id,
            'dormitory_id' => $this->dormitory->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'permit_type' => 'pulang',
            'destination' => 'Rumah wali',
            'departure_datetime' => now()->addDay()->setTime(8, 0),
            'expected_return_datetime' => now()->addDays(4)->setTime(8, 0),
            'status' => $status,
            'created_by' => $this->admin->id,
        ]);
    }

    private function mapActor(User $user, string $identity = '628111222333'): ConnectActorIdentity
    {
        return ConnectActorIdentity::create([
            'channel' => 'whatsapp',
            'external_identity' => ConnectActorIdentity::normalizeIdentity($identity),
            'user_id' => $user->id,
            'display_name' => $user->name,
            'is_active' => true,
            'verified_at' => now(),
        ]);
    }

    private function assignHead(User $user, ?Dormitory $dormitory = null): DormitoryStaffAssignment
    {
        return DormitoryStaffAssignment::create([
            'user_id' => $user->id,
            'dormitory_id' => ($dormitory ?? $this->dormitory)->id,
            'start_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
            'position' => 'kepala_asrama',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'command_id' => (string) Str::uuid(),
            'request_id' => $this->makePermit()->id,
            'decision' => 'approved',
            'approved_duration_days' => 2,
            'actor_external_id' => '628111222333',
            'source' => 'whatsapp',
            'external_message_id' => 'wamid.'.Str::random(16),
            'message_text' => 'SETUJU 2 HARI',
        ], $overrides);
    }

    // ── 1. Kepala Asrama berwenang menyetujui ─────────────────────────────

    public function test_kepala_asrama_berwenang_dapat_menyetujui(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->dormitory->update(['head_id' => $head->id]);
        $this->mapActor($head);

        $payload = $this->payload();

        $response = $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.outcome', 'applied')
            ->assertJsonPath('data.decision', 'approved')
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.actor.core_person_id', $head->id)
            ->assertJsonPath('data.approved_duration_days', 2);

        $permit = DormitoryPermit::findOrFail($payload['request_id']);

        $this->assertSame('approved', $permit->status);
        $this->assertSame($head->id, $permit->approved_by);
        $this->assertSame(
            $permit->departure_datetime->copy()->addDays(2)->toDateTimeString(),
            $permit->expected_return_datetime->toDateTimeString()
        );
    }

    // ── 2. Kepala Asrama berwenang menolak ────────────────────────────────

    public function test_kepala_asrama_berwenang_dapat_menolak(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $payload = $this->payload([
            'decision' => 'rejected',
            'approved_duration_days' => null,
            'message_text' => 'TOLAK',
        ]);

        $response = $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload);

        $response->assertOk()
            ->assertJsonPath('data.outcome', 'applied')
            ->assertJsonPath('data.status', 'rejected');

        $this->assertSame('rejected', DormitoryPermit::findOrFail($payload['request_id'])->status);
    }

    // ── 3. Aktor tanpa penugasan aktif ────────────────────────────────────

    public function test_aktor_tanpa_penugasan_aktif_ditolak(): void
    {
        $actor = User::factory()->create(['is_active' => true]);
        $this->mapActor($actor);

        $payload = $this->payload();

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload)
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'actor_not_authorized');

        $this->assertSame('pending', DormitoryPermit::findOrFail($payload['request_id'])->status);
        $this->assertSame(0, ApprovalCommand::count());
    }

    // ── 4. Aktor dari asrama berbeda ──────────────────────────────────────

    public function test_aktor_asrama_berbeda_ditolak(): void
    {
        $otherDormitory = Dormitory::create([
            'work_unit_id' => $this->dormitory->work_unit_id,
            'school_id' => $this->school->id,
            'code' => 'ASR-LAIN',
            'name' => 'Asrama Lain',
            'gender' => 'putra',
        ]);

        $actor = User::factory()->create(['is_active' => true]);
        $this->assignHead($actor, $otherDormitory);
        $this->mapActor($actor);

        $payload = $this->payload();

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload)
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'actor_not_authorized');

        $this->assertSame('pending', DormitoryPermit::findOrFail($payload['request_id'])->status);
    }

    // ── 5. Permohonan sudah diputuskan ────────────────────────────────────

    public function test_permohonan_sudah_diputuskan_ditolak(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $permit = $this->makePermit(DormitoryPermit::STATUS_APPROVED);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload(['request_id' => $permit->id]))
            ->assertStatus(409)
            ->assertJsonPath('errors.code', 'permit_not_decidable')
            ->assertJsonPath('errors.context.status', 'approved');
    }

    // ── 6. Durasi tidak valid ─────────────────────────────────────────────

    public function test_durasi_tidak_valid_ditolak(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $permit = $this->makePermit();

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'request_id' => $permit->id,
                'approved_duration_days' => 0,
            ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.code', 'validation_failed');

        $this->assertSame('pending', $permit->fresh()->status);
    }

    // ── 7. command_id duplikat ────────────────────────────────────────────

    public function test_command_id_duplikat_mengembalikan_hasil_yang_sama(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $payload = $this->payload();

        $first = $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload);
        $first->assertOk()->assertJsonPath('data.duplicate', false);

        $second = $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload);

        $second->assertOk()
            ->assertJsonPath('data.outcome', 'duplicate')
            ->assertJsonPath('data.duplicate', true)
            ->assertJsonPath('data.status', 'approved');

        $this->assertSame(1, ApprovalCommand::count());
    }

    // ── 8. command_id dipakai ulang dengan payload berbeda ────────────────

    public function test_command_id_dipakai_ulang_dengan_payload_berbeda_ditolak(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $payload = $this->payload();

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload)
            ->assertOk();

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', array_merge($payload, [
                'approved_duration_days' => 5,
            ]))
            ->assertStatus(409)
            ->assertJsonPath('errors.code', 'command_id_reused');
    }

    // ── 9. Pesan eksternal dipakai ulang oleh command lain ────────────────

    public function test_pesan_eksternal_duplikat_untuk_command_lain_ditolak(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $first = $this->payload();
        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $first)
            ->assertOk();

        $secondPermit = $this->makePermit();

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'request_id' => $secondPermit->id,
                'external_message_id' => $first['external_message_id'],
            ]))
            ->assertStatus(409)
            ->assertJsonPath('errors.code', 'external_message_replayed');

        $this->assertSame('pending', $secondPermit->fresh()->status);
    }

    // ── 10. Kegagalan transaksi tidak meninggalkan perubahan parsial ──────

    public function test_kegagalan_transaksi_tidak_meninggalkan_perubahan_parsial(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $permit = $this->makePermit();
        $originalReturn = $permit->expected_return_datetime->toDateTimeString();

        $this->mock(LeaveWorkflowService::class, function ($mock): void {
            $mock->shouldReceive('approve')->once()->andThrow(new \RuntimeException('forced failure'));
        });

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'request_id' => $permit->id,
            ]))
            ->assertStatus(500);

        $fresh = $permit->fresh();

        $this->assertSame('pending', $fresh->status);
        $this->assertSame($originalReturn, $fresh->expected_return_datetime->toDateTimeString());
        $this->assertSame(0, ApprovalCommand::count());
    }

    // ── 11. Audit tercatat ────────────────────────────────────────────────

    public function test_audit_keputusan_tercatat(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $payload = $this->payload();

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload)
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'DORMITORY_PERMIT_APPROVED',
            'table_name' => 'dormitory_permits',
            'record_id' => $payload['request_id'],
            'user_id' => $head->id,
        ]);

        $command = ApprovalCommand::firstOrFail();

        $this->assertSame($head->id, $command->actor_user_id);
        $this->assertSame('628111222333', $command->actor_external_identity);
        $this->assertSame('whatsapp', $command->source);
        $this->assertSame($payload['external_message_id'], $command->external_message_id);
        $this->assertSame('SETUJU 2 HARI', $command->message_text);
        $this->assertNotNull($command->processed_at);
    }

    // ── 12. API key tidak valid ───────────────────────────────────────────

    public function test_api_key_tidak_valid_ditolak(): void
    {
        $this->postJson('/api/connect/v1/approval-commands', $this->payload(), [
            self::API_HEADER => 'salah',
        ])->assertStatus(401);
    }

    // ── 13. Key tanpa cakupan command ─────────────────────────────────────

    public function test_api_key_tanpa_cakupan_command_ditolak(): void
    {
        config(['services.connect_approval.scopes' => ['organization:read']]);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload())
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'insufficient_scope');
    }

    // ── 14. Identitas pengirim belum terpetakan ───────────────────────────

    public function test_identitas_belum_terpetakan_ditolak(): void
    {
        $payload = $this->payload(['actor_external_id' => '628999000111']);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload)
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'actor_identity_unmapped');
    }

    // ── 15. actor_core_person_id dari payload tidak dipercaya ─────────────

    public function test_actor_core_person_id_payload_tidak_dipercaya(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);

        // Aktor nyata: user tanpa penugasan. Payload mengaku sebagai kepala.
        $actor = User::factory()->create(['is_active' => true]);
        $this->mapActor($actor);

        $payload = $this->payload(['actor_core_person_id' => $head->id]);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $payload)
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'actor_not_authorized');

        $this->assertSame('pending', DormitoryPermit::findOrFail($payload['request_id'])->status);
    }

    // ── 16. Permohonan tidak ditemukan ────────────────────────────────────

    public function test_permohonan_tidak_ditemukan(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'request_id' => (string) Str::uuid(),
            ]))
            ->assertStatus(404)
            ->assertJsonPath('errors.code', 'permit_not_found');
    }

    // ── 17. Keputusan di luar kontrak ─────────────────────────────────────

    public function test_keputusan_di_luar_kontrak_ditolak(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'decision' => 'maybe',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.code', 'validation_failed');
    }

    public function test_needs_clarification_keeps_permit_pending(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);
        $permit = $this->makePermit();

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'request_id' => $permit->id,
                'decision' => 'needs_clarification',
                'approved_duration_days' => null,
            ]))
            ->assertOk()
            ->assertJsonPath('data.outcome', 'needs_clarification')
            ->assertJsonPath('data.status', DormitoryPermit::STATUS_PENDING);

        $this->assertSame('needs_clarification', $permit->fresh()->secondary_status);
    }

    public function test_expired_permit_can_be_marked_expired(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);
        $permit = $this->makePermit();
        $permit->update([
            'departure_datetime' => now()->subDays(4),
            'expected_return_datetime' => now()->subDay(),
        ]);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'request_id' => $permit->id,
                'decision' => 'expired',
                'approved_duration_days' => null,
            ]))
            ->assertOk()
            ->assertJsonPath('data.outcome', 'expired');

        $this->assertSame('expired', $permit->fresh()->secondary_status);
        $this->assertSame(DormitoryPermit::STATUS_PENDING, $permit->fresh()->status);
    }

    public function test_cancelled_permit_can_be_marked_cancelled(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);
        $permit = $this->makePermit();

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'request_id' => $permit->id,
                'decision' => 'cancelled',
                'approved_duration_days' => null,
            ]))
            ->assertOk()
            ->assertJsonPath('data.outcome', 'cancelled');

        $this->assertSame('cancelled', $permit->fresh()->secondary_status);
    }

    public function test_inactive_dormitory_is_rejected(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);
        $this->dormitory->update(['is_active' => false]);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload())
            ->assertStatus(403)
            ->assertJsonPath('errors.code', 'actor_not_authorized');
    }

    public function test_message_text_is_truncated_for_storage_and_response(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $longText = str_repeat('A', 800);
        $response = $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'message_text' => $longText,
            ]));

        $response->assertOk();
        $this->assertSame(500, mb_strlen(ApprovalCommand::firstOrFail()->message_text));
        $this->assertSame(500, mb_strlen($response->json('data.message_text')));
    }

    public function test_guard_is_restored_after_command(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $previous = User::factory()->create(['is_active' => true]);
        $this->actingAs($previous);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload())
            ->assertOk();

        $this->assertSame($previous->id, Auth::id());
    }

    // ── Header kontrak: Idempotency-Key ───────────────────────────────────

    public function test_idempotency_key_sama_dengan_command_id_diterima(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $payload = $this->payload();
        $key = $payload['command_id'];

        $this->withHeaders($this->headers([
            'Idempotency-Key' => $key,
            'X-ALIM-API-Version' => '1',
        ]))->postJson('/api/connect/v1/approval-commands', $payload)
            ->assertOk()
            ->assertHeader('Idempotency-Key', $key)
            ->assertJsonPath('data.outcome', 'applied');

        $command = ApprovalCommand::query()->where('command_id', $key)->firstOrFail();
        $this->assertSame($key, $command->idempotency_key);
        $this->assertSame('1', $command->api_version);
    }

    public function test_replay_dengan_idempotency_key_mengembalikan_duplikat(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $payload = $this->payload();
        $headers = $this->headers([
            'Idempotency-Key' => $payload['command_id'],
            'X-ALIM-API-Version' => '1',
        ]);

        $first = $this->withHeaders($headers)->postJson('/api/connect/v1/approval-commands', $payload)->assertOk();

        $second = $this->withHeaders($headers)->postJson('/api/connect/v1/approval-commands', $payload);

        $second->assertOk()
            ->assertJsonPath('data.outcome', 'duplicate')
            ->assertJsonPath('data.duplicate', true)
            ->assertJsonPath('data.status', $first->json('data.status'))
            ->assertJsonPath('data.expected_return_datetime', $first->json('data.expected_return_datetime'));

        $this->assertSame(1, ApprovalCommand::query()->count());
    }

    public function test_idempotency_key_berbeda_dari_command_id_ditolak(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $payload = $this->payload();

        $this->withHeaders($this->headers(['Idempotency-Key' => (string) Str::uuid()]))
            ->postJson('/api/connect/v1/approval-commands', $payload)
            ->assertStatus(422)
            ->assertJsonPath('errors.code', 'idempotency_key_mismatch');

        $this->assertSame(0, ApprovalCommand::query()->count());
        $this->assertSame('pending', DormitoryPermit::findOrFail($payload['request_id'])->status);
    }

    public function test_tanpa_idempotency_key_tetap_idempoten_lewat_command_id(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $payload = $this->payload();

        $this->withHeaders($this->headers())->postJson('/api/connect/v1/approval-commands', $payload)->assertOk();

        $this->withHeaders($this->headers())->postJson('/api/connect/v1/approval-commands', $payload)
            ->assertOk()
            ->assertJsonPath('data.duplicate', true);
    }

    // ── Header kontrak: X-ALIM-API-Version ────────────────────────────────

    public function test_versi_api_tidak_dikenal_ditolak_422_bukan_500(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $this->withHeaders($this->headers(['X-ALIM-API-Version' => '2']))
            ->postJson('/api/connect/v1/approval-commands', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('errors.code', 'unsupported_api_version');
    }

    public function test_tanpa_header_versi_tetap_diterima(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload())
            ->assertOk();
    }

    // ── Header kontrak: content-type ──────────────────────────────────────

    public function test_content_type_selain_json_ditolak_415(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $this->call(
            'POST',
            '/api/connect/v1/approval-commands',
            [],
            [],
            [],
            array_merge($this->transformHeadersToServerVars($this->headers()), [
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_ACCEPT' => 'application/json',
            ]),
            http_build_query($this->payload())
        )->assertStatus(415)
            ->assertJsonPath('errors.code', 'unsupported_media_type');
    }

    // ── Error mapping: kontrak §7 ────────────────────────────────────────

    public function test_permit_tidak_ditemukan_tanpa_membocorkan_struktur(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'request_id' => (string) Str::uuid(),
            ]))
            ->assertStatus(404)
            ->assertJsonPath('errors.code', 'permit_not_found')
            ->assertJsonPath('data', null);
    }

    public function test_kegagalan_tak_terduga_dibalas_5xx_bukan_4xx(): void
    {
        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $this->mock(LeaveWorkflowService::class, function ($mock) {
            $mock->shouldReceive('approve')->andThrow(new \RuntimeException('db down'));
        });

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload())
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.code', 'core_error');
    }

    public function test_max_duration_days_dari_config_dihormati(): void
    {
        config(['services.connect_approval.max_duration_days' => 3]);

        $head = User::factory()->create(['is_active' => true]);
        $this->assignHead($head);
        $this->mapActor($head);

        $this->withHeaders($this->headers())
            ->postJson('/api/connect/v1/approval-commands', $this->payload([
                'approved_duration_days' => 4,
            ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.code', 'validation_failed');
    }
}
