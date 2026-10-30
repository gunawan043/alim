<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetBuilding;
use App\Models\ChecklistTemplate;
use App\Models\ChecklistTemplateItem;
use App\Models\CleaningArea;
use App\Models\CleaningProgram;
use App\Models\CleaningTask;
use App\Models\PertamananAgenda;
use App\Models\PertamananAgendaItem;
use App\Models\User;
use App\Models\WasteFacility;
use App\Models\WasteSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Pengaturan Item URT (menu "Pengaturan Item").
 *
 * Konsol terpusat untuk mengatur, menambah, mengelompokkan (group_label),
 * dan memperbarui item yang dipakai modul Kebersihan / Pertamanan / Sampah:
 *
 *  - Area Kerja & Area Taman (cleaning_areas + cleaning_tasks + penugasan)
 *  - Agenda Harian Pertamanan (pertamanan_agendas + items)
 *  - Program Tahunan (cleaning_programs)
 *  - Sumber & Fasilitas Sampah DLH (waste_sources + waste_facilities)
 *  - Template & Item Checklist Pemeliharaan (checklist_templates + items)
 *
 * Untuk CRUD yang sudah punya endpoint sendiri (area/task/program/fasilitas),
 * halaman ini memanggil endpoint yang sama; controller ini melengkapi
 * operasi yang belum punya route (sumber sampah & item template checklist).
 */
class SarprasItemManagerController extends SarprasBaseController
{
    public function index(Request $request)
    {
        $this->authorizeManage();

        $tab = $request->input('tab', 'area');

        return view('sarpras.item-manager', array_merge(
            ['tab' => $tab, 'canManage' => true],
            $this->tabData($request, $tab)
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // SUMBER SAMPAH — lengkapi CRUD (sebelumnya hanya store)
    // ─────────────────────────────────────────────────────────────

    public function updateSource(Request $request, string $userId, string $sourceId)
    {
        $this->authorizeManage();
        $source = WasteSource::findOrFail($sourceId);

        $validated = $request->validate([
            'name' => 'required|string|max:191',
            'source_type' => 'required|in:'.implode(',', WasteSource::TYPE_OPTIONS),
            'building_id' => 'nullable|uuid|exists:asset_buildings,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $source->update($validated);

        return back()->with('success', 'Sumber sampah diperbarui.');
    }

    public function toggleSource(string $userId, string $sourceId)
    {
        $this->authorizeManage();
        $source = WasteSource::findOrFail($sourceId);
        $source->update(['is_active' => ! $source->is_active]);

        return back()->with('success', 'Status sumber sampah diperbarui.');
    }

    public function destroySource(string $userId, string $sourceId)
    {
        $this->authorizeManage();
        $source = WasteSource::findOrFail($sourceId);

        if ($source->logs()->exists()) {
            $source->update(['is_active' => false]);

            return back()->with('success', 'Sumber sudah dipakai pada catatan sampah, jadi dinonaktifkan agar histori tetap aman.');
        }

        $source->delete();

        return back()->with('success', 'Sumber sampah dihapus.');
    }

    // ─────────────────────────────────────────────────────────────
    // ITEM TEMPLATE CHECKLIST PEMELIHARAAN
    // ─────────────────────────────────────────────────────────────

    public function storeChecklistItem(Request $request, string $userId, string $templateId)
    {
        $this->authorizeManage();

        $validated = $this->validateChecklistItem($request);

        ChecklistTemplateItem::create([
            ...$validated,
            'template_id' => $templateId,
            'sequence' => $validated['sequence'] ?? ((int) ChecklistTemplateItem::where('template_id', $templateId)->max('sequence') + 1),
        ]);

        return back()->with('success', 'Item checklist ditambahkan.');
    }

    public function updateChecklistItem(Request $request, string $userId, string $itemId)
    {
        $this->authorizeManage();
        $item = ChecklistTemplateItem::findOrFail($itemId);

        $validated = $this->validateChecklistItem($request);
        $item->update($validated);

        return back()->with('success', 'Item checklist diperbarui.');
    }

    public function destroyChecklistItem(string $userId, string $itemId)
    {
        $this->authorizeManage();
        ChecklistTemplateItem::findOrFail($itemId)->delete();

        return back()->with('success', 'Item checklist dihapus.');
    }

    public function toggleChecklistTemplate(string $userId, string $templateId)
    {
        $this->authorizeManage();
        $template = \App\Models\ChecklistTemplate::findOrFail($templateId);
        $template->update(['is_active' => ! $template->is_active]);

        return back()->with('success', 'Status template checklist diperbarui.');
    }

    public function destroyChecklistTemplate(string $userId, string $templateId)
    {
        $this->authorizeManage();
        $template = \App\Models\ChecklistTemplate::findOrFail($templateId);

        if ($template->instances()->exists()) {
            $template->update(['is_active' => false]);

            return back()->with('success', 'Template sudah dipakai, jadi dinonaktifkan agar histori tetap aman.');
        }

        ChecklistTemplateItem::where('template_id', $template->id)->delete();
        $template->delete();

        return back()->with('success', 'Template checklist dihapus.');
    }

    // ─────────────────────────────────────────────────────────────
    // PENGELOMPOKKAN & URUTAN (regroup / reorder massal)
    // ─────────────────────────────────────────────────────────────

    /**
     * Pindahkan tugas area ke kelompok lain (cleaning_tasks.group_label).
     */
    public function regroupCleaningTasks(Request $request)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'task_ids' => 'required|array|min:1',
            'task_ids.*' => 'uuid',
            'group_label' => 'required|string|max:100',
        ]);

        CleaningTask::whereIn('id', $validated['task_ids'])->update(['group_label' => $validated['group_label']]);

        return back()->with('success', count($validated['task_ids']).' uraian dipindahkan ke kelompok "'.$validated['group_label'].'".');
    }

    /**
     * Ubah kelompok (section) item agenda pertamanan secara massal.
     */
    public function regroupAgendaItems(Request $request)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'item_ids' => 'required|array|min:1',
            'item_ids.*' => 'uuid',
            'section' => 'required|in:pemeliharaan,pembibitan,permintaan_darurat',
        ]);

