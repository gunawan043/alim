<?php

namespace App\Services\Sarpras;

use App\Models\Asset;
use App\Models\AssetLoan;
use App\Models\AssetMaintenanceSchedule;
use App\Models\ElectricityOutage;
use App\Models\PertamananAgendaItem;
use App\Models\ProcurementRequest;
use App\Models\RepairRequest;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Agenda mendatang + daftar prioritas untuk dashboard URT/Sarpras.
 *
 * Semua sumber data memakai modul yang sudah ada (jadwal pemeliharaan,
 * work order, agenda pertamanan, peminjaman, pengadaan) sehingga tidak ada
 * tabel baru dan tidak ada duplikasi data.
 */
class UrtAgendaService
{
    private const AGENDA_DAYS = 30;

    private const LOAN_DAYS = 7;

    /**
     * @param  array<int, string>|null  $workUnitIds
     * @return array{agenda: array<int, array<string, mixed>>, priorities: array<int, array<string, mixed>>, counts: array<string, int>}
     */
    public function build(?string $schoolId = null, ?array $workUnitIds = null): array
    {
        $today = today();

        $agenda = collect()
            ->merge($this->maintenanceAgenda($schoolId, $workUnitIds, $today))
            ->merge($this->workOrderAgenda($schoolId, $workUnitIds, $today))
            ->merge($this->gardenAgenda($today))
            ->merge($this->loanAgenda($schoolId, $workUnitIds, $today))
            ->merge($this->procurementAgenda($schoolId, $workUnitIds, $today))
            ->sortBy(fn (array $row) => $row['date']->timestamp)
            ->values()
            ->take(15)
            ->all();

        $counts = $this->priorityCounts($schoolId, $workUnitIds, $today);

        return [
            'agenda' => $agenda,
            'priorities' => $this->priorities($counts),
            'counts' => $counts,
        ];
    }

