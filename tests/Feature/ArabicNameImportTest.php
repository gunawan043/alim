<?php

namespace Tests\Feature;

use App\Exports\GtkArabicNameTemplateExport;
use App\Exports\StudentArabicNameTemplateExport;
use App\Http\Controllers\GtkArabicNameController;
use App\Http\Controllers\StudentArabicNameController;
use App\Http\Controllers\StudentController;
use App\Models\GtkEmployment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\ArabicName\ArabicNameGenerator;
use App\Services\ArabicName\ArabicNameImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * V6 — Import & generator nama Arab (santri via NIS/NISN, GTK via NUPY)
 * beserta menu import terpisah dan template Excel.
 */
class ArabicNameImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private School $otherSchool;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['school-a', 'school-b'] as $index => $code) {
            $workUnitId = (string) Str::uuid();
            DB::table('work_units')->insert([
                'id' => $workUnitId, 'name' => "Unit {$code}", 'code' => strtoupper($code),
                'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);

            $school = School::create([
                'work_unit_id' => $workUnitId,
                'npsn' => '1234500'.$index,
                'name' => "Sekolah {$code}",
            ]);

            if ($index === 0) {
                $this->school = $school;
            } else {
                $this->otherSchool = $school;
            }
        }
    }

    private function makeStudent(array $attributes): Student
    {
        return Student::create(array_merge([
            'school_id' => $this->school->id,
            'name' => 'Santri',
            'nisn' => (string) random_int(1000000000, 9999999999),
            'gender' => 'L',
            'status' => 'active',
        ], $attributes));
    }

    private function makeGtk(string $name, string $nupy, ?School $school = null): User
    {
        $user = User::create([
            'name' => $name,
            'email' => Str::slug($name).'-'.Str::lower(Str::random(6)).'@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        GtkEmployment::create([
            'user_id' => $user->id,
            'school_id' => ($school ?? $this->school)->id,
            'nupy' => $nupy,
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Pendidik / Guru',
            'jabatan' => 'Guru Umum',
        ]);

        return $user;
    }

