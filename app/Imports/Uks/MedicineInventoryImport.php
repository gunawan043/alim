<?php

namespace App\Imports\Uks;

use App\Models\StudentMedicineInventory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MedicineInventoryImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    public function __construct(private readonly ?string $schoolId = null) {}

    public function collection(Collection $rows): void
    {
        $categories = [
            'obat_dalam' => 'obat_dalam',
            'obat dalam' => 'obat_dalam',
            'obat_luar' => 'obat_luar',
            'obat luar' => 'obat_luar',
            'vitamin_suplemen' => 'vitamin_suplemen',
            'vitamin & suplemen' => 'vitamin_suplemen',
            'antiseptik' => 'antiseptik',
            'alat_kesehatan' => 'alat_kesehatan',
            'alat kesehatan' => 'alat_kesehatan',
        ];

        DB::beginTransaction();

        try {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2;
                $data = $row->toArray();

                if (blank(trim((string) ($data['nama_obat'] ?? '')))) {
                    $this->skipped++;

                    continue;
                }

                try {
                    $name = trim((string) $data['nama_obat']);
                    $code = trim((string) ($data['kode_obat'] ?? ''));
                    $categoryInput = strtolower(trim((string) ($data['kategori'] ?? 'obat_dalam')));
                    $category = $categories[$categoryInput] ?? null;
                    $unit = trim((string) ($data['satuan'] ?? ''));
                    $stock = $this->number($data['stok'] ?? 0);
                    $minimum = $this->number($data['stok_minimum'] ?? 0);
                    $price = $this->number($data['harga_satuan'] ?? 0);
                    $expiry = $this->date($data['tanggal_kedaluwarsa'] ?? null);
                    $purchaseDate = $this->date($data['tanggal_pembelian'] ?? null);

                    if ($code === '' && $name === '') {
                        throw new \RuntimeException('Nama obat wajib diisi.');
                    }

                    if ($category === null) {
                        throw new \RuntimeException('Kategori obat tidak valid. Gunakan obat_dalam, obat_luar, vitamin_suplemen, antiseptik, atau alat_kesehatan.');
                    }

                    if ($unit === '') {
                        throw new \RuntimeException('Satuan obat wajib diisi.');
                    }

                    if ($stock === null || $stock < 0 || $minimum === null || $minimum < 0) {
                        throw new \RuntimeException('Stok dan stok minimum harus berupa angka positif.');
                    }

                    $query = StudentMedicineInventory::query()
                        ->when($this->schoolId, fn ($q) => $q->where('school_id', $this->schoolId));

                    if ($code !== '') {
                        $existing = (clone $query)->where('medicine_code', $code)->first();
                    } else {
                        $existing = (clone $query)
                            ->where('medicine_name', $name)
                            ->where('unit', $unit)
                            ->first();
                    }

                    $payload = [
                        'school_id' => $this->schoolId,
                        'medicine_name' => $name,
                        'medicine_code' => $code ?: null,
                        'category' => $category,
                        'generic_name' => trim((string) ($data['nama_generik'] ?? '')) ?: null,
                        'unit' => $unit,
                        'current_stock' => $stock,
                        'min_stock_alert' => $minimum,
                        'expiry_date' => $expiry,
                        'storage_location' => trim((string) ($data['lokasi_penyimpanan'] ?? '')) ?: null,
                        'supplier' => trim((string) ($data['supplier'] ?? '')) ?: null,
                        'purchase_date' => $purchaseDate,
                        'unit_price' => $price,
                        'dosage_info' => trim((string) ($data['info_dosis'] ?? '')) ?: null,
                        'notes' => trim((string) ($data['catatan'] ?? '')) ?: null,
                    ];

                    if ($existing) {
                        $existing->update($payload);
                        $this->updated++;
                    } else {
                        StudentMedicineInventory::create($payload);
                        $this->created++;
                    }
                } catch (\Throwable $e) {
                    $this->skipped++;
                    $this->errors[] = "Baris {$rowNumber}: ".$e->getMessage();
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->errors[] = 'Import dibatalkan: '.$e->getMessage();
        }
    }

    private function number(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return 0.0;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
