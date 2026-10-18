<?php

namespace App\Imports;

use App\Models\AssetBuilding;
use App\Models\AssetCategory;
use App\Models\AssetRoom;
use App\Models\School;
use App\Models\Sparepart;
use App\Models\Vendor;
use App\Models\WorkUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SarprasDatasetImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    private array $errors = [];

    private int $created = 0;

    private int $updated = 0;

    private ?School $school = null;

    public function __construct(
        private readonly string $dataset,
        private readonly ?string $schoolId = null,
    ) {}

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;

                try {
                    $result = $this->processRow($row->toArray(), $rowNumber);
                    if ($result === 'created') {
                        $this->created++;
                    } elseif ($result === 'updated') {
                        $this->updated++;
                    }
                } catch (\Throwable $e) {
                    $this->errors[] = "Baris {$rowNumber}: ".$e->getMessage();
                    Log::warning('SarprasDatasetImport row failed', [
                        'dataset' => $this->dataset,
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = 'Import gagal: '.$e->getMessage();
            Log::error('SarprasDatasetImport failed', ['dataset' => $this->dataset, 'error' => $e->getMessage()]);
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getCreatedCount(): int
    {
        return $this->created;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }

    private function processRow(array $row, int $rowNumber): string
    {
        return match ($this->dataset) {
            'gedung' => $this->importBuilding($row, $rowNumber),
            'ruang' => $this->importRoom($row, $rowNumber),
            'kategori' => $this->importCategory($row, $rowNumber),
            'vendor' => $this->importVendor($row, $rowNumber),
            'sparepart' => $this->importSparepart($row, $rowNumber),
            default => throw new \RuntimeException('Dataset tidak mendukung import.'),
        };
    }

    private function importBuilding(array $row, int $rowNumber): string
    {
        $name = trim((string) ($row['nama_gedung'] ?? ''));
        if ($name === '') {
            throw new \RuntimeException('Nama gedung wajib diisi.');
        }

        $code = $this->nullable($row['kode_gedung'] ?? null);
        $school = $this->resolveSchool($row);
        $workUnitId = $school->work_unit_id ?: WorkUnit::query()->value('id');
        $attributes = [
            'school_id' => $school->id,
            'work_unit_id' => $workUnitId,
            'building_code' => $code ?: $this->nextCode('GDG', AssetBuilding::class, 'building_code'),
            'building_name' => $name,
            'building_type' => $this->choice($row['jenis_gedung'] ?? null, AssetBuilding::BUILDING_TYPE_OPTIONS, 'lainnya'),
            'total_floors' => $this->integer($row['jumlah_lantai'] ?? null),
            'building_area' => $this->decimal($row['luas_m2'] ?? null),
            'build_year' => $this->integer($row['tahun_bangun'] ?? null),
            'structure_condition' => $this->choice($row['kondisi_struktur'] ?? null, AssetBuilding::CONDITION_OPTIONS, 'baik'),
            'ownership_status' => $this->choice($row['status_kepemilikan'] ?? null, AssetBuilding::OWNERSHIP_OPTIONS, 'milik_sendiri'),
            'is_active' => $this->boolean($row['aktif'] ?? null, true),
            'created_by' => auth()->id(),
        ];

        $building = $code
            ? AssetBuilding::where('building_code', $code)->first()
            : AssetBuilding::where('school_id', $school->id)->where('building_name', $name)->first();

        if ($building) {
            $building->update($attributes);

            return 'updated';
        }

        AssetBuilding::create($attributes);

        return 'created';
    }

    private function importRoom(array $row, int $rowNumber): string
    {
        $name = trim((string) ($row['nama_ruang'] ?? ''));
        if ($name === '') {
            throw new \RuntimeException('Nama ruang wajib diisi.');
        }

        $school = $this->resolveSchool($row);
        $building = $this->resolveBuilding($row, $school);
        if (! $building) {
            throw new \RuntimeException('Gedung tidak ditemukan. Isi kode_gedung atau nama_gedung yang valid.');
        }

        $code = $this->nullable($row['kode_ruang'] ?? null);
        $attributes = [
            'school_id' => $school->id,
            'work_unit_id' => $building->work_unit_id ?: ($school->work_unit_id ?: WorkUnit::query()->value('id')),
            'building_id' => $building->id,
            'room_code' => $code ?: null,
            'room_name' => $name,
            'floor' => $this->integer($row['lantai'] ?? null),
            'room_type' => $this->choice($row['jenis_ruang'] ?? null, AssetRoom::ROOM_TYPE_OPTIONS, 'kelas'),
            'capacity' => $this->integer($row['kapasitas'] ?? null),
            'room_area' => $this->decimal($row['luas_m2'] ?? null),
            'condition' => $this->choice($row['kondisi'] ?? null, AssetRoom::CONDITION_OPTIONS, 'baik'),
            'is_active' => $this->boolean($row['aktif'] ?? null, true),
        ];

        $room = $code
            ? AssetRoom::where('room_code', $code)->first()
            : AssetRoom::where('building_id', $building->id)->where('room_name', $name)->first();

        if ($room) {
            $room->update($attributes);

            return 'updated';
        }

        AssetRoom::create($attributes);

        return 'created';
    }

    private function importCategory(array $row, int $rowNumber): string
    {
        $name = trim((string) ($row['nama_kategori'] ?? ''));
        if ($name === '') {
            throw new \RuntimeException('Nama kategori wajib diisi.');
        }

        $code = $this->nullable($row['kode_kategori'] ?? null);
        $attributes = [
            'code' => $code,
            'name' => $name,
            'asset_type' => $this->choice($row['tipe_aset'] ?? null, AssetCategory::ASSET_TYPE_OPTIONS, 'bergerak'),
            'depreciation_years' => $this->integer($row['masa_pakai_tahun'] ?? null),
            'is_active' => $this->boolean($row['aktif'] ?? null, true),
        ];

        $category = $code
            ? AssetCategory::where('code', $code)->first()
            : AssetCategory::where('name', $name)->first();

        if ($category) {
            $category->update($attributes);

            return 'updated';
        }

        AssetCategory::create($attributes);

        return 'created';
    }

    private function importVendor(array $row, int $rowNumber): string
    {
        $name = trim((string) ($row['nama_vendor'] ?? ''));
        if ($name === '') {
            throw new \RuntimeException('Nama vendor wajib diisi.');
        }

        $code = $this->nullable($row['kode_vendor'] ?? null) ?: $this->nextCode('VND', Vendor::class, 'vendor_code');
        $attributes = [
            'vendor_code' => $code,
            'name' => $name,
            'vendor_type' => $this->nullable($row['tipe_vendor'] ?? null) ?: 'supplier',
            'phone' => $this->nullable($row['telepon'] ?? null),
            'email' => $this->nullable($row['email'] ?? null),
            'status' => $this->nullable($row['status'] ?? null) ?: 'active',
            'created_by' => auth()->id(),
        ];

        $vendor = Vendor::where('vendor_code', $code)->first();
        if ($vendor) {
            $vendor->update($attributes);

            return 'updated';
        }

        Vendor::create($attributes);

        return 'created';
    }

    private function importSparepart(array $row, int $rowNumber): string
    {
        $partNumber = trim((string) ($row['part_number'] ?? ''));
        $name = trim((string) ($row['nama_sparepart'] ?? ''));

        if ($partNumber === '') {
            throw new \RuntimeException('Part Number wajib diisi.');
        }
        if ($name === '') {
            throw new \RuntimeException('Nama sparepart wajib diisi.');
        }

        $attributes = [
            'part_number' => $partNumber,
            'name' => $name,
            'stock' => $this->decimal($row['stok'] ?? null) ?? 0,
            'min_stock' => $this->decimal($row['stok_minimum'] ?? null) ?? 0,
            'reorder_point' => $this->decimal($row['reorder_point'] ?? null) ?? 0,
            'unit_price' => $this->decimal($row['harga_satuan'] ?? null) ?? 0,
            'is_active' => $this->boolean($row['aktif'] ?? null, true),
        ];

        $sparepart = Sparepart::where('part_number', $partNumber)->first();
        if ($sparepart) {
            $sparepart->update($attributes);

            return 'updated';
        }

        Sparepart::create($attributes);

        return 'created';
    }

    private function resolveSchool(array $row): School
    {
        if ($this->school) {
            return $this->school;
        }

        $name = $this->nullable($row['sekolah'] ?? null);
        $school = $this->schoolId
            ? School::find($this->schoolId)
            : ($name ? School::where('name', 'like', $name)->first() : null);

        $school ??= School::orderBy('name')->first();

        if (! $school) {
            throw new \RuntimeException('Satuan pendidikan tidak ditemukan. Tambahkan data sekolah terlebih dahulu.');
        }

        return $this->school = $school;
    }

    private function resolveBuilding(array $row, School $school): ?AssetBuilding
    {
        $code = $this->nullable($row['kode_gedung'] ?? null);
        if ($code) {
            $building = AssetBuilding::where('building_code', $code)->first();
            if ($building) {
                return $building;
            }
        }

        $name = $this->nullable($row['nama_gedung'] ?? null);
        if ($name) {
            return AssetBuilding::where('school_id', $school->id)->where('building_name', $name)->first();
        }

        return null;
    }

    private function nextCode(string $prefix, string $model, string $column): string
    {
        $last = $model::where($column, 'like', $prefix.'-%')->orderByDesc($column)->value($column);
        $number = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $matches)) {
            $number = (int) $matches[1] + 1;
        }

        return $prefix.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    private function choice($value, array $allowed, string $default): string
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(' ', '_', $value);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function boolean($value, bool $default): bool
    {
        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'ya', 'yes', 'true', 'aktif', 'active'], true);
    }

    private function integer($value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (int) preg_replace('/[^0-9-]/', '', (string) $value);
    }

    private function decimal($value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $clean = str_replace(['Rp', '.', ' '], '', (string) $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function nullable($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
