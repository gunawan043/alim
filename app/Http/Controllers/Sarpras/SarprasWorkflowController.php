<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\Asset;
use App\Models\AssetBuilding;
use App\Models\AssetLoan;
use App\Models\AssetMaintenanceSchedule;
use App\Models\AssetRoom;
use App\Models\CleaningArea;
use App\Models\CleaningAssignment;
use App\Models\CleaningTask;
use App\Models\ElectricityOutage;
use App\Models\ElectricityOutageAction;
use App\Models\ProcurementRequest;
use App\Models\RepairRequest;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkUnit;
use App\Models\WorkUnitSarprasOfficer;
use Illuminate\Http\Request;

/**
 * Peta alur kerja Unit Rumah Tangga.
 *
 * Menyatukan sembilan prosedur URT dalam satu halaman sehingga rantai
 * satuan kerja -> petugas -> area kerja -> uraian pekerjaan -> eksekusi
 * -> verifikasi terlihat utuh dan setiap tahap punya tombol menuju modulnya.
 */
class SarprasWorkflowController extends SarprasBaseController
{
    public function index(Request $request)
    {
        $this->authorizeWorkflow();

        $counts = $this->flowCounts();
        $procedures = $this->procedures($counts);
        $chain = $this->assignmentChain();

        return view('sarpras.workflow.index', compact('procedures', 'counts', 'chain'));
    }

    public function assignment(Request $request)
    {
        $this->authorizeWorkflow();

        $chain = $this->assignmentChain();
        $form = $this->assignmentFormData();

        return view('sarpras.workflow.assignment', [
            'units' => $chain['units'],
            'areas' => $chain['areas'],
            'officers' => $chain['officers'],
            'areaByUser' => $chain['areaByUser'],
            'openOrders' => $chain['openOrders'],
            'technical' => $chain['technical'],
            'technicalUsers' => $chain['technicalUsers'],
            'gaps' => $chain['gaps'],
            'stats' => $chain['stats'],
            'canManage' => $this->canManageAssignment(),
            'tasks' => $form['tasks'],
            'assignments' => $form['assignments'],
            'users' => $form['users'],
            'buildings' => $form['buildings'],
            'rooms' => $form['rooms'],
            'areaTypes' => CleaningArea::TYPE_OPTIONS,
        ]);
    }

    /**
     * Data pendukung form penambahan (petugas, area, uraian, penugasan).
     *
     * @return array<string, mixed>
     */
    private function assignmentFormData(): array
    {
        $ids = $this->unitScope();

        $tasks = CleaningTask::query()
            ->where('is_active', true)
            ->when($ids, fn ($q) => $q->whereHas('area', fn ($a) => $a->whereIn('work_unit_id', $ids)))
            ->with('area:id,name,area_type,work_unit_id')
            ->orderBy('area_id')
            ->orderBy('sort_order')
            ->get();

        $assignments = CleaningAssignment::query()
            ->where('is_active', true)
            ->when($ids, fn ($q) => $q->whereHas('area', fn ($a) => $a->whereIn('work_unit_id', $ids)))
            ->with(['area:id,name', 'user:id,name', 'backupUser:id,name', 'supervisor:id,name'])
            ->latest()
            ->get();

        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $buildings = AssetBuilding::query()
            ->orderBy('building_name')
            ->get(['id', 'building_name', 'work_unit_id']);

        $rooms = AssetRoom::query()
            ->where('is_active', true)
            ->with('building:id,building_name')
            ->orderBy('room_name')
            ->get(['id', 'building_id', 'room_name', 'work_unit_id']);

        return compact('tasks', 'assignments', 'users', 'buildings', 'rooms');
    }

    private function canManageAssignment(): bool
    {
        if ($this->isCentralSarpras() || $this->isUrtLeadership()) {
            return true;
        }

        $user = auth()->user();

        return $user
            && method_exists($user, 'hasAnyRole')
            && $user->hasAnyRole(['Koordinator Kebersihan', 'Admin Sarpras', 'Unit Rumah Tangga']);
    }

