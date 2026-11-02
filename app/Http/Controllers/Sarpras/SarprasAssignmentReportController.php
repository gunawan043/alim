<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetMaintenanceLog;
use App\Models\SarprasAssignmentReport;
use App\Models\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Laporan penugasan Sarpras (perbaikan & pemeliharaan).
 *
 * Alur: work order selesai -> masuk daftar "Belum dilaporkan" -> petugas
 * mengisi laporan singkat (apa yang dikerjakan + kendala) -> koordinator
 * verifikasi. Dengan begitu agenda pekerjaan yang sudah selesai terlihat
 * dari status laporannya, bukan hanya dari status work order.
 */
class SarprasAssignmentReportController extends SarprasBaseController
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $canVerify = $this->isCentralSarpras() || $this->isUrtLeadership();
        $tab = $request->input('tab') === 'laporan' ? 'laporan' : 'belum';
        $search = trim((string) $request->input('q'));

        $pending = $this->pendingOrders($user, $canVerify, $search)->get();
        $reports = $this->reportQuery($user, $canVerify, $search)->get();

        $monthStart = now()->startOfMonth();

        $stats = [
            'pending' => $pending->count(),
            'reports_month' => $reports->where('report_date', '>=', $monthStart)->count(),
            'verified_month' => $reports->where('status', SarprasAssignmentReport::STATUS_VERIFIED)
                ->where('report_date', '>=', $monthStart)->count(),
            'cost_month' => (float) $reports->where('report_date', '>=', $monthStart)->sum('cost'),
        ];

        return view('sarpras.assignment-report.index', [
            'pending' => $pending,
            'reports' => $reports,
            'stats' => $stats,
            'tab' => $tab,
            'search' => $search,
            'canVerify' => $canVerify,
            'selectedOrderId' => $request->input('work_order_id'),
            'conditionOptions' => AssetMaintenanceLog::CONDITION_OPTIONS,
            'obstaclePresets' => SarprasAssignmentReport::OBSTACLE_PRESETS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'work_order_id' => 'required|exists:work_orders,id',
            'report_date' => 'required|date',
            'work_done' => 'required|string|max:2000',
            'obstacles' => 'nullable|string|max:1000',
            'follow_up' => 'nullable|string|max:1000',
            'parts_replaced' => 'nullable|string|max:191',
            'cost' => 'nullable|numeric|min:0',
            'condition_after' => 'nullable|string|max:30',
            'duration_minutes' => 'nullable|integer|min:0|max:100000',
            'action' => 'nullable|in:submit,draft',
        ], [
            'work_order_id.required' => 'Pilih pekerjaan yang dilaporkan.',
            'work_done.required' => 'Tuliskan singkat apa yang sudah dikerjakan.',
            'report_date.required' => 'Tanggal laporan wajib diisi.',
        ]);

        $order = WorkOrder::with(['asset', 'repairRequest'])->findOrFail($data['work_order_id']);

        if ($order->assignmentReport()->exists()) {
            return back()->withErrors(['work_order_id' => 'Work order ini sudah punya laporan.']);
        }

        if (! $this->mayReport($order)) {
            abort(403, 'Anda tidak berhak membuat laporan untuk work order ini.');
        }

        try {
            $report = DB::transaction(function () use ($data, $order) {
                return SarprasAssignmentReport::create([
                    'work_order_id' => $order->id,
                    'repair_request_id' => $order->repair_request_id,
                    'maintenance_schedule_id' => $order->maintenance_schedule_id,
                    'asset_id' => $order->asset_id,
                    'work_unit_id' => $order->asset?->work_unit_id ?? $order->repairRequest?->asset?->work_unit_id,
                    'school_id' => $order->asset?->school_id,
                    'reported_by' => auth()->id(),
                    'report_date' => $data['report_date'],
                    'work_done' => $data['work_done'],
                    'obstacles' => $data['obstacles'] ?? null,
                    'follow_up' => $data['follow_up'] ?? null,
                    'parts_replaced' => $data['parts_replaced'] ?? null,
                    'cost' => (float) ($data['cost'] ?? 0),
                    'condition_after' => $data['condition_after'] ?? null,
                    'duration_minutes' => $data['duration_minutes'] ?? null,
                    'status' => ($data['action'] ?? 'submit') === 'draft'
                        ? SarprasAssignmentReport::STATUS_DRAFT
                        : SarprasAssignmentReport::STATUS_SUBMITTED,
                ]);
            });
        } catch (\Throwable $e) {
            Log::warning('sarpras.assignment_report.store_failed', [
                'work_order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors(['work_done' => 'Laporan gagal disimpan. Coba lagi.']);
        }

        return redirect()
            ->route('sarpras.assignment-report.index', ['tab' => 'laporan'])
            ->with('success', 'Laporan pekerjaan '.$order->order_number.' tersimpan.');
    }

    public function show(string $userId, Request $request, string $reportId)
    {
        $report = SarprasAssignmentReport::with([
            'workOrder.asset.room.building', 'workOrder.assignee', 'workOrder.repairRequest',
            'asset', 'reporter', 'verifier', 'workUnit',
        ])->findOrFail($reportId);

        $this->authorizeView($report);

        return view('sarpras.assignment-report.show', [
            'report' => $report,
            'canVerify' => $this->isCentralSarpras() || $this->isUrtLeadership(),
        ]);
    }

    public function verify(string $userId, Request $request, string $reportId): RedirectResponse
    {
        abort_unless($this->isCentralSarpras() || $this->isUrtLeadership(), 403);

        $data = $request->validate([
            'verification_note' => 'nullable|string|max:1000',
        ]);

        $report = SarprasAssignmentReport::findOrFail($reportId);
        $report->update([
            'status' => SarprasAssignmentReport::STATUS_VERIFIED,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'verification_note' => $data['verification_note'] ?? null,
        ]);

        return back()->with('success', 'Laporan diverifikasi.');
    }

    public function destroy(string $userId, string $reportId): RedirectResponse
    {
        $report = SarprasAssignmentReport::findOrFail($reportId);

        $isOwner = $report->reported_by === auth()->id();
        abort_unless($isOwner || $this->isCentralSarpras(), 403);

        if (! $isOwner && $report->status === SarprasAssignmentReport::STATUS_VERIFIED) {
            return back()->withErrors(['report' => 'Laporan terverifikasi tidak dapat dihapus.']);
        }

        $report->delete();

        return redirect()
            ->route('sarpras.assignment-report.index')
            ->with('success', 'Laporan dihapus.');
    }

    /**
     * Work order selesai yang belum punya laporan — inilah agenda yang
     * secara administratif belum ditutup.
     */
    private function pendingOrders($user, bool $canVerify, string $search)
    {
        return WorkOrder::query()
            ->whereIn('status', ['completed', 'closed'])
            ->whereDoesntHave('assignmentReport')
            ->with(['asset.room.building', 'assignee', 'repairRequest', 'maintenanceSchedule'])
            ->when(! $canVerify, fn ($q) => $q->where('assignee_id', $user->id))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(fn ($sub) => $sub->where('order_number', 'like', "%{$search}%")
                    ->orWhere('scope_of_work', 'like', "%{$search}%")
                    ->orWhereHas('asset', fn ($a) => $a->where('asset_name', 'like', "%{$search}%")));
            })
            ->orderByDesc('actual_end')
            ->limit(100);
    }

    private function reportQuery($user, bool $canVerify, string $search)
    {
        return SarprasAssignmentReport::query()
            ->with(['workOrder', 'asset', 'reporter', 'verifier', 'workUnit'])
            ->when(! $canVerify, fn ($q) => $q->where('reported_by', $user->id))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(fn ($sub) => $sub->where('work_done', 'like', "%{$search}%")
                    ->orWhere('obstacles', 'like', "%{$search}%")
                    ->orWhereHas('workOrder', fn ($w) => $w->where('order_number', 'like', "%{$search}%")));
            })
            ->orderByDesc('report_date')
            ->orderByDesc('created_at')
            ->limit(100);
    }

    private function mayReport(WorkOrder $order): bool
    {
        if ($this->isCentralSarpras() || $this->isUrtLeadership()) {
            return true;
        }

        return $order->assignee_id === auth()->id();
    }

    private function authorizeView(SarprasAssignmentReport $report): void
    {
        if ($this->isCentralSarpras() || $this->isUrtLeadership()) {
            return;
        }

        abort_unless(
            $report->reported_by === auth()->id() || $report->workOrder?->assignee_id === auth()->id(),
            403,
            'Anda tidak berhak melihat laporan ini.'
        );
    }
}
