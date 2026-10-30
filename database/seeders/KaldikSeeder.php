<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Kaldik;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Kaldik Pondok resmi tahun ajaran 2026/2027 dan awal 2027/2028.
 *
 * Seeder ini mengganti seluruh Kaldik Pondok lama. Agenda kegiatan dari
 * Info/Broadcast (category=agenda) tidak dihapus.
 */
class KaldikSeeder extends Seeder
{
    /**
     * @var array<int, array{0:string,1:string}>
     */
    private array $events = [
        ['2026-07-06', '2026-07-09', 'Workshop Pendidikan'],
        ['2026-07-10', '2026-07-10', 'Serah Terima Santri Baru'],
        ['2026-07-11', '2026-07-12', 'Jadwal Masuk Pondok'],
        ['2026-07-13', '2026-07-13', 'Hari Pertama KBM'],
        ['2026-07-13', '2026-07-18', 'MPLS'],
        ['2026-08-17', '2026-08-17', 'Libur Nasional (Hari Kemerdekaan RI)'],
        ['2026-09-14', '2026-09-23', 'STS (Sumatif Tengah Semester) Ganjil'],
        ['2026-09-25', '2026-09-25', 'Pembagian Rapor STS Ganjil'],
        ['2026-10-05', '2026-10-07', "Audisi Festival Internal (Al-Usbu' Atsaqoofi)"],
        ['2026-10-12', '2026-10-12', 'Pengumuman Festival Internal'],
        ['2026-10-19', '2026-10-19', 'Start Promosi SPMB'],
        ['2026-11-01', '2026-11-16', 'Pendaftaran SPMB Tahap Alumni'],
        ['2026-11-09', '2026-11-13', "Final Festival Internal (Al-Usbu' Atsaqoofi)"],
        ['2026-11-21', '2026-11-21', 'Tes SPMB Tahap Alumni'],
        ['2026-11-23', '2026-12-12', 'SAS Ganjil Jenjang MA/SMA'],
        ['2026-11-30', '2026-12-12', 'SAS Ganjil Jenjang SD & SMP'],
        ['2026-12-01', '2026-12-16', 'Pendaftaran SPMB Tahap Non Alumni'],
        ['2026-12-18', '2026-12-19', 'Pembagian Rapor Semester Ganjil'],
        ['2026-12-21', '2027-01-09', 'Libur Semester Ganjil'],
        ['2026-12-25', '2026-12-26', 'Tes SPMB Tahap Non Alumni'],
        ['2027-01-10', '2027-01-10', 'Jadwal Masuk Pondok Semester Genap'],
        ['2027-01-11', '2027-01-11', 'Hari Pertama KBM'],
        ['2027-01-28', '2027-01-30', 'Festival Eksternal Bali Nusra'],
        ['2027-02-08', '2027-02-09', 'Perkiraan Awal Ramadhan'],
        ['2027-02-22', '2027-03-20', 'Libur Ramadhan dan Idul Fitri'],
        ['2027-03-21', '2027-03-21', 'Jadwal Masuk Pondok'],
        ['2027-03-22', '2027-03-22', 'Hari Pertama Belajar Setelah Libur Idul Fitri'],
        ['2027-03-24', '2027-04-02', 'STS Genap'],
        ['2027-03-29', '2027-04-10', 'Perkiraan Ujian Akhir MA/SMA'],
        ['2027-04-05', '2027-04-08', 'TKA Jenjang SD/SMP'],
        ['2027-05-03', '2027-05-08', 'Perkiraan AAS Jenjang SD/SMP'],
        ['2027-05-16', '2027-05-16', 'Perkiraan Hari Raya Idul Adha'],
        ['2027-05-24', '2027-06-12', 'SAS Genap Jenjang MA/SMA'],
        ['2027-06-01', '2027-06-13', 'SAS Genap Jenjang SD & SMP'],
        ['2027-06-18', '2027-06-19', 'Pembagian Rapor Semester Genap'],
        ['2027-06-21', '2027-07-10', 'Libur Akhir Semester Genap'],
        ['2027-07-10', '2027-07-10', 'Serah Terima Santri Baru (TA Selanjutnya)'],
        ['2027-07-10', '2027-07-11', 'Jadwal Masuk Pondok (TA Selanjutnya)'],
        ['2027-07-12', '2027-07-12', 'Hari Pertama KBM (TA Selanjutnya)'],
        ['2027-07-12', '2027-07-17', 'MPLS (TA Selanjutnya)'],
    ];

    public function run(): void
    {
        Kaldik::withTrashed()
            ->where('category', Kaldik::CATEGORY_KALDIK)
            ->forceDelete();

        $creatorId = User::where('is_system_admin', true)->value('id');

        foreach ($this->events as [$start, $end, $name]) {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);
            $semester = $startDate->month >= 7 ? 'ganjil' : 'genap';
            $yearStart = $startDate->month >= 7 ? $startDate->year : $startDate->year - 1;
            $yearName = $yearStart.'/'.($yearStart + 1);

            $academicYear = AcademicYear::firstOrCreate(
                ['name' => $yearName, 'semester' => $semester],
                [
                    'is_active' => false,
                    'start_date' => $semester === 'ganjil'
                        ? $yearStart.'-07-01'
                        : ($yearStart + 1).'-01-01',
                    'end_date' => $semester === 'ganjil'
                        ? $yearStart.'-12-31'
                        : ($yearStart + 1).'-06-30',
                ]
            );

            $type = $this->typeFor($name);

            Kaldik::create([
                'name' => $name,
                'category' => Kaldik::CATEGORY_KALDIK,
                'visibility' => Kaldik::VISIBILITY_SATUAN,
                'semester' => $semester,
                'academic_year_id' => $academicYear->id,
                'work_unit_id' => null,
                'owner_user_id' => null,
                'created_by' => $creatorId,
                'type' => $type,
                'color' => function_exists('kaldik_default_color')
                    ? kaldik_default_color($type)
                    : '#64748B',
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'description' => 'Kaldik Pondok '.$yearName,
                'is_active' => true,
            ]);
        }

        $this->command?->info('KaldikSeeder: '.count($this->events).' agenda Kaldik Pondok dibuat.');
    }

    private function typeFor(string $name): string
    {
        $name = mb_strtolower($name);

        // Cek kegiatan rutin lebih dulu agar "Hari Pertama ... setelah libur"
        // tidak salah dianggap hari libur.
        if (str_contains($name, 'mpls')
            || str_contains($name, 'hari pertama')
            || str_contains($name, 'jadwal masuk')
            || str_contains($name, 'serah terima')
            || str_contains($name, 'pembagian rapor')) {
            return Kaldik::TYPE_TAHUNAN;
        }

        if (str_contains($name, 'libur')
            || str_contains($name, 'idul adha')
            || str_contains($name, 'idul fitri')) {
            return Kaldik::TYPE_LIBUR;
        }

        if (str_contains($name, 'sts')
            || str_contains($name, 'sas')
            || str_contains($name, 'ujian')
            || str_contains($name, 'tka')
            || str_contains($name, 'aas')
            || str_contains($name, 'tes spmb')) {
            return Kaldik::TYPE_UJIAN;
        }

        if (str_contains($name, 'workshop')
            || str_contains($name, 'pelatihan')
            || str_contains($name, 'seminar')) {
            return Kaldik::TYPE_PROGRAM_BERSAMA;
        }

        return Kaldik::TYPE_KEGIATAN;
    }
}