    public function test_generator_menggunakan_kamus_dan_transliterasi(): void
    {
        $generator = app(ArabicNameGenerator::class);

        $this->assertSame('محمد فوزان', $generator->generate('Muhammad Fauzan'));
        $this->assertSame('أحمد', $generator->generate('Ahmad'));
        $this->assertSame('ذو الكفل', $generator->generate('Zulkifli'));

        $unknown = $generator->generate('Bambang');
        $this->assertNotSame('', $unknown);
        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $unknown);
        $this->assertStringContainsString('نغ', $unknown, 'Digraf ng harus ditulis نغ (bukan ج).');
        $this->assertStringContainsString('ذ', $generator->generate('Dzaki'), 'Digraf dz harus ditulis ذ.');
        $this->assertStringContainsString('ش', $generator->generate('Syarif'), 'Digraf sy harus ditulis ش.');
    }

    public function test_import_nama_arab_santri_berdasarkan_nis_dan_nisn(): void
    {
        $byNis = $this->makeStudent(['name' => 'Santri NIS', 'nis' => '1001']);
        $byNisn = $this->makeStudent(['name' => 'Santri NISN', 'nis' => '1002', 'nisn' => '2002002002']);

        $result = app(ArabicNameImportService::class)->importStudents([
            ['nis' => '1001', 'nisn' => null, 'nama_arab' => 'الطالب الأول'],
            ['nis' => null, 'nisn' => '2002002002', 'nama_arab' => 'الطالب الثاني'],
            ['nis' => '9999', 'nisn' => null, 'nama_arab' => 'غير موجود'],
            ['nis' => '1001', 'nisn' => null, 'nama_arab' => ''],
        ], $this->school->id);

        $this->assertSame(2, $result['updated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(['9999'], $result['not_found']);

        $this->assertSame('الطالب الأول', $byNis->fresh()->name_ar);
        $this->assertSame('الطالب الثاني', $byNisn->fresh()->name_ar);
        $this->assertSame('pending', $byNis->fresh()->name_ar_status, 'Hasil import harus menunggu tinjauan.');
    }

    public function test_import_nama_arab_gtk_berdasarkan_nupy(): void
    {
        $guru = $this->makeGtk('Guru Satu', 'NUPY-001');
        $guruLain = $this->makeGtk('Guru Lain', 'NUPY-002', $this->otherSchool);

        $result = app(ArabicNameImportService::class)->importGtk([
            ['nupy' => 'NUPY-001', 'nama_arab' => 'الأستاذ الأول'],
            ['nupy' => 'NUPY-002', 'nama_arab' => 'أستاذ المدرسة الأخرى'],
            ['nupy' => 'NUPY-999', 'nama_arab' => 'غير موجود'],
        ], $this->school->id);

        $this->assertSame(1, $result['updated']);
        $this->assertSame(['NUPY-002', 'NUPY-999'], $result['not_found']);
        $this->assertSame('الأستاذ الأول', $guru->fresh()->name_ar);
        $this->assertSame('pending', $guru->fresh()->name_ar_status);
        $this->assertNull($guruLain->fresh()->name_ar, 'GTK satuan pendidikan lain tidak boleh terubah.');
    }

    public function test_generate_otomatis_hanya_mengisi_yang_kosong(): void
    {
        $filled = $this->makeStudent(['name' => 'Ahmad', 'name_ar' => 'أحمد المخصوص']);
        $empty = $this->makeStudent(['name' => 'Muhammad Fauzan']);

        $count = app(ArabicNameImportService::class)->generateStudents($this->school->id);

        $this->assertSame(1, $count);
        $this->assertSame('أحمد المخصوص', $filled->fresh()->name_ar, 'Nama Arab yang sudah ada tidak ditimpa.');
        $this->assertSame('محمد فوزان', $empty->fresh()->name_ar);
        $this->assertSame('pending', $empty->fresh()->name_ar_status);
    }

    public function test_halaman_import_nama_arab_santri_dan_template(): void
    {
        $this->makeStudent(['name' => 'Santri A', 'nis' => '3001']);
        $this->makeStudent(['name' => 'Santri B', 'nis' => '3002', 'name_ar' => 'سانتري ب']);

        $request = Request::create('/students/import-nama-arab', 'GET');
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => null);

        $view = app(StudentArabicNameController::class)->form($request, (string) Str::uuid());
        $stats = $view->getData()['stats'];

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['filled']);
        $this->assertSame(1, $stats['missing']);

        $export = new StudentArabicNameTemplateExport($this->school->id);
        $this->assertSame(['nis', 'nisn', 'nama', 'nama_arab'], $export->headings());
        $this->assertCount(2, $export->collection());
    }

    public function test_generate_otomatis_nama_arab_gtk(): void
    {
        $guru = $this->makeGtk('Ahmad Fauzi', 'NUPY-G01');

        $count = app(ArabicNameImportService::class)->generateGtk($this->school->id);

        $this->assertSame(1, $count);
        $this->assertSame('أحمد فوزي', $guru->fresh()->name_ar);
    }

    public function test_import_excel_nama_arab_santri_end_to_end(): void
    {
        $student = $this->makeStudent(['name' => 'Santri Excel', 'nis' => '4001']);

        $role = Role::firstOrCreate(
            ['name' => 'Satuan Pendidikan', 'guard_name' => 'web'],
            ['level' => 8]
        );
        Permission::firstOrCreate(['name' => 'impersonate_role', 'guard_name' => 'web']);

        $tu = User::create([
            'name' => 'TU Import',
            'email' => 'tu.import@test.local',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        // Buat employment lebih dulu: observer GTK menyinkronkan role berdasarkan
        // jabatan, sehingga role harus diberikan setelah employment terbentuk.
        GtkEmployment::create([
            'user_id' => $tu->id,
            'school_id' => $this->school->id,
            'nupy' => 'NUPY-TU-01',
            'status_kepegawaian' => 'GTY',
            'jenis_gtk' => 'Tenaga Kependidikan',
            'jabatan' => 'Staf Tata Usaha',
        ]);
        $tu->assignRole($role);

        $export = new class implements FromArray, WithHeadings
        {
            public function array(): array
            {
                return [['4001', null, 'Santri Excel', 'الطالب إكسل']];
            }

            public function headings(): array
            {
                return ['nis', 'nisn', 'nama', 'nama_arab'];
            }
        };

        $bytes = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $file = UploadedFile::fake()->createWithContent('nama-arab.xlsx', $bytes)
            ->mimeType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->actingAs($tu)
            ->post("/{$tu->id}/students/import-nama-arab", ['file' => $file])
            ->assertRedirect()
            ->assertSessionHas('import_result');

        $this->assertSame('الطالب إكسل', $student->fresh()->name_ar);
    }

    public function test_review_dan_approve_nama_arab_santri(): void
    {
        $pending = $this->makeStudent([
            'name' => 'Santri Pending', 'nis' => '6001', 'name_ar' => 'سانتري',
            'name_ar_status' => 'pending',
        ]);
        $approved = $this->makeStudent(['name' => 'Santri Approved', 'nis' => '6002', 'name_ar' => 'سانتري معتمد']);

        $role = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        $tu = User::create([
            'name' => 'TU Review', 'email' => 'tu.review@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $tu->assignRole($role);
        $this->actingAs($tu);

        $request = Request::create('/students/review-nama-arab', 'GET', ['status' => 'pending']);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $tu);

        $view = app(StudentArabicNameController::class)->review($request, (string) $tu->id);
        $rows = $view->getData()['students'];

        $this->assertTrue($rows->contains('id', $pending->id));
        $this->assertFalse($rows->contains('id', $approved->id), 'Hanya status menunggu yang tampil pada filter pending.');

        $approveRequest = Request::create('/students/review-nama-arab/'.$pending->id, 'POST', ['name_ar' => 'سانتري المصحح']);
        $approveRequest->attributes->set('schoolContextId', $this->school->id);
        $approveRequest->setUserResolver(fn () => $tu);

        app(StudentArabicNameController::class)->approve($approveRequest, (string) $tu->id, $pending->id);

        $pending->refresh();
        $this->assertSame('سانتري المصحح', $pending->name_ar);
        $this->assertSame('approved', $pending->name_ar_status);
        $this->assertSame($tu->id, $pending->name_ar_reviewed_by);

        // Bulk approve hanya menyentuh status pending.
        $second = $this->makeStudent(['name' => 'Santri Kedua', 'nis' => '6003', 'name_ar' => 'سانتري الثاني', 'name_ar_status' => 'pending']);

        $bulkRequest = Request::create('/students/review-nama-arab/approve-all', 'POST');
        $bulkRequest->attributes->set('schoolContextId', $this->school->id);
        $bulkRequest->setUserResolver(fn () => $tu);

        app(StudentArabicNameController::class)->approveAll($bulkRequest, (string) $tu->id);

        $this->assertSame('approved', $second->fresh()->name_ar_status);
    }

    public function test_approve_nama_arab_gtk(): void
    {
        $admin = User::create([
            'name' => 'Super Admin Review', 'email' => 'sa.review@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);
        $guru = $this->makeGtk('Guru Review', 'NUPY-R01');
        $guru->update(['name_ar' => 'الأستاذ للمراجعة', 'name_ar_status' => 'pending']);

        $this->actingAs($admin);

        $request = Request::create('/gtk/review-nama-arab/'.$guru->id, 'POST', ['name_ar' => 'الأستاذ المعتمد']);
        $request->attributes->set('schoolContextId', $this->school->id);
        $request->setUserResolver(fn () => $admin);

        app(GtkArabicNameController::class)->approve($request, (string) $admin->id, $guru->id);

        $guru->refresh();
        $this->assertSame('الأستاذ المعتمد', $guru->name_ar);
        $this->assertSame('approved', $guru->name_ar_status);
        $this->assertSame($admin->id, $guru->name_ar_reviewed_by);
    }

    public function test_tabel_santri_kolom_sekolah_hanya_untuk_peran_lintas_sekolah(): void
    {
        $this->makeStudent(['name' => 'Santri Kolom', 'nis' => '5001']);

        $roleSatuan = Role::firstOrCreate(['name' => 'Satuan Pendidikan', 'guard_name' => 'web'], ['level' => 8]);
        $rolePimpinan = Role::firstOrCreate(['name' => 'Pimpinan', 'guard_name' => 'web'], ['level' => 2]);

        $tu = User::create([
            'name' => 'TU Kolom', 'email' => 'tu.kolom@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $tu->assignRole($roleSatuan);

        $pimpinan = User::create([
            'name' => 'Pimpinan Kolom', 'email' => 'pimpinan.kolom@test.local',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $pimpinan->assignRole($rolePimpinan);

        $request = Request::create('/students', 'GET');
        $request->attributes->set('schoolContextId', $this->school->id);

        $this->actingAs($tu);
        $request->setUserResolver(fn () => $tu);
        $viewTu = app(StudentController::class)->index($request);
        $this->assertFalse($viewTu->getData()['showSchoolColumn'], 'Peran satuan pendidikan: kolom sekolah diganti nama Arab.');

        $requestPimpinan = Request::create('/students', 'GET');
        $requestPimpinan->attributes->set('schoolContextId', $this->school->id);
        $this->actingAs($pimpinan);
        $requestPimpinan->setUserResolver(fn () => $pimpinan);
        $viewPimpinan = app(StudentController::class)->index($requestPimpinan);
        $this->assertTrue($viewPimpinan->getData()['showSchoolColumn'], 'Pimpinan (lintas sekolah) tetap melihat kolom sekolah.');
    }

    public function test_template_gtk_berisi_nupy(): void
    {
        $this->makeGtk('Guru Template', 'NUPY-T01');

        $export = new GtkArabicNameTemplateExport($this->school->id);

        $this->assertSame(['nupy', 'nama', 'nama_arab'], $export->headings());
        $rows = $export->collection();
        $this->assertCount(1, $rows);
        $this->assertSame('NUPY-T01', $rows->first()[0]);
    }
}
