<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Services\Arsip\ArsipInventoryService;
use Illuminate\Console\Command;

/**
 * Pengingat penutupan periode: menandai tahun ajaran yang sudah/sedang
 * berakhir namun belum dikunci, beserta ringkasan kelengkapan arsipnya.
 *
 * Tidak mengunci otomatis — penutupan tetap keputusan pengguna berwenang
 * (audit + alasan) demi keamanan data.
 */
class ArsipPengingatPenutupan extends Command
{
    protected $signature = 'arsip:pengingat-penutupan
                            {--hari=30 : Ambang hari sebelum akhir periode}
                            {--kunci-terlambat : Tampilkan juga periode yang sudah terkunci}';

    protected $description = 'Pengingat tahun ajaran yang mendekati/sudah melewati akhir periode dan belum dikunci.';

    public function handle(ArsipInventoryService $inventory): int
    {
        $days = max(0, (int) $this->option('hari'));
        $today = now()->startOfDay();
        $threshold = $today->copy()->addDays($days);

        $years = AcademicYear::query()
            ->whereNotNull('end_date')
            ->when(! $this->option('kunci-terlambat'), fn ($q) => $q->whereNull('locked_at'))
            ->orderBy('end_date')
            ->get();

        $rows = [];

        foreach ($years as $year) {
            $isPast = $year->end_date->lt($today);
            $isNear = ! $isPast && $year->end_date->lte($threshold);

            if (! $isPast && ! $isNear) {
                continue;
            }

            $overview = $inventory->overview($year->id, null);
            $status = $year->isLocked()
                ? 'TERKUNCI'
                : ($year->is_active ? 'AKTIF — belum dikunci' : 'TERBUKA — belum dikunci');

            $rows[] = [
                $year->name.' ('.$year->semester_text.')',
                $year->end_date->format('d M Y'),
                $isPast ? 'SUDAH BERAKHIR' : 'SEGERA BERAKHIR',
                $status,
                $overview['filled'].'/'.$overview['modules'].' modul',
                $overview['documents'],
            ];
        }

        if (empty($rows)) {
            $this->info('Tidak ada periode yang mendekati/sudah melewati akhir dan perlu ditutup.');

            return self::SUCCESS;
        }

        $this->warn('Pengingat Arsip — periode yang perlu ditinjau:');
        $this->table(
            ['Tahun Ajaran', 'Berakhir', 'Kondisi', 'Status Arsip', 'Modul Terisi', 'Dokumen'],
            $rows
        );

        $this->line('Tindak lanjut: buka halaman Inventaris Arsip → periksa modul kosong → Tutup & Kunci Periode.');

        return self::SUCCESS;
    }
}
