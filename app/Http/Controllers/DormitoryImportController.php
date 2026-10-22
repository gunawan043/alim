<?php

namespace App\Http\Controllers;

use App\Exports\DormitoryRoomsTemplateExport;
use App\Imports\DormitoryRoomsImport;
use App\Models\Dormitory;
use App\Models\DormitoryRoom;
use App\Models\DormitoryWing;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DormitoryImportController extends Controller
{
    public function index(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->authorizedDormitory($asramaUuid);

        return view('dormitory.imports.index', [
            'dormitory' => $dormitory,
            'userId' => $userId,
            'roomCount' => DormitoryRoom::where('dormitory_id', $asramaUuid)->count(),
            'wingCount' => DormitoryWing::where('dormitory_id', $asramaUuid)->count(),
        ]);
    }

    public function roomsTemplate(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->authorizedDormitory($asramaUuid);

        return (new DormitoryRoomsTemplateExport($dormitory))->download(
            'template_import_kamar_'.strtolower($dormitory->code ?: 'asrama').'.xlsx'
        );
    }

    public function roomsStore(Request $request, string $userId, string $asramaUuid)
    {
        $dormitory = $this->authorizedDormitory($asramaUuid);

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new DormitoryRoomsImport($asramaUuid, $dormitory->gender ?? 'putra', $userId);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        $message = "Import selesai: {$import->created} kamar baru, {$import->updated} kamar diperbarui, {$import->skipped} baris dilewati.";

        return redirect()
            ->route('user.asrama.imports.index', ['userId' => $userId, 'asramaUuid' => $asramaUuid])
            ->with($import->errors === [] ? 'success' : 'warning', $message)
            ->with('import_errors', $import->errors);
    }

    private function authorizedDormitory(string $asramaUuid): Dormitory
    {
        $user = request()->user();

        if ($user && ! $user->canAccessDormitory($asramaUuid)) {
            abort(403, 'Anda tidak memiliki akses ke asrama ini.');
        }

        return Dormitory::findOrFail($asramaUuid);
    }
}
