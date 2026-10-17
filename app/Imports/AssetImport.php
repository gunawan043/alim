<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AssetImport implements ToCollection, WithHeadingRow, WithValidation
{
    protected string $userId;

    protected ?string $schoolContextId;

    protected ?string $forcedRoomId;

    protected ?AssetRoom $forcedRoom = null;

    protected array $errors = [];

    protected array $successes = [];

    protected array $categoryMap = [];

    protected array $roomMap = [];

    public function __construct(string $userId, ?string $schoolContextId = null, ?string $forcedRoomId = null)
    {
        $this->userId = $userId;
        $this->schoolContextId = $schoolContextId;
        $this->forcedRoomId = $forcedRoomId;

        // Pre-load forced room
        if ($forcedRoomId) {
            $this->forcedRoom = AssetRoom::find($forcedRoomId);
        }

        // Pre-load category map (name => id)
        $categories = AssetCategory::where('is_active', true)->get();
        foreach ($categories as $cat) {
            $this->categoryMap[strtolower($cat->name)] = $cat->id;
        }

        // Pre-load room map (room_name or room_code => room)
        $roomQuery = AssetRoom::where('is_active', true);
        if ($schoolContextId) {
            $roomQuery->where('school_id', $schoolContextId);
        }
        foreach ($roomQuery->get() as $room) {
            $this->roomMap[strtolower($room->room_name)] = $room;
            if ($room->room_code) {
                $this->roomMap['code:'.strtolower($room->room_code)] = $room;
            }
        }
    }

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();
        try {
            $created = 0;
            $skipped = 0;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2: 1 for 0-index, 1 for header row
                $rowData = $row->toArray();

                // Skip empty rows
                if (blank(trim($rowData['nama_aset'] ?? ''))) {
                    continue;
                }

                $result = $this->processRow($rowData, $rowNumber);
                if ($result === true) {
                    $created++;
                } else {
                    $skipped++;
                    $this->errors[] = $result;
                }
            }

            if ($created > 0) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            Log::info("AssetImport: {$created} created, {$skipped} skipped.");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = 'Error fatal: '.$e->getMessage();
            Log::error('AssetImport error: '.$e->getMessage());
        }
    }

    public function rules(): array
    {
        return [
            'nama_aset' => 'required|string|max:191',
            'kode_aset' => 'nullable|string|max:50',
            'ruang' => 'required|string|max:191',
            'kategori' => 'required|string|max:191',
            'merk' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'nomor_seri' => 'nullable|string|max:100',
            'warna' => 'nullable|string|max:50',
            'kondisi' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'tahun_perolehan' => 'nullable|integer|min:1900|max:2100',
            'harga_perolehan' => 'nullable|numeric|min:0',
            'sumber_perolehan' => 'nullable|string|max:100',
            'sumber_dana' => 'nullable|string|max:100',
            'spesifikasi' => 'nullable|string',
            'catatan' => 'nullable|string',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nama_aset.required' => 'Nama aset wajib diisi.',
            'ruang.required' => 'Nama/coded ruang wajib diisi.',
            'kategori.required' => 'Nama kategori wajib diisi.',
        ];
    }

    /**
     * Get import errors to display to user.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get success count.
     */
    public function getSuccessCount(): int
    {
        return count($this->successes);
    }

    // ─── Row Processing ───────────────────────────────────────────────────

    private function processRow(array $row, int $rowNumber): true|string
    {
        $assetName = trim($row['nama_aset'] ?? '');
        $categoryRef = trim($row['kategori'] ?? '');
        $assetCode = trim($row['kode_aset'] ?? '');

        // Find room — use forced room if set, otherwise lookup from column
        $room = $this->forcedRoom;
        if (! $room && blank($this->forcedRoomId)) {
            $roomRef = trim($row['ruang'] ?? '');
            $room = $this->findRoom($roomRef);
            if (! $room) {
                return "Baris {$rowNumber}: Ruang '{$roomRef}' tidak ditemukan.";
            }
        }
        if (! $room) {
            return "Baris {$rowNumber}: Ruang tidak ditemukan.";
        }

        // Find category
        $category = $this->findCategory($categoryRef);
        if (! $category) {
            return "Baris {$rowNumber}: Kategori '{$categoryRef}' tidak ditemukan.";
        }

        // Check duplicate code if provided
        if ($assetCode !== '') {
            $exists = Asset::where('asset_code', $assetCode)->exists();
            if ($exists) {
                return "Baris {$rowNumber}: Kode aset '{$assetCode}' sudah terdaftar.";
            }
        }

        $condition = $this->normalizeCondition($row['kondisi'] ?? 'baik');
        $status = $this->normalizeStatus($row['status'] ?? 'tersedia');
        $acqSource = $this->normalizeAcqSource($row['sumber_perolehan'] ?? '');
        $acqYear = ! empty($row['tahun_perolehan']) ? (int) $row['tahun_perolehan'] : null;
        $acqPrice = ! empty($row['harga_perolehan']) ? (float) $row['harga_perolehan'] : null;

        Asset::create([
            'school_id' => $room->school_id,
            'work_unit_id' => $room->work_unit_id,
            'room_id' => $room->id,
            'asset_category_id' => $category->id,
            'asset_code' => $assetCode ?: null,
            'asset_name' => $assetName,
            'brand' => trim($row['merk'] ?? '') ?: null,
            'model' => trim($row['model'] ?? '') ?: null,
            'serial_number' => trim($row['nomor_seri'] ?? '') ?: null,
            'color' => trim($row['warna'] ?? '') ?: null,
            'specification' => trim($row['spesifikasi'] ?? '') ?: null,
            'acquisition_year' => $acqYear,
            'acquisition_price' => $acqPrice,
            'acquisition_source' => $acqSource ?: null,
            'funding_source' => trim($row['sumber_dana'] ?? '') ?: null,
            'condition' => $condition,
            'status' => $status,
            'is_bookable' => true,
            'is_active' => true,
            'notes' => trim($row['catatan'] ?? '') ?: null,
            'created_by' => $this->userId,
        ]);

        $this->successes[] = $assetName;

        return true;
    }

    // ─── Lookup Helpers ───────────────────────────────────────────────────

    private function findRoom(string $ref): ?AssetRoom
    {
        $refLower = strtolower($ref);

        // Check by name first
        if (isset($this->roomMap[$refLower])) {
            return $this->roomMap[$refLower];
        }

        // Check by code
        if (isset($this->roomMap['code:'.$refLower])) {
            return $this->roomMap['code:'.$refLower];
        }

        // Fuzzy: partial match on room name
        foreach ($this->roomMap as $key => $room) {
            if (str_contains($key, $refLower) || str_contains(strtolower($room->room_name), $refLower)) {
                return $room;
            }
        }

        return null;
    }

    private function findCategory(string $ref): ?AssetCategory
    {
        $refLower = strtolower(trim($ref));

        if (isset($this->categoryMap[$refLower])) {
            return AssetCategory::find($this->categoryMap[$refLower]);
        }

        // Fuzzy match
        $found = AssetCategory::whereRaw('LOWER(name) LIKE ?', ["%{$refLower}%"])
            ->where('is_active', true)->first();

        return $found;
    }

    // ─── Normalize Helpers ────────────────────────────────────────────────

    private function normalizeCondition(?string $val): string
    {
        $map = [
            'baik' => 'baik',
            'bagus' => 'baik',
            'baik sekali' => 'baik',
            'rusak ringan' => 'rusak_ringan',
            'rusak_ringan' => 'rusak_ringan',
            'ringan' => 'rusak_ringan',
            'rusak sedang' => 'rusak_sedang',
            'rusak_sedang' => 'rusak_sedang',
            'sedang' => 'rusak_sedang',
            'rusak berat' => 'rusak_berat',
            'rusak_berat' => 'rusak_berat',
            'berat' => 'rusak_berat',
            'hilang' => 'hilang',
            'dihapus' => 'dihapus',
        ];
        $val = strtolower(trim($val ?? ''));

        return $map[$val] ?? 'baik';
    }

    private function normalizeStatus(?string $val): string
    {
        $map = [
            'tersedia' => 'tersedia',
            'tersedia' => 'tersedia',
            'tersedia' => 'tersedia',
            'dipinjam' => 'dipinjam',
            'pinjam' => 'dipinjam',
            'dalam perbaikan' => 'dalam_perbaikan',
            'dalam_perbaikan' => 'dalam_perbaikan',
            'perbaikan' => 'dalam_perbaikan',
            'dihapus' => 'dihapus',
        ];
        $val = strtolower(trim($val ?? ''));

        return $map[$val] ?? 'tersedia';
    }

    private function normalizeAcqSource(?string $val): ?string
    {
        if (blank($val)) {
            return null;
        }
        $map = [
            'pembelian' => 'pembelian',
            'hibah' => 'hibah',
            'sumbangan' => 'sumbangan',
            'pengadaan bos' => 'pengadaan_bos',
            'pengadaan_bos' => 'pengadaan_bos',
            'bos' => 'pengadaan_bos',
            'bantuan pemerintah' => 'bantuan_pemerintah',
            'bantuan_pemerintah' => 'bantuan_pemerintah',
            'pemerintah' => 'bantuan_pemerintah',
            'lainnya' => 'lainnya',
            'lain lain' => 'lainnya',
        ];
        $val = strtolower(trim($val));

        return $map[$val] ?? 'lainnya';
    }
}
