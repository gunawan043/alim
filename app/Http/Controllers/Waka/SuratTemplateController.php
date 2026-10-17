<?php

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\SuratTemplate;
use Illuminate\Http\Request;

class SuratTemplateController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);

        $templates = SuratTemplate::query()
            ->where(fn ($q) => $q->where('school_id', $schoolId)->orWhereNull('school_id'))
            ->orderBy('nama')
            ->get();

        return view('waka.surat-template.index', compact('templates', 'schoolId'));
    }

    public function store(Request $request)
    {
        $schoolId = $this->resolveSchoolId($request);
        abort_unless($schoolId, 403, 'Satuan pendidikan tidak terdeteksi.');

        $validated = $this->validated($request);
        $validated['school_id'] = $schoolId;

        SuratTemplate::create($validated);

        return back()->with('success', 'Template surat berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $this->resolveSchoolId($request);
        $template = SuratTemplate::where('school_id', $schoolId)->findOrFail($id);

        $template->update($this->validated($request));

        return back()->with('success', 'Template surat berhasil diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $this->resolveSchoolId($request);
        SuratTemplate::where('school_id', $schoolId)->findOrFail($id)->delete();

        return back()->with('success', 'Template surat berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:191',
            'perihal_template' => 'nullable|string|max:255',
            'isi_template' => 'required|string|max:20000',
            'is_active' => 'boolean',
        ]);

        $validated['jenis'] = 'keluar';
        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }

    private function resolveSchoolId(Request $request): ?string
    {
        return $request->attributes->get('schoolContextId')
            ?? $request->user()?->employment?->school_id;
    }
}
