<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetCategory;
use App\Models\AssetRoom;
use App\Models\Sparepart;
use App\Models\Vendor;
use InvalidArgumentException;

class SarprasDataExchange
{
    /**
     * Registry dataset import/export. Tambah dataset baru cukup di sini.
     */
    public static function datasets(): array
    {
        return [
            'aset' => [
                'label' => 'Aset',
                'description' => 'Seluruh unit aset beserta kategori, ruang, kondisi, dan nilai perolehan.',
                'icon' => 'ri-archive-2-line',
                'color' => 'primary',
                'can_import' => false,
                'import_route' => 'sarpras.aset.import',
                'headings' => [
                    'kode_aset' => 'Kode Aset',
                    'nama_aset' => 'Nama Aset',
                    'kategori' => 'Kategori',
                    'ruang' => 'Ruang',
                    'gedung' => 'Gedung',
                    'satuan_kerja' => 'Satuan Kerja',
                    'sekolah' => 'Sekolah',
                    'merk' => 'Merk',
                    'model' => 'Model',
                    'nomor_seri' => 'Nomor Seri',
                    'kondisi' => 'Kondisi',
                    'status' => 'Status',
                    'tahun_perolehan' => 'Tahun Perolehan',
                    'harga_perolehan' => 'Harga Perolehan',
                    'jumlah' => 'Jumlah',
                    'satuan' => 'Satuan',
                    'aktif' => 'Aktif',
                ],
                'sample' => [
                    'kode_aset' => 'AST-0001',
                    'nama_aset' => 'Meja Siswa',
                    'kategori' => 'Meubelair',
                    'ruang' => 'Kelas 7A',
                    'gedung' => 'Gedung A',
                    'satuan_kerja' => 'Satuan Pendidikan',
                    'sekolah' => 'Ponpes Abu Hurairah',
                    'merk' => 'Cosco',
                    'model' => 'DX-200',
                    'nomor_seri' => '',
                    'kondisi' => 'baik',
                    'status' => 'tersedia',
                    'tahun_perolehan' => date('Y'),
                    'harga_perolehan' => 1500000,
                    'jumlah' => 1,
                    'satuan' => 'unit',
                    'aktif' => 'ya',
                ],
            ],
            'gedung' => [
                'label' => 'Gedung',
                'description' => 'Master gedung, jenis, kondisi struktur, dan status kepemilikan.',
                'icon' => 'ri-building-3-line',
                'color' => 'info',
                'can_import' => true,
                'headings' => [
                    'kode_gedung' => 'Kode Gedung',
                    'nama_gedung' => 'Nama Gedung',
                    'sekolah' => 'Sekolah',
                    'jenis_gedung' => 'Jenis Gedung',
                    'jumlah_lantai' => 'Jumlah Lantai',
                    'luas_m2' => 'Luas (m2)',
                    'tahun_bangun' => 'Tahun Bangun',
                    'kondisi_struktur' => 'Kondisi Struktur',
                    'status_kepemilikan' => 'Status Kepemilikan',
                    'aktif' => 'Aktif',
                ],
                'sample' => [
                    'kode_gedung' => 'GDG-A',
                    'nama_gedung' => 'Gedung A',
                    'sekolah' => 'Ponpes Abu Hurairah',
                    'jenis_gedung' => 'kelas',
                    'jumlah_lantai' => 2,
                    'luas_m2' => 500,
                    'tahun_bangun' => 2020,
                    'kondisi_struktur' => 'baik',
                    'status_kepemilikan' => 'milik_sendiri',
                    'aktif' => 'ya',
                ],
            ],
            'ruang' => [
                'label' => 'Ruang',
                'description' => 'Daftar ruang, gedung induk, lantai, kapasitas, dan kondisi.',
                'icon' => 'ri-door-open-line',
                'color' => 'success',
                'can_import' => true,
                'headings' => [
                    'kode_gedung' => 'Kode Gedung',
                    'nama_gedung' => 'Nama Gedung',
                    'kode_ruang' => 'Kode Ruang',
                    'nama_ruang' => 'Nama Ruang',
                    'sekolah' => 'Sekolah',
                    'lantai' => 'Lantai',
                    'jenis_ruang' => 'Jenis Ruang',
                    'kapasitas' => 'Kapasitas',
                    'luas_m2' => 'Luas (m2)',
                    'kondisi' => 'Kondisi',
                    'aktif' => 'Aktif',
                ],
                'sample' => [
                    'kode_gedung' => 'GDG-A',
                    'nama_gedung' => 'Gedung A',
                    'kode_ruang' => 'KLS-0001',
                    'nama_ruang' => 'Kelas 7A',
                    'sekolah' => 'Ponpes Abu Hurairah',
                    'lantai' => 1,
                    'jenis_ruang' => 'kelas',
                    'kapasitas' => 32,
                    'luas_m2' => 56,
                    'kondisi' => 'baik',
                    'aktif' => 'ya',
                ],
            ],
            'kategori' => [
                'label' => 'Kategori Aset',
                'description' => 'Kategori dan tipe aset untuk klasifikasi inventaris.',
                'icon' => 'ri-price-tag-3-line',
                'color' => 'warning',
                'can_import' => true,
                'headings' => [
                    'kode_kategori' => 'Kode Kategori',
                    'nama_kategori' => 'Nama Kategori',
                    'tipe_aset' => 'Tipe Aset',
                    'masa_pakai_tahun' => 'Masa Pakai (Tahun)',
                    'aktif' => 'Aktif',
                ],
                'sample' => [
                    'kode_kategori' => 'MUB',
                    'nama_kategori' => 'Meubelair',
                    'tipe_aset' => 'bergerak',
                    'masa_pakai_tahun' => 8,
                    'aktif' => 'ya',
                ],
            ],
            'vendor' => [
                'label' => 'Vendor',
                'description' => 'Data vendor, kontak, status kerja sama, dan risiko.',
                'icon' => 'ri-shop-line',
                'color' => 'secondary',
                'can_import' => true,
                'headings' => [
                    'kode_vendor' => 'Kode Vendor',
                    'nama_vendor' => 'Nama Vendor',
                    'tipe_vendor' => 'Tipe Vendor',
                    'telepon' => 'Telepon',
                    'email' => 'Email',
                    'status' => 'Status',
                ],
                'sample' => [
                    'kode_vendor' => 'VND-001',
                    'nama_vendor' => 'CV Sumber Teknik',
                    'tipe_vendor' => 'supplier',
                    'telepon' => '081234567890',
                    'email' => 'vendor@example.com',
                    'status' => 'active',
                ],
            ],
            'sparepart' => [
                'label' => 'Sparepart',
                'description' => 'Stok sparepart, titik reorder, dan harga satuan.',
                'icon' => 'ri-tools-line',
                'color' => 'danger',
                'can_import' => true,
                'headings' => [
                    'part_number' => 'Part Number',
                    'nama_sparepart' => 'Nama Sparepart',
                    'stok' => 'Stok',
                    'stok_minimum' => 'Stok Minimum',
                    'reorder_point' => 'Reorder Point',
                    'harga_satuan' => 'Harga Satuan',
                    'aktif' => 'Aktif',
                ],
                'sample' => [
                    'part_number' => 'SP-001',
                    'nama_sparepart' => 'Filter AC',
                    'stok' => 10,
                    'stok_minimum' => 3,
                    'reorder_point' => 5,
                    'harga_satuan' => 75000,
                    'aktif' => 'ya',
                ],
            ],
        ];
    }

