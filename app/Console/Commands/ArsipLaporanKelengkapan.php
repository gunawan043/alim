<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Services\Arsip\ArsipInventoryService;
use App\Services\Arsip\ArsipRegistry;
use Illuminate\Console\Command;

/**
 * Laporan kelengkapan arsip per tahun ajaran — dipakai menjelang penutupan
 * periode untuk memastikan data penting sudah terisi sebelum dikunci.
 */
class ArsipLaporanKelengkapan extends Command
{
    protected $signature = 'arsip:laporan-kelengkapan
                            {academicYearId? : ID tahun ajaran (default: TA aktif)}
                            {--kosong : Tampilkan hanya modul yang belum terisi}';

    protected $description = 'Laporan kelengkapan data arsip per modul untuk satu tahun ajaran.';

    public function handle(ArsipInventoryService $inventory, ArsipRegistry $registry): int
    {
        $academicYearId = $this->argument('academicYearId')
            ?? AcademicYear::where('is_active', true)->value('id');

        if (! $academicYearId) {
            $this->error('Tidak ada tahun ajaran aktif dan tidak ada ID yang diberikan.');

            return self::FAILURE;
        }

        $academicYear = AcademicYear::find($academicYearId);

        if (! $academicYear) {
            $this->error("Tahun ajaran {$academicYearId} tidak ditemukan.");

            return self::FAILURE;
        }

        $summary = collect($inventory->summary($academicYearId, null));
        $onlyEmpty = (bool) $this->option('kosong');
        $rows = $summary
            ->when($onlyEmpty, fn ($collection) => $collection->where('count', 0))
            ->map(fn ($module) => [$module['label'], number_format($module['count'])])
            ->values()
            ->all();

        $status = $academicYear->isLocked() ? 'TERKUNCI (arsip)' : ($academicYear->is_active ? 'AKTIF' : 'TERBUKA');

        $this->info("Kelengkapan Arsip — {$academicYear->name} ({$academicYear->semester_text}) [{$status}]");

        if (empty($rows)) {
            $this->info($onlyEmpty
                ? 'Semua modul sudah terisi.'
                : 'Belum ada data pada periode ini.');

            return self::SUCCESS;
        }

        $this->table(['Modul', 'Jumlah Data'], $rows);

        $empty = $summary->where('count', 0);
        $this->line(sprintf(
            'Terisi: %d/%d modul · Dokumen arsip: %d · Modul belum berperiode: %d',
            $summary->where('count', '>', 0)->count(),
            $summary->count(),
            $inventory->documentCount($academicYearId, null),
            count($registry->unsupportedModules()),
        ));

        if (! $onlyEmpty && $empty->isNotEmpty()) {
            $this->warn('Modul belum terisi: '.$empty->pluck('label')->implode(', '));
        }

        return self::SUCCESS;
    }
}
