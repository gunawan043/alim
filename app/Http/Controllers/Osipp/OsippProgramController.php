<?php

namespace App\Http\Controllers\Osipp;

use App\Models\Osipp\OsippProgram;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OsippProgramController extends OsippBaseController
{
    public function index(Request $request)
    {
        $context = $this->context($request);
        $period = $this->workspace->ensurePeriod($context);

        $programs = OsippProgram::query()
            ->with(['responsible.student:id,name,nis,nisn'])
            ->withCount(['activities', 'documents'])
            ->where('period_id', $period->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('name', 'like', '%'.$request->search.'%')
                        ->orWhere('category', 'like', '%'.$request->search.'%')
                        ->orWhere('description', 'like', '%'.$request->search.'%');
                });
            })
            ->orderByRaw("CASE WHEN status = 'running' THEN 0 WHEN status = 'planned' THEN 1 WHEN status = 'completed' THEN 2 ELSE 3 END")
            ->orderBy('start_date')
            ->paginate(20)
            ->withQueryString();

        $members = $this->workspace->memberOptions($period);
        $statusOptions = OsippProgram::STATUS_OPTIONS;

        $programsJson = $programs->getCollection()->map(fn (OsippProgram $program) => [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->description,
            'category' => $program->category,
            'responsible_member_id' => $program->responsible_member_id,
            'start_date' => $program->start_date?->format('Y-m-d'),
            'end_date' => $program->end_date?->format('Y-m-d'),
            'target_output' => $program->target_output,
            'progress' => $program->progress,
            'status' => $program->status,
            'notes' => $program->notes,
            'update_url' => route('osipp.program.update', ['userId' => $context['userId'], 'program' => $program->id]),
        ])->values();

        return view('osipp.program.index', array_merge($context, compact('period', 'programs', 'members', 'statusOptions', 'programsJson')));
    }

    public function store(Request $request)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);

        $data = $this->validated($request, $period->id);

        OsippProgram::create($data + [
            'period_id' => $period->id,
            'created_by' => $context['user']->id,
            'updated_by' => $context['user']->id,
        ]);

        return back()->with('success', 'Program kerja OSIPP ditambahkan.');
    }

    public function update(Request $request, string $userId, OsippProgram $program)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $program->period_id === (string) $period->id, 404);

        $data = $this->validated($request, $period->id);
        $program->update($data + ['updated_by' => $context['user']->id]);

        return back()->with('success', 'Program kerja OSIPP diperbarui.');
    }

    public function updateProgress(Request $request, string $userId, OsippProgram $program)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $program->period_id === (string) $period->id, 404);

        $data = $request->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $program->update([
            'progress' => $data['progress'],
            'updated_by' => $context['user']->id,
        ]);

        return back()->with('success', 'Progres program kerja diperbarui.');
    }

    public function destroy(Request $request, string $userId, OsippProgram $program)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);
        abort_unless((string) $program->period_id === (string) $period->id, 404);

        $program->delete();

        return back()->with('success', 'Program kerja OSIPP dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $periodId): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'responsible_member_id' => [
                'nullable',
                'uuid',
                Rule::exists('osipp_members', 'id')->where(fn ($q) => $q->where('period_id', $periodId)),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'target_output' => ['nullable', 'string', 'max:3000'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(array_keys(OsippProgram::STATUS_OPTIONS))],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }
}