    public static function find(string $dataset): array
    {
        $datasets = self::datasets();
        if (! isset($datasets[$dataset])) {
            throw new InvalidArgumentException("Dataset '{$dataset}' tidak dikenal.");
        }

        return $datasets[$dataset];
    }

    public static function exportRows(string $dataset, ?string $schoolId = null): array
    {
        return match ($dataset) {
            'aset' => self::assetRows($schoolId),
            'gedung' => self::buildingRows($schoolId),
            'ruang' => self::roomRows($schoolId),
            'kategori' => self::categoryRows(),
            'vendor' => self::vendorRows(),
            'sparepart' => self::sparepartRows(),
            default => [],
        };
    }

    private static function assetRows(?string $schoolId): array
    {
        return Asset::with(['category', 'room.building', 'workUnit', 'school'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('asset_name')
            ->get()
            ->map(fn (Asset $asset) => [
                'kode_aset' => $asset->asset_code,
                'nama_aset' => $asset->asset_name,
                'kategori' => $asset->category?->name,
                'ruang' => $asset->room?->room_name,
                'gedung' => $asset->room?->building?->building_name,
                'satuan_kerja' => $asset->workUnit?->name,
                'sekolah' => $asset->school?->name,
                'merk' => $asset->brand,
                'model' => $asset->model,
                'nomor_seri' => $asset->serial_number,
                'kondisi' => $asset->condition,
                'status' => $asset->status,
                'tahun_perolehan' => $asset->acquisition_year,
                'harga_perolehan' => $asset->acquisition_price,
                'jumlah' => $asset->quantity,
                'satuan' => $asset->unit,
                'aktif' => $asset->is_active ? 'ya' : 'tidak',
            ])->all();
    }

    private static function buildingRows(?string $schoolId): array
    {
        return AssetBuilding::with('school')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('building_name')
            ->get()
            ->map(fn (AssetBuilding $building) => [
                'kode_gedung' => $building->building_code,
                'nama_gedung' => $building->building_name,
                'sekolah' => $building->school?->name,
                'jenis_gedung' => $building->building_type,
                'jumlah_lantai' => $building->total_floors,
                'luas_m2' => $building->building_area,
                'tahun_bangun' => $building->build_year,
                'kondisi_struktur' => $building->structure_condition,
                'status_kepemilikan' => $building->ownership_status,
                'aktif' => $building->is_active ? 'ya' : 'tidak',
            ])->all();
    }

    private static function roomRows(?string $schoolId): array
    {
        return AssetRoom::with(['building', 'school'])
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->orderBy('room_name')
            ->get()
            ->map(fn (AssetRoom $room) => [
                'kode_gedung' => $room->building?->building_code,
                'nama_gedung' => $room->building?->building_name,
                'kode_ruang' => $room->room_code,
                'nama_ruang' => $room->room_name,
                'sekolah' => $room->school?->name,
                'lantai' => $room->floor,
                'jenis_ruang' => $room->room_type,
                'kapasitas' => $room->capacity,
                'luas_m2' => $room->room_area,
                'kondisi' => $room->condition,
                'aktif' => $room->is_active ? 'ya' : 'tidak',
            ])->all();
    }

    private static function categoryRows(): array
    {
        return AssetCategory::orderBy('name')->get()->map(fn (AssetCategory $category) => [
            'kode_kategori' => $category->code,
            'nama_kategori' => $category->name,
            'tipe_aset' => $category->asset_type,
            'masa_pakai_tahun' => $category->depreciation_years,
            'aktif' => $category->is_active ? 'ya' : 'tidak',
        ])->all();
    }

    private static function vendorRows(): array
    {
        return Vendor::orderBy('name')->get()->map(fn (Vendor $vendor) => [
            'kode_vendor' => $vendor->vendor_code,
            'nama_vendor' => $vendor->name,
            'tipe_vendor' => $vendor->vendor_type,
            'telepon' => $vendor->phone,
            'email' => $vendor->email,
            'status' => $vendor->status,
        ])->all();
    }

    private static function sparepartRows(): array
    {
        return Sparepart::orderBy('name')->get()->map(fn (Sparepart $sparepart) => [
            'part_number' => $sparepart->part_number,
            'nama_sparepart' => $sparepart->name,
            'stok' => $sparepart->stock,
            'stok_minimum' => $sparepart->min_stock,
            'reorder_point' => $sparepart->reorder_point,
            'harga_satuan' => $sparepart->unit_price,
            'aktif' => $sparepart->is_active ? 'ya' : 'tidak',
        ])->all();
    }
}