    private function authorizeWorkflow(): void
    {
        $user = auth()->user();
        $allowed = $this->isCentralSarpras()
            || $this->accessibleWorkUnitIds() !== []
            || ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Unit Rumah Tangga', 'Admin Sarpras']));

        abort_unless($allowed, 403, 'Anda tidak memiliki akses ke alur kerja Unit Rumah Tangga.');
    }

    /**
     * Batas unit kerja untuk pengguna non-pusat. null = tanpa batas.
     *
     * @return array<int, string>|null
     */
    private function unitScope(): ?array
    {
        if ($this->isCentralSarpras()) {
            return null;
        }

        $ids = $this->accessibleWorkUnitIds();

        return $ids === [] ? ['00000000-0000-0000-0000-000000000000'] : $ids;
    }

    /**
     * Angka antrean per tahap, dipakai sebagai badge pada peta alur.
     *
     * @return array<string, int>
     */
    private function flowCounts(): array
    {
        $ids = $this->unitScope();

        $assetsPending = Asset::query()
            ->where('is_active', true)
            ->where('verification_status', 'pending')
            ->when($ids, fn ($q) => $q->whereIn('work_unit_id', $ids))
            ->count();

        $damageOpen = RepairRequest::query()
            ->whereIn('status', [
                RepairRequest::STATUS_VERIFICATION_PENDING,
                RepairRequest::STATUS_APPROVAL_PENDING,
                RepairRequest::STATUS_EXECUTION_PENDING,
            ])
            ->when($ids, fn ($q) => $q->whereHas('asset', fn ($a) => $a->whereIn('work_unit_id', $ids)))
            ->count();

        $maintenanceDue = AssetMaintenanceSchedule::query()
            ->where('is_active', true)
            ->whereDate('next_maintenance_date', '<=', today())
            ->when($ids, fn ($q) => $q->whereIn('work_unit_id', $ids))
            ->count();

        $loansPending = AssetLoan::query()
            ->whereIn('status', ['pending', 'approved'])
            ->when($ids, fn ($q) => $q->whereIn('work_unit_id', $ids))
            ->count();

        $procurementOpen = ProcurementRequest::query()
            ->whereIn('status', ['draft', 'pending', 'approved', 'ordered'])
            ->when($ids, fn ($q) => $q->whereIn('work_unit_id', $ids))
            ->count();

        $disposalPending = Asset::query()
            ->where(function ($q) {
                $q->where(fn ($qq) => $qq->whereNotNull('disposal_date')->where('condition', '!=', 'dihapus'))
                    ->orWhere(fn ($qq) => $qq->where('condition', 'dihapus')->whereNull('disposal_date'));
            })
            ->when($ids, fn ($q) => $q->whereIn('work_unit_id', $ids))
            ->count();

        $outageOpen = ElectricityOutage::query()
            ->where('status', 'open')
            ->when($ids, fn ($q) => $q->whereIn('work_unit_id', $ids))
            ->count();

        $workOrdersOpen = WorkOrder::query()
            ->whereIn('status', ['draft', 'dispatched', 'in_progress', 'paused', 'waiting_parts'])
            ->when($ids, fn ($q) => $q->whereHas('asset', fn ($a) => $a->whereIn('work_unit_id', $ids)))
            ->count();

        // Pekerjaan selesai yang belum dilaporkan = agenda yang belum ditutup.
        $reportsPending = WorkOrder::query()
            ->whereIn('status', ['completed', 'closed'])
            ->whereDoesntHave('assignmentReport')
            ->when($ids, fn ($q) => $q->whereHas('asset', fn ($a) => $a->whereIn('work_unit_id', $ids)))
            ->count();

        return [
            'assets_pending' => $assetsPending,
            'damage_open' => $damageOpen,
            'maintenance_due' => $maintenanceDue,
            'loans_pending' => $loansPending,
            'procurement_open' => $procurementOpen,
            'disposal_pending' => $disposalPending,
            'outage_open' => $outageOpen,
            'work_orders_open' => $workOrdersOpen,
            'reports_pending' => $reportsPending,
        ];
    }

