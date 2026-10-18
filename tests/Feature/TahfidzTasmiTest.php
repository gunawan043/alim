<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Student;
use App\Models\TahfidzCertificate;
use App\Models\TahfidzTasmianParticipant;
use App\Models\TahfidzTasmianScore;
use App\Models\TahfidzTasmianSession;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TahfidzTasmiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private AcademicYear $year;

    private Student $student;

    private User $mustami;

    protected function setUp(): void
    {
        parent::setUp();

        $unit = WorkUnit::create([
            'name' => 'Sekolah Tasmi', 'code' => 'SCH-TASMI', 'type' => 'Unit Akademik', 'is_active' => true,
        ]);
        $this->school = School::create(['work_unit_id' => $unit->id, 'npsn' => '90909090', 'name' => 'Sekolah Tasmi']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $this->student = Student::create([
            'school_id' => $this->school->id, 'name' => 'Ahmad Tasmi', 'name_ar' => 'أحمد تسميع',
            'nis' => '999', 'nisn' => '999999', 'gender' => 'L', 'status' => 'active',
        ]);
        $mustami = User::create([
            'name' => 'Ustadz Mustami', 'email' => 'mustami.tasmi@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $this->admin = User::create([
            'name' => 'Super Admin Tasmi', 'email' => 'superadmin.tasmi@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $this->mustami = $mustami;
    }

    public function test_sesi_peserta_nilai_instrumen_dan_syahadah(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/tasmi", [
                'school_id' => $this->school->id,
                'academic_year_id' => $this->year->id,
                'session_name' => 'Tasmi’ Juz 30',
                'session_date' => now()->toDateString(),
                'session_time_start' => '08:00',
                'session_time_end' => '10:00',
                'location' => 'Aula Tahfidz',
                'session_type' => 'reguler',
                'status' => 'terjadwal',
            ])->assertRedirect();

        $session = TahfidzTasmianSession::firstOrFail();
        $this->assertSame($this->school->id, $session->school_id);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/tasmi/{$session->id}/participants", [
                'student_id' => $this->student->id,
                'juz_tasmi' => 30,
                'juz_detail' => '30',
                'materi_detail' => 'Juz 30 halaman 582–604',
                'mustami_id' => $this->mustami->id,
                'waktu_mulai' => '08:00',
                'waktu_selesai' => '08:30',
                'tempat' => 'Aula Tahfidz',
            ])->assertRedirect();

        $participant = TahfidzTasmianParticipant::firstOrFail();
        $this->assertSame([30], $participant->juz_detail);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/tasmi/participants/{$participant->id}/score", [
                'tahfizh_score_raw' => 90,
                'tajwid_score_raw' => 80,
                'fashohah_score_raw' => 70,
                'catatan_penilai' => 'Tartil dan lancar.',
            ])->assertRedirect();

        $score = TahfidzTasmianScore::firstOrFail();
        $this->assertSame('80.00', (string) $score->nilai_akhir);
        $this->assertSame('jayyid_jiddan', $score->predikat);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/tasmi")
            ->assertOk()
            ->assertSee('Tasmi’ Juz 30');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/tasmi/{$session->id}")
            ->assertOk()
            ->assertSee('Ahmad Tasmi')
            ->assertSee('80.00')
            ->assertSee('Jayyid Jiddan');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/tasmi/participants/{$participant->id}/instrument")
            ->assertOk()
            ->assertSee('Instrumen Penilaian Tasmi')
            ->assertSee('Ahmad Tasmi');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->post("/{$this->admin->id}/tahfidz/tasmi/participants/{$participant->id}/certificate")
            ->assertRedirect();

        $certificate = TahfidzCertificate::firstOrFail();
        $this->assertSame('juz_tunggal', $certificate->certificate_type);
        $this->assertSame('jayyid_jiddan', $certificate->predikat);
        $this->assertSame($this->student->id, $certificate->student_id);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/tasmi/certificates")
            ->assertOk()
            ->assertSee('Ahmad Tasmi')
            ->assertSee($certificate->certificate_number);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/tasmi/certificates/audit")
            ->assertOk()
            ->assertSee('Audit Akhir Syahadah')
            ->assertSee('Semua syahadah lengkap');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get("/{$this->admin->id}/tahfidz/tasmi/certificates/{$certificate->id}/print")
            ->assertOk()
            ->assertSee('Syahadah Tasmi')
            ->assertSee('أحمد تسميع')
            ->assertSee($certificate->certificate_number);
    }
}
