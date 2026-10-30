<?php

namespace App\Http\Controllers\Uks;

use App\Http\Controllers\Controller;
use App\Imports\Uks\GtkHealthRecordImport;
use App\Imports\Uks\HealthMetricImport;
use App\Imports\Uks\MedicineInventoryImport;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    private const DATASETS = [
        'medicine' => [
            'label' => 'Inventaris Obat',
            'description' => 'Impor stok obat dan alat kesehatan. Data dengan kode obat yang sama akan diperbarui.',
            'headers' => [
                'kode_obat', 'nama_obat', 'nama_generik', 'kategori', 'satuan',
                'stok', 'stok_minimum', 'tanggal_kedaluwarsa', 'lokasi_penyimpanan',
                'supplier', 'harga_satuan', 'info_dosis', 'catatan',
            ],
            'examples' => [
                'OBT-001', 'Paracetamol 500 mg', 'Paracetamol', 'obat_dalam', 'tablet',
                '100', '20', '2027-12-31', 'Lemari obat A', 'Supplier Sehat', '250', '3x1 sesudah makan', 'Contoh',
            ],
        ],
        'metrics' => [
            'label' => 'Antropometri Santri',
            'description' => 'Impor tinggi, berat, dan lingkar kepala santri. Duplikat berdasarkan santri, tanggal, dan sesi akan diperbarui.',
            'headers' => [
                'nisn', 'nis', 'tanggal_pengukuran', 'tinggi_cm', 'berat_kg',
                'lingkar_kepala_cm', 'sesi', 'catatan',
            ],
            'examples' => [
                '0012345678', '1234', '2026-10-24', '150', '45', '52', 'awal_tahun', 'Contoh',
            ],
        ],
        'gtk-health' => [
            'label' => 'Kesehatan GTK',
            'description' => 'Impor riwayat pemeriksaan kesehatan GTK. Cari berdasarkan NUPY atau email.',
            'headers' => [
                'nupy', 'email', 'tanggal_pemeriksaan', 'tinggi_cm', 'berat_kg',
                'sistolik', 'diastolik', 'nadi', 'suhu', 'respirasi',
                'saturasi_oksigen', 'gula_darah', 'kolesterol', 'asam_urat', 'hemoglobin', 'catatan',
            ],
            'examples' => [
                '19740120141164', '', '2026-10-24', '170', '60', '120', '80', '80', '36.5',
                '18', '98', '90', '180', '5.5', '14', 'Contoh',
            ],
        ],
    ];

    public function form(Request $request, string $userId, string $dataset)
    {
        $config = $this->config($dataset);

        return view('uks.imports.form', compact('config', 'dataset', 'userId'));
    }

    public function store(Request $request, string $userId, string $dataset): RedirectResponse
    {
        $config = $this->config($dataset);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'file.required' => 'File import wajib dipilih.',
            'file.mimes' => 'File harus berformat .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $schoolId = $request->attributes->get('schoolContextId');

        if (in_array($dataset, ['medicine', 'metrics'], true) && ! $schoolId) {
            return back()->withInput()->with(
                'error',
                'Pilih satuan pendidikan terlebih dahulu melalui pemilih sekolah sebelum mengimpor data ini.'
            );
        }

        try {
            $import = match ($dataset) {
                'medicine' => new MedicineInventoryImport($schoolId),
                'metrics' => new HealthMetricImport($schoolId),
                'gtk-health' => new GtkHealthRecordImport,
                default => null,
            };

            if (! $import) {
                abort(404);
            }

            Excel::import($import, $request->file('file'));

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'UKS_IMPORT_'.strtoupper(str_replace('-', '_', $dataset)),
                'table_name' => $this->tableFor($dataset),
                'record_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $summary = "Import {$config['label']} selesai: {$import->created} dibuat, {$import->updated} diperbarui, {$import->skipped} dilewati.";
            $redirect = redirect()->route('user.uks.imports.form', ['userId' => $userId, 'dataset' => $dataset])
                ->with('success', $summary);

            if (! empty($import->errors)) {
                $redirect->with('import_errors', $import->errors);
            }

            return $redirect;
        } catch (\Throwable $e) {
            Log::error('UKS import gagal', [
                'dataset' => $dataset,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Import gagal: '.$e->getMessage());
        }
    }

    public function template(string $userId, string $dataset): StreamedResponse
    {
        $config = $this->config($dataset);

        return response()->streamDownload(function () use ($config) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $config['headers']);
            fclose($out);
        }, 'template-import-'.$dataset.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function config(string $dataset): array
    {
        if (! array_key_exists($dataset, self::DATASETS)) {
            abort(404);
        }

        return self::DATASETS[$dataset] + ['dataset' => $dataset];
    }

    private function tableFor(string $dataset): string
    {
        return match ($dataset) {
            'medicine' => 'student_medicine_inventory',
            'metrics' => 'student_health_metrics',
            'gtk-health' => 'gtk_health_records',
            default => 'uks',
        };
    }
}
