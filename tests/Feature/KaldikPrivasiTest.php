<?php

namespace Tests\Feature;

use App\Http\Controllers\KaldikController;
use App\Models\AcademicYear;
use App\Models\Kaldik;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Privasi Kalender Pendidikan / Agenda:
 * - Kaldik pondok (global) terlihat semua GTK.
 * - Agenda SATUAN hanya terlihat GTK dalam satuan kerja tersebut.
 * - Agenda PRIBADI hanya terlihat oleh pemiliknya (tidak bocor).
 */
class KaldikPrivasiTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private AcademicYear $ay;

    private User $userA;

    private User $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $wuA = (string) Str::uuid();
        $wuB = (string) Str::uuid();

        DB::table('work_units')->insert([
            ['id' => $wuA, 'name' => 'Unit A', 'code' => 'UA', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => $wuB, 'name' => 'Unit B', 'code' => 'UB', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->schoolA = School::create(['work_unit_id' => $wuA, 'npsn' => '10101010', 'name' => 'Sekolah A']);
        $this->schoolB = School::create(['work_unit_id' => $wuB, 'npsn' => '20202020', 'name' => 'Sekolah B']);

        $this->ay = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);

        $this->userA = User::create(['name' => 'User A', 'email' => 'ua@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $this->userB = User::create(['name' => 'User B', 'email' => 'ub@test.local', 'password' => bcrypt('password'), 'is_active' => true]);

        $mk = fn (array $attrs) => Kaldik::create(array_merge([
            'academic_year_id' => $this->ay->id,
            'type' => Kaldik::TYPE_KEGIATAN,
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-10',
            'is_active' => true,
        ], $attrs));

        $mk(['name' => 'Kaldik Pondok', 'category' => Kaldik::CATEGORY_KALDIK, 'work_unit_id' => null, 'visibility' => Kaldik::VISIBILITY_SATUAN]);
        $mk(['name' => 'Agenda Satuan A', 'category' => Kaldik::CATEGORY_AGENDA, 'work_unit_id' => $this->schoolA->work_unit_id, 'visibility' => Kaldik::VISIBILITY_SATUAN]);
        $mk(['name' => 'Agenda Satuan B', 'category' => Kaldik::CATEGORY_AGENDA, 'work_unit_id' => $this->schoolB->work_unit_id, 'visibility' => Kaldik::VISIBILITY_SATUAN]);
        $mk(['name' => 'Agenda Pribadi A', 'category' => Kaldik::CATEGORY_AGENDA, 'work_unit_id' => $this->schoolA->work_unit_id, 'visibility' => Kaldik::VISIBILITY_PRIBADI, 'owner_user_id' => $this->userA->id]);
        $mk(['name' => 'Agenda Pribadi B', 'category' => Kaldik::CATEGORY_AGENDA, 'work_unit_id' => $this->schoolB->work_unit_id, 'visibility' => Kaldik::VISIBILITY_PRIBADI, 'owner_user_id' => $this->userB->id]);
    }

    public function test_agenda_pribadi_hanya_terlihat_pemiliknya_dan_satuan_per_unit(): void
    {
        $controller = app(KaldikController::class);

        $namesA = $controller->index($this->makeRequest($this->userA, $this->schoolA), $this->userA->id)
            ->getData()['kaldiks']->pluck('name')->all();

        $this->assertContains('Kaldik Pondok', $namesA, 'Kaldik pondok terlihat semua GTK.');
        $this->assertContains('Agenda Satuan A', $namesA, 'Agenda satuan sendiri terlihat.');
        $this->assertContains('Agenda Pribadi A', $namesA, 'Agenda pribadi pemilik terlihat.');
        $this->assertNotContains('Agenda Satuan B', $namesA, 'Agenda satuan lain tidak boleh bocor.');
        $this->assertNotContains('Agenda Pribadi B', $namesA, 'Agenda pribadi GTK lain tidak boleh bocor.');

        $namesB = $controller->index($this->makeRequest($this->userB, $this->schoolB), $this->userB->id)
            ->getData()['kaldiks']->pluck('name')->all();

        $this->assertContains('Kaldik Pondok', $namesB);
        $this->assertContains('Agenda Satuan B', $namesB);
        $this->assertContains('Agenda Pribadi B', $namesB);
        $this->assertNotContains('Agenda Satuan A', $namesB);
        $this->assertNotContains('Agenda Pribadi A', $namesB);
    }

    public function test_halaman_agenda_kegiatan_memakai_sumber_kaldik_dan_privasi_sama(): void
    {
        $controller = app(\App\Http\Controllers\Personalia\KalenderKegiatanController::class);

        $view = $controller->akademik($this->makeRequest($this->userA, $this->schoolA), $this->userA->id);
        $names = $view->getData()['agendaList']->pluck('name')->all();
        $stats = $view->getData()['stats'];

        $this->assertContains('Agenda Satuan A', $names, 'Agenda satuan sendiri tampil di halaman agenda.');
        $this->assertContains('Agenda Pribadi A', $names, 'Agenda pribadi pemilik tampil.');
        $this->assertNotContains('Agenda Satuan B', $names, 'Agenda satuan lain tidak bocor.');
        $this->assertNotContains('Agenda Pribadi B', $names, 'Agenda pribadi GTK lain tidak bocor.');
        $this->assertNotContains('Kaldik Pondok', $names, 'Kategori kaldik bukan agenda kegiatan.');

        $this->assertSame(2, $stats['total']);
    }

    private function makeRequest(User $user, School $school): Request
    {
        $request = Request::create('/kaldik', 'GET');
        $request->attributes->set('schoolContextId', $school->id);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
