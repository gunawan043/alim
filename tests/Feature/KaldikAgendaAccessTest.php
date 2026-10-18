<?php

namespace Tests\Feature;

use App\Http\Controllers\KaldikController;
use App\Models\Kaldik;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * GTK biasa dapat menambah agenda satuan kerja & agenda pribadi.
 */
class KaldikAgendaAccessTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId, 'name' => 'Unit Kaldik', 'code' => 'UKD',
            'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->school = School::create([
            'work_unit_id' => $workUnitId, 'npsn' => '31313131', 'name' => 'Sekolah Kaldik',
        ]);

        $this->user = User::create([
            'name' => 'Guru Kaldik', 'email' => 'guru.kaldik@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
    }

    public function test_gtk_biasa_bisa_membuat_agenda_pribadi(): void
    {
        $this->actingAs($this->user);
        $controller = app(KaldikController::class);

        $controller->store($this->request('POST', [
            'name' => 'Catatan Pribadi',
            'category' => 'agenda_pribadi',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]), $this->user->id);

        $pribadi = Kaldik::where('name', 'Catatan Pribadi')->firstOrFail();
        $this->assertSame('agenda', $pribadi->category);
        $this->assertSame('pribadi', $pribadi->visibility);
        $this->assertSame($this->user->id, $pribadi->owner_user_id);

        $view = $controller->index($this->request('GET'), $this->user->id);
        $this->assertTrue($view->getData()['canCreate']);
        $event = collect($view->getData()['kaldikEvents'])->firstWhere('title', 'Catatan Pribadi');
        $this->assertTrue($event['extendedProps']['canEdit']);
    }

    public function test_kepala_satuan_bisa_membuat_agenda_satuan_kerja(): void
    {
        \App\Models\GtkEmployment::create([
            'user_id' => $this->user->id,
            'school_id' => $this->school->id,
            'jenis_gtk' => 'guru',
            'jabatan' => 'Kepala Satuan Pendidikan',
            'status_kepegawaian' => 'tetap',
        ]);

        $this->actingAs($this->user);
        $controller = app(KaldikController::class);

        $controller->store($this->request('POST', [
            'name' => 'Rapat Guru',
            'category' => 'agenda_satuan',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ]), $this->user->id);

        $satuan = Kaldik::where('name', 'Rapat Guru')->firstOrFail();
        $this->assertSame('agenda', $satuan->category);
        $this->assertSame('satuan', $satuan->visibility);
        $this->assertSame($this->school->work_unit_id, $satuan->work_unit_id);
    }

    private function request(string $method, array $data = []): Request
    {
        $request = Request::create('/kaldik', $method, $data);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $this->user);

        return $request;
    }
}
