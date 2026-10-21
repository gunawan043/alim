<?php

namespace App\Http\Controllers\Osipp;

use App\Models\Osipp\OsippActivity;
use App\Models\Osipp\OsippDocument;
use App\Models\Osipp\OsippProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OsippDocumentController extends OsippBaseController
{
    public function index(Request $request)
    {
        $context = $this->context($request);
        $period = $this->workspace->ensurePeriod($context);

        $documents = OsippDocument::query()
            ->with([
                'program:id,name',
                'activity:id,title',
                'verifier:id,name',
            ])
            ->where('period_id', $period->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('document_type'), fn ($q) => $q->where('document_type', $request->document_type))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->where('title', 'like', '%'.$request->search.'%')
                        ->orWhere('notes', 'like', '%'.$request->search.'%');
                });
            })
            ->orderByRaw("CASE WHEN status = 'submitted' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $programs = OsippProgram::query()->where('period_id', $period->id)->orderBy('name')->get(['id', 'name']);
        $activities = OsippActivity::query()->where('period_id', $period->id)->orderByDesc('activity_date')->get(['id', 'title']);
        $statusOptions = OsippDocument::STATUS_OPTIONS;
        $typeOptions = OsippDocument::TYPE_OPTIONS;

        $summary = [
            'total' => OsippDocument::query()->where('period_id', $period->id)->count(),
            'submitted' => OsippDocument::query()->where('period_id', $period->id)->where('status', OsippDocument::STATUS_SUBMITTED)->count(),
            'verified' => OsippDocument::query()->where('period_id', $period->id)->where('status', OsippDocument::STATUS_VERIFIED)->count(),
            'revision' => OsippDocument::query()->where('period_id', $period->id)->where('status', OsippDocument::STATUS_REVISION)->count(),
        ];

        $documentsJson = $documents->getCollection()->map(fn (OsippDocument $document) => [
            'id' => $document->id,
            'title' => $document->title,
            'document_type' => $document->document_type,
            'program_id' => $document->program_id,
            'activity_id' => $document->activity_id,
            'external_url' => $document->external_url,
            'notes' => $document->notes,
            'status' => $document->status,
            'has_file' => (bool) $document->file_path,
            'verification_notes' => $document->verification_notes,
            'update_url' => route('osipp.administrasi.update', ['userId' => $context['userId'], 'document' => $document->id]),
        ])->values();

        return view('osipp.administrasi.index', array_merge($context, compact(
            'period', 'documents', 'programs', 'activities', 'statusOptions', 'typeOptions', 'documentsJson', 'summary'
        )));
    }

    public function store(Request $request)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $period = $this->workspace->ensurePeriod($context);

        $data = $this->validated($request, $period->id);
        $file = $request->file('file');

        if (! $file && empty($data['external_url'])) {
            throw ValidationException::withMessages([
                'file' => 'Unggah berkas atau isi tautan dokumen.',
            ]);
        }

        if ($file) {
            $data['file_path'] = $file->store('osipp/'.$context['schoolId'].'/'.$period->id, 'local');
        }

        unset($data['file']);
        OsippDocument::create($data + [
            'period_id' => $period->id,
            'status' => OsippDocument::STATUS_DRAFT,
            'created_by' => $context['user']->id,
            'updated_by' => $context['user']->id,
        ]);

        return back()->with('success', 'Dokumen administrasi OSIPP ditambahkan sebagai draft.');
    }

    public function update(Request $request, string $userId, OsippDocument $document)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $this->authorizePeriod($document->period);
        abort_unless($document->isEditable(), 422, 'Dokumen yang sudah diverifikasi tidak dapat diedit.');

        $data = $this->validated($request, $document->period_id, true);
        $file = $request->file('file');

        if ($file) {
            if ($document->file_path) {
                Storage::disk('local')->delete($document->file_path);
            }
            $data['file_path'] = $file->store('osipp/'.$context['schoolId'].'/'.$document->period_id, 'local');
        }

        unset($data['file']);
        $document->update($data + ['updated_by' => $context['user']->id]);

        return back()->with('success', 'Dokumen administrasi OSIPP diperbarui.');
    }

    public function submit(Request $request, string $userId, OsippDocument $document)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $this->authorizePeriod($document->period);

        $this->workflow->transitionDocument($document, OsippDocument::STATUS_SUBMITTED, $request->user());

        return back()->with('success', 'Dokumen diajukan untuk verifikasi.');
    }

    public function verify(Request $request, string $userId, OsippDocument $document)
    {
        $context = $this->context($request);
        $this->ensureVerify($context);
        $this->authorizePeriod($document->period);

        $data = $request->validate(['verification_notes' => ['nullable', 'string', 'max:3000']]);
        $this->workflow->transitionDocument($document, OsippDocument::STATUS_VERIFIED, $request->user(), $data['verification_notes'] ?? null);

        return back()->with('success', 'Dokumen administrasi berhasil diverifikasi.');
    }

    public function revision(Request $request, string $userId, OsippDocument $document)
    {
        $context = $this->context($request);
        $this->ensureVerify($context);
        $this->authorizePeriod($document->period);

        $data = $request->validate(['verification_notes' => ['required', 'string', 'max:3000']]);
        $this->workflow->transitionDocument($document, OsippDocument::STATUS_REVISION, $request->user(), $data['verification_notes']);

        return back()->with('success', 'Dokumen dikembalikan untuk revisi.');
    }

    public function reject(Request $request, string $userId, OsippDocument $document)
    {
        $context = $this->context($request);
        $this->ensureVerify($context);
        $this->authorizePeriod($document->period);

        $data = $request->validate(['verification_notes' => ['required', 'string', 'max:3000']]);
        $this->workflow->transitionDocument($document, OsippDocument::STATUS_REJECTED, $request->user(), $data['verification_notes']);

        return back()->with('success', 'Dokumen administrasi ditolak.');
    }

    public function download(Request $request, string $userId, OsippDocument $document)
    {
        $this->authorizePeriod($document->period);

        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            return Storage::disk('local')->download($document->file_path, basename($document->file_path));
        }

        if ($document->external_url) {
            return redirect()->away($document->external_url);
        }

        abort(404, 'Berkas dokumen tidak ditemukan.');
    }

    public function destroy(Request $request, string $userId, OsippDocument $document)
    {
        $context = $this->context($request);
        $this->ensureManage($context);
        $this->authorizePeriod($document->period);
        abort_unless($document->isEditable(), 422, 'Dokumen yang sudah diverifikasi tidak dapat dihapus.');

        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Dokumen administrasi OSIPP dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, string $periodId, bool $isUpdate = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'document_type' => ['required', Rule::in(array_keys(OsippDocument::TYPE_OPTIONS))],
            'program_id' => [
                'nullable',
                'uuid',
                Rule::exists('osipp_programs', 'id')->where(fn ($q) => $q->where('period_id', $periodId)),
            ],
            'activity_id' => [
                'nullable',
                'uuid',
                Rule::exists('osipp_activities', 'id')->where(fn ($q) => $q->where('period_id', $periodId)),
            ],
            'external_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'file' => [$isUpdate ? 'nullable' : 'nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);
    }
}
