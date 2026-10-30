<?php

namespace App\Services\Humas;

use App\Models\AcademicYear;
use App\Models\Dormitory;
use App\Models\HumasAnnouncement;
use App\Models\Kaldik;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Menyinkronkan tanggal kegiatan pada Info/Broadcast ke Kalender Pendidikan.
 *
 * Tipe kegiatan dipilih otomatis dari kata kunci judul/isi, lalu warna
 * mengikuti legenda Kaldik yang sudah menjadi ketentuan aplikasi.
 */
class AnnouncementCalendarService
{
    public function sync(HumasAnnouncement $announcement, ?User $actor = null): void
    {
        $source = Kaldik::withTrashed()
            ->where('source_type', HumasAnnouncement::class)
            ->where('source_id', $announcement->id);

        if (! $announcement->activity_start_date
            || ! $announcement->activity_end_date
            || $announcement->status !== 'published') {
            $source->forceDelete();

            return;
        }

        $units = $this->targetUnits($announcement);

        if ($units->isEmpty()) {
            $source->forceDelete();

            return;
        }

        // Buat ulang agar perubahan target/tipe/warna tidak menyisakan
        // baris lama atau baris yang sudah soft-deleted.
        $source->forceDelete();

        $type = $this->classify(
            trim($announcement->title.' '.strip_tags((string) $announcement->content))
        );
        $color = function_exists('kaldik_default_color')
            ? kaldik_default_color($type)
            : '#64748B';
        $actorId = $actor?->id ?? $announcement->created_by;
        $description = Str::limit(
            'Otomatis dari pengumuman: '.$announcement->title
                .($announcement->excerpt ? "\n".$announcement->excerpt : ''),
            2000,
            ''
        );

        foreach ($units as $unit) {
            $academicYear = $this->academicYear($announcement->activity_start_date);

            Kaldik::create([
                'source_type' => HumasAnnouncement::class,
                'source_id' => $announcement->id,
                'work_unit_id' => $unit['work_unit_id'],
                'name' => $announcement->title,
                'category' => Kaldik::CATEGORY_AGENDA,
                'visibility' => Kaldik::VISIBILITY_SATUAN,
                'semester' => $academicYear?->semester
                    ?? ($announcement->activity_start_date->month >= 7 ? 'ganjil' : 'genap'),
                'academic_year_id' => $academicYear?->id,
                'owner_user_id' => null,
                'created_by' => $actorId,
                'type' => $type,
                'color' => $color,
                'start_date' => $announcement->activity_start_date,
                'end_date' => $announcement->activity_end_date,
                'description' => $description,
                'is_active' => true,
            ]);
        }
    }

    public function classify(string $text): string
    {
        $text = Str::lower($text);

        $rules = [
            Kaldik::TYPE_LIBUR => [
                'libur', 'cuti bersama', 'hari raya', 'idul fitri', 'idul adha',
                'natal', 'tahun baru', 'wafat isa', 'nyepi', 'waisak', 'imlek',
            ],
            Kaldik::TYPE_UJIAN => [
                'ujian', 'asesmen', 'sumatif', 'pts', 'pas', 'uts', 'uas',
                'try out', 'tryout', 'penilaian akhir', 'ujian sekolah',
            ],
            Kaldik::TYPE_MID_SEMESTER => [
                'mid semester', 'mid-semester', 'tengah semester', 'mid semester',
            ],
            Kaldik::TYPE_HARI_EFEKTIF => [
                'hari efektif', 'pekan efektif',
            ],
            Kaldik::TYPE_TAHUNAN => [
                'tahun ajaran', 'awal tahun', 'tahunan', 'mpls', 'orientasi siswa',
                'wisuda', 'kelulusan', 'penerimaan rapor',
            ],
            Kaldik::TYPE_PROGRAM_BERSAMA => [
                'program bersama', 'kerja sama', 'kolaborasi', 'parenting',
                'seminar', 'workshop', 'pelatihan', 'bimbingan teknis', 'bimtek',
            ],
            Kaldik::TYPE_KEGIATAN => [
                'festival', 'lomba', 'kegiatan', 'acara', 'peringatan', 'pentas',
                'bazar', 'bakti sosial', 'sosialisasi', 'rapat', 'kunjungan',
                'studi tour', 'outbound', 'kemah', 'rihlah', 'ziarah', 'haul',
                'dies natalis', 'porseni', 'class meeting',
            ],
        ];

        foreach ($rules as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $type;
                }
            }
        }

        return Kaldik::TYPE_KEGIATAN;
    }

    /**
     * @return Collection<int, array{work_unit_id: string, school_id: string|null}>
     */
    private function targetUnits(HumasAnnouncement $announcement): Collection
    {
        $units = collect();

        $addSchool = function (?School $school) use ($units) {
            if ($school?->work_unit_id) {
                $units->push([
                    'work_unit_id' => (string) $school->work_unit_id,
                    'school_id' => (string) $school->id,
                ]);
            }
        };

        $addDormitory = function (?Dormitory $dormitory) use ($units, $addSchool) {
            if (! $dormitory) {
                return;
            }

            if ($dormitory->work_unit_id) {
                $units->push([
                    'work_unit_id' => (string) $dormitory->work_unit_id,
                    'school_id' => (string) $dormitory->school_id,
                ]);

                return;
            }

            $addSchool($dormitory->school);
        };

        switch ($announcement->target_type) {
            case 'school':
                $addSchool($announcement->school);
                break;

            case 'class':
                $addSchool($announcement->studyGroup?->school);
                break;

            case 'dormitory':
                $addDormitory($announcement->dormitory);
                break;

            case 'global':
                School::where('is_active', true)->with('workUnit')->get()
                    ->each(fn (School $school) => $addSchool($school));
                Dormitory::where('is_active', true)->with('school')->get()
                    ->each(fn (Dormitory $dormitory) => $addDormitory($dormitory));
                break;
        }

        return $units->unique(fn (array $unit) => $unit['work_unit_id'].'|'.$unit['school_id'])->values();
    }

    private function academicYear($date): ?AcademicYear
    {
        // Tahun ajaran bersifat global (tidak lagi per sekolah).
        return AcademicYear::where('is_active', true)
            ->first()
            ?? AcademicYear::whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->latest('start_date')
                ->first()
            ?? AcademicYear::latest('start_date')->first();
    }
}
