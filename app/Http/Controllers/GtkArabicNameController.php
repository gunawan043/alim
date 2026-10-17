<?php

namespace App\Http\Controllers;

use App\Exports\GtkArabicNameTemplateExport;
use App\Models\GtkEmployment;
use App\Services\ArabicName\ArabicNameImportService;
use App\Services\ArabicName\ArabicNameSpreadsheetReader;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Import nama Arab GTK (menu terpisah dari import GTK umum).
 * Pencocokan: NUPY pada data kepegawaian (gtk_employments).
 */
class GtkArabicNameController extends Controller
{
    public function form(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');

        $stats = app(ArabicNameImportService::class)->gtkStats($schoolId);

        $missingEmployees = GtkEmployment::query()
            ->with('user:id,name,name_ar')
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereHas('user', fn ($q) => $q->where(fn ($qq) => $qq->whereNull('name_ar')->orWhere('name_ar', '')))
            ->orderBy('nupy')
            ->limit(30)
            ->get();

        return view('gtk.import-nama-arab', compact('userId', 'stats', 'missingEmployees'));
    }

    public function import(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $rows = app(ArabicNameSpreadsheetReader::class)->read($request->file('file'));
        $result = app(ArabicNameImportService::class)->importGtk($rows, $schoolId);

        return redirect()
            ->route('user.gtk.import-nama-arab-form', ['userId' => $userId])
            ->with('import_result', $result);
    }

    public function template(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');

        return Excel::download(
            new GtkArabicNameTemplateExport($schoolId),
            'template-nama-arab-gtk-'.date('Ymd').'.xlsx'
        );
    }

    public function generate(Request $request, string $userId)
    {
        $this->authorizeGtkImport();

        $schoolId = $request->attributes->get('schoolContextId');

        $overwrite = $request->boolean('overwrite');
        $limit = config('arabic_names.driver') === 'ai'
            ? max(1, (int) config('arabic_names.ai.batch_limit', 20))
            : null;

        $count = app(ArabicNameImportService::class)->generateGtk($schoolId, $overwrite, $limit);

        $batchNote = $limit ? " (batch AI maks {$limit} nama — jalankan lagi untuk sisanya)" : '';

        return redirect()
            ->route('user.gtk.import-nama-arab-form', ['userId' => $userId])
            ->with('success', "Nama Arab dibuat otomatis untuk {$count} GTK{$batchNote}. Silakan tinjau hasilnya di Data GTK.");
    }

    private function authorizeGtkImport(): void
    {
        abort_unless(
            canPermission('super-admin-only') || canPermission('gtk-create'),
            403,
            'Hanya pengguna berwenang yang dapat mengimpor nama Arab GTK.'
        );
    }
}
