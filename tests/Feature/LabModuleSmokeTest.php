<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Lab\LabProfile;
use App\Models\School;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LabModuleSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_lab_pages_and_print_endpoints_are_available(): void
    {
        $workUnit = WorkUnit::create([
            'id' => (string) Str::uuid(),
            'name' => 'Laboratorium IPA',
            'code' => 'LAB-SMOKE',
            'is_active' => true,
        ]);
        $school = School::create([
            'work_unit_id' => $workUnit->id,
            'npsn' => '99999993',
            'name' => 'Sekolah Uji Lab Modul',
        ]);
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
        ]);
        $user = User::create([
            'name' => 'Admin Lab Uji',
            'email' => 'lab.smoke@example.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->forceFill(['is_system_admin' => true])->save();

        LabProfile::create([
            'school_id' => $school->id,
            'work_unit_id' => $workUnit->id,
            'code' => 'LAB-SMOKE',
            'name' => 'Laboratorium IPA Uji',
            'lab_type' => 'ipa',
            'coordinator_user_id' => $user->id,
            'head_user_id' => $user->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)->withSession(['sa_school_id' => $school->id]);

        foreach ([
            'laboratorium',
            'laboratorium/inventaris',
            'laboratorium/pemeriksaan',
            'laboratorium/kalibrasi',
            'laboratorium/peminjaman',
            'laboratorium/kegiatan',
            'laboratorium/izin',
            'laboratorium/jadwal',
            'laboratorium/keselamatan',
            'laboratorium/insiden',
            'laboratorium/penghapusan',
            'laboratorium/evaluasi',
            'laboratorium/stok',
            'laboratorium/pengajuan',
        ] as $path) {
            $this->get('/'.$user->id.'/'.$path)->assertOk();
        }

        $this->get('/'.$user->id.'/laboratorium/inventaris/cetak')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
