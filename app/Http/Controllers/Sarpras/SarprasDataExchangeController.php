<?php

namespace App\Http\Controllers\Sarpras;

use App\Exports\SarprasDatasetExport;
use App\Imports\SarprasDatasetImport;
use App\Support\SarprasDataExchange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SarprasDataExchangeController extends SarprasBaseController
{
    public function index(Request $request)
    {
        $this->authorizeCentralSarpras();

        return view('sarpras.data-exchange.index', [
            'datasets' => SarprasDataExchange::datasets(),
        ]);
    }

    public function template(string $userId, Request $request, string $dataset): BinaryFileResponse
    {
        $this->authorizeCentralSarpras();
        $config = SarprasDataExchange::find($dataset);

        return Excel::download(
            new SarprasDatasetExport($config['headings'], [$config['sample']], 'Template '.$config['label']),
            'template-import-'.Str::slug($config['label']).'.xlsx'
        );
    }

    public function export(string $userId, Request $request, string $dataset): BinaryFileResponse
    {
        $this->authorizeCentralSarpras();
        $config = SarprasDataExchange::find($dataset);
        $schoolId = $request->attributes->get('schoolContextId') ?: null;
        $rows = SarprasDataExchange::exportRows($dataset, $schoolId);

        return Excel::download(
            new SarprasDatasetExport($config['headings'], $rows, $config['label']),
            'sarpras-'.Str::slug($dataset).'-'.date('Ymd-His').'.xlsx'
        );
    }

    public function import(string $userId, Request $request, string $dataset): RedirectResponse
    {
        $this->authorizeCentralSarpras();
        $config = SarprasDataExchange::find($dataset);

        if (! $config['can_import']) {
            return back()->with('error', 'Dataset '.$config['label'].' belum mendukung import di halaman ini.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ], [
            'file.required' => 'File import wajib dipilih.',
            'file.mimes' => 'Format file harus Excel (.xlsx/.xls) atau CSV.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $import = new SarprasDatasetImport($dataset, $request->attributes->get('schoolContextId'));

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::warning('Sarpras data exchange import failed', ['dataset' => $dataset, 'error' => $e->getMessage()]);

            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        $errors = $import->getErrors();
        $message = "Import {$config['label']} selesai: {$import->getCreatedCount()} baru, {$import->getUpdatedCount()} diperbarui.";

        if ($errors !== []) {
            $message .= ' '.count($errors).' baris dilewati.';
        }

        return back()
            ->with('success', $message)
            ->with('import_errors', $errors);
    }
}
