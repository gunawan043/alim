<?php

namespace App\Services;

use App\Models\LibraryGenre;
use App\Models\LibraryUnit;
use Illuminate\Support\Facades\DB;
use Throwable;

class LibraryGenreImportService
{
    public function import(LibraryUnit $unit, array $rows): array
    {
        $result = ['total' => 0, 'success' => 0, 'failed' => []];
        $headers = array_shift($rows) ?? [];
        $map = [];
        $aliases = [
            'nama' => 'nama', 'name' => 'nama', 'genre' => 'nama',
            'kode' => 'kode', 'code' => 'kode',
            'deskripsi' => 'deskripsi', 'description' => 'deskripsi', 'keterangan' => 'deskripsi',
            'status' => 'status', 'aktif' => 'status', 'is_active' => 'status',
        ];
        foreach ($headers as $index => $header) {
            $key = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header)));
            $key = preg_replace('/[^a-z0-9]+/', '_', $key);
            $key = trim($key, '_');
            if ($key !== '') {
                $map[$aliases[$key] ?? $key] = $index;
            }
        }

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = [];
            foreach ($map as $key => $column) {
                $data[$key] = trim((string) ($row[$column] ?? ''));
            }
            if (($data['nama'] ?? '') === '') {
                if (array_filter($data, fn ($value) => $value !== '') === []) {
                    continue;
                }
                $result['total']++;
                $result['failed'][] = ['row' => $rowNumber, 'reason' => 'Nama genre wajib diisi.'];

                continue;
            }
            $result['total']++;
            try {
                DB::transaction(function () use ($unit, $data) {
                    $existing = LibraryGenre::where('school_id', $unit->school_id)
                        ->where('name', $data['nama'])->first();
                    if ($existing) {
                        throw new \InvalidArgumentException('Genre '.$data['nama'].' sudah ada.');
                    }
                    LibraryGenre::create([
                        'school_id' => $unit->school_id,
                        'name' => $data['nama'],
                        'code' => $data['kode'] ?? null,
                        'description' => $data['deskripsi'] ?? null,
                        'is_active' => ! in_array(strtolower($data['status'] ?? '1'), ['0', 'nonaktif', 'inactive', 'tidak'], true),
                    ]);
                });
                $result['success']++;
            } catch (Throwable $e) {
                $result['failed'][] = ['row' => $rowNumber, 'reason' => $e->getMessage()];
            }
        }

        return $result;
    }
}
