<?php

namespace App\Console\Commands;

use App\Models\ArchiveDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Verifikasi integritas seluruh dokumen arsip (hash SHA-256 + keberadaan berkas).
 * Dijadwalkan mingguan; mengembalikan exit code gagal bila ada masalah.
 */
class ArsipVerifikasiDokumen extends Command
{
    protected $signature = 'arsip:verifikasi-dokumen
                            {--academic-year= : Batasi ke satu tahun ajaran}
                            {--limit=0 : Batasi jumlah dokumen (0 = semua)}';

    protected $description = 'Verifikasi berkas arsip: keberadaan file dan kecocokan hash SHA-256.';

    public function handle(): int
    {
        $query = ArchiveDocument::query()->orderBy('academic_year_id')->orderBy('created_at');

        if ($this->option('academic-year')) {
            $query->where('academic_year_id', $this->option('academic-year'));
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = 0;
        $ok = 0;
        $issues = [];

        foreach ($query->cursor() as $document) {
            $total++;

            if (! Storage::disk('local')->exists($document->file_path)) {
                $issues[] = [$document->id, $document->title, 'BERKAS HILANG'];

                continue;
            }

            $contents = Storage::disk('local')->get($document->file_path);
            $hash = hash('sha256', $contents);

            if (! hash_equals((string) $document->file_hash, $hash)) {
                $issues[] = [$document->id, $document->title, 'HASH TIDAK COCOK'];

                continue;
            }

            $ok++;
        }

        $this->info("Verifikasi arsip: {$total} dokumen diperiksa, {$ok} valid, ".count($issues).' bermasalah.');

        if ($issues !== []) {
            $this->table(['ID', 'Dokumen', 'Masalah'], $issues);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