    /**
     * Rantai penugasan: satuan kerja -> petugas -> area -> uraian -> eksekusi.
     *
     * @return array<string, mixed>
     */
    private function assignmentChain(): array
    {
        $ids = $this->unitScope();

        $units = WorkUnit::query()
            ->where('is_active', true)
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->withCount([
                'sarprasOfficers as active_officers_count' => fn ($q) => $q->where('is_active', true),
                'assets as assets_count',
            ])
            ->orderBy('name')
            ->get();

        $areas = CleaningArea::query()
            ->where('is_active', true)
            ->when($ids, fn ($q) => $q->whereIn('work_unit_id', $ids))
            ->with(['workUnit:id,name', 'coordinator:id,name'])
            ->withCount([
                'tasks as active_tasks_count' => fn ($q) => $q->where('is_active', true),
                'assignments as active_assignments_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('name')
            ->get();

        $officers = WorkUnitSarprasOfficer::query()
            ->where('is_active', true)
            ->when($ids, fn ($q) => $q->whereIn('work_unit_id', $ids))
            ->with(['user:id,name', 'workUnit:id,name'])
            ->orderByDesc('is_primary')
            ->orderBy('work_unit_id')
            ->get();

        $areaByUser = CleaningAssignment::query()
            ->where('is_active', true)
            ->whereIn('user_id', $officers->pluck('user_id')->all())
            ->with('area:id,name')
            ->get()
            ->groupBy('user_id');

        $openOrders = WorkOrder::query()
            ->whereIn('assignee_id', $officers->pluck('user_id')->all())
            ->whereIn('status', ['draft', 'dispatched', 'in_progress', 'paused', 'waiting_parts'])
            ->selectRaw('assignee_id, count(*) as total')
            ->groupBy('assignee_id')
            ->pluck('total', 'assignee_id');

        $gaps = [
            'units_without_officer' => $units->where('active_officers_count', 0),
            'areas_without_officer' => $areas->where('active_assignments_count', 0),
            'areas_without_task' => $areas->where('active_tasks_count', 0),
        ];

        // Penanggung jawab teknis: jadwal pemeliharaan, work order berjalan,
        // dan tindakan listrik padam per orang. Melengkapi rantai penugasan
        // untuk prosedur 2, 3, dan 7 yang tidak memakai area kebersihan.
        $maintenanceByUser = AssetMaintenanceSchedule::query()
            ->where('is_active', true)
            ->whereNotNull('responsible_user_id')
            ->when($ids, fn ($q) => $q->whereIn('work_unit_id', $ids))
            ->selectRaw(
                'responsible_user_id, count(*) as total, sum(case when next_maintenance_date <= ? then 1 else 0 end) as overdue',
                [today()->toDateString()]
            )
            ->groupBy('responsible_user_id')
            ->get();

        $ordersByAssignee = WorkOrder::query()
            ->whereNotNull('assignee_id')
            ->whereIn('status', ['draft', 'dispatched', 'in_progress', 'paused', 'waiting_parts'])
            ->when($ids, fn ($q) => $q->whereHas('asset', fn ($a) => $a->whereIn('work_unit_id', $ids)))
            ->selectRaw('assignee_id, count(*) as total')
            ->groupBy('assignee_id')
            ->get();

        $outagesByOfficer = ElectricityOutageAction::query()
            ->whereNotNull('officer_id')
            ->selectRaw('officer_id, count(*) as total')
            ->groupBy('officer_id')
            ->get();

        $technical = [];
        $blank = ['maintenance' => 0, 'overdue' => 0, 'work_orders' => 0, 'outages' => 0];

        foreach ($maintenanceByUser as $row) {
            $technical[$row->responsible_user_id] = [
                'maintenance' => (int) $row->total,
                'overdue' => (int) $row->overdue,
                'work_orders' => 0,
                'outages' => 0,
            ];
        }
        foreach ($ordersByAssignee as $row) {
            $technical[$row->assignee_id] ??= $blank;
            $technical[$row->assignee_id]['work_orders'] = (int) $row->total;
        }
        foreach ($outagesByOfficer as $row) {
            $technical[$row->officer_id] ??= $blank;
            $technical[$row->officer_id]['outages'] = (int) $row->total;
        }

        $technicalUsers = $technical === []
            ? collect()
            : User::query()->whereIn('id', array_keys($technical))->pluck('name', 'id');

        return [
            'units' => $units,
            'areas' => $areas,
            'officers' => $officers,
            'areaByUser' => $areaByUser,
            'openOrders' => $openOrders,
            'technical' => $technical,
            'technicalUsers' => $technicalUsers,
            'gaps' => $gaps,
            'stats' => [
                'units' => $units->count(),
                'units_covered' => $units->where('active_officers_count', '>', 0)->count(),
                'officers' => $officers->count(),
                'areas' => $areas->count(),
                'areas_covered' => $areas->where('active_assignments_count', '>', 0)->count(),
                'tasks' => (int) $areas->sum('active_tasks_count'),
                'gaps' => collect($gaps)->sum(fn ($rows) => $rows->count()),
            ],
        ];
    }

    /**
     * Definisi sembilan prosedur URT beserta tombol setiap tahap.
     *
     * @param  array<string, int>  $counts
     * @return array<int, array<string, mixed>>
     */
    private function procedures(array $counts): array
    {
        return [
            [
                'key' => 'pendataan-aset',
                'no' => 1,
                'title' => 'Prosedur Pendataan Aset',
                'icon' => 'ri-archive-2-line',
                'tone' => 'primary',
                'summary' => 'Satuan kerja mencatat aset, URT memverifikasi, lalu aset diberi QR dan masuk buku induk.',
                'owner' => 'Satuan Kerja → URT',
                'steps' => [
                    ['label' => 'Satuan kerja mencatat aset', 'actor' => 'Kepala unit / petugas unit', 'route' => 'sarpras.user.aset.create', 'cta' => 'Catat Aset', 'desc' => 'Aset baru, ruang, dan kondisi dicatat oleh unit pemilik.'],
                    ['label' => 'Import massal (opsional)', 'actor' => 'Petugas unit / URT', 'route' => 'sarpras.aset.import', 'cta' => 'Import Aset', 'desc' => 'Untuk pendataan awal banyak aset sekaligus dari template.'],
                    ['label' => 'URT memverifikasi', 'actor' => 'Kepala / Koordinator URT', 'route' => 'sarpras.aset.index', 'params' => ['verification_status' => 'pending'], 'cta' => 'Verifikasi', 'badge' => 'assets_pending', 'desc' => 'Aset dari unit berstatus pending sampai disetujui URT.'],
                    ['label' => 'Cetak & tempel QR', 'actor' => 'URT', 'route' => 'sarpras.qr.index', 'cta' => 'Generate QR', 'desc' => 'Setiap aset terverifikasi mendapat label QR untuk audit dan scan.'],
                    ['label' => 'Stock opname', 'actor' => 'URT + unit', 'route' => 'sarpras.stock-opname.index', 'cta' => 'Mulai Opname', 'desc' => 'Cocokkan fisik dengan catatan, temukan aset hilang/berlebih.'],
                    ['label' => 'Buku induk & kartu inventaris', 'actor' => 'URT', 'route' => 'sarpras.laporan.buku-induk', 'cta' => 'Lihat Buku Induk', 'desc' => 'Hasil pendataan menjadi laporan resmi inventaris.'],
                ],
            ],
            [
                'key' => 'pemeliharaan-sarpras',
                'no' => 2,
                'title' => 'Prosedur Pemeliharaan Sarpras',
                'icon' => 'ri-calendar-todo-line',
                'tone' => 'success',
                'summary' => 'Jadwal preventif disetujui unit, diterbitkan menjadi work order, dieksekusi teknisi, lalu dicatat sebagai riwayat.',
                'owner' => 'URT → Teknisi',
                'steps' => [
                    ['label' => 'Susun jadwal pemeliharaan', 'actor' => 'Koordinator URT', 'route' => 'sarpras.pemeliharaan.schedule.create', 'cta' => 'Buat Jadwal', 'desc' => 'Tentukan aset/ruang, jenis perawatan, frekuensi, dan penanggung jawab.'],
                    ['label' => 'Persetujuan unit (PIC)', 'actor' => 'Koordinator / PIC', 'route' => 'sarpras.pic.index', 'cta' => 'PIC Approval', 'badge' => 'maintenance_due', 'desc' => 'Jadwal yang menyentuh unit lain diverifikasi PIC unit tersebut.'],
                    ['label' => 'Persetujuan Kepala URT', 'actor' => 'Kepala URT', 'route' => 'sarpras.kepala.index', 'cta' => 'Kepala Approval', 'desc' => 'Anggaran dan jadwal disetujui sebelum work order terbit.'],
                    ['label' => 'Terbitkan work order', 'actor' => 'Koordinator URT', 'route' => 'sarpras.pemeliharaan.schedule.index', 'cta' => 'Buka Jadwal', 'badge' => 'work_orders_open', 'desc' => 'Work order menentukan teknisi (assignee) dan tanggal pelaksanaan.'],
                    ['label' => 'Eksekusi + checklist', 'actor' => 'Teknisi', 'route' => 'sarpras.teknisi.dashboard', 'cta' => 'Workspace Teknisi', 'desc' => 'Teknisi mulai, jeda, menyelesaikan, dan mengisi checklist pekerjaan.'],
                    ['label' => 'Isi laporan pekerjaan', 'actor' => 'Teknisi', 'route' => 'sarpras.assignment-report.index', 'cta' => 'Laporan Penugasan', 'badge' => 'reports_pending', 'desc' => 'Apa yang dikerjakan, kendala, dan tindak lanjut dicatat singkat sebagai bukti tuntas.'],
                    ['label' => 'Catat riwayat & biaya', 'actor' => 'Teknisi / URT', 'route' => 'sarpras.pemeliharaan.log.index', 'cta' => 'Riwayat Perawatan', 'desc' => 'Biaya, kondisi sebelum-sesudah, dan jadwal berikutnya tersimpan otomatis.'],
                ],
            ],
            [
                'key' => 'perbaikan-sarpras',
                'no' => 3,
                'title' => 'Prosedur Perbaikan Sarpras',
                'icon' => 'ri-tools-line',
                'tone' => 'warning',
                'summary' => 'Kerusakan dilaporkan siapa pun, diverifikasi URT, diputuskan repair/replace, lalu dikerjakan lewat work order.',
                'owner' => 'Satuan Kerja → URT → Teknisi',
                'steps' => [
                    ['label' => 'Lapor kerusakan', 'actor' => 'Siapa pun (unit/URT)', 'route' => 'sarpras.user.kerusakan.create', 'cta' => 'Lapor Kerusakan', 'desc' => 'Foto, lokasi, dan tingkat kerusakan dicatat dari lapangan.'],
                    ['label' => 'Verifikasi laporan', 'actor' => 'Koordinator URT', 'route' => 'sarpras.repair.index', 'cta' => 'Antrean Perbaikan', 'badge' => 'damage_open', 'desc' => 'Laporan dicek kelayakannya; bisa minta informasi tambahan.'],
                    ['label' => 'Putuskan repair vs replace', 'actor' => 'Koordinator / Kepala', 'route' => 'sarpras.rvr.index', 'cta' => 'Repair vs Replace', 'desc' => 'Bandingkan biaya perbaikan dengan nilai aset sebelum eksekusi.'],
                    ['label' => 'Terbitkan work order', 'actor' => 'Koordinator URT', 'route' => 'sarpras.repair.index', 'cta' => 'Buat WO', 'desc' => 'Tunjuk teknisi dan target penyelesaian.'],
                    ['label' => 'Eksekusi teknisi', 'actor' => 'Teknisi', 'route' => 'sarpras.teknisi.dashboard', 'cta' => 'Workspace Teknisi', 'desc' => 'Kondisi aset diperbarui setelah pekerjaan selesai.'],
                    ['label' => 'Isi laporan pekerjaan', 'actor' => 'Teknisi', 'route' => 'sarpras.assignment-report.index', 'cta' => 'Laporan Penugasan', 'badge' => 'reports_pending', 'desc' => 'Hasil kerja dan kendala dicatat agar agenda perbaikan jelas selesai.'],
                    ['label' => 'Riwayat perbaikan', 'actor' => 'URT', 'route' => 'sarpras.pemeliharaan.log.index', 'params' => ['log_type' => 'korektif'], 'cta' => 'Riwayat Perbaikan', 'desc' => 'Jejak biaya dan hasil perbaikan tersimpan per aset.'],
                ],
            ],
            [
                'key' => 'peminjaman-barang',
                'no' => 4,
                'title' => 'Prosedur Peminjaman Barang Unit Rumah Tangga',
                'icon' => 'ri-hand-coin-line',
                'tone' => 'info',
                'summary' => 'Pengajuan peminjaman disetujui URT, diserahterimakan, lalu dikembalikan dan dicatat kondisinya.',
                'owner' => 'Satuan Kerja → URT',
                'steps' => [
                    ['label' => 'Ajukan peminjaman', 'actor' => 'Pemohon / unit', 'route' => 'sarpras.peminjaman.create', 'cta' => 'Ajukan Pinjam', 'desc' => 'Pilih aset tersedia, tanggal pinjam, dan tanggal kembali.'],
                    ['label' => 'Persetujuan URT', 'actor' => 'Koordinator / Kepala', 'route' => 'sarpras.peminjaman.index', 'cta' => 'Proses', 'badge' => 'loans_pending', 'desc' => 'Cek ketersediaan dan riwayat peminjam sebelum menyetujui.'],
                    ['label' => 'Serah terima barang', 'actor' => 'URT + peminjam', 'route' => 'sarpras.peminjaman.index', 'cta' => 'Serah Terima', 'desc' => 'Status aset berubah menjadi dipinjam dan tercatat pada kartu aset.'],
                    ['label' => 'Pengembalian', 'actor' => 'Peminjam + URT', 'route' => 'sarpras.peminjaman.index', 'cta' => 'Terima Kembali', 'desc' => 'Kondisi kembali dicatat; keterlambatan ditandai otomatis.'],
                    ['label' => 'Buku peminjaman', 'actor' => 'URT', 'route' => 'sarpras.laporan.buku-peminjaman', 'cta' => 'Lihat Laporan', 'desc' => 'Rekap peminjaman untuk audit dan evaluasi.'],
                ],
            ],
            [
                'key' => 'penghapusan-aset',
                'no' => 5,
                'title' => 'Prosedur Penghapusan Aset',
                'icon' => 'ri-delete-bin-6-line',
                'tone' => 'danger',
                'summary' => 'Usulan penghapusan diajukan dari kartu aset, ditelaah, disetujui Kepala URT, lalu dieksekusi dan dicatat.',
                'owner' => 'URT → Kepala URT',
                'steps' => [
                    ['label' => 'Usulkan penghapusan', 'actor' => 'Unit pemilik / URT', 'route' => 'sarpras.aset.index', 'cta' => 'Pilih Aset', 'desc' => 'Buka kartu aset lalu tekan "Usulkan Penghapusan" beserta alasan dan metode.'],
                    ['label' => 'Telaah kondisi & nilai', 'actor' => 'Koordinator URT', 'route' => 'sarpras.disposal.pending', 'cta' => 'Antrean Penghapusan', 'badge' => 'disposal_pending', 'desc' => 'Pastikan aset tidak layak pakai dan bukan barang pinjaman.'],
                    ['label' => 'Persetujuan Kepala URT', 'actor' => 'Kepala URT', 'route' => 'sarpras.disposal.pending', 'cta' => 'Setujui', 'desc' => 'Penghapusan mengubah kondisi aset menjadi dihapus dan menonaktifkannya.'],
                    ['label' => 'Eksekusi (jual / scrap / hibah)', 'actor' => 'URT', 'route' => 'sarpras.disposal.pending', 'cta' => 'Catat Eksekusi', 'desc' => 'Nilai jual, pembeli, dan nomor bukti dicatat untuk audit.'],
                    ['label' => 'Perbarui buku induk', 'actor' => 'URT', 'route' => 'sarpras.laporan.buku-induk', 'cta' => 'Buku Induk', 'desc' => 'Aset dihapus dari inventaris aktif, riwayatnya tetap tersimpan.'],
                ],
            ],
            [
                'key' => 'pengadaan-sarpras',
                'no' => 6,
                'title' => 'Prosedur Pengadaan Sarana Prasarana',
                'icon' => 'ri-shopping-cart-2-line',
                'tone' => 'primary',
                'summary' => 'Usulan unit menjadi permintaan pengadaan, disetujui, dipesan ke vendor, diterima, lalu dikonversi menjadi aset.',
                'owner' => 'Satuan Kerja → URT → Vendor',
                'steps' => [
                    ['label' => 'Usulan kebutuhan unit', 'actor' => 'Satuan kerja', 'route' => 'sarpras.user.pengadaan.index', 'cta' => 'Usulkan', 'desc' => 'Unit mengajukan kebutuhan; jalur mandiri diarahkan ke SIPLah.'],
                    ['label' => 'Susun permintaan pengadaan', 'actor' => 'URT', 'route' => 'sarpras.pengadaan.create', 'cta' => 'Buat Pengadaan', 'desc' => 'Rincian barang, anggaran, urgensi, dan sumber dana.'],
                    ['label' => 'Persetujuan', 'actor' => 'Kepala URT / pimpinan', 'route' => 'sarpras.pengadaan.index', 'cta' => 'Proses', 'badge' => 'procurement_open', 'desc' => 'Permintaan disetujui atau ditolak dengan alasan.'],
                    ['label' => 'Purchase order ke vendor', 'actor' => 'URT', 'route' => 'sarpras.po.index', 'cta' => 'Purchase Order', 'desc' => 'PO diterbitkan dan dipantau sampai barang datang.'],
                    ['label' => 'Penerimaan barang', 'actor' => 'URT + unit', 'route' => 'sarpras.pengadaan.index', 'cta' => 'Terima Barang', 'desc' => 'Jumlah dan kondisi barang dicek saat serah terima.'],
                    ['label' => 'Konversi menjadi aset', 'actor' => 'URT', 'route' => 'sarpras.pengadaan.index', 'cta' => 'Konversi Aset', 'desc' => 'Barang masuk inventaris dan lanjut ke Prosedur 1 (pendataan).'],
                ],
            ],
            [
                'key' => 'listrik-padam',
                'no' => 7,
                'title' => 'Prosedur Penanganan Listrik Padam',
                'icon' => 'ri-flashlight-line',
                'tone' => 'warning',
                'summary' => 'Gangguan dilaporkan, teknisi ditugaskan, tindakan dicatat, lalu gangguan ditutup dengan bukti foto.',
                'owner' => 'Pelapor → URT → Teknisi',
                'steps' => [
                    ['label' => 'Lapor gangguan', 'actor' => 'Siapa pun', 'route' => 'sarpras.electricity.outages.index', 'cta' => 'Lapor Padam', 'desc' => 'Waktu, lokasi, jenis (terencana/tidak), dan penyebab awal dicatat.'],
                    ['label' => 'Tugaskan teknisi', 'actor' => 'Koordinator URT', 'route' => 'sarpras.electricity.outages.index', 'cta' => 'Buka Gangguan', 'badge' => 'outage_open', 'desc' => 'Gangguan terbuka masuk antrean penanganan teknisi listrik.'],
                    ['label' => 'Catat tindakan', 'actor' => 'Teknisi', 'route' => 'sarpras.electricity.outages.index', 'cta' => 'Tindakan', 'desc' => 'Setiap tindakan dicatat berurutan beserta waktu dan pelakunya.'],
                    ['label' => 'Tutup gangguan', 'actor' => 'Teknisi / URT', 'route' => 'sarpras.electricity.outages.index', 'cta' => 'Selesaikan', 'desc' => 'Waktu pulih dan foto sebelum-sesudah menjadi bukti penyelesaian.'],
                    ['label' => 'Riwayat & token listrik', 'actor' => 'URT', 'route' => 'sarpras.electricity.index', 'cta' => 'Listrik & Token', 'desc' => 'Riwayat gangguan dan pembelian token terpantau per unit.'],
                ],
            ],
            [
                'key' => 'pemeliharaan-kebersihan',
                'no' => 8,
                'title' => 'Prosedur Pemeliharaan Kebersihan',
                'icon' => 'ri-brush-line',
                'tone' => 'success',
                'summary' => 'Area kerja dibagi, uraian pekerjaan disusun, petugas ditugaskan, checklist harian diisi, lalu diverifikasi.',
                'owner' => 'URT → Petugas Kebersihan',
                'steps' => [
                    ['label' => 'Tetapkan area kerja', 'actor' => 'Koordinator URT', 'route' => 'sarpras.kebersihan.areas', 'cta' => 'Area Kerja', 'desc' => 'Area mengikuti gedung/ruang master Sarpras atau area non-ruang.'],
                    ['label' => 'Susun uraian pekerjaan', 'actor' => 'Koordinator URT', 'route' => 'sarpras.kebersihan.areas', 'cta' => 'Uraian', 'desc' => 'Uraian, standar, metode, dan frekuensi per area.'],
                    ['label' => 'Tugaskan petugas + backup', 'actor' => 'Koordinator URT', 'route' => 'sarpras.kebersihan.areas', 'cta' => 'Tugaskan', 'desc' => 'Satu area punya petugas utama, backup, dan supervisor.'],
                    ['label' => 'Checklist harian', 'actor' => 'Petugas kebersihan', 'route' => 'sarpras.kebersihan.report.create', 'cta' => 'Isi Checklist', 'desc' => 'Petugas mengisi capaian per uraian beserta foto bukti.'],
                    ['label' => 'Verifikasi laporan', 'actor' => 'Koordinator URT', 'route' => 'sarpras.kebersihan.reports', 'cta' => 'Verifikasi', 'desc' => 'Laporan difinalisasi dan diverifikasi; temuan menjadi laporan kerusakan.'],
                    ['label' => 'Program tahunan', 'actor' => 'Koordinator URT', 'route' => 'sarpras.kebersihan.programs', 'cta' => 'Program', 'desc' => 'Pekerjaan besar (gotong royong, fogging) dijadwalkan per bulan.'],
                ],
            ],
            [
                'key' => 'pemeliharaan-taman',
                'no' => 9,
                'title' => 'Prosedur Pemeliharaan Taman',
                'icon' => 'ri-plant-line',
                'tone' => 'success',
                'summary' => 'Area taman dibagi, petugas taman ditugaskan, agenda harian dijalankan, dan hasilnya dilaporkan.',
                'owner' => 'URT → Petugas Pertamanan',
                'steps' => [
                    ['label' => 'Tetapkan area taman', 'actor' => 'Koordinator URT', 'route' => 'sarpras.kebersihan.areas', 'params' => ['area_type' => 'taman'], 'cta' => 'Area Taman', 'desc' => 'Taman, jalur hijau, dan area tanam didaftarkan sebagai area kerja.'],
                    ['label' => 'Uraian pekerjaan taman', 'actor' => 'Koordinator URT', 'route' => 'sarpras.kebersihan.areas', 'params' => ['area_type' => 'taman'], 'cta' => 'Uraian', 'desc' => 'Penyiraman, pemangkasan, pemupukan, dan pengendalian hama.'],
                    ['label' => 'Tugaskan petugas taman', 'actor' => 'Koordinator URT', 'route' => 'sarpras.kebersihan.areas', 'params' => ['area_type' => 'taman'], 'cta' => 'Tugaskan', 'desc' => 'Petugas utama dan backup per area taman.'],
                    ['label' => 'Agenda harian', 'actor' => 'Petugas pertamanan', 'route' => 'sarpras.pertamanan.agenda.index', 'cta' => 'Agenda Harian', 'desc' => 'Pemeliharaan, pembibitan, dan permintaan darurat dicatat per hari.'],
                    ['label' => 'Checklist & laporan', 'actor' => 'Petugas pertamanan', 'route' => 'sarpras.kebersihan.report.create', 'params' => ['area_type' => 'taman'], 'cta' => 'Isi Checklist', 'desc' => 'Hasil kerja harian dilaporkan dengan bukti foto.'],
                    ['label' => 'Program pertamanan', 'actor' => 'Koordinator URT', 'route' => 'sarpras.kebersihan.programs', 'params' => ['area_type' => 'taman'], 'cta' => 'Program', 'desc' => 'Penanaman dan perawatan besar dijadwalkan per bulan.'],
                ],
            ],
        ];
    }
}
