<?php

namespace App\Console\Commands;

use App\Models\DormitoryResident;
use App\Models\StudentBoardingStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sinkronkan `student_boarding_statuses` dengan data penghuni asrama.
 *
 * Tabel status hanya terisi saat santri keluar (izin/UKS/kegiatan), sehingga
 * santri yang menetap tidak pernah punya baris IN_DORM. Akibatnya dashboard
 * dan laporan yang menghitung "masih di asrama" selalu 0.
 *
 * Perintah ini mengisi baris IN_DORM untuk setiap penghuni aktif dan
 * melengkapi dormitory_id/room_id pada baris yang belum punya lokasi.
 * Default dry-run; gunakan --commit untuk menulis.
 */
class BoardingSyncStatusCommand extends Command
{
    protected $signature = 'boarding:sync-status
                            {--commit : Tulis perubahan ke database (default hanya laporan)}
                            {--dormitory= : Batasi ke satu dormitory_id}';

    protected $description = 'Isi status IN_DORM untuk penghuni aktif dan lengkapi lokasi kamar pada status boarding.';

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');
        $dormitoryId = $this->option('dormitory');

        $residents = DormitoryResident::query()
            ->where('is_active', true)
            ->when($dormitoryId, fn ($q) => $q->where('dormitory_id', $dormitoryId))
            ->get(['id', 'student_id', 'dormitory_id', 'room_id', 'check_in_date']);

        $statuses = StudentBoardingStatus::query()
            ->whereIn('student_id', $residents->pluck('student_id')->all() ?: ['-'])
            ->get()
            ->keyBy('student_id');

        $willInsert = 0;
        $willFillLocation = 0;
        $skipped = 0;
        $rows = [];

        foreach ($residents as $resident) {
            $status = $statuses->get($resident->student_id);

            if (! $status) {
                $willInsert++;
                $rows[] = ['insert', $resident->student_id, 'IN_DORM', $resident->dormitory_id, $resident->room_id];

                continue;
            }

            if (($status->dormitory_id === null || $status->room_id === null)
                && ($resident->dormitory_id || $resident->room_id)) {
                $willFillLocation++;
                $rows[] = ['lengkapi', $resident->student_id, $status->status, $resident->dormitory_id, $resident->room_id];

                continue;
            }

            $skipped++;
        }

        $this->table(
            ['Aksi', 'student_id', 'Status', 'dormitory_id', 'room_id'],
            array_slice($rows, 0, 20)
        );

        $this->newLine();
        $this->line('Penghuni aktif        : '.number_format($residents->count()));
        $this->line('Akan dibuat IN_DORM   : '.number_format($willInsert));
        $this->line('Lengkapi lokasi kamar : '.number_format($willFillLocation));
        $this->line('Sudah lengkap         : '.number_format($skipped));

        if (! $commit) {
            $this->warn('Dry-run. Tidak ada data yang ditulis. Jalankan ulang dengan --commit.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($residents, $statuses) {
            $now = now();

            foreach ($residents as $resident) {
                $status = $statuses->get($resident->student_id);

                if (! $status) {
                    StudentBoardingStatus::create([
                        'student_id' => $resident->student_id,
                        'status' => StudentBoardingStatus::IN_DORM,
                        'dormitory_id' => $resident->dormitory_id,
                        'room_id' => $resident->room_id,
                        'effective_from' => $resident->check_in_date ?? $now,
                        'last_event_at' => $now,
                    ]);

                    continue;
                }

                if ($status->dormitory_id === null || $status->room_id === null) {
                    $status->dormitory_id ??= $resident->dormitory_id;
                    $status->room_id ??= $resident->room_id;
                    $status->save();
                }
            }
        });

        $this->info('Selesai. Status boarding sudah disinkronkan.');

        return self::SUCCESS;
    }
}
