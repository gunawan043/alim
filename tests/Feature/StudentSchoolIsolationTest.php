<?php

namespace Tests\Feature;

use App\Http\Controllers\StudentController;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Isolasi data santri antar satuan pendidikan:
 * - pencarian tidak boleh mengembalikan santri sekolah lain;
 * - create/update tidak boleh memindahkan santri ke sekolah lain lewat input form.
 */
class StudentSchoolIsolationTest extends TestCase
{
    use RefreshDatabase;

    private School $schoolA;

    private School $schoolB;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolA = $this->makeSchool('Sekolah A', '91000001');
        $this->schoolB = $this->makeSchool('Sekolah B', '91000002');

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        $this->user = User::create([
            'name' => 'Operator A',
            'email' => 'operator.a@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $this->user->assignRole($role);

        Student::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Santri Sekolah A',
            'nisn' => '9100000101',
            'gender' => 'L',
            'status' => 'active',
        ]);

        Student::create([
            'school_id' => $this->schoolB->id,
            'name' => 'Santri Sekolah B',
            'nisn' => '9100000102',
            'gender' => 'P',
            'status' => 'active',
        ]);
    }

    public function test_pencarian_santri_hanya_mengembalikan_sekolah_sendiri(): void
    {
        $request = Request::create('/'.$this->user->id.'/students/find-student', 'GET', ['q' => 'Santri']);
        $request->attributes->set('schoolContextId', $this->schoolA->id);
        $request->attributes->set('isGlobalView', false);

        $response = app(StudentController::class)->findStudent($request);
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data);
        $this->assertSame('Santri Sekolah A', $data[0]['name']);
    }

    public function test_tambah_santri_tidak_bisa_memakai_school_id_sekolah_lain(): void
    {
        Queue::fake();

        $request = $this->makeRequest('/'.$this->user->id.'/students', 'POST', [
            'school_id' => $this->schoolB->id,
            'name' => 'Santri Baru A',
            'nisn' => '9100000201',
            'gender' => 'L',
        ], 'user.students.store');

        $response = app(StudentController::class)->store($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseHas('students', [
            'name' => 'Santri Baru A',
            'school_id' => $this->schoolA->id,
        ]);
        $this->assertDatabaseMissing('students', [
            'name' => 'Santri Baru A',
            'school_id' => $this->schoolB->id,
        ]);
    }

    public function test_edit_santri_tidak_bisa_dipindahkan_ke_sekolah_lain(): void
    {
        Queue::fake();

        $student = Student::where('school_id', $this->schoolA->id)->firstOrFail();

        $request = $this->makeRequest('/'.$this->user->id.'/students/'.$student->id, 'PUT', [
            'school_id' => $this->schoolB->id,
            'name' => 'Santri A Diubah',
            'gender' => 'L',
            'status' => 'active',
        ], 'user.students.update');
        $response = app(StudentController::class)->update($request, $this->user->id, $student->id);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($this->schoolA->id, $student->fresh()->school_id);
        $this->assertSame('Santri A Diubah', $student->fresh()->name);
    }

    private function makeSchool(string $name, string $npsn): School
    {
        $workUnitId = (string) Str::uuid();
        DB::table('work_units')->insert([
            'id' => $workUnitId,
            'name' => 'Unit '.$name,
            'code' => 'U-'.$npsn,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return School::create([
            'work_unit_id' => $workUnitId,
            'name' => $name,
            'npsn' => $npsn,
        ]);
    }

    private function makeRequest(string $uri, string $method, array $data, string $routeName): Request
    {
        $request = Request::create($uri, $method, $data);
        $request->setUserResolver(fn () => $this->user);
        $request->attributes->set('schoolContextId', $this->schoolA->id);
        $request->attributes->set('isGlobalView', false);
        $request->setLaravelSession(app('session.store'));

        $route = Route::getRoutes()->getByName($routeName);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $request->attributes->set('route', $route);

        return $request;
    }
}
