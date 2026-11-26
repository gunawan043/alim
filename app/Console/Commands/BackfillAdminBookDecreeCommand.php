<?php

namespace App\Console\Commands;

use App\Models\InstitutionDecree;
use App\Models\TeacherAdminBook;
use Illuminate\Console\Command;

/**
 * Isi decree_id pada buku nilai lama berdasarkan SK yang menerbitkan guru
 * mapel tersebut. Buku lama tidak punya ikatan langsung ke SK, sehingga
 * semester-nya hanya bisa dipastikan setelah dipetakan.
 *
 * Bawaan = laporan (dry-run). Tambahkan --apply untuk menulis.
 */
class BackfillAdminBookDecreeCommand extends Command
{
    protected $signature = 'nilai:backfill-decree
                            {--apply : Simpan hasil pemetaan (tanpa ini hanya laporan)}
                            {--school= : Batasi ke satu sekolah (id)}';

    protected $description = 'Petakan buku nilai lama ke SK pembagian tugas yang berlaku';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $schoolId = $this->option('school');

        // SK yang mungkin menjadi sumber: semua SK aktif per sekolah.
        $decrees = InstitutionDecree::query()
            ->where('status', InstitutionDecree::STATUS_ACTIVE)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get()
            ->keyBy('id');

        if ($decrees->isEmpty()) {
            $this->warn('Tidak ada SK aktif untuk dipakai sebagai sumber pemetaan.');

            return self::SUCCESS;
        }

        $rows = TeacherAdminBook::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNull('decree_id')
            ->get(['id', 'school_id', 'subject_id', 'study_group_id', 'academic_year_id', 'semester']);

        $mapped = 0;
        $ambiguous = 0;
        $unmapped = 0;
        $updates = [];

        foreach ($rows as $book) {
            $candidates = $decrees->where('school_id', $book->school_id)
                ->where('academic_year_id', $book->academic_year_id);

            // Satu sekolah bisa punya beberapa SK aktif pada TA yang sama
            // (SK ganjil & genap). Semester buku dipakai untuk memisahkan.
            $service = app(\App\Services\DecreePeriodService::class);
            $match = $candidates->first(
                fn (InstitutionDecree $d) => $service->semesterFromDecree($d) === $book->semester
            );

            if (! $match && $candidates->count() === 1) {
                $match = $candidates->first();
            }

            if ($match) {
                $updates[$match->id] = ($updates[$match->id] ?? 0) + 1;
                $mapped++;

                if ($apply) {
                    TeacherAdminBook::whereKey($book->id)->update(['decree_id' => $match->id]);
                }

                continue;
            }

            if ($candidates->count() > 1) {
                $ambiguous++;
            } else {
                $unmapped++;
            }
        }

        $this->table(['Keterangan', 'Jumlah'], [
            ['Buku tanpa SK', $rows->count()],
            ['Bisa dipetakan', $mapped],
            ['Ambigu (SK bersaing)', $ambiguous],
            ['Tidak ada SK cocok', $unmapped],
        ]);

        foreach ($updates as $decreeId => $count) {
            $this->line(sprintf('  %s → %d buku', $decrees[$decreeId]->decree_number, $count));
        }

        if (! $apply) {
            $this->newLine();
            $this->info('Mode laporan. Jalankan ulang dengan --apply untuk menyimpan.');
        } else {
            $this->newLine();
            $this->info("Selesai: {$mapped} buku diperbarui.");
        }

        return self::SUCCESS;
    }
}
