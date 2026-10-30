<?php

namespace Tests\Feature\Uks;

use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\GtkHealthRecord;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentHealthMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UksImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget('system_settings_map');
    }

    public function test_medicine_inventory_can_be_imported(): void
    {
        $admin = $this->admin();
        $school = School::factory()->create();
        $this->withSession(['sa_school_id' => $school->id]);
        $csv = implode("\n", [
            'kode_obat,nama_obat,nama_generik,kategori,satuan,stok,stok_minimum,tanggal_kedaluwarsa,lokasi_penyimpanan,supplier,harga_satuan,info_dosis,catatan',
            'OBT-TEST,Paracetamol,Paracetamol,obat_dalam,tablet,100,20,2027-12-31,Lemari A,Supplier Test,250,3x1,Contoh',
        ]);

        $this->actingAs($admin)
            ->post(route('user.uks.imports.store', ['userId' => $admin->id, 'dataset' => 'medicine']), [
                'file' => UploadedFile::fake()->createWithContent('medicine.csv', $csv),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_medicine_inventory', [
            'medicine_code' => 'OBT-TEST',
            'medicine_name' => 'Paracetamol',
            'current_stock' => 100,
        ]);
    }

    public function test_health_metrics_can_be_imported(): void
    {
        $admin = $this->admin();
        $school = School::factory()->create();
        AcademicYear::create([
            'school_id' => $school->id,
            'name' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $this->withSession(['sa_school_id' => $school->id]);
        $student = Student::create([
            'school_id' => $school->id,
            'nisn' => '0088888888',
            'name' => 'Santri Import',
            'gender' => 'L',
        ]);

        $csv = implode("\n", [
            'nisn,tanggal_pengukuran,tinggi_cm,berat_kg,lingkar_kepala_cm,sesi,catatan',
            '0088888888,2026-10-24,150,45,52,awal_tahun,Import test',
        ]);

        $this->actingAs($admin)
            ->post(route('user.uks.imports.store', ['userId' => $admin->id, 'dataset' => 'metrics']), [
                'file' => UploadedFile::fake()->createWithContent('metrics.csv', $csv),
            ])
            ->assertRedirect();

        $metric = StudentHealthMetric::where('student_id', $student->id)->firstOrFail();
        $this->assertSame(150, $metric->height_cm);
        $this->assertSame(45, $metric->weight_kg);
    }

    public function test_gtk_health_records_can_be_imported(): void
    {
        $admin = $this->admin();
        $gtk = User::factory()->create(['is_active' => true]);
        GtkEmployment::create([
            'user_id' => $gtk->id,
            'status_kepegawaian' => 'GTT',
            'nupy' => '198001012010011001',
        ]);

        $csv = implode("\n", [
            'nupy,email,tanggal_pemeriksaan,tinggi_cm,berat_kg,sistolik,diastolik,nadi,suhu,respirasi,saturasi_oksigen,gula_darah,kolesterol,asam_urat,hemoglobin,catatan',
            '198001012010011001,,2026-10-24,170,60,120,80,78,36.5,18,98,90,180,5.5,14,Import test',
        ]);

        $this->actingAs($admin)
            ->post(route('user.uks.imports.store', ['userId' => $admin->id, 'dataset' => 'gtk-health']), [
                'file' => UploadedFile::fake()->createWithContent('gtk-health.csv', $csv),
            ])
            ->assertRedirect();

        $record = GtkHealthRecord::where('user_id', $gtk->id)->firstOrFail();
        $this->assertSame('170.0', (string) $record->height_cm);
        $this->assertSame('60.0', (string) $record->weight_kg);
        $this->assertSame('120/80', $record->blood_pressure);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'is_active' => true,
            'is_system_admin' => true,
        ]);
    }
}
