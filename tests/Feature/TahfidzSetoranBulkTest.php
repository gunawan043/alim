<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GtkEmployment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudyGroup;
use App\Models\TahfidzGroup;
use App\Models\TahfidzGroupMember;
use App\Models\TahfidzPackage;
use App\Models\TahfidzSetoran;
use App\Models\User;
use App\Models\WorkUnit;
use Database\Seeders\TahfidzFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grid input setoran harian: satu layar untuk seluruh halaqah,
 * Ziyadah dan Rabt diisi berdampingan lalu disimpan sekali.
 */
class TahfidzSetoranBulkTest extends TestCase
{
    use RefreshDatabase;

    private WorkUnit $unit;

    private School $school;

    private AcademicYear $year;

    private StudyGroup $studyGroup;

    private User $teacher;

    private User $admin;

    private TahfidzGroup $group;

    private Student $santriA;

    private Student $santriB;

    protected function setUp(): void
    {
        parent::setUp();
        config(['tahfidz.auto_verify_setoran' => true]);

        $this->unit = WorkUnit::create(['name' => 'Sekolah Bulk', 'code' => 'SCH-BLK', 'type' => 'Unit Akademik', 'is_active' => true]);
        $this->school = School::create(['work_unit_id' => $this->unit->id, 'npsn' => '60606060', 'name' => 'Sekolah Bulk']);
        $this->year = AcademicYear::create([
            'name' => '2026/2027', 'semester' => 'ganjil', 'is_active' => true,
            'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
        ]);
        $grade = GradeLevel::create(['school_id' => $this->school->id, 'level' => 7, 'name' => 'Kelas 7', 'fase' => 'D', 'is_active' => true]);
        $this->studyGroup = StudyGroup::create([
            'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'grade_level_id' => $grade->id, 'name' => '7B', 'code' => '7B', 'is_active' => true,
        ]);

        $this->teacher = User::create(['name' => 'Guru Bulk', 'email' => 'guru.bulk@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        GtkEmployment::create([
            'user_id' => $this->teacher->id, 'school_id' => $this->school->id, 'academic_year_id' => $this->year->id,
            'status_kepegawaian' => 'GTY', 'jenis_gtk' => 'Pendidik / Guru', 'jabatan' => 'Guru Tahfidz',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Bulk', 'email' => 'admin.bulk@test.local',
            'password' => bcrypt('password'), 'is_active' => true, 'is_system_admin' => true,
        ]);

        $this->santriA = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Alif', 'nis' => 'BK-1', 'nisn' => 'BK-1',
            'gender' => 'L', 'status' => 'active',
        ]);
        $this->santriB = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Ba', 'nis' => 'BK-2', 'nisn' => 'BK-2',
            'gender' => 'L', 'status' => 'active',
        ]);

        $this->seed(TahfidzFoundationSeeder::class);
        $package = TahfidzPackage::firstOrFail();

        $this->group = TahfidzGroup::create([
            'work_unit_id' => $this->unit->id, 'school_id' => $this->school->id,
            'study_group_id' => $this->studyGroup->id, 'academic_year_id' => $this->year->id,
            'tahfidz_package_id' => $package->id, 'name' => 'Halaqah Bulk',
            'gender' => 'putra', 'teacher_id' => $this->teacher->id, 'is_active' => true,
        ]);

        foreach ([$this->santriA, $this->santriB] as $student) {
            TahfidzGroupMember::create([
                'tahfidz_group_id' => $this->group->id, 'student_id' => $student->id,
                'academic_year_id' => $this->year->id, 'join_date' => now()->toDateString(), 'status' => 'aktif',
            ]);
        }
    }

    public function test_guru_melihat_semua_santri_halaqah_dalam_satu_grid(): void
    {
        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.tahfidz.setoran-bulk.index', ['userId' => $this->teacher->id, 'group_id' => $this->group->id]));

        $response->assertOk()
            ->assertSee('Input Setoran Harian')
            ->assertSee('Santri Alif')
            ->assertSee('Santri Ba')
            ->assertSee('Ziyadah')
            ->assertSee('Rabt')
            // Daftar santri dengan tombol aksi, bukan tabel isian lebar.
            ->assertSee('Belum ada setoran')
            ->assertDontSee('bulk-table');
    }

    public function test_tombol_ziyadah_dan_rabt_membuka_halaman_input_per_santri(): void
    {
        $list = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.tahfidz.setoran-bulk.index', ['userId' => $this->teacher->id, 'group_id' => $this->group->id]));

        // Tiap santri punya dua tautan aksi, satu per jenis setoran.
        $entryBase = "/{$this->teacher->id}/tahfidz/setoran-harian/santri/{$this->santriA->id}";
        $list->assertOk()->assertSee($entryBase, false);

        $html = $list->getContent();
        $this->assertMatchesRegularExpression(
            '/'.preg_quote($entryBase, '/').'\?[^"]*jenis=ziyadah/',
            $html,
            'Tautan Ziyadah per santri harus tersedia.',
        );
        $this->assertMatchesRegularExpression(
            '/'.preg_quote($entryBase, '/').'\?[^"]*jenis=robet/',
            $html,
            'Tautan Rabt per santri harus tersedia.',
        );

        // Tombol membuka halaman input santri yang sudah ada, bukan form kedua.
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.tahfidz.setoran-bulk.entry', [
                'userId' => $this->teacher->id, 'studentId' => $this->santriA->id,
                'group_id' => $this->group->id, 'jenis' => 'robet',
            ]))
            ->assertRedirect(route('user.tahfidz.input.index', [
                'userId' => $this->teacher->id, 'student_id' => $this->santriA->id,
            ]));

        // Santri di luar halaqah tidak boleh dibuka lewat URL langsung.
        $outside = Student::create([
            'school_id' => $this->school->id, 'name' => 'Santri Luar', 'nis' => 'OUT-9',
            'gender' => 'putra', 'status' => 'active',
        ]);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.tahfidz.setoran-bulk.entry', [
                'userId' => $this->teacher->id, 'studentId' => $outside->id,
                'group_id' => $this->group->id, 'jenis' => 'ziyadah',
            ]))
            ->assertNotFound();
    }

    public function test_grid_menampilkan_isi_setoran_yang_sudah_ada_pada_tanggal_itu(): void
    {
        $date = now()->toDateString();

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])->post(
            route('user.tahfidz.setoran-bulk.store', ['userId' => $this->teacher->id]),
            [
                'tahfidz_group_id' => $this->group->id,
                'setoran_date' => $date,
                'entries' => [[
                    'student_id' => $this->santriB->id,
                    'robet' => [
                        'surah_start_name' => 'Al-Ikhlas', 'ayat_start' => 1,
                        'surah_end_name' => 'Al-Ikhlas', 'ayat_end' => 4,
                        'juz' => 30, 'nilai_rabt' => 77, 'nilai_tikrar' => 66,
                        'robet_status' => 'belum_siap',
                    ],
                ]],
            ]
        );

        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.tahfidz.setoran-bulk.index', [
                'userId' => $this->teacher->id, 'group_id' => $this->group->id, 'date' => $date,
            ]));

        $response->assertOk()
            ->assertSee('Al-Ikhlas')
            ->assertSee('77')
            ->assertSee('Edit Rabt');
    }

    public function test_ziyadah_dan_rabt_disimpan_sekaligus_dengan_nilai_tikrar(): void
    {
        $date = now()->toDateString();

        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])->post(
            route('user.tahfidz.setoran-bulk.store', ['userId' => $this->teacher->id]),
            [
                'tahfidz_group_id' => $this->group->id,
                'setoran_date' => $date,
                'entries' => [
                    [
                        'student_id' => $this->santriA->id,
                        'ziyadah' => [
                            'surah_start_name' => 'Al-Baqarah', 'ayat_start' => 1,
                            'surah_end_name' => 'Al-Baqarah', 'ayat_end' => 5,
                            'juz' => 1, 'hasil_hafalan' => 90, 'nilai_tikrar' => 85,
                            'status' => 'lulus', 'catatan_guru' => 'Lancar',
                        ],
                        'robet' => [
                            'surah_start_name' => 'Al-Fatihah', 'ayat_start' => 1,
                            'surah_end_name' => 'Al-Fatihah', 'ayat_end' => 7,
                            'juz' => 1, 'nilai_rabt' => 88, 'nilai_tikrar' => 80,
                            'robet_status' => 'siap',
                        ],
                    ],
                    [
                        'student_id' => $this->santriB->id,
                        'robet' => [
                            'surah_start_name' => 'Al-Ikhlas', 'ayat_start' => 1,
                            'surah_end_name' => 'Al-Ikhlas', 'ayat_end' => 4,
                            'juz' => 30, 'nilai_rabt' => 75, 'nilai_tikrar' => 70,
                            'robet_status' => 'perlu_diulang',
                        ],
                    ],
                ],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');


        $ziyadah = TahfidzSetoran::where('student_id', $this->santriA->id)->where('setoran_type', 'ziyadah')->firstOrFail();
        $this->assertSame(90.0, (float) $ziyadah->hasil_hafalan);
        $this->assertSame(90.0, (float) $ziyadah->nilai_setoran);
        $this->assertSame(85.0, (float) $ziyadah->nilai_tikrar);
        $this->assertContains($ziyadah->verification_status, ['verified', 'pending']);
        $this->assertNotEmpty($ziyadah->surah_start_name);

        $rabtA = TahfidzSetoran::where('student_id', $this->santriA->id)->where('setoran_type', 'robet')->firstOrFail();
        $this->assertSame(88.0, (float) $rabtA->nilai_setoran);
        $this->assertSame(80.0, (float) $rabtA->nilai_tikrar);
        $this->assertSame('siap', $rabtA->robet_status);

        $rabtB = TahfidzSetoran::where('student_id', $this->santriB->id)->where('setoran_type', 'robet')->firstOrFail();
        $this->assertSame(75.0, (float) $rabtB->nilai_setoran);
        $this->assertSame('perlu_diulang', $rabtB->robet_status);
        $this->assertSame(3, TahfidzSetoran::count());
    }

    public function test_baris_kosong_tidak_menyimpan_apa_pun(): void
    {
        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])->post(
            route('user.tahfidz.setoran-bulk.store', ['userId' => $this->teacher->id]),
            [
                'tahfidz_group_id' => $this->group->id,
                'setoran_date' => now()->toDateString(),
                'entries' => [
                    ['student_id' => $this->santriA->id, 'jenis' => ''],
                    ['student_id' => $this->santriB->id, 'ziyadah' => ['surah_start_name' => '', 'ayat_start' => '']],
                ],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, TahfidzSetoran::count());
    }

    public function test_simpan_ulang_pada_tanggal_sama_memperbarui_bukan_menduplikasi(): void
    {
        $date = now()->toDateString();
        $payload = [
            'tahfidz_group_id' => $this->group->id,
            'setoran_date' => $date,
            'entries' => [[
                'student_id' => $this->santriA->id,
                'ziyadah' => [
                    'surah_start_name' => 'An-Nas', 'ayat_start' => 1,
                    'surah_end_name' => 'An-Nas', 'ayat_end' => 6,
                    'juz' => 30, 'hasil_hafalan' => 70, 'status' => 'lulus',
                ],
            ]],
        ];

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])->post(route('user.tahfidz.setoran-bulk.store', ['userId' => $this->teacher->id]), $payload);

        $payload['entries'][0]['ziyadah']['hasil_hafalan'] = 95;
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])->post(route('user.tahfidz.setoran-bulk.store', ['userId' => $this->teacher->id]), $payload);

        $this->assertSame(1, TahfidzSetoran::count());
        $this->assertSame(95.0, (float) TahfidzSetoran::firstOrFail()->hasil_hafalan);
    }

    public function test_progres_juz_hanya_dari_ziyadah_bukan_rabt(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])->post(
            route('user.tahfidz.setoran-bulk.store', ['userId' => $this->teacher->id]),
            [
                'tahfidz_group_id' => $this->group->id,
                'setoran_date' => now()->toDateString(),
                'entries' => [[
                    'student_id' => $this->santriA->id,
                    'rabt' => [
                        'surah_start_name' => 'Al-Fatihah', 'ayat_start' => 1,
                        'surah_end_name' => 'Al-Fatihah', 'ayat_end' => 7,
                        'juz' => 1, 'nilai_rabt' => 80, 'robet_status' => 'siap',
                    ],
                ]],
            ]
        );

        $this->assertSame(0, \App\Models\TahfidzJuzProgress::count(), 'Rabt tidak menambah halaman progres juz.');
    }

    public function test_guru_tidak_boleh_menyimpan_ke_halaqah_lain(): void
    {
        $teacherLain = User::create(['name' => 'Guru Lain', 'email' => 'guru.lain.bulk@test.local', 'password' => bcrypt('password'), 'is_active' => true]);
        $groupLain = TahfidzGroup::create([
            'work_unit_id' => $this->unit->id, 'school_id' => $this->school->id,
            'study_group_id' => $this->studyGroup->id, 'academic_year_id' => $this->year->id,
            'tahfidz_package_id' => TahfidzPackage::firstOrFail()->id, 'name' => 'Halaqah Lain',
            'gender' => 'putra', 'teacher_id' => $teacherLain->id, 'is_active' => true,
        ]);

        // Santri Alif hanya anggota halaqah Bulk, bukan halaqah Lain.
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])->post(
            route('user.tahfidz.setoran-bulk.store', ['userId' => $this->teacher->id]),
            [
                'tahfidz_group_id' => $groupLain->id,
                'setoran_date' => now()->toDateString(),
                'entries' => [[
                    'student_id' => $this->santriA->id,
                    'ziyadah' => ['surah_start_name' => 'An-Nas', 'ayat_start' => 1, 'juz' => 30, 'hasil_hafalan' => 80, 'status' => 'lulus'],
                ]],
            ]
        );

        $this->assertSame(0, TahfidzSetoran::count(), 'Santri di luar halaqah tidak boleh dicatat.');
    }

    public function test_halaman_sts_sas_membaca_buku_nilai_mapel_tahfidz(): void
    {
        $subject = \App\Models\Subject::create([
            'school_id' => $this->school->id,
            'name' => 'Tahfidz', 'code' => 'THF-7', 'is_active' => true,
            'penilaian_format' => 'tahfidz',
        ]);
        $book = \App\Models\TahfidzGradebook::create([
            'school_id' => $this->school->id,
            'tahfidz_group_id' => $this->group->id, 'subject_id' => $subject->id,
            'academic_year_id' => $this->year->id, 'semester' => 'ganjil', 'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.tahfidz.penilaian.index', ['userId' => $this->teacher->id, 'group_id' => $this->group->id]));

        $response->assertOk()
            ->assertSee('Buku Nilai Halaqah')
            ->assertSee('STS')
            ->assertSee('SAS')
            ->assertSee('Santri Alif');

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])->post(
            route('user.tahfidz.penilaian.store', ['userId' => $this->teacher->id]),
            [
                'tahfidz_group_id' => $this->group->id,
                'tahfidz_gradebook_id' => $book->id,
                'semester' => 'ganjil',
                'scores' => [$this->santriA->id => ['sts' => 87.5]],
            ]
        )->assertRedirect();

        $row = \App\Models\TahfidzGradebookScore::where('tahfidz_gradebook_id', $book->id)
            ->where('student_id', $this->santriA->id)->firstOrFail();
        $this->assertSame(87.5, (float) $row->sts);
    }

    public function test_halaman_sts_sas_memberi_jalan_keluar_bila_buku_nilai_belum_ada(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.tahfidz.penilaian.index', ['userId' => $this->teacher->id, 'group_id' => $this->group->id]))
            ->assertOk()
            ->assertSee('Belum ada mapel berformat tahfidz')
            ->assertSee('Format Penilaian Mapel');
    }

    public function test_pencarian_nama_santri_menyaring_daftar(): void
    {
        $base = route('user.tahfidz.setoran-bulk.index', ['userId' => $this->teacher->id, 'group_id' => $this->group->id]);

        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get($base.'&q=Alif')
            ->assertOk()
            ->assertSee('Santri Alif')
            ->assertDontSee('Santri Ba');

        // Pencarian juga mengenali NIS.
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get($base.'&q='.$this->santriB->nis)
            ->assertOk()
            ->assertSee('Santri Ba')
            ->assertDontSee('Santri Alif');
    }

    public function test_jumlah_baris_dihitung_dengan_kaidah_mushaf_madinah(): void
    {
        $service = app(\App\Services\QuranPositionService::class);

        // Al-Fatihah 1–7 duduk di halaman 1 baris 2–8 → 7 baris.
        $this->assertSame(7, $service->countLines(1, 2, 1, 8));

        // Satu halaman penuh selalu 15 baris.
        $this->assertSame(15, $service->countLines(1, 1, 1, 15));

        // Lintas halaman: 14 sisa baris halaman awal + 5 baris halaman akhir.
        $this->assertSame(19, $service->countLines(1, 2, 2, 5));

        // Tiga halaman: sisa awal + satu halaman penuh (15) + baris akhir.
        $this->assertSame(14 + 15 + 7, $service->countLines(1, 2, 3, 7));

        // Rentang terbalik tidak menghasilkan angka negatif.
        $this->assertSame(0, $service->countLines(3, 2, 2, 5));
    }

    public function test_halaman_setoran_harian_tidak_lagi_memuat_tab_mutabaah(): void
    {
        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.tahfidz.setoran-bulk.index', [
                'userId' => $this->teacher->id, 'group_id' => $this->group->id,
            ]));

        $response->assertOk()
            ->assertSee('Santri Alif')
            ->assertSee('Santri Ba')
            ->assertDontSee('Mutaba’ah Harian')
            ->assertDontSee('tab=mutabaah', false);
    }

    public function test_halaman_mutabaah_kini_berdiri_sendiri(): void
    {
        $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->get(route('user.tahfidz.mutabaah.index', ['userId' => $this->teacher->id]))
            ->assertOk();
    }

    public function test_mutabaah_disimpan_lewat_ajax_tanpa_meninggalkan_halaman(): void
    {
        $response = $this->actingAs($this->admin)->withSession(['sa_school_id' => $this->school->id])
            ->postJson(route('user.tahfidz.mutabaah.store', ['userId' => $this->teacher->id]), [
                'tahfidz_group_id' => $this->group->id,
                'student_id' => $this->santriA->id,
                'record_date' => now()->toDateString(),
                'sholat_subuh' => 'berjamaah',
                'sholat_dzuhur' => 'berjamaah',
                'sholat_tahajud' => 1,
                'tilawah_halaman' => 3,
                'tikror_mandiri_halaman' => 2,
            ]);

        $response->assertOk()->assertJson(['saved' => true]);

        $record = \App\Models\TahfidzMutabaah::where('student_id', $this->santriA->id)->firstOrFail();
        $this->assertSame('berjamaah', $record->sholat_subuh);
        $this->assertSame(3, (int) $record->tilawah_halaman);
    }
}
