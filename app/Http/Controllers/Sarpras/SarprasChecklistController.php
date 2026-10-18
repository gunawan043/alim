<?php

namespace App\Http\Controllers\Sarpras;

use App\Models\AssetMaintenanceSchedule;
use App\Models\ChecklistInstance;
use App\Models\ChecklistTemplate;
use App\Models\RepairRequest;
use App\Services\Sarpras\ChecklistEngine;
use App\Services\Sarpras\RepairRequestWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SarprasChecklistController extends SarprasBaseController
{
    public function __construct(protected ChecklistEngine $engine) {}

    public function index(Request $request)
    {
        $query = ChecklistTemplate::withCount('items')
            ->where('workflow_type', 'maintenance')
            ->where('is_active', true);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$s}%")
                ->orWhere('category_slug', 'like', "%{$s}%")
                ->orWhere('code', 'like', "%{$s}%"));
        }
        if ($request->filled('category_slug')) {
            $query->where('category_slug', $request->category_slug);
        }

        $frequencyGroups = [
            'Harian' => ['Harian'],
            'Pekanan' => ['Mingguan', '2 minggu'],
            'Bulanan' => ['Bulanan', 'Per 3 bulan', 'Per 6 bulan', 'Tahunan'],
        ];
        if ($request->filled('frequency_group') && isset($frequencyGroups[$request->frequency_group])) {
            $frequencies = $frequencyGroups[$request->frequency_group];
            $query->whereHas('items', fn ($q) => $q->whereIn('frequency', $frequencies));
        }

        $templates = $query->withCount([
            'items as harian_count' => fn ($q) => $q->whereIn('frequency', $frequencyGroups['Harian']),
            'items as pekanan_count' => fn ($q) => $q->whereIn('frequency', $frequencyGroups['Pekanan']),
            'items as bulanan_count' => fn ($q) => $q->whereIn('frequency', $frequencyGroups['Bulanan']),
        ])->orderBy('name')->get();
        $categories = ChecklistTemplate::where('workflow_type', 'maintenance')
            ->whereNotNull('category_slug')
            ->distinct()
            ->orderBy('category_slug')
            ->pluck('category_slug');

        $myInstances = ChecklistInstance::with(['template', 'executor'])
            ->where('executor_id', auth()->id())
            ->latest()
            ->limit(10)
            ->get();

        return view('sarpras.checklist.index', compact('templates', 'categories', 'myInstances', 'frequencyGroups'));
    }

    public function showTemplate(string $userId, Request $request, string $templateId)
    {
        $template = ChecklistTemplate::with(['items' => fn ($q) => $q->orderBy('sequence')])
            ->where('workflow_type', 'maintenance')
            ->findOrFail($templateId);

        return view('sarpras.checklist.template', compact('template'));
    }

    public function start(string $userId, Request $request, string $scheduleId)
    {
        $schedule = AssetMaintenanceSchedule::with(['asset.room.building', 'room.building', 'building', 'workUnit'])
            ->findOrFail($scheduleId);
        $this->authorizeMaintenanceAccess($schedule, $request);

        $templates = ChecklistTemplate::withCount('items')
            ->where('workflow_type', 'maintenance')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $suggested = $this->suggestTemplate($schedule, $templates);

        return view('sarpras.checklist.start', compact('schedule', 'templates', 'suggested'));
    }

    public function startTemplate(string $userId, Request $request, string $templateId)
    {
        $template = ChecklistTemplate::with('items')
            ->where('workflow_type', 'maintenance')
            ->where('is_active', true)
            ->findOrFail($templateId);

        $instance = $this->engine->start($template, $template, auth()->id());

        return redirect()->route('sarpras.checklist.instance', ['instanceId' => $instance->id])
            ->with('success', 'Checklist '.$template->name.' dimulai. Isi item harian, pekanan, dan bulanan sesuai kondisi.');
    }

    public function store(string $userId, Request $request, string $scheduleId)
    {
        $schedule = AssetMaintenanceSchedule::findOrFail($scheduleId);
        $this->authorizeMaintenanceAccess($schedule, $request);

        $validated = $request->validate([
            'template_id' => 'required|uuid|exists:checklist_templates,id',
        ]);

        $template = ChecklistTemplate::where('workflow_type', 'maintenance')
            ->where('is_active', true)
            ->findOrFail($validated['template_id']);

        $instance = $this->engine->start($schedule, $template, auth()->id());

        return redirect()->route('sarpras.checklist.instance', ['instanceId' => $instance->id])
            ->with('success', 'Checklist pemeliharaan dimulai.');
    }

    public function showInstance(string $userId, Request $request, string $instanceId)
    {
        $instance = ChecklistInstance::with(['template.items', 'responses', 'executor', 'context'])
            ->findOrFail($instanceId);

        $this->authorizeInstance($instance, $request);

        $progress = $this->engine->progress($instance);
        $responses = $instance->responses->keyBy('template_item_id');
        $canEdit = $instance->status === 'in_progress';
        $context = $instance->context;

        return view('sarpras.checklist.instance', compact('instance', 'progress', 'responses', 'canEdit', 'context'));
    }

    public function save(string $userId, Request $request, string $instanceId)
    {
        $instance = ChecklistInstance::with(['template.items', 'responses'])->findOrFail($instanceId);
        $this->authorizeInstance($instance, $request);
        abort_unless($instance->status === 'in_progress', 422, 'Checklist yang sudah selesai tidak dapat diubah.');

        $this->saveAnswers($instance, $request);

        return back()->with('success', 'Progres checklist disimpan.');
    }

    public function complete(string $userId, Request $request, string $instanceId)
    {
        $instance = ChecklistInstance::with(['template.items', 'responses'])->findOrFail($instanceId);
        $this->authorizeInstance($instance, $request);
        abort_unless($instance->status === 'in_progress', 422, 'Checklist sudah diselesaikan.');

        $this->saveAnswers($instance, $request);

        try {
            $instance = $this->engine->complete($instance->fresh(), [
                'completed_by' => auth()->id(),
            ]);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $repairMessage = '';

        if ($instance->status === 'failed') {
            $repairMessage = $this->createRepairFromFindings($instance);
        }

        return redirect()->route('sarpras.checklist.instance', ['instanceId' => $instance->id])
            ->with('success', $instance->status === 'failed'
                ? 'Checklist selesai dengan temuan penyimpangan. Tindak lanjuti item bertanda X.'.$repairMessage
                : 'Checklist selesai tanpa temuan.');
    }

    private function saveAnswers(ChecklistInstance $instance, Request $request): void
    {
        $answers = $request->input('answers', []);
        $notes = $request->input('notes', []);

        foreach ($instance->template->items as $item) {
            $value = $answers[$item->id] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $booleanValue = (bool) ((int) $value);
            $this->engine->record($instance, $item->id, $booleanValue, [
                'notes' => $notes[$item->id] ?? null,
            ]);
        }
    }

    private function authorizeInstance(ChecklistInstance $instance, Request $request): void
    {
        if ($instance->context instanceof AssetMaintenanceSchedule) {
            $this->authorizeMaintenanceAccess($instance->context, $request);
            return;
        }

        abort_unless($this->isCentralSarpras() || $instance->executor_id === auth()->id(), 403);
    }

    private function createRepairFromFindings(ChecklistInstance $instance): string
    {
        $context = $instance->context;
        if (! $context instanceof AssetMaintenanceSchedule || ! $context->asset) {
            return '';
        }

        $failedLabels = $instance->responses()
            ->with('item')
            ->where('passed', false)
            ->get()
            ->map(fn ($response) => $response->item?->label)
            ->filter()
            ->values();

        if ($failedLabels->isEmpty()) {
            return '';
        }

        $asset = $context->asset;

        $existing = RepairRequest::query()
            ->where('asset_id', $asset->id)
            ->whereNotIn('status', ['completed', 'closed', 'verification_rejected', 'approval_rejected'])
            ->where('title', 'like', 'Temuan checklist:%')
            ->exists();

        if ($existing) {
            return ' Temuan sudah ada di antrean perbaikan.';
        }

        try {
            $repair = app(RepairRequestWorkflow::class)->submitDamageReport(
                $asset,
                auth()->user(),
                'Temuan checklist: '.$instance->template?->name,
                'Item menyimpang: '.$failedLabels->implode('; '),
                'normal'
            );

            return ' Repair request '.$repair->request_number.' dibuat otomatis.';
        } catch (\Throwable $e) {
            \Log::warning('Gagal membuat repair request dari checklist: '.$e->getMessage());

            return ' Temuan belum masuk antrean perbaikan; catat manual dari menu Antrean Perbaikan.';
        }
    }

    private function suggestTemplate(AssetMaintenanceSchedule $schedule, $templates): ?ChecklistTemplate
    {
        $text = Str::lower($schedule->maintenance_type.' '.$schedule->notes);
        $map = [
            'genset' => 'genset',
            'pompa' => 'pompa-panel',
            'panel' => 'pompa-panel',
            'listrik' => 'jaringan-listrik',
            'air minum' => 'air-minum',
            'septic' => 'septic-tank',
            'septik' => 'septic-tank',
            'kamar mandi' => 'kamar-mandi',
            'ac' => 'ac',
            'gedung' => 'bangunan',
            'bangunan' => 'bangunan',
        ];

        foreach ($map as $keyword => $slug) {
            if (str_contains($text, $keyword)) {
                $match = $templates->firstWhere('category_slug', $slug);
                if ($match) {
                    return $match;
                }
            }
        }

        return $templates->firstWhere('category_slug', 'bangunan') ?? $templates->first();
    }
}