        PertamananAgendaItem::whereIn('id', $validated['item_ids'])->update(['section' => $validated['section']]);

        return back()->with('success', count($validated['item_ids']).' item agenda dipindahkan ke bagian '.str_replace('_', ' ', $validated['section']).'.');
    }

    // ─────────────────────────────────────────────────────────────
    // AGENDA PERTAMANAN — tambah/ubah atribut item (judul, kelompok, bagian)
    // ─────────────────────────────────────────────────────────────

    /**
     * Perbarui atribut item agenda pertamanan (bukan status harian).
     */
    public function updateAgendaItem(Request $request, string $userId, string $itemId)
    {
        $this->authorizeManage();

        $item = PertamananAgendaItem::findOrFail($itemId);

        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'section' => 'required|in:pemeliharaan,pembibitan,permintaan_darurat',
            'group_title' => 'nullable|string|max:100',
            'officer_id' => 'nullable|uuid|exists:users,id',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $item->update($validated);

        return back()->with('success', 'Item agenda diperbarui.');
    }

    /**
     * Tambah item agenda untuk tanggal tertentu (dari konsol pengaturan).
     */
    public function storeAgendaItem(Request $request)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'agenda_date' => 'required|date',
            'title' => 'required|string|max:191',
            'section' => 'required|in:pemeliharaan,pembibitan,permintaan_darurat',
            'group_title' => 'nullable|string|max:100',
            'officer_id' => 'nullable|uuid|exists:users,id',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $date = \Illuminate\Support\Carbon::parse($validated['agenda_date']);

        $agenda = PertamananAgenda::firstOrCreate(
            ['agenda_date' => $date->toDateString()],
            [
                'academic_year' => $date->month >= 7
                    ? $date->year.'/'.($date->year + 1)
                    : ($date->year - 1).'/'.$date->year,
                'officer_id' => auth()->id(),
                'created_by' => auth()->id(),
            ]
        );

        $agenda->items()->create([
            'section' => $validated['section'],
            'group_title' => $validated['group_title'] ?? null,
            'title' => $validated['title'],
            'status' => 'belum',
            'officer_id' => $validated['officer_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'sort_order' => $validated['sort_order'] ?? (($agenda->items()->max('sort_order') ?? 0) + 1),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Item agenda ditambahkan.');
    }

    // ─────────────────────────────────────────────────────────────
    // DATA HALAMAN
    // ─────────────────────────────────────────────────────────────

    /**
     * Kumpulkan data sesuai tab aktif. Semua tab mengembalikan data
     * TERKELOMPOKKAN agar pengelompokan bisa dilihat/ diubah langsung.
     */
    protected function tabData(Request $request, string $tab): array
    {
        return match ($tab) {
            'agenda' => $this->agendaTab($request),
            'program' => $this->programTab($request),
            'sampah' => $this->sampahTab($request),
            'checklist' => $this->checklistTab($request),
            'area' => $this->areaTab($request),
            default => $this->areaTab($request),
        };
    }

    protected function areaTab(Request $request): array
    {
        $typeFilter = $request->input('area_type');

        $areas = CleaningArea::query()
            ->with(['building', 'room', 'coordinator', 'tasks' => fn ($q) => $q->orderBy('group_label')->orderBy('sort_order'), 'assignments.user'])
            ->when($typeFilter, fn ($q) => $q->where('area_type', $typeFilter))
            ->orderBy('area_type')
            ->orderBy('name')
            ->get();

        // Kelompokkan per area_type untuk tampilan berelompok.
        $grouped = $areas->groupBy('area_type');

        $buildings = AssetBuilding::orderBy('building_name')->get(['id', 'building_name']);
        $rooms = \App\Models\AssetRoom::where('is_active', true)->orderBy('room_name')->get(['id', 'building_id', 'room_name']);
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $coordinators = $this->urtPersonnelOptions();

        return compact('areas', 'grouped', 'buildings', 'rooms', 'users', 'coordinators');
    }

    protected function agendaTab(Request $request): array
    {
        $date = $request->input('date', now()->toDateString());

        try {
            $date = \Illuminate\Support\Carbon::parse($date)->toDateString();
        } catch (\Throwable) {
            $date = now()->toDateString();
        }

        $agenda = PertamananAgenda::with(['items' => fn ($q) => $q->orderBy('section')->orderBy('sort_order')])
            ->whereDate('agenda_date', $date)
            ->first();

        $recentDates = PertamananAgenda::query()
            ->orderByDesc('agenda_date')
            ->limit(30)
            ->pluck('agenda_date');

        $users = $this->urtPersonnelOptions();

        return compact('agenda', 'date', 'recentDates', 'users');
    }

    protected function programTab(Request $request): array
    {
        $year = (int) ($request->input('year', now()->year));

        $programs = CleaningProgram::query()
            ->with(['area', 'building', 'assignee'])
            ->orderBy('category')
            ->orderBy('title')
            ->get()
            ->groupBy('category');

        $areas = CleaningArea::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $buildings = AssetBuilding::orderBy('building_name')->get(['id', 'building_name']);
        $users = $this->urtPersonnelOptions();

        return compact('programs', 'year', 'areas', 'buildings', 'users');
    }

    protected function sampahTab(Request $request): array
    {
        $sources = WasteSource::query()
            ->with(['building', 'logs'])
            ->orderBy('source_type')
            ->orderBy('name')
            ->get()
            ->groupBy('source_type');

        $facilities = WasteFacility::query()
            ->with(['building', 'pic'])
            ->orderBy('facility_type')
            ->orderBy('name')
            ->get()
            ->groupBy('facility_type');

        $buildings = AssetBuilding::orderBy('building_name')->get(['id', 'building_name']);
        $users = $this->urtPersonnelOptions();

        return compact('sources', 'facilities', 'buildings', 'users');
    }

    protected function checklistTab(Request $request): array
    {
        $templates = ChecklistTemplate::query()
            ->with(['items' => fn ($q) => $q->orderBy('group_label')->orderBy('sequence')])
            ->orderBy('workflow_type')
            ->orderBy('name')
            ->get()
            ->groupBy('workflow_type');

        return compact('templates');
    }

    /**
     * Personel URT aktif (koordinator/PIC/penugasan).
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function urtPersonnelOptions()
    {
        $personnel = User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('roles', fn ($role) => $role->whereIn('name', ['Unit Rumah Tangga', 'Petugas Kebersihan', 'Petugas Pertamanan']))
                    ->orWhereHas('gtkWorkUnits', fn ($gtk) => $gtk->where(function ($jabatan) {
                        $jabatan->where('jabatan', 'like', '%RUMAH TANGGA%')
                            ->orWhere('jabatan', 'like', '%KEBERSIHAN%')
                            ->orWhere('jabatan', 'like', '%PERTAMANAN%')
                            ->orWhere('jabatan', 'like', '%DRIVER%')
                            ->orWhere('jabatan', 'like', '%PENGEMUDI%')
                            ->orWhere('jabatan', 'like', '%TEKNISI%');
                    }));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return $personnel->isNotEmpty()
            ? $personnel
            : User::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    protected function validateChecklistItem(Request $request): array
    {
        return $request->validate([
            'group_label' => 'nullable|string|max:100',
            'label' => 'required|string|max:191',
            'standard' => 'nullable|string|max:1000',
            'method' => 'nullable|string|max:191',
            'frequency' => 'nullable|in:'.implode(',', \App\Models\CleaningTask::FREQUENCY_OPTIONS),
            'description' => 'nullable|string|max:1000',
            'response_type' => 'nullable|string|max:30',
            'options' => 'nullable|array',
            'is_required' => 'nullable|boolean',
            'triggers_failure' => 'nullable|boolean',
            'failure_severity' => 'nullable|string|max:30',
            'sequence' => 'nullable|integer|min:0',
        ]);
    }

    protected function authorizeManage(): void
    {
        $user = auth()->user();

        abort_unless((bool) $user, 403);

        if ($this->isCentralSarpras($user) || $this->isUrtLeadership($user)) {
            return;
        }

        abort(403, 'Hanya Kepala/TU/Koordinator Unit Rumah Tangga yang dapat mengatur item URT.');
    }
}
