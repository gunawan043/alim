<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waka;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\GtkEmployment;
use App\Models\GtkProfile;
use App\Models\GtkWorkUnit;
use App\Models\SupervisiSk;
use App\Models\SupervisiTimAnggota;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupervisiSkController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan tidak ditemukan.');

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $selectedYearId = $request->input('academic_year_id', AcademicYear::where('is_active', true)->value('id'));

        $sks = SupervisiSk::with(['academicYear:id,name'])
            ->withCount('anggota')
            ->where('school_id', $schoolId)
            ->when($selectedYearId, fn ($q) => $q->where('academic_year_id', $selectedYearId))
            ->orderByDesc('tanggal')
            ->get();

        return view('waka.supervisi.sk-index', compact('sks', 'academicYears', 'selectedYearId'));
    }

    public function store(Request $request)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        abort_unless($schoolId, 403, 'Konteks satuan pendidikan tidak ditemukan.');

        $validated = $request->validate([
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'semester' => 'required|integer|in:1,2',
            'nomor' => 'required|string|max:100',
            'tanggal' => 'required|date',
            'menimbang' => 'nullable|string|max:5000',
            'mengingat' => 'nullable|string|max:5000',
        ]);

        $exists = SupervisiSk::where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'semester' => 'SK untuk tahun ajaran dan semester tersebut sudah ada.',
            ]);
        }

        $sk = SupervisiSk::create($validated + ['school_id' => $schoolId]);

        return redirect()
            ->route('waka.supervisi-sk.edit', $sk->id)
            ->with('success', 'SK dibuat. Tambahkan anggota tim supervisi.');
    }

    public function edit(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::with(['academicYear:id,name', 'anggota.gtk.user:id,name'])
            ->where('school_id', $schoolId)
            ->findOrFail($id);

        $academicYears = AcademicYear::orderByDesc('start_date')->get();
        $gtks = $this->gtkOptions($schoolId);

        return view('waka.supervisi.sk-edit', compact('sk', 'academicYears', 'gtks'));
    }

    public function update(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
            'semester' => 'required|integer|in:1,2',
            'nomor' => 'required|string|max:100',
            'tanggal' => 'required|date',
            'menimbang' => 'nullable|string|max:5000',
            'mengingat' => 'nullable|string|max:5000',
        ]);

        $duplicate = SupervisiSk::where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('semester', $validated['semester'])
            ->where('id', '!=', $sk->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'semester' => 'SK untuk tahun ajaran dan semester tersebut sudah ada.',
            ]);
        }

        $sk->update($validated);

        return back()->with('success', 'Identitas SK diperbarui.');
    }

    public function destroy(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::where('school_id', $schoolId)->findOrFail($id);
        $sk->delete();

        return redirect()->route('waka.supervisi-sk.index')->with('success', 'SK dihapus.');
    }

    public function storeMember(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::where('school_id', $schoolId)->findOrFail($id);

        $validated = $request->validate([
            'gtk_profile_id' => 'required|uuid|exists:gtk_profiles,id',
            'peran' => 'required|in:ksp,wksp,guru',
            'urutan' => 'nullable|integer|min:0|max:99',
        ]);

        $this->ensureGtkBelongsToSchool($schoolId, $validated['gtk_profile_id']);

        $exists = SupervisiTimAnggota::where('supervisi_sk_id', $sk->id)
            ->where('gtk_profile_id', $validated['gtk_profile_id'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'gtk_profile_id' => 'GTK ini sudah menjadi anggota tim pada SK tersebut.',
            ]);
        }

        SupervisiTimAnggota::create([
            'supervisi_sk_id' => $sk->id,
            'gtk_profile_id' => $validated['gtk_profile_id'],
            'peran' => $validated['peran'],
            'urutan' => $validated['urutan'] ?? 0,
        ]);

        return back()->with('success', 'Anggota tim ditambahkan.');
    }

    public function destroyMember(Request $request, string $id, string $memberId)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::where('school_id', $schoolId)->findOrFail($id);
        $member = SupervisiTimAnggota::where('supervisi_sk_id', $sk->id)->findOrFail($memberId);
        $member->delete();

        return back()->with('success', 'Anggota tim dihapus.');
    }

    public function cetak(Request $request, string $id)
    {
        $schoolId = $request->attributes->get('schoolContextId');
        $sk = SupervisiSk::with(['academicYear', 'anggota.gtk.user', 'school'])
            ->where('school_id', $schoolId)
            ->findOrFail($id);

        return view('waka.supervisi.sk-cetak', compact('sk'));
    }

    private function gtkOptions(string $schoolId)
    {
        $userIds = GtkEmployment::query()
            ->where('school_id', $schoolId)
            ->pluck('user_id')
            ->merge(
                GtkWorkUnit::query()
                    ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                    ->pluck('user_id')
            )
            ->unique();

        return GtkProfile::query()
            ->with(['user:id,name', 'latestEmployment:id,user_id,nupy,jabatan'])
            ->whereIn('user_id', $userIds)
            ->get()
            ->sortBy(fn ($gtk) => mb_strtolower((string) $gtk->user?->name))
            ->values();
    }

    private function ensureGtkBelongsToSchool(string $schoolId, string $gtkProfileId): void
    {
        $userIds = GtkProfile::whereKey($gtkProfileId)->pluck('user_id');
        $exists = GtkEmployment::where('school_id', $schoolId)->whereIn('user_id', $userIds)->exists()
            || GtkWorkUnit::whereIn('user_id', $userIds)
                ->whereHas('workUnit.school', fn ($q) => $q->where('id', $schoolId))
                ->exists();

        abort_unless($exists, 422, 'GTK tidak terdaftar pada satuan pendidikan ini.');
    }
}
