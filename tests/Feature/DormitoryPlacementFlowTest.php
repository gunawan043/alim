<?php

namespace Tests\Feature;

use App\Http\Controllers\DormitoryRoomApiController;
use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\DormitoryResident;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use App\Models\School;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Alur penempatan kamar ala-Dapodik: tarik data santri belum berkamar,
 * tempatkan ke kamar (validasi kapasitas), keluarkan dari kamar.
 */
class DormitoryPlacementFlowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private Dormitory $dormitory;

    private DormitoryRoom $room;

    private Student $santriPutra1;

    private Student $santriPutra2;

    private Student $santriPutri;

    private string $ayId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('positions')) {
            Schema::create('positions', function ($table) {
                $table->uuid('id')->primary();
                $table->string('name')->nullable();
                $table->timestamps();
            });
        }

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Penempatan', 'code' => 'UU-PLACE',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create(['work_unit_id' => $workUnitId, 'npsn' => '22334455', 'name' => 'Sekolah Penempatan', 'boarding_type' => 'boarding']);

        $this->dormitory = Dormitory::create([
            'work_unit_id' => $workUnitId, 'school_id' => $this->school->id,
            'code' => 'ASR-PA', 'name' => 'Asrama Putra', 'gender' => 'putra',
        ]);

        $this->ayId = (string) Str::uuid();
        AcademicYear::create([
            'id' => $this->ayId, 'name' => '2026/2027', 'semester' => 'ganjil',
            'is_active' => true, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $wing = DormitoryWing::create([
            'id' => (string) Str::uuid(), 'dormitory_id' => $this->dormitory->id,
            'code' => 'W1', 'name' => 'Sayap 1', 'gender' => 'putra',
        ]);

        $this->room = DormitoryRoom::create([
            'id' => (string) Str::uuid(), 'dormitory_id' => $this->dormitory->id,
            'wing_id' => $wing->id, 'code' => 'R1', 'name' => 'Kamar 1',
            'gender' => 'putra', 'capacity' => 2,
        ]);

        $this->santriPutra1 = Student::create(['id' => (string) Str::uuid(), 'school_id' => $this->school->id, 'nisn' => '1110000001', 'name' => 'Santri Putra 1', 'gender' => 'L', 'status' => 'active', 'boarding_status' => 'boarding']);
        $this->santriPutra2 = Student::create(['id' => (string) Str::uuid(), 'school_id' => $this->school->id, 'nisn' => '1110000002', 'name' => 'Santri Putra 2', 'gender' => 'L', 'status' => 'active', 'boarding_status' => 'boarding']);
        $this->santriPutri = Student::create(['id' => (string) Str::uuid(), 'school_id' => $this->school->id, 'nisn' => '1110000003', 'name' => 'Santri Putri', 'gender' => 'P', 'status' => 'active', 'boarding_status' => 'boarding']);
    }

    public function test_tarik_data_hanya_santri_belum_berkamar_sesuai_gender(): void
    {
        $request = Request::create('/x', 'GET');
        $response = app(DormitoryRoomApiController::class)->pullableStudents($request, 'u1', $this->dormitory->id);
        $data = json_decode($response->getContent(), true);

        $names = collect($data['students'])->pluck('name')->all();
        $this->assertContains('Santri Putra 1', $names);
        $this->assertContains('Santri Putra 2', $names);
        $this->assertNotContains('Santri Putri', $names, 'Gender tidak sesuai tidak ikut ditarik.');

        // Setelah ditempatkan, tidak ikut tertarik lagi.
        app(DormitoryRoomApiController::class)->assignStudents(
            Request::create('/x', 'POST', ['student_ids' => [$this->santriPutra1->id]]),
            'u1', $this->dormitory->id, $this->room->id
        );

        $data2 = json_decode(app(DormitoryRoomApiController::class)->pullableStudents(
            Request::create('/x', 'GET'), 'u1', $this->dormitory->id
        )->getContent(), true);

        $this->assertNotContains('Santri Putra 1', collect($data2['students'])->pluck('name')->all(), 'Yang sudah berkamar tidak ditarik lagi.');
    }

    public function test_penempatan_validasi_kapasitas_dan_nomor_ranjang(): void
    {
        $assign = fn (array $ids) => app(DormitoryRoomApiController::class)->assignStudents(
            Request::create('/x', 'POST', ['student_ids' => $ids]),
            'u1', $this->dormitory->id, $this->room->id
        );

        // Isi penuh (kapasitas 2).
        $ok = json_decode($assign([$this->santriPutra1->id, $this->santriPutra2->id])->getContent(), true);
        $this->assertTrue($ok['success']);
        $this->assertSame(2, $ok['placed']);

        $residents = DormitoryResident::where('room_id', $this->room->id)->where('is_active', true)->orderBy('bed_number')->get();
        $this->assertSame([1, 2], $residents->pluck('bed_number')->all(), 'Nomor ranjang otomatis 1..n.');

        // Melebihi kapasitas → 422.
        $santri3 = Student::create(['id' => (string) Str::uuid(), 'school_id' => $this->school->id, 'nisn' => '1110000004', 'name' => 'Santri Putra 3', 'gender' => 'L', 'status' => 'active', 'boarding_status' => 'boarding']);
        $over = $assign([$santri3->id]);
        $this->assertSame(422, $over->getStatusCode());
        $this->assertStringContainsString('Kapasitas', json_decode($over->getContent(), true)['message']);
    }

    public function test_keluarkan_penghuni_dari_kamar(): void
    {
        app(DormitoryRoomApiController::class)->assignStudents(
            Request::create('/x', 'POST', ['student_ids' => [$this->santriPutra1->id]]),
            'u1', $this->dormitory->id, $this->room->id
        );

        $resident = DormitoryResident::where('room_id', $this->room->id)->where('is_active', true)->firstOrFail();

        $response = app(DormitoryRoomApiController::class)->removeResident(
            Request::create('/x', 'POST', ['resident_id' => $resident->id]),
            'u1', $this->dormitory->id, $this->room->id
        );

        $this->assertTrue(json_decode($response->getContent(), true)['success']);

        $resident->refresh();
        $this->assertNull($resident->room_id, 'Penghuni keluar dari kamar.');
        $this->assertNull($resident->bed_number);
        $this->assertTrue((bool) $resident->is_active, 'Tetap aktif di asrama (siap ditempatkan ulang).');

        // Muncul kembali di daftar tarik data → dapat ditempatkan ulang.
        $data = json_decode(app(DormitoryRoomApiController::class)->pullableStudents(
            Request::create('/x', 'GET'), 'u1', $this->dormitory->id
        )->getContent(), true);
        $this->assertContains('Santri Putra 1', collect($data['students'])->pluck('name')->all());

        $again = json_decode(app(DormitoryRoomApiController::class)->assignStudents(
            Request::create('/x', 'POST', ['student_ids' => [$this->santriPutra1->id]]),
            'u1', $this->dormitory->id, $this->room->id
        )->getContent(), true);
        $this->assertTrue($again['success']);
        $this->assertSame(1, $again['placed'], 'Penempatan ulang berhasil tanpa duplikasi record.');
    }

    public function test_penempatan_terkunci_satuan_pendidikan_dan_gender(): void
    {
        // Santri dari sekolah lain → ditolak.
        $workUnit2 = (string) Str::uuid();
        DB::table('work_units')->insert(['id' => $workUnit2, 'name' => 'Unit 2', 'code' => 'U2', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $school2 = School::create(['work_unit_id' => $workUnit2, 'npsn' => '99900011', 'name' => 'Sekolah Lain']);
        $luar = Student::create(['id' => (string) Str::uuid(), 'school_id' => $school2->id, 'nisn' => '2220000001', 'name' => 'Santri Sekolah Lain', 'gender' => 'L', 'status' => 'active', 'boarding_status' => 'boarding']);

        $res = app(DormitoryRoomApiController::class)->assignStudents(
            Request::create('/x', 'POST', ['student_ids' => [$luar->id]]),
            'u1', $this->dormitory->id, $this->room->id
        );
        $this->assertSame(422, $res->getStatusCode());
        $this->assertStringContainsString('satuan pendidikan lain', json_decode($res->getContent(), true)['message']);

        // Gender tidak sesuai → ditolak.
        $res2 = app(DormitoryRoomApiController::class)->assignStudents(
            Request::create('/x', 'POST', ['student_ids' => [$this->santriPutri->id]]),
            'u1', $this->dormitory->id, $this->room->id
        );
        $this->assertSame(422, $res2->getStatusCode());
    }

    public function test_kelola_status_boarding_dan_berhenti_mondok(): void
    {
        $controller = app(DormitoryRoomApiController::class);

        // Tandai non-boarding untuk santri sendiri.
        $res = $controller->updateBoardingStatus(
            Request::create('/x', 'POST', ['student_ids' => [$this->santriPutra1->id], 'status' => 'non_boarding']),
            'u1', $this->dormitory->id
        );
        $this->assertSame(1, json_decode($res->getContent(), true)['updated']);
        $this->assertSame('non_boarding', $this->santriPutra1->fresh()->boarding_status);

        // Kembalikan ke boarding lalu tempatkan.
        $controller->updateBoardingStatus(
            Request::create('/x', 'POST', ['student_ids' => [$this->santriPutra1->id], 'status' => 'boarding']),
            'u1', $this->dormitory->id
        );
        $controller->assignStudents(Request::create('/x', 'POST', ['student_ids' => [$this->santriPutra1->id]]), 'u1', $this->dormitory->id, $this->room->id);

        $resident = DormitoryResident::where('student_id', $this->santriPutra1->id)->where('is_active', true)->firstOrFail();

        // BERHENTI MONDOK → penghuni nonaktif, santri tetap aktif di sekolah.
        $out = $controller->checkoutResident(
            Request::create('/x', 'POST', ['reason' => 'Permintaan orang tua']),
            'u1', $this->dormitory->id, $resident->id
        );
        $this->assertTrue(json_decode($out->getContent(), true)['success']);

        $resident->refresh();
        $this->assertFalse((bool) $resident->is_active);
        $this->assertSame('Permintaan orang tua', $resident->check_out_reason);
        $this->assertSame('non_boarding', $this->santriPutra1->fresh()->boarding_status, 'Observer menyelaraskan status boarding.');
        $this->assertSame('active', $this->santriPutra1->fresh()->status, 'Santri tetap aktif di sekolah.');
    }

    public function test_mutasi_menutup_penghuni_asrama(): void
    {
        app(DormitoryRoomApiController::class)->assignStudents(
            Request::create('/x', 'POST', ['student_ids' => [$this->santriPutra2->id]]),
            'u1', $this->dormitory->id, $this->room->id
        );
        $resident = DormitoryResident::where('student_id', $this->santriPutra2->id)->where('is_active', true)->firstOrFail();

        // Mutasi keluar diproses listener → penghuni ditutup otomatis.
        app(\App\Listeners\CloseDormitoryResidentOnStudentExit::class)->handle(
            new \App\Events\StudentMutatedOut($this->santriPutra2, null, 'pindah_sekolah', now()->toDateString(), 'tester')
        );

        $resident->refresh();
        $this->assertFalse((bool) $resident->is_active, 'Penghuni ditutup saat mutasi.');
        $this->assertStringContainsString('Mutasi', (string) $resident->notes);
        $this->assertSame('non_boarding', $this->santriPutra2->fresh()->boarding_status);
    }

    public function test_mutasi_antar_kamar_butuh_persetujuan(): void
    {
        $controller = app(DormitoryRoomApiController::class);

        // Kamar tujuan.
        $wing2 = DormitoryWing::create(['id' => (string) Str::uuid(), 'dormitory_id' => $this->dormitory->id, 'code' => 'W2', 'name' => 'Sayap 2', 'gender' => 'putra']);
        $room2 = DormitoryRoom::create(['id' => (string) Str::uuid(), 'dormitory_id' => $this->dormitory->id, 'wing_id' => $wing2->id, 'code' => 'R2', 'name' => 'Kamar 2', 'gender' => 'putra', 'capacity' => 1]);

        $controller->assignStudents(Request::create('/x', 'POST', ['student_ids' => [$this->santriPutra1->id]]), 'u1', $this->dormitory->id, $this->room->id);
        $resident = DormitoryResident::where('student_id', $this->santriPutra1->id)->where('is_active', true)->firstOrFail();

        // Ajukan mutasi → pending.
        $req = $controller->requestMove(
            Request::create('/x', 'POST', ['to_room_id' => $room2->id, 'reason' => 'Rotasi kamar']),
            'u1', $this->dormitory->id, $resident->id
        );
        $this->assertTrue(json_decode($req->getContent(), true)['success']);

        $move = \App\Models\DormitoryRoomMove::where('student_id', $this->santriPutra1->id)->where('approval_status', 'pending')->firstOrFail();

        // Sebelum disetujui, penghuni belum pindah.
        $this->assertSame($this->room->id, $resident->fresh()->room_id);

        // Setujui → penghuni pindah + jejak approval.
        $ok = $controller->approveMove(Request::create('/x', 'POST'), 'u1', $this->dormitory->id, $move->id);
        $this->assertTrue(json_decode($ok->getContent(), true)['success']);
        $this->assertSame($room2->id, $resident->fresh()->room_id);
        $this->assertSame('approved', $move->fresh()->approval_status);

        // Tolak tidak memindahkan.
        $req2 = $controller->requestMove(Request::create('/x', 'POST', ['to_room_id' => $this->room->id]), 'u1', $this->dormitory->id, $resident->id);
        $move2 = \App\Models\DormitoryRoomMove::where('student_id', $this->santriPutra1->id)->where('approval_status', 'pending')->firstOrFail();
        $controller->rejectMove(Request::create('/x', 'POST', ['notes' => 'Tidak perlu']), 'u1', $this->dormitory->id, $move2->id);
        $this->assertSame($room2->id, $resident->fresh()->room_id, 'Ditolak → tetap di kamar semula.');
    }

    public function test_mutasi_masuk_menandai_boarding_dan_notifikasi_petugas(): void
    {
        $kepala = \App\Models\User::create(['name' => 'Kepala Asrama Uji', 'email' => 'kepala.mutasi@test.local', 'password' => bcrypt('x'), 'is_active' => true]);
        $this->dormitory->forceFill(['head_id' => $kepala->id])->save();

        $studentBaru = Student::create(['id' => (string) Str::uuid(), 'school_id' => $this->school->id, 'nisn' => '3330000001', 'name' => 'Santri Mutasi Masuk', 'gender' => 'L', 'status' => 'active', 'boarding_status' => null]);

        app(\App\Listeners\NotifyDormitoryOnStudentMutatedIn::class)->handle(
            new \App\Events\StudentMutatedIn($studentBaru, new \App\Models\StudentMutationIn, null, null, null, 'tester')
        );

        $this->assertSame('boarding', $studentBaru->fresh()->boarding_status, 'Sekolah boarding → otomatis ditandai boarding.');
        $this->assertTrue(
            DB::table('notifications_universal')->where('user_id', $kepala->id)->where('module', 'dormitory')->exists(),
            'Petugas asrama menerima notifikasi mutasi masuk.'
        );
    }

}
