<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\MaintenanceHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Timeline kegiatan aset (perawatan & perbaikan) dengan tampilan grid
 * seperti spreadsheet: input baris per baris, tempel dari Excel, simpan sekaligus.
 */
class SarprasAssetTimelineController extends SarprasBaseController
{
    public const TYPE_OPTIONS = [
        'preventive' => 'Perawatan rutin',
        'corrective' => 'Perbaikan',
        'emergency' => 'Darurat',
    ];

    public function index(string $userId, Request $request, string $id)
    {
        $asset = $this->findAsset($id, $request);

        $rows = MaintenanceHistory::where('asset_id', $asset->id)
            ->orderByDesc('performed_date')
            ->orderByDesc('id')
            ->get();

        $repairs = $asset->repairRequests()
            ->with('assignee')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('sarpras.aset.timeline', [
            'asset' => $asset,
            'rows' => $rows,
            'repairs' => $repairs,
            'canEdit' => $this->canEdit($request),
            'typeOptions' => self::TYPE_OPTIONS,
            'conditionOptions' => Asset::CONDITION_OPTIONS,
        ]);
    }

    public function save(string $userId, Request $request, string $id)
    {
        $asset = $this->findAsset($id, $request);
        $this->authorizeEditor($request);

        $conditionRule = 'nullable|in:'.implode(',', Asset::CONDITION_OPTIONS);

        $data = $request->validate([
            'rows' => 'nullable|array',
            'rows.*.id' => 'nullable|integer',
            'rows.*.performed_date' => 'required|date',
            'rows.*.maintenance_type' => 'required|in:'.implode(',', array_keys(self::TYPE_OPTIONS)),
            'rows.*.work_description' => 'nullable|string|max:1000',
            'rows.*.performed_by_name' => 'nullable|string|max:150',
            'rows.*.cost' => 'nullable|numeric|min:0',
            'rows.*.condition_before' => $conditionRule,
            'rows.*.condition_after' => $conditionRule,
            'rows.*.next_due_date' => 'nullable|date',
            'rows.*.notes' => 'nullable|string|max:1000',
            'rows.*.delete' => 'nullable|boolean',
        ]);

        $saved = 0;
        $deleted = 0;

        DB::transaction(function () use ($asset, $data, &$saved, &$deleted) {
            foreach ($data['rows'] ?? [] as $row) {
                if (! empty($row['delete'])) {
                    if (! empty($row['id'])) {
                        $deleted += MaintenanceHistory::where('asset_id', $asset->id)
                            ->whereKey($row['id'])
                            ->delete();
                    }

                    continue;
                }

                $payload = [
                    'asset_id' => $asset->id,
                    'maintenance_type' => $row['maintenance_type'],
                    'performed_date' => $row['performed_date'],
                    'performed_by_name' => $row['performed_by_name'] ?? null,
                    'condition_before' => $row['condition_before'] ?? null,
                    'condition_after' => $row['condition_after'] ?? null,
                    'work_description' => $row['work_description'] ?? null,
                    'cost' => $row['cost'] ?? 0,
                    'next_due_date' => $row['next_due_date'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ];

                if (! empty($row['id'])) {
                    $saved += MaintenanceHistory::where('asset_id', $asset->id)
                        ->whereKey($row['id'])
                        ->update($payload);

                    continue;
                }

                MaintenanceHistory::create($payload);
                $saved++;
            }
        });

        return redirect()
            ->route('sarpras.aset.timeline', ['id' => $asset->id])
            ->with('success', "Timeline tersimpan: {$saved} baris".($deleted ? ", {$deleted} baris dihapus" : '').'.');
    }

    /**
     * Ekspor CSV (kompatibel Excel) supaya bisa diolah/diimpor balik.
     */
    public function export(string $userId, Request $request, string $id): StreamedResponse
    {
        $asset = $this->findAsset($id, $request);

        $rows = MaintenanceHistory::where('asset_id', $asset->id)
            ->orderBy('performed_date')
            ->orderBy('id')
            ->get();

        $filename = 'timeline-'.($asset->asset_code ?: $asset->id).'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8

            fputcsv($handle, [
                'Tanggal', 'Jenis', 'Uraian Pekerjaan', 'Pelaksana',
                'Biaya', 'Kondisi Sebelum', 'Kondisi Sesudah', 'Jadwal Berikutnya', 'Catatan',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    optional($row->performed_date)->format('Y-m-d'),
                    self::TYPE_OPTIONS[$row->maintenance_type] ?? $row->maintenance_type,
                    $row->work_description,
                    $row->performed_by_name,
                    $row->cost,
                    $row->condition_before,
                    $row->condition_after,
                    optional($row->next_due_date)->format('Y-m-d'),
                    $row->notes,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function findAsset(string $id, Request $request): Asset
    {
        $asset = Asset::with(['room', 'room.school', 'category', 'workUnit'])->findOrFail($id);
        $this->authorizeAssetAccess($asset, $request);

        return $asset;
    }

    private function canEdit(Request $request): bool
    {
        return $this->isCentralSarpras()
            || $this->isUrtLeadership()
            || $this->isUnitSarprasOfficer()
            || $this->canViewAll($request);
    }

    private function authorizeEditor(Request $request): void
    {
        abort_unless(
            $this->canEdit($request),
            403,
            'Hanya pengelola Sarpras yang dapat mengubah timeline kegiatan aset.'
        );
    }
}