    /**
     * @param  array<int, string>|null  $workUnitIds
     * @return array<int, array<string, mixed>>
     */
    private function maintenanceAgenda(?string $schoolId, ?array $workUnitIds, Carbon $today): array
    {
        try {
            return AssetMaintenanceSchedule::query()
                ->where('is_active', true)
                ->whereNotNull('next_maintenance_date')
                ->whereDate('next_maintenance_date', '>=', $today)
                ->whereDate('next_maintenance_date', '<=', $today->copy()->addDays(self::AGENDA_DAYS))
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($workUnitIds, fn ($q) => $q->whereIn('work_unit_id', $workUnitIds))
                ->with(['asset:id,asset_name', 'building:id,building_name', 'room:id,room_name'])
                ->orderBy('next_maintenance_date')
                ->limit(20)
                ->get()
                ->map(fn (AssetMaintenanceSchedule $schedule) => [
                    'date' => $schedule->next_maintenance_date,
                    'title' => $schedule->maintenance_type ?: 'Pemeliharaan',
                    'target' => $schedule->asset?->asset_name
                        ?? $schedule->room?->room_name
                        ?? $schedule->building?->building_name
                        ?? 'Aset',
                    'category' => 'Pemeliharaan',
                    'tone' => 'success',
                    'icon' => 'ri-calendar-todo-line',
                    'url' => $this->route('sarpras.pemeliharaan.schedule.index'),
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('urt.agenda.maintenance', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  array<int, string>|null  $workUnitIds
     * @return array<int, array<string, mixed>>
     */
    private function workOrderAgenda(?string $schoolId, ?array $workUnitIds, Carbon $today): array
    {
        try {
            return WorkOrder::query()
                ->whereIn('status', ['draft', 'dispatched', 'in_progress', 'paused', 'waiting_parts'])
                ->whereNotNull('scheduled_date')
                ->whereDate('scheduled_date', '>=', $today)
                ->whereDate('scheduled_date', '<=', $today->copy()->addDays(self::AGENDA_DAYS))
                ->when($schoolId, fn ($q) => $q->whereHas('asset', fn ($a) => $a->where('school_id', $schoolId)))
                ->when($workUnitIds, fn ($q) => $q->whereHas('asset', fn ($a) => $a->whereIn('work_unit_id', $workUnitIds)))
                ->with(['asset:id,asset_name', 'room:id,room_name', 'building:id,building_name', 'assignee:id,name'])
                ->orderBy('scheduled_date')
                ->limit(20)
                ->get()
                ->map(fn (WorkOrder $order) => [
                    'date' => $order->scheduled_date,
                    'title' => $order->scope_of_work ?: 'Work order '.$order->order_number,
                    'target' => $order->target_name,
                    'category' => 'Perbaikan',
                    'tone' => 'warning',
                    'icon' => 'ri-tools-line',
                    'url' => $this->route('sarpras.teknisi.dashboard'),
                    'meta' => $order->assignee?->name,
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('urt.agenda.work_order', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function gardenAgenda(Carbon $today): array
    {
        try {
            return PertamananAgendaItem::query()
                ->where('status', 'belum')
                ->whereHas('agenda', fn ($q) => $q->whereDate('agenda_date', $today))
                ->with('agenda:id,agenda_date')
                ->orderBy('sort_order')
                ->limit(10)
                ->get()
                ->map(fn (PertamananAgendaItem $item) => [
                    'date' => $item->agenda?->agenda_date ?? $today,
                    'title' => $item->title,
                    'target' => $item->group_title ?: 'Pertamanan',
                    'category' => 'Pertamanan',
                    'tone' => 'success',
                    'icon' => 'ri-plant-line',
                    'url' => $this->route('sarpras.pertamanan.agenda.index'),
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('urt.agenda.garden', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  array<int, string>|null  $workUnitIds
     * @return array<int, array<string, mixed>>
     */
    private function loanAgenda(?string $schoolId, ?array $workUnitIds, Carbon $today): array
    {
        try {
            return AssetLoan::query()
                ->where('status', 'dipinjam')
                ->whereNotNull('expected_return_date')
                ->whereDate('expected_return_date', '>=', $today)
                ->whereDate('expected_return_date', '<=', $today->copy()->addDays(self::LOAN_DAYS))
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($workUnitIds, fn ($q) => $q->whereIn('work_unit_id', $workUnitIds))
                ->with(['asset:id,asset_name', 'borrower:id,name'])
                ->orderBy('expected_return_date')
                ->limit(10)
                ->get()
                ->map(fn (AssetLoan $loan) => [
                    'date' => $loan->expected_return_date,
                    'title' => 'Jatuh tempo peminjaman',
                    'target' => $loan->asset?->asset_name ?? 'Aset',
                    'category' => 'Peminjaman',
                    'tone' => 'info',
                    'icon' => 'ri-hand-coin-line',
                    'url' => $this->route('sarpras.peminjaman.index'),
                    'meta' => $loan->borrower?->name,
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('urt.agenda.loan', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  array<int, string>|null  $workUnitIds
     * @return array<int, array<string, mixed>>
     */
    private function procurementAgenda(?string $schoolId, ?array $workUnitIds, Carbon $today): array
    {
        try {
            return ProcurementRequest::query()
                ->whereIn('status', ['approved', 'ordered'])
                ->whereNotNull('delivery_date')
                ->whereDate('delivery_date', '>=', $today)
                ->whereDate('delivery_date', '<=', $today->copy()->addDays(self::AGENDA_DAYS))
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($workUnitIds, fn ($q) => $q->whereIn('work_unit_id', $workUnitIds))
                ->orderBy('delivery_date')
                ->limit(10)
                ->get()
                ->map(fn (ProcurementRequest $request) => [
                    'date' => $request->delivery_date,
                    'title' => 'Perkiraan barang datang',
                    'target' => $request->request_number.' — '.($request->purpose ?: 'Pengadaan'),
                    'category' => 'Pengadaan',
                    'tone' => 'primary',
                    'icon' => 'ri-shopping-cart-2-line',
                    'url' => $this->route('sarpras.pengadaan.index'),
                ])
                ->all();
        } catch (\Throwable $e) {
            Log::warning('urt.agenda.procurement', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @param  array<int, string>|null  $workUnitIds
     * @return array<string, int>
     */
    private function priorityCounts(?string $schoolId, ?array $workUnitIds, Carbon $today): array
    {
        $count = function (callable $query) {
            try {
                return (int) $query()->count();
            } catch (\Throwable $e) {
                Log::warning('urt.priority.count', ['error' => $e->getMessage()]);

                return 0;
            }
        };

        return [
            'maintenance_overdue' => $count(fn () => AssetMaintenanceSchedule::query()
                ->where('is_active', true)
                ->whereDate('next_maintenance_date', '<', $today)
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($workUnitIds, fn ($q) => $q->whereIn('work_unit_id', $workUnitIds))),

            'damage_open' => $count(fn () => RepairRequest::query()
                ->whereIn('status', [
                    RepairRequest::STATUS_VERIFICATION_PENDING,
                    RepairRequest::STATUS_APPROVAL_PENDING,
                    RepairRequest::STATUS_EXECUTION_PENDING,
                ])
                ->when($schoolId, fn ($q) => $q->whereHas('asset', fn ($a) => $a->where('school_id', $schoolId)))
                ->when($workUnitIds, fn ($q) => $q->whereHas('asset', fn ($a) => $a->whereIn('work_unit_id', $workUnitIds)))),

            'outage_open' => $count(fn () => ElectricityOutage::query()
                ->where('status', 'open')
                ->when($workUnitIds, fn ($q) => $q->whereIn('work_unit_id', $workUnitIds))),

            'loans_pending' => $count(fn () => AssetLoan::query()
                ->where('status', 'pending')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($workUnitIds, fn ($q) => $q->whereIn('work_unit_id', $workUnitIds))),

            'procurement_pending' => $count(fn () => ProcurementRequest::query()
                ->where('status', 'pending')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($workUnitIds, fn ($q) => $q->whereIn('work_unit_id', $workUnitIds))),

            'assets_unverified' => $count(fn () => Asset::query()
                ->where('is_active', true)
                ->where('verification_status', 'pending')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($workUnitIds, fn ($q) => $q->whereIn('work_unit_id', $workUnitIds))),

            'reports_pending' => $count(fn () => WorkOrder::query()
                ->whereIn('status', ['completed', 'closed'])
                ->whereDoesntHave('assignmentReport')
                ->when($schoolId, fn ($q) => $q->whereHas('asset', fn ($a) => $a->where('school_id', $schoolId)))
                ->when($workUnitIds, fn ($q) => $q->whereHas('asset', fn ($a) => $a->whereIn('work_unit_id', $workUnitIds)))),

            'disposal_pending' => $count(fn () => Asset::query()
                ->where(function ($q) {
                    $q->where(fn ($qq) => $qq->whereNotNull('disposal_date')->where('condition', '!=', 'dihapus'))
                        ->orWhere(fn ($qq) => $qq->where('condition', 'dihapus')->whereNull('disposal_date'));
                })
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->when($workUnitIds, fn ($q) => $q->whereIn('work_unit_id', $workUnitIds))),
        ];
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<int, array<string, mixed>>
     */
    private function priorities(array $counts): array
    {
        $definitions = [
            ['key' => 'maintenance_overdue', 'label' => 'Pemeliharaan terlambat', 'tone' => 'danger', 'icon' => 'ri-alarm-warning-line', 'route' => 'sarpras.pemeliharaan.schedule.index', 'hint' => 'Sudah melewati tanggal jadwal'],
            ['key' => 'damage_open', 'label' => 'Kerusakan menunggu tindakan', 'tone' => 'danger', 'icon' => 'ri-error-warning-line', 'route' => 'sarpras.repair.index', 'hint' => 'Belum diverifikasi / belum ada work order'],
            ['key' => 'outage_open', 'label' => 'Listrik padam aktif', 'tone' => 'danger', 'icon' => 'ri-flashlight-line', 'route' => 'sarpras.electricity.outages.index', 'hint' => 'Gangguan belum ditutup'],
            ['key' => 'assets_unverified', 'label' => 'Aset belum diverifikasi', 'tone' => 'warning', 'icon' => 'ri-shield-check-line', 'route' => 'sarpras.aset.index', 'hint' => 'Menunggu verifikasi URT'],
            ['key' => 'loans_pending', 'label' => 'Peminjaman menunggu persetujuan', 'tone' => 'warning', 'icon' => 'ri-hand-coin-line', 'route' => 'sarpras.peminjaman.index', 'hint' => 'Belum diserahterimakan'],
            ['key' => 'procurement_pending', 'label' => 'Pengadaan menunggu persetujuan', 'tone' => 'info', 'icon' => 'ri-shopping-cart-2-line', 'route' => 'sarpras.pengadaan.index', 'hint' => 'Usulan belum diproses'],
            ['key' => 'disposal_pending', 'label' => 'Usulan penghapusan', 'tone' => 'info', 'icon' => 'ri-delete-bin-6-line', 'route' => 'sarpras.disposal.pending', 'hint' => 'Menunggu keputusan Kepala URT'],
            ['key' => 'reports_pending', 'label' => 'Pekerjaan selesai belum dilaporkan', 'tone' => 'warning', 'icon' => 'ri-file-list-3-line', 'route' => 'sarpras.assignment-report.index', 'hint' => 'Agenda belum ditutup dengan laporan'],
        ];

        $rows = [];
        foreach ($definitions as $definition) {
            $value = $counts[$definition['key']] ?? 0;
            if ($value <= 0) {
                continue;
            }

            $rows[] = [
                'label' => $definition['label'],
                'hint' => $definition['hint'],
                'count' => $value,
                'tone' => $definition['tone'],
                'icon' => $definition['icon'],
                'url' => $this->route($definition['route']),
            ];
        }

        return $rows;
    }

    private function route(string $name): string
    {
        try {
            return route($name);
        } catch (\Throwable $e) {
            return '#';
        }
    }
}
