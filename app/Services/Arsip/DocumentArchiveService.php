<?php

namespace App\Services\Arsip;

use App\Models\ArchiveDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyimpanan dokumen arsip (privat) + verifikasi integritas SHA-256.
 */
class DocumentArchiveService
{
    public const DISK = 'local';

    /**
     * @param  array<string, mixed>  $meta
     */
    public function store(array $meta, string $contents, string $extension = 'json'): ArchiveDocument
    {
        $academicYearSlug = $meta['academic_year_id'] ? substr((string) $meta['academic_year_id'], 0, 8) : 'umum';
        $filename = now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $path = sprintf('arsip/%s/%s/%s', $meta['type'] ?? 'lainnya', $academicYearSlug, $filename);

        Storage::disk(self::DISK)->makeDirectory(dirname($path));
        Storage::disk(self::DISK)->put($path, $contents);

        return ArchiveDocument::create([
            'school_id' => $meta['school_id'] ?? null,
            'academic_year_id' => $meta['academic_year_id'] ?? null,
            'semester' => $meta['semester'] ?? null,
            'type' => $meta['type'],
            'title' => $meta['title'],
            'file_path' => $path,
            'file_hash' => hash('sha256', $contents),
            'file_size' => strlen($contents),
            'record_type' => $meta['record_type'] ?? null,
            'record_id' => $meta['record_id'] ?? null,
            'student_id' => $meta['student_id'] ?? null,
            'study_group_id' => $meta['study_group_id'] ?? null,
            'generated_by' => $meta['generated_by'] ?? null,
            'notes' => $meta['notes'] ?? null,
        ]);
    }

    public function exists(ArchiveDocument $document): bool
    {
        return Storage::disk(self::DISK)->exists($document->file_path);
    }

    public function contents(ArchiveDocument $document): string
    {
        abort_unless($this->exists($document), 404, 'Berkas arsip tidak ditemukan pada penyimpanan.');

        return Storage::disk(self::DISK)->get($document->file_path);
    }

    public function verify(ArchiveDocument $document): bool
    {
        return hash_equals((string) $document->file_hash, hash('sha256', $this->contents($document)));
    }

    public function download(ArchiveDocument $document): StreamedResponse
    {
        abort_unless($this->exists($document), 404, 'Berkas arsip tidak ditemukan pada penyimpanan.');

        return Storage::disk(self::DISK)->download(
            $document->file_path,
            Str::slug($document->title).'-'.$document->short_hash.'.'.pathinfo($document->file_path, PATHINFO_EXTENSION)
        );
    }
}
